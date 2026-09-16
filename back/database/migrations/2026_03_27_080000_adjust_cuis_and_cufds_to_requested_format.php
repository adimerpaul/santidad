<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignIfExists('cuis', 'cuis_agencia_id_foreign');
        $this->dropForeignIfExists('cuis', 'cuis_created_by_foreign');
        $this->dropForeignIfExists('cufds', 'cufds_agencia_id_foreign');
        $this->dropForeignIfExists('cufds', 'cufds_cuis_id_foreign');
        $this->dropForeignIfExists('cufds', 'cufds_created_by_foreign');

        DB::statement('ALTER TABLE cuis CHANGE fecha_vigencia fechaVigencia DATETIME NULL');
        DB::statement('ALTER TABLE cuis CHANGE codigo_punto_venta codigoPuntoVenta INT NULL DEFAULT 0');
        DB::statement('ALTER TABLE cuis CHANGE codigo_sucursal codigoSucursal INT NULL DEFAULT 0');

        DB::statement('ALTER TABLE cufds CHANGE codigo_control codigoControl VARCHAR(100) NULL');
        DB::statement('ALTER TABLE cufds CHANGE fecha_vigencia fechaVigencia DATETIME NULL');
        DB::statement('ALTER TABLE cufds CHANGE codigo_punto_venta codigoPuntoVenta INT NULL DEFAULT 0');
        DB::statement('ALTER TABLE cufds CHANGE codigo_sucursal codigoSucursal INT NULL DEFAULT 0');

        Schema::table('cuis', function (Blueprint $table) {
            $table->dateTime('fechaCreacion')->nullable()->after('fechaVigencia');
            $table->softDeletes();
            $table->dropColumn([
                'agencia_id',
                'nit',
                'codigo_sistema',
                'codigo_modalidad',
                'codigo_ambiente',
                'transaccion',
                'activo',
                'respuesta',
                'created_by',
            ]);
        });

        Schema::table('cufds', function (Blueprint $table) {
            $table->string('direccion')->nullable()->after('codigoControl');
            $table->dateTime('fechaCreacion')->nullable()->after('fechaVigencia');
            $table->softDeletes();
            $table->dropColumn([
                'agencia_id',
                'cuis_id',
                'nit',
                'codigo_sistema',
                'codigo_modalidad',
                'codigo_ambiente',
                'cuis_codigo',
                'transaccion',
                'activo',
                'respuesta',
                'created_by',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('cuis', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('fechaCreacion');
            $table->renameColumn('fechaVigencia', 'fecha_vigencia');
            $table->renameColumn('codigoPuntoVenta', 'codigo_punto_venta');
            $table->renameColumn('codigoSucursal', 'codigo_sucursal');
            $table->unsignedBigInteger('agencia_id')->nullable();
            $table->string('nit', 30)->nullable();
            $table->string('codigo_sistema', 80)->nullable();
            $table->unsignedTinyInteger('codigo_modalidad')->nullable();
            $table->unsignedTinyInteger('codigo_ambiente')->nullable();
            $table->boolean('transaccion')->default(false);
            $table->boolean('activo')->default(true);
            $table->json('respuesta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
        });

        Schema::table('cufds', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['direccion', 'fechaCreacion']);
            $table->renameColumn('codigoControl', 'codigo_control');
            $table->renameColumn('fechaVigencia', 'fecha_vigencia');
            $table->renameColumn('codigoPuntoVenta', 'codigo_punto_venta');
            $table->renameColumn('codigoSucursal', 'codigo_sucursal');
            $table->unsignedBigInteger('agencia_id')->nullable();
            $table->unsignedBigInteger('cuis_id')->nullable();
            $table->string('nit', 30)->nullable();
            $table->string('codigo_sistema', 80)->nullable();
            $table->unsignedTinyInteger('codigo_modalidad')->nullable();
            $table->unsignedTinyInteger('codigo_ambiente')->nullable();
            $table->string('cuis_codigo', 50)->nullable();
            $table->boolean('transaccion')->default(false);
            $table->boolean('activo')->default(true);
            $table->json('respuesta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
        });
    }

    private function dropForeignIfExists(string $table, string $foreign): void
    {
        try {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$foreign}");
        } catch (\Throwable $e) {
        }
    }
};
