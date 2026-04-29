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
        Schema::create('programas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->nullable();
            $table->dateTime("horaInicio")->nullable();
            $table->dateTime("horaFin")->nullable();
            $table->string("subtitulada")->nullable();
            $table->string("activo")->nullable()->default('ACTIVO');
            $table->string("nroFuncion")->nullable();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->foreign("user_id")->references("id")->on("users");
            $table->unsignedBigInteger("movie_id")->nullable();
            $table->foreign("movie_id")->references("id")->on("movies");
            $table->unsignedBigInteger("sala_id")->nullable();
            $table->foreign("sala_id")->references("id")->on("salas");
            $table->unsignedBigInteger("price_id")->nullable();
            $table->foreign("price_id")->references("id")->on("prices");
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
        Schema::dropIfExists('programas');
    }
};
