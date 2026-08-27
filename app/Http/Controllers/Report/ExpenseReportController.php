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

        if ($user->role !== 'owner') {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
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

        return view('reports.expenses', compact('expenses', 'chartData', 'totalExpenses', 'branches'));
    }
}
