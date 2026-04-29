<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('facturas')) {
            Schema::create('facturas', function (Blueprint $table) {
                $table->id();
                $table->integer("n")->nullable();
                $table->date("fecha")->nullable();
                $table->integer("nFactura")->nullable();
                $table->string("cuf")->nullable();
                $table->string("nit")->nullable();
                $table->string("complemento")->nullable();
                $table->string("nombre")->nullable();
                $table->string("importe")->nullable();
                $table->integer("ice")->nullable();
                $table->integer("iehd")->nullable();
                $table->integer("ipj")->nullable();
                $table->integer("tasas")->nullable();
                $table->integer("noSujeto")->nullable();
                $table->integer("exentas")->nullable();
                $table->integer("tasaCero")->nullable();
                $table->double("subTotal",11,2)->nullable();
                $table->integer("rebajas")->nullable();
                $table->integer("card")->nullable();
                $table->double("importeBase",11,2)->nullable();
                $table->double("iva",11,2)->nullable();
                $table->string("estado")->nullable();
                $table->string("codigoControl")->nullable();
                $table->string("tipoVenta")->nullable();
                $table->string("derecho")->nullable();
                $table->string("consolidado")->nullable();
                $table->string("impuesto",2)->nullable()->default("NO");
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('facturas');
    }
};
