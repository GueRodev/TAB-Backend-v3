<?php

use App\Http\Controllers\Api\v1\CategoryController;
use Illuminate\Support\Facades\Route;

// RUTAS PÚBLICAS - Cualquiera puede acceder
Route::get('/categories', [CategoryController::class, 'index']);

// RUTAS PROTEGIDAS - Solo Super Admin
Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
    // ⚠️ RUTAS ESPECÍFICAS PRIMERO (antes de {id})
    Route::put('/categories/reorder', [CategoryController::class, 'reorder']);
    Route::get('/categories/recycle-bin', [CategoryController::class, 'recycleBin']);

    // RUTAS CON {id} DESPUÉS
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::post('/categories/{id}/restore', [CategoryController::class, 'restore']);
    Route::delete('/categories/{id}/force', [CategoryController::class, 'forceDelete']);
});

// RUTAS PÚBLICAS CON {id} - AL FINAL
Route::get('/categories/{id}', [CategoryController::class, 'show']);