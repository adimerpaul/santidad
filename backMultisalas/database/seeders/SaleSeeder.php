<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("sales")->insert([
            [
                "numeroFactura"=>"1",
                "cuf"=>"xxx",
                "cufd"=>"xxx",
                "codigoSucursal"=>"0",
                "codigoPuntoVenta"=>"0",
                "fechaEmision"=>"2022-01-01",
                "montoTotal"=>"10",
                "usuario"=>"asda",
                "codigoDocumentoSector"=>"1",
                "user_id"=>"1",
                "client_id"=>"1",
            ]
        ]);
    }
}
