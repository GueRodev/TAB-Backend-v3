<?php

namespace App\Services\Dashboard;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Analytics Service
 *
 * Servicio para análisis avanzado de datos anuales y mensuales.
 * Proporciona métricas detalladas de ventas, ganancias y tendencias.
 */
class AnalyticsService
{
    /**
     * Obtiene el análisis completo de un año específico
     * Cache: 1 día (datos históricos que cambian poco)
     *
     * @param int $year
     * @return array
     */
    public function getYearlyAnalytics(int $year): array
    {
        return Cache::remember("analytics.yearly.{$year}", now()->addDay(), function () use ($year) {
            $startOfYear = Carbon::create($year, 1, 1)->startOfDay();
            $endOfYear = Carbon::create($year, 12, 31)->endOfDay();

            // Obtener todas las órdenes completadas del año
            $orders = Order::where('status', 'completed')
                ->whereBetween('created_at', [$startOfYear, $endOfYear])
                ->with('items.product')
                ->get();

            // Calcular métricas principales
            $totalRevenue = $orders->sum('total');
            $totalProfit = $this->calculateOrdersProfit($orders);
            $totalOrders = $orders->count();
            $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
            $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

            // Desglose mensual
            $monthlyBreakdown = $this->getMonthlyBreakdown($year);

            // Productos únicos vendidos en el año
            $uniqueProductIds = $orders->flatMap(function ($order) {
                return $order->items->pluck('product_id');
            })->unique();

            return [
                'year' => $year,
                'summary' => [
                    'total_revenue' => (float) $totalRevenue,
                    'total_profit' => (float) $totalProfit,
                    'profit_margin' => (float) $profitMargin,
                    'total_orders' => $totalOrders,
                    'average_order_value' => (float) $averageOrderValue,
                    'unique_products_sold' => $uniqueProductIds->count(),
                ],
                'monthly_breakdown' => $monthlyBreakdown,
                'best_month' => $this->findBestMonth($monthlyBreakdown),
                'worst_month' => $this->findWorstMonth($monthlyBreakdown),
            ];
        });
    }

    /**
     * Obtiene el desglose mensual de un año
     * Retorna datos de cada mes (revenue, profit, orders)
     *
     * @param int $year
     * @return array
     */
    public function getMonthlyBreakdown(int $year): array
    {
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

            // Obtener órdenes del mes
            $orders = Order::where('status', 'completed')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->with('items.product')
                ->get();

            $revenue = $orders->sum('total');
            $profit = $this->calculateOrdersProfit($orders);
            $ordersCount = $orders->count();
            $avgOrderValue = $ordersCount > 0 ? $revenue / $ordersCount : 0;

            $monthlyData[] = [
                'month' => $month,
                'month_name' => $startOfMonth->format('F'), // Enero, Febrero, etc.
                'month_short' => $startOfMonth->format('M'), // Ene, Feb, etc.
                'revenue' => (float) $revenue,
                'profit' => (float) $profit,
                'orders' => $ordersCount,
                'average_order_value' => (float) $avgOrderValue,
                'profit_margin' => $revenue > 0 ? ($profit / $revenue) * 100 : 0,
            ];
        }

        return $monthlyData;
    }

    /**
     * Compara dos años y retorna las diferencias
     * Cache: 1 día
     *
     * @param int $year1
     * @param int $year2
     * @return array
     */
    public function compareYears(int $year1, int $year2): array
    {
        $cacheKey = "analytics.compare.{$year1}.{$year2}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($year1, $year2) {
            $analytics1 = $this->getYearlyAnalytics($year1);
            $analytics2 = $this->getYearlyAnalytics($year2);

            $summary1 = $analytics1['summary'];
            $summary2 = $analytics2['summary'];

            return [
                'year1' => $year1,
                'year2' => $year2,
                'comparison' => [
                    'revenue_difference' => $summary2['total_revenue'] - $summary1['total_revenue'],
                    'revenue_growth_percentage' => $this->calculateGrowthPercentage(
                        $summary1['total_revenue'],
                        $summary2['total_revenue']
                    ),
                    'profit_difference' => $summary2['total_profit'] - $summary1['total_profit'],
                    'profit_growth_percentage' => $this->calculateGrowthPercentage(
                        $summary1['total_profit'],
                        $summary2['total_profit']
                    ),
                    'orders_difference' => $summary2['total_orders'] - $summary1['total_orders'],
                    'orders_growth_percentage' => $this->calculateGrowthPercentage(
                        $summary1['total_orders'],
                        $summary2['total_orders']
                    ),
                ],
                'year1_data' => $summary1,
                'year2_data' => $summary2,
            ];
        });
    }

    /**
     * Obtiene el top de meses con mejores ventas de todos los tiempos
     * Cache: 1 día
     *
     * @param int $limit
     * @return array
     */
    public function getTopMonthsAllTime(int $limit = 12): array
    {
        return Cache::remember("analytics.top_months.{$limit}", now()->addDay(), function () use ($limit) {
            // Agrupar órdenes por año-mes
            $monthlyStats = Order::where('status', 'completed')
                ->with('items.product')
                ->get()
                ->groupBy(function ($order) {
                    return $order->created_at->format('Y-m');
                })
                ->map(function ($orders, $yearMonth) {
                    $revenue = $orders->sum('total');
                    $profit = $this->calculateOrdersProfit($orders);
                    $date = Carbon::createFromFormat('Y-m', $yearMonth);

                    return [
                        'year_month' => $yearMonth,
                        'year' => $date->year,
                        'month' => $date->month,
                        'month_name' => $date->format('F Y'),
                        'revenue' => (float) $revenue,
                        'profit' => (float) $profit,
                        'orders' => $orders->count(),
                    ];
                })
                ->sortByDesc('revenue')
                ->take($limit)
                ->values()
                ->toArray();

            return $monthlyStats;
        });
    }

    // ========================================================================
    // MÉTODOS PRIVADOS DE AYUDA
    // ========================================================================

    /**
     * Calcula la ganancia de una colección de órdenes
     * Reutiliza la lógica del DashboardMetricsService
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
                    $profit = ($item->price - $item->product->cost_price) * $item->quantity;
                    $totalProfit += $profit;
                }
            }
        }

        return (float) $totalProfit;
    }

    /**
     * Encuentra el mejor mes del desglose mensual
     * Basado en revenue
     *
     * @param array $monthlyBreakdown
     * @return array|null
     */
    private function findBestMonth(array $monthlyBreakdown): ?array
    {
        if (empty($monthlyBreakdown)) {
            return null;
        }

        $bestMonth = collect($monthlyBreakdown)->sortByDesc('revenue')->first();

        return $bestMonth;
    }

    /**
     * Encuentra el peor mes del desglose mensual
     * Basado en revenue
     *
     * @param array $monthlyBreakdown
     * @return array|null
     */
    private function findWorstMonth(array $monthlyBreakdown): ?array
    {
        if (empty($monthlyBreakdown)) {
            return null;
        }

        // Filtrar meses con revenue > 0 para evitar meses sin ventas
        $monthsWithSales = collect($monthlyBreakdown)->filter(function ($month) {
            return $month['revenue'] > 0;
        });

        if ($monthsWithSales->isEmpty()) {
            return null;
        }

        $worstMonth = $monthsWithSales->sortBy('revenue')->first();

        return $worstMonth;
    }

    /**
     * Calcula el porcentaje de crecimiento entre dos valores
     *
     * @param float $oldValue
     * @param float $newValue
     * @return float
     */
    private function calculateGrowthPercentage(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return (($newValue - $oldValue) / $oldValue) * 100;
    }
}
