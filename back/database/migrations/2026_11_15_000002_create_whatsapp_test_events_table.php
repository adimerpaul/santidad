<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_test_events', function (Blueprint $table) {
            $table->id();
            $table->char('event_key', 64)->unique();
            $table->string('phone_number_id', 32);
            $table->string('kind', 40);
            $table->timestamp('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_test_events');
    }
};
