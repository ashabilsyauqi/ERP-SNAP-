<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class FinanceDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

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

        // Base queries
        $cashQuery = CashTransaction::query();
        $posQuery = Transaction::query()->whereNotIn('order_status', ['draft', 'cancelled']);

        // Branch scoping
        if ($branchId && $branchId !== 'all') {
            $cashQuery->where('branch_id', $branchId);
            $posQuery->where('branch_id', $branchId);
        }

        // Stats
        $totalKasMasuk = (clone $cashQuery)
            ->where('tipe', 'masuk')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('jumlah');

        $totalKasKeluar = (clone $cashQuery)
            ->where('tipe', 'keluar')
            ->whereDoesntHave('account', function($q) {
                $q->where('kode_akun', '6-1000'); // Exclude HPP accounting adjustments from actual cash outflow
            })
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('jumlah');

        $saldoKas = (clone $cashQuery)->where('tipe', 'masuk')->sum('jumlah') - 
                    (clone $cashQuery)->where('tipe', 'keluar')
                        ->whereDoesntHave('account', function($q) {
                            $q->where('kode_akun', '6-1000');
                        })->sum('jumlah');

        $jumlahTransaksi = (clone $posQuery)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $totalPenjualan = (clone $posQuery)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_price');

        $recentTransactions = (clone $cashQuery)
            ->with(['account', 'branch', 'transaction.transactionDetails.material', 'transaction.user'])
            ->whereDoesntHave('account', function($q) {
                $q->where('kode_akun', '6-1000');
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        $branches = \App\Models\Branch::orderBy('nama_cabang')->get();

        return view('finance-dashboard', compact(
            'totalKasMasuk', 
            'totalKasKeluar', 
            'saldoKas', 
            'jumlahTransaksi', 
            'totalPenjualan', 
            'recentTransactions',
            'branches',
            'branchId'
        ));
    }
}
