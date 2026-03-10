<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Receta;

class RecetaController extends Controller
{
    //
    public function index()
    {
        $recetas = Receta::with('ingredientes')->get();

        return response()->json($recetas);
    }
    /*
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string'
        ]);

        $receta = Receta::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion
        ]);

        return response()->json([
            'message' => 'Receta creada correctamente',
            'receta' => $receta
        ], 201);
    }
    */
    public function store(Request $request)
    {
        //dd($request->all());

        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'ingredientes' => 'array',
            //'ingredientes.*.ingrediente_id' => 'exists:ingredientes,id',
            //'ingredientes.*.cantidad_libras' => 'numeric|min:0'
        ]);

        $receta = Receta::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion
        ]);
        /*
        if ($request->ingredientes) {

            foreach ($request->ingredientes as $ingrediente) {

                $receta->ingredientes()->attach(
                    $ingrediente['ingrediente_id'],
                    ['cantidad_libras' => $ingrediente['cantidad_libras']]
                );

            }
        }
        */
        if ($request->has('ingredientes')) {
            foreach ($request->ingredientes as $ingrediente) {
                /*
                $receta->ingredientes()->attach(
                    $ingrediente['ingrediente_id'],
                    ['cantidad_libras' => $ingrediente['cantidad_libras']]
                );
                */
                $receta->ingredientes()->syncWithoutDetaching([
                    $ingrediente['ingrediente_id'] => [
                        'cantidad_libras' => $ingrediente['cantidad_libras']
                    ]
                ]);
            }
        }

        return response()->json([
            'message' => 'Receta creada correctamente',
            'receta' => $receta->load('ingredientes')
        ], 201);
    }
}
