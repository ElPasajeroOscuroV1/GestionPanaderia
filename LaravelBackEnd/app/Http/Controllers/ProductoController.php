<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with('receta')->get();

        return response()->json($productos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'precio' => 'required|numeric',
            'receta_id' => 'required|exists:recetas,id'
        ]);

        $producto = Producto::updateOrCreate(
            ['receta_id' => $request->receta_id],
            [
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio' => $request->precio
            ]
        );

        return response()->json([
            'message' => 'Producto guardado correctamente',
            'producto' => $producto
        ]);
    }
}
