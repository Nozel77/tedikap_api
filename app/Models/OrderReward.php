<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cart_reward_id',
        'total_point',
        'status',
    ];

    protected $casts = [
        'order_items' => 'array',
    ];

    public $incrementing = false;

    public function orderRewardItems()
    {
        return $this->hasMany(OrderRewardItem::class, 'order_reward_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cartReward()
    {
        return $this->belongsTo(CartReward::class);
    }
}
