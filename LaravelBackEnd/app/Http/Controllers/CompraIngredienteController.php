<?php

namespace App\Http\Controllers;

use App\Models\CompraIngrediente;
use App\Models\Ingrediente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraIngredienteController extends Controller
{
    public function index(): JsonResponse
    {
        $compras = CompraIngrediente::query()
            ->with('ingrediente:id,nombre,unidad_medida')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json($compras);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ingrediente_id' => ['required', 'integer', 'exists:ingredientes,id'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['nullable', 'date'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ]);

        $resultado = DB::transaction(function () use ($validated): array {
            $ingrediente = Ingrediente::query()
                ->lockForUpdate()
                ->findOrFail((int) $validated['ingrediente_id']);

            $cantidad = round((float) $validated['cantidad'], 2);

            $compra = CompraIngrediente::create([
                'ingrediente_id' => $ingrediente->id,
                'cantidad' => $cantidad,
                'fecha' => $validated['fecha'] ?? now()->toDateString(),
                'observacion' => $validated['observacion'] ?? null,
            ]);

            $ingrediente->increment('stock_libras', $cantidad);
            $ingrediente->refresh();

            return [
                'compra' => $compra->load('ingrediente:id,nombre,unidad_medida'),
                'ingrediente' => $ingrediente,
            ];
        });

        return response()->json([
            'message' => 'Compra registrada correctamente.',
            'compra' => $resultado['compra'],
            'ingrediente' => $resultado['ingrediente'],
        ], 201);
    }
}
