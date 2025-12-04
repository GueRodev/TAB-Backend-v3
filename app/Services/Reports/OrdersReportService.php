<?php

namespace App\Services\Reports;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Orders Report Service
 *
 * Servicio para generar reportes de pedidos.
 * Incluye análisis de órdenes por estado, tipo, método de pago y auditoría básica.
 */
class OrdersReportService
{
    /**
     * Genera el reporte completo de órdenes para un rango de fechas
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters Filtros opcionales (status, order_type, etc.)
     * @return array
     */
    public function generateReport(Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();

        // Query base
        $query = Order::with(['user', 'items.product', 'completedBy', 'cancelledBy']);

        // Filtro de fecha: Si hay filtro de estado específico, usar la fecha correspondiente
        // Usa created_at como fallback para pedidos antiguos sin auditoría
        if (isset($filters['status'])) {
            if ($filters['status'] === 'completed') {
                // Para completados, filtrar por completed_at (con fallback a created_at)
                $query->where('status', 'completed')
                      ->where(function($q) use ($start, $end) {
                          $q->whereBetween('completed_at', [$start, $end])
                            ->orWhere(function($q2) use ($start, $end) {
                                $q2->whereNull('completed_at')
                                   ->whereBetween('created_at', [$start, $end]);
                            });
                      });
            } elseif ($filters['status'] === 'cancelled') {
                // Para cancelados, filtrar por cancelled_at (con fallback a created_at)
                $query->where('status', 'cancelled')
                      ->where(function($q) use ($start, $end) {
                          $q->whereBetween('cancelled_at', [$start, $end])
                            ->orWhere(function($q2) use ($start, $end) {
                                $q2->whereNull('cancelled_at')
                                   ->whereBetween('created_at', [$start, $end]);
                            });
                      });
            } else {
                // Para otros estados (pending, etc.), usar created_at
                $query->where('status', $filters['status'])
                      ->whereBetween('created_at', [$start, $end]);
            }
        } else {
            // Sin filtro de estado: mostrar todos los pedidos creados en el rango
            $query->whereBetween('created_at', [$start, $end]);
        }

        // Aplicar otros filtros
        if (isset($filters['order_type'])) {
            $query->where('order_type', $filters['order_type']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        $orders = $query->get();

        // Calcular resumen
        $summary = $this->calculateSummary($orders);

        // Desglose por estado
        $statusBreakdown = $this->getStatusBreakdown($orders);

        // Desglose por tipo de orden
        $orderTypeBreakdown = $this->getOrderTypeBreakdown($orders);

        // Desglose por método de pago
        $paymentMethodBreakdown = $this->getPaymentMethodBreakdown($orders);

        // Órdenes detalladas (para export)
        $ordersData = $this->formatOrdersData($orders);

        return [
            'period' => [
                'start_date' => $start->toISOString(),
                'end_date' => $end->toISOString(),
                'days' => $start->diffInDays($end) + 1,
            ],
            'summary' => $summary,
            'status_breakdown' => $statusBreakdown,
            'order_type_breakdown' => $orderTypeBreakdown,
            'payment_method_breakdown' => $paymentMethodBreakdown,
            'orders' => $ordersData,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Genera reporte de auditoría de órdenes
     * Enfocado en órdenes completadas y canceladas con información de quién las procesó
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generateAuditReport(Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();

        // Órdenes completadas con auditoría
        $completedOrders = Order::with(['user', 'completedBy'])
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->whereNotNull('completed_by')
            ->orderBy('completed_at', 'desc')
            ->get();

        // Órdenes canceladas con auditoría
        $cancelledOrders = Order::with(['user', 'cancelledBy'])
            ->where('status', 'cancelled')
            ->whereBetween('cancelled_at', [$start, $end])
            ->whereNotNull('cancelled_by')
            ->orderBy('cancelled_at', 'desc')
            ->get();

        return [
            'period' => [
                'start_date' => $start->toISOString(),
                'end_date' => $end->toISOString(),
            ],
            'completed_orders' => $this->formatAuditData($completedOrders, 'completed'),
            'cancelled_orders' => $this->formatAuditData($cancelledOrders, 'cancelled'),
            'summary' => [
                'total_completed' => $completedOrders->count(),
                'total_cancelled' => $cancelledOrders->count(),
                'completed_revenue' => (float) $completedOrders->sum('total'),
                'cancelled_revenue_loss' => (float) $cancelledOrders->sum('total'),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Genera reporte de órdenes pendientes
     * Útil para gestión de inventario y preparación de pedidos
     *
     * @return array
     */
    public function generatePendingOrdersReport(): array
    {
        $pendingOrders = Order::with(['user', 'items.product', 'shippingAddress'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('created_at', 'asc')
            ->get();

        $ordersData = $pendingOrders->map(function ($order) {
            return [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name ?? $order->user?->name ?? 'Guest',
                'customer_email' => $order->customer_email ?? $order->user?->email ?? '',
                'customer_phone' => $order->customer_phone ?? '',
                'status' => $order->status,
                'order_type' => $order->order_type,
                'delivery_option' => $order->delivery_option,
                'total' => (float) $order->total,
                'items_count' => $order->items->count(),
                'created_at' => $order->created_at->toISOString(),
                'age_in_hours' => $order->created_at->diffInHours(now()),
                'requires_shipping' => $order->requiresShipping(),
            ];
        })->toArray();

        return [
            'total_pending' => $pendingOrders->count(),
            'total_value' => (float) $pendingOrders->sum('total'),
            'orders' => $ordersData,
            'generated_at' => now()->toISOString(),
        ];
    }

    // ========================================================================
    // MÉTODOS PRIVADOS DE FORMATO Y CÁLCULO
    // ========================================================================

    /**
     * Calcula el resumen de órdenes
     *
     * @param \Illuminate\Support\Collection $orders
     * @return array
     */
    private function calculateSummary($orders): array
    {
        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('total');
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => (float) $totalRevenue,
            'average_order_value' => (float) $averageOrderValue,
        ];
    }

    /**
     * Obtiene el desglose por estado
     *
     * @param \Illuminate\Support\Collection $orders
     * @return array
     */
    private function getStatusBreakdown($orders): array
    {
        $breakdown = $orders->groupBy('status')->map(function ($statusOrders, $status) {
            $count = $statusOrders->count();
            $revenue = $statusOrders->sum('total');

            return [
                'status' => $status,
                'count' => $count,
                'revenue' => (float) $revenue,
                'percentage' => 0, // Se calculará después
            ];
        })->values();

        // Calcular porcentajes
        $totalOrders = $orders->count();
        $breakdown = $breakdown->map(function ($item) use ($totalOrders) {
            $item['percentage'] = $totalOrders > 0 ? ($item['count'] / $totalOrders) * 100 : 0;
            return $item;
        })->toArray();

        return $breakdown;
    }

    /**
     * Obtiene el desglose por tipo de orden
     *
     * @param \Illuminate\Support\Collection $orders
     * @return array
     */
    private function getOrderTypeBreakdown($orders): array
    {
        $totalOrders = $orders->count();

        return $orders->groupBy('order_type')->map(function ($typeOrders, $type) use ($totalOrders) {
            $count = $typeOrders->count();
            $total = $typeOrders->sum('total');
            $average = $count > 0 ? $total / $count : 0;
            $percentage = $totalOrders > 0 ? ($count / $totalOrders) * 100 : 0;

            return [
                'order_type' => $type,
                'total' => (float) $total,
                'orders' => $count,
                'average' => (float) $average,
                'percentage' => (float) $percentage,
            ];
        })->values()->toArray();
    }

    /**
     * Obtiene el desglose por método de pago
     *
     * @param \Illuminate\Support\Collection $orders
     * @return array
     */
    private function getPaymentMethodBreakdown($orders): array
    {
        $totalOrders = $orders->count();

        return $orders->groupBy('payment_method')->map(function ($methodOrders, $method) use ($totalOrders) {
            $count = $methodOrders->count();
            $total = $methodOrders->sum('total');
            $average = $count > 0 ? $total / $count : 0;
            $percentage = $totalOrders > 0 ? ($count / $totalOrders) * 100 : 0;

            return [
                'payment_method' => $method ?? 'No especificado',
                'total' => (float) $total,
                'orders' => $count,
                'average' => (float) $average,
                'percentage' => (float) $percentage,
            ];
        })->values()->toArray();
    }

    /**
     * Formatea los datos de órdenes para export
     *
     * @param \Illuminate\Support\Collection $orders
     * @return array
     */
    private function formatOrdersData($orders): array
    {
        return $orders->map(function ($order) {
            // Formatear productos: "Producto 1 (x2), Producto 2 (x1)"
            $productsText = $order->items->map(function ($item) {
                return $item->product_name . ' (x' . $item->quantity . ')';
            })->join(', ');

            return [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name ?? $order->user?->name ?? 'Guest',
                'customer_email' => $order->customer_email ?? $order->user?->email ?? '',
                'order_type' => $order->order_type,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'subtotal' => (float) $order->subtotal,
                'shipping_cost' => (float) $order->shipping_cost,
                'total' => (float) $order->total,
                'items_count' => $order->items->count(),
                'products' => $productsText, // Productos formateados
                'created_at' => $order->created_at->toISOString(),
                'completed_at' => $order->completed_at?->toISOString(),
                'completed_by' => $order->completedBy?->name ?? ($order->completed_by ? "Usuario #{$order->completed_by}" : null),
                'cancelled_by' => $order->cancelledBy?->name ?? ($order->cancelled_by ? "Usuario #{$order->cancelled_by}" : null),
            ];
        })->toArray();
    }

    /**
     * Formatea los datos de auditoría
     *
     * @param \Illuminate\Support\Collection $orders
     * @param string $action 'completed' o 'cancelled'
     * @return array
     */
    private function formatAuditData($orders, string $action): array
    {
        return $orders->map(function ($order) use ($action) {
            $actionBy = $action === 'completed' ? $order->completedBy : $order->cancelledBy;
            $actionAt = $action === 'completed' ? $order->completed_at : $order->cancelled_at;

            return [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name ?? $order->user?->name ?? 'Guest',
                'total' => (float) $order->total,
                'action' => $action,
                'action_by' => $actionBy?->name ?? 'Desconocido',
                'action_by_email' => $actionBy?->email ?? '',
                'action_at' => $actionAt?->toISOString(),
                'created_at' => $order->created_at->toISOString(),
                'processing_time_hours' => $order->created_at->diffInHours($actionAt),
            ];
        })->toArray();
    }
}
