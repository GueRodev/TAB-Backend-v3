<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Exception;

class StockReservationService
{
    /**
     * Reserva stock para un pedido
     *
     * @param array $items Array de items: [['product_id' => 1, 'quantity' => 2], ...]
     * @param int|null $orderId ID del pedido
     * @param int $userId ID del usuario que ejecuta la acción
     * @return array Array de movimientos de stock creados
     * @throws Exception Si no hay stock suficiente
     */
    public function reserveStock(array $items, ?int $orderId, int $userId): array
    {
        $movements = [];

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $quantity = $item['quantity'];

                // Calcular stock disponible
                $reservedStock = $this->getReservedStock($product->id);
                $availableStock = $product->stock - $reservedStock;

                // Verificar si hay stock disponible
                if ($availableStock < $quantity) {
                    throw new Exception(
                        "Stock insuficiente para el producto '{$product->name}'. " .
                        "Disponible: {$availableStock}, Solicitado: {$quantity}"
                    );
                }

                // Crear movimiento de reserva
                $movement = StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'reserva',
                    'quantity' => -$quantity,
                    'stock_before' => $product->stock,
                    'stock_after' => $product->stock, // No cambia el stock real, solo reserva
                    'reason' => 'Reserva de stock para pedido #' . ($orderId ?? 'pendiente'),
                    'user_id' => $userId,
                    'order_id' => $orderId,
                ]);

                $movements[] = $movement;
            }

            DB::commit();
            return $movements;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Convierte una reserva en venta confirmada (descuenta del stock real)
     *
     * @param int $orderId ID del pedido
     * @param int $userId ID del usuario que ejecuta la acción
     * @return array Array de movimientos de stock creados
     */
    public function confirmSale(int $orderId, int $userId): array
    {
        $movements = [];

        DB::beginTransaction();
        try {
            // Obtener todas las reservas del pedido
            $reservations = StockMovement::where('order_id', $orderId)
                ->where('type', 'reserva')
                ->get();

            foreach ($reservations as $reservation) {
                $product = Product::lockForUpdate()->findOrFail($reservation->product_id);
                $quantity = abs($reservation->quantity);

                // Descontar del stock real
                $product->stock -= $quantity;
                $product->save();

                // Crear movimiento de venta
                $movement = StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'venta',
                    'quantity' => -$quantity,
                    'stock_before' => $reservation->stock_after,
                    'stock_after' => $product->stock,
                    'reason' => 'Venta confirmada - Pedido #' . $orderId,
                    'user_id' => $userId,
                    'order_id' => $orderId,
                ]);

                $movements[] = $movement;
            }

            DB::commit();
            return $movements;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Libera stock reservado (al cancelar un pedido)
     *
     * @param int $orderId ID del pedido
     * @param int $userId ID del usuario que ejecuta la acción
     * @return array Array de movimientos de stock creados
     */
    public function releaseReservedStock(int $orderId, int $userId): array
    {
        $movements = [];

        DB::beginTransaction();
        try {
            // Obtener todas las reservas del pedido
            $reservations = StockMovement::where('order_id', $orderId)
                ->where('type', 'reserva')
                ->get();

            foreach ($reservations as $reservation) {
                $product = Product::lockForUpdate()->findOrFail($reservation->product_id);
                $quantity = abs($reservation->quantity);

                // Crear movimiento de cancelación de reserva
                $movement = StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'cancelacion_reserva',
                    'quantity' => $quantity, // Positivo para liberar
                    'stock_before' => $product->stock,
                    'stock_after' => $product->stock, // Stock real no cambia
                    'reason' => 'Cancelación de reserva - Pedido #' . $orderId,
                    'user_id' => $userId,
                    'order_id' => $orderId,
                ]);

                $movements[] = $movement;
            }

            DB::commit();
            return $movements;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calcula el stock reservado de un producto
     *
     * @param int $productId ID del producto
     * @return int Cantidad de stock reservado
     */
    public function getReservedStock(int $productId): int
    {
        // Obtener todas las reservas activas (sin cancelar ni confirmar)
        $reservations = StockMovement::where('product_id', $productId)
            ->where('type', 'reserva')
            ->get();

        $reservedTotal = 0;

        foreach ($reservations as $reservation) {
            $orderId = $reservation->order_id;

            // Verificar si esta reserva fue cancelada
            $wasCancelled = StockMovement::where('order_id', $orderId)
                ->where('product_id', $productId)
                ->where('type', 'cancelacion_reserva')
                ->exists();

            // Verificar si esta reserva fue confirmada como venta
            $wasConfirmed = StockMovement::where('order_id', $orderId)
                ->where('product_id', $productId)
                ->where('type', 'venta')
                ->exists();

            // Solo contar si no fue cancelada ni confirmada
            if (!$wasCancelled && !$wasConfirmed) {
                $reservedTotal += abs($reservation->quantity);
            }
        }

        return $reservedTotal;
    }

    /**
     * Obtiene el stock disponible de un producto
     *
     * @param int $productId ID del producto
     * @return int Stock disponible para venta
     */
    public function getAvailableStock(int $productId): int
    {
        $product = Product::findOrFail($productId);
        $reservedStock = $this->getReservedStock($productId);

        return max(0, $product->stock - $reservedStock);
    }

    /**
     * Verifica si hay stock disponible para un array de items
     *
     * @param array $items Array de items: [['product_id' => 1, 'quantity' => 2], ...]
     * @return array Array con resultado ['available' => bool, 'errors' => array]
     */
    public function checkAvailability(array $items): array
    {
        $errors = [];

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];
            $available = $this->getAvailableStock($productId);

            if ($available < $quantity) {
                $product = Product::find($productId);
                $errors[] = [
                    'product_id' => $productId,
                    'product_name' => $product->name ?? 'Desconocido',
                    'requested' => $quantity,
                    'available' => $available,
                    'message' => "Stock insuficiente para '{$product->name}'. Disponible: {$available}, Solicitado: {$quantity}"
                ];
            }
        }

        return [
            'available' => empty($errors),
            'errors' => $errors
        ];
    }
}
