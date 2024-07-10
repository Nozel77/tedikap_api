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
                'name' => 'Green Tea',
                'description' => 'Refreshing green tea.',
                'regular_point' => 30,
                'large_point' => 50,
                'category' => 'tea',
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR3rerCLy9C70ZXgtXADp50Ll-8pLKulyVhml0cXubfd094cf_n5REPLVaZC5nniGmWrYM&usqp=CAU',
            ],
            [
                'name' => 'Original Tea',
                'description' => 'Original Tea',
                'regular_point' => 30,
                'large_point' => 50,
                'category' => 'tea',
                'image' => 'https://img.ws.mms.shopee.co.id/ad37690ae8e69dd79068158592088447',
            ],
            [
                'name' => 'Lemon Tea',
                'description' => 'Smooth Lemon tea.',
                'regular_point' => 30,
                'large_point' => 50,
                'category' => 'tea',
                'image' => 'https://agentisu.com/wp-content/uploads/2023/09/1694534944666.jpg',
            ],
            [
                'name' => 'Mango Tea',
                'description' => 'Mango tea.',
                'regular_point' => 30,
                'large_point' => 50,
                'category' => 'tea',
                'image' => 'https://takestwoeggs.com/wp-content/uploads/2021/10/Mango-Green-Tea-Takestwoeggs-sq.jpg',
            ],
            [
                'name' => 'Vanilla Tea',
                'description' => 'Vanilla Tea Lezat.',
                'regular_point' => 30,
                'large_point' => 50,
                'category' => 'tea',
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQb55vCSZETc9lrPqBpW1ovC4_sGc_cZ_RgsJZhs43sqxeyxcI4h5smmI5WJy7AOHglguM&usqp=CAU',
            ],
            [
                'name' => 'Lemonade',
                'description' => 'Fresh Lemonade',
                'regular_point' => 30,
                'large_point' => 50,
                'category' => 'nontea',
                'image' => 'https://detoxinista.com/wp-content/uploads/2022/05/lemonade-recipe.jpg',
            ],
            [
                'name' => 'Smoothie',
                'description' => 'Delicious smoothie.',
                'regular_point' => 30,
                'large_point' => 50,
                'category' => 'nontea',
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQfAVbiEihquqGf6teaxPuHjQU9uG0IBwON-A&s',
            ],
            [
                'name' => 'Matcha',
                'description' => 'Matcha Grass.',
                'regular_point' => 30,
                'large_point' => 50,
                'category' => 'nontea',
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTXoMEhpuhInjkJZqhqAJ_7OQ1a-S0FXuCKAA&s',
            ],
            [
                'name' => 'Coffee',
                'description' => 'delicious coffee.',
                'regular_point' => 30,
                'large_point' => 30,
                'category' => 'nontea',
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTSVYAb9l2oOQ4iRwqARLLwHoTuyZgY9RKT0Q&s',
            ],
            [
                'name' => 'Milkshake',
                'description' => 'Creamy milkshake.',
                'regular_point' => 30,
                'large_point' => 30,
                'category' => 'nontea',
                'image' => 'https://www.dessertfortwo.com/wp-content/uploads/2022/08/How-to-Make-a-Milkshake-11-735x1103.jpg',
            ],
            [
                'name' => 'Sandwich',
                'description' => 'Delicious sandwich.',
                'regular_point' => 50,
                'large_point' => 50,
                'category' => 'snack',
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQJo4ziA4i6jFa5nfck-mfjk0g7-AxUPjK6Sw&s',
            ],
            [
                'name' => 'Cupcake',
                'description' => 'Sweet cupcake.',
                'regular_point' => 130,
                'large_point' => 130,
                'category' => 'snack',
                'image' => 'https://natashaskitchen.com/wp-content/uploads/2020/05/Vanilla-Cupcakes-3.jpg',
            ],
            [
                'name' => 'Cookie',
                'description' => 'Crunchy cookie.',
                'regular_point' => 50,
                'large_point' => 50,
                'category' => 'snack',
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRfqsuWEeM1mFd-_ELti5hY-a7YHauXga6m8A&s',
            ],
            [
                'name' => 'Pastry',
                'description' => 'Flaky pastry.',
                'regular_point' => 20000,
                'large_point' => 20000,
                'category' => 'snack',
                'image' => 'https://i0.wp.com/resepkoki.id/wp-content/uploads/2018/11/puff-pastry.jpg?fit=1194%2C893&ssl=1',
            ],
            [
                'name' => 'Muffin',
                'description' => 'Tasty muffin.',
                'regular_point' => 20000,
                'large_point' => 20000,
                'category' => 'snack',
                'image' => 'https://sugargeekshow.com/wp-content/uploads/2019/10/chocolate-chip-muffins-featured.jpg',
            ],
        ];

        foreach ($products as $product) {
            $productData = new RewardProduct();
            $productData->fill($product);
            $productData->save();
        }
    }
}
