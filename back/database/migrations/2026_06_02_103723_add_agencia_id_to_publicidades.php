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
        Schema::table('publicidads', function (Blueprint $colum) {
            $colum->unsignedBigInteger('agencia_id')->nullable()->after('id');
            $colum->foreign('agencia_id')->references('id')->on('agencias')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publicidads', function (Blueprint $table) {
            $table->dropForeign(['agencia_id']);
            $table->dropColumn('agencia_id');
        });
    }
};
