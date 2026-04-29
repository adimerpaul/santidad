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
        if (!Schema::hasTable('web_movie_programa')) {
            Schema::create('web_movie_programa', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('web_movie_id');
                $table->unsignedBigInteger('programa_id');
                $table->timestamps();

                $table->unique(['web_movie_id', 'programa_id']);
                $table->foreign('web_movie_id')->references('id')->on('web_movies')->cascadeOnDelete();
                $table->foreign('programa_id')->references('id')->on('programas')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('web_movie_programa');
    }
};

