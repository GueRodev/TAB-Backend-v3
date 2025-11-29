<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Products Report Service
 *
 * Servicio para generar reportes de inventario y rendimiento de productos.
 * Incluye análisis de stock, productos más vendidos, y alertas de inventario bajo.
 */
class ProductsReportService
{
    /**
     * Genera el reporte completo de productos
     *
     * @param array $filters Filtros opcionales (category_id, status, etc.)
     * @return array
     */
    public function generateReport(array $filters = []): array
    {
        $query = Product::with('category');

        // Aplicar filtros si existen
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $products = $query->get();

        // Calcular métricas de inventario
        $inventorySummary = $this->calculateInventorySummary($products);

        // Formatear todos los productos para el tab "Todos"
        $formattedProducts = $products->map(function ($product) {
            return [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category->name ?? 'Sin categoría',
                'subcategory' => $product->subcategory ?? null,
                'current_stock' => $product->stock,
                'sale_price' => (float) $product->price,
                'cost_price' => (float) ($product->cost_price ?? 0),
                'inventory_value' => (float) ($product->price * $product->stock),
                'status' => $product->status,
            ];
        })->toArray();

        // Obtener productos con stock bajo
        $lowStockProducts = $this->getLowStockProducts();

        // Obtener productos sin stock
        $outOfStockProducts = $this->getOutOfStockProducts();

        // Obtener productos más vendidos (histórico)
        $topSellingProducts = $this->getTopSellingProducts(20);

        // Obtener productos menos vendidos
        $slowMovingProducts = $this->getSlowMovingProducts(20);

        // Valoración del inventario
        $inventoryValuation = $this->calculateInventoryValuation($products);

        return [
            'summary' => $inventorySummary,
            'products' => $formattedProducts,
            'inventory_valuation' => $inventoryValuation,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'top_selling_products' => $topSellingProducts,
            'slow_moving_products' => $slowMovingProducts,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Genera reporte de rendimiento de productos en un periodo
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generatePerformanceReport(Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();

        // Productos vendidos en el periodo con sus métricas
        $productPerformance = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$start, $end])
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.price',
                'products.cost_price',
                'products.stock',
                'products.stock_min',
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.price_at_purchase * order_items.quantity) as revenue'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count'),
            ])
            ->groupBy(
                'products.id',
                'products.name',
                'products.sku',
                'products.price',
                'products.cost_price',
                'products.stock',
                'products.stock_min',
                'categories.name'
            )
            ->orderByDesc('revenue')
            ->get();

        $performanceData = $productPerformance->map(function ($product) {
            $cost = 0;
            $profit = 0;

            if ($product->cost_price) {
                $cost = $product->cost_price * $product->quantity_sold;
                $profit = $product->revenue - $cost;
            }

            return [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'sku' => $product->sku,
                'category' => $product->category_name,
                'current_price' => (float) $product->price,
                'cost_price' => (float) $product->cost_price,
                'current_stock' => (int) $product->stock,
                'stock_min' => (int) $product->stock_min,
                'quantity_sold' => (int) $product->quantity_sold,
                'orders_count' => (int) $product->orders_count,
                'revenue' => (float) $product->revenue,
                'cost' => (float) $cost,
                'profit' => (float) $profit,
                'profit_margin' => $product->revenue > 0 ? ($profit / $product->revenue) * 100 : 0,
                'is_low_stock' => $product->stock <= $product->stock_min,
            ];
        })->toArray();

        return [
            'period' => [
                'start_date' => $start->toISOString(),
                'end_date' => $end->toISOString(),
            ],
            'products_sold_count' => count($performanceData),
            'performance_data' => $performanceData,
            'generated_at' => now()->toISOString(),
        ];
    }

    // ========================================================================
    // MÉTODOS PRIVADOS DE CÁLCULO
    // ========================================================================

    /**
     * Calcula resumen de inventario
     *
     * @param \Illuminate\Support\Collection $products
     * @return array
     */
    private function calculateInventorySummary($products): array
    {
        $totalProducts = $products->count();
        $activeProducts = $products->where('status', 'active')->count();
        $inactiveProducts = $products->where('status', 'inactive')->count();
        $outOfStockCount = $products->where('stock', 0)->count();
        $inStockCount = $products->where('stock', '>', 0)->count();
        $lowStockProducts = $products->filter(function ($product) {
            return $product->stock > 0 && $product->stock <= $product->stock_min;
        })->count();

        $totalStockUnits = $products->sum('stock');

        // Calculate total inventory value (at sale price)
        $totalInventoryValue = $products->sum(function ($product) {
            return $product->price * $product->stock;
        });

        // Calculate average product value
        $averageProductValue = $totalProducts > 0 ? $totalInventoryValue / $totalProducts : 0;

        return [
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'inactive_products' => $inactiveProducts,
            'out_of_stock_count' => $outOfStockCount,
            'in_stock_count' => $inStockCount,
            'low_stock_products' => $lowStockProducts,
            'total_stock_units' => $totalStockUnits,
            'total_inventory_value' => (float) $totalInventoryValue,
            'average_product_value' => (float) $averageProductValue,
        ];
    }

    /**
     * Calcula la valoración del inventario
     * Suma del valor de todos los productos en stock (precio de venta y costo)
     *
     * @param \Illuminate\Support\Collection $products
     * @return array
     */
    private function calculateInventoryValuation($products): array
    {
        $totalValueAtSalePrice = 0;
        $totalValueAtCostPrice = 0;

        foreach ($products as $product) {
            $totalValueAtSalePrice += $product->price * $product->stock;

            if ($product->cost_price) {
                $totalValueAtCostPrice += $product->cost_price * $product->stock;
            }
        }

        $potentialProfit = $totalValueAtSalePrice - $totalValueAtCostPrice;

        return [
            'total_value_at_sale_price' => (float) $totalValueAtSalePrice,
            'total_value_at_cost_price' => (float) $totalValueAtCostPrice,
            'potential_profit' => (float) $potentialProfit,
        ];
    }

    /**
     * Obtiene productos con stock bajo (stock <= stock_min)
     *
     * @return array
     */
    private function getLowStockProducts(): array
    {
        $products = Product::with('category')
            ->where('status', 'active')
            ->whereColumn('stock', '<=', 'stock_min')
            ->where('stock', '>', 0)
            ->orderBy('stock', 'asc')
            ->get();

        return $products->map(function ($product) {
            return [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category->name ?? 'Sin categoría',
                'current_stock' => $product->stock,
                'stock_min' => $product->stock_min,
                'deficit' => $product->stock_min - $product->stock,
            ];
        })->toArray();
    }

    /**
     * Obtiene productos sin stock
     *
     * @return array
     */
    private function getOutOfStockProducts(): array
    {
        $products = Product::with('category')
            ->where('stock', 0)
            ->orderBy('updated_at', 'desc')
            ->get();

        return $products->map(function ($product) {
            return [
                'product_id' => $product->id,
                'name' => $product->name,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category->name ?? 'Sin categoría',
                'status' => $product->status,
                'last_updated' => $product->updated_at->toISOString(),
            ];
        })->toArray();
    }

    /**
     * Obtiene los productos más vendidos (histórico)
     *
     * @param int $limit
     * @return array
     */
    private function getTopSellingProducts(int $limit): array
    {
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.status', 'completed')
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.stock',
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.price_at_purchase * order_items.quantity) as total_revenue'),
            ])
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.stock', 'categories.name')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();

        return $topProducts->map(function ($product) {
            return [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'sku' => $product->sku,
                'category' => $product->category_name ?? 'Sin categoría',
                'current_stock' => (int) $product->stock,
                'total_sold' => (int) $product->total_sold,
                'total_revenue' => (float) $product->total_revenue,
            ];
        })->toArray();
    }

    /**
     * Obtiene productos de movimiento lento (menos vendidos)
     *
     * @param int $limit
     * @return array
     */
    private function getSlowMovingProducts(int $limit): array
    {
        // Obtener todos los productos activos
        $allProducts = Product::with('category')
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        // Obtener productos que sí se han vendido
        $soldProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->select([
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.price_at_purchase * order_items.quantity) as total_revenue'),
            ])
            ->groupBy('order_items.product_id')
            ->get()
            ->keyBy('product_id');

        // Combinar datos: productos con ventas bajas o sin ventas
        $slowMoving = $allProducts->map(function ($product) use ($soldProducts) {
            $soldData = $soldProducts->get($product->id);
            $totalSold = $soldData?->total_sold ?? 0;
            $totalRevenue = $soldData?->total_revenue ?? 0;

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category->name ?? 'Sin categoría',
                'current_stock' => $product->stock,
                'total_sold' => (int) $totalSold,
                'total_revenue' => (float) $totalRevenue,
            ];
        })
        ->sortBy('total_sold')
        ->take($limit)
        ->values()
        ->toArray();

        return $slowMoving;
    }
}
