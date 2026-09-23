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
            $table->index('nombre');
            $table->index('barra');
            $table->index('activo');
            $table->index('category_id');
            $table->index('subcategory_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['nombre']);
            $table->dropIndex(['barra']);
            $table->dropIndex(['activo']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['subcategory_id']);
        });
    }
};
