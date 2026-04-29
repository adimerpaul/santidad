<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('movies')->insert([
            [
                'nombre' => 'minions 2: nace un villano',
                'duracion' => 100,
                'paisOrigen' => 'Pais 1',
                'genero' => 'Genero 1',
                'formato' => '2D',
                'sipnosis' => 'Sipnosis 1',
                'urlTrailer' => 'Url 1',
                'imagen' => 'mini.jpg',
                'clasificacion' => 'Clasificacion 1',
                'fechaEstreno' => now(),
                'distributor_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'minions 2: nace un villano',
                'duracion' => 100,
                'paisOrigen' => 'Pais 1',
                'genero' => 'Genero 1',
                'formato' => '3D',
                'sipnosis' => 'Sipnosis 1',
                'urlTrailer' => 'Url 1',
                'imagen' => 'mini.jpg',
                'clasificacion' => 'Clasificacion 1',
                'fechaEstreno' => now(),
                'distributor_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'thor love and thunder',
                'duracion' => 100,
                'paisOrigen' => 'Pais 2',
                'genero' => 'Genero 2',
                'formato' => '2D',

                'sipnosis' => 'Sipnosis 2',
                'urlTrailer' => 'Url 2',
                'imagen' => 'thor.jpg',
                'clasificacion' => 'Clasificacion 2',
                'fechaEstreno' => now(),
                'distributor_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'thor love and thunder',
                'duracion' => 100,
                'paisOrigen' => 'Pais 2',
                'genero' => 'Genero 2',
                'sipnosis' => 'Sipnosis 2',
                'formato' => '3D',

                'urlTrailer' => 'Url 2',
                'imagen' => 'thor.jpg',
                'clasificacion' => 'Clasificacion 2',
                'fechaEstreno' => now(),
                'distributor_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}
