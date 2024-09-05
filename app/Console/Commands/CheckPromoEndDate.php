<?php

namespace App\Console\Commands;

use App\Models\Promo;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckPromoEndDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promo:check-end-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if any promo has ended and revert product prices to original';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $promos = Promo::where('end_date', '<', Carbon::now())->get();

        foreach ($promos as $promo) {
            foreach ($promo->products as $product) {
                $product->regular_price = $product->original_regular_price;
                $product->large_price = $product->original_large_price;
                $product->save();
            }
        }

        return 0;
    }
}
