<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestMailController;

// RUTA DE PRUEBA EMAIL
Route::post('/test-email', [TestMailController::class, 'sendTestEmail']);

// ========================================
// API VERSION 1
// ========================================
Route::prefix('v1')->group(function () {

    // MÓDULO DE AUTENTICACIÓN
    require base_path('routes/v1/auth.php');

    // MÓDULO DE CATEGORÍAS
    require base_path('routes/v1/categories.php');  

    // MÓDULO DE PRODUCTOS
    require base_path('routes/v1/products.php');

    // MÓDULO DE PEDIDOS - CLIENTES
    require base_path('routes/v1/orders.php');

    // MÓDULO DE PEDIDOS - ADMIN
    require base_path('routes/v1/admin_orders.php');

   //Ruta de ejemplo para verificar si el usuario está autenticado
    Route::middleware('auth:sanctum')->group(function () {
        // Ruta de ejemplo (la que venía por defecto)
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });

});