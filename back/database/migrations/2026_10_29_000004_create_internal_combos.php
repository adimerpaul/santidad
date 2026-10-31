<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_combos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('modo', 20);
            $table->decimal('porcentaje', 8, 4)->default(0);
            $table->decimal('precio_objetivo', 12, 2)->nullable();
            $table->string('canal', 20)->default('trae');
            $table->boolean('activo')->default(true);
            $table->boolean('permanente')->default(true);
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('internal_combo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_combo_id')->constrained('internal_combos')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_base', 12, 2);
            $table->decimal('precio_unitario', 12, 2);
            $table->unique(['internal_combo_id', 'product_id']);
        });
        Schema::table('sales', fn (Blueprint $table) => $table->json('internal_combos')->nullable());
        Schema::table('details', function (Blueprint $table) {
            $table->unsignedBigInteger('combo_id')->nullable();
            $table->string('combo_nombre', 100)->nullable();
            $table->uuid('combo_grupo')->nullable();
            $table->unsignedInteger('combo_cantidad')->nullable();
            $table->unsignedInteger('combo_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('details', fn (Blueprint $table) => $table->dropColumn(['combo_id', 'combo_nombre', 'combo_grupo', 'combo_cantidad', 'combo_version']));
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn('internal_combos'));
        Schema::dropIfExists('internal_combo_items');
        Schema::dropIfExists('internal_combos');
    }
};
