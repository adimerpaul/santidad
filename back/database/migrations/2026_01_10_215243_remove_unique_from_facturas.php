<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Eliminamos la regla que impide repetir números de factura
            // El nombre 'facturas_numero_factura_unique' es el que te salió en el error
            $table->dropUnique('facturas_numero_factura_unique');
            
            // Opcional: Si quieres evitar duplicados SOLO del mismo proveedor, descomenta esto:
            // $table->unique(['proveedor_id', 'numero_factura'], 'facturas_prov_num_unique');
        });
    }

    public function down()
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->unique('numero_factura');
        });
    }

};
