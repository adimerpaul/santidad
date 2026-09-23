<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('agencias', function (Blueprint $table) {
            $table->tinyInteger('sucursal')->default(0)->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('agencias', function (Blueprint $table) {
            $table->dropColumn('sucursal');
        });
    }
};
