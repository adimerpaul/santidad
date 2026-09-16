<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            // Agregamos la columna para saber quién envió. Puede ser NULL si es el Almacén Central.
            $table->unsignedBigInteger('agencia_origen_id')->nullable()->after('agencia_id');
            $table->foreign('agencia_origen_id')->references('id')->on('agencias')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropForeign(['agencia_origen_id']);
            $table->dropColumn('agencia_origen_id');
        });
    }
};