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
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $salesData = collect();
        $chartLabels = [];
        $chartValues = [];

        if ($period === 'daily') {
            $data = $query->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(id) as jumlah_transaksi'),
                DB::raw('SUM(total_price) as total_penjualan')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

            foreach ($data as $row) {
                $salesData->push((object)[
                    'label' => Carbon::parse($row->date)->translatedFormat('d F Y'),
                    'jumlah_transaksi' => $row->jumlah_transaksi,
                    'total_penjualan' => $row->total_penjualan,
                ]);
                array_unshift($chartLabels, Carbon::parse($row->date)->format('d/m'));
                array_unshift($chartValues, $row->total_penjualan);
            }
        } elseif ($period === 'monthly') {
            $data = $query->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(id) as jumlah_transaksi'),
                DB::raw('SUM(total_price) as total_penjualan')
            )
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

            foreach ($data as $row) {
                $monthName = Carbon::createFromDate($row->year, $row->month, 1)->translatedFormat('F Y');
                $daysInMonth = Carbon::createFromDate($row->year, $row->month, 1)->daysInMonth;
                $rataHarian = $row->total_penjualan / $daysInMonth;

                $salesData->push((object)[
                    'label' => $monthName,
                    'jumlah_transaksi' => $row->jumlah_transaksi,
                    'total_penjualan' => $row->total_penjualan,
                    'rata_rata_harian' => $rataHarian
                ]);
                array_unshift($chartLabels, Carbon::createFromDate($row->year, $row->month, 1)->format('M Y'));
                array_unshift($chartValues, $row->total_penjualan);
            }
        } elseif ($period === 'yearly') {
            $data = $query->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('COUNT(id) as jumlah_transaksi'),
                DB::raw('SUM(total_price) as total_penjualan')
            )
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();

            foreach ($data as $row) {
                $salesData->push((object)[
                    'label' => 'Tahun ' . $row->year,
                    'jumlah_transaksi' => $row->jumlah_transaksi,
                    'total_penjualan' => $row->total_penjualan,
                ]);
                array_unshift($chartLabels, $row->year);
                array_unshift($chartValues, $row->total_penjualan);
            }
        }

        $chartData = (object)[
            'labels' => $chartLabels,
            'values' => $chartValues
        ];

        $branches = Branch::all();

        return view('reports.sales', compact('salesData', 'chartData', 'period', 'branches'));
    }
}
