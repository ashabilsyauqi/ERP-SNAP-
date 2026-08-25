<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $period = $request->input('period', 'daily');
        $selectedBranchId = $request->input('branch_id', 'all');
        $isAllBranches = ($selectedBranchId === 'all' || empty($selectedBranchId));

        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();

        $timeframe = $request->input('timeframe', '6m'); // Default 6 bulan
        $now = Carbon::now();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date', $now->toDateString());

        // Apply timeframe presets if custom date range isn't manually specified
        if (!$request->filled('start_date')) {
            switch ($timeframe) {
                case '1w':
                    $startDate = $now->copy()->subDays(7)->toDateString();
                    $period = 'daily';
                    break;
                case '1m':
                    $startDate = $now->copy()->subMonth()->toDateString();
                    $period = 'daily';
                    break;
                case '3m':
                    $startDate = $now->copy()->subMonths(3)->toDateString();
                    $period = 'daily';
                    break;
                case '6m':
                default:
                    $startDate = $now->copy()->subMonths(6)->toDateString();
                    $period = $request->input('period', 'monthly');
                    break;
                case '1y':
                    $startDate = $now->copy()->subYear()->toDateString();
                    $period = $request->input('period', 'monthly');
                    break;
                case '3y':
                    $startDate = $now->copy()->subYears(3)->toDateString();
                    $period = $request->input('period', 'monthly');
                    break;
                case '5y':
                    $startDate = $now->copy()->subYears(5)->toDateString();
                    $period = $request->input('period', 'yearly');
                    break;
            }
        }

        $query = Transaction::with('branch');

        if ($user->role !== 'owner') {
            $query->where('branch_id', $user->branch_id);
            $selectedBranchId = $user->branch_id;
            $isAllBranches = false;
        } elseif (!$isAllBranches) {
            $query->where('branch_id', $selectedBranchId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        $salesData = collect();
        $branchBreakdown = collect();

        // 1. Grouped Sales Table Query
        if ($period === 'daily') {
            $selectFields = [
                DB::raw('DATE(created_at) as date_val'),
                DB::raw('COUNT(id) as total_transactions'),
                DB::raw('SUM(total_price) as total_sales'),
                DB::raw("SUM(CASE WHEN payment_method = 'Cash' THEN total_price ELSE 0 END) as cash_sales"),
                DB::raw("SUM(CASE WHEN payment_method = 'QRIS' THEN total_price ELSE 0 END) as qris_sales"),
                DB::raw("SUM(CASE WHEN payment_method = 'Transfer' THEN total_price ELSE 0 END) as transfer_sales")
            ];

            $groupBy = ['date_val'];
            if ($isAllBranches) {
                $selectFields[] = 'branch_id';
                $groupBy[] = 'branch_id';
            }

            $rawRecords = (clone $query)->select($selectFields)
                ->groupBy($groupBy)
                ->orderBy('date_val', 'desc')
                ->get();

            foreach ($rawRecords as $row) {
                $branchObj = $branches->firstWhere('id', $row->branch_id);
                $salesData->push((object)[
                    'period_date' => Carbon::parse($row->date_val)->translatedFormat('d F Y'),
                    'raw_date' => Carbon::parse($row->date_val)->format('d/m'),
                    'branch_name' => $branchObj ? $branchObj->nama_cabang : 'Pusat',
                    'branch_id' => $row->branch_id ?? null,
                    'total_transactions' => $row->total_transactions,
                    'cash_sales' => $row->cash_sales,
                    'qris_sales' => $row->qris_sales,
                    'transfer_sales' => $row->transfer_sales,
                    'total_sales' => $row->total_sales,
                ]);
            }
        } elseif ($period === 'monthly') {
            $selectFields = [
                DB::raw('YEAR(created_at) as year_val'),
                DB::raw('MONTH(created_at) as month_val'),
                DB::raw('COUNT(id) as total_transactions'),
                DB::raw('SUM(total_price) as total_sales'),
                DB::raw("SUM(CASE WHEN payment_method = 'Cash' THEN total_price ELSE 0 END) as cash_sales"),
                DB::raw("SUM(CASE WHEN payment_method = 'QRIS' THEN total_price ELSE 0 END) as qris_sales"),
                DB::raw("SUM(CASE WHEN payment_method = 'Transfer' THEN total_price ELSE 0 END) as transfer_sales")
            ];

            $groupBy = ['year_val', 'month_val'];
            if ($isAllBranches) {
                $selectFields[] = 'branch_id';
                $groupBy[] = 'branch_id';
            }

            $rawRecords = (clone $query)->select($selectFields)
                ->groupBy($groupBy)
                ->orderBy('year_val', 'desc')
                ->orderBy('month_val', 'desc')
                ->get();

            foreach ($rawRecords as $row) {
                $branchObj = $branches->firstWhere('id', $row->branch_id);
                $monthName = Carbon::createFromDate($row->year_val, $row->month_val, 1)->translatedFormat('F Y');
                $salesData->push((object)[
                    'period_date' => $monthName,
                    'raw_date' => Carbon::createFromDate($row->year_val, $row->month_val, 1)->format('M Y'),
                    'branch_name' => $branchObj ? $branchObj->nama_cabang : 'Pusat',
                    'branch_id' => $row->branch_id ?? null,
                    'total_transactions' => $row->total_transactions,
                    'cash_sales' => $row->cash_sales,
                    'qris_sales' => $row->qris_sales,
                    'transfer_sales' => $row->transfer_sales,
                    'total_sales' => $row->total_sales,
                ]);
            }
        } else {
            // Yearly
            $selectFields = [
                DB::raw('YEAR(created_at) as year_val'),
                DB::raw('COUNT(id) as total_transactions'),
                DB::raw('SUM(total_price) as total_sales'),
                DB::raw("SUM(CASE WHEN payment_method = 'Cash' THEN total_price ELSE 0 END) as cash_sales"),
                DB::raw("SUM(CASE WHEN payment_method = 'QRIS' THEN total_price ELSE 0 END) as qris_sales"),
                DB::raw("SUM(CASE WHEN payment_method = 'Transfer' THEN total_price ELSE 0 END) as transfer_sales")
            ];

            $groupBy = ['year_val'];
            if ($isAllBranches) {
                $selectFields[] = 'branch_id';
                $groupBy[] = 'branch_id';
            }

            $rawRecords = (clone $query)->select($selectFields)
                ->groupBy($groupBy)
                ->orderBy('year_val', 'desc')
                ->get();

            foreach ($rawRecords as $row) {
                $branchObj = $branches->firstWhere('id', $row->branch_id);
                $salesData->push((object)[
                    'period_date' => 'Tahun ' . $row->year_val,
                    'raw_date' => 'Tahun ' . $row->year_val,
                    'branch_name' => $branchObj ? $branchObj->nama_cabang : 'Pusat',
                    'branch_id' => $row->branch_id ?? null,
                    'total_transactions' => $row->total_transactions,
                    'cash_sales' => $row->cash_sales,
                    'qris_sales' => $row->qris_sales,
                    'transfer_sales' => $row->transfer_sales,
                    'total_sales' => $row->total_sales,
                ]);
            }
        }

        // 2. Per-Branch Summary Cards / Breakdown
        $branchBreakdown = (clone $query)
            ->select(
                'branch_id',
                DB::raw('COUNT(id) as total_orders'),
                DB::raw('SUM(total_price) as total_omzet'),
                DB::raw('AVG(total_price) as avg_order_value')
            )
            ->groupBy('branch_id')
            ->get()
            ->map(function ($item) use ($branches) {
                $b = $branches->firstWhere('id', $item->branch_id);
                $item->branch_name = $b ? $b->nama_cabang : 'Cabang Utama';
                return $item;
            });

        // 3. Multi-Dataset Chart Configuration
        $uniqueDates = $salesData->pluck('raw_date')->unique()->values()->reverse()->values()->all();
        $chartDatasets = [];

        $branchColors = [
            0 => ['border' => '#2563EB', 'bg' => 'rgba(37, 99, 235, 0.1)'], // Blue
            1 => ['border' => '#059669', 'bg' => 'rgba(5, 150, 105, 0.1)'], // Emerald
            2 => ['border' => '#D97706', 'bg' => 'rgba(217, 119, 6, 0.1)'],  // Amber
            3 => ['border' => '#7C3AED', 'bg' => 'rgba(124, 58, 237, 0.1)'], // Purple
            4 => ['border' => '#E11D48', 'bg' => 'rgba(225, 29, 72, 0.1)'],  // Rose
        ];

        if ($isAllBranches) {
            $distinctBranches = $salesData->pluck('branch_name')->unique()->values();
            foreach ($distinctBranches as $index => $bName) {
                $dataPoints = [];
                foreach ($uniqueDates as $d) {
                    $match = $salesData->where('raw_date', $d)->where('branch_name', $bName)->first();
                    $dataPoints[] = $match ? (float) $match->total_sales : 0;
                }

                $color = $branchColors[$index % count($branchColors)];
                $chartDatasets[] = [
                    'label' => $bName,
                    'data' => $dataPoints,
                    'borderColor' => $color['border'],
                    'backgroundColor' => $color['bg'],
                    'borderWidth' => 2.5,
                    'fill' => true,
                    'tension' => 0.3,
                    'pointBackgroundColor' => $color['border'],
                    'pointRadius' => 4
                ];
            }
        } else {
            $dataPoints = [];
            foreach ($uniqueDates as $d) {
                $match = $salesData->where('raw_date', $d)->first();
                $dataPoints[] = $match ? (float) $match->total_sales : 0;
            }
            $chartDatasets[] = [
                'label' => 'Total Omzet Penjualan (Rp)',
                'data' => $dataPoints,
                'borderColor' => '#1E3A8A',
                'backgroundColor' => 'rgba(30, 58, 138, 0.1)',
                'borderWidth' => 2.5,
                'fill' => true,
                'tension' => 0.3,
                'pointBackgroundColor' => '#2563EB',
                'pointRadius' => 4
            ];
        }

        $chartData = (object)[
            'labels' => $uniqueDates,
            'datasets' => $chartDatasets
        ];

        return view('reports.sales', compact('salesData', 'chartData', 'period', 'branches', 'branchBreakdown', 'isAllBranches', 'timeframe', 'startDate', 'endDate'));
    }
}

