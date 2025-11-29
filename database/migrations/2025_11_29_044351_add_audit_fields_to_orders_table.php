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
        Schema::table('orders', function (Blueprint $table) {
            // Auditoría de completado
            $table->unsignedBigInteger('completed_by')
                  ->nullable()
                  ->after('status')
                  ->comment('Usuario admin que completó la orden');

            $table->timestamp('completed_at')
                  ->nullable()
                  ->after('completed_by')
                  ->comment('Fecha y hora de completado');

            // Auditoría de cancelación
            $table->unsignedBigInteger('cancelled_by')
                  ->nullable()
                  ->after('completed_at')
                  ->comment('Usuario admin que canceló la orden');

            $table->timestamp('cancelled_at')
                  ->nullable()
                  ->after('cancelled_by')
                  ->comment('Fecha y hora de cancelación');

            // Auditoría de eliminación (soft delete)
            $table->unsignedBigInteger('deleted_by')
                  ->nullable()
                  ->after('deleted_at')
                  ->comment('Usuario admin que eliminó la orden');

            // Foreign keys
            $table->foreign('completed_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->foreign('cancelled_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->foreign('deleted_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['completed_by']);
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['deleted_by']);

            // Drop columns
            $table->dropColumn([
                'completed_by',
                'completed_at',
                'cancelled_by',
                'cancelled_at',
                'deleted_by',
            ]);
        });
    }
};
