<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CashTransaction;
use App\Models\Account;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class CashInReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = CashTransaction::with(['account', 'branch', 'transaction.transactionDetails.material', 'transaction.user'])
            ->where('tipe', 'masuk')
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        if ($user->role !== 'owner') {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('tanggal', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('tanggal', '<=', $request->end_date);
        } else {
            // Default load current month to prevent memory overload with 3 years of data
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $totalMasuk = (clone $query)->sum('jumlah');
        $cashTransactions = $query->paginate(50)->withQueryString();

        $accounts = Account::whereIn('tipe', ['pendapatan', 'aset'])->active()->orderBy('nama_akun')->get();
        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();

        return view('reports.cash-in', compact('cashTransactions', 'accounts', 'branches', 'totalMasuk', 'startDate', 'endDate'));
    }
}
