<?php

namespace App\Services\Dashboard;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Dashboard Metrics Service
 *
 * Servicio centralizado para calcular todas las métricas del dashboard
 * con estrategia de cache optimizada para performance.
 */
class DashboardMetricsService
{
    /**
     * Obtiene las métricas principales del dashboard (Overview)
     * Cache: 5 minutos
     *
     * @return array
     */
    public function getOverviewMetrics(): array
    {
        return Cache::remember('dashboard.overview', now()->addMinutes(5), function () {
            $now = Carbon::now();
            $today = $now->copy()->startOfDay();
            $firstDayOfMonth = $now->copy()->startOfMonth();
            $firstDayOfYear = $now->copy()->startOfYear();

            return [
                // Métricas de ingresos
                'total_revenue' => $this->calculateTotalRevenue(),
                'monthly_revenue' => $this->calculateMonthlyRevenue($firstDayOfMonth),
                'daily_revenue' => $this->calculateDailyRevenue($today),
                'yearly_revenue' => $this->calculateYearlyRevenue($firstDayOfYear),

                // Métricas de ganancias (opcionales si hay cost_price)
                'total_profit' => $this->calculateTotalProfit(),
                'monthly_profit' => $this->calculateMonthlyProfit($firstDayOfMonth),
                'daily_profit' => $this->calculateDailyProfit($today),
                'yearly_profit' => $this->calculateYearlyProfit($firstDayOfYear),
                'profit_margin' => $this->calculateProfitMargin(),

                // Métricas de órdenes
                'pending_orders' => $this->countPendingOrders(),
                'completed_orders' => $this->countCompletedOrders(),
                'total_orders' => $this->countTotalOrders(),

                // Métricas derivadas
                'average_order_value' => $this->calculateAverageOrderValue(),
            ];
        });
    }

    /**
     * Obtiene la tendencia de ventas de los últimos N días
     * Cache: 10 minutos
     *
     * @param int $days
     * @return array
     */
    public function getSalesTrend(int $days = 7): array
    {
        return Cache::remember("dashboard.sales_trend.{$days}", now()->addMinutes(10), function () use ($days) {
            $data = [];
            $now = Carbon::now();

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i);
                $startOfDay = $date->copy()->startOfDay();
                $endOfDay = $date->copy()->endOfDay();

                $dailyOrders = Order::where('status', 'completed')
                    ->whereBetween('created_at', [$startOfDay, $endOfDay])
                    ->with('items.product')
                    ->get();

                $revenue = $dailyOrders->sum('total');
                $profit = $this->calculateOrdersProfit($dailyOrders);

                $data[] = [
                    'date' => $date->format('Y-m-d'),
                    'formatted_date' => $date->format('d M'),
                    'revenue' => (float) $revenue,
                    'orders' => $dailyOrders->count(),
                    'profit' => (float) $profit,
                ];
            }

            return $data;
        });
    }

    /**
     * Obtiene los pedidos más recientes
     * Cache: 2 minutos (datos más dinámicos)
     *
     * @param int $limit
     * @return array
     */
    public function getRecentOrders(int $limit = 5): array
    {
        return Cache::remember('dashboard.recent_orders', now()->addMinutes(2), function () use ($limit) {
            return Order::with(['user', 'items.product'])
                ->where('status', '!=', 'cancelled')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer_name ?? $order->user?->name ?? 'Guest',
                        'customer_email' => $order->customer_email ?? $order->user?->email ?? '',
                        'total' => (float) $order->total,
                        'status' => $order->status,
                        'created_at' => $order->created_at->toISOString(),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Obtiene los productos más vendidos
     * Cache: 15 minutos (cambia lentamente)
     *
     * @param int $limit
     * @return array
     */
    public function getTopProducts(int $limit = 5): array
    {
        return Cache::remember('dashboard.top_products', now()->addMinutes(15), function () use ($limit) {
            $topProducts = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.status', 'completed')
                ->select([
                    'products.id as product_id',
                    'products.name as product_name',
                    'products.sku',
                    'products.cost_price',
                    DB::raw('SUM(order_items.quantity) as quantity_sold'),
                    DB::raw('SUM(order_items.price_at_purchase * order_items.quantity) as revenue'),
                ])
                ->groupBy('products.id', 'products.name', 'products.sku', 'products.cost_price')
                ->orderByDesc('revenue')
                ->limit($limit)
                ->get();

            return $topProducts->map(function ($product) {
                $cost = 0;
                $profit = 0;

                if ($product->cost_price) {
                    // Necesitamos calcular el costo total basado en quantity_sold
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
                ];
            })->toArray();
        });
    }

    /**
     * Obtiene el resumen rápido
     * Cache: 5 minutos
     *
     * @return array
     */
    public function getQuickSummary(): array
    {
        return Cache::remember('dashboard.quick_summary', now()->addMinutes(5), function () {
            $completedOrders = Order::where('status', 'completed')->with('items')->get();

            $uniqueProductIds = $completedOrders->flatMap(function ($order) {
                return $order->items->pluck('product_id');
            })->unique();

            return [
                'products_sold' => $uniqueProductIds->count(),
                'completed_orders' => $completedOrders->count(),
                'pending_orders' => $this->countPendingOrders(),
                'average_order_value' => $this->calculateAverageOrderValue(),
            ];
        });
    }

    // ========================================================================
    // MÉTODOS PRIVADOS DE CÁLCULO
    // ========================================================================

    /**
     * Calcula el total de ingresos (todas las ventas completadas)
     */
    private function calculateTotalRevenue(): float
    {
        return (float) Order::where('status', 'completed')->sum('total');
    }

    /**
     * Calcula los ingresos del mes actual
     */
    private function calculateMonthlyRevenue(Carbon $startDate): float
    {
        return (float) Order::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('total');
    }

    /**
     * Calcula los ingresos del día actual
     */
    private function calculateDailyRevenue(Carbon $today): float
    {
        return (float) Order::where('status', 'completed')
            ->whereBetween('created_at', [$today, $today->copy()->endOfDay()])
            ->sum('total');
    }

    /**
     * Calcula los ingresos del año actual
     */
    private function calculateYearlyRevenue(Carbon $startDate): float
    {
        return (float) Order::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('total');
    }

    /**
     * Calcula la ganancia total
     * Retorna 0 si no hay productos con cost_price
     */
    private function calculateTotalProfit(): float
    {
        $orders = Order::where('status', 'completed')->with('items.product')->get();
        return $this->calculateOrdersProfit($orders);
    }

    /**
     * Calcula la ganancia del mes
     */
    private function calculateMonthlyProfit(Carbon $startDate): float
    {
        $orders = Order::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->with('items.product')
            ->get();

        return $this->calculateOrdersProfit($orders);
    }

    /**
     * Calcula la ganancia del día
     */
    private function calculateDailyProfit(Carbon $today): float
    {
        $orders = Order::where('status', 'completed')
            ->whereBetween('created_at', [$today, $today->copy()->endOfDay()])
            ->with('items.product')
            ->get();

        return $this->calculateOrdersProfit($orders);
    }

    /**
     * Calcula la ganancia del año
     */
    private function calculateYearlyProfit(Carbon $startDate): float
    {
        $orders = Order::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->with('items.product')
            ->get();

        return $this->calculateOrdersProfit($orders);
    }

    /**
     * Calcula el margen de ganancia global en porcentaje
     */
    private function calculateProfitMargin(): float
    {
        $totalRevenue = $this->calculateTotalRevenue();

        if ($totalRevenue <= 0) {
            return 0;
        }

        $totalProfit = $this->calculateTotalProfit();

        return ($totalProfit / $totalRevenue) * 100;
    }

    /**
     * Cuenta los pedidos pendientes
     */
    private function countPendingOrders(): int
    {
        return Order::where('status', 'pending')->count();
    }

    /**
     * Cuenta los pedidos completados
     */
    private function countCompletedOrders(): int
    {
        return Order::where('status', 'completed')->count();
    }

    /**
     * Cuenta el total de órdenes
     */
    private function countTotalOrders(): int
    {
        return Order::count();
    }

    /**
     * Calcula el valor promedio de orden
     */
    private function calculateAverageOrderValue(): float
    {
        $completed = Order::where('status', 'completed');
        $count = $completed->count();

        if ($count === 0) {
            return 0;
        }

        return (float) ($completed->sum('total') / $count);
    }

    /**
     * Calcula la ganancia de una colección de órdenes
     * Helper method reutilizable
     *
     * @param \Illuminate\Support\Collection $orders
     * @return float
     */
    private function calculateOrdersProfit($orders): float
    {
        $totalProfit = 0;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->product && $item->product->cost_price) {
                    $profit = ($item->price_at_purchase - $item->product->cost_price) * $item->quantity;
                    $totalProfit += $profit;
                }
            }
        }

        return (float) $totalProfit;
    }
}
