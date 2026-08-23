<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CashTransaction;
use App\Models\Account;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class CashOutReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = CashTransaction::with(['account', 'branch', 'transaction.transactionDetails.material', 'transaction.user'])
            ->where('tipe', 'keluar')
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        if ($user->role !== 'owner') {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $cashTransactions = $query->get();
        $totalKeluar = $cashTransactions->sum('jumlah');

        $accounts = Account::where('tipe', 'beban')->active()->orderBy('nama_akun')->get();
        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();

        return view('reports.cash-out', compact('cashTransactions', 'accounts', 'branches', 'totalKeluar'));
    }
}
