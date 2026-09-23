<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('unids')->insert([
//            ['UNIDAD', 'PAQUETE', 'SOBRE', 'BOLSA', 'FRASCO', 'SOBRES', 'CAPSULAS', 'PASTILLA', 'TABLETAS', 'OTROS'],
            ['nombre' => 'UNIDAD'],
            ['nombre' => 'PAQUETE'],
            ['nombre' => 'SOBRE'],
            ['nombre' => 'BOLSA'],
            ['nombre' => 'FRASCO'],
            ['nombre' => 'SOBRES'],
            ['nombre' => 'CAPSULAS'],
            ['nombre' => 'PASTILLA'],
            ['nombre' => 'TABLETAS'],
            ['nombre' => 'OTROS'],
        ]);
    }
}
