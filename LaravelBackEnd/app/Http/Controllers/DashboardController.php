<?php

namespace App\Http\Controllers;

use App\Models\Ingrediente;
use App\Models\Producto;
use App\Models\Produccion;
use App\Models\Receta;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $ingredientes = Ingrediente::count();
        $recetas = Receta::count();
        $produccionHoy = Produccion::whereDate('fecha', today())->sum('cantidad');
        $productosEnStock = (int) Producto::sum('stock');

        $ingredientesBajoStockQuery = Ingrediente::query()
            ->whereColumn('stock_libras', '<=', 'stock_minimo')
            ->orderBy('stock_libras')
            ->orderBy('nombre');

        $ingredientesBajoStockTotal = (clone $ingredientesBajoStockQuery)->count();

        $ingredientesBajoStock = $ingredientesBajoStockQuery
            ->limit(6)
            ->get(['id', 'nombre', 'unidad_medida', 'stock_libras', 'stock_minimo']);

        $produccionesRecientes = Produccion::query()
            ->with('receta:id,nombre')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'receta_id', 'cantidad', 'fecha', 'created_at']);

        return response()->json([
            'ingredientes' => $ingredientes,
            'recetas' => $recetas,
            'produccion_hoy' => (int) $produccionHoy,
            'productos_en_stock' => $productosEnStock,
            'ingredientes_bajo_stock_total' => $ingredientesBajoStockTotal,
            'ingredientes_bajo_stock' => $ingredientesBajoStock,
            'producciones_recientes' => $produccionesRecientes,
        ]);
    }
}
