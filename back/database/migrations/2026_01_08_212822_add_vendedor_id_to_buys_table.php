<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('buys', function (Blueprint $table) {
        $table->foreignId('vendedor_id')->nullable()->constrained('vendedors');
    });
}

public function down()
{
    Schema::table('buys', function (Blueprint $table) {
        $table->dropForeign(['vendedor_id']);
        $table->dropColumn('vendedor_id');
    });
}
};
