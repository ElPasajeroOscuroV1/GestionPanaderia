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
}
