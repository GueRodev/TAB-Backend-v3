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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Solo 'order' se usa actualmente - los demás tipos están comentados en el código
            $table->enum('type', ['order', 'user', 'product', 'stock', 'general']);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Información adicional (IDs, links, etc)
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            // COMENTADO: No utilizamos soft delete por el momento
            // $table->softDeletes(); // Para permitir eliminación suave

            // Índices para mejorar rendimiento
            $table->index(['user_id', 'read_at']);
            $table->index('created_at');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
