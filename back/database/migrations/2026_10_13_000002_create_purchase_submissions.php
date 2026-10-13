<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id');
            $table->unsignedBigInteger('user_id');
            $table->string('operation', 20);
            $table->string('input_hash', 64);
            $table->longText('response')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamps();
            $table->unique(['request_id', 'user_id', 'operation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_submissions');
    }
};
