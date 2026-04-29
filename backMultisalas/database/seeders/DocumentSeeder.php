<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("documents")->insert([
            ["codigoClasificador"=>'1',"descripcion"=> 'CI - CEDULA DE IDENTIDAD'],
["codigoClasificador"=>'2',"descripcion"=> 'CEX - CEDULA DE IDENTIDAD DE EXTRANJERO'],
["codigoClasificador"=>'3',"descripcion"=> 'PAS - PASAPORTE'],
["codigoClasificador"=>'4',"descripcion"=> 'OD - OTRO DOCUMENTO DE IDENTIDAD'],
["codigoClasificador"=>'5',"descripcion"=> 'NIT - NÚMERO DE IDENTIFICACIÓN TRIBUTARIA'],
        ]);
    }
}
