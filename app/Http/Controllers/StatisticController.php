<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnalyticStatisticResource;
use App\Http\Resources\EarningStatisticResource;
use App\Models\Order;
use App\Models\Statistic;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatisticController extends Controller
{
    public function earningsStatistic()
    {
        $orders = Order::where('status', 'pesanan selesai')->get();

        $totalSales = $orders->sum('total_price');

        $startDate = now()->subWeeks(4);
        $endDate = now();

        $weeklyEarnings = $orders->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(function ($order) {
                return Carbon::parse($order->created_at)->format('W');
            })
            ->map(function ($week) {
                return $week->sum('total_price');
            });

        $averagePerWeek = $weeklyEarnings->avg();

        $currentWeekEarnings = $weeklyEarnings->last();
        $previousWeekEarnings = $weeklyEarnings->slice(-2, 1)->first() ?? 0;

        if ($previousWeekEarnings > 0) {
            $earningGrowth = (($currentWeekEarnings - $previousWeekEarnings) / $previousWeekEarnings) * 100;
            // Round to the nearest even integer
            $earningGrowth = round($earningGrowth / 2) * 2;
            // Ensure it does not exceed 100
            $earningGrowth = min($earningGrowth, 100);
        } else {
            $earningGrowth = $currentWeekEarnings > 0 ? 100 : 0;
        }

        Statistic::updateOrCreate(
            ['type' => 'earning', 'date' => now()->toDateString()],
            [
                'total_sales' => $totalSales,
                'average_per_week' => $averagePerWeek,
                'earning_growth' => $earningGrowth,
            ]
        );

        return new EarningStatisticResource([
            'total_sales' => $totalSales,
            'average_per_week' => $averagePerWeek,
            'earning_growth' => $earningGrowth,
        ]);
    }

    public function analyticStatistic(Request $request)
    {
        $period = $request->input('period', 'this_week'); // Ambil parameter period dari request, default 'this_week'
        $startDate = $request->input('start_date'); // Ambil parameter start_date dari request
        $endDate = $request->input('end_date'); // Ambil parameter end_date dari request

        switch ($period) {
            case 'this_week':
                $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfWeek();
                $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfWeek();
                break;

            case 'this_month':
                $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
                $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();
                break;

            case 'this_year':
                $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfYear();
                $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfYear();
                break;

            default:
                return response()->json(['error' => 'Invalid period'], 400);
        }

        $orders = Order::where('status', 'pesanan selesai')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('orderItems')
            ->get();

        $salesData = [];
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            switch ($period) {
                case 'this_week':
                    $dailyOrders = $orders->filter(function ($order) use ($currentDate) {
                        return Carbon::parse($order->created_at)->isSameDay($currentDate);
                    });
                    $label = $currentDate->format('l');
                    $currentDate->addDay();
                    break;

                case 'this_month':
                    $dailyOrders = $orders->filter(function ($order) use ($currentDate) {
                        return Carbon::parse($order->created_at)->format('W') == $currentDate->format('W');
                    });
                    $label = $currentDate->format('W');
                    $currentDate->addWeek();
                    break;

                case 'this_year':
                    $dailyOrders = $orders->filter(function ($order) use ($currentDate) {
                        return Carbon::parse($order->created_at)->format('m') == $currentDate->format('m');
                    });
                    $label = $currentDate->format('F');
                    $currentDate->addMonth();
                    break;
            }

            $totalPcsSold = $dailyOrders->sum(function ($order) {
                return $order->orderItems->sum('quantity');
            });

            $totalIncome = $dailyOrders->sum('total_price');

            $salesData[$label] = new AnalyticStatisticResource((object) [
                'total_pcs_sold' => $totalPcsSold,
                'total_income' => $totalIncome,
            ]);

            // Save to database
            Statistic::updateOrCreate(
                ['type' => 'analytic', 'date' => $currentDate->toDateString()],
                [
                    'total_pcs_sold' => $totalPcsSold,
                    'total_income' => $totalIncome,
                ]
            );
        }

        return response()->json([
            'data' => $salesData,
        ]);
    }
}
