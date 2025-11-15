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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            // Relación con usuario (1:N)
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Etiqueta personalizada
            $table->string('label', 50)
                  ->comment('Casa, Trabajo, Otro');

            // Estructura de Costa Rica
            $table->string('province', 100)
                  ->comment('Provincia de Costa Rica');

            $table->string('canton', 100)
                  ->comment('Cantón de la provincia');

            $table->string('district', 100)
                  ->comment('Distrito del cantón');

            // Dirección específica
            $table->text('address_details')
                  ->comment('Señas exactas: calle, número, referencias');

            // Dirección predeterminada
            $table->boolean('is_default')
                  ->default(false)
                  ->comment('Dirección predeterminada para pedidos');

            $table->timestamps();

            // Índices
            $table->index('user_id');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
