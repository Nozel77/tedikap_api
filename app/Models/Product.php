<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'regular_price',
        'large_price',
        'category',
        'image',
    ];

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
}
