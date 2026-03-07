<?php

namespace App\Http\Controllers;

use App\Models\Ingrediente;
use App\Models\Receta;
use App\Models\Produccion;

class DashboardController extends Controller
{
    public function index()
    {
        $ingredientes = Ingrediente::count();
        $recetas = Receta::count();

        $produccionHoy = Produccion::whereDate('fecha', now())->sum('cantidad');

        return response()->json([
            'ingredientes' => $ingredientes,
            'recetas' => $recetas,
            'produccion_hoy' => $produccionHoy
        ]);
    }
}