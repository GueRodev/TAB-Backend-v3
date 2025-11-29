<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\Reports\SalesReportService;
use App\Services\Reports\ProductsReportService;
use App\Services\Reports\OrdersReportService;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Reports Controller
 *
 * Controlador para generación y exportación de reportes.
 * Maneja reportes de ventas, productos y órdenes en formato JSON, PDF y Excel.
 */
class ReportsController extends Controller
{
    protected SalesReportService $salesReportService;
    protected ProductsReportService $productsReportService;
    protected OrdersReportService $ordersReportService;
    protected ReportExportService $exportService;

    public function __construct(
        SalesReportService $salesReportService,
        ProductsReportService $productsReportService,
        OrdersReportService $ordersReportService,
        ReportExportService $exportService
    ) {
        $this->salesReportService = $salesReportService;
        $this->productsReportService = $productsReportService;
        $this->ordersReportService = $ordersReportService;
        $this->exportService = $exportService;
    }

    // ========================================================================
    // REPORTES DE VENTAS (JSON)
    // ========================================================================

    /**
     * Genera reporte de ventas para un rango de fechas
     * GET /api/v1/reports/sales?start_date=2024-01-01&end_date=2024-12-31
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sales(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            $report = $this->salesReportService->generateReport($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte de ventas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera reporte mensual de ventas para un año
     * GET /api/v1/reports/sales/monthly?year=2024
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function monthlySales(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2000|max:2100',
            ]);

            $report = $this->salesReportService->generateMonthlyReport($validated['year']);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte mensual de ventas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ========================================================================
    // REPORTES DE PRODUCTOS (JSON)
    // ========================================================================

    /**
     * Genera reporte de inventario de productos
     * GET /api/v1/reports/products?category_id=1&status=active
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function products(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['category_id', 'status']);

            $report = $this->productsReportService->generateReport($filters);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte de productos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera reporte de rendimiento de productos
     * GET /api/v1/reports/products/performance?start_date=2024-01-01&end_date=2024-12-31
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function productsPerformance(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            $report = $this->productsReportService->generatePerformanceReport($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte de rendimiento de productos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ========================================================================
    // REPORTES DE ÓRDENES (JSON)
    // ========================================================================

    /**
     * Genera reporte de órdenes para un rango de fechas
     * GET /api/v1/reports/orders?start_date=2024-01-01&end_date=2024-12-31&status=completed
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function orders(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'status' => 'nullable|string',
                'order_type' => 'nullable|string',
                'payment_method' => 'nullable|string',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $filters = $request->only(['status', 'order_type', 'payment_method']);

            $report = $this->ordersReportService->generateReport($startDate, $endDate, $filters);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte de órdenes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera reporte de auditoría de órdenes
     * GET /api/v1/reports/orders/audit?start_date=2024-01-01&end_date=2024-12-31
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ordersAudit(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            $report = $this->ordersReportService->generateAuditReport($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte de auditoría',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera reporte de órdenes pendientes
     * GET /api/v1/reports/orders/pending
     *
     * @return JsonResponse
     */
    public function ordersPending(): JsonResponse
    {
        try {
            $report = $this->ordersReportService->generatePendingOrdersReport();

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte de órdenes pendientes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ========================================================================
    // EXPORTACIONES A PDF
    // ========================================================================

    /**
     * Exporta reporte de ventas a PDF
     * GET /api/v1/reports/sales/export/pdf?start_date=2024-01-01&end_date=2024-12-31
     */
    public function exportSalesPDF(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            return $this->exportService->exportSalesReportToPDF($startDate, $endDate);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el reporte a PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exporta reporte de productos a PDF
     * GET /api/v1/reports/products/export/pdf
     */
    public function exportProductsPDF(Request $request)
    {
        try {
            $filters = $request->only(['category_id', 'status']);

            return $this->exportService->exportProductsReportToPDF($filters);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el reporte a PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exporta reporte de órdenes a PDF
     * GET /api/v1/reports/orders/export/pdf?start_date=2024-01-01&end_date=2024-12-31
     */
    public function exportOrdersPDF(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $filters = $request->only(['status', 'order_type', 'payment_method']);

            return $this->exportService->exportOrdersReportToPDF($startDate, $endDate, $filters);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el reporte a PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ========================================================================
    // EXPORTACIONES A EXCEL
    // ========================================================================

    /**
     * Exporta reporte de ventas a Excel
     * GET /api/v1/reports/sales/export/excel?start_date=2024-01-01&end_date=2024-12-31
     */
    public function exportSalesExcel(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            return $this->exportService->exportSalesReportToExcel($startDate, $endDate);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el reporte a Excel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exporta reporte de productos a Excel
     * GET /api/v1/reports/products/export/excel
     */
    public function exportProductsExcel(Request $request)
    {
        try {
            $filters = $request->only(['category_id', 'status']);

            return $this->exportService->exportProductsReportToExcel($filters);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el reporte a Excel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exporta reporte de órdenes a Excel
     * GET /api/v1/reports/orders/export/excel?start_date=2024-01-01&end_date=2024-12-31
     */
    public function exportOrdersExcel(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $filters = $request->only(['status', 'order_type', 'payment_method']);

            return $this->exportService->exportOrdersReportToExcel($startDate, $endDate, $filters);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el reporte a Excel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
