<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('orders', function (Blueprint $table) {
      $table->id();
      $table->string('order_number')->unique(); // PEDIDOWEB_Nº{id}
      $table->unsignedBigInteger('customer_id')->nullable();
      $table->string('customer_name')->nullable();
      $table->string('customer_phone')->nullable();
      $table->string('customer_address')->nullable();

      $table->decimal('subtotal', 12, 2)->default(0);
      $table->decimal('shipping', 12, 2)->default(0);
      $table->decimal('total', 12, 2)->default(0);
      $table->string('status')->default('pending'); // pending|confirmed|canceled
      $table->string('source')->default('web');     // web|whatsapp|app
      $table->text('whatsapp_message')->nullable();
      $table->json('meta')->nullable();

      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('orders');
  }
};
