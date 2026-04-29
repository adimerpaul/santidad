<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProximoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        DB::table('proximos')->insert([
            [
                'nombre' => 'El Rey León',
                'descripcion' => 'El Rey León es una película de aventuras y drama musical estadounidense de 2019, dirigida y producida por Jon Favreau y escrita por Jeff Nathanson, siendo el remake de la película animada de Disney de 1994 del mismo nombre. La película incluye las voces de Donald Glover, Seth Rogen, Chiwetel Ejiofor, Alfre Woodard, Billy Eichner, John Kani, John Oliver y Beyoncé Knowles-Carter, así como James Earl Jones, quien retoma su papel de Mufasa de la película original.',
                'fecha' => '2024-04-20',
                'start' => 0,
                'director' => 'Jon Favreau',
                'imagen' => 'rey_leon.jpg',
                'trailer' => 'https://www.youtube.com/watch?v=7TavVZMewpY',
                'estado' => 'ACTIVO',
                'minutos' => 118,
                'pais' => 'Estados Unidos',
                'clasificacion' => 'A',
                'generos' => 'Aventura, Drama, Musical'
            ],
            [
                'nombre' => 'Toy Story 4',
                'descripcion' => 'Toy Story 4 es una película de animación por computadora de comedia y aventuras estadounidense de 2019, producida por Pixar Animation Studios y lanzada por Walt Disney Pictures. Es la cuarta entrega de la serie de películas Toy Story y la secuela de Toy Story 3 de 2010. La película fue dirigida por Josh Cooley (en su debut como director) a partir de un guion de Andrew Stanton y Stephany Folsom; Stanton y Pete Docter, los directores de las dos primeras películas de la serie, actuaron como productores ejecutivos.',
                'fecha' => '2024-04-20',
                'start' => 0,
                'director' => 'Josh Cooley',
                'imagen' => 'toy_story.jpg',
                'trailer' => 'https://www.youtube.com/watch?v=wmiIUN-7qhE',
                'estado' => 'ACTIVO',
                'minutos' => 100,
                'pais' => 'Estados Unidos',
                'clasificacion' => 'A',
                'generos' => 'Aventura, Comedia, Animación'
            ],
            [
                'nombre' => 'Aladdin',
                'descripcion' => 'Aladdín es una película de aventuras y fantasía musical estadounidense de 2019 dirigida por Guy Ritchie, basada en el cuento árabe Aladino y la lámpara maravillosa de Las mil y una noches y en la película animada homónima de 1992 de Walt Disney Pictures. La película es protagonizada por Mena Massoud como el personaje titular, junto a Will Smith, Naomi Scott, Marwan Kenzari, Navid Negahban, Nasim Pedrad, Billy Magnussen y Numan Acar.',
                'fecha' => '2024-04-20',
                'start' => 0,
                'director' => 'Guy Ritchie',
                'imagen' => 'aladdin.jpg',
                'trailer' => 'https://www.youtube.com/watch?v=JcMtWwiyzpU',
                'estado' => 'ACTIVO',
                'minutos' => 128,
                'pais' => 'Estados Unidos',
                'clasificacion' => 'A',
                'generos' => 'Aventura, Fantasía, Musical'
            ],
        ]);
    }
}
