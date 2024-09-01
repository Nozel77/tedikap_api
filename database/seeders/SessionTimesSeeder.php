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
        SessionTime::truncate();

        $sessions = [
            [
                'session_name' => 'Pick Up Sesi 1',
                'start_time' => '07:00',
                'end_time' => '10:20',
            ],
            [
                'session_name' => 'Pick Up Sesi 2',
                'start_time' => '10:40',
                'end_time' => '13:40',
            ],
        ];

        foreach ($sessions as $session) {
            SessionTime::create($session);
        }
    }
}
