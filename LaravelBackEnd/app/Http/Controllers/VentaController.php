<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function index()
    {
        $ventas = Venta::with('detalles.producto')->get();

        return response()->json($ventas);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $venta = Venta::create([
                'user_id' => $request->user_id,
                'total' => 0
            ]);

            $total = 0;

            foreach ($request->productos as $item) {

                $producto = Producto::findOrFail($item['producto_id']);

                if ($producto->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente");
                }

                $subtotal = $producto->precio * $item['cantidad'];

                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio' => $producto->precio
                ]);

                $producto->decrement('stock', $item['cantidad']);

                $total += $subtotal;
            }

            $venta->update([
                'total' => $total
            ]);

            return response()->json([
                'message' => 'Venta registrada',
                'venta' => $venta
            ]);
        });
    }
}