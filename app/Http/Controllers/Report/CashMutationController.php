<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CashTransaction;
use App\Models\Account;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class CashMutationController extends Controller
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
            ->orderBy('tanggal', 'asc')
            ->orderBy('created_at', 'asc');

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

        if ($request->filled('tipe') && $request->tipe !== 'Semua') {
            $query->where('tipe', strtolower($request->tipe));
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Hitung saldo awal sebelum tanggal mulai filter
        $effectiveStartDate = $request->input('start_date', $startDate);
        $prevQuery = CashTransaction::where('tanggal', '<', $effectiveStartDate);
        if ($branchId && $branchId !== 'all') {
            $prevQuery->where('branch_id', $branchId);
        }
        if ($request->filled('account_id')) {
            $prevQuery->where('account_id', $request->account_id);
        }
        
        $masukAwal = (clone $prevQuery)->where('tipe', 'masuk')->sum('jumlah');
        $keluarAwal = (clone $prevQuery)->where('tipe', 'keluar')->sum('jumlah');
        $saldoAwal = $masukAwal - $keluarAwal;

        $totalMasuk = (clone $query)->where('tipe', 'masuk')->sum('jumlah');
        $totalKeluar = (clone $query)->where('tipe', 'keluar')->sum('jumlah');
        $saldoAkhir = $saldoAwal + $totalMasuk - $totalKeluar;

        $mutasi = $query->paginate(50)->withQueryString();
        $runningBalance = $saldoAwal;

        foreach ($mutasi as $mut) {
            if ($mut->tipe === 'masuk') {
                $runningBalance += $mut->jumlah;
            } else {
                $runningBalance -= $mut->jumlah;
            }
            $mut->running_balance = $runningBalance;
        }

        $accounts = Account::active()->orderBy('nama_akun')->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        return view('reports.cash-mutation', [
            'mutasi' => $mutasi,
            'accounts' => $accounts,
            'branches' => $branches,
            'saldoAwal' => $saldoAwal,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
            'saldoAkhir' => $runningBalance,
            'branchId' => $branchId,
            'month' => $month,
            'year' => $year,
            'timeframe' => $timeframe,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
