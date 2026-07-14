<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CashTransaction;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $periodType = $request->input('period_type', 'monthly'); // monthly or yearly
        
        $query = CashTransaction::with('account');

        if ($user->role !== 'owner') {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $periodLabel = '';
        if ($periodType === 'monthly') {
            $month = $request->input('month', Carbon::now()->month);
            $year = $request->input('year', Carbon::now()->year);
            $query->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
            $periodLabel = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        } else {
            $year = $request->input('year', Carbon::now()->year);
            $query->whereYear('tanggal', $year);
            $periodLabel = 'Tahun ' . $year;
        }

        $transactions = $query->get();

        // 1. PENDAPATAN
        $pendapatan = collect();
        $totalPendapatan = 0;
        
        $pendapatanTrx = $transactions->where('tipe', 'masuk')
            ->filter(function($t) { return $t->account->tipe === 'pendapatan'; })
            ->groupBy('account.nama_akun');
            
        foreach ($pendapatanTrx as $akun => $trx) {
            $sum = $trx->sum('jumlah');
            $pendapatan->push((object)['nama_akun' => $akun, 'jumlah' => $sum]);
            $totalPendapatan += $sum;
        }

        // 2. HPP (Harga Pokok Penjualan)
        $hpp = collect();
        $totalHpp = 0;
        
        $hppTrx = $transactions->where('tipe', 'keluar')
            ->filter(function($t) { return $t->account->kode_akun === '6-1000'; })
            ->groupBy('account.nama_akun');
            
        foreach ($hppTrx as $akun => $trx) {
            $sum = $trx->sum('jumlah');
            $hpp->push((object)['nama_akun' => $akun, 'jumlah' => $sum]);
            $totalHpp += $sum;
        }

        $labaKotor = $totalPendapatan - $totalHpp;

        // 3. BEBAN OPERASIONAL
        $bebanOperasional = collect();
        $totalBebanOperasional = 0;
        
        $bebanTrx = $transactions->where('tipe', 'keluar')
            ->filter(function($t) { return $t->account->tipe === 'beban' && $t->account->kode_akun !== '6-1000'; })
            ->groupBy('account.nama_akun');
            
        foreach ($bebanTrx as $akun => $trx) {
            $sum = $trx->sum('jumlah');
            $bebanOperasional->push((object)['nama_akun' => $akun, 'jumlah' => $sum]);
            $totalBebanOperasional += $sum;
        }

        $labaBersih = $labaKotor - $totalBebanOperasional;

        $branches = Branch::all();

        return view('reports.profit-loss', compact(
            'pendapatan', 'totalPendapatan',
            'hpp', 'totalHpp',
            'labaKotor',
            'bebanOperasional', 'totalBebanOperasional',
            'labaBersih',
            'periodType', 'periodLabel', 'branches'
        ));
    }
}
