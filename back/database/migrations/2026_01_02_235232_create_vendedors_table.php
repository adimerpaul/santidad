<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::create('vendedors', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('celular'); // Para WhatsApp
            $table->foreignId('client_id')->constrained('clients'); // Relación con Proveedor (Tabla clients)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendedors');
    }
};
