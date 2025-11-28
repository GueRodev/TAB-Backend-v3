<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\NotificationController;

// ========================================
// RUTAS PROTEGIDAS - Super Admin y Moderador
// ========================================
Route::middleware(['auth:sanctum', 'role:Super Admin|Moderador'])->group(function () {
    // Listar todas las notificaciones del usuario autenticado
    Route::get('/notifications', [NotificationController::class, 'index']);

    // Obtener contador de notificaciones no leídas
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // Marcar una notificación específica como leída
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Marcar todas las notificaciones como leídas
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Eliminar una notificación específica (soft delete)
    // COMENTADO: No utilizamos soft delete por el momento
    // Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Eliminar permanentemente una notificación (force delete)
    Route::delete('/notifications/{id}/force', [NotificationController::class, 'forceDelete']);
});
