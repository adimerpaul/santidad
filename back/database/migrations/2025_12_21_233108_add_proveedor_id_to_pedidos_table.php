<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // PASO 1: "Limpieza automática"
        // Si la columna existe mal creada (como int), la borramos para evitar errores.
        if (Schema::hasColumn('pedidos', 'proveedor_id')) {
            Schema::table('pedidos', function (Blueprint $table) {
                $table->dropColumn('proveedor_id');
            });
        }

        // PASO 2: Crear la columna correcta y la relación
        Schema::table('pedidos', function (Blueprint $table) {
            // Creamos la columna como unsignedBigInteger (para que coincida con clients.id)
            $table->unsignedBigInteger('proveedor_id')->nullable()->after('user_id');

            // Creamos la relación (Foreign Key)
            $table->foreign('proveedor_id')
                  ->references('id')
                  ->on('clients') // Conectamos con la tabla 'clients'
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Eliminar la relación y la columna si revertimos
            $table->dropForeign(['proveedor_id']);
            $table->dropColumn('proveedor_id');
        });
    }
};