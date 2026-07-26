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
                $q->where('tipe', 'beban');
            })->with('account');

        if ($user->role !== 'owner') {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        $expenses = $query->get();
        
        $grouped = $expenses->groupBy('account_id');
        $totalExpenses = $expenses->sum('jumlah');
        
        $expenseData = collect();
        $chartLabels = [];
        $chartValues = [];

        foreach ($grouped as $accountId => $trans) {
            $accountName = $trans->first()->account->nama_akun;
            $total = $trans->sum('jumlah');
            $count = $trans->count();
            $percentage = $totalExpenses > 0 ? ($total / $totalExpenses) * 100 : 0;

            $expenseData->push((object)[
                'nama_akun' => $accountName,
                'total' => $total,
                'count' => $count,
                'percentage' => round($percentage, 2)
            ]);
        }

        // Sort by highest expense
        $expenseData = $expenseData->sortByDesc('total')->values();

        foreach ($expenseData as $data) {
            $chartLabels[] = $data->nama_akun;
            $chartValues[] = $data->total;
        }

        $chartData = (object)[
            'labels' => $chartLabels,
            'values' => $chartValues
        ];

        $branches = Branch::withTrashed()->get();

        return view('reports.expenses', compact('expenseData', 'chartData', 'totalExpenses', 'branches'));
    }
}
