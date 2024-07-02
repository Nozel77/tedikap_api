<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderRewardItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_reward_id',
        'reward_product_id',
        'item_type',
        'temperatur',
        'size',
        'ice',
        'sugar',
        'note',
        'quantity',
        'points',
    ];

    public function orderReward()
    {
        return $this->belongsTo(OrderReward::class, 'order_reward_id');
    }
}
