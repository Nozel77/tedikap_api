<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'date',
        'total_sales',
        'average_per_week',
        'earning_growth',
        'total_pcs_sold',
        'total_income',
    ];
}
