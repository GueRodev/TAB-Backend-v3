<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\CrLocation;
use Illuminate\Support\Facades\Cache;

class LocationController extends Controller
{
    /**
     * Obtener estructura jerárquica de ubicaciones de Costa Rica.
     * GET /api/v1/locations/cr
     *
     * Endpoint público (sin autenticación) para obtener
     * provincias > cantones > distritos de Costa Rica.
     */
    public function getCrLocations()
    {
        // Cachear por 24 horas (estos datos no cambian frecuentemente)
        $locations = Cache::remember('cr_locations_hierarchy', 60 * 60 * 24, function () {
            return CrLocation::getHierarchy();
        });

        return response()->json([
            'provincias' => $locations,
            'total_provincias' => count($locations)
        ]);
    }
}
