<?php

namespace Database\Seeders;

use App\Models\Agencia;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgenciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
//        DB::table('agencias')->insert([
//            ['nombre' => 'Agencia 1'],
//            ['nombre' => 'Agencia 2'],
//            ['nombre' => 'Agencia 3'],
//            ['nombre' => 'Agencia 4'],
//        ]);


//        CASA MATRIZ VELAZCO
//DIRECION:	Velazco Galvarro Nº 2246 entre Santa Barbara y Arce
//TELEFONO	52 10798
//ATENCION:	Lunes a Domingo / Feriados
//HORARIOS:	8:00 am a 11:00 pm
//FACEBOOK:	https://www.facebook.com/farmacia.santidaddivina
//WHATSAPP:	https://walink.co/224178
//GPS:	https://maps.app.goo.gl/nRQk8aTPoAC2eLVM9
//
//SUCURSAL 1 - FARMACIA SANTIDAD DIVINA I
//DIRECION:	Rodriguez Nº 15 entre Brasil y Tejerina
//TELEFONO	52 82487
//ATENCION:	Lunes a Domingo / Feriados
//HORARIOS:	8:00 am a 10:00 pm
//FACEBOOK:
//WHATSAPP:	https://walink.co/d2a179
//GPS:	https://maps.app.goo.gl/dxYMyXo9KLSuAmtt5
//
//SUCURSAL 2 - FARMACIA SANTIDAD DIVINA II
//DIRECION:	Rodriguez Nº 526 entre 6 de Octubre y La Paz
//TELEFONO	52 56598
//ATENCION:	Lunes a Domingo / Feriados
//HORARIOS:	8:00 am a 10:00 pm
//FACEBOOK:	https://www.facebook.com/farmaciasantidad.divinaii
//WHATSAPP:	https://walink.co/5f6010
//GPS:	https://maps.app.goo.gl/CQ3YkRd7iB5P8U1m8
//
//SUCURSAL 3 - FARMACIA SANTIDAD DIVINA III
//DIRECION:	Av. España entre Dehene y Acha
//TELEFONO	52 60779
//ATENCION:	Lunes a Domingo / Feriados
//HORARIOS:	8:00 am a 11:00 pm
//FACEBOOK:
//WHATSAPP:	https://walink.co/74d7f6
//GPS:	https://maps.app.goo.gl/iNnJ8kCFZTEvEoxo7
//
//SUCURSAL 4 - FARMACIA SANTIDAD DIVINA IV
//DIRECION:	Av. Circunvalación Nº 15 entre Franz Tamayo y Benjamin Guzman
//TELEFONO	52 36001
//ATENCION:	Lunes a Domingo / Feriados
//HORARIOS:	08:00 am a 10:00 pm
//FACEBOOK:
//WHATSAPP:	https://walink.co/14c7df
//GPS:	https://maps.app.goo.gl/pvaxoxQqzC2qStYe6
//
//SUCURSAL 5 - FARMACIA POTOSI
//DIRECION:	Adolfo Mier Nº 445 entre Tejerina y Tarapaca
//TELEFONO	52 86468
//ATENCION:	Lunes a Domingo / Feriados
//HORARIOS:	07:30 am a 10:00 pm
//FACEBOOK:	https://www.facebook.com/farmacia.potosi.144
//WHATSAPP:	https://walink.co/b80053
//GPS:	https://maps.app.goo.gl/QWZKKcSeFdnFfxcF8
//
//SUCURSAL 6 - FARMACIA MABEL
//DIRECION:	San Felipe Nº 500 esq. Pisagua
//TELEFONO	52 89789
//ATENCION:	Lunes a Domingo / Feriados
//HORARIOS:	8:00 am a 11:00 pm
//FACEBOOK:	https://www.facebook.com/farmacia.mabel
//WHATSAPP:	https://walink.co/613688
//GPS:	https://maps.app.goo.gl/1FakQToSJpCNtZTp6

//protected $fillable = ['nombre','direccion','telefono','atencion','horario','facebook','whatsapp','gps','latitud','longitud','status'];
        $agencia1=Agencia::where('id',1)->first();
//        echo $agencia1;
        if($agencia1){
            $agencia1->direccion='Velazco Galvarro Nº 2246 entre Santa Barbara y Arce';
            $agencia1->telefono='52 10798';
            $agencia1->atencion='Lunes a Domingo / Feriados';
            $agencia1->horario='8:00 am a 11:00 pm';
            $agencia1->facebook='https://www.facebook.com/farmacia.santidaddivina';
            $agencia1->whatsapp='https://walink.co/224178';
            $agencia1->gps='https://maps.app.goo.gl/nRQk8aTPoAC2eLVM9';
            $agencia1->latitud='-17.9771278';
            $agencia1->longitud='-67.1116408';
            $agencia1->status='ACTIVO';
            $agencia1->save();
//            echo $agencia1;
        }
        $agencia2=Agencia::find(2);
        if($agencia2){
            $agencia2->direccion='Rodriguez Nº 15 entre Brasil y Tejerina';
            $agencia2->telefono='52 82487';
            $agencia2->atencion='Lunes a Domingo / Feriados';
            $agencia2->horario='8:00 am a 10:00 pm';
            $agencia2->facebook='';
            $agencia2->whatsapp='https://walink.co/d2a179';
            $agencia2->gps='https://maps.app.goo.gl/dxYMyXo9KLSuAmtt5';
            $agencia2->latitud='-17.9629399';
            $agencia2->longitud='-67.1043014';
            $agencia2->status='ACTIVO';
            $agencia2->save();
        }
        $agencia3=Agencia::find(3);
        if($agencia3){
            $agencia3->direccion='Rodriguez Nº 526 entre 6 de Octubre y La Paz';
            $agencia3->telefono='52 56598';
            $agencia3->atencion='Lunes a Domingo / Feriados';
            $agencia3->horario='8:00 am a 10:00 pm';
            $agencia3->facebook='https://www.facebook.com/farmaciasantidad.divinaii';
            $agencia3->whatsapp='https://walink.co/5f6010';
            $agencia3->gps='https://maps.app.goo.gl/CQ3YkRd7iB5P8U1m8';
            $agencia3->latitud='17.9620611';
            $agencia3->longitud='-67.1114951';
            $agencia3->status='ACTIVO';
            $agencia3->save();
        }
        $agencia4=Agencia::find(4);
        if($agencia4){
            $agencia4->direccion='Av. España entre Dehene y Acha';
            $agencia4->telefono='52 60779';
            $agencia4->atencion='Lunes a Domingo / Feriados';
            $agencia4->horario='8:00 am a 11:00 pm';
            $agencia4->facebook='';
            $agencia4->whatsapp='https://walink.co/74d7f6';
            $agencia4->gps='https://maps.app.goo.gl/iNnJ8kCFZTEvEoxo7';
            $agencia4->latitud='-17.978669';
            $agencia4->longitud='-67.132325';
            $agencia4->status='ACTIVO';
            $agencia4->save();
        }
        $agencia5=Agencia::find(5);
        if($agencia5){
            $agencia5->direccion='Av. Circunvalación Nº 15 entre Franz Tamayo y Benjamin Guzman';
            $agencia5->telefono='52 36001';
            $agencia5->atencion='Lunes a Domingo / Feriados';
            $agencia5->horario='08:00 am a 10:00 pm';
            $agencia5->facebook='';
            $agencia5->whatsapp='https://walink.co/14c7df';
            $agencia5->gps='https://maps.app.goo.gl/pvaxoxQqzC2qStYe6';
            $agencia5->latitud='-17.92701';
            $agencia5->longitud='-67.122287';
            $agencia5->status='ACTIVO';
            $agencia5->save();
        }
        $agencia6=Agencia::find(6);
        if($agencia6){
            $agencia6->direccion='Adolfo Mier Nº 445 entre Tejerina y Tarapaca';
            $agencia6->telefono='52 86468';
            $agencia6->atencion='Lunes a Domingo / Feriados';
            $agencia6->horario='07:30 am a 10:00 pm';
            $agencia6->facebook='https://www.facebook.com/farmacia.potosi.144';
            $agencia6->whatsapp='https://walink.co/b80053';
            $agencia6->gps='https://maps.app.goo.gl/QWZKKcSeFdnFfxcF8';
            $agencia6->latitud='-17.9720109';
            $agencia6->longitud='-67.1055173';
            $agencia6->status='ACTIVO';
            $agencia6->save();
        }
        $agencia7=Agencia::find(7);
        if($agencia7){
            $agencia7->direccion='San Felipe Nº 500 esq. Pisagua';
            $agencia7->telefono='52 89789';
            $agencia7->atencion='Lunes a Domingo / Feriados';
            $agencia7->horario='8:00 am a 11:00 pm';
            $agencia7->facebook='https://www.facebook.com/farmacia.mabel';
            $agencia7->whatsapp='https://walink.co/613688';
            $agencia7->gps='https://maps.app.goo.gl/1FakQToSJpCNtZTp6';
            $agencia7->latitud='-17.9771487';
            $agencia7->longitud='-67.1022885';
            $agencia7->status='ACTIVO';
            $agencia7->save();
        }
    }
}
