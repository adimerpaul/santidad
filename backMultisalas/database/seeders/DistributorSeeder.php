<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistributorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('distributors')->insert([
            [
                'nombre' => 'Distributor 1',
                'dir' => 'Dir 1',
                'loc' => 'Loc 1',
                'nit' => 'Nit 1',
                'tel' => 'Tel 1',
                'email' => 'Email 1',
                'responsable' => 'Responsable 1',
                'created_at' => now(),
                'updated_at' => now(),
            ],[
                'nombre' => 'Distributor 2',
                'dir' => 'Dir 2',
                'loc' => 'Loc 2',
                'nit' => 'Nit 2',
                'tel' => 'Tel 2',
                'email' => 'Email 2',
                'responsable' => 'Responsable 2',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
