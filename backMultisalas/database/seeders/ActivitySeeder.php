<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("activities")->insert([
            [ "codigoCaeb"=>'590000',"descripcion"=> 'ACTIVIDADES DE CINEMATOGRAFÍA Y OTRAS ACTIVIDADES DE ENTRETENIMIENTO',"tipoActividad"=> 'P'],
            [ "codigoCaeb"=>'472000',"descripcion"=> 'VENTA AL POR MENOR DE ALIMENTOS, BEBIDAS Y TABACO EN ALMACENES ESPECIALIZADOS',"tipoActividad"=> 'S'],
            [ "codigoCaeb"=>'681011',"descripcion"=> 'ALQUILER DE BIENES RAÍCES PROPIOS',"tipoActividad"=> 'S'],
            [ "codigoCaeb"=>'522000',"descripcion"=> 'OTRAS ACTIVIDADES DE TRANSPORTE COMPLEMENTARIA (TERMINAL DE BUSES, PLAYAS DE ESTACIONAMIENTO, ETC)',"tipoActividad"=> 'S'],
        ]);
    }
}
