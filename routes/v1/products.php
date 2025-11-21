<?php

use App\Http\Controllers\Api\v1\ProductController;
use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS PÚBLICAS SIN PARÁMETROS
// ========================================
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);

// ========================================
// RUTAS PROTEGIDAS - Solo Super Admin
// ⚠️ IMPORTANTE: Definir ANTES de rutas públicas con {id}
// ========================================
Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
    // Rutas específicas (sin {id})
    Route::get('/products/recycle-bin', [ProductController::class, 'recycleBin']);
    Route::post('/products', [ProductController::class, 'store']);

    // Rutas con {id}
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::patch('/products/{id}/featured', [ProductController::class, 'toggleFeatured']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::delete('/products/{id}/force', [ProductController::class, 'forceDelete']);
    Route::post('/products/{id}/restore', [ProductController::class, 'restore']);
    Route::post('/products/{id}/stock', [ProductController::class, 'adjustStock']);
});

// ========================================
// RUTAS PÚBLICAS CON {id} - AL FINAL
// ========================================
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/{id}/stock-movements', [ProductController::class, 'stockMovements']);