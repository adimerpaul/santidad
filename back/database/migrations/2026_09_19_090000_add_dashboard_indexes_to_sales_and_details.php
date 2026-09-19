<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para el dashboard de ventas.
 *
 * Los resúmenes agrupan cientos de miles de ventas y más de un millón de
 * detalles. Sin estos índices MySQL elegía `sales_deleted_at_index` (que no
 * filtra nada: casi todas las ventas tienen deleted_at NULL) y un resumen
 * anual tardaba ~90 s. Con los índices de cobertura baja a unos pocos segundos.
 *
 * `sales_deleted_at_index` se elimina porque el nuevo índice ya empieza por
 * `deleted_at`: mientras existía, el optimizador seguía prefiriéndolo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!$this->indiceExiste('sales', 'idx_sales_dashboard')) {
            DB::statement('CREATE INDEX idx_sales_dashboard ON sales (deleted_at, fechaEmision, tipoVenta, estado, montoTotal, descuento)');
        }

        if (!$this->indiceExiste('details', 'idx_details_dashboard')) {
            DB::statement('CREATE INDEX idx_details_dashboard ON details (sale_id, product_id, cantidad, subTotal)');
        }

        if ($this->indiceExiste('sales', 'sales_deleted_at_index')) {
            DB::statement('DROP INDEX sales_deleted_at_index ON sales');
        }
    }

    public function down(): void
    {
        if (!$this->indiceExiste('sales', 'sales_deleted_at_index')) {
            DB::statement('CREATE INDEX sales_deleted_at_index ON sales (deleted_at)');
        }

        if ($this->indiceExiste('sales', 'idx_sales_dashboard')) {
            DB::statement('DROP INDEX idx_sales_dashboard ON sales');
        }

        if ($this->indiceExiste('details', 'idx_details_dashboard')) {
            DB::statement('DROP INDEX idx_details_dashboard ON details');
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
