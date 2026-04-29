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
        if (!Schema::hasTable('anulaciones')) {
            Schema::create('anulaciones', function (Blueprint $table) {
                $table->id();
                $table->date('fecha');
                $table->string('cajero');
                $table->decimal('monto', 10, 2);
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('user_autoriza_id')->nullable();
                $table->unsignedBigInteger('user_anulacion_id')->nullable();
                $table->string('motivo')->nullable();
                $table->unsignedBigInteger('sale_id')->nullable();
                $table->string('seccion')->nullable();
                $table->string('detalle')->nullable();
                $table->string('estado')->default('Pendiente');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('user_autoriza_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('user_anulacion_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
                $table->softDeletes();
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
        Schema::dropIfExists('anulaciones');
    }
};
