<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\Branch;
use App\Models\CashTransaction;
use App\Models\TransactionPayment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OwnerController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $timeframe = $request->input('timeframe', $request->input('period', 'month')); // 'today', '7days', 'month', 'year', 'all'
        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);

        // Branch Selection (supports request parameter & session persistence)
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

        $query = Transaction::query()->whereNotIn('order_status', ['draft', 'cancelled']);
        $materialQuery = Material::query();
        $purchaseQuery = Purchase::query();
        $opexQuery = CashTransaction::keluar()->whereHas('account', function($q) {
            $q->where('tipe', 'beban')->where('kode_akun', '!=', '6-1000');
        });

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
            $materialQuery->where('branch_id', $branchId);
            $purchaseQuery->where('branch_id', $branchId);
            $opexQuery->where('branch_id', $branchId);
        }

        // Apply Timeframe Constraints
        if ($timeframe === 'today' || $timeframe === '1D') {
            $query->whereDate('created_at', Carbon::today());
            $opexQuery->whereDate('tanggal', Carbon::today());
        } elseif ($timeframe === '7days' || $timeframe === '7D') {
            $query->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay());
            $opexQuery->where('tanggal', '>=', Carbon::now()->subDays(6)->startOfDay());
        } elseif ($timeframe === 'year' || $timeframe === '1Y') {
            $query->whereYear('created_at', $year);
            $opexQuery->whereYear('tanggal', $year);
        } elseif ($timeframe === 'month' || $timeframe === '1M') {
            $query->whereMonth('created_at', $month)->whereYear('created_at', $year);
            $opexQuery->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
        } // 'all' has no constraints

        $totalSales = (clone $query)->sum('total_price');
        $totalHpp = (clone $query)->sum('total_hpp');
        $grossProfit = $totalSales - $totalHpp;
        $totalOpex = (clone $opexQuery)->sum('jumlah');
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

        // Payment Breakdown (including split payments)
        $directCash = (clone $query)->whereIn('payment_method', ['Cash', 'cash'])->sum('paid_amount');
        $directQris = (clone $query)->whereIn('payment_method', ['QRIS', 'qris'])->sum('paid_amount');
        $directTransfer = (clone $query)->whereIn('payment_method', ['Transfer', 'transfer'])->sum('paid_amount');

        $splitTxIds = (clone $query)->where('payment_method', 'like', 'Split%')->pluck('id');
        $splitPaymentsQuery = TransactionPayment::whereIn('transaction_id', $splitTxIds);
        $splitCash = (clone $splitPaymentsQuery)->whereIn('payment_method', ['Cash', 'cash'])->sum('amount');
        $splitQris = (clone $splitPaymentsQuery)->whereIn('payment_method', ['QRIS', 'qris'])->sum('amount');
        $splitTransfer = (clone $splitPaymentsQuery)->whereIn('payment_method', ['Transfer', 'transfer'])->sum('amount');

        $cashSales = (float) $directCash + (float) $splitCash;
        $qrisSales = (float) $directQris + (float) $splitQris;
        $transferSales = (float) $directTransfer + (float) $splitTransfer;

        // Branch Sales Comparison
        $branchSalesData = Branch::all()->map(function ($branch) use ($timeframe, $month, $year) {
            $bQuery = Transaction::where('branch_id', $branch->id)->whereNotIn('order_status', ['draft', 'cancelled']);
            if ($timeframe === 'today' || $timeframe === '1D') {
                $bQuery->whereDate('created_at', Carbon::today());
            } elseif ($timeframe === '7days' || $timeframe === '7D') {
                $bQuery->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay());
            } elseif ($timeframe === 'month' || $timeframe === '1M') {
                $bQuery->whereMonth('created_at', $month)->whereYear('created_at', $year);
            } elseif ($timeframe === 'year' || $timeframe === '1Y') {
                $bQuery->whereYear('created_at', $year);
            }
            $sales = $bQuery->sum('total_price');
            return [
                'name' => $branch->nama_cabang,
                'sales' => $sales,
            ];
        });

        // Interactive Trading Chart Data (Dynamic Labels & Series based on timeframe)
        $chartLabels = [];
        $chartSales = [];
        $chartVolume = [];
        $chartNet = [];

        if ($timeframe === 'today' || $timeframe === '1D') {
            for ($h = 8; $h <= 22; $h++) {
                $timeSlot = sprintf('%02d:00', $h);
                $chartLabels[] = $timeSlot;

                $startTime = Carbon::today()->setTime($h, 0, 0);
                $endTime = Carbon::today()->setTime($h, 59, 59);

                $hTrx = Transaction::whereBetween('created_at', [$startTime, $endTime])
                    ->whereNotIn('order_status', ['draft', 'cancelled']);

                if ($branchId && $branchId !== 'all') {
                    $hTrx->where('branch_id', $branchId);
                }

                $salesVal = (float) $hTrx->sum('total_price');
                $volVal = (int) $hTrx->count();
                $chartSales[] = $salesVal;
                $chartVolume[] = $volVal;
                $chartNet[] = $salesVal;
            }
        } elseif ($timeframe === '7days' || $timeframe === '7D') {
            for ($d = 6; $d >= 0; $d--) {
                $targetDate = Carbon::today()->subDays($d);
                $chartLabels[] = $targetDate->format('d M');

                $dTrx = Transaction::whereDate('created_at', $targetDate)
                    ->whereNotIn('order_status', ['draft', 'cancelled']);
                if ($branchId && $branchId !== 'all') {
                    $dTrx->where('branch_id', $branchId);
                }

                $salesVal = (float) $dTrx->sum('total_price');
                $volVal = (int) $dTrx->count();
                $chartSales[] = $salesVal;
                $chartVolume[] = $volVal;
                $chartNet[] = $salesVal;
            }
        } elseif ($timeframe === 'year' || $timeframe === '1Y') {
            for ($m = 1; $m <= 12; $m++) {
                $chartLabels[] = Carbon::create($year, $m, 1)->translatedFormat('M');

                $mTrx = Transaction::whereYear('created_at', $year)
                    ->whereMonth('created_at', $m)
                    ->whereNotIn('order_status', ['draft', 'cancelled']);
                if ($branchId && $branchId !== 'all') {
                    $mTrx->where('branch_id', $branchId);
                }

                $salesVal = (float) $mTrx->sum('total_price');
                $volVal = (int) $mTrx->count();
                $chartSales[] = $salesVal;
                $chartVolume[] = $volVal;
                $chartNet[] = $salesVal;
            }
        } else {
            // Default: Month (30/31 days or past 6 months overview)
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $chartLabels[] = $date->translatedFormat('F Y');

                $mSalesQuery = Transaction::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->whereNotIn('order_status', ['draft', 'cancelled']);
                $mOpexQuery = CashTransaction::keluar()
                    ->whereYear('tanggal', $date->year)
                    ->whereMonth('tanggal', $date->month);

                if ($branchId && $branchId !== 'all') {
                    $mSalesQuery->where('branch_id', $branchId);
                    $mOpexQuery->where('branch_id', $branchId);
                }

                $mSales = (float) $mSalesQuery->sum('total_price');
                $mHpp = (float) $mSalesQuery->sum('total_hpp');
                $mOpex = (float) $mOpexQuery->whereHas('account', function($q) {
                    $q->where('tipe', 'beban')->where('kode_akun', '!=', '6-1000');
                })->sum('jumlah');

                $chartSales[] = $mSales;
                $chartVolume[] = (int) $mSalesQuery->count();
                $chartNet[] = $mSales - $mHpp - $mOpex;
            }
        }

        $highestSales = !empty($chartSales) ? max($chartSales) : 0;
        $lowestSales = !empty($chartSales) ? min($chartSales) : 0;
        $avgSales = !empty($chartSales) ? round(array_sum($chartSales) / count($chartSales)) : 0;

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
            'chartLabels',
            'chartSales',
            'chartVolume',
            'chartNet',
            'highestSales',
            'lowestSales',
            'avgSales',
            'branches',
            'branchId',
            'timeframe',
            'month',
            'year'
        ));
    }
}
