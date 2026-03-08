<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IngredienteController;
use App\Http\Controllers\RecetaController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\VentaController;

Route::get('/dashboard', [DashboardController::class, 'index']);
Route::apiResource('ingredientes', IngredienteController::class);
Route::get('/recetas', [RecetaController::class, 'index']);
Route::post('/recetas', [RecetaController::class, 'store']);
Route::get('/producciones', [ProduccionController::class, 'index']);
Route::post('/producciones', [ProduccionController::class, 'store']);
Route::get('/productos', [ProductoController::class, 'index']);
Route::post('/productos', [ProductoController::class, 'store']);
Route::get('/ventas', [VentaController::class, 'index']);
Route::post('/ventas', [VentaController::class, 'store']);




