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
        Schema::table('withdrawal_items', function (Blueprint $table) {
            $table->string('central_estado')->default('PENDIENTE');
            $table->text('central_observacion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawal_items', function (Blueprint $table) {
            $table->dropColumn(['central_estado', 'central_observacion']);
        });
    }
};
