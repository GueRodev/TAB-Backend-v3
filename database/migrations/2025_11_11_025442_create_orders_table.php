<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Número de pedido único (ej: ORD-20251110-0001)
            $table->string('order_number', 50)->unique();

            // Relación con usuario (puede ser null para guests)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('set null');

            // Tipo de pedido
            $table->enum('order_type', ['online', 'in_store'])
                  ->comment('online: desde carrito web, in_store: creado por admin en tienda física');

            // Estado del pedido
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled', 'archived'])
                  ->default('pending');

            // Información del cliente (obligatorio)
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20);
            $table->string('customer_email', 100)->nullable(); // Obligatorio para online, opcional para in_store

            // Opción de entrega
            $table->enum('delivery_option', ['pickup', 'delivery'])
                  ->comment('pickup: Recoger en tienda, delivery: Envío a domicilio');

            // Método de pago
            $table->enum('payment_method', ['cash', 'card', 'transfer', 'sinpe'])
                  ->comment('cash: Efectivo, card: Tarjeta, transfer: Transferencia, sinpe: SINPE Móvil');

            // Montos
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            // Notas opcionales
            $table->text('notes')->nullable();

            // Soft deletes y timestamps
            $table->softDeletes();
            $table->timestamps();

            // Índices para búsquedas y filtros frecuentes
            $table->index('order_number'); // Búsqueda por número de pedido
            $table->index('status'); // Filtro por estado
            $table->index('order_type'); // Filtro por tipo
            $table->index('customer_email'); // Búsqueda por email
            $table->index('customer_phone'); // Búsqueda por teléfono
            $table->index('payment_method'); // Reportes por método de pago
            $table->index('delivery_option'); // Reportes por opción de entrega
            $table->index('created_at'); // Orden cronológico
            $table->index(['order_type', 'status']); // Filtro combinado
            $table->index(['status', 'created_at']); // Estado + fecha
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
