<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("prices")->insert([
            ["serie"=>"MD0","precio"=>'15.00'],
            ["serie"=>"ES0","precio"=>'17.50'],
            ["serie"=>"WV0","precio"=>'20.00'],
            ["serie"=>"EE0","precio"=>'50.00'],
            ["serie"=>"WW0","precio"=>'40.00'],
            ["serie"=>"W10","precio"=>'35.00'],
            ["serie"=>"W20","precio"=>'30.00'],
            ["serie"=>"D40","precio"=>'40.00'],
            ["serie"=>"D35","precio"=>'35.00'],
            ["serie"=>"D30","precio"=>'30.00'],
            ["serie"=>"D20","precio"=>'20.00'],
            ["serie"=>"W10","precio"=>'10.00'],
            ["serie"=>"W3","precio"=>'25.00'],
            ["serie"=>"2X1","precio"=>'12.50'],
            ["serie"=>"D50","precio"=>'50.00'],
            ["serie"=>"W120","precio"=>'20.00'],
        ]);
    }
}
