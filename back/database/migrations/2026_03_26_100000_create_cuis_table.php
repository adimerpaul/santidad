<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agencia_id')->nullable()->constrained('agencias')->nullOnDelete();
            $table->string('codigo', 50);
            $table->dateTime('fecha_vigencia')->nullable();
            $table->unsignedInteger('codigo_sucursal')->default(0);
            $table->unsignedInteger('codigo_punto_venta')->default(0);
            $table->string('nit', 30);
            $table->string('codigo_sistema', 80);
            $table->unsignedTinyInteger('codigo_modalidad');
            $table->unsignedTinyInteger('codigo_ambiente');
            $table->boolean('transaccion')->default(false);
            $table->boolean('activo')->default(true);
            $table->json('respuesta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['codigo_sucursal', 'codigo_punto_venta', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuis');
    }
};
