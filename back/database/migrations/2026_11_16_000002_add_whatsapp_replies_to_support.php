<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            $table->text('whatsapp_recipient')->nullable();
            $table->string('whatsapp_phone_id', 32)->nullable();
            $table->unsignedBigInteger('whatsapp_last_inbound')->nullable();
        });

        Schema::table('support_messages', function (Blueprint $table) {
            $table->uuid('whatsapp_send_key')->nullable();
            $table->string('whatsapp_state', 16)->nullable();
            $table->string('whatsapp_message_id')->nullable();
            $table->unique(['support_case_id', 'whatsapp_send_key']);
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropUnique(['support_case_id', 'whatsapp_send_key']);
            $table->dropColumn(['whatsapp_send_key', 'whatsapp_state', 'whatsapp_message_id']);
        });
        Schema::table('support_cases', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_recipient', 'whatsapp_phone_id', 'whatsapp_last_inbound']);
        });
    }
};
