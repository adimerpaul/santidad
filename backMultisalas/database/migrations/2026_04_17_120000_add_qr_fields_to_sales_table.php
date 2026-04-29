<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('pagoQr')->default(false)->after('credito');
            $table->string('qrId')->nullable()->after('pagoQr');
            $table->string('qrTransactionId')->nullable()->after('qrId');
            $table->dateTime('qrPagadoAt')->nullable()->after('qrTransactionId');
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['pagoQr', 'qrId', 'qrTransactionId', 'qrPagadoAt']);
        });
    }
};
