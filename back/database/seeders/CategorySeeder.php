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
                ['id'=>1,'name'=>'Ofertas'],
                ['id'=>2,'name'=>'Supermercado'],
                ['id'=>3,'name'=>'Salud y Medicamentos'],
                ['id'=>4,'name'=>'Alimentos y Bebidas'],
                ['id'=>5,'name'=>'Belleza'],
                ['id'=>6,'name'=>'Mamá y Bebé'],
                ['id'=>7,'name'=>'Cuidado Personal'],
                ['id'=>8,'name'=>'Adulto Mayor'],
                ['id'=>9,'name'=>'Bienestar y Deportes'],
                ['id'=>10,'name'=>'Hogar'],
                ['id'=>11,'name'=>'Mascotas'],
                ['id'=>12,'name'=>'Tienda'],
        ]);
    }
}
