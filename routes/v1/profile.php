<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\ProfileController;

// ========================================
// RUTAS DE PERFIL (AUTENTICADAS)
// ========================================
// Solo usuarios autenticados (Super Admin + Cliente)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
});
