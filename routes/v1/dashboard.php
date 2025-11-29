<?php

use App\Http\Controllers\Api\v1\DashboardController;
use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS PROTEGIDAS - Dashboard
// Requiere autenticación y rol Super Admin
// ========================================
Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
    // Métricas principales del dashboard
    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);

    // Tendencia de ventas (últimos N días)
    Route::get('/dashboard/sales-trend', [DashboardController::class, 'salesTrend']);

    // Pedidos recientes
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'recentOrders']);

    // Productos más vendidos
    Route::get('/dashboard/top-products', [DashboardController::class, 'topProducts']);

    // Resumen rápido
    Route::get('/dashboard/quick-summary', [DashboardController::class, 'quickSummary']);
});
