<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call([
            AgenciaSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            DocumentSeeder::class,
            CarouselSeeder::class,
            UnidSeeder::class,
        ]);
        $sql = storage_path('sql/clients_202305292324.sql');
        DB::unprepared(file_get_contents($sql));
        $sql = storage_path('sql/products_202310011128.sql');
        DB::unprepared(file_get_contents($sql));
//        Product::factory(20000)->create();
//        Client::factory(5)->create();
    }
}
