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
    Schema::table('pedidos', function (Blueprint $table) {
        // Usamos unsignedInteger porque tu tabla providers parece usar ese tipo
        $table->unsignedInteger('proveedor_id')->nullable()->after('user_id');
        
        $table->foreign('proveedor_id')->references('id')->on('providers');
    });
}

public function down()
{
    Schema::table('pedidos', function (Blueprint $table) {
        $table->dropForeign(['proveedor_id']);
        $table->dropColumn('proveedor_id');
    });
}
};
