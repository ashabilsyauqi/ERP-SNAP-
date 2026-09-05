<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CashTransaction;
use App\Models\Account;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class CashBalanceController extends Controller
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

        $query = CashTransaction::query();
        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $timeframe = $request->input('timeframe', 'month');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
        } elseif ($timeframe === 'year' || $timeframe === '1Y') {
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, 12, 31)->endOfMonth()->toDateString();
        } elseif ($timeframe === 'all') {
            $startDate = null;
            $endDate = null;
        } else {
            // Calendar month
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
        }

        if ($startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        $totalMasuk = (clone $query)->where('tipe', 'masuk')->sum('jumlah');
        $totalKeluar = (clone $query)->where('tipe', 'keluar')->sum('jumlah');
        $saldo = $totalMasuk - $totalKeluar;

        // Fetch Accounts with their filtered CashTransactions
        $accountQuery = Account::active()->orderBy('kode_akun', 'asc');
        $accounts = $accountQuery->get()->map(function ($acc) use ($request, $user, $branchId, $startDate, $endDate) {
            $tQuery = CashTransaction::where('account_id', $acc->id);
            if ($branchId && $branchId !== 'all') {
                $tQuery->where('branch_id', $branchId);
            }
            if ($startDate && $endDate) {
                $tQuery->whereBetween('tanggal', [$startDate, $endDate]);
            }

            $acc->inflow = (clone $tQuery)->where('tipe', 'masuk')->sum('jumlah');
            $acc->outflow = (clone $tQuery)->where('tipe', 'keluar')->sum('jumlah');
            $acc->balance = $acc->inflow - $acc->outflow;
            return $acc;
        });

        // Branch Breakdown
        $branches = Branch::orderBy('nama_cabang')->get();
        $perBranch = [];

        foreach ($branches as $branch) {
            if (!$user->isOwner() && $user->branch_id !== $branch->id) {
                continue;
            }

            $bQuery = CashTransaction::where('branch_id', $branch->id);
            if ($startDate && $endDate) {
                $bQuery->whereBetween('tanggal', [$startDate, $endDate]);
            }

            $bMasuk = (clone $bQuery)->where('tipe', 'masuk')->sum('jumlah');
            $bKeluar = (clone $bQuery)->where('tipe', 'keluar')->sum('jumlah');

            $perBranch[] = (object) [
                'nama_cabang' => $branch->nama_cabang,
                'masuk' => $bMasuk,
                'keluar' => $bKeluar,
                'saldo' => $bMasuk - $bKeluar
            ];
        }

        return view('reports.cash-balance', compact('totalMasuk', 'totalKeluar', 'saldo', 'accounts', 'perBranch', 'branches', 'branchId', 'startDate', 'endDate', 'month', 'year', 'timeframe'));
    }
}
