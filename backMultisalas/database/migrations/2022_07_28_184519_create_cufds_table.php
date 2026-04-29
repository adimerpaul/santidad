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
        Schema::create('cufds', function (Blueprint $table) {
            $table->id();
            $table->string("codigo")->nullable();
            $table->string("codigoControl")->nullable();
            $table->string("direccion")->nullable();
            $table->dateTime("fechaVigencia")->nullable();
            $table->dateTime("fechaCreacion")->nullable();
            $table->integer("codigoPuntoVenta")->nullable()->default(0);
            $table->integer("codigoSucursal")->nullable()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cufds');
    }
};
