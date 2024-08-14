<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
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
                'regular_price' => 5000,
                'large_price' => 7000,
                'category' => 'tea',
                'image' => 'mangotea.jpg',
            ],
            [
                'name' => 'Lemonade',
                'description' => 'Air lemon yang menyegarkan',
                'regular_price' => 5000,
                'large_price' => 8000,
                'category' => 'nontea',
                'image' => 'lemonade.jpg',
            ],
            [
                'name' => 'Choco Hazelnut',
                'description' => 'Choco Hazelnut yang lezat.',
                'regular_price' => 5000,
                'large_price' => 7000,
                'category' => 'nontea',
                'image' => 'hazelnut.jpg',
            ],
            [
                'name' => 'Original Tea',
                'description' => 'Original Tea Lezat.',
                'regular_price' => 3000,
                'large_price' => 5000,
                'category' => 'tea',
                'image' => 'original.jpg',
            ],
            [
                'name' => 'Yakult Orange',
                'description' => 'Delicious Yakult Series.',
                'regular_price' => 8500,
                'large_price' => 12000,
                'category' => 'yakult',
                'image' => 'chocopudding.jpg',
            ],
            [
                'name' => 'Yakult Strawberry',
                'description' => 'Delicious Yakult Series.',
                'regular_price' => 8500,
                'large_price' => 12000,
                'category' => 'yakult',
                'image' => 'sandofruit.jpeg',
            ],
        ];

        foreach ($products as $product) {
            $productData = new Product();
            $productData->fill($product);
            $productData->save();
        }
    }
}
