<?php

namespace App\Http\Controllers;

use App\Models\Receta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecetaController extends Controller
{
    public function index(): JsonResponse
    {
        $recetas = Receta::query()
            ->with(['ingredientes' => function ($query) {
                $query->select('ingredientes.id', 'ingredientes.nombre', 'ingredientes.unidad_medida', 'ingredientes.stock_libras');
            }])
            ->orderBy('nombre')
            ->get();

        return response()->json($recetas);
    }

    public function show(Receta $receta): JsonResponse
    {
        $receta->load(['ingredientes' => function ($query) {
            $query->select('ingredientes.id', 'ingredientes.nombre', 'ingredientes.unidad_medida', 'ingredientes.stock_libras');
        }]);

        return response()->json($receta);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRecetaPayload($request);

        $receta = DB::transaction(function () use ($validated) {
            $receta = Receta::create([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
            ]);

            $receta->ingredientes()->sync($this->mapIngredientesPivot($validated['ingredientes']));

            return $receta->load(['ingredientes' => function ($query) {
                $query->select('ingredientes.id', 'ingredientes.nombre', 'ingredientes.unidad_medida', 'ingredientes.stock_libras');
            }]);
        });

        return response()->json([
            'message' => 'Receta creada correctamente',
            'receta' => $receta,
        ], 201);
    }

    public function update(Request $request, Receta $receta): JsonResponse
    {
        $validated = $this->validateRecetaPayload($request, $receta->id);

        $receta = DB::transaction(function () use ($receta, $validated) {
            $receta->update([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
            ]);

            $receta->ingredientes()->sync($this->mapIngredientesPivot($validated['ingredientes']));

            return $receta->load(['ingredientes' => function ($query) {
                $query->select('ingredientes.id', 'ingredientes.nombre', 'ingredientes.unidad_medida', 'ingredientes.stock_libras');
            }]);
        });

        return response()->json([
            'message' => 'Receta actualizada correctamente',
            'receta' => $receta,
        ]);
    }

    public function destroy(Receta $receta): JsonResponse
    {
        $receta->delete();

        return response()->json([
            'message' => 'Receta eliminada correctamente',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRecetaPayload(Request $request, ?int $recetaId = null): array
    {
        $nombreRule = [
            'required',
            'string',
            'max:120',
            Rule::unique('recetas', 'nombre')->ignore($recetaId),
        ];

        return $request->validate([
            'nombre' => $nombreRule,
            'descripcion' => ['nullable', 'string', 'max:500'],
            'ingredientes' => ['required', 'array', 'min:1'],
            'ingredientes.*.ingrediente_id' => ['required', 'integer', 'distinct', 'exists:ingredientes,id'],
            'ingredientes.*.cantidad' => ['required', 'numeric', 'min:0.01'],
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $ingredientes
     * @return array<int, array<string, float>>
     */
    private function mapIngredientesPivot(array $ingredientes): array
    {
        return collect($ingredientes)
            ->mapWithKeys(function (array $item): array {
                return [
                    (int) $item['ingrediente_id'] => [
                        'cantidad_libras' => round((float) $item['cantidad'], 2),
                    ],
                ];
            })
            ->all();
    }
}
