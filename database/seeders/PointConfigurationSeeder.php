<?php

namespace Database\Seeders;

use App\Models\PointConfiguration;
use Illuminate\Database\Seeder;

class PointConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PointConfiguration::create([
            'minimum_amount' => 5000,
            'collect_point' => 1000,
        ]);
    }
}
