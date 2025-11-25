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

    // TEMPORALMENTE DESHABILITADO - No se está utilizando
    // // Marcar pedido como en progreso
    // Route::patch('/admin/orders/{id}/mark-in-progress', [AdminOrderController::class, 'markInProgress']);

    // Completar pedido (confirma stock y envía email)
    Route::patch('/admin/orders/{id}/complete', [AdminOrderController::class, 'complete']);

    // Cancelar pedido (libera stock)
    Route::patch('/admin/orders/{id}/cancel', [AdminOrderController::class, 'cancel']);

    // TEMPORALMENTE DESHABILITADO - No se está utilizando
    // // Archivar pedido
    // Route::post('/admin/orders/{id}/archive', [AdminOrderController::class, 'archive']);

    // TEMPORALMENTE DESHABILITADO - No se está utilizando
    // // Desarchivar pedido
    // Route::patch('/admin/orders/{id}/unarchive', [AdminOrderController::class, 'unarchive']);

    // Eliminar pedido (soft delete)
    Route::delete('/admin/orders/{id}', [AdminOrderController::class, 'destroy']);

    // Listar pedidos eliminados (soft deleted)
    Route::get('/admin/orders-trashed', [AdminOrderController::class, 'trashed']);

    // TEMPORALMENTE DESHABILITADO - No se está utilizando
    // // Restaurar pedido eliminado
    // Route::patch('/admin/orders/{id}/restore', [AdminOrderController::class, 'restore']);

    // TEMPORALMENTE DESHABILITADO - No se está utilizando
    // // Eliminar permanentemente un pedido (force delete - solo desde papelera)
    // Route::delete('/admin/orders/{id}/force', [AdminOrderController::class, 'forceDelete']);
});
