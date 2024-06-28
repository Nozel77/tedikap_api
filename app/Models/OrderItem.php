<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'reward_item_id',
        'item_type',
        'temperatur',
        'size',
        'ice',
        'sugar',
        'note',
        'quantity',
        'price',
        'points',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function rewardItem()
    {
        return $this->belongsTo(CartRewardItem::class);
    }
}
