<?php

namespace Database\Seeders;

use App\Models\HelpCenter;
use Illuminate\Database\Seeder;

class HelpCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $helpCenters = [
            [
                'title' => 'Bagaimana saya bisa mendapatkan Hadiah Pengguna Baru?',
                'subtitle' => 'Hadiah pengguna baru tersedia untuk pertama kali Anda mendaftarkan akun aplikasi Tedikap dengan perangkat baru. Setelah diterima, Anda dapat memeriksanya di dompet voucher Anda dan mempelajari tentang ketentuan penggunaan.',
            ],
            [
                'title' => 'Mengapa saya tidak mendapatkan Hadiah Pengguna Baru?',
                'subtitle' => 'Nomor Telepon yang sama, perangkat selurer ...',
            ],
            [
                'title' => 'Cara Menggunakan Fitur Voucher',
                'subtitle' => 'Fitur voucher tersedia untuk semua pengguna aplikasi Tedikap. Anda dapat menggunakan voucher yang diterima di dompet voucher Anda untuk mendapatkan diskon saat melakukan pembelian di aplikasi Tedikap.',
            ],
        ];

        foreach ($helpCenters as $helpCenter) {
            $helpCenterData = new HelpCenter();
            $helpCenterData->fill($helpCenter);
            $helpCenterData->save();
        }
    }
}
