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
        Schema::create('order_shipping_addresses', function (Blueprint $table) {
            $table->id();

            // Relación con pedido (1:1)
            $table->foreignId('order_id')
                  ->unique()
                  ->constrained()
                  ->onDelete('cascade');

            // Dirección de Costa Rica (snapshot inmutable)
            $table->string('province', 100);
            $table->string('canton', 100);
            $table->string('district', 100);
            $table->text('address_details')
                  ->comment('Detalles específicos de la dirección');

            $table->timestamps();

            // Índice
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_shipping_addresses');
    }
};
