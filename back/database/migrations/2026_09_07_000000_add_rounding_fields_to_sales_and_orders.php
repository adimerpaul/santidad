<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('montoCalculado', 11, 2)->nullable()->after('montoTotal');
            $table->decimal('ajusteRedondeo', 11, 2)->default(0)->after('montoCalculado');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('calculated_total', 12, 2)->nullable()->after('total');
            $table->decimal('rounding_adjustment', 12, 2)->default(0)->after('calculated_total');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['calculated_total', 'rounding_adjustment']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['montoCalculado', 'ajusteRedondeo']);
        });
    }
};
