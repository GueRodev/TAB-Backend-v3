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
        Schema::create('cr_locations', function (Blueprint $table) {
            $table->id();

            // IDs originales del JSON
            $table->integer('province_id');
            $table->integer('canton_id')->nullable();

            // Nombres
            $table->string('province_name', 100);
            $table->string('canton_name', 100)->nullable();
            $table->string('district_name', 100)->nullable();

            // Tipo de registro (province, canton, district)
            $table->enum('type', ['province', 'canton', 'district']);

            $table->timestamps();

            // Índices
            $table->index('province_id');
            $table->index('canton_id');
            $table->index('type');
            $table->unique(['province_id', 'canton_id', 'district_name'], 'unique_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cr_locations');
    }
};
