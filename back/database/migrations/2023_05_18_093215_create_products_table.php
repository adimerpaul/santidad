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
            $table->integer('cantidadSucursal1')->default(0)->comment('cantidad en stock sucursal 1');
            $table->integer('cantidadSucursal2')->default(0)->comment('cantidad en stock sucursal 2');
            $table->integer('cantidadSucursal3')->default(0)->comment('cantidad en stock sucursal 3');
            $table->integer('cantidadSucursal4')->default(0)->comment('cantidad en stock sucursal 4');
            $table->double('costo',10,2)->nullable();
            $table->double('precioAntes',10,2)->nullable();
            $table->double('precio',10,2)->nullable();
            //$table->double('utilidad',10,2)->nullable();
            $table->string('activo')->default('ACTIVO');
            $table->string('unidad')->default('UNIDAD');
            $table->string('registroSanitario')->nullable();
            $table->string('paisOrigen')->nullable();
            $table->string('nombreComun')->nullable();
            $table->string('composicion')->nullable();
            $table->string('marca')->nullable();
            $table->string('distribuidora')->nullable();
            $table->string('imagen')->nullable()->default('productDefault.jpg');
//            $table->string('color')->nullable();
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger("category_id")->nullable();
            $table->foreign("category_id")->references("id")->on("categories");
            $table->unsignedBigInteger("agencia_id")->nullable();
            $table->foreign("agencia_id")->references("id")->on("agencias");
            $table->unsignedBigInteger("subcategory_id")->nullable();
            $table->foreign("subcategory_id")->references("id")->on("subcategories");
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
