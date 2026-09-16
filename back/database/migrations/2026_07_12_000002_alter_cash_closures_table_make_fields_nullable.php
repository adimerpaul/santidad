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
        Schema::table('cash_closures', function (Blueprint $table) {
            $table->dateTime('fecha_cierre')->nullable()->change();
            $table->double('monto_fisico', 11, 2)->nullable()->change();
            $table->double('monto_sistema_efectivo', 11, 2)->nullable()->change();
            $table->double('monto_sistema_digital', 11, 2)->nullable()->change();
            $table->double('monto_sistema_total', 11, 2)->nullable()->change();
            $table->double('diferencia', 11, 2)->nullable()->change();
            $table->text('observaciones_apertura')->nullable()->after('observaciones');
            $table->string('estado')->default('ABIERTO')->after('observaciones_apertura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_closures', function (Blueprint $table) {
            // Revert fields to non-nullable if rolled back
        });
    }
};
