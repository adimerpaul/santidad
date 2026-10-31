<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Restrict the report to TRAE before scanning dates or branches.
            $table->index(['canal_venta', 'deleted_at', 'fechaEmision', 'id'], 'idx_sales_trae_fecha');
            $table->index(['canal_venta', 'deleted_at', 'agencia_id', 'fechaEmision', 'id'], 'idx_sales_trae_agencia_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_trae_fecha');
            $table->dropIndex('idx_sales_trae_agencia_fecha');
        });
    }
};
