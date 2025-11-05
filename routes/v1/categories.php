<?php

use App\Http\Controllers\Api\v1\CategoryController;
use Illuminate\Support\Facades\Route;

// RUTAS PÚBLICAS - Cualquiera puede acceder
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// RUTAS PROTEGIDAS - Solo Super Admin
Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::put('/categories/reorder', [CategoryController::class, 'reorder']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::delete('/categories/{id}/force', [CategoryController::class, 'forceDelete']);
    Route::post('/categories/{id}/restore', [CategoryController::class, 'restore']);
});