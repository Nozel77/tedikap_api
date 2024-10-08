<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProductSeeder::class,
            StatusStoreSeeder::class,
            VoucherSeeder::class,
            RewardProductSeeder::class,
            AdminUserSeeder::class,
            UserSeeder::class,
            BannerSeeder::class,
            BoxPromoSeeder::class,
            HelpCenterSeeder::class,
            SessionTimesSeeder::class,
            PointConfigurationSeeder::class,
            // OrderSeeder::class,
        ]);
    }
}
