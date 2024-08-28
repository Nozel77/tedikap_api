<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function generateCustomUUID()
    {
        $now = now()->setTimezone('Asia/Jakarta');
        $date = $now->format('d');
        $month = $now->format('m');
        $year = $now->format('Y');
        $hour = $now->format('H');
        $minute = $now->format('i');
        $second = $now->format('s');
        $millisecond = $now->format('v');
        $randomNumber = rand(1000, 9999);

        $customUUID = strtoupper("ORD{$date}{$month}{$year}{$hour}{$minute}{$second}{$millisecond}{$randomNumber}");

        return $customUUID;
    }

    public function generateRandomPickupSchedule()
    {
        $schedules = [
            '09:40-10:00',
            '12:00-12:30',
        ];

        return $schedules[array_rand($schedules)];
    }

    public function run()
    {
        $userId = 2;
        $paymentChannels = ['SHOPEEPAY', 'OVO', 'DANA'];

        $temperaturOptions = ['hot', 'ice'];
        $sizeOptions = ['regular', 'large'];
        $iceOptions = ['less', 'normal'];
        $sugarOptions = ['less', 'normal'];
        $itemTypes = ['product', 'reward'];

        for ($i = 0; $i < 5; $i++) {
            for ($j = 0; $j < 5; $j++) {
                $order = Order::create([
                    'id' => $this->generateCustomUUID(),
                    'user_id' => $userId,
                    'cart_id' => null,
                    'voucher_id' => null,
                    'total_price' => rand(10000, 50000),
                    'discount_amount' => rand(1000, 5000),
                    'reward_point' => rand(1, 20),
                    'status' => 'menunggu konfirmasi',
                    'status_description' => 'Pesanan telah selesai',
                    'whatsapp' => 'https://wa.me/62895395343223?text=halo+saya+ingin+tanya+tentang+pesanan+saya',
                    'whatsapp_user' => '0895395343223',
                    'order_type' => 'order',
                    'schedule_pickup' => $this->generateRandomPickupSchedule(),
                    'payment_channel' => $paymentChannels[array_rand($paymentChannels)],
                    'icon_status' => 'ic_status_completed',
                    'rating' => 0,
                    'created_at' => Carbon::now()->subWeeks($i)->subDays(rand(0, 6)),
                    'updated_at' => Carbon::now()->subWeeks($i)->subDays(rand(0, 6)),
                    'expires_at' => Carbon::now()->addMinutes(5),
                ]);

                for ($k = 0; $k < 3; $k++) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => rand(1, 4),
                        'item_type' => $itemTypes[array_rand($itemTypes)],
                        'temperatur' => $temperaturOptions[array_rand($temperaturOptions)],
                        'size' => $sizeOptions[array_rand($sizeOptions)],
                        'ice' => $iceOptions[array_rand($iceOptions)],
                        'sugar' => $sugarOptions[array_rand($sugarOptions)],
                        'note' => 'Catatan untuk item '.$k,
                        'quantity' => rand(1, 5),
                        'price' => rand(5000, 20000),
                    ]);
                }

                for ($k = 0; $k < 2; $k++) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => rand(1, 6), // menggunakan range ID yang berbeda untuk variasi
                        'item_type' => $itemTypes[array_rand($itemTypes)],
                        'temperatur' => $temperaturOptions[array_rand($temperaturOptions)],
                        'size' => $sizeOptions[array_rand($sizeOptions)],
                        'ice' => $iceOptions[array_rand($iceOptions)],
                        'sugar' => $sugarOptions[array_rand($sugarOptions)],
                        'note' => 'Catatan tambahan untuk item '.$k,
                        'quantity' => rand(1, 5),
                        'price' => rand(5000, 20000),
                    ]);
                }
            }
        }
    }
}
