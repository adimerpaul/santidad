<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("tickets")->insert([
           [
                'numboc'=>"xxx",
                'numero'=>"xx",
                'fecha'=>"2022-08-02",
                'numeroFuncion'=>"5",
                'nombreSala'=>"Sala 1",
                'serieTarifa'=>"Dw",
                'fechaFuncion'=>"2022-08-02",
                'horaFuncion'=>"2022-08-02 10:00:00",
                'fila'=>"1",
                'columna'=>"16",
                'letra'=>"A",
                'costo'=>"20",
                'titulo'=>"thor",
                'devuelto'=>"0",
                'tarjeta'=>"",
                'credito'=>"",
                "client_id"=>"1",
                "programa_id"=>"1",
                "sale_id"=>"1",
                "price_id"=>"1",
                "sala_id"=>"1",
           ]
        ]);
    }
}
