<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            $table->string('whatsapp_bot_step', 16)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            $table->dropColumn('whatsapp_bot_step');
        });
    }
};
