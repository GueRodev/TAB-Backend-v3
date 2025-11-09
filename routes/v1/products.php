<?php

use App\Http\Controllers\Api\v1\ProductController;
use Illuminate\Support\Facades\Route;

// RUTAS PÚBLICAS - Cualquiera puede ver
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// RUTAS PROTEGIDAS - Solo Super Admin
Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::delete('/products/{id}/force', [ProductController::class, 'forceDelete']);
    Route::post('/products/{id}/restore', [ProductController::class, 'restore']);
    Route::post('/products/{id}/stock', [ProductController::class, 'adjustStock']);
});