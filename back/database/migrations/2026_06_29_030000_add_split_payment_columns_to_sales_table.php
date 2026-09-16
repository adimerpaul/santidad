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
        Schema::table('sales', function (Blueprint $table) {
            $table->double('montoEfectivo', 11, 2)->nullable()->default(0.00)->after('metodoPago');
            $table->double('montoQr', 11, 2)->nullable()->default(0.00)->after('montoEfectivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['montoEfectivo', 'montoQr']);
        });
    }
};
