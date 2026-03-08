<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IngredienteController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\RecetaController;

Route::get('/dashboard', [DashboardController::class, 'index']);
Route::apiResource('ingredientes', IngredienteController::class);
Route::post('/producciones', [ProduccionController::class, 'store']);
Route::get('/recetas', [RecetaController::class, 'index']);
Route::post('/recetas', [RecetaController::class, 'store']);





