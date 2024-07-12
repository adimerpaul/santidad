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
        Schema::table('agencias', function (Blueprint $table) {
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('atencion')->nullable();
            $table->string('horario')->nullable();
            $table->string('facebook')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('gps')->nullable();
            $table->string('latitud')->nullable();
            $table->string('longitud')->nullable();
            $table->string('status')->nullable()->default('INACTIVO');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencias', function (Blueprint $table) {
            $table->dropColumn('direccion');
            $table->dropColumn('telefono');
            $table->dropColumn('atencion');
            $table->dropColumn('horario');
            $table->dropColumn('facebook');
            $table->dropColumn('whatsapp');
            $table->dropColumn('gps');
            $table->dropColumn('latitud');
            $table->dropColumn('longitud');
            $table->dropColumn('status');
        });
    }
};
