<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CashTransaction;
use App\Models\Transaction;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $periodType = $request->input('period_type', 'monthly'); // monthly or yearly
        
        $cashQuery = CashTransaction::with('account');
        $salesQuery = Transaction::query()->whereNotIn('order_status', ['draft', 'cancelled']);

        if ($request->has('branch_id')) {
            $branchId = $request->input('branch_id');
            session(['selected_branch_id' => $branchId]);
        } else {
            $branchId = session('selected_branch_id', 'all');
        }

        if (!$user->isOwner()) {
            $branchId = $user->branch_id;
        }

        if ($branchId && $branchId !== 'all') {
            $cashQuery->where('branch_id', $branchId);
            $salesQuery->where('branch_id', $branchId);
        }

        $periodLabel = '';
        if ($periodType === 'monthly') {
            $month = $request->input('month', Carbon::now()->month);
            $year = $request->input('year', Carbon::now()->year);
            $cashQuery->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
            $salesQuery->whereMonth('created_at', $month)->whereYear('created_at', $year);
            $periodLabel = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        } else {
            $year = $request->input('year', Carbon::now()->year);
            $cashQuery->whereYear('tanggal', $year);
            $salesQuery->whereYear('created_at', $year);
            $periodLabel = 'Tahun ' . $year;
        }

        $cashTransactions = $cashQuery->get();
        $totalSalesFromTrx = (clone $salesQuery)->sum('total_price');
        $totalHppFromTrx = (clone $salesQuery)->sum('total_hpp');

        // 1. PENDAPATAN USAHA (REVENUE)
        $pendapatan = collect();
        $totalPendapatan = 0;
        
        if ($totalSalesFromTrx > 0) {
            $pendapatan->push((object)[
                'nama_akun' => 'Pendapatan Penjualan Kasir POS',
                'jumlah' => $totalSalesFromTrx
            ]);
            $totalPendapatan += $totalSalesFromTrx;
        }

        // Tambahan Pendapatan Lain-lain dari Jurnal Kas Masuk Non-POS (Hanya Akun Pendapatan Selain 4-1000)
        $otherIncome = $cashTransactions->where('tipe', 'masuk')
            ->whereNull('transaction_id')
            ->filter(function($t) { 
                return $t->account && $t->account->tipe === 'pendapatan' && $t->account->kode_akun !== '4-1000'; 
            })
            ->groupBy('account.nama_akun');
            
        foreach ($otherIncome as $akun => $trx) {
            $sum = $trx->sum('jumlah');
            $pendapatan->push((object)['nama_akun' => $akun, 'jumlah' => $sum]);
            $totalPendapatan += $sum;
        }

        if ($totalPendapatan == 0) {
            $generalIncome = $cashTransactions->where('tipe', 'masuk')
                ->filter(function($t) { return $t->account && $t->account->tipe === 'pendapatan'; })
                ->sum('jumlah');
            if ($generalIncome > 0) {
                $pendapatan->push((object)['nama_akun' => 'Pendapatan Kas Masuk Usaha', 'jumlah' => $generalIncome]);
                $totalPendapatan += $generalIncome;
            }
        }

        // 2. HPP (HARGA POKOK PENJUALAN / COGS)
        $hpp = collect();
        $totalHpp = 0;
        
        if ($totalHppFromTrx > 0) {
            $hpp->push((object)[
                'nama_akun' => 'Harga Pokok Penjualan (HPP / COGS)',
                'jumlah' => $totalHppFromTrx
            ]);
            $totalHpp += $totalHppFromTrx;
        } else {
            $hppTrx = $cashTransactions->where('tipe', 'keluar')
                ->filter(function($t) { return $t->account && $t->account->kode_akun === '6-1000'; })
                ->groupBy('account.nama_akun');
                
            foreach ($hppTrx as $akun => $trx) {
                $sum = $trx->sum('jumlah');
                $hpp->push((object)['nama_akun' => $akun, 'jumlah' => $sum]);
                $totalHpp += $sum;
            }
        }

        $labaKotor = $totalPendapatan - $totalHpp;

        // 3. BEBAN OPERASIONAL (OPEX)
        $bebanOperasional = collect();
        $totalBebanOperasional = 0;
        
        $bebanTrx = $cashTransactions->where('tipe', 'keluar')
            ->filter(function($t) { 
                return $t->account && $t->account->tipe === 'beban' && $t->account->kode_akun !== '6-1000'; 
            })
            ->groupBy('account.nama_akun');
            
        foreach ($bebanTrx as $akun => $trx) {
            $sum = $trx->sum('jumlah');
            $bebanOperasional->push((object)['nama_akun' => $akun, 'jumlah' => $sum]);
            $totalBebanOperasional += $sum;
        }

        $labaBersih = $labaKotor - $totalBebanOperasional;

        $branches = Branch::orderBy('nama_cabang')->get();

        return view('reports.profit-loss', compact(
            'pendapatan', 'totalPendapatan',
            'hpp', 'totalHpp',
            'labaKotor',
            'bebanOperasional', 'totalBebanOperasional',
            'labaBersih',
            'periodType', 'periodLabel', 'branches', 'branchId'
        ));
    }
}

