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
        'original_regular_price',
        'original_large_price',
        'category',
        'image',
    ];

    protected $casts = [
        'stock' => 'boolean',
    ];

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function getFavoritesCountAttribute()
    {
        return $this->favorites()->count();
    }

    public function promos()
    {
        return $this->belongsToMany(Promo::class, 'product_promotion');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_product');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }
}
