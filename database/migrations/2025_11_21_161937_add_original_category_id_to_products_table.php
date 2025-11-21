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
            // Add original_category_id to track the original category before reassignment
            $table->unsignedBigInteger('original_category_id')->nullable()->after('category_id');

            // Add foreign key constraint
            $table->foreign('original_category_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('set null');

            $table->index('original_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop foreign key and column
            $table->dropForeign(['original_category_id']);
            $table->dropColumn('original_category_id');
        });
    }
};
