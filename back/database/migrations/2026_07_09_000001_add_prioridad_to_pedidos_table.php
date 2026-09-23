<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pedidos', 'prioridad')) {
            Schema::table('pedidos', function (Blueprint $table) {
                // NORMAL | URGENTE — usado por la app móvil de pre-venta
                $table->string('prioridad')->default('NORMAL')->after('estado');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pedidos', 'prioridad')) {
            Schema::table('pedidos', function (Blueprint $table) {
                $table->dropColumn('prioridad');
            });
        }
    }
};
