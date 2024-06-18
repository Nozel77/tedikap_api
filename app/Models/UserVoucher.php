<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'voucher_id',
        'used',
    ];

    /**
     * Get the user that owns the UserVoucher.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the voucher that owns the UserVoucher.
     */
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}
