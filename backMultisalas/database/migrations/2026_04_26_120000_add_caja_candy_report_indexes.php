<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['tipo', 'siatAnulado', 'fechaEmision', 'user_id'], 'sales_caja_candy_report_idx');
        });

        Schema::table('details', function (Blueprint $table) {
            $table->index(['sale_id', 'product_id'], 'details_caja_candy_report_idx');
            $table->index(['sale_id', 'pelicula_id'], 'details_caja_bol_report_idx');
        });

        Schema::table('anulaciones', function (Blueprint $table) {
            $table->index(['estado', 'fecha', 'user_id', 'sale_id'], 'anulaciones_caja_candy_report_idx');
        });
    }

    public function down()
    {
        Schema::table('anulaciones', function (Blueprint $table) {
            $table->dropIndex('anulaciones_caja_candy_report_idx');
        });

        Schema::table('details', function (Blueprint $table) {
            $table->dropIndex('details_caja_bol_report_idx');
            $table->dropIndex('details_caja_candy_report_idx');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_caja_candy_report_idx');
        });
    }
};
