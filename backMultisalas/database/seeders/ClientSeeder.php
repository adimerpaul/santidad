<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("clients")->insert([
            [
                "nombreRazonSocial"=>"Chambi",
                "numeroDocumento"=>"7336199017",
                "codigoTipoDocumentoIdentidad"=>"5",
                "complemento"=>"",
            ],
            [
                "nombreRazonSocial"=>"Adimer Paul",
                "numeroDocumento"=>"7336199",
                "codigoTipoDocumentoIdentidad"=>"1",
                "complemento"=>"",
            ],            [
                "nombreRazonSocial"=>"Ajata",
                "numeroDocumento"=>"7336199",
                "codigoTipoDocumentoIdentidad"=>"1",
                "complemento"=>"A1",
            ],
        ]);
    }
}
