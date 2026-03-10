<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produccion;
use App\Models\Receta;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;

class ProduccionController extends Controller
{
    //
    public function index()
    {
        $producciones = Produccion::with('receta')->get();

        return response()->json($producciones);
    }
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
    /*
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

            // Buscar producto asociado a la receta
            $producto = Producto::where('receta_id', $receta->id)->first();

            if ($producto) {
                $producto->increment('stock', $request->cantidad);
            }

            throw new \Exception(
                'Inventario insuficiente para el ingrediente: ' . $ingrediente->nombre
            );
        });
    }
    */
    public function store(Request $request)
    {
        $request->validate([
            'receta_id' => 'required|exists:recetas,id',
            'cantidad' => 'required|numeric|min:1',
            'fecha' => 'required|date'
        ]);

        try {

            $produccion = DB::transaction(function () use ($request) {

                $receta = Receta::with('ingredientes')
                    ->findOrFail($request->receta_id);

                // Verificar inventario
                foreach ($receta->ingredientes as $ingrediente) {

                    $cantidadNecesaria =
                        $ingrediente->pivot->cantidad_libras * $request->cantidad;

                    if ($ingrediente->stock_libras < $cantidadNecesaria) {

                        throw new \Exception(
                            'Inventario insuficiente para ' . $ingrediente->nombre
                        );
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

                // Aumentar stock del producto
                /*
                $producto = Producto::where('receta_id', $receta->id)->first();

                if ($producto) {
                    $producto->increment('stock', $request->cantidad);
                }
                */
                // Buscar producto o crearlo automáticamente
                $producto = Producto::where('receta_id', $receta->id)
                    ->lockForUpdate()
                    ->orderByDesc('precio')
                    ->orderByDesc('id')
                    ->first();

                if (!$producto) {
                    $producto = Producto::create([
                        'receta_id' => $receta->id,
                        'nombre' => $receta->nombre,
                        'descripcion' => 'Producto generado automaticamente desde produccion',
                        'precio' => 0,
                        'stock' => 0
                    ]);
                }

                // Aumentar stock del producto
                $producto->increment('stock', $request->cantidad);
                
                return $produccion;
            });

            return response()->json([
                'message' => 'Producción registrada correctamente',
                'produccion' => $produccion
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
