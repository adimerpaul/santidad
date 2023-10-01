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
        Schema::create('sales', function (Blueprint $table) {
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
            $table->text("concepto")->nullable();
            $table->string("codigoRecepcion")->nullable();
            $table->boolean("siatEnviado")->nullable()->default(false);
            $table->string("codigoRecepcionEventoSignificativo")->nullable();
            $table->boolean("siatAnulado")->nullable()->default(false);
            $table->string("tipoVenta")->nullable()->default('Ingreso');
            $table->string("metodoPago")->nullable()->default('Efectivo');
            $table->integer("codigoDocumentoSector")->nullable();
            $table->string("leyenda")->nullable();
            $table->string("venta")->default('R');
            $table->double("aporte",11,2)->nullable();
            $table->string("estado")->nullable()->default('ACTIVO')->comment('ACTIVO, ANULADO');
            $table->string("qr")->nullable()->default('No');
            $table->unsignedBigInteger("user_id")->nullable();
//            $table->foreign("user_id")->references("id")->on("users");
            $table->unsignedBigInteger("cufd_id")->nullable();
//            $table->foreign("cufd_id")->references("id")->on("cufds");
            $table->unsignedBigInteger("client_id")->nullable();
//            $table->foreign("client_id")->references("id")->on("clients");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
