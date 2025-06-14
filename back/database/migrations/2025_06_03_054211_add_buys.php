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
        Schema::table('buys', function (Blueprint $table) {
            //venta numbre
            $table->integer('cantidadVendida')->default(0)->after('cantidadBaja')->comment('Cantidad vendida del producto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buys', function (Blueprint $table) {
            //venta
            $table->dropColumn('cantidadVendida');
        });
    }
};
