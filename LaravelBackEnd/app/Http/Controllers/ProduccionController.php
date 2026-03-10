<?php

namespace App\Http\Controllers;

use App\Models\Ingrediente;
use App\Models\Produccion;
use App\Models\Producto;
use App\Models\Receta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProduccionController extends Controller
{
    public function index(): JsonResponse
    {
        $producciones = Produccion::query()
            ->with('receta:id,nombre')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return response()->json($producciones);
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receta_id' => ['required', 'exists:recetas,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $receta = Receta::query()
            ->with('ingredientes:id,nombre,unidad_medida,stock_libras')
            ->findOrFail((int) $validated['receta_id']);

        if ($receta->ingredientes->isEmpty()) {
            return response()->json([
                'can_produce' => false,
                'message' => 'La receta no tiene ingredientes configurados.',
                'consumo' => [],
            ], 422);
        }

        $analysis = $this->buildConsumptionAnalysis($receta, (int) $validated['cantidad']);

        return response()->json([
            'receta_id' => $receta->id,
            'receta' => $receta->nombre,
            'cantidad' => (int) $validated['cantidad'],
            'can_produce' => $analysis['can_produce'],
            'consumo' => $analysis['consumo'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receta_id' => ['required', 'exists:recetas,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'fecha' => ['nullable', 'date'],
        ]);

        try {
            $result = DB::transaction(function () use ($validated): array {
                $cantidad = (int) $validated['cantidad'];

                $receta = Receta::query()
                    ->with('ingredientes:id,nombre,unidad_medida,stock_libras')
                    ->findOrFail((int) $validated['receta_id']);

                if ($receta->ingredientes->isEmpty()) {
                    throw new \RuntimeException('La receta seleccionada no tiene ingredientes.');
                }

                $ingredienteIds = $receta->ingredientes->pluck('id')->all();

                $ingredientesConLock = Ingrediente::query()
                    ->whereIn('id', $ingredienteIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $analysis = $this->buildConsumptionAnalysis($receta, $cantidad, $ingredientesConLock);

                if (!$analysis['can_produce']) {
                    $faltantes = collect($analysis['consumo'])
                        ->where('insuficiente', true)
                        ->map(function (array $item): string {
                            $faltante = round(abs((float) $item['stock_restante']), 2);

                            return sprintf(
                                '%s (faltan %s %s)',
                                $item['ingrediente'],
                                $faltante,
                                $item['unidad_medida']
                            );
                        })
                        ->values()
                        ->implode(', ');

                    throw new \RuntimeException('Inventario insuficiente para: ' . $faltantes);
                }

                $produccion = Produccion::create([
                    'receta_id' => $receta->id,
                    'cantidad' => $cantidad,
                    'fecha' => $validated['fecha'] ?? now()->toDateString(),
                ]);

                foreach ($analysis['consumo'] as $item) {
                    Ingrediente::query()
                        ->where('id', $item['ingrediente_id'])
                        ->decrement('stock_libras', $item['cantidad_necesaria']);
                }

                $producto = Producto::firstOrCreate(
                    ['receta_id' => $receta->id],
                    [
                        'nombre' => $receta->nombre,
                        'descripcion' => 'Producto generado automaticamente desde produccion',
                        'precio' => 0,
                        'stock' => 0,
                    ]
                );

                $producto->increment('stock', $cantidad);

                return [
                    'produccion' => $produccion->load('receta:id,nombre'),
                    'consumo' => $analysis['consumo'],
                ];
            });

            return response()->json([
                'message' => 'Produccion registrada correctamente',
                'produccion' => $result['produccion'],
                'consumo' => $result['consumo'],
            ], 201);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * @param \Illuminate\Support\Collection<int, Ingrediente>|null $lockedIngredientes
     * @return array<string, mixed>
     */
    private function buildConsumptionAnalysis(Receta $receta, int $cantidad, $lockedIngredientes = null): array
    {
        $canProduce = true;

        $consumo = $receta->ingredientes
            ->map(function (Ingrediente $ingrediente) use ($cantidad, $lockedIngredientes, &$canProduce): array {
                $origen = $lockedIngredientes?->get($ingrediente->id) ?? $ingrediente;

                $cantidadNecesaria = round(((float) $ingrediente->pivot->cantidad_libras) * $cantidad, 2);
                $stockActual = round((float) $origen->stock_libras, 2);
                $stockRestante = round($stockActual - $cantidadNecesaria, 2);
                $insuficiente = $stockRestante < 0;

                if ($insuficiente) {
                    $canProduce = false;
                }

                return [
                    'ingrediente_id' => $ingrediente->id,
                    'ingrediente' => $ingrediente->nombre,
                    'unidad_medida' => $ingrediente->unidad_medida,
                    'stock_actual' => $stockActual,
                    'cantidad_necesaria' => $cantidadNecesaria,
                    'stock_restante' => $stockRestante,
                    'insuficiente' => $insuficiente,
                ];
            })
            ->values()
            ->all();

        return [
            'can_produce' => $canProduce,
            'consumo' => $consumo,
        ];
    }
}
