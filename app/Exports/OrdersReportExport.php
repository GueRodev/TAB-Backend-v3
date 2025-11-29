<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Orders Report Export
 *
 * Clase de exportación para reportes de pedidos.
 * Genera archivos Excel con múltiples hojas (resumen, detalle, desgloses por estado/tipo).
 * También se usa como fuente de datos para PDFs mediante las vistas Blade.
 */
class OrdersReportExport implements WithMultipleSheets
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    /**
     * Define las hojas del archivo Excel
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            new OrdersReportSummarySheet($this->reportData),
            new OrdersReportDetailsSheet($this->reportData),
            new OrdersReportStatusBreakdownSheet($this->reportData),
            new OrdersReportTypeBreakdownSheet($this->reportData),
        ];
    }
}

// ========================================================================
// HOJA 1: RESUMEN
// ========================================================================
class OrdersReportSummarySheet implements FromCollection, WithTitle, WithStyles
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        $summary = $this->reportData['summary'];
        $period = $this->reportData['period'];

        return collect([
            ['REPORTE DE PEDIDOS', ''],
            ['Periodo', $period['start_date'] . ' - ' . $period['end_date']],
            ['Días', $period['days']],
            [],
            ['Métrica', 'Valor'],
            ['Total de Órdenes', $summary['total_orders']],
            ['Ingresos Totales', '$' . number_format($summary['total_revenue'], 2)],
            ['Valor Promedio de Orden', '$' . number_format($summary['average_order_value'], 2)],
        ]);
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            5 => ['font' => ['bold' => true]],
            'A:B' => ['font' => ['size' => 12]],
        ];
    }
}

// ========================================================================
// HOJA 2: DETALLE DE ÓRDENES
// ========================================================================
class OrdersReportDetailsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithMapping
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        return collect($this->reportData['orders']);
    }

    public function headings(): array
    {
        return [
            'ID Orden',
            'Número',
            'Cliente',
            'Email',
            'Tipo',
            'Estado',
            'Método Pago',
            'Subtotal',
            'Envío',
            'Total',
            'Items',
            'Creada',
            'Completada',
            'Completada Por',
        ];
    }

    public function map($order): array
    {
        return [
            $order['order_id'],
            $order['order_number'],
            $order['customer_name'],
            $order['customer_email'],
            $order['order_type'],
            $order['status'],
            $order['payment_method'],
            '$' . number_format($order['subtotal'], 2),
            '$' . number_format($order['shipping_cost'], 2),
            '$' . number_format($order['total'], 2),
            $order['items_count'],
            $order['created_at'],
            $order['completed_at'] ?? 'N/A',
            $order['completed_by'] ?? 'N/A',
        ];
    }

    public function title(): string
    {
        return 'Detalle Órdenes';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

// ========================================================================
// HOJA 3: DESGLOSE POR ESTADO
// ========================================================================
class OrdersReportStatusBreakdownSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithMapping
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        return collect($this->reportData['status_breakdown']);
    }

    public function headings(): array
    {
        return [
            'Estado',
            'Cantidad',
            'Ingresos',
            'Porcentaje',
        ];
    }

    public function map($status): array
    {
        return [
            $status['status'],
            $status['count'],
            '$' . number_format($status['revenue'], 2),
            number_format($status['percentage'], 2) . '%',
        ];
    }

    public function title(): string
    {
        return 'Por Estado';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

// ========================================================================
// HOJA 4: DESGLOSE POR TIPO DE ORDEN
// ========================================================================
class OrdersReportTypeBreakdownSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithMapping
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        return collect($this->reportData['order_type_breakdown']);
    }

    public function headings(): array
    {
        return [
            'Tipo de Orden',
            'Cantidad',
            'Ingresos Totales',
        ];
    }

    public function map($type): array
    {
        return [
            $type['order_type'],
            $type['count'],
            '$' . number_format($type['revenue'], 2),
        ];
    }

    public function title(): string
    {
        return 'Por Tipo';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
