<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompraIngredienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IngredienteController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\RecetaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('api.token')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/reportes', [ReporteController::class, 'index'])->middleware('role:admin');

    Route::apiResource('ingredientes', IngredienteController::class)->only(['index', 'show']);
    Route::apiResource('ingredientes', IngredienteController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:admin');
    Route::apiResource('recetas', RecetaController::class)->only(['index', 'show', 'store', 'update', 'destroy']);

    Route::get('/compras-ingredientes', [CompraIngredienteController::class, 'index']);
    Route::post('/compras-ingredientes', [CompraIngredienteController::class, 'store'])->middleware('role:admin');

    Route::get('/producciones', [ProduccionController::class, 'index']);
    Route::get('/producciones/preview', [ProduccionController::class, 'preview']);
    Route::post('/producciones', [ProduccionController::class, 'store']);

    Route::get('/productos', [ProductoController::class, 'index']);
    Route::post('/productos', [ProductoController::class, 'store']);

    Route::get('/ventas', [VentaController::class, 'index']);
    Route::post('/ventas', [VentaController::class, 'store']);
});
