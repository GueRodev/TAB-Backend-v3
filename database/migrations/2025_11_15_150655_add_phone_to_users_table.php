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
        Schema::table('users', function (Blueprint $table) {
            // Agregar campo phone después de email
            $table->string('phone', 20)->nullable()->after('email');

            // Índice para búsquedas
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar índice primero
            $table->dropIndex(['phone']);

            // Eliminar columna
            $table->dropColumn('phone');
        });
    }
};
