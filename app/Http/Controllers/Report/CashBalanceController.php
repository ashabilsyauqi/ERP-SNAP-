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
        $query = CashTransaction::query();

        if ($user->role !== 'owner') {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        $totalMasuk = (clone $query)->where('tipe', 'masuk')->sum('jumlah');
        $totalKeluar = (clone $query)->where('tipe', 'keluar')->sum('jumlah');
        $saldo = $totalMasuk - $totalKeluar;

        // Fetch Accounts with their filtered CashTransactions
        $accountQuery = Account::active()->orderBy('kode_akun', 'asc');
        $accounts = $accountQuery->get()->map(function ($acc) use ($request, $user) {
            $tQuery = CashTransaction::where('account_id', $acc->id);
            if ($user->role !== 'owner') {
                $tQuery->where('branch_id', $user->branch_id);
            } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $tQuery->where('branch_id', $request->branch_id);
            }
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $tQuery->whereBetween('tanggal', [$request->start_date, $request->end_date]);
            }

            $acc->inflow = (clone $tQuery)->where('tipe', 'masuk')->sum('jumlah');
            $acc->outflow = (clone $tQuery)->where('tipe', 'keluar')->sum('jumlah');
            $acc->balance = $acc->inflow - $acc->outflow;
            return $acc;
        });

        // Branch Breakdown
        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();
        $perBranch = [];

        foreach ($branches as $branch) {
            if ($user->role !== 'owner' && $user->branch_id !== $branch->id) {
                continue;
            }

            $bQuery = CashTransaction::where('branch_id', $branch->id);
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $bQuery->whereBetween('tanggal', [$request->start_date, $request->end_date]);
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

        return view('reports.cash-balance', compact('totalMasuk', 'totalKeluar', 'saldo', 'accounts', 'perBranch', 'branches'));
    }
}
