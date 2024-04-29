<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'point'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($point) {
            if (is_null($point->point)) {
                $point->point = 0;
            }
        });
    }
}
