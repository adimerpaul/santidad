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
            $table->string('estado')->default('PENDIENTE')->after('descripcion');
            $table->date('prorroga_hasta')->nullable()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawal_items', function (Blueprint $table) {
            $table->dropColumn(['estado', 'prorroga_hasta']);
        });
    }
};
