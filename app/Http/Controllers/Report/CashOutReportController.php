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
        
        if ($request->has('branch_id')) {
            $branchId = $request->input('branch_id');
            session(['selected_branch_id' => $branchId]);
        } else {
            $branchId = session('selected_branch_id', 'all');
        }

        if (!$user->isOwner()) {
            $branchId = $user->branch_id;
        }

        $query = CashTransaction::with(['account', 'branch', 'transaction.transactionDetails.material', 'transaction.user'])
            ->where('tipe', 'keluar')
            ->whereDoesntHave('account', function($q) {
                $q->where('kode_akun', '6-1000');
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
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
            // Default load current month to prevent memory overload
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $totalKeluar = (clone $query)->sum('jumlah');
        $cashTransactions = $query->paginate(50)->withQueryString();

        $accounts = Account::where('tipe', 'beban')->active()->orderBy('nama_akun')->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        return view('reports.cash-out', compact('cashTransactions', 'accounts', 'branches', 'totalKeluar', 'startDate', 'endDate', 'branchId'));
    }
}
