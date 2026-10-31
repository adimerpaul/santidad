<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('request_hash', 64);
            $table->foreignId('sale_id')->nullable()->unique()->constrained('sales')->restrictOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->string('warning')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('sale_requests'); }
};
