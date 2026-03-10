<?php

namespace App\Http\Controllers;

use App\Models\Ingrediente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IngredienteController extends Controller
{
    public function index(): JsonResponse
    {
        $ingredientes = Ingrediente::query()
            ->orderBy('nombre')
            ->get();

        return response()->json($ingredientes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:120', 'unique:ingredientes,nombre'],
            'unidad_medida' => ['required', Rule::in(Ingrediente::UNIDADES_MEDIDA)],
            'stock_libras' => ['required', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
        ]);

        $payload = $validated;
        $payload['stock_minimo'] = isset($validated['stock_minimo'])
            ? (float) $validated['stock_minimo']
            : 10;

        $ingrediente = Ingrediente::create($payload);

        return response()->json($ingrediente, 201);
    }

    public function show(Ingrediente $ingrediente): JsonResponse
    {
        return response()->json($ingrediente);
    }

    public function update(Request $request, Ingrediente $ingrediente): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('ingredientes', 'nombre')->ignore($ingrediente->id),
            ],
            'unidad_medida' => ['required', Rule::in(Ingrediente::UNIDADES_MEDIDA)],
            'stock_libras' => ['required', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
        ]);

        $payload = $validated;
        if (!array_key_exists('stock_minimo', $payload)) {
            $payload['stock_minimo'] = $ingrediente->stock_minimo;
        }

        $ingrediente->update($payload);

        return response()->json($ingrediente);
    }

    public function destroy(Ingrediente $ingrediente): JsonResponse
    {
        $ingrediente->delete();

        return response()->json([
            'message' => 'Ingrediente eliminado correctamente',
        ]);
    }
}
