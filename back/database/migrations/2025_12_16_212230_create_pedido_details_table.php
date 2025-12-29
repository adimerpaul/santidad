<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_details', function (Blueprint $table) {
            $table->id();
            // Si borras el pedido, se borran sus detalles (cascade)
            $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->integer('cantidad');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_details');
    }
};