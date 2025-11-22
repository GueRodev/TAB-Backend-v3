<?php

use App\Http\Controllers\Api\v1\StockMovementController;
use Illuminate\Support\Facades\Route;

// RUTAS PROTEGIDAS - Usuarios autenticados
Route::middleware(['auth:sanctum'])->group(function () {
    // Verificar disponibilidad de stock antes de crear pedido
    Route::post('/stock-movements/check-availability', [StockMovementController::class, 'checkAvailability']);
});
