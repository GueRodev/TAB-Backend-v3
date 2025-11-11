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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            // Relación con pedido
            $table->foreignId('order_id')
                  ->constrained()
                  ->onDelete('cascade'); // Si se elimina el pedido, se eliminan los items

            // Relación con producto (puede ser null si el producto se elimina)
            $table->foreignId('product_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('set null');

            // Snapshots inmutables del producto al momento de la venta
            $table->string('product_name', 255);
            $table->string('product_sku', 100)->nullable();
            $table->text('product_description')->nullable();
            $table->string('product_image_url', 500)->nullable();

            // Cantidad y precios
            $table->integer('quantity');
            $table->decimal('price_at_purchase', 10, 2)
                  ->comment('Precio unitario al momento de la compra');
            $table->decimal('subtotal', 10, 2)
                  ->comment('quantity * price_at_purchase');

            $table->timestamps();

            // Índices
            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
