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
 * Sales Report Export
 *
 * Clase de exportación para reportes de ventas.
 * Genera archivos Excel con múltiples hojas (resumen, productos top, tendencia diaria).
 * También se usa como fuente de datos para PDFs mediante las vistas Blade.
 */
class SalesReportExport implements WithMultipleSheets
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
            new SalesReportSummarySheet($this->reportData),
            new SalesReportTopProductsSheet($this->reportData),
            new SalesReportDailyTrendSheet($this->reportData),
        ];
    }
}

// ========================================================================
// HOJA 1: RESUMEN DE VENTAS
// ========================================================================
class SalesReportSummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        $summary = $this->reportData['summary'];

        return collect([
            [
                'Métrica',
                'Valor',
            ],
            [
                'Ingresos Totales',
                '$' . number_format($summary['total_revenue'], 2),
            ],
            [
                'Costo Total',
                '$' . number_format($summary['total_cost'], 2),
            ],
            [
                'Ganancia Total',
                '$' . number_format($summary['total_profit'], 2),
            ],
            [
                'Margen de Ganancia',
                number_format($summary['profit_margin'], 2) . '%',
            ],
            [
                'Total de Órdenes',
                $summary['total_orders'],
            ],
            [
                'Productos Vendidos',
                $summary['total_items_sold'],
            ],
            [
                'Valor Promedio de Orden',
                '$' . number_format($summary['average_order_value'], 2),
            ],
        ]);
    }

    public function headings(): array
    {
        return []; // No headings porque usamos collection personalizada
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            'A:B' => ['font' => ['size' => 12]],
        ];
    }
}

// ========================================================================
// HOJA 2: PRODUCTOS MÁS VENDIDOS
// ========================================================================
class SalesReportTopProductsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithMapping
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        return collect($this->reportData['top_products']);
    }

    public function headings(): array
    {
        return [
            'Producto',
            'SKU',
            'Cantidad Vendida',
            'Ingresos',
            'Costo',
            'Ganancia',
            'Margen %',
        ];
    }

    public function map($product): array
    {
        return [
            $product['product_name'],
            $product['sku'],
            $product['quantity_sold'],
            '$' . number_format($product['revenue'], 2),
            '$' . number_format($product['cost'], 2),
            '$' . number_format($product['profit'], 2),
            number_format($product['profit_margin'], 2) . '%',
        ];
    }

    public function title(): string
    {
        return 'Productos Top';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

// ========================================================================
// HOJA 3: TENDENCIA DIARIA
// ========================================================================
class SalesReportDailyTrendSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithMapping
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        return collect($this->reportData['daily_trend']);
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Día',
            'Ingresos',
            'Ganancia',
            'Órdenes',
            'Productos Vendidos',
        ];
    }

    public function map($day): array
    {
        return [
            $day['formatted_date'],
            $day['day_name'],
            '$' . number_format($day['revenue'], 2),
            '$' . number_format($day['profit'], 2),
            $day['orders'],
            $day['items_sold'],
        ];
    }

    public function title(): string
    {
        return 'Tendencia Diaria';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
