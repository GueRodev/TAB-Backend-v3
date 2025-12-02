<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\PasswordResetController;

// ========================================
// RUTAS PÚBLICAS DE AUTENTICACIÓN
// ========================================
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// ========================================
// RUTAS PÚBLICAS DE RECUPERACIÓN DE CONTRASEÑA
// ========================================
Route::post('/auth/password/forgot', [PasswordResetController::class, 'sendResetLink']);
Route::post('/auth/password/reset', [PasswordResetController::class, 'reset']);

// ========================================
// RUTAS PROTEGIDAS DE AUTENTICACIÓN
// ========================================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
});