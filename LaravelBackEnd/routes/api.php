<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IngredienteController;


Route::apiResource('ingredientes', IngredienteController::class);