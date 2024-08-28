<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banners = [
            [
                'image' => 'promo.png',
            ],
            [
                'image' => 'promo2.png',
            ],
            [
                'image' => 'promo3.png',
            ],
        ];

        foreach ($banners as $banner) {
            $productData = new Banner();
            $productData->fill($banner);
            $productData->save();
        }
    }
}
