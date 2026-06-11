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
        Schema::create('withdrawal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('withdrawal_report_id')->constrained('withdrawal_reports')->onDelete('cascade');
            $table->foreignId('buy_id')->nullable()->constrained('buys');
            $table->foreignId('product_id')->constrained('products');
            $table->integer('cantidad');
            $table->string('tipo'); // VENCIMIENTO, DEVOLUCION, OTRO
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_items');
    }
};
