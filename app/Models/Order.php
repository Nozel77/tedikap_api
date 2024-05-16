<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'promo_id',
        'temperatur',
        'size',
        'ice',
        'sugar',
        'note',
        'quantity',
        'total',
    ];
}
