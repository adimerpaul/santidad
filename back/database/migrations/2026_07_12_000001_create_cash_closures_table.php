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
        Schema::create('cash_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agencia_id')->constrained('agencias');
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('fecha_apertura');
            $table->dateTime('fecha_cierre');
            $table->double('monto_fisico', 11, 2);
            $table->double('monto_sistema_efectivo', 11, 2);
            $table->double('monto_sistema_digital', 11, 2);
            $table->double('monto_sistema_total', 11, 2);
            $table->double('diferencia', 11, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_closures');
    }
};
