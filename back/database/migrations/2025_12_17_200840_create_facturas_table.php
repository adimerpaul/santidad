<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_factura')->nullable();
            $table->string('proveedor')->nullable();
            $table->string('vendedor')->nullable();
            $table->string('numero_transaccion')->nullable();
            $table->date('fecha_compra');
            $table->decimal('monto_total', 10, 2);
            $table->string('tipo_pago')->nullable();
            $table->string('metodo_pago')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('estado')->default('Pendiente');
            $table->decimal('pagado', 10, 2)->default(0);
//            $table->foreignId('agencia_id')->constrained('agencias');
//            $table->foreignId('proveedor_id')->nullable()->constrained('clients');
            $table->unsignedBigInteger('agencia_id')->nullable();
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->foreign('agencia_id')->references('id')->on('agencias');
            $table->foreign('proveedor_id')->references('id')->on('clients');
            $table->text('observaciones')->nullable();
            $table->json('detalle_compras')->nullable(); // Para relacionar con las compras
            $table->foreignId('user_id')->constrained('users'); // Usuario que crea la factura
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pagos_factura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->string('vendedor')->nullable(); // Quién recibió el pago
            $table->string('metodo_pago')->nullable();
            $table->string('referencia')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->constrained('users'); // Usuario que registra el pago
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_factura');
        Schema::dropIfExists('facturas');
    }
};
