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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)
                  ->nullable()
                  ->default(null)
                  ->after('price')
                  ->comment('Precio de costo para cálculo de ganancias');

            $table->integer('stock_min')
                  ->default(0)
                  ->after('stock')
                  ->comment('Stock mínimo de seguridad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'stock_min']);
        });
    }
};
