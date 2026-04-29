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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string("nombre")->nullable();
            $table->integer("duracion")->nullable();
            $table->string("paisOrigen")->nullable();
            $table->string("genero")->nullable();
            $table->string("sipnosis")->nullable();
            $table->string("formato")->nullable()->default('2D');
            $table->string("urlTrailer")->nullable();
            $table->string("imagen")->nullable()->default('default.jpg');
            $table->string("clasificacion")->nullable();
            $table->date("fechaEstreno")->nullable();
            $table->unsignedBigInteger("distributor_id");
            $table->foreign("distributor_id")->references("id")->on("distributors");
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
        Schema::dropIfExists('movies');
    }
};
