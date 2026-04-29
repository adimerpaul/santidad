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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('numboc')->nullable();
            $table->string('numero')->nullable();
            $table->date('fecha')->nullable();
            $table->string('numeroFuncion')->nullable();
            $table->string('nombreSala')->nullable();
            $table->string('serieTarifa')->nullable();
            $table->date('fechaFuncion')->nullable();
            $table->datetime('horaFuncion')->nullable();
            $table->integer('fila')->nullable();
            $table->integer('columna')->nullable();
            $table->string('letra')->nullable();
            $table->string('costo')->nullable();
            $table->string('titulo')->nullable();
            $table->boolean('promo')->default(false);
            $table->string('devuelto')->nullable()->default(0);
            $table->string('idCupon')->nullable();
            $table->string('pelicula_id')->nullable();
            $table->string('tarjeta')->nullable()->default('NO');
            $table->string('credito')->nullable()->default('NO');
            $table->unsignedBigInteger("client_id")->nullable();
            $table->foreign("client_id")->references("id")->on("clients");
            $table->unsignedBigInteger("programa_id")->nullable();
            $table->foreign("programa_id")->references("id")->on("programas");
            $table->unsignedBigInteger("sale_id")->nullable();
            $table->foreign("sale_id")->references("id")->on("sales");
            $table->unsignedBigInteger("price_id")->nullable();
            $table->foreign("price_id")->references("id")->on("prices");
            $table->unsignedBigInteger("sala_id")->nullable();
            $table->foreign("sala_id")->references("id")->on("salas");
            $table->unsignedBigInteger("user_id")->nullable();
            $table->foreign("user_id")->references("id")->on("users");
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
        Schema::dropIfExists('tickets');
    }
};
