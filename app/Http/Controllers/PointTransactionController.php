<?php

namespace App\Http\Controllers;

use App\Models\PointConfiguration;
use App\Models\PointTransaction;

class PointTransactionController extends Controller
{
    public function pointHistory()
    {
        $totalPointsUsed = PointTransaction::where('type', 'used')->sum('points');

        $pointConfig = PointConfiguration::all()->first();
        $minimumAmount = $pointConfig->minimum_amount;
        $pointToMoneyConversionRate = $minimumAmount;
        $totalMoneyEquivalent = $totalPointsUsed * $pointToMoneyConversionRate;

        return response()->json([
            'total_points_used' => $totalPointsUsed,
            'total_money_equivalent' => $totalMoneyEquivalent,
        ]);
    }
}
