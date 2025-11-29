<?php

use App\Http\Controllers\Api\v1\AnalyticsController;
use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS PROTEGIDAS - Analytics
// Requiere autenticación y rol Super Admin
// ========================================
Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
    // Análisis anual completo
    Route::get('/analytics/yearly', [AnalyticsController::class, 'yearly']);

    // Desglose mensual de un año
    Route::get('/analytics/monthly-breakdown', [AnalyticsController::class, 'monthlyBreakdown']);

    // Comparación entre dos años
    Route::get('/analytics/compare-years', [AnalyticsController::class, 'compareYears']);

    // Top meses con mejores ventas
    Route::get('/analytics/top-months', [AnalyticsController::class, 'topMonths']);
});
