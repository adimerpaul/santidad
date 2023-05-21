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
            $table->integer('cantidad')->default(0);
            $table->double('costo',10,2)->nullable();
            $table->double('precio',10,2)->nullable();
            //$table->double('utilidad',10,2)->nullable();
            $table->string('activo')->default('ACTIVO');
            $table->string('imagen')->nullable()->default('productDefault.jpg');
//            $table->string('color')->nullable();
            $table->string('descripcion')->nullable();
            $table->unsignedBigInteger("category_id")->nullable();
            $table->foreign("category_id")->references("id")->on("categories");
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
