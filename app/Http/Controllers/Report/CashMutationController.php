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
        
        $query = CashTransaction::with(['account', 'branch', 'transaction.transactionDetails.material', 'transaction.user'])
            ->orderBy('tanggal', 'asc')
            ->orderBy('created_at', 'asc');

        if ($user->role !== 'owner') {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('tipe') && $request->tipe !== 'Semua') {
            $query->where('tipe', strtolower($request->tipe));
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Hitung saldo awal sebelum start_date
        $saldoAwal = 0;
        if ($request->filled('start_date')) {
            $prevQuery = CashTransaction::where('tanggal', '<', $request->start_date);
            if ($user->role !== 'owner') {
                $prevQuery->where('branch_id', $user->branch_id);
            } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $prevQuery->where('branch_id', $request->branch_id);
            }
            if ($request->filled('account_id')) {
                $prevQuery->where('account_id', $request->account_id);
            }
            
            $masukAwal = (clone $prevQuery)->where('tipe', 'masuk')->sum('jumlah');
            $keluarAwal = (clone $prevQuery)->where('tipe', 'keluar')->sum('jumlah');
            $saldoAwal = $masukAwal - $keluarAwal;
        }

        $allMutations = $query->get();
        
        $mutasi = collect();
        $runningBalance = $saldoAwal;
        $totalMasuk = 0;
        $totalKeluar = 0;

        foreach ($allMutations as $mut) {
            if ($mut->tipe === 'masuk') {
                $runningBalance += $mut->jumlah;
                $totalMasuk += $mut->jumlah;
            } else {
                $runningBalance -= $mut->jumlah;
                $totalKeluar += $mut->jumlah;
            }
            
            $mut->running_balance = $runningBalance;
            $mutasi->push($mut);
        }

        $accounts = Account::active()->orderBy('nama_akun')->get();
        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();

        return view('reports.cash-mutation', [
            'mutasi' => $mutasi,
            'accounts' => $accounts,
            'branches' => $branches,
            'saldoAwal' => $saldoAwal,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
            'saldoAkhir' => $runningBalance
        ]);
    }
}
