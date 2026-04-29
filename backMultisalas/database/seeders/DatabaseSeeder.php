<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call([
            UserSeeder::class,
//            DistributorSeeder::class,
//            MovieSeeder::class,
//            CuiSeeder::class,
            SalaSeeder::class,
            SeatSeeder::class,
            PriceSeeder::class,
//            ProgramaSeeder::class,
//            ClientSeeder::class,
//            SaleSeeder::class,
//            TicketSeeder::class,
            RubroSeeder::class,
            ProductoSeeder::class,
            ActivitySeeder::class,
            LeyendaSeeder::class,
            DocumentSeeder::class,
            VehiculoSeeder::class,
            PermisoSeeder::class,
            Permiso_user::class
        ]);
    }
}
