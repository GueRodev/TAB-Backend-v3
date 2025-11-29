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
 * Products Report Export
 *
 * Clase de exportación para reportes de productos e inventario.
 * Genera archivos Excel con múltiples hojas (resumen, sin stock, más vendidos, movimiento lento).
 * También se usa como fuente de datos para PDFs mediante las vistas Blade.
 */
class ProductsReportExport implements WithMultipleSheets
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
            new ProductsReportSummarySheet($this->reportData),
            new ProductsReportOutOfStockSheet($this->reportData),
            new ProductsReportTopSellingSheet($this->reportData),
            new ProductsReportSlowMovingSheet($this->reportData),
        ];
    }
}

// ========================================================================
// HOJA 1: RESUMEN DE INVENTARIO
// ========================================================================
class ProductsReportSummarySheet implements FromCollection, WithTitle, WithStyles
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        $summary = $this->reportData['summary'];
        $valuation = $this->reportData['inventory_valuation'];

        return collect([
            ['Métrica', 'Valor'],
            ['Total de Productos', $summary['total_products']],
            ['Productos Activos', $summary['active_products']],
            ['Productos Inactivos', $summary['inactive_products']],
            ['Productos sin Stock', $summary['out_of_stock_products']],
            ['Unidades Totales en Stock', $summary['total_stock_units']],
            [],
            ['VALORACIÓN DE INVENTARIO', ''],
            ['Valor a Precio de Venta', '$' . number_format($valuation['total_value_at_sale_price'], 2)],
            ['Valor a Precio de Costo', '$' . number_format($valuation['total_value_at_cost_price'], 2)],
            ['Ganancia Potencial', '$' . number_format($valuation['potential_profit'], 2)],
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
            8 => ['font' => ['bold' => true]],
            'A:B' => ['font' => ['size' => 12]],
        ];
    }
}

// ========================================================================
// HOJA 2: PRODUCTOS SIN STOCK
// ========================================================================
class ProductsReportOutOfStockSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithMapping
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        return collect($this->reportData['out_of_stock_products']);
    }

    public function headings(): array
    {
        return [
            'Producto',
            'SKU',
            'Categoría',
            'Estado',
            'Última Actualización',
        ];
    }

    public function map($product): array
    {
        return [
            $product['name'],
            $product['sku'],
            $product['category'],
            $product['status'],
            $product['last_updated'],
        ];
    }

    public function title(): string
    {
        return 'Sin Stock';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

// ========================================================================
// HOJA 3: PRODUCTOS MÁS VENDIDOS
// ========================================================================
class ProductsReportTopSellingSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithMapping
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        return collect($this->reportData['top_selling_products']);
    }

    public function headings(): array
    {
        return [
            'Producto',
            'SKU',
            'Categoría',
            'Stock Actual',
            'Total Vendido',
            'Ingresos Totales',
        ];
    }

    public function map($product): array
    {
        return [
            $product['product_name'],
            $product['sku'],
            $product['category'],
            $product['current_stock'],
            $product['total_sold'],
            '$' . number_format($product['total_revenue'], 2),
        ];
    }

    public function title(): string
    {
        return 'Más Vendidos';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

// ========================================================================
// HOJA 4: PRODUCTOS DE MOVIMIENTO LENTO
// ========================================================================
class ProductsReportSlowMovingSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithMapping
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        return collect($this->reportData['slow_moving_products']);
    }

    public function headings(): array
    {
        return [
            'Producto',
            'SKU',
            'Categoría',
            'Stock Actual',
            'Total Vendido',
        ];
    }

    public function map($product): array
    {
        return [
            $product['product_name'],
            $product['sku'],
            $product['category'],
            $product['current_stock'],
            $product['total_sold'],
        ];
    }

    public function title(): string
    {
        return 'Movimiento Lento';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
