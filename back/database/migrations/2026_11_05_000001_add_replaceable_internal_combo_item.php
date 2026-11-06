<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_combo_items', function (Blueprint $table) {
            $table->boolean('reemplazable')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('internal_combo_items', fn (Blueprint $table) => $table->dropColumn('reemplazable'));
    }
};
