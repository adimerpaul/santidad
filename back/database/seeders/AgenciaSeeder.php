<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgenciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('agencias')->insert([
            ['nombre' => 'Agencia 1'],
            ['nombre' => 'Agencia 2'],
            ['nombre' => 'Agencia 3'],
            ['nombre' => 'Agencia 4'],
        ]);
    }
}
