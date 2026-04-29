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
        Schema::create('web_movie_actors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('web_movie_id');
            $table->string('nombre');
            $table->string('imagen')->nullable();
            $table->timestamps();

            $table->foreign('web_movie_id')
                ->references('id')
                ->on('web_movies')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('web_movie_actors');
    }
};

