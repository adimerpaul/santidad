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
        Schema::create('buys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('product_id')->constrained();
//            $table->foreignId('agencia_id')->constrained();
            $table->unsignedBigInteger('agencia_id')->nullable();
            $table->foreign('agencia_id')->references('id')->on('agencias');
            $table->string('lote');
            $table->integer('quantity');
            $table->decimal('total', 10, 2);
            $table->decimal('price', 10, 2);
            $table->date('dateExpiry');
            $table->string('factura');
            $table->date('date');
            $table->time('time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buys');
    }
};
