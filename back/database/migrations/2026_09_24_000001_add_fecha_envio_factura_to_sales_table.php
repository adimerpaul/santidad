<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fecha con la que la factura se declaró a SIAT cuando se reemite (envío
     * de pendientes con CUFD nuevo). `fechaEmision` se conserva intacta porque
     * es la que usa la caja y los reportes.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dateTime('fechaEnvioFactura')->nullable()->after('fechaEmision');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('fechaEnvioFactura');
        });
    }
};
