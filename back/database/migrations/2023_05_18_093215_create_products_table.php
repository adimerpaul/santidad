<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->string('barra')->nullable();
            $table->integer('cantidad')->default(0)->comment('cantidad en stock');
            $table->integer('cantidadAlmacen')->default(0)->comment('cantidad en stock almacen');
            $table->integer('cantidadSurcusal1')->default(0)->comment('cantidad en stock sucursal 1');
            $table->integer('cantidadSurcusal2')->default(0)->comment('cantidad en stock sucursal 2');
            $table->integer('cantidadSurcusal3')->default(0)->comment('cantidad en stock sucursal 3');
            $table->integer('cantidadSurcusal4')->default(0)->comment('cantidad en stock sucursal 4');
            $table->double('costo',10,2)->nullable();
            $table->double('precioAntes',10,2)->nullable();
            $table->double('precio',10,2)->nullable();
            //$table->double('utilidad',10,2)->nullable();
            $table->string('activo')->default('ACTIVO');
            $table->string('unidad')->default('UNIDAD');
            $table->string('imagen')->nullable()->default('productDefault.jpg');
//            $table->string('color')->nullable();
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger("category_id")->nullable();
            $table->foreign("category_id")->references("id")->on("categories");
            $table->unsignedBigInteger("agencia_id")->nullable();
            $table->foreign("agencia_id")->references("id")->on("agencias");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
