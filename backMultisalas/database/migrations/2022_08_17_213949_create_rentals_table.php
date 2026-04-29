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
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->integer("numeroFactura")->nullable();
            $table->string("cuf")->nullable();
            $table->string("cufd")->nullable();
            $table->string("cui")->nullable();
            $table->integer("codigoSucursal")->nullable();
            $table->integer("codigoPuntoVenta")->nullable();
            $table->dateTime("fechaEmision")->nullable();
            $table->double("montoTotal",11,2)->nullable();
            $table->string("usuario")->nullable();
            $table->string("periodoFacturado")->nullable();
            $table->string("codigoRecepcion")->nullable();
            $table->boolean("siatEnviado")->nullable();
            $table->string("codigoRecepcionEventoSignificativo")->nullable();
            $table->boolean("siatAnulado")->nullable()->default(false);
            $table->integer("codigoDocumentoSector")->nullable();
            $table->string("actividadEconomica")->nullable();
            $table->string("codigoProductoSin")->nullable();
            $table->string("codigoProducto")->nullable();
            $table->string("descripcion")->nullable();
            $table->string("cantidad")->nullable();
            $table->string("unidadMedida")->nullable();
            $table->string("medida")->nullable();
            $table->string("precioUnitario")->nullable();
            $table->string("montoDescuento")->nullable();
            $table->string("subTotal")->nullable();
            $table->string("leyenda")->nullable();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->foreign("user_id")->references("id")->on("users");
            $table->unsignedBigInteger("cufd_id")->nullable();
            $table->foreign("cufd_id")->references("id")->on("cufds");
            $table->unsignedBigInteger("client_id")->nullable();
            $table->foreign("client_id")->references("id")->on("clients");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rentals');
    }
};
