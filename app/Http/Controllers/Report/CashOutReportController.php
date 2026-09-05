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
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();
        
        if (!$isOwnerOrSuper) {
            // Store Managers and staff are strictly restricted to their own branch
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
            ->where('tipe', 'keluar')
            ->whereDoesntHave('account', function($q) {
                $q->where('kode_akun', '6-1000');
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        // Date filter handling
        $isAllTime = $request->boolean('all_time') || $request->input('period') === 'all';
        $isExplicitFilterAction = $request->has('filter');
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $timeframe = $request->input('timeframe', 'month');

        $startDate = null;
        $endDate = null;

        if ($isAllTime) {
            // No date bounds applied
            $startDate = null;
            $endDate = null;
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        } elseif ($request->filled('start_date')) {
            $startDate = $request->input('start_date');
            $query->where('tanggal', '>=', $startDate);
        } elseif ($request->filled('end_date')) {
            $endDate = $request->input('end_date');
            $query->where('tanggal', '<=', $endDate);
        } elseif ($timeframe === 'year' || $timeframe === '1Y') {
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, 12, 31)->endOfMonth()->toDateString();
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        } elseif ($isExplicitFilterAction) {
            // User intentionally cleared dates and clicked filter -> show all
            $startDate = null;
            $endDate = null;
        } else {
            // Default: calendar month (startOfMonth to endOfMonth)
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        // Account filter
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Keyword search (nomor_referensi or keterangan)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_referensi', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        $totalKeluar = (clone $query)->sum('jumlah');
        $cashTransactions = $query->paginate(50)->withQueryString();

        $accounts = Account::where('tipe', 'beban')->active()->orderBy('nama_akun')->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        return view('reports.cash-out', compact(
            'cashTransactions', 
            'accounts', 
            'branches', 
            'totalKeluar', 
            'startDate', 
            'endDate', 
            'branchId',
            'isAllTime',
            'month',
            'year',
            'timeframe'
        ));
    }
}
