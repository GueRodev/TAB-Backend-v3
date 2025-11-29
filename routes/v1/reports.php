<?php

use App\Http\Controllers\Api\v1\ReportsController;
use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS PROTEGIDAS - Reports
// Requiere autenticación y rol Super Admin
// ========================================
Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {

    // ========================================
    // REPORTES DE VENTAS (JSON)
    // ========================================
    Route::get('/reports/sales', [ReportsController::class, 'sales']);
    Route::get('/reports/sales/monthly', [ReportsController::class, 'monthlySales']);

    // ========================================
    // REPORTES DE PRODUCTOS (JSON)
    // ========================================
    Route::get('/reports/products', [ReportsController::class, 'products']);
    Route::get('/reports/products/performance', [ReportsController::class, 'productsPerformance']);

    // ========================================
    // REPORTES DE ÓRDENES (JSON)
    // ========================================
    Route::get('/reports/orders', [ReportsController::class, 'orders']);
    Route::get('/reports/orders/audit', [ReportsController::class, 'ordersAudit']);
    Route::get('/reports/orders/pending', [ReportsController::class, 'ordersPending']);

    // ========================================
    // EXPORTACIONES A PDF
    // ========================================
    Route::get('/reports/sales/export/pdf', [ReportsController::class, 'exportSalesPDF']);
    Route::get('/reports/products/export/pdf', [ReportsController::class, 'exportProductsPDF']);
    Route::get('/reports/orders/export/pdf', [ReportsController::class, 'exportOrdersPDF']);

    // ========================================
    // EXPORTACIONES A EXCEL
    // ========================================
    Route::get('/reports/sales/export/excel', [ReportsController::class, 'exportSalesExcel']);
    Route::get('/reports/products/export/excel', [ReportsController::class, 'exportProductsExcel']);
    Route::get('/reports/orders/export/excel', [ReportsController::class, 'exportOrdersExcel']);
});
