<?php

namespace App\Http\Controllers;

use App\Http\Resources\EarningStatisticResource;
use App\Models\Order;
use App\Models\Statistic;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatisticController extends Controller
{
    public function earningsStatistic()
    {
        $startDate = now()->subWeeks(4);
        $endDate = now();

        $orders = Order::where('status', 'pesanan selesai')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalSales = $orders->sum('total_price');

        $weeklyEarnings = $orders->groupBy(function ($order) {
            return Carbon::parse($order->created_at)->format('W');
        })
            ->map(function ($week) {
                return $week->sum('total_price');
            });

        $averagePerWeek = $weeklyEarnings->avg();

        $currentWeekEarnings = $weeklyEarnings->last();
        $previousWeekEarnings = $weeklyEarnings->slice(-2, 1)->first() ?? 0;

        $earningGrowth = $previousWeekEarnings > 0
            ? min(round((($currentWeekEarnings - $previousWeekEarnings) / $previousWeekEarnings) * 100 / 2) * 2, 100)
            : ($currentWeekEarnings > 0 ? 100 : 0);

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
        $period = $request->input('period', 'this_week');

        switch ($period) {
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek()->startOfDay();
                $endDate = Carbon::now()->endOfWeek()->endOfDay();
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth()->startOfDay();
                $endDate = Carbon::now()->endOfMonth()->endOfDay();
                break;
            case 'this_year':
                $startDate = Carbon::now()->startOfYear()->startOfDay();
                $endDate = Carbon::now()->endOfYear()->endOfDay();
                break;
            default:
                return response()->json(['error' => 'Invalid period'], 400);
        }

        $orders = Order::where('status', 'pesanan selesai')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('orderItems')
            ->get();

        $salesData = [];
        $currentDate = $startDate->copy();
        $totalProductSales = 0;
        $totalIncome = 0;

        while ($currentDate <= $endDate) {
            $dailyOrders = $orders->filter(function ($order) use ($currentDate) {
                return $order->created_at->isSameDay($currentDate);
            });

            $label = $this->getLabelByPeriod($currentDate, $period);

            $totalPcsSold = $dailyOrders->sum(function ($order) {
                return $order->orderItems->sum('quantity');
            });

            $dailyIncome = $dailyOrders->sum('total_price');

            $totalProductSales += $totalPcsSold;
            $totalIncome += $dailyIncome;

            $salesData[$label] = [
                'date' => $currentDate->toDateString(),
                'total_pcs_sold' => $totalPcsSold,
                'total_income' => $dailyIncome,
            ];

            switch ($period) {
                case 'this_week':
                case 'this_month':
                    $currentDate->addDay();
                    break;
                case 'this_year':
                    $currentDate->addMonth();
                    break;
            }
        }

        return response()->json([
            'data' => $salesData,
            'product_sales' => $totalProductSales,
            'income' => $totalIncome,
        ]);
    }

    private function getLabelByPeriod(Carbon $date, $period)
    {
        switch ($period) {
            case 'this_week':
                return $date->format('l');
            case 'this_month':
                return $date->format('d');
            case 'this_year':
                return $date->format('F');
            default:
                return $date->format('l');
        }
    }
}
