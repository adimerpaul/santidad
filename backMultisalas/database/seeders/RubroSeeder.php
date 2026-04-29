<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RubroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('rubros')->insert([
            [
                'id'=>1,
                'nombre'=>"COMBOS",
                'imagen'=>"combo.png",
                'color'=>"#770050",
            ],
            [
                'id'=>2,
                'nombre'=>"BEBIDAS",
                'imagen'=>"VASO COCA.png",
                'color'=>"#770050",
            ],
            [
                'id'=>3,
                'nombre'=>"GRANIZADO",
                'imagen'=>"ice.jpg",
                'color'=>"#770050",
            ],
            [
                'id'=>4,
                'nombre'=>"PIPOCAS",
                'imagen'=>"pipoca.png",
                'color'=>"#770050",
            ],
            [
                'id'=>5,
                'nombre'=>"HOT DOG",
                'imagen'=>"hotdog2.png",
                'color'=>"#770050",
            ],
            [
                'id'=>6,
                'nombre'=>"DULCES",
                'imagen'=>"rubro.jpg",
                'color'=>"#770050",
            ],
            [
                'id'=>7,
                'nombre'=>"ENERGIZANTE",
                'imagen'=>"red2.jpg",
                'color'=>"#770050",
            ],
        ]);
    }
}
