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
        Schema::create('web_movies', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->enum('tipo', ['pelicula', 'proximo'])->default('pelicula');
            $table->unsignedBigInteger('tmdb_id')->nullable()->index();
            $table->string('imdb_id')->nullable()->index();
            $table->string('titulo_original')->nullable();
            $table->unsignedBigInteger('web_studio_id')->nullable();
            $table->string('imagen')->nullable();
            $table->string('trailer_youtube')->nullable();
            $table->decimal('puntaje_web', 5, 2)->nullable();
            $table->decimal('puntaje_tomatoes', 5, 2)->nullable();
            $table->decimal('puntaje_ibm', 5, 2)->nullable();
            $table->decimal('puntaje_metacritic', 5, 2)->nullable();
            $table->string('calidad')->nullable();
            $table->integer('anio')->nullable();
            $table->date('fecha_estreno')->nullable();
            $table->string('tele')->nullable();
            $table->text('descripcion')->nullable();
            $table->integer('me_gusta')->default(0);
            $table->string('duracion')->nullable();
            $table->string('genero')->nullable();
            $table->string('pais')->nullable();
            $table->string('idioma')->nullable();
            $table->string('clasificacion')->nullable();
            $table->string('premios')->nullable();
            $table->string('estado')->default('ACTIVO');
            $table->json('api_payload')->nullable();
            $table->timestamps();

            $table->foreign('web_studio_id')
                ->references('id')
                ->on('web_studios')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('web_movies');
    }
};
