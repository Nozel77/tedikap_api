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
                'regular_point' => 85,
                'large_point' => 120,
                'category' => 'yakult',
                'image' => 'yakultorange.jpeg',
            ],
            [
                'name' => 'Yakult Strawberry',
                'description' => 'Delicious Yakult Series.',
                'regular_point' => 85,
                'large_point' => 120,
                'category' => 'yakult',
                'image' => 'yakultstrawberry.jpg',
            ],
            [
                'name' => 'Peach Tea',
                'description' => 'Teh rasa peach yang segar.',
                'regular_point' => 60,
                'large_point' => 80,
                'category' => 'tea',
                'image' => 'peachtea.jpeg',
            ],
            [
                'name' => 'Green Tea Latte',
                'description' => 'Latte dengan rasa teh hijau.',
                'regular_point' => 70,
                'large_point' => 90,
                'category' => 'tea',
                'image' => 'greentealatte.jpg',
            ],
            [
                'name' => 'Ginger Tea',
                'description' => 'Teh madu yang harum.',
                'regular_point' => 55,
                'large_point' => 75,
                'category' => 'tea',
                'image' => 'gingertea.jpeg',
            ],
            [
                'name' => 'Mango Smoothie',
                'description' => 'Smoothie mangga yang lezat.',
                'regular_point' => 70,
                'large_point' => 100,
                'category' => 'nontea',
                'image' => 'mangosmoothie.jpeg',
            ],
            [
                'name' => 'Matcha Latte',
                'description' => 'Matcha latte yang nikmat.',
                'regular_point' => 80,
                'large_point' => 110,
                'category' => 'nontea',
                'image' => 'matchalatte.jpg',
            ],
            [
                'name' => 'Banana Shake',
                'description' => 'Shake pisang dengan susu.',
                'regular_point' => 60,
                'large_point' => 90,
                'category' => 'nontea',
                'image' => 'bananashake.jpeg',
            ],
            [
                'name' => 'Yakult Blueberry',
                'description' => 'Delicious Yakult Series.',
                'regular_point' => 85,
                'large_point' => 120,
                'category' => 'yakult',
                'image' => 'yakultblueberry.jpeg',
            ],
            [
                'name' => 'Yakult Mango',
                'description' => 'Delicious Yakult Series.',
                'regular_point' => 85,
                'large_point' => 120,
                'category' => 'yakult',
                'image' => 'yakultmango.jpg',
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
