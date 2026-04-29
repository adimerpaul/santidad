<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CastSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        //1 rey leon
        //2 toy story 4
        //3 aladin
        DB::table('casts')->insert([
            ['nombre' => 'Donald Glover', 'imagen' => 'donald.jpg', 'proximo_id' => 1],
            ['nombre' => 'Seth Rogen', 'imagen' => 'seth.jpg', 'proximo_id' => 1],
            ['nombre' => 'Chiwetel Ejiofor', 'imagen' => 'chiwetel.jpg', 'proximo_id' => 1],
            ['nombre' => 'Tom Hanks', 'imagen' => 'tom.jpg', 'proximo_id' => 1],
            ['nombre' => 'Tim Allen', 'imagen' => 'tim.jpg', 'proximo_id' => 1],

            ['nombre' => 'Donald Glover', 'imagen' => 'donald.jpg', 'proximo_id' => 2],
            ['nombre' => 'Seth Rogen', 'imagen' => 'seth.jpg', 'proximo_id' => 2],
            ['nombre' => 'Chiwetel Ejiofor', 'imagen' => 'chiwetel.jpg', 'proximo_id' => 2],
            ['nombre' => 'Tom Hanks', 'imagen' => 'tom.jpg', 'proximo_id' => 2],
            ['nombre' => 'Tim Allen', 'imagen' => 'tim.jpg', 'proximo_id' => 2],

            ['nombre' => 'Donald Glover', 'imagen' => 'donald.jpg', 'proximo_id' => 3],
            ['nombre' => 'Seth Rogen', 'imagen' => 'seth.jpg', 'proximo_id' => 3],
            ['nombre' => 'Chiwetel Ejiofor', 'imagen' => 'chiwetel.jpg', 'proximo_id' => 3],
            ['nombre' => 'Tom Hanks', 'imagen' => 'tom.jpg', 'proximo_id' => 3],
            ['nombre' => 'Tim Allen', 'imagen' => 'tim.jpg', 'proximo_id' => 3],
        ]);
    }
}
