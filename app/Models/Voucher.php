<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'discount',
        'min_transaction',
        'max_discount',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'is_eligible' => 'boolean',
    ];

    public function userVouchers()
    {
        return $this->hasMany(UserVoucher::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
