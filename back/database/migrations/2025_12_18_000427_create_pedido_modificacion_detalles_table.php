<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_modificacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modificacion_id')->constrained('pedido_modificaciones')->onDelete('cascade');
            $table->foreignId('pedido_detail_id')->constrained('pedido_details')->onDelete('cascade');
            $table->integer('cantidad_anterior');
            $table->integer('cantidad_nueva');
            $table->string('accion')->nullable(); // TRANSFERIR, COMPRAR, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_modificacion_detalles');
    }
};