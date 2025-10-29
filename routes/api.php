<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// ========================================
// API VERSION 1
// ========================================

Route::prefix('v1')->group(function () {// Prefijo de la versión de la API

    // ========================================
    // RUTAS PÚBLICAS (Sin autenticación)
    // ========================================

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);// Registrar nuevo usuario
        Route::post('/login', [AuthController::class, 'login']);// Iniciar sesión
    });

    // ========================================
    // RUTAS PROTEGIDAS (Requieren autenticación)
    // ========================================

    Route::middleware('auth:sanctum')->group(function () {
        
        // Rutas de autenticación
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']); // Obtener información del usuario autenticado
            Route::post('/logout', [AuthController::class, 'logout']); // Cerrar sesión en el dispositivo actual
            Route::post('/logout-all', [AuthController::class, 'logoutAll']); // Cerrar sesión en todos los dispositivos
        });

        // Ruta de ejemplo (la que venía por defecto)
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });

});