<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            $table->text('whatsapp_context')->nullable();
            $table->string('whatsapp_indicator', 32)->nullable();
            $table->string('whatsapp_handoff_reason', 100)->nullable();
            $table->timestamp('whatsapp_context_expires_at')->nullable()->index();
        });
        Schema::table('agencias', function (Blueprint $table) {
            $table->json('whatsapp_hours')->nullable();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->string('whatsapp_classification', 24)->default('pending')->index();
        });
        Schema::create('whatsapp_ai_limits', function (Blueprint $table) {
            $table->string('bucket', 100)->primary();
            $table->unsignedBigInteger('used')->default(0);
            $table->timestamp('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_ai_limits');
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['whatsapp_classification']);
            $table->dropColumn('whatsapp_classification');
        });
        Schema::table('agencias', fn (Blueprint $table) => $table->dropColumn('whatsapp_hours'));
        Schema::table('support_cases', function (Blueprint $table) {
            $table->dropIndex(['whatsapp_context_expires_at']);
            $table->dropColumn(['whatsapp_context', 'whatsapp_indicator', 'whatsapp_handoff_reason', 'whatsapp_context_expires_at']);
        });
    }
};
