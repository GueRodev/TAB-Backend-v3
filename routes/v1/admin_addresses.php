<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\AdminAddressController;

// ========================================
// RUTAS DE ADMIN PARA DIRECCIONES (SOLO LECTURA)
// ========================================
// Solo Super Admin - Ver direcciones de todos los usuarios
Route::middleware(['auth:sanctum', 'role:Super Admin'])->prefix('admin')->group(function () {
    // Listar todas las direcciones con filtros y paginación
    Route::get('/addresses', [AdminAddressController::class, 'index']);

    // Ver dirección específica
    Route::get('/addresses/{address}', [AdminAddressController::class, 'show']);

    // Ver todas las direcciones de un usuario específico
    Route::get('/users/{userId}/addresses', [AdminAddressController::class, 'byUser']);
});
