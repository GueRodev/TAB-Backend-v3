<?php

namespace App\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

/**
 * Report Export Service
 *
 * Servicio centralizado para exportar reportes a PDF y Excel.
 * Maneja la generación de archivos usando Laravel Excel y DomPDF.
 */
class ReportExportService
{
    protected SalesReportService $salesReportService;
    protected ProductsReportService $productsReportService;
    protected OrdersReportService $ordersReportService;

    public function __construct(
        SalesReportService $salesReportService,
        ProductsReportService $productsReportService,
        OrdersReportService $ordersReportService
    ) {
        $this->salesReportService = $salesReportService;
        $this->productsReportService = $productsReportService;
        $this->ordersReportService = $ordersReportService;
    }

    // ========================================================================
    // EXPORTACIONES DE REPORTE DE VENTAS
    // ========================================================================

    /**
     * Exporta el reporte de ventas a PDF
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Http\Response
     */
    public function exportSalesReportToPDF(Carbon $startDate, Carbon $endDate)
    {
        $reportData = $this->salesReportService->generateReport($startDate, $endDate);

        $pdf = Pdf::loadView('reports.sales-pdf', [
            'data' => $reportData,
            'title' => 'Reporte de Ventas',
        ]);

        $fileName = 'reporte_ventas_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Exporta el reporte de ventas a Excel
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportSalesReportToExcel(Carbon $startDate, Carbon $endDate)
    {
        $reportData = $this->salesReportService->generateReport($startDate, $endDate);

        $fileName = 'reporte_ventas_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.xlsx';

        // Se usará la clase Export que crearemos más adelante
        return Excel::download(
            new \App\Exports\SalesReportExport($reportData),
            $fileName
        );
    }

    /**
     * Exporta el reporte mensual de ventas a PDF
     *
     * @param int $year
     * @return \Illuminate\Http\Response
     */
    public function exportMonthlySalesReportToPDF(int $year)
    {
        $reportData = $this->salesReportService->generateMonthlyReport($year);

        $pdf = Pdf::loadView('reports.monthly-sales-pdf', [
            'data' => $reportData,
            'title' => "Reporte Mensual de Ventas {$year}",
        ]);

        $fileName = "reporte_ventas_mensual_{$year}.pdf";

        return $pdf->download($fileName);
    }

    // ========================================================================
    // EXPORTACIONES DE REPORTE DE PRODUCTOS
    // ========================================================================

    /**
     * Exporta el reporte de productos a PDF
     *
     * @param array $filters
     * @return \Illuminate\Http\Response
     */
    public function exportProductsReportToPDF(array $filters = [])
    {
        $reportData = $this->productsReportService->generateReport($filters);

        $pdf = Pdf::loadView('reports.products-pdf', [
            'data' => $reportData,
            'title' => 'Reporte de Productos e Inventario',
        ]);

        $fileName = 'reporte_productos_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Exporta el reporte de productos a Excel
     *
     * @param array $filters
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportProductsReportToExcel(array $filters = [])
    {
        $reportData = $this->productsReportService->generateReport($filters);

        $fileName = 'reporte_productos_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new \App\Exports\ProductsReportExport($reportData),
            $fileName
        );
    }

    /**
     * Exporta el reporte de rendimiento de productos a PDF
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Http\Response
     */
    public function exportProductsPerformanceReportToPDF(Carbon $startDate, Carbon $endDate)
    {
        $reportData = $this->productsReportService->generatePerformanceReport($startDate, $endDate);

        $pdf = Pdf::loadView('reports.products-performance-pdf', [
            'data' => $reportData,
            'title' => 'Reporte de Rendimiento de Productos',
        ]);

        $fileName = 'reporte_rendimiento_productos_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }

    // ========================================================================
    // EXPORTACIONES DE REPORTE DE ÓRDENES
    // ========================================================================

    /**
     * Exporta el reporte de órdenes a PDF
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return \Illuminate\Http\Response
     */
    public function exportOrdersReportToPDF(Carbon $startDate, Carbon $endDate, array $filters = [])
    {
        $reportData = $this->ordersReportService->generateReport($startDate, $endDate, $filters);

        $pdf = Pdf::loadView('reports.orders-pdf', [
            'data' => $reportData,
            'title' => 'Reporte de Pedidos',
        ]);

        $fileName = 'reporte_pedidos_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Exporta el reporte de órdenes a Excel
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportOrdersReportToExcel(Carbon $startDate, Carbon $endDate, array $filters = [])
    {
        $reportData = $this->ordersReportService->generateReport($startDate, $endDate, $filters);

        $fileName = 'reporte_pedidos_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new \App\Exports\OrdersReportExport($reportData),
            $fileName
        );
    }

    /**
     * Exporta el reporte de auditoría de órdenes a PDF
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Http\Response
     */
    public function exportAuditReportToPDF(Carbon $startDate, Carbon $endDate)
    {
        $reportData = $this->ordersReportService->generateAuditReport($startDate, $endDate);

        $pdf = Pdf::loadView('reports.audit-pdf', [
            'data' => $reportData,
            'title' => 'Reporte de Auditoría de Órdenes',
        ]);

        $fileName = 'reporte_auditoria_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Exporta el reporte de órdenes pendientes a PDF
     *
     * @return \Illuminate\Http\Response
     */
    public function exportPendingOrdersReportToPDF()
    {
        $reportData = $this->ordersReportService->generatePendingOrdersReport();

        $pdf = Pdf::loadView('reports.pending-orders-pdf', [
            'data' => $reportData,
            'title' => 'Reporte de Órdenes Pendientes',
        ]);

        $fileName = 'reporte_pedidos_pendientes_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }

    // ========================================================================
    // MÉTODOS DE AYUDA
    // ========================================================================

    /**
     * Valida el rango de fechas
     * Asegura que start_date <= end_date
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return void
     * @throws \InvalidArgumentException
     */
    public function validateDateRange(Carbon $startDate, Carbon $endDate): void
    {
        if ($startDate->gt($endDate)) {
            throw new \InvalidArgumentException('La fecha de inicio no puede ser mayor que la fecha de fin');
        }
    }

    /**
     * Formatea un rango de fechas para usar en nombres de archivo
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return string
     */
    public function formatDateRangeForFilename(Carbon $startDate, Carbon $endDate): string
    {
        return $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d');
    }
}
