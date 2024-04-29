<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'point_price',
        'category',
    ];
}
