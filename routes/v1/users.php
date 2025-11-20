<?php

use App\Http\Controllers\Api\v1\UserController;
use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS DE GESTIÓN DE USUARIOS
// ========================================
// SOLO Super Admin puede gestionar usuarios Admin y Moderador
// Los usuarios Cliente NO se gestionan por aquí (se registran ellos mismos)

Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {

    // Listar todos los usuarios Admin y Moderador
    // GET /api/v1/users
    Route::get('/users', [UserController::class, 'index']);

    // Crear nuevo usuario Admin o Moderador
    // POST /api/v1/users
    // Body: { name, email, password, password_confirmation, role }
    Route::post('/users', [UserController::class, 'store']);

    // Ver detalles de un usuario específico
    // GET /api/v1/users/{id}
    Route::get('/users/{user}', [UserController::class, 'show']);

    // Ver permisos de un usuario específico
    // GET /api/v1/users/{id}/permissions
    Route::get('/users/{user}/permissions', [UserController::class, 'getPermissions']);

    // Actualizar usuario existente
    // PUT/PATCH /api/v1/users/{id}
    // Body: { name?, email?, password?, password_confirmation?, role? }
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::patch('/users/{user}', [UserController::class, 'update']);

    // Eliminar usuario
    // DELETE /api/v1/users/{id}
    // VALIDACIONES:
    // - No se puede eliminar el último Super Admin
    // - No se puede eliminar a sí mismo
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});
