<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CashTransaction;
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
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        $totalMasuk = (clone $query)->where('tipe', 'masuk')->sum('jumlah');
        $totalKeluar = (clone $query)->where('tipe', 'keluar')->sum('jumlah');
        $saldo = $totalMasuk - $totalKeluar;

        $branches = Branch::withTrashed()->get();
        $perBranch = [];

        foreach ($branches as $branch) {
            if ($user->role !== 'owner' && $user->branch_id !== $branch->id) {
                continue;
            }

            $bQuery = (clone $query)->where('branch_id', $branch->id);
            $bMasuk = (clone $bQuery)->where('tipe', 'masuk')->sum('jumlah');
            $bKeluar = (clone $bQuery)->where('tipe', 'keluar')->sum('jumlah');

            $perBranch[] = (object) [
                'nama_cabang' => $branch->nama_cabang,
                'masuk' => $bMasuk,
                'keluar' => $bKeluar,
                'saldo' => $bMasuk - $bKeluar
            ];
        }

        return view('reports.cash-balance', compact('totalMasuk', 'totalKeluar', 'saldo', 'perBranch'));
    }
}
