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
            $table->text('admin_descripcion')->nullable()->after('descripcion');
            $table->integer('cantidad_original')->nullable()->after('cantidad');
            $table->integer('agencia_id_original')->nullable()->after('agencia_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawal_items', function (Blueprint $table) {
            $table->dropColumn(['admin_descripcion', 'cantidad_original', 'agencia_id_original']);
        });
    }
};
