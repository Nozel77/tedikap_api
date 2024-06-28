<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartReward extends Model
{
    use HasFactory;

    public function rewardCartItems()
    {
        return $this->hasMany(CartRewardItem::class);
    }
}
