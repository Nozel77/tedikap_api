<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartRewardItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_reward_id',
        'reward_product_id',
        'quantity',
        'temperatur',
        'size',
        'ice',
        'sugar',
        'note',
        'points',
    ];

    public function cart()
    {
        return $this->belongsTo(CartReward::class, 'cart_reward_id');
    }
}
