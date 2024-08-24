<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'email',
        'otp',
    ];

    public function isExpired()
    {
        return Carbon::now()->gt($this->expires_at);
    }
}
