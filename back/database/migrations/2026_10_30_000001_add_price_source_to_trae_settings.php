<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trae_settings', function (Blueprint $table) {
            $table->string('fuente_precios', 20)->default('propio');
            $table->foreignId('fuente_agencia_id')->nullable()->constrained('agencias')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trae_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fuente_agencia_id');
            $table->dropColumn('fuente_precios');
        });
    }
};
