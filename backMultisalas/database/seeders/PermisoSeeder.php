<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('permisos')->insert([
            ['id'=>1,'nombre'=>'Usuarios'],
            ['id'=>2,'nombre'=>'Cuis'],
            ['id'=>3,'nombre'=>'Sincronizacion'],
            ['id'=>4,'nombre'=>'Cufd'],
            ['id'=>5,'nombre'=>'Evento Significativo'],
            ['id'=>6,'nombre'=>'Peliculas'],
            ['id'=>7,'nombre'=>'Distribuidor'],
            ['id'=>8,'nombre'=>'Salas'],
            ['id'=>9,'nombre'=>'Tarifas'],
            ['id'=>10,'nombre'=>'Rubro'],
            ['id'=>11,'nombre'=>'Producto'],
            ['id'=>12,'nombre'=>'Programacion'],
            ['id'=>13,'nombre'=>'Venta Boleteria'],
            ['id'=>14,'nombre'=>'Listado Boleteria'],
            ['id'=>15,'nombre'=>'Venta Candy'],
            ['id'=>16,'nombre'=>'Listado Candy'],
            ['id'=>17,'nombre'=>'Caja Boleteria'],
            ['id'=>18,'nombre'=>'Caja Candy'],
            ['id'=>19,'nombre'=>'Alquiler Ambiente'],
            ['id'=>20,'nombre'=>'Clientes'],
            ['id'=>21,'nombre'=>'Cortesia'],
            ['id'=>22,'nombre'=>'Reporte Funcion'],
            ['id'=>23,'nombre'=>'Solicitud Anulacion'],
            ['id'=>24,'nombre'=>'Autorizar Anulacion'],
            ['id'=>25,'nombre'=>'Aprobar Anulacion'],
            ['id'=>26,'nombre'=>'Ver Todo Venta'],
            ['id'=>27,'nombre'=>'Imp Boletos'],
            ['id'=>28,'nombre'=>'Imp Factura'],
            ['id'=>29,'nombre'=>'Tipo Pago'],
            ['id'=>30,'nombre'=>'Pagos'],

        ]);

    }
}
