<?php

namespace Database\Seeders;

use App\Models\StatusStore;
use Illuminate\Database\Seeder;

class StatusStoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $status = new StatusStore();
        $status->open = true;
        $status->save();
    }
}
