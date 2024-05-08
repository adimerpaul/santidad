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
        Schema::create('transfer_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('agencia_id_origen')->nullable();
            $table->foreign('agencia_id_origen')->references('id')->on('agencias');
            $table->unsignedBigInteger('agencia_id_destino')->nullable();
            $table->foreign('agencia_id_destino')->references('id')->on('agencias');
            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')->references('id')->on('products');
            $table->integer('cantidad');
            $table->date('fecha');
            $table->date('fecha_entrega_vencimiento')->nullable();
            $table->time('hora');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_histories');
    }
};
