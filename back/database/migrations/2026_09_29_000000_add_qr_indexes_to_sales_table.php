<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para la pantalla de Pagos QR.
 *
 * - idx_sales_agencia_fecha: ventas candidatas filtradas por agencia y día.
 * - idx_sales_qr_id: cruce de pagos del banco con ventas (whereIn qrId) y vincular/desvincular.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!$this->indiceExiste('sales', 'idx_sales_agencia_fecha')) {
            DB::statement('CREATE INDEX idx_sales_agencia_fecha ON sales (agencia_id, fechaEmision)');
        }

        if (!$this->indiceExiste('sales', 'idx_sales_qr_id')) {
            DB::statement('CREATE INDEX idx_sales_qr_id ON sales (qrId)');
        }
    }

    public function down(): void
    {
        if ($this->indiceExiste('sales', 'idx_sales_agencia_fecha')) {
            DB::statement('DROP INDEX idx_sales_agencia_fecha ON sales');
        }

        if ($this->indiceExiste('sales', 'idx_sales_qr_id')) {
            DB::statement('DROP INDEX idx_sales_qr_id ON sales');
        }
    }

    private function indiceExiste(string $tabla, string $indice): bool
    {
        if (!Schema::hasTable($tabla)) {
            return false;
        }

        return count(DB::select("SHOW INDEX FROM `{$tabla}` WHERE Key_name = ?", [$indice])) > 0;
    }
};
