<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trae_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('descuento_general', 5, 2)->default(0);
            $table->decimal('comision_porcentaje', 5, 2)->default(12);
            $table->timestamps();
        });
        DB::table('trae_settings')->insert([
            'id' => 1, 'descuento_general' => 0, 'comision_porcentaje' => 12,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Schema::create('trae_discount_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->unique()->constrained('categories')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->unique()->constrained('products')->cascadeOnDelete();
            $table->decimal('porcentaje', 5, 2);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->string('canal_venta', 20)->default('sucursal');
            $table->decimal('comision_trae_porcentaje', 5, 2)->default(0);
            $table->decimal('comision_trae_monto', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn([
            'canal_venta', 'comision_trae_porcentaje', 'comision_trae_monto',
        ]));
        Schema::dropIfExists('trae_discount_rules');
        Schema::dropIfExists('trae_settings');
    }
};
