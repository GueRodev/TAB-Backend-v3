<?php

namespace App\Services;

use App\Models\Address;
use App\Models\CrLocation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShippingAddress;
use App\Models\Product;
use App\Services\StockReservationService;
use App\Services\NotificationService;
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
     *
     * INTEGRACIÓN CON ADDRESSES:
     * - Auto-completa datos del cliente desde el perfil si no se envían
     * - Acepta address_id (dirección guardada) o campos manuales
     * - Usa Address::toShippingSnapshot() para crear snapshot de dirección
     *
     * IMPORTANTE:
     * - Solo usuarios AUTENTICADOS pueden crear pedidos online
     * - El parámetro $userId SIEMPRE tiene valor (validado en StoreOnlineOrderRequest)
     */
    public function createOnlineOrder(array $data, int $userId): Order
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

            // AUTO-COMPLETAR datos del cliente desde el perfil si no se enviaron
            $customerData = $this->prepareCustomerData($data, $userId);

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
                'customer_name' => $customerData['name'],
                'customer_phone' => $customerData['phone'],
                'customer_email' => $customerData['email'],
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
            // Usa Address::toShippingSnapshot() o resuelve ubicaciones manuales
            if ($data['delivery_option'] === 'delivery') {
                $shippingData = $this->prepareShippingAddress($data);

                OrderShippingAddress::create([
                    'order_id' => $order->id,
                    'province' => $shippingData['province'],
                    'canton' => $shippingData['canton'],
                    'district' => $shippingData['district'],
                    'address_details' => $shippingData['address_details'],
                ]);
            }

            // Reservar stock
            $this->stockService->reserveStock(
                $data['items'],
                $order->id,
                $userId
            );

            DB::commit();

            // Notificar a los administradores sobre el nuevo pedido
            NotificationService::notifyNewOrder($order);

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

    // TEMPORALMENTE DESHABILITADO - No se está utilizando
    // /**
    //  * Actualiza el estado de un pedido a 'in_progress'
    //  * Para completar o cancelar, usar completeOrder() o cancelOrder()
    //  */
    // public function markAsInProgress(Order $order): Order
    // {
    //     if (!in_array($order->status, ['pending'])) {
    //         throw new Exception('Solo los pedidos pendientes pueden marcarse como en proceso');
    //     }

    //     $order->update(['status' => 'in_progress']);

    //     return $order->fresh();
    // }

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

            // Notificar a los administradores sobre el pedido completado
            NotificationService::notifyOrderCompleted($order);

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

            // Notificar a los administradores sobre el pedido cancelado
            NotificationService::notifyOrderCancelled($order);

            return $order->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // TEMPORALMENTE DESHABILITADO - No se está utilizando
    // /**
    //  * Archiva un pedido completado
    //  */
    // public function archiveOrder(Order $order): Order
    // {
    //     if (!$order->canBeArchived()) {
    //         throw new Exception('Solo los pedidos completados pueden ser archivados');
    //     }

    //     $order->update(['status' => 'archived']);

    //     return $order->fresh();
    // }

    // TEMPORALMENTE DESHABILITADO - No se está utilizando
    // /**
    //  * Desarchiva un pedido (lo devuelve a estado completado)
    //  */
    // public function unarchiveOrder(Order $order): Order
    // {
    //     if ($order->status !== 'archived') {
    //         throw new Exception('Solo los pedidos archivados pueden ser desarchivados');
    //     }

    //     $order->update(['status' => 'completed']);

    //     return $order->fresh();
    // }

    /**
     * Elimina un pedido (soft delete)
     */
    public function deleteOrder(Order $order, int $userId): bool
    {
        // Si el pedido está pending o in_progress, liberar stock antes de eliminar
        if (in_array($order->status, ['pending', 'in_progress'])) {
            $this->stockService->releaseReservedStock($order->id, $userId);
        }

        $deleted = $order->delete();

        if ($deleted) {
            // Notificar a los administradores sobre el pedido eliminado
            NotificationService::notifyOrderDeleted($order);
        }

        return $deleted;
    }

    // TEMPORALMENTE DESHABILITADO - No se está utilizando
    // /**
    //  * Elimina permanentemente un pedido (force delete)
    //  * Solo se permite para pedidos que ya están en la papelera (soft deleted)
    //  * Elimina también: items, dirección de envío y movimientos de stock asociados
    //  */
    // public function forceDeleteOrder(Order $order): bool
    // {
    //     DB::beginTransaction();
    //     try {
    //         // Eliminar items del pedido
    //         $order->items()->delete();

    //         // Eliminar dirección de envío si existe
    //         if ($order->shippingAddress) {
    //             $order->shippingAddress()->delete();
    //         }

    //         // Eliminar movimientos de stock asociados
    //         $order->stockMovements()->delete();

    //         // Eliminar permanentemente el pedido
    //         $order->forceDelete();

    //         DB::commit();
    //         return true;

    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }
    // }

    /**
     * Genera un número de pedido único
     * Usa el máximo order_number del día + 1 para evitar colisiones
     */
    protected function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "ORD-{$date}-";

        // Buscar el último order_number del día (incluye soft deleted)
        $lastOrder = Order::withTrashed()
            ->where('order_number', 'like', $prefix . '%')
            ->orderByRaw("CAST(SUBSTRING(order_number FROM '\d+$') AS INTEGER) DESC")
            ->first();

        if ($lastOrder) {
            // Extraer el número y sumar 1
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s%04d', $prefix, $newNumber);
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

    /**
     * Prepara los datos del cliente para el pedido
     *
     * AUTO-COMPLETADO DESDE EL PERFIL:
     * - Si el usuario envió los datos (customer_name, customer_phone, customer_email), los usa
     * - Si NO los envió, los toma automáticamente del perfil del usuario autenticado
     *
     * BENEFICIO:
     * - El usuario no tiene que escribir sus datos en cada pedido
     * - Permite override si el usuario quiere usar datos diferentes (ej: enviar a otra persona)
     *
     * IMPORTANTE:
     * - Este método SIEMPRE recibe un userId válido
     * - Solo usuarios autenticados pueden crear pedidos online (validado en StoreOnlineOrderRequest)
     *
     * @param array $data Datos del request
     * @param int $userId ID del usuario autenticado (SIEMPRE presente)
     * @return array Array con keys: name, phone, email
     */
    private function prepareCustomerData(array $data, int $userId): array
    {
        // Cargar el perfil del usuario autenticado para auto-completar datos
        $user = \App\Models\User::findOrFail($userId);

        return [
            // Si envió customer_name, usarlo. Si no, usar nombre del perfil
            'name' => $data['customer_name'] ?? $user->name,

            // Si envió customer_phone, usarlo. Si no, usar teléfono del perfil
            'phone' => $data['customer_phone'] ?? $user->phone,

            // Si envió customer_email, usarlo. Si no, usar email del perfil
            'email' => $data['customer_email'] ?? $user->email,
        ];
    }

    /**
     * Prepara los datos de dirección de envío para el pedido
     *
     * OPCIÓN A: Usar dirección guardada (address_id)
     * - Carga el modelo Address
     * - Usa el método toShippingSnapshot() para obtener el snapshot
     *
     * OPCIÓN B: Usar campos manuales con IDs (province_id, canton_id, district_id)
     * - Resuelve los nombres de las ubicaciones desde cr_locations
     * - Construye el snapshot manualmente
     *
     * OPCIÓN C: Usar campos manuales con nombres (province, canton, district)
     * - Los nombres ya vienen resueltos desde el frontend
     * - Más flexible para frontends que usan selectores con nombres
     *
     * FORMATO DE RETORNO:
     * Array con keys: province, canton, district, address_details
     * (Todos son strings, NO IDs - es un snapshot del momento de compra)
     *
     * @param array $data Datos del request
     * @return array Snapshot de la dirección con nombres resueltos
     */
    private function prepareShippingAddress(array $data): array
    {
        // OPCIÓN A: Usar dirección guardada
        // Si el usuario seleccionó una dirección de su perfil
        if (isset($data['address_id']) && $data['address_id']) {
            $address = Address::findOrFail($data['address_id']);

            // toShippingSnapshot() ya resuelve los nombres de las ubicaciones
            // y retorna: ['province' => 'San José', 'canton' => 'Escazú', ...]
            return $address->toShippingSnapshot();
        }

        $shippingAddress = $data['shipping_address'];

        // OPCIÓN B: Usar campos manuales con IDs
        // Si el frontend envía province_id, canton_id, district_id
        if (isset($shippingAddress['province_id'])) {
            // Cargar las ubicaciones desde la BD para obtener sus nombres
            $province = CrLocation::findOrFail($shippingAddress['province_id']);
            $canton = CrLocation::findOrFail($shippingAddress['canton_id']);
            $district = CrLocation::findOrFail($shippingAddress['district_id']);

            // Construir el snapshot manualmente con los nombres
            return [
                'province' => $province->province_name,
                'canton' => $canton->canton_name,
                'district' => $district->district_name,
                'address_details' => $shippingAddress['address_details'],
            ];
        }

        // OPCIÓN C: Usar campos manuales con nombres
        // Si el frontend envía province, canton, district como strings
        return [
            'province' => $shippingAddress['province'],
            'canton' => $shippingAddress['canton'],
            'district' => $shippingAddress['district'],
            'address_details' => $shippingAddress['address_details'],
        ];
    }
}