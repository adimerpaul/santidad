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
        Schema::create('momentaneos', function (Blueprint $table) {
            $table->id();
            $table->integer('fila')->nullable();
            $table->integer('columna')->nullable();
            $table->string('letra')->nullable();
            $table->dateTime('fecha')->nullable();
            $table->double('precio',11,2)->nullable();
            $table->string('pelicula')->nullable();
            $table->boolean('promo')->default(false);
            $table->string('pelicula_id')->nullable();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->foreign("user_id")->references("id")->on("users");
            $table->unsignedBigInteger("programa_id")->nullable();
            $table->foreign("programa_id")->references("id")->on("programas");
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
        Schema::dropIfExists('momentaneos');
    }
};
