<?php

use App\Http\Controllers\Api\v1\ClientOrderController;
use Illuminate\Support\Facades\Route;

// RUTAS PROTEGIDAS - Clientes autenticados
Route::middleware(['auth:sanctum'])->group(function () {
    // Listar pedidos del cliente autenticado
    Route::get('/orders', [ClientOrderController::class, 'index']);

    // Crear pedido online (desde carrito)
    Route::post('/orders', [ClientOrderController::class, 'store']);

    // Ver detalles de un pedido específico del cliente
    Route::get('/orders/{id}', [ClientOrderController::class, 'show']);
});
