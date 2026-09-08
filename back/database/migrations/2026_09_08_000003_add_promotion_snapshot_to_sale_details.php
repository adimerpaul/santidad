<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $details = [
            'precioOriginal' => !Schema::hasColumn('details', 'precioOriginal'),
            'promocion_id' => !Schema::hasColumn('details', 'promocion_id'),
            'promocion_nombre' => !Schema::hasColumn('details', 'promocion_nombre'),
            'promocion_porcentaje' => !Schema::hasColumn('details', 'promocion_porcentaje'),
        ];
        if (in_array(true, $details, true)) {
            Schema::table('details', function (Blueprint $table) use ($details) {
                if ($details['precioOriginal']) {
                    $table->decimal('precioOriginal', 11, 2)->nullable()->after('precioUnitario');
                }
                if ($details['promocion_id']) {
                    $table->foreignId('promocion_id')->nullable()->after('precioOriginal')->constrained('promotions')->nullOnDelete();
                }
                if ($details['promocion_nombre']) {
                    $table->string('promocion_nombre', 150)->nullable()->after('promocion_id');
                }
                if ($details['promocion_porcentaje']) {
                    $table->decimal('promocion_porcentaje', 5, 2)->nullable()->after('promocion_nombre');
                }
            });
        }

        $orderItems = [
            'original_price' => !Schema::hasColumn('order_items', 'original_price'),
            'promotion_id' => !Schema::hasColumn('order_items', 'promotion_id'),
            'promotion_name' => !Schema::hasColumn('order_items', 'promotion_name'),
            'discount_percentage' => !Schema::hasColumn('order_items', 'discount_percentage'),
        ];
        if (in_array(true, $orderItems, true)) {
            Schema::table('order_items', function (Blueprint $table) use ($orderItems) {
                if ($orderItems['original_price']) {
                    $table->decimal('original_price', 12, 2)->nullable()->after('price');
                }
                if ($orderItems['promotion_id']) {
                    $table->foreignId('promotion_id')->nullable()->after('original_price')->constrained('promotions')->nullOnDelete();
                }
                if ($orderItems['promotion_name']) {
                    $table->string('promotion_name', 150)->nullable()->after('promotion_id');
                }
                if ($orderItems['discount_percentage']) {
                    $table->decimal('discount_percentage', 5, 2)->nullable()->after('promotion_name');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promocion_id');
            $table->dropColumn(['precioOriginal', 'promocion_nombre', 'promocion_porcentaje']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropColumn(['original_price', 'promotion_name', 'discount_percentage']);
        });
    }
};
