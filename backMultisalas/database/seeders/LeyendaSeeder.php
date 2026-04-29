<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeyendaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('leyendas')->insert([
            ["codigoActividad"=>'522000', "descripcionLeyenda"=>'Ley N° 453: Tienes derecho a un trato equitativo sin discriminación en la oferta de servicios.'],
            ["codigoActividad"=>'590000', "descripcionLeyenda"=>'Ley N° 453: Tienes derecho a un trato equitativo sin discriminación en la oferta de servicios.'],
            ["codigoActividad"=>'472000', "descripcionLeyenda"=>'Ley N° 453: Está prohibido importar, distribuir o comercializar productos expirados o prontos a expirar.'],
            ["codigoActividad"=>'522000', "descripcionLeyenda"=>'Ley N° 453: En caso de incumplimiento a lo ofertado o convenido, el proveedor debe reparar o sustituir el servicio.'],
            ["codigoActividad"=>'590000', "descripcionLeyenda"=>'Ley N° 453: En caso de incumplimiento a lo ofertado o convenido, el proveedor debe reparar o sustituir el servicio.'],
            ["codigoActividad"=>'681011', "descripcionLeyenda"=>'Ley N° 453: En caso de incumplimiento a lo ofertado o convenido, el proveedor debe reparar o sustituir el servicio.'],
            ["codigoActividad"=>'522000', "descripcionLeyenda"=>'Ley N° 453: El proveedor debe exhibir certificaciones de habilitación o documentos que acrediten las capacidades u ofertas de servicios especializados.'],
            ["codigoActividad"=>'590000', "descripcionLeyenda"=>'Ley N° 453: El proveedor debe exhibir certificaciones de habilitación o documentos que acrediten las capacidades u ofertas de servicios especializados.'],
            ["codigoActividad"=>'681011', "descripcionLeyenda"=>'Ley N° 453: El proveedor debe exhibir certificaciones de habilitación o documentos que acrediten las capacidades u ofertas de servicios especializados.'],
            ["codigoActividad"=>'472000', "descripcionLeyenda"=>'Ley N° 453: Los alimentos declarados de primera necesidad deben ser suministrados de manera adecuada, oportuna, continua y a precio justo.'],
            ["codigoActividad"=>'522000', "descripcionLeyenda"=>'Ley N° 453: Tienes derecho a recibir información sobre las características y contenidos de los servicios que utilices.'],
            ["codigoActividad"=>'590000', "descripcionLeyenda"=>'Ley N° 453: Tienes derecho a recibir información sobre las características y contenidos de los servicios que utilices.'],
            ["codigoActividad"=>'681011', "descripcionLeyenda"=>'Ley N° 453: Tienes derecho a recibir información sobre las características y contenidos de los servicios que utilices.'],
            ["codigoActividad"=>'472000', "descripcionLeyenda"=>'Ley N° 453: Tienes derecho a recibir información sobre las características y contenidos de los productos que consumes.'],
            ["codigoActividad"=>'472000', "descripcionLeyenda"=>'Ley N° 453: Tienes derecho a un trato equitativo sin discriminación en la oferta de productos.'],
            ["codigoActividad"=>'472000', "descripcionLeyenda"=>'Ley N° 453: Está prohibido importar, distribuir o comercializar productos prohibidos o retirados en el país de origen por atentar a la integridad física y a la salud.'],
            ["codigoActividad"=>'522000', "descripcionLeyenda"=>'Ley N° 453: El proveedor de servicios debe habilitar medios e instrumentos para efectuar consultas y reclamaciones.'],
            ["codigoActividad"=>'590000', "descripcionLeyenda"=>'Ley N° 453: El proveedor de servicios debe habilitar medios e instrumentos para efectuar consultas y reclamaciones.'],
            ["codigoActividad"=>'681011', "descripcionLeyenda"=>'Ley N° 453: El proveedor de servicios debe habilitar medios e instrumentos para efectuar consultas y reclamaciones.'],
            ["codigoActividad"=>'522000', "descripcionLeyenda"=>'Ley N° 453: El proveedor deberá suministrar el servicio en las modalidades y términos ofertados o convenidos.'],
            ["codigoActividad"=>'590000', "descripcionLeyenda"=>'Ley N° 453: El proveedor deberá suministrar el servicio en las modalidades y términos ofertados o convenidos.'],
            ["codigoActividad"=>'681011', "descripcionLeyenda"=>'Ley N° 453: El proveedor deberá suministrar el servicio en las modalidades y términos ofertados o convenidos.'],
            ["codigoActividad"=>'472000', "descripcionLeyenda"=>'Ley N° 453: El proveedor deberá entregar el producto en las modalidades y términos ofertados o convenidos.'],
            ["codigoActividad"=>'472000', "descripcionLeyenda"=>'Ley N° 453: En caso de incumplimiento a lo ofertado o convenido, el proveedor debe reparar o sustituir el producto..'],
            ["codigoActividad"=>'522000', "descripcionLeyenda"=>'Ley N° 453: La interrupción del servicio debe comunicarse con anterioridad a las Autoridades que correspondan y a los usuarios afectados.'],
            ["codigoActividad"=>'590000', "descripcionLeyenda"=>'Ley N° 453: La interrupción del servicio debe comunicarse con anterioridad a las Autoridades que correspondan y a los usuarios afectados.'],
            ["codigoActividad"=>'681011', "descripcionLeyenda"=>'Ley N° 453: La interrupción del servicio debe comunicarse con anterioridad a las Autoridades que correspondan y a los usuarios afectados.'],
            ["codigoActividad"=>'472000', "descripcionLeyenda"=>'Ley N° 453: Los servicios deben suministrarse en condiciones de inocuidad, calidad y seguridad.'],
            ["codigoActividad"=>'522000', "descripcionLeyenda"=>'Ley N° 453: Los servicios deben suministrarse en condiciones de inocuidad, calidad y seguridad.'],
            ["codigoActividad"=>'590000', "descripcionLeyenda"=>'Ley N° 453: Los servicios deben suministrarse en condiciones de inocuidad, calidad y seguridad.'],
            ["codigoActividad"=>'681011', "descripcionLeyenda"=>'Ley N° 453: Los servicios deben suministrarse en condiciones de inocuidad, calidad y seguridad.'],
        ]);
    }
}
