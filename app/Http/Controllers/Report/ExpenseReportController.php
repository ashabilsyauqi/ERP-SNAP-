<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CashTransaction;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class ExpenseReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = CashTransaction::where('tipe', 'keluar')
            ->whereHas('account', function($q) {
                $q->where('tipe', 'beban')
                  ->where('kode_akun', '!=', '6-1000')
                  ->where('kode_akun', '!=', '5-1000');
            })->with('account');

        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();

        if (!$isOwnerOrSuper) {
            $branchId = $user->branch_id;
        } else {
            if ($request->has('branch_id')) {
                $branchId = $request->input('branch_id');
                session(['selected_branch_id' => $branchId]);
            } else {
                $branchId = session('selected_branch_id', 'all');
            }
        }

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $month = (int) $request->input('month', \Carbon\Carbon::now()->month);
        $year = (int) $request->input('year', \Carbon\Carbon::now()->year);
        $timeframe = $request->input('timeframe', 'month');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        } elseif ($timeframe === 'year' || $timeframe === '1Y') {
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, 12, 31)->endOfMonth()->toDateString();
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        } elseif ($timeframe === 'all') {
            $startDate = null;
            $endDate = null;
        } else {
            // Default: current calendar month (startOfMonth s/d endOfMonth)
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        $allExpenses = $query->get();
        $totalExpenses = $allExpenses->sum('jumlah');
        
        $grouped = $allExpenses->groupBy('account_id');
        $expenses = collect();
        $chartLabels = [];
        $chartValues = [];

        foreach ($grouped as $accountId => $trans) {
            $first = $trans->first();
            $accountName = $first->account ? $first->account->nama_akun : 'Beban Operasional';
            $accountCode = $first->account ? $first->account->kode_akun : '';
            $totalAmount = $trans->sum('jumlah');
            $count = $trans->count();
            $percentage = $totalExpenses > 0 ? ($totalAmount / $totalExpenses) * 100 : 0;

            $expenses->push((object)[
                'nama_akun' => $accountName,
                'kode_akun' => $accountCode,
                'total_amount' => $totalAmount,
                'total_count' => $count,
                'percentage' => round($percentage, 1)
            ]);
        }

        // Sort by highest expense
        $expenses = $expenses->sortByDesc('total_amount')->values();

        foreach ($expenses as $data) {
            $chartLabels[] = $data->nama_akun;
            $chartValues[] = $data->total_amount;
        }

        $chartData = (object)[
            'labels' => $chartLabels,
            'values' => $chartValues
        ];

        $branches = Branch::orderBy('nama_cabang')->get();

        return view('reports.expenses', compact('expenses', 'chartData', 'totalExpenses', 'branches', 'branchId', 'startDate', 'endDate', 'month', 'year', 'timeframe'));
    }
}
