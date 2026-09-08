<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->decimal('porcentaje', 5, 2);
            $table->string('alcance', 20);
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            $table->boolean('permanente')->default(false);
            $table->boolean('activo')->default(true);
            $table->boolean('todas_agencias')->default(true);
            $table->boolean('canal_fisico')->default(true);
            $table->boolean('canal_web')->default(true);
            $table->boolean('canal_app')->default(true);
            $table->timestamps();

            $table->index(['activo', 'fecha_inicio', 'fecha_fin']);
            $table->index(['alcance', 'category_id']);
        });

        Schema::create('promotion_product', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->primary(['promotion_id', 'product_id']);
        });

        Schema::create('promotion_agencia', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('agencia_id')->constrained('agencias')->cascadeOnDelete();
            $table->primary(['promotion_id', 'agencia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_agencia');
        Schema::dropIfExists('promotion_product');
        Schema::dropIfExists('promotions');
    }
};
