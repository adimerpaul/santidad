<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Administrador',
                'email' => 'admin@test.com',
                'password' => bcrypt('admin123Admin'),
                'agencia_id' => 1,
            ],
            [
                'name' => 'Usuario 1',
                'email' => 'usuario1@gmail.com',
                'password' => bcrypt('usuario123Usuario'),
                'agencia_id' => 1,
            ],
            [
                'name' => 'Usuario 2',
                'email' => 'usuario2@gmail.com',
                'password' => bcrypt('usuario123Usuario'),
                'agencia_id' => 2,
            ],
            [
                'name' => 'Usuario 3',
                'email' => 'usuario3@gmail.com',
                'password' => bcrypt('usuario123Usuario'),
                'agencia_id' => 3,
            ],
            [
                'name' => 'Usuario 4',
                'email' => 'usuario4@gmail.com',
                'password' => bcrypt('usuario123Usuario'),
                'agencia_id' => 4,
            ],
        ]);
    }
}
