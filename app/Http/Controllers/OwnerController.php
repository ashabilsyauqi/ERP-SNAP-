<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class OwnerController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $branchId = $request->input('branch_id', 'all');

        $query = Transaction::query();
        $materialQuery = Material::query();
        $purchaseQuery = Purchase::query();
        $opexQuery = \App\Models\CashTransaction::keluar();

        if ($user->isManager()) {
            $branchId = $user->branch_id;
        }

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
            $materialQuery->where('branch_id', $branchId);
            $purchaseQuery->where('branch_id', $branchId);
            $opexQuery->where('branch_id', $branchId);
        }

        $totalSales = (clone $query)->sum('total_price');
        $totalHpp = (clone $query)->sum('total_hpp');
        $grossProfit = $totalSales - $totalHpp;
        
        $totalOpex = (clone $opexQuery)->whereHas('account', function($q) {
            $q->where('kode_akun', 'like', '5-2%')
              ->orWhere('kode_akun', 'like', '5-3%')
              ->orWhere('kode_akun', 'like', '5-4%')
              ->orWhere('kode_akun', 'like', '5-5%')
              ->orWhere('kode_akun', 'like', '5-9%');
        })->sum('jumlah');

        $netProfit = $grossProfit - $totalOpex;

        $omsetBase = (float) $totalSales;
        $hppPct = $omsetBase > 0 ? round(($totalHpp / $omsetBase) * 100, 1) : 0;
        $grossPct = $omsetBase > 0 ? round(($grossProfit / $omsetBase) * 100, 1) : 0;
        $opexPct = $omsetBase > 0 ? round(($totalOpex / $omsetBase) * 100, 1) : 0;
        $netPct = $omsetBase > 0 ? round(($netProfit / $omsetBase) * 100, 1) : 0;

        $totalTransactionsCount = (clone $query)->count();
        $totalMaterialsCount = (clone $materialQuery)->count();
        $lowStockCount = (clone $materialQuery)->where('stock_qty', '<=', 5)->count();
        $pendingPOCount = (clone $purchaseQuery)->whereIn('status', ['waiting_approval', 'pending_verification'])->count();

        // Payment Method Breakdown
        $cashSales = (clone $query)->whereIn('payment_method', ['Cash', 'cash'])->sum('total_price');
        $qrisSales = (clone $query)->whereIn('payment_method', ['QRIS', 'qris'])->sum('total_price');
        $transferSales = (clone $query)->whereIn('payment_method', ['Transfer', 'transfer'])->sum('total_price');

        // Sales Per Branch Data
        $branchSalesData = Branch::all()->map(function ($branch) {
            $sales = Transaction::where('branch_id', $branch->id)->sum('total_price');
            return [
                'name' => $branch->nama_cabang,
                'sales' => $sales,
            ];
        });

        // 6-Month Trend Data
        $months = [];
        $monthlySales = [];
        $monthlyHpp = [];
        $monthlyOpex = [];
        $monthlyNetProfit = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $year = $date->year;
            $month = $date->month;
            $months[] = $date->translatedFormat('F Y');

            $mSalesQuery = Transaction::whereYear('created_at', $year)->whereMonth('created_at', $month);
            $mOpexQuery = \App\Models\CashTransaction::keluar()->whereYear('tanggal', $year)->whereMonth('tanggal', $month);

            if ($branchId && $branchId !== 'all') {
                $mSalesQuery->where('branch_id', $branchId);
                $mOpexQuery->where('branch_id', $branchId);
            }

            $mSales = $mSalesQuery->sum('total_price');
            $mHpp = $mSalesQuery->sum('total_hpp');
            $mOpex = $mOpexQuery->whereHas('account', function($q) {
                $q->where('kode_akun', 'like', '5-2%')
                  ->orWhere('kode_akun', 'like', '5-3%')
                  ->orWhere('kode_akun', 'like', '5-4%')
                  ->orWhere('kode_akun', 'like', '5-5%')
                  ->orWhere('kode_akun', 'like', '5-9%');
            })->sum('jumlah');

            $monthlySales[] = $mSales;
            $monthlyHpp[] = $mHpp;
            $monthlyOpex[] = $mOpex;
            $monthlyNetProfit[] = $mSales - $mHpp - $mOpex;
        }

        $recentTransactions = (clone $query)->with(['user', 'branch', 'transactionDetails.material'])->orderBy('created_at', 'desc')->take(10)->get();
        $branches = Branch::all();

        return view('owner.dashboard', compact(
            'totalSales',
            'totalHpp',
            'grossProfit',
            'netProfit',
            'totalOpex',
            'hppPct',
            'grossPct',
            'opexPct',
            'netPct',
            'totalTransactionsCount',
            'totalMaterialsCount',
            'lowStockCount',
            'pendingPOCount',
            'cashSales',
            'qrisSales',
            'transferSales',
            'branchSalesData',
            'recentTransactions',
            'months',
            'monthlySales',
            'monthlyHpp',
            'monthlyOpex',
            'monthlyNetProfit',
            'branches',
            'branchId'
        ));
    }
}
