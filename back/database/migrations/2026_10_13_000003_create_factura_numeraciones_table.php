<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Contador único de facturas. Se bloquea la fila (SELECT ... FOR UPDATE)
        // al reservar un número, así dos ventas simultáneas nunca obtienen el
        // mismo numeroFactura.
        Schema::create('factura_numeraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 50)->unique();
            $table->unsignedBigInteger('ultimo_numero')->default(0);
            $table->timestamps();
        });

        // Arranca desde el último número ya emitido.
        DB::table('factura_numeraciones')->insert([
            'clave'         => 'factura',
            'ultimo_numero' => (int) DB::table('sales')->max('numeroFactura'),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_numeraciones');
    }
};
