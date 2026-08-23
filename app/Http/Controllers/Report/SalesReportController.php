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
        
        $query = Transaction::query();

        if ($user->role !== 'owner') {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $salesData = collect();
        $chartLabels = [];
        $chartValues = [];

        if ($period === 'daily') {
            $data = (clone $query)->select(
                DB::raw('DATE(created_at) as date_val'),
                DB::raw('COUNT(id) as total_transactions'),
                DB::raw('SUM(total_price) as total_sales'),
                DB::raw("SUM(CASE WHEN payment_method = 'Cash' THEN total_price ELSE 0 END) as cash_sales"),
                DB::raw("SUM(CASE WHEN payment_method = 'QRIS' THEN total_price ELSE 0 END) as qris_sales"),
                DB::raw("SUM(CASE WHEN payment_method = 'Transfer' THEN total_price ELSE 0 END) as transfer_sales")
            )
            ->groupBy('date_val')
            ->orderBy('date_val', 'desc')
            ->get();

            foreach ($data as $row) {
                $salesData->push((object)[
                    'period_date' => Carbon::parse($row->date_val)->translatedFormat('d F Y'),
                    'total_transactions' => $row->total_transactions,
                    'cash_sales' => $row->cash_sales,
                    'qris_sales' => $row->qris_sales,
                    'transfer_sales' => $row->transfer_sales,
                    'total_sales' => $row->total_sales,
                ]);
                array_unshift($chartLabels, Carbon::parse($row->date_val)->format('d/m'));
                array_unshift($chartValues, (float) $row->total_sales);
            }
        } elseif ($period === 'monthly') {
            $data = (clone $query)->select(
                DB::raw('YEAR(created_at) as year_val'),
                DB::raw('MONTH(created_at) as month_val'),
                DB::raw('COUNT(id) as total_transactions'),
                DB::raw('SUM(total_price) as total_sales'),
                DB::raw("SUM(CASE WHEN payment_method = 'Cash' THEN total_price ELSE 0 END) as cash_sales"),
                DB::raw("SUM(CASE WHEN payment_method = 'QRIS' THEN total_price ELSE 0 END) as qris_sales"),
                DB::raw("SUM(CASE WHEN payment_method = 'Transfer' THEN total_price ELSE 0 END) as transfer_sales")
            )
            ->groupBy('year_val', 'month_val')
            ->orderBy('year_val', 'desc')
            ->orderBy('month_val', 'desc')
            ->get();

            foreach ($data as $row) {
                $monthName = Carbon::createFromDate($row->year_val, $row->month_val, 1)->translatedFormat('F Y');

                $salesData->push((object)[
                    'period_date' => $monthName,
                    'total_transactions' => $row->total_transactions,
                    'cash_sales' => $row->cash_sales,
                    'qris_sales' => $row->qris_sales,
                    'transfer_sales' => $row->transfer_sales,
                    'total_sales' => $row->total_sales,
                ]);
                array_unshift($chartLabels, Carbon::createFromDate($row->year_val, $row->month_val, 1)->format('M Y'));
                array_unshift($chartValues, (float) $row->total_sales);
            }
        } elseif ($period === 'yearly') {
            $data = (clone $query)->select(
                DB::raw('YEAR(created_at) as year_val'),
                DB::raw('COUNT(id) as total_transactions'),
                DB::raw('SUM(total_price) as total_sales'),
                DB::raw("SUM(CASE WHEN payment_method = 'Cash' THEN total_price ELSE 0 END) as cash_sales"),
                DB::raw("SUM(CASE WHEN payment_method = 'QRIS' THEN total_price ELSE 0 END) as qris_sales"),
                DB::raw("SUM(CASE WHEN payment_method = 'Transfer' THEN total_price ELSE 0 END) as transfer_sales")
            )
            ->groupBy('year_val')
            ->orderBy('year_val', 'desc')
            ->get();

            foreach ($data as $row) {
                $salesData->push((object)[
                    'period_date' => 'Tahun ' . $row->year_val,
                    'total_transactions' => $row->total_transactions,
                    'cash_sales' => $row->cash_sales,
                    'qris_sales' => $row->qris_sales,
                    'transfer_sales' => $row->transfer_sales,
                    'total_sales' => $row->total_sales,
                ]);
                array_unshift($chartLabels, 'Tahun ' . $row->year_val);
                array_unshift($chartValues, (float) $row->total_sales);
            }
        }

        $chartData = (object)[
            'labels' => $chartLabels,
            'values' => $chartValues
        ];

        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();

        return view('reports.sales', compact('salesData', 'chartData', 'period', 'branches'));
    }
}
