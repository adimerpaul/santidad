<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index('fechaEmision');
            $table->index('agencia_id');
            $table->index('user_id');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['fechaEmision']);
            $table->dropIndex(['agencia_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['deleted_at']);
        });
    }
};
