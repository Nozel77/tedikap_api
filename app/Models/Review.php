<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'staff_service',
        'product_quality',
        'note',
        'rating',
    ];

    protected $casts = [
        'staff_service' => 'double',
        'product_quality' => 'double',
        'rating' => 'double',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
