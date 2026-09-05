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

        $query = CashTransaction::with(['account', 'branch', 'transaction.transactionDetails.material', 'transaction.user'])
            ->where('tipe', 'masuk')
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $timeframe = $request->input('timeframe', 'month');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        } elseif ($request->filled('start_date')) {
            $startDate = $request->start_date;
            $endDate = null;
            $query->where('tanggal', '>=', $startDate);
        } elseif ($request->filled('end_date')) {
            $startDate = null;
            $endDate = $request->end_date;
            $query->where('tanggal', '<=', $endDate);
        } elseif ($timeframe === 'year' || $timeframe === '1Y') {
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, 12, 31)->endOfMonth()->toDateString();
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        } elseif ($timeframe === 'all') {
            $startDate = null;
            $endDate = null;
        } else {
            // Default calendar month (startOfMonth to endOfMonth)
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $totalMasuk = (clone $query)->sum('jumlah');
        $cashTransactions = $query->paginate(50)->withQueryString();

        $accounts = Account::whereIn('tipe', ['pendapatan', 'aset'])->active()->orderBy('nama_akun')->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        return view('reports.cash-in', compact('cashTransactions', 'accounts', 'branches', 'totalMasuk', 'startDate', 'endDate', 'branchId', 'month', 'year', 'timeframe'));
    }
}
