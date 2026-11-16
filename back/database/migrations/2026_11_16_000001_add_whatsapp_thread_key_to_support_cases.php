<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            $table->char('whatsapp_thread_key', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            $table->dropUnique(['whatsapp_thread_key']);
            $table->dropColumn('whatsapp_thread_key');
        });
    }
};
