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
        // Creamos la columna. Usamos unsignedBigInteger que es el estándar para IDs
        // Si tu tabla 'clients' usa Integer normal, cambia esto a: $table->unsignedInteger(...)
        $table->unsignedBigInteger('proveedor_id')->nullable()->after('user_id');
        
        // Opcional: Index para búsquedas rápidas
        $table->index('proveedor_id');
    });
}

public function down()
{
    Schema::table('pedidos', function (Blueprint $table) {
        $table->dropColumn('proveedor_id');
    });
}
};
