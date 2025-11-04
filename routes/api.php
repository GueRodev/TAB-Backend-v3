<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestMailController;

// ========================================
// RUTA DE PRUEBA EMAIL
// ========================================
Route::post('/test-email', [TestMailController::class, 'sendTestEmail']);

// ========================================
// API VERSION 1
// ========================================
Route::prefix('v1')->group(function () {

    // ========================================
    // MÓDULO DE AUTENTICACIÓN
    // ========================================
    require base_path('routes/v1/auth.php');

    // ========================================
    // RUTAS PROTEGIDAS (Requieren autenticación)
    // ========================================
    Route::middleware('auth:sanctum')->group(function () {

        // Ruta de ejemplo (la que venía por defecto)
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // ========================================
        // RUTAS SOLO PARA SUPER ADMIN
        // (Rutas de prueba temporal para Postman)
        // ========================================
        Route::middleware('role:Super Admin')->group(function () {
            
            Route::prefix('admin')->group(function () {
                
                // Dashboard Admin
                Route::get('/dashboard', function (Request $request) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Bienvenido al Dashboard Admin',
                        'data' => [
                            'user' => $request->user()->name,
                            'role' => $request->user()->getRoleName(),
                        ],
                    ]);
                });
 
                // Gestión de productos (solo Super Admin)
                Route::get('/products', function (Request $request) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Gestión de productos - Solo Super Admin',
                        'data' => [
                            'access_level' => 'Super Admin',
                            'can_create' => $request->user()->can('create products'),
                            'can_edit' => $request->user()->can('edit products'),
                            'can_delete' => $request->user()->can('delete products'),
                        ],
                    ]);
                });

                // Gestión de usuarios (solo Super Admin)
                Route::get('/users', function (Request $request) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Gestión de usuarios - Solo Super Admin',
                        'data' => [
                            'access_level' => 'Super Admin',
                            'can_view' => $request->user()->can('view users'),
                            'can_create' => $request->user()->can('create users'),
                            'can_edit' => $request->user()->can('edit users'),
                            'can_delete' => $request->user()->can('delete users'),
                        ],
                    ]);
                });
            });
        });

        // ========================================
        // RUTAS PÚBLICAS AUTENTICADAS (Todos los usuarios)
        // ========================================
        
        // Ver productos (Cliente y Admin)
        Route::get('/products', function (Request $request) {
            return response()->json([
                'success' => true,
                'message' => 'Listado de productos - Acceso para usuarios autenticados',
                'data' => [
                    'user' => $request->user()->name,
                    'role' => $request->user()->getRoleName(),
                    'can_view' => $request->user()->can('view products'),
                    'can_create' => $request->user()->can('create products'),
                ],
            ]);
        });
    });

});