<?php

namespace App\Http\Controllers;

use App\Models\Ingrediente;
use App\Models\Produccion;
use App\Models\Receta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ReporteController extends Controller
{
    private const TIPO_INVENTARIO = 'inventario';
    private const TIPO_RECETAS = 'recetas';
    private const TIPO_PRODUCCION = 'produccion';

    private const TIPOS_REPORTE = [
        self::TIPO_INVENTARIO,
        self::TIPO_RECETAS,
        self::TIPO_PRODUCCION,
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo' => ['required', 'string', Rule::in(self::TIPOS_REPORTE)],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $tipo = (string) $validated['tipo'];

        if (
            $tipo === self::TIPO_PRODUCCION &&
            (empty($validated['fecha_inicio']) || empty($validated['fecha_fin']))
        ) {
            return response()->json([
                'message' => 'Para el reporte de produccion debes enviar fecha_inicio y fecha_fin.',
            ], 422);
        }

        $payload = match ($tipo) {
            self::TIPO_INVENTARIO => $this->buildInventarioReport(),
            self::TIPO_RECETAS => $this->buildRecetasReport(),
            self::TIPO_PRODUCCION => $this->buildProduccionReport(
                (string) $validated['fecha_inicio'],
                (string) $validated['fecha_fin']
            ),
        };

        return response()->json($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInventarioReport(): array
    {
        $items = Ingrediente::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'stock_libras', 'stock_minimo'])
            ->map(function (Ingrediente $ingrediente): array {
                $stockActual = round((float) $ingrediente->stock_libras, 2);
                $stockMinimo = round((float) $ingrediente->stock_minimo, 2);

                return [
                    'ingrediente_id' => $ingrediente->id,
                    'nombre_ingrediente' => $ingrediente->nombre,
                    'stock_actual_libras' => $stockActual,
                    'stock_minimo_libras' => $stockMinimo,
                    'estado' => $this->resolveEstadoInventario($stockActual, $stockMinimo),
                ];
            })
            ->values();

        return [
            'tipo' => self::TIPO_INVENTARIO,
            'generated_at' => now()->toIso8601String(),
            'filtros' => (object) [],
            'resumen' => [
                'total_ingredientes' => (int) $items->count(),
                'stock_total_libras' => round((float) $items->sum('stock_actual_libras'), 2),
            ],
            'items' => $items->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRecetasReport(): array
    {
        $recetas = Receta::query()
            ->with('ingredientes:id,nombre')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $items = $recetas
            ->flatMap(function (Receta $receta): Collection {
                return $receta->ingredientes
                    ->map(function (Ingrediente $ingrediente) use ($receta): array {
                        return [
                            'receta_id' => $receta->id,
                            'nombre_receta' => $receta->nombre,
                            'ingrediente_id' => $ingrediente->id,
                            'nombre_ingrediente' => $ingrediente->nombre,
                            'cantidad_utilizada_libras' => round((float) $ingrediente->pivot->cantidad_libras, 2),
                        ];
                    });
            })
            ->values();

        return [
            'tipo' => self::TIPO_RECETAS,
            'generated_at' => now()->toIso8601String(),
            'filtros' => (object) [],
            'resumen' => [
                'total_recetas' => (int) $recetas->count(),
                'total_detalles_ingredientes' => (int) $items->count(),
            ],
            'items' => $items->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProduccionReport(string $fechaInicio, string $fechaFin): array
    {
        $producciones = Produccion::query()
            ->with(['receta:id,nombre', 'receta.ingredientes:id,nombre'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderBy('fecha')
            ->orderBy('id')
            ->get(['id', 'receta_id', 'cantidad', 'fecha']);

        $items = $producciones
            ->flatMap(function (Produccion $produccion): Collection {
                $ingredientes = $produccion->receta?->ingredientes ?? collect();

                return $ingredientes
                    ->map(function (Ingrediente $ingrediente) use ($produccion): array {
                        $cantidadUtilizada = round(
                            ((float) $ingrediente->pivot->cantidad_libras) * (int) $produccion->cantidad,
                            2
                        );

                        return [
                            'produccion_id' => $produccion->id,
                            'fecha' => $produccion->fecha,
                            'receta_id' => $produccion->receta_id,
                            'nombre_receta' => $produccion->receta?->nombre,
                            'cantidad_producida' => (int) $produccion->cantidad,
                            'ingrediente_id' => $ingrediente->id,
                            'nombre_ingrediente' => $ingrediente->nombre,
                            'cantidad_utilizada_libras' => $cantidadUtilizada,
                        ];
                    });
            })
            ->values();

        $resumenIngredientes = $items
            ->groupBy('ingrediente_id')
            ->map(function (Collection $rows): array {
                /** @var array<string, mixed> $first */
                $first = $rows->first();

                return [
                    'ingrediente_id' => $first['ingrediente_id'],
                    'nombre_ingrediente' => $first['nombre_ingrediente'],
                    'cantidad_total_utilizada_libras' => round((float) $rows->sum('cantidad_utilizada_libras'), 2),
                ];
            })
            ->sortBy('nombre_ingrediente')
            ->values();

        return [
            'tipo' => self::TIPO_PRODUCCION,
            'generated_at' => now()->toIso8601String(),
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ],
            'resumen' => [
                'total_lotes' => (int) $producciones->count(),
                'total_unidades_producidas' => (int) $producciones->sum('cantidad'),
                'total_detalles_ingredientes' => (int) $items->count(),
                'consumo_total_libras' => round((float) $items->sum('cantidad_utilizada_libras'), 2),
            ],
            'items' => $items->all(),
            'resumen_ingredientes' => $resumenIngredientes->all(),
        ];
    }

    private function resolveEstadoInventario(float $stockActual, float $stockMinimo): string
    {
        if ($stockActual <= 0) {
            return 'agotado';
        }

        if ($stockActual <= $stockMinimo) {
            return 'bajo_stock';
        }

        return 'normal';
    }
}
