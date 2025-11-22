<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\StockReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class StockMovementController extends Controller
{
    protected StockReservationService $stockService;

    public function __construct(StockReservationService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Verificar disponibilidad de stock para múltiples items
     * POST /api/v1/stock-movements/check-availability
     *
     * Request body:
     * {
     *   "items": [
     *     { "product_id": 1, "quantity": 2 },
     *     { "product_id": 3, "quantity": 1 }
     *   ]
     * }
     *
     * Response:
     * {
     *   "available": true/false,
     *   "errors": [
     *     {
     *       "product_id": 1,
     *       "product_name": "Producto X",
     *       "requested": 5,
     *       "available": 3,
     *       "message": "Stock insuficiente..."
     *     }
     *   ]
     * }
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $result = $this->stockService->checkAvailability($request->items);

            return response()->json([
                'success' => true,
                'available' => $result['available'],
                'errors' => $result['errors'],
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar disponibilidad',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
