<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("users")->insert([
            [ "name" => "Admin","nick"=>'',"email" => "admin@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("admin"),],
            [ "name" => "BOLETERIA","nick"=>'boleteria',"email" => "boleteria@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "CANDY","nick"=>'candy',"email" => "candy@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "MARY VILLANUEVA","nick"=>'MVILLANUEVA',"email" => "MVILLANUEVA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "OMAR LUIZAGA","nick"=>'OLUIZAGA',"email" => "OLUIZAGA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "PABLO PADILLA","nick"=>'PPADILLA',"email" => "PPADILLA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "ABIGAIL SALAZAR","nick"=>'ASALAZAR',"email" => "ASALAZAR@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "USUARIO CANDY","nick"=>'MMIRANDA',"email" => "MMIRANDA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "DANIELA ALBERTO","nick"=>'DALBERTO',"email" => "DALBERTO@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "JUAN PABLO CUAQUIRA","nick"=>'PCUAQUIRA',"email" => "PCUAQUIRA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "ADOLFO HIDALGO","nick"=>'AHIDALGO',"email" => "AHIDALGO@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "MAURICIO VILLARROEL","nick"=>'MVILLAROEL',"email" => "MVILLAROEL@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "ANGHELO RAMIREZ RICALDI","nick"=>'ARAMIREZ',"email" => "ARAMIREZ@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "JESUS OSCAR FLORES JAILLITA","nick"=>'JFLORES',"email" => "JFLORES@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "ILENKA ANTONIO REVILLA","nick"=>'MYASMIN',"email" => "MYASMIN@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "SAUL GARECA ANTEQUERA","nick"=>'SGARECA',"email" => "SGARECA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "CRISTHIAN ORTEGA BOHORQUEZ","nick"=>'CORTEGA',"email" => "CORTEGA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "JOSUE ISRAEL CORREA ALCOCER","nick"=>'JCORREA',"email" => "JCORREA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "ANAHI TANIA COPA QUEZADA","nick"=>'ACOPA',"email" => "ACOPA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "ARACELY RODRIGUEZ PEREZ","nick"=>'ARODRIGUEZ',"email" => "ARODRIGUEZ@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "GERALDINE PATIÑO","nick"=>'GERAL',"email" => "GERAL@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "YERKO COLQUE ACAPA","nick"=>'YCOLQUE',"email" => "YCOLQUE@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "MARCELO HUARACHI CHILA","nick"=>'MHUARACHI',"email" => "MHUARACHI@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "GHISLAIN MAURA ALARCON APAZA","nick"=>'MALARCON',"email" => "MALARCON@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "ALVARO ARCE","nick"=>'AARCE',"email" => "AARCE@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "RONALD MAMANI CHOQUE","nick"=>'RMAMANI',"email" => "RMAMANI@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "MESIAS JOSE QUISPE QUILO","nick"=>'MJQUISPE',"email" => "MJQUISPE@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "MARIA RENE MORANTE CONDORI","nick"=>'MMORANTE',"email" => "MMORANTE@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "MAURICIO VILLARROEL","nick"=>'MVILLARROEL',"email" => "MVILLARROEL@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "TATIANA CHOQUE","nick"=>'TCHOQUE',"email" => "TCHOQUE@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "BLANCA RAMIREZ","nick"=>'BRAMIREZ',"email" => "BRAMIREZ@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "ANDREA NOGALES ALAVE","nick"=>'ANOGALES',"email" => "ANOGALES@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "JUAN PABLO CERROGRANDE","nick"=>'JCERROGRANDE',"email" => "JCERROGRANDE@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "EVELYN MOYA","nick"=>'EMOYA',"email" => "EMOYA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "MIGUEL ANGEL HUMEREZ MAMANI","nick"=>'MHUMEREZ',"email" => "MHUMEREZ@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "OLIVER CHAMBI GOMEZ","nick"=>'OCHAMBI',"email" => "OCHAMBI@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "NATALY MARTINEZ JIMENEZ","nick"=>'NMARTINEZ',"email" => "NMARTINEZ@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "LUIS ANGEL GONZALES REYES","nick"=>'LGONZALES',"email" => "LGONZALES@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "MARCELO QUIROGA","nick"=>'MQUIROGA',"email" => "MQUIROGA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "ESTEFANY DAYANA VELASQUEZ LOPEZ","nick"=>'EVELASQUEZ',"email" => "EVELASQUEZ@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "KEVIN JONATHAN COPA RICALDI","nick"=>'KCOPA',"email" => "KCOPA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "STEINLER CALDERON","nick"=>'SCALDERON',"email" => "SCALDERON@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "JERSON CAMACHO CHOQUERIVE","nick"=>'JCAMACHO',"email" => "JCAMACHO@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "ERLAN CHAMBI CUEVAS","nick"=>'ECUEVAS',"email" => "ECUEVAS@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "IVETT HINOJOSA","nick"=>'IHINOJOSA',"email" => "IHINOJOSA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "ROXANA ALVAREZ FRANIC","nick"=>'RALVAREZ',"email" => "RALVAREZ@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "DEYMAR MONTAÑO","nick"=>'DMONTAÑO',"email" => "DMONTAÑO@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "LUIS FERNANDO VILLARROEL ARCE","nick"=>'LVILLARROEL',"email" => "LVILLARROEL@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "ISRAEL CORREA ALCOCER","nick"=>'ICORREA',"email" => "ICORREA@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],
            [ "name" => "GILDA ACHACOLLO TARQUI","nick"=>'GACHACOLLO',"email" => "GACHACOLLO@test.com","fechaLimite" => "2030-07-29","password" => bcrypt("123456"),],

        ]);
    }
}
