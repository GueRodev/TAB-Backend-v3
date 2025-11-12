<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShippingAddress;
use App\Models\Product;
use App\Models\User;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener usuarios y productos existentes
        $users = User::role('Cliente')->get();
        $superAdmin = User::role('Super Admin')->first();
        $products = Product::where('stock', '>', 10)->get();

        if ($users->isEmpty()) {
            $this->command->warn('⚠️  No hay usuarios con rol Cliente. Crea usuarios primero.');
            return;
        }

        if (!$superAdmin) {
            $this->command->warn('⚠️  No hay usuario con rol Super Admin.');
            return;
        }

        if ($products->isEmpty()) {
            $this->command->warn('⚠️  No hay productos con stock suficiente.');
            return;
        }

        $this->command->info('🚀 Creando pedidos de prueba...');

        // PEDIDOS ONLINE COMPLETADOS (5)
        $this->command->info('📦 Creando 5 pedidos online completados...');
        for ($i = 1; $i <= 5; $i++) {
            $this->createOnlineOrder($users->random(), $products, 'completed', $superAdmin->id);
        }

        // PEDIDOS ONLINE PENDIENTES (3)
        $this->command->info('⏳ Creando 3 pedidos online pendientes...');
        for ($i = 1; $i <= 3; $i++) {
            $this->createOnlineOrder($users->random(), $products, 'pending', $superAdmin->id);
        }

        // PEDIDOS ONLINE EN PROGRESO (2)
        $this->command->info('🔄 Creando 2 pedidos online en progreso...');
        for ($i = 1; $i <= 2; $i++) {
            $this->createOnlineOrder($users->random(), $products, 'in_progress', $superAdmin->id);
        }

        // PEDIDOS ONLINE CANCELADOS (2)
        $this->command->info('❌ Creando 2 pedidos online cancelados...');
        for ($i = 1; $i <= 2; $i++) {
            $this->createOnlineOrder($users->random(), $products, 'cancelled', $superAdmin->id);
        }

        // PEDIDOS EN TIENDA COMPLETADOS (4)
        $this->command->info('🏪 Creando 4 pedidos en tienda completados...');
        for ($i = 1; $i <= 4; $i++) {
            $this->createInStoreOrder($products, 'completed', $superAdmin->id);
        }

        // PEDIDOS EN TIENDA PENDIENTES (2)
        $this->command->info('⏳ Creando 2 pedidos en tienda pendientes...');
        for ($i = 1; $i <= 2; $i++) {
            $this->createInStoreOrder($products, 'pending', $superAdmin->id);
        }

        $this->command->info('✅ ¡20 pedidos creados exitosamente!');
    }

    /**
     * Crear pedido online
     */
    private function createOnlineOrder($user, $products, $status, $adminId)
    {
        DB::beginTransaction();
        try {
            $deliveryOption = rand(0, 1) ? 'pickup' : 'delivery';
            $orderNumber = $this->generateOrderNumber();

            // Calcular totales
            $itemsData = $this->generateOrderItems($products);
            $subtotal = collect($itemsData)->sum('subtotal');
            $shippingCost = $deliveryOption === 'delivery' ? 0 : 0;
            $total = $subtotal + $shippingCost;

            // Crear orden
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'order_type' => 'online',
                'status' => 'pending', // Siempre empieza en pending
                'customer_name' => $user->name,
                'customer_phone' => '8' . rand(1000000, 9999999),
                'customer_email' => $user->email,
                'delivery_option' => $deliveryOption,
                'payment_method' => $this->randomPaymentMethod(),
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'notes' => rand(0, 1) ? 'Pedido de prueba generado por seeder' : null,
            ]);

            // Crear items
            foreach ($itemsData as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'product_name' => $itemData['product_name'],
                    'product_sku' => $itemData['product_sku'],
                    'product_description' => $itemData['product_description'],
                    'product_image_url' => $itemData['product_image_url'],
                    'quantity' => $itemData['quantity'],
                    'price_at_purchase' => $itemData['price_at_purchase'],
                    'subtotal' => $itemData['subtotal'],
                ]);
            }

            // Crear dirección si es delivery
            if ($deliveryOption === 'delivery') {
                OrderShippingAddress::create([
                    'order_id' => $order->id,
                    'province' => $this->randomProvince(),
                    'canton' => 'Escazú',
                    'district' => 'San Rafael',
                    'address_details' => '100m norte de la iglesia',
                ]);
            }

            // Crear movimientos de stock según el estado
            $this->createStockMovements($order, $itemsData, $status, $adminId);

            // Actualizar estado final
            if ($status !== 'pending') {
                $order->update(['status' => $status]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error creando pedido: ' . $e->getMessage());
        }
    }

    /**
     * Crear pedido en tienda
     */
    private function createInStoreOrder($products, $status, $adminId)
    {
        DB::beginTransaction();
        try {
            $orderNumber = $this->generateOrderNumber();

            // Calcular totales
            $itemsData = $this->generateOrderItems($products);
            $subtotal = collect($itemsData)->sum('subtotal');
            $total = $subtotal;

            // Crear orden
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => null,
                'order_type' => 'in_store',
                'status' => 'pending',
                'customer_name' => 'Cliente ' . rand(1, 100),
                'customer_phone' => '8' . rand(1000000, 9999999),
                'customer_email' => rand(0, 1) ? 'cliente' . rand(1, 100) . '@example.com' : null,
                'delivery_option' => 'pickup',
                'payment_method' => $this->randomPaymentMethod(),
                'subtotal' => $subtotal,
                'shipping_cost' => 0,
                'total' => $total,
                'notes' => null,
            ]);

            // Crear items
            foreach ($itemsData as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'product_name' => $itemData['product_name'],
                    'product_sku' => $itemData['product_sku'],
                    'product_description' => $itemData['product_description'],
                    'product_image_url' => $itemData['product_image_url'],
                    'quantity' => $itemData['quantity'],
                    'price_at_purchase' => $itemData['price_at_purchase'],
                    'subtotal' => $itemData['subtotal'],
                ]);
            }

            // Crear movimientos de stock
            $this->createStockMovements($order, $itemsData, $status, $adminId);

            // Actualizar estado final
            if ($status !== 'pending') {
                $order->update(['status' => $status]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error creando pedido en tienda: ' . $e->getMessage());
        }
    }

    /**
     * Generar items del pedido
     */
    private function generateOrderItems($products)
    {
        $itemCount = rand(1, 3);
        $selectedProducts = $products->random(min($itemCount, $products->count()));
        $items = [];

        foreach ($selectedProducts as $product) {
            $quantity = rand(1, 3);
            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'product_description' => $product->description,
                'product_image_url' => $product->image_url,
                'quantity' => $quantity,
                'price_at_purchase' => $product->price,
                'subtotal' => $product->price * $quantity,
            ];
        }

        return $items;
    }

    /**
     * Crear movimientos de stock según el estado
     */
    private function createStockMovements($order, $itemsData, $status, $adminId)
    {
        foreach ($itemsData as $item) {
            $product = Product::find($item['product_id']);

            // Siempre crear reserva
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'reserva',
                'quantity' => -$item['quantity'],
                'stock_before' => $product->stock,
                'stock_after' => $product->stock,
                'reason' => 'Reserva de stock para pedido #' . $order->order_number,
                'user_id' => $adminId,
                'order_id' => $order->id,
            ]);

            // Si está completado, crear venta y descontar stock
            if ($status === 'completed') {
                $product->stock -= $item['quantity'];
                $product->save();

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'venta',
                    'quantity' => -$item['quantity'],
                    'stock_before' => $product->stock + $item['quantity'],
                    'stock_after' => $product->stock,
                    'reason' => 'Venta confirmada - Pedido #' . $order->order_number,
                    'user_id' => $adminId,
                    'order_id' => $order->id,
                ]);
            }

            // Si está cancelado, crear cancelación de reserva
            if ($status === 'cancelled') {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'cancelacion_reserva',
                    'quantity' => $item['quantity'],
                    'stock_before' => $product->stock,
                    'stock_after' => $product->stock,
                    'reason' => 'Cancelación de reserva - Pedido #' . $order->order_number,
                    'user_id' => $adminId,
                    'order_id' => $order->id,
                ]);
            }
        }
    }

    /**
     * Generar número de orden único
     */
    private function generateOrderNumber()
    {
        $date = now()->format('Ymd');
        $count = Order::whereDate('created_at', now()->toDateString())->count() + 1;
        return sprintf('ORD-%s-%04d', $date, $count);
    }

    /**
     * Método de pago aleatorio
     */
    private function randomPaymentMethod()
    {
        $methods = ['cash', 'card', 'transfer', 'sinpe'];
        return $methods[array_rand($methods)];
    }

    /**
     * Provincia aleatoria
     */
    private function randomProvince()
    {
        $provinces = ['San José', 'Alajuela', 'Cartago', 'Heredia', 'Guanacaste', 'Puntarenas', 'Limón'];
        return $provinces[array_rand($provinces)];
    }
}
