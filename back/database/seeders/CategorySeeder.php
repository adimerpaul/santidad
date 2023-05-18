<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert([
                ['name'=>'Ofertas'],
                ['name'=>'Supermercado'],
                ['name'=>'Salud y Medicamentos'],
                ['name'=>'Alimentos y Bebidas'],
                ['name'=>'Belleza'],
                ['name'=>'Mamá y Bebé'],
                ['name'=>'Cuidado Personal'],
                ['name'=>'Adulto Mayor'],
                ['name'=>'Bienestar y Deportes'],
                ['name'=>'Hogar'],
                ['name'=>'Mascotas'],
                ['name'=>'Tienda'],
        ]);
    }
}
