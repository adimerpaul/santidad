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
        Schema::create('withdrawal_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agencia_id')->constrained('agencias');
            $table->integer('mes');
            $table->integer('anio');
            $table->foreignId('user_id')->constrained('users');
            $table->string('estado')->default('ABIERTO'); // ABIERTO, CERRADO
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_reports');
    }
};
