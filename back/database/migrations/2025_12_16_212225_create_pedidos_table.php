<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agencia_id')->constrained('agencias'); // Relación con Sucursal
            $table->foreignId('user_id')->constrained('users');       // Quién creó el pedido
            $table->date('fecha_pedido');
            $table->string('estado')->default('PENDIENTE'); // PENDIENTE, PROCESADO, CANCELADO
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};