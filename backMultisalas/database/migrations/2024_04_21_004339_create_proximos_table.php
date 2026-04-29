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
        if (!Schema::hasTable('proximos')) {
            Schema::create('proximos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('descripcion');
                $table->date('fecha');
                $table->integer('start');
                $table->string('director');
                $table->string('imagen');
                $table->string('trailer');
                $table->string('estado');
                $table->integer('minutos');
                $table->string('pais');
                $table->string('clasificacion');
                $table->string('generos');
                $table->timestamps();
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
        Schema::dropIfExists('proximos');
    }
};
