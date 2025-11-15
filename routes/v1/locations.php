<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\LocationController;

// ========================================
// RUTAS PÚBLICAS DE UBICACIONES
// ========================================
// Endpoint público (sin autenticación) para obtener ubicaciones de Costa Rica
Route::get('/locations/cr', [LocationController::class, 'getCrLocations']);
