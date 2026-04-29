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
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('codigoActividad')->nullable();
            $table->string('codigoDocumentoSector')->nullable();
            $table->string('tipoDocumentoSector')->nullable();
            $table->integer("codigoPuntoVenta")->nullable()->default(0);
            $table->integer("codigoSucursal")->nullable()->default(0);
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
        Schema::dropIfExists('sectors');
    }
};
