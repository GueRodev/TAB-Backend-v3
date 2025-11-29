<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Analytics Controller
 *
 * Controlador para endpoints de análisis avanzado.
 * Proporciona métricas anuales, mensuales y comparativas.
 */
class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Obtiene el análisis completo de un año específico
     * GET /api/v1/analytics/yearly?year=2024
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function yearly(Request $request): JsonResponse
    {
        try {
            $year = $request->query('year', now()->year);

            // Validar que year sea un número válido
            if (!is_numeric($year) || $year < 2000 || $year > 2100) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro "year" debe ser un año válido entre 2000 y 2100',
                ], 400);
            }

            $analytics = $this->analyticsService->getYearlyAnalytics((int) $year);

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el análisis anual',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene el desglose mensual de un año
     * GET /api/v1/analytics/monthly-breakdown?year=2024
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function monthlyBreakdown(Request $request): JsonResponse
    {
        try {
            $year = $request->query('year', now()->year);

            // Validar que year sea un número válido
            if (!is_numeric($year) || $year < 2000 || $year > 2100) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro "year" debe ser un año válido entre 2000 y 2100',
                ], 400);
            }

            $breakdown = $this->analyticsService->getMonthlyBreakdown((int) $year);

            return response()->json([
                'success' => true,
                'data' => $breakdown,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el desglose mensual',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Compara dos años y retorna las diferencias
     * GET /api/v1/analytics/compare-years?year1=2023&year2=2024
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function compareYears(Request $request): JsonResponse
    {
        try {
            $year1 = $request->query('year1');
            $year2 = $request->query('year2');

            // Validar que ambos parámetros existan
            if (!$year1 || !$year2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requieren los parámetros "year1" y "year2"',
                ], 400);
            }

            // Validar que sean años válidos
            if (!is_numeric($year1) || $year1 < 2000 || $year1 > 2100 ||
                !is_numeric($year2) || $year2 < 2000 || $year2 > 2100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Los parámetros "year1" y "year2" deben ser años válidos entre 2000 y 2100',
                ], 400);
            }

            $comparison = $this->analyticsService->compareYears((int) $year1, (int) $year2);

            return response()->json([
                'success' => true,
                'data' => $comparison,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al comparar los años',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene el top de meses con mejores ventas de todos los tiempos
     * GET /api/v1/analytics/top-months?limit=12
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function topMonths(Request $request): JsonResponse
    {
        try {
            $limit = $request->query('limit', 12);

            // Validar que limit sea un número entre 1 y 120
            if (!is_numeric($limit) || $limit < 1 || $limit > 120) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro "limit" debe ser un número entre 1 y 120',
                ], 400);
            }

            $topMonths = $this->analyticsService->getTopMonthsAllTime((int) $limit);

            return response()->json([
                'success' => true,
                'data' => $topMonths,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mejores meses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
