<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            [
                'nombre'=>"Prudential Total Talla M Unisex Para Adulto X 20 Unidades",
                'cantidad'=>"4",
                'precioAntes'=>"138.21",
                'precio'=>"110.60",
                'unidad'=>"PAQUETES",
                'imagen'=>"7861078302235_543x543.webp",
                'descripcion'=>"Panales para incontingencias de 20 unidade talla M",
                "category_id"=>8,
            ],
            [
                'nombre'=>"Prudential Total G Unisex Para Adulto X 20 Unidades",
                'cantidad'=>"1",
                'precioAntes'=>"144.61",
                'precio'=>"115.70",
                'unidad'=>"PAQUETES",
                'imagen'=>"7861078302242_543x543.webp",
                'descripcion'=>"Panales para la incontingencias de 20 unidades talla G",
                "category_id"=>8,
            ],
            [
                'nombre'=>"Prudential Total Talla M Unisex Para Adulto X 20 Unidades",
                'cantidad'=>"4",
                'precioAntes'=>"138.21",
                'precio'=>"110.60",
                'unidad'=>"PAQUETES",
                'imagen'=>"7861078303102_80cfb859-d4a3-4d63-a0c7-50745f7ee04c_543x543.webp",
                'descripcion'=>"Panales para incontingencias de 20 unidade talla M",
                "category_id"=>8,
            ],
            [
                'nombre'=>"Prudential Confort Talla M Unisex Para Adulto X 20 Unidades",
                'cantidad'=>"4",
                'precioAntes'=>"9.85",
                'precio'=>"75.10",
                'unidad'=>"PAQUETES",
                'imagen'=>"7861078303102_80cfb859-d4a3-4d63-a0c7-50745f7ee04c_543x543.webp",
                'descripcion'=>"Protector para adultos super absorbentes.",
                "category_id"=>8,
            ],
            [
                'nombre'=>"Prudential Total Talla M Unisex Para Adulto X 20 Unidades",
                'cantidad'=>"4",
                'precioAntes'=>"138.21",
                'precio'=>"110.60",
                'unidad'=>"PAQUETES",
                'imagen'=>"7861078303102_80cfb859-d4a3-4d63-a0c7-50745f7ee04c_543x543.webp",
                'descripcion'=>"Panales para incontingencias de 20 unidade talla M",
                "category_id"=>8,
            ],
            [
                'nombre'=>"Prudential Total Talla M Unisex Para Adulto X 20 Unidades",
                'cantidad'=>"4",
                'precioAntes'=>"138.21",
                'precio'=>"110.60",
                'unidad'=>"PAQUETES",
                'imagen'=>"7861078303102_80cfb859-d4a3-4d63-a0c7-50745f7ee04c_543x543.webp",
                'descripcion'=>"Panales para incontingencias de 20 unidade talla M",
                "category_id"=>8,
            ]
        ]);
    }
}
