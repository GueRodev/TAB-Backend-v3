<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\AddressController;

// ========================================
// RUTAS DE DIRECCIONES (CLIENTES)
// ========================================
// Solo usuarios autenticados con rol Cliente
Route::middleware(['auth:sanctum', 'role:Cliente'])->group(function () {
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::get('/addresses/{address}', [AddressController::class, 'show']);
    Route::put('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);

    // Ruta adicional para marcar dirección como predeterminada
    Route::post('/addresses/{address}/set-default', [AddressController::class, 'setDefault']);
});
