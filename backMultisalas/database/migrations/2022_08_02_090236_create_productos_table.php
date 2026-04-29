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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            //$table->double('costo',10,2)->nullable();
            $table->double('precio',10,2)->nullable();
            //$table->double('utilidad',10,2)->nullable();
            $table->string('activo')->default('ACTIVO');
            $table->string('imagen')->nullable();
            $table->string('color')->nullable();
            //$table->integer('cantidad')->default(0);
            $table->unsignedBigInteger("rubro_id")->nullable();
            $table->foreign("rubro_id")->references("id")->on("rubros");

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
        Schema::dropIfExists('productos');
    }
};
