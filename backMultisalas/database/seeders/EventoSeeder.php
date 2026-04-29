<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('eventos')->insert([
            [
                'nombre' => 'Dia del niño',
                'descripcion' => 'Dia del niño en el cine',
                'fecha' => '2024-04-20',
                'hora' => '22:53:47',
                'lugar' => 'Cine',
                'imagen' => 'nino.jpg',
                'estado' => 'ACTIVO'
            ],
            [
                'nombre' => 'Miercoles 2 por 1',
                'descripcion' => 'Dos personas por el precio de una Todos los miercoles',
                'fecha' => '2024-04-20',
                'hora' => '22:53:47',
                'lugar' => 'Cine',
                'imagen' => 'miercoles.jpg',
                'estado' => 'ACTIVO'
            ],
            [
                'nombre' => 'Martes duo',
                'descripcion' => 'Por la compra de una entrada, te lleva pipocas gratis',
                'fecha' => '2024-04-20',
                'hora' => '22:53:47',
                'lugar' => 'Cine',
                'imagen' => 'martes.jpg',
                'estado' => 'ACTIVO'
            ],
            [
                'nombre' => 'Cumpleaños',
                'descripcion' => 'Las personas que cumplan años en este mes tienen un descuento',
                'fecha' => '2024-04-20',
                'hora' => '22:53:47',
                'lugar' => 'Cine',
                'imagen' => 'estreno.jpg',
                'estado' => 'ACTIVO'
            ]
        ]);


    }
}
