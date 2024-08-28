<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vouchers = [
            [
                'title' => 'Nikmati promo sebesar 30% Sekarang !!!',
                'description' => 'WLEOWLEOWLEO',
                'image' => 'promo.png',
                'discount' => 30,
                'min_transaction' => 20000,
                'max_discount' => 30000,
                'is_used' => false,
                'start_date' => '2024-06-21',
                'end_date' => '2024-08-27',
            ],
            [
                'title' => 'Nikmati promo sebesar 20% Sekarang !!!',
                'description' => 'WLEOWLEOWLEO',
                'image' => 'promo2.png',
                'discount' => 10,
                'min_transaction' => 10000,
                'max_discount' => 20000,
                'is_used' => false,
                'start_date' => '2024-06-21',
                'end_date' => '2024-08-27',
            ],
            [
                'title' => 'Nikmati promo sebesar 10% Sekarang !!!',
                'description' => 'WLEOWLEOWLEO',
                'image' => 'promo3.png',
                'discount' => 10,
                'min_transaction' => 10000,
                'max_discount' => 20000,
                'is_used' => false,
                'start_date' => '2024-06-21',
                'end_date' => '2024-08-27',
            ],
        ];

        foreach ($vouchers as $voucher) {
            $voucherData = new Voucher();
            $voucherData->fill($voucher);
            $voucherData->save();
        }
    }
}
