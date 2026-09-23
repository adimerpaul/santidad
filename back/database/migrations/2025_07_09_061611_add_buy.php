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
            $table->softDeletes()->after('sucursal_id_baja');
            $table->foreignId('agencia_comprador_id')->nullable()->constrained('agencias')->after('agencia_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buys', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['agencia_comprador_id']);
            $table->dropColumn('agencia_comprador_id');
        });
    }
};
