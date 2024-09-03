<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'minimum_amount',
        'collect_point',
    ];
}
