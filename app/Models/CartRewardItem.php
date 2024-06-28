<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartRewardItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'temperatur',
        'size',
        'ice',
        'sugar',
        'note',
        'price',
    ];

    public function cart(){
        return $this->belongsTo(CartReward::class);
    }

    public function rewardProduct(){
        return $this->belongsTo(RewardProduct::class);
    }
}
