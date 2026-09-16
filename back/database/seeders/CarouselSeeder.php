<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarouselSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("carousels")->insert([
            [
                "url" => "",
                "image" => "Banner_WebSuperbebe_ba1c4f63-9e23-486e-a5e8-b525d78d6e9b_1221x560_crop_center.webp",
                "imageResponsive" => "Banner_WebSuperbebe_ba1c4f63-9e23-486e-a5e8-b525d78d6e9b_1221x560_crop_center.webp",
            ],
            [
                "url" => "",
                "image" => "thumbnail_Banner-Web-productos-para-invierno_1221x560_crop_center.webp",
                "imageResponsive" => "thumbnail_Banner-Web-productos-para-invierno_1221x560_crop_center.webp",
            ],
            [
                "url" => "",
                "image" => "Banner-web-lactovit_f92cbe7f-49e0-4268-bf72-325bffeb179d_1221x560_crop_center.webp",
                "imageResponsive" => "Banner-web-lactovit_f92cbe7f-49e0-4268-bf72-325bffeb179d_1221x560_crop_center.webp",
            ],
        ]);
    }
}
