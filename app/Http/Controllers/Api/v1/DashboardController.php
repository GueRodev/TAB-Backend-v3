<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dashboard Controller
 *
 * Controlador para endpoints del dashboard principal.
 * Proporciona métricas en tiempo real para la vista de administración.
 */
class DashboardController extends Controller
{
    protected DashboardMetricsService $metricsService;

    public function __construct(DashboardMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Obtiene las métricas principales del dashboard (Overview)
     * GET /api/dashboard/overview
     *
     * @return JsonResponse
     */
    public function overview(): JsonResponse
    {
        try {
            $metrics = $this->metricsService->getOverviewMetrics();

            return response()->json([
                'success' => true,
                'data' => $metrics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las métricas del dashboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene la tendencia de ventas de los últimos N días
     * GET /api/dashboard/sales-trend?days=7
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function salesTrend(Request $request): JsonResponse
    {
        try {
            $days = $request->query('days', 7);

            // Validar que days sea un número entre 1 y 365
            if (!is_numeric($days) || $days < 1 || $days > 365) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro "days" debe ser un número entre 1 y 365',
                ], 400);
            }

            $trend = $this->metricsService->getSalesTrend((int) $days);

            return response()->json([
                'success' => true,
                'data' => $trend,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la tendencia de ventas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene los pedidos más recientes
     * GET /api/dashboard/recent-orders?limit=5
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recentOrders(Request $request): JsonResponse
    {
        try {
            $limit = $request->query('limit', 5);

            // Validar que limit sea un número entre 1 y 50
            if (!is_numeric($limit) || $limit < 1 || $limit > 50) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro "limit" debe ser un número entre 1 y 50',
                ], 400);
            }

            $orders = $this->metricsService->getRecentOrders((int) $limit);

            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pedidos recientes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene los productos más vendidos
     * GET /api/dashboard/top-products?limit=5
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function topProducts(Request $request): JsonResponse
    {
        try {
            $limit = $request->query('limit', 5);

            // Validar que limit sea un número entre 1 y 50
            if (!is_numeric($limit) || $limit < 1 || $limit > 50) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro "limit" debe ser un número entre 1 y 50',
                ], 400);
            }

            $products = $this->metricsService->getTopProducts((int) $limit);

            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los productos más vendidos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene el resumen rápido del dashboard
     * GET /api/dashboard/quick-summary
     *
     * @return JsonResponse
     */
    public function quickSummary(): JsonResponse
    {
        try {
            $summary = $this->metricsService->getQuickSummary();

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el resumen rápido',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
