<?php

namespace Database\Seeders;

use App\Models\BoxPromo;
use Illuminate\Database\Seeder;

class BoxPromoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $boxPromos = [
            [
                'title' => 'Jajan Makin Hemat',
                'subtitle' => 'Dapatkan diskon dan harga spesial hanya dengan melakukan pemesanan di App Tedikap',
                'image' => 'promo2.png',
            ],
        ];

        foreach ($boxPromos as $boxPromo) {
            $boxPromoData = new BoxPromo();
            $boxPromoData->fill($boxPromo);
            $boxPromoData->save();
        }
    }
}
