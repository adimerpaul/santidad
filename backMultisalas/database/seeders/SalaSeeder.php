<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("salas")->insert([
            [
                "nro"=>"1",
                "nombre" => "Sala 1",
                "filas" => "10",
                "columnas" => "22",
                "capacidad" => "169",
            ],
            [
                "nro"=>"2",
                "nombre" => "Sala 2",
                "filas" => "6",
                "columnas" => "17",
                "capacidad" => "92",
            ],
            [
                "nro"=>"3",
                "nombre" => "Sala 3",
                "filas" => "7",
                "columnas" => "19",
                "capacidad" => "109",
            ],
        ]);
    }
}
