<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Sales Report Service
 *
 * Servicio para generar reportes detallados de ventas.
 * Incluye métricas de ingresos, costos, ganancias y productos más vendidos.
 */
class SalesReportService
{
    /**
     * Genera el reporte completo de ventas para un rango de fechas
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generateReport(Carbon $startDate, Carbon $endDate): array
    {
        // Asegurar que las fechas cubran el día completo
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();

        // Obtener todas las órdenes completadas en el rango
        $orders = Order::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->with('items.product')
            ->get();

        // Calcular métricas principales
        $summary = $this->calculateSummary($orders);

        // Obtener productos más vendidos en el periodo
        $topProducts = $this->getTopProductsInPeriod($start, $end, 10);

        // Obtener tendencia diaria
        $dailyTrend = $this->getDailyTrend($start, $end);

        // Desglose por métodos de pago
        $paymentMethodBreakdown = $this->getPaymentMethodBreakdown($orders);

        // Desglose por tipo de orden (online vs in_store)
        $orderTypeBreakdown = $this->getOrderTypeBreakdown($orders);

        return [
            'period' => [
                'start_date' => $start->toISOString(),
                'end_date' => $end->toISOString(),
                'days' => $start->diffInDays($end) + 1,
            ],
            'summary' => $summary,
            'top_products' => $topProducts,
            'daily_trend' => $dailyTrend,
            'payment_methods' => $paymentMethodBreakdown,
            'order_types' => $orderTypeBreakdown,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Genera reporte de ventas mensuales para un año específico
     *
     * @param int $year
     * @return array
     */
    public function generateMonthlyReport(int $year): array
    {
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

            $orders = Order::where('status', 'completed')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->with('items.product')
                ->get();

            $summary = $this->calculateSummary($orders);

            $monthlyData[] = [
                'month' => $month,
                'month_name' => $startOfMonth->format('F'),
                'year' => $year,
                'summary' => $summary,
            ];
        }

        return [
            'year' => $year,
            'monthly_data' => $monthlyData,
            'generated_at' => now()->toISOString(),
        ];
    }

    // ========================================================================
    // MÉTODOS PRIVADOS DE CÁLCULO
    // ========================================================================

    /**
     * Calcula el resumen de métricas de ventas
     *
     * @param \Illuminate\Support\Collection $orders
     * @return array
     */
    private function calculateSummary($orders): array
    {
        $totalRevenue = $orders->sum('total');
        $totalCost = 0;
        $totalProfit = 0;
        $totalItemsSold = 0;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $totalItemsSold += $item->quantity;

                if ($item->product && $item->product->cost_price) {
                    $itemCost = $item->product->cost_price * $item->quantity;
                    $itemProfit = ($item->price - $item->product->cost_price) * $item->quantity;

                    $totalCost += $itemCost;
                    $totalProfit += $itemProfit;
                }
            }
        }

        $ordersCount = $orders->count();
        $averageOrderValue = $ordersCount > 0 ? $totalRevenue / $ordersCount : 0;
        $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        return [
            'total_revenue' => (float) $totalRevenue,
            'total_cost' => (float) $totalCost,
            'total_profit' => (float) $totalProfit,
            'profit_margin' => (float) $profitMargin,
            'total_orders' => $ordersCount,
            'total_items_sold' => $totalItemsSold,
            'average_order_value' => (float) $averageOrderValue,
        ];
    }

    /**
     * Obtiene los productos más vendidos en un periodo
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param int $limit
     * @return array
     */
    private function getTopProductsInPeriod(Carbon $start, Carbon $end, int $limit): array
    {
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$start, $end])
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.cost_price',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue'),
            ])
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.cost_price')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        return $topProducts->map(function ($product) {
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
                'quantity_sold' => (int) $product->quantity_sold,
                'revenue' => (float) $product->revenue,
                'cost' => (float) $cost,
                'profit' => (float) $profit,
                'profit_margin' => $product->revenue > 0 ? ($profit / $product->revenue) * 100 : 0,
            ];
        })->toArray();
    }

    /**
     * Obtiene la tendencia diaria de ventas
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    private function getDailyTrend(Carbon $start, Carbon $end): array
    {
        $dailyData = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();

            $dayOrders = Order::where('status', 'completed')
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->with('items.product')
                ->get();

            $summary = $this->calculateSummary($dayOrders);

            $dailyData[] = [
                'date' => $current->format('Y-m-d'),
                'formatted_date' => $current->format('d M Y'),
                'day_name' => $current->format('l'),
                'revenue' => $summary['total_revenue'],
                'profit' => $summary['total_profit'],
                'orders' => $summary['total_orders'],
                'items_sold' => $summary['total_items_sold'],
            ];

            $current->addDay();
        }

        return $dailyData;
    }

    /**
     * Obtiene el desglose por métodos de pago
     *
     * @param \Illuminate\Support\Collection $orders
     * @return array
     */
    private function getPaymentMethodBreakdown($orders): array
    {
        $breakdown = $orders->groupBy('payment_method')->map(function ($methodOrders, $method) {
            $revenue = $methodOrders->sum('total');
            $count = $methodOrders->count();

            return [
                'payment_method' => $method ?? 'No especificado',
                'orders_count' => $count,
                'total_revenue' => (float) $revenue,
                'average_order_value' => $count > 0 ? $revenue / $count : 0,
            ];
        })->values()->toArray();

        return $breakdown;
    }

    /**
     * Obtiene el desglose por tipo de orden (online vs in_store)
     *
     * @param \Illuminate\Support\Collection $orders
     * @return array
     */
    private function getOrderTypeBreakdown($orders): array
    {
        $breakdown = $orders->groupBy('order_type')->map(function ($typeOrders, $type) {
            $revenue = $typeOrders->sum('total');
            $count = $typeOrders->count();

            return [
                'order_type' => $type,
                'orders_count' => $count,
                'total_revenue' => (float) $revenue,
                'average_order_value' => $count > 0 ? $revenue / $count : 0,
            ];
        })->values()->toArray();

        return $breakdown;
    }
}
