<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('publicidads', function (Blueprint $table) {
            $table->string('media_sha256', 64)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
        });
        Schema::create('publicidad_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('agencia_id')->primary();
            $table->unsignedBigInteger('revision')->default(0);
            $table->longText('payload');
            $table->boolean('pending')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publicidad_schedules');
        Schema::table('publicidads', fn (Blueprint $table) => $table->dropColumn(['media_sha256', 'size_bytes']));
    }
};
