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
        Schema::create('siat_envios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->unique();
            $table->string('codigo_evento')->nullable();
            $table->string('codigo_recepcion')->nullable();
            $table->enum('estado', ['pendiente', 'enviado', 'validado', 'error'])->default('pendiente');
            $table->text('ultimo_mensaje')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siat_envios');
    }
};
