<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class FinanceDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Base queries
        $cashQuery = CashTransaction::query();
        $posQuery = Transaction::query();

        // Branch scoping
        if ($user->role !== 'owner') {
            $cashQuery->where('branch_id', $user->branch_id);
            $posQuery->where('branch_id', $user->branch_id);
        }

        // Stats
        $totalKasMasuk = (clone $cashQuery)
            ->where('tipe', 'masuk')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('jumlah');

        $totalKasKeluar = (clone $cashQuery)
            ->where('tipe', 'keluar')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('jumlah');

        $saldoKas = (clone $cashQuery)->where('tipe', 'masuk')->sum('jumlah') - 
                    (clone $cashQuery)->where('tipe', 'keluar')->sum('jumlah');

        $jumlahTransaksi = (clone $posQuery)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $totalPenjualan = (clone $posQuery)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_price');

        $recentTransactions = (clone $cashQuery)
            ->with(['account', 'branch'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('finance-dashboard', compact(
            'totalKasMasuk', 
            'totalKasKeluar', 
            'saldoKas', 
            'jumlahTransaksi', 
            'totalPenjualan', 
            'recentTransactions'
        ));
    }
}
