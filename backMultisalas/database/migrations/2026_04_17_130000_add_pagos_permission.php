<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!DB::table('permisos')->where('id', 30)->exists()) {
            DB::table('permisos')->insert([
                'id' => 30,
                'nombre' => 'Pagos',
                'permiso_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        DB::table('permisos')->where('id', 30)->delete();
    }
};
