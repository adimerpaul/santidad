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
        Schema::create('detail_candies', function (Blueprint $table) {
            $table->id();
            $table->string("actividadEconomica")->nullable();
            $table->string("codigoProductoSin")->nullable();
            $table->string("cantidad")->nullable();
            $table->string("precioUnitario")->nullable();
            $table->string("subTotal")->nullable();
            $table->string("producto_id")->nullable();
            $table->string("descripcion")->nullable();
            $table->unsignedBigInteger("sale_id")->nullable();
            $table->foreign("sale_id")->references("id")->on("sales");

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
        Schema::dropIfExists('detail_candies');
    }
};
