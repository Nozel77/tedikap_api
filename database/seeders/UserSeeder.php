<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $whatsappMessage = urlencode('Halo Tedikap, Saya membutuhkan bantuan');
        User::factory()->create([
            'name' => 'halo',
            'email' => 'halo@mail.com',
            'password' => Hash::make('halopass'),
            'whatsapp_service' => "https://wa.me/62895395343223?text={$whatsappMessage}",
        ]);

        User::factory()->create([
            'name' => 'nozel',
            'email' => 'nozel@mail.com',
            'password' => Hash::make('nozelpass'),
            'whatsapp_service' => "https://wa.me/62895395343223?text={$whatsappMessage}",
        ]);
    }
}
