<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('productos')->insert([
            [
                'nombre'=>"COMBO 1",
                'precio'=>"20",
                'imagen'=>"combo mediano.png",
                'color'=>"#770050",
                "rubro_id"=>"1",
            ],
            [
                'nombre'=>"COMBO 2",
                'precio'=>"30",
                'imagen'=>"combo mediano.png",
                'color'=>"#770050",
                "rubro_id"=>"1",
            ],
            [
                'nombre'=>"COMBO 3",
                'precio'=>"35",
                'imagen'=>"COMBOBALDEMOANA.png",
                'color'=>"#770050",
                "rubro_id"=>"1",
            ],
            [
                'nombre'=>"COMBO NACHO",
                'precio'=>"25",
                'imagen'=>"combo nacho.png",
                'color'=>"#770050",
                "rubro_id"=>"1",
            ],
            [
                'nombre'=>"SODA",
                'precio'=>"9",
                'imagen'=>"botellita.png",
                'color'=>"#770050",
                "rubro_id"=>"2",
            ],
            [
                'nombre'=>"AQUARIUS",
                'precio'=>"9",
                'imagen'=>"aquiarius.png",
                'color'=>"#770050",
                "rubro_id"=>"2",
            ],            [
                'nombre'=>"AGUA 500ML",
                'precio'=>"8",
                'imagen'=>"aguamineral.png",
                'color'=>"#770050",
                "rubro_id"=>"2",
            ],            [
                'nombre'=>"CAFE",
                'precio'=>"3",
                'imagen'=>"vaso-cafe.jpg",
                'color'=>"#770050",
                "rubro_id"=>"2",
            ],
            [
                'nombre'=>"PEPSI 7UP GUARANA H2O",
                'precio'=>"9",
                'imagen'=>"pepsi.jpg",
                'color'=>"#770050",
                "rubro_id"=>"2",
            ],

            [
                'nombre'=>"GRANIZADO",
                'precio'=>"10",
                'imagen'=>"ice.jpg",
                'color'=>"#770050",
                "rubro_id"=>"3",
            ],

            [
                'nombre'=>"CAJA NACHOS",
                'precio'=>"17",
                'imagen'=>"nachos.png",
                'color'=>"#770050",
                "rubro_id"=>"4",
            ],
            [
                'nombre'=>"PIPOCA PEQUEÑA",
                'precio'=>"12",
                'imagen'=>"pipoca.png",
                'color'=>"#770050",
                "rubro_id"=>"4",
            ],
            [
                'nombre'=>"PIPOCA MEDIANA",
                'precio'=>"16",
                'imagen'=>"bag popcorn.png",
                'color'=>"#770050",
                "rubro_id"=>"4",
            ],
            [
                'nombre'=>"PIPOCA GRANDE",
                'precio'=>"20",
                'imagen'=>"BALDE.png",
                'color'=>"#770050",
                "rubro_id"=>"4",
            ],
            [
                'nombre'=>"MINI COMBO",
                'precio'=>"10",
                'imagen'=>"combo mediano.png",
                'color'=>"#770050",
                "rubro_id"=>"4",
            ],
            [
                'nombre'=>"PORCION QUESO",
                'precio'=>"5",
                'imagen'=>"queso.png",
                'color'=>"#770050",
                "rubro_id"=>"4",
            ],

            [
                'nombre'=>"HOT DOG",
                'precio'=>"10",
                'imagen'=>"hotdog2.png",
                'color'=>"#770050",
                "rubro_id"=>"5",
            ],
            [
                'nombre'=>"HOT DOG (PROMO)",
                'precio'=>"9",
                'imagen'=>"hotdog2.png",
                'color'=>"#770050",
                "rubro_id"=>"5",
            ],

            [
                'nombre'=>"MYM",
                'precio'=>"12",
                'imagen'=>"mym.png",
                'color'=>"#770050",
                "rubro_id"=>"6",
            ],
            [
                'nombre'=>"SNICKERS",
                'precio'=>"12",
                'imagen'=>"snicker.png",
                'color'=>"#770050",
                "rubro_id"=>"6",
            ],
            [
                'nombre'=>"SKTTLES",
                'precio'=>"12",
                'imagen'=>"skittles.png",
                'color'=>"#770050",
                "rubro_id"=>"6",
            ],
            [
                'nombre'=>"MYM MINI",
                'precio'=>"5",
                'imagen'=>"mym.png",
                'color'=>"#770050",
                "rubro_id"=>"6",
            ],
            [
                'nombre'=>"GOMITAS",
                'precio'=>"5",
                'imagen'=>"gomitas.png",
                'color'=>"#770050",
                "rubro_id"=>"6",
            ],

            [
                'nombre'=>"RED BULL",
                'precio'=>"5",
                'imagen'=>"red2.jpg",
                'color'=>"#770050",
                "rubro_id"=>"7",
            ],
            [
                'nombre'=>"GATORADE",
                'precio'=>"12",
                'imagen'=>"GATORADE.png",
                'color'=>"#770050",
                "rubro_id"=>"7",
            ],

        ]);
    }
}
