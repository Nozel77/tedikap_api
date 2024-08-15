<?php

namespace Database\Seeders;

use App\Models\RewardProduct;
use Illuminate\Database\Seeder;

class RewardProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Mango Tea',
                'description' => 'Teh mangga manis.',
                'regular_point' => 50,
                'large_point' => 70,
                'category' => 'tea',
                'image' => 'mangotea.jpg',
            ],
            [
                'name' => 'Lemonade',
                'description' => 'Air lemon yang menyegarkan',
                'regular_point' => 50,
                'large_point' => 80,
                'category' => 'nontea',
                'image' => 'lemonade.jpg',
            ],
            [
                'name' => 'Choco Hazelnut',
                'description' => 'Choco Hazelnut yang lezat.',
                'regular_point' => 50,
                'large_point' => 70,
                'category' => 'nontea',
                'image' => 'hazelnut.jpg',
            ],
            [
                'name' => 'Original Tea',
                'description' => 'Original Tea Lezat.',
                'regular_point' => 30,
                'large_point' => 50,
                'category' => 'tea',
                'image' => 'original.jpg',
            ],
            [
                'name' => 'Yakult Orange',
                'description' => 'Delicious Yakult Series.',
                'regular_point' => 80,
                'large_point' => 100,
                'category' => 'yakult',
                'image' => 'chocopudding.jpg',
            ],
            [
                'name' => 'Yakult Strawberry',
                'description' => 'Delicious Yakult Series.',
                'regular_point' => 80,
                'large_point' => 100,
                'category' => 'yakult',
                'image' => 'sandofruit.jpeg',
            ],
            [
                'name' => 'Keychain Tedikap',
                'description' => 'Official Merchandise Tedikap',
                'regular_point' => 1000,
                'large_point' => 1000,
                'category' => 'merchandise',
                'image' => 'keychain.jpeg',
            ],
        ];

        foreach ($products as $product) {
            $productData = new RewardProduct();
            $productData->fill($product);
            $productData->save();
        }
    }
}
