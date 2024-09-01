<?php

namespace Database\Seeders;

use App\Models\SessionTime;
use Illuminate\Database\Seeder;

class SessionTimesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Hapus semua data lama agar tidak ada duplikasi saat menjalankan seeder berkali-kali
        SessionTime::truncate();

        // Data awal untuk sesi
        $sessions = [
            [
                'session_name' => 'Pick Up Sesi 1',
                'start_time' => '07:00',
                'end_time' => '09:20',
            ],
            [
                'session_name' => 'Pick Up Sesi 2',
                'start_time' => '09:40',
                'end_time' => '11:40',
            ],
        ];

        // Insert data ke tabel session_times
        foreach ($sessions as $session) {
            SessionTime::create($session);
        }
    }
}
