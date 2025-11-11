<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShippingAddress;
use App\Models\Product;
use App\Services\StockReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderReceiptMail;
use Exception;

class OrderService
{
    protected StockReservationService $stockService;

    public function __construct(StockReservationService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Crea un pedido online (desde carrito del cliente)
     */
    public function createOnlineOrder(array $data, ?int $userId): Order
    {
        DB::beginTransaction();
        try {
            // Verificar disponibilidad de stock antes de crear el pedido
            $stockCheck = $this->stockService->checkAvailability($data['items']);
            if (!$stockCheck['available']) {
                throw new Exception(
                    'Stock insuficiente: ' .
                    collect($stockCheck['errors'])->pluck('message')->implode(', ')
                );
            }

            // Calcular totales
            $subtotal = $this->calculateSubtotal($data['items']);
            $shippingCost = $data['delivery_option'] === 'delivery' ? 0 : 0; // TODO: Implementar cálculo de envío
            $total = $subtotal + $shippingCost;

            // Crear pedido
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $userId,
                'order_type' => 'online',
                'status' => 'pending',
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'],
                'delivery_option' => $data['delivery_option'],
                'payment_method' => $data['payment_method'],
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            // Crear items del pedido con snapshots de productos
            $this->createOrderItems($order, $data['items']);

            // Crear dirección de envío si es delivery
            if ($data['delivery_option'] === 'delivery' && isset($data['shipping_address'])) {
                OrderShippingAddress::create([
                    'order_id' => $order->id,
                    'province' => $data['shipping_address']['province'],
                    'canton' => $data['shipping_address']['canton'],
                    'district' => $data['shipping_address']['district'],
                    'address_details' => $data['shipping_address']['address_details'],
                ]);
            }

            // Reservar stock
            $this->stockService->reserveStock(
                $data['items'],
                $order->id,
                $userId ?? 1 // Si es guest, usar ID 1 (Super Admin)
            );

            DB::commit();

            // Cargar relaciones para devolver el pedido completo
            return $order->load(['items', 'shippingAddress']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Crea un pedido en tienda física (por Super Admin)
     */
    public function createInStoreOrder(array $data, int $adminUserId): Order
    {
        DB::beginTransaction();
        try {
            // Verificar disponibilidad de stock
            $stockCheck = $this->stockService->checkAvailability($data['items']);
            if (!$stockCheck['available']) {
                throw new Exception(
                    'Stock insuficiente: ' .
                    collect($stockCheck['errors'])->pluck('message')->implode(', ')
                );
            }

            // Calcular totales
            $subtotal = $this->calculateSubtotal($data['items']);
            $total = $subtotal; // Sin costo de envío para pickup

            // Crear pedido
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => null, // Pedidos en tienda no tienen usuario asociado
                'order_type' => 'in_store',
                'status' => 'pending',
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'delivery_option' => 'pickup', // Siempre pickup para in_store
                'payment_method' => $data['payment_method'],
                'subtotal' => $subtotal,
                'shipping_cost' => 0,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            // Crear items del pedido
            $this->createOrderItems($order, $data['items']);

            // Reservar stock
            $this->stockService->reserveStock(
                $data['items'],
                $order->id,
                $adminUserId
            );

            DB::commit();

            return $order->load(['items']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza el estado de un pedido a 'in_progress'
     * Para completar o cancelar, usar completeOrder() o cancelOrder()
     */
    public function markAsInProgress(Order $order): Order
    {
        if (!in_array($order->status, ['pending'])) {
            throw new Exception('Solo los pedidos pendientes pueden marcarse como en proceso');
        }

        $order->update(['status' => 'in_progress']);

        return $order->fresh();
    }

    /**
     * Completa un pedido (confirma venta y envía email)
     */
    public function completeOrder(Order $order, int $userId): Order
    {
        if (!$order->canBeCompleted()) {
            throw new Exception('El pedido no puede ser completado desde su estado actual');
        }

        DB::beginTransaction();
        try {
            // Confirmar venta (convierte reservas en ventas y descuenta stock real)
            $this->stockService->confirmSale($order->id, $userId);

            // Actualizar estado del pedido
            $order->update(['status' => 'completed']);

            DB::commit();

            // Enviar email de comprobante si hay email
            if ($order->customer_email) {
                try {
                    Mail::to($order->customer_email)->send(new OrderReceiptMail($order->load(['items', 'shippingAddress'])));
                } catch (Exception $e) {
                    // Log el error pero no fallar la transacción
                    logger()->error('Error enviando email de comprobante', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $order->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancela un pedido (libera stock reservado)
     */
    public function cancelOrder(Order $order, int $userId): Order
    {
        if (!$order->canBeCancelled()) {
            throw new Exception('El pedido no puede ser cancelado desde su estado actual');
        }

        DB::beginTransaction();
        try {
            // Liberar stock reservado
            $this->stockService->releaseReservedStock($order->id, $userId);

            // Actualizar estado del pedido
            $order->update(['status' => 'cancelled']);

            DB::commit();

            return $order->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Archiva un pedido completado
     */
    public function archiveOrder(Order $order): Order
    {
        if (!$order->canBeArchived()) {
            throw new Exception('Solo los pedidos completados pueden ser archivados');
        }

        $order->update(['status' => 'archived']);

        return $order->fresh();
    }

    /**
     * Elimina un pedido (soft delete)
     */
    public function deleteOrder(Order $order, int $userId): bool
    {
        // Si el pedido está pending o in_progress, liberar stock antes de eliminar
        if (in_array($order->status, ['pending', 'in_progress'])) {
            $this->stockService->releaseReservedStock($order->id, $userId);
        }

        return $order->delete();
    }

    /**
     * Genera un número de pedido único
     */
    protected function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $count = Order::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('ORD-%s-%04d', $date, $count);
    }

    /**
     * Calcula el subtotal del pedido
     */
    protected function calculateSubtotal(array $items): float
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $subtotal += $product->price * $item['quantity'];
        }

        return $subtotal;
    }

    /**
     * Crea los items del pedido con snapshots de productos
     */
    protected function createOrderItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'product_description' => $product->description,
                'product_image_url' => $product->image_url,
                'quantity' => $item['quantity'],
                'price_at_purchase' => $product->price,
                'subtotal' => $product->price * $item['quantity'],
            ]);
        }
    }
}