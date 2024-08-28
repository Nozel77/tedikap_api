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
                'image' => 'yakultorange.jpeg',
            ],
            [
                'name' => 'Yakult Strawberry',
                'description' => 'Delicious Yakult Series.',
                'regular_price' => 8500,
                'large_price' => 12000,
                'category' => 'yakult',
                'image' => 'yakultstrawberry.jpg',
            ],
            [
                'name' => 'Peach Tea',
                'description' => 'Teh rasa peach yang segar.',
                'regular_price' => 6000,
                'large_price' => 8000,
                'category' => 'tea',
                'image' => 'peachtea.jpeg',
            ],
            [
                'name' => 'Green Tea Latte',
                'description' => 'Latte dengan rasa teh hijau.',
                'regular_price' => 7000,
                'large_price' => 9000,
                'category' => 'tea',
                'image' => 'greentealatte.jpg',
            ],
            [
                'name' => 'Ginger Tea',
                'description' => 'Teh madu yang harum.',
                'regular_price' => 5500,
                'large_price' => 7500,
                'category' => 'tea',
                'image' => 'gingertea.jpeg',
            ],
            [
                'name' => 'Mango Smoothie',
                'description' => 'Smoothie mangga yang lezat.',
                'regular_price' => 7000,
                'large_price' => 10000,
                'category' => 'nontea',
                'image' => 'mangosmoothie.jpeg',
            ],
            [
                'name' => 'Matcha Latte',
                'description' => 'Matcha latte yang nikmat.',
                'regular_price' => 8000,
                'large_price' => 11000,
                'category' => 'nontea',
                'image' => 'matchalatte.jpg',
            ],
            [
                'name' => 'Banana Shake',
                'description' => 'Shake pisang dengan susu.',
                'regular_price' => 6000,
                'large_price' => 9000,
                'category' => 'nontea',
                'image' => 'bananashake.jpeg',
            ],
            [
                'name' => 'Yakult Blueberry',
                'description' => 'Delicious Yakult Series.',
                'regular_price' => 8500,
                'large_price' => 12000,
                'category' => 'yakult',
                'image' => 'yakultblueberry.jpeg',
            ],
            [
                'name' => 'Yakult Mango',
                'description' => 'Delicious Yakult Series.',
                'regular_price' => 8500,
                'large_price' => 12000,
                'category' => 'yakult',
                'image' => 'yakultmango.jpg',
            ],
        ];

        foreach ($products as $product) {
            $productData = new Product();
            $productData->fill($product);
            $productData->save();
        }
    }
}
