<?php

use App\Http\Controllers\Api\v1\AdminOrderController;
use Illuminate\Support\Facades\Route;

// RUTAS PROTEGIDAS - Solo Super Admin
Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
    // Listar todos los pedidos (online + in_store)
    Route::get('/admin/orders', [AdminOrderController::class, 'index']);

    // Crear pedido en tienda física
    Route::post('/admin/orders', [AdminOrderController::class, 'store']);

    // Ver detalles de cualquier pedido
    Route::get('/admin/orders/{id}', [AdminOrderController::class, 'show']);

    // Marcar pedido como en progreso
    Route::patch('/admin/orders/{id}/mark-in-progress', [AdminOrderController::class, 'markInProgress']);

    // Completar pedido (confirma stock y envía email)
    Route::patch('/admin/orders/{id}/complete', [AdminOrderController::class, 'complete']);

    // Cancelar pedido (libera stock)
    Route::patch('/admin/orders/{id}/cancel', [AdminOrderController::class, 'cancel']);

    // Archivar pedido
    Route::post('/admin/orders/{id}/archive', [AdminOrderController::class, 'archive']);

    // Eliminar pedido (soft delete)
    Route::delete('/admin/orders/{id}', [AdminOrderController::class, 'destroy']);
});
