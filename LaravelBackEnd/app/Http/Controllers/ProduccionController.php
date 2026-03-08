<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produccion;
use App\Models\Receta;
use App\Models\Ingrediente;
use Illuminate\Support\Facades\DB;

class ProduccionController extends Controller
{
    //
    /*
    public function store(Request $request)
    {
        $request->validate([
            'receta_id' => 'required|exists:recetas,id',
            'cantidad' => 'required|numeric|min:1',
            'fecha' => 'required|date'
        ]);

        $receta = Receta::with('ingredientes')->findOrFail($request->receta_id);

        // Verificar inventario antes de producir
        foreach ($receta->ingredientes as $ingrediente) {

            $cantidadNecesaria =
                $ingrediente->pivot->cantidad_libras * $request->cantidad;

            if ($ingrediente->stock_libras < $cantidadNecesaria) {

                return response()->json([
                    'error' => 'Inventario insuficiente para producir',
                    'ingrediente' => $ingrediente->nombre,
                    'stock_actual' => $ingrediente->stock_libras,
                    'necesario' => $cantidadNecesaria
                ], 400);
            }
        }

        // Crear producción
        $produccion = Produccion::create([
            'receta_id' => $receta->id,
            'cantidad' => $request->cantidad,
            'fecha' => now()
        ]);

        // Descontar ingredientes
        foreach ($receta->ingredientes as $ingrediente) {

            $cantidadNecesaria =
                $ingrediente->pivot->cantidad_libras * $request->cantidad;

            $ingrediente->stock_libras -= $cantidadNecesaria;

            $ingrediente->save();
        }

        return response()->json([
            'message' => 'Producción registrada',
            'produccion' => $produccion
        ]);
    }
    */
    public function store(Request $request)
    {
        $request->validate([
            'receta_id' => 'required|exists:recetas,id',
            'cantidad' => 'required|numeric|min:1',
            'fecha' => 'required|date'
        ]);

        return DB::transaction(function () use ($request) {

            $receta = Receta::with('ingredientes')
                ->findOrFail($request->receta_id);

            // Verificar inventario
            foreach ($receta->ingredientes as $ingrediente) {

                $cantidadNecesaria =
                    $ingrediente->pivot->cantidad_libras * $request->cantidad;

                if ($ingrediente->stock_libras < $cantidadNecesaria) {

                    return response()->json([
                        'error' => 'Inventario insuficiente',
                        'ingrediente' => $ingrediente->nombre,
                        'stock_actual' => $ingrediente->stock_libras,
                        'necesario' => $cantidadNecesaria
                    ], 400);
                }
            }

            // Crear producción
            $produccion = Produccion::create([
                'receta_id' => $receta->id,
                'cantidad' => $request->cantidad,
                'fecha' => $request->fecha
            ]);

            // Descontar ingredientes
            foreach ($receta->ingredientes as $ingrediente) {

                $cantidadNecesaria =
                    $ingrediente->pivot->cantidad_libras * $request->cantidad;

                $ingrediente->decrement(
                    'stock_libras',
                    $cantidadNecesaria
                );
            }

            return response()->json([
                'message' => 'Producción registrada correctamente',
                'produccion' => $produccion
            ]);
        });
    }
}
