<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'points', 'type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
