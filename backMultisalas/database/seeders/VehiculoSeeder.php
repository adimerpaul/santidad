<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehiculoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("vehiculos")->insert([
            ["costo"=>'5.00',"descripcion"=>"MOTOCICLETA"],
            ["costo"=>'10.00',"descripcion"=>"AUTOMOVIL"],
        ]);
    }
}
