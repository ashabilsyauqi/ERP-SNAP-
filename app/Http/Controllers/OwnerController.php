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
        $timeframe = $request->input('timeframe', $request->input('period', 'month')); // 'today', '7days', 'month', 'year', 'all', 'custom'
        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);
        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');

        if ($startDateInput && $endDateInput) {
            $timeframe = 'custom';
        }

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
        $opexQuery = CashTransaction::keluar()->whereDoesntHave('account', function($q) {
            $q->whereIn('kode_akun', ['6-1000', '5-1100']); // Exclude HPP 6-1000 and Click Charge 5-1100 from cash out OPEX
        });
        $clickChargeQuery = CashTransaction::keluar()->whereHas('account', function($q) {
            $q->where('kode_akun', '5-1100'); // Direct Click Charge expenses routed to HPP
        });

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
            $materialQuery->where('branch_id', $branchId);
            $purchaseQuery->where('branch_id', $branchId);
            $branchFilter = function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
                $centralBranch = Branch::where('nama_cabang', 'like', '%Pusat%')->first() ?: Branch::first();
                if ($centralBranch && (int)$branchId === (int)$centralBranch->id) {
                    $q->orWhereNull('branch_id');
                }
            };
            $opexQuery->where($branchFilter);
            $clickChargeQuery->where($branchFilter);
        }

        $startDate = null;
        $endDate = null;

        if ($timeframe === 'custom' && $startDateInput && $endDateInput) {
            $parsedStart = $this->parseFlexibleDate($startDateInput);
            $parsedEnd = $this->parseFlexibleDate($endDateInput);
            $startDate = $parsedStart ? $parsedStart->startOfDay() : null;
            $endDate = $parsedEnd ? $parsedEnd->endOfDay() : null;
        } elseif ($timeframe === 'today' || $timeframe === '1D') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        } elseif ($timeframe === '7days' || $timeframe === '7D') {
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } elseif ($timeframe === 'year' || $timeframe === '1Y') {
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfMonth()->startOfDay();
            $endDate = Carbon::createFromDate($year, 12, 31)->endOfMonth()->endOfDay();
        } elseif ($timeframe === 'month' || $timeframe === '1M') {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->startOfDay();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
        }

        if ($startDate && $endDate) {
            $startDayStr = $startDate->toDateString();
            $endDayStr = $endDate->toDateString();
            $query->whereBetween('created_at', [$startDate, $endDate]);

            // OPEX & Click Charge strictly follow financial transaction date (tanggal) in accordance with accounting standards.
            // This ensures backdated or batch-inputted past expenses (e.g. September payroll entered on Oct 1)
            // correctly stay in their own transaction date and do not inflate daily or monthly metrics.
            $dateRangeFilter = function($q) use ($startDayStr, $endDayStr) {
                $q->whereBetween('tanggal', [$startDayStr . ' 00:00:00', $endDayStr . ' 23:59:59'])
                  ->orWhereDate('tanggal', '>=', $startDayStr)->whereDate('tanggal', '<=', $endDayStr)
                  ->orWhereBetween('tanggal', [$startDayStr, $endDayStr]);
            };
            $opexQuery->where($dateRangeFilter);
            $clickChargeQuery->where($dateRangeFilter);

            // Older transactions settlement cash inflows received during this period (strictly from unique CashTransaction records)
            $allPeriodSettlements = $this->fetchSettlementCashTransactions($startDate, $endDate, null, $startDate);
            $cashSettlements = ($branchId && $branchId !== 'all')
                ? $allPeriodSettlements->filter(fn($cs) => (int) ($cs->transaction->branch_id ?? 0) === (int) $branchId)->values()
                : $allPeriodSettlements;

            $settleCash = 0;
            $settleQris = 0;
            $settleTransfer = 0;
            $settledTrxIds = [];

            foreach ($cashSettlements as $cs) {
                $settledTrxIds[] = $cs->transaction_id;
                $pm = strtolower($cs->transaction->payment_method ?? 'transfer');
                if (str_contains($pm, 'cash') || str_contains($pm, 'tunai')) {
                    $settleCash += (float) $cs->jumlah;
                } elseif (str_contains($pm, 'qris')) {
                    $settleQris += (float) $cs->jumlah;
                } else {
                    $settleTransfer += (float) $cs->jumlah;
                }
            }

            $settleTotal = $settleCash + $settleQris + $settleTransfer;
        } else {
            $allPeriodSettlements = collect();
            $cashSettlements = collect();
            $settleCash = 0;
            $settleQris = 0;
            $settleTransfer = 0;
            $settleTotal = 0;
            $settledTrxIds = [];
        }

        // Synchronized Financial Calculations:
        // 1. Omzet: Daily / Period POS Revenue (Cash Inflow Basis: DP + Pelunasan)
        $directSales = (float) (clone $query)->sum('paid_amount');
        $totalSales = $directSales + $settleTotal;
        // 2. HPP: Material Cost from POS + Direct Click Charge Cash Out (COA 5-1100)
        $posHpp = (float) (clone $query)->sum('total_hpp');
        $clickChargeCash = (float) (clone $clickChargeQuery)->sum('jumlah');
        $totalHpp = $posHpp + $clickChargeCash;
        // 3. Gross Profit: Omzet - HPP
        $grossProfit = $totalSales - $totalHpp;
        // 4. OPEX: Cash Outflows (Kas Keluar Non-HPP & Non-Click Charge)
        $totalOpex = (float) (clone $opexQuery)->sum('jumlah');
        // 5. Net Profit: Gross Profit - OPEX
        $netProfit = $grossProfit - $totalOpex;

        $omsetBase = (float) $totalSales;
        $hppPct = $omsetBase > 0 ? round(($totalHpp / $omsetBase) * 100, 1) : 0;
        $grossPct = $omsetBase > 0 ? round(($grossProfit / $omsetBase) * 100, 1) : 0;
        $opexPct = $omsetBase > 0 ? round(($totalOpex / $omsetBase) * 100, 1) : 0;
        $netPct = $omsetBase > 0 ? round(($netProfit / $omsetBase) * 100, 1) : 0;

        $totalTransactionsCount = (clone $query)->count() + count(array_unique(array_filter($settledTrxIds)));
        $totalMaterialsCount = (clone $materialQuery)->count();
        $lowStockCount = (clone $materialQuery)->where('stock_qty', '<=', 5)->count();
        $pendingPOCount = (clone $purchaseQuery)->whereIn('status', ['waiting_approval', 'pending_verification', 'waiting_owner_approval'])->count();

        // Payment Breakdown (including split payments & older settlements)
        $directCash = (clone $query)->whereIn('payment_method', ['Cash', 'cash'])->sum('paid_amount');
        $directQris = (clone $query)->whereIn('payment_method', ['QRIS', 'qris'])->sum('paid_amount');
        $directTransfer = (clone $query)->whereIn('payment_method', ['Transfer', 'transfer'])->sum('paid_amount');

        $splitTxIds = (clone $query)->where('payment_method', 'like', 'Split%')->pluck('id');
        $splitPaymentsQuery = TransactionPayment::whereIn('transaction_id', $splitTxIds);
        $splitCash = (clone $splitPaymentsQuery)->whereIn('payment_method', ['Cash', 'cash'])->sum('amount');
        $splitQris = (clone $splitPaymentsQuery)->whereIn('payment_method', ['QRIS', 'qris'])->sum('amount');
        $splitTransfer = (clone $splitPaymentsQuery)->whereIn('payment_method', ['Transfer', 'transfer'])->sum('amount');

        $cashSales = (float) $directCash + (float) $splitCash + $settleCash;
        $qrisSales = (float) $directQris + (float) $splitQris + $settleQris;
        $transferSales = (float) $directTransfer + (float) $splitTransfer + $settleTransfer;

        // Branch Sales Comparison (synchronized with Total Sales cash inflow basis)
        $branchSettlementSums = $allPeriodSettlements
            ->groupBy(fn($cs) => (int) ($cs->transaction->branch_id ?? 0))
            ->map(fn($rows) => (float) $rows->sum('jumlah'));

        $branchSalesData = Branch::all()->map(function ($branch) use ($startDate, $endDate, $branchSettlementSums) {
            $bQuery = Transaction::where('branch_id', $branch->id)->whereNotIn('order_status', ['draft', 'cancelled']);
            if ($startDate && $endDate) {
                $bQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $sales = (float) $bQuery->sum('paid_amount') + ($branchSettlementSums[$branch->id] ?? 0.0);
            return [
                'name' => $branch->nama_cabang,
                'sales' => $sales,
            ];
        });

        // Pre-index settlements by effective date so chart buckets match Total Sales without double-counting
        $settleByDay = [];
        $settleIdsByDay = [];
        if ($startDate && $endDate) {
            $minDay = $startDate->toDateString();
            $maxDay = $endDate->toDateString();
            foreach ($cashSettlements as $cs) {
                $dKey = $this->resolveSettlementBucketDate($cs, $minDay, $maxDay);
                $settleByDay[$dKey] = ($settleByDay[$dKey] ?? 0.0) + (float) $cs->jumlah;
                $settleIdsByDay[$dKey][$cs->transaction_id] = true;
            }
        }

        // Interactive Trading Chart Data (Dynamic Labels & Series based on timeframe)
        $chartLabels = [];
        $chartSales = [];
        $chartVolume = [];
        $chartNet = [];
        $chartAov = [];

        if ($timeframe === 'custom' && $startDate && $endDate) {
            $diffDays = $startDate->diffInDays($endDate);

            if ($diffDays <= 45) {
                for ($cur = $startDate->copy()->startOfDay(); $cur->lte($endDate); $cur->addDay()) {
                    $dKey = $cur->toDateString();
                    $chartLabels[] = $cur->translatedFormat('d M');
                    $dTrx = Transaction::whereDate('created_at', $cur)
                        ->whereNotIn('order_status', ['draft', 'cancelled']);
                    if ($branchId && $branchId !== 'all') {
                        $dTrx->where('branch_id', $branchId);
                    }
                    $salesVal = (float) $dTrx->sum('paid_amount') + ($settleByDay[$dKey] ?? 0.0);
                    $volVal = (int) $dTrx->count() + count($settleIdsByDay[$dKey] ?? []);
                    $chartSales[] = $salesVal;
                    $chartVolume[] = $volVal;
                    $chartNet[] = $salesVal;
                    $chartAov[] = $volVal > 0 ? round($salesVal / $volVal) : 0;
                }
            } else {
                for ($cur = $startDate->copy()->startOfMonth(); $cur->lte($endDate); $cur->addMonth()) {
                    $mStart = $cur->copy()->startOfMonth()->max($startDate);
                    $mEnd = $cur->copy()->endOfMonth()->min($endDate);
                    $chartLabels[] = $cur->translatedFormat('M Y');
                    $mSalesQuery = Transaction::whereBetween('created_at', [$mStart, $mEnd])
                        ->whereNotIn('order_status', ['draft', 'cancelled']);
                    if ($branchId && $branchId !== 'all') {
                        $mSalesQuery->where('branch_id', $branchId);
                    }
                    [$mSettleVal, $mSettleVol] = $this->sumIndexedSettlements($settleByDay, $settleIdsByDay, $mStart->toDateString(), $mEnd->toDateString());
                    $salesVal = (float) $mSalesQuery->sum('paid_amount') + $mSettleVal;
                    $volVal = (int) $mSalesQuery->count() + $mSettleVol;
                    $chartSales[] = $salesVal;
                    $chartVolume[] = $volVal;
                    $chartNet[] = $salesVal;
                    $chartAov[] = $volVal > 0 ? round($salesVal / $volVal) : 0;
                }
            }
        } elseif ($timeframe === 'today' || $timeframe === '1D') {
            // Aggregate all transactions for today in 1 query
            $todayTransactions = (clone $query)->get(['paid_amount', 'created_at']);

            $hourlyData = [];
            // Full 24-Hour Range (00:00 - 23:00) to support all 24 hours seamlessly
            for ($h = 0; $h <= 23; $h++) {
                $hourlyData[$h] = ['sales' => 0.0, 'volume' => 0, 'settledIds' => []];
            }

            foreach ($todayTransactions as $trx) {
                $h = (int) $trx->created_at->format('G');
                if (isset($hourlyData[$h])) {
                    $hourlyData[$h]['sales'] += (float) $trx->paid_amount;
                    $hourlyData[$h]['volume'] += 1;
                }
            }

            foreach ($cashSettlements as $cs) {
                $h = $cs->created_at->between($startDate, $endDate) ? (int) $cs->created_at->format('G') : 0;
                if (isset($hourlyData[$h])) {
                    $hourlyData[$h]['sales'] += (float) $cs->jumlah;
                    $hourlyData[$h]['settledIds'][$cs->transaction_id] = true;
                }
            }

            foreach ($hourlyData as $hour => $data) {
                $volVal = $data['volume'] + count($data['settledIds']);
                $chartLabels[] = sprintf('%02d:00', $hour);
                $chartSales[] = $data['sales'];
                $chartVolume[] = $volVal;
                $chartNet[] = $data['sales'];
                $chartAov[] = $volVal > 0 ? round($data['sales'] / $volVal) : 0;
            }
        } elseif ($timeframe === '7days' || $timeframe === '7D') {
            for ($d = 6; $d >= 0; $d--) {
                $targetDate = Carbon::today()->subDays($d);
                $dKey = $targetDate->toDateString();
                $chartLabels[] = $targetDate->format('d M');

                $dTrx = Transaction::whereDate('created_at', $targetDate)
                    ->whereNotIn('order_status', ['draft', 'cancelled']);
                if ($branchId && $branchId !== 'all') {
                    $dTrx->where('branch_id', $branchId);
                }

                $salesVal = (float) $dTrx->sum('paid_amount') + ($settleByDay[$dKey] ?? 0.0);
                $volVal = (int) $dTrx->count() + count($settleIdsByDay[$dKey] ?? []);
                $chartSales[] = $salesVal;
                $chartVolume[] = $volVal;
                $chartNet[] = $salesVal;
                $chartAov[] = $volVal > 0 ? round($salesVal / $volVal) : 0;
            }
        } elseif ($timeframe === 'month' || $timeframe === '1M') {
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $targetDate = Carbon::createFromDate($year, $month, $d);
                $dKey = $targetDate->toDateString();
                $chartLabels[] = sprintf('%02d %s', $d, $targetDate->translatedFormat('M'));

                $dTrx = Transaction::whereDate('created_at', $targetDate)
                    ->whereNotIn('order_status', ['draft', 'cancelled']);
                if ($branchId && $branchId !== 'all') {
                    $dTrx->where('branch_id', $branchId);
                }

                $salesVal = (float) $dTrx->sum('paid_amount') + ($settleByDay[$dKey] ?? 0.0);
                $volVal = (int) $dTrx->count() + count($settleIdsByDay[$dKey] ?? []);
                $chartSales[] = $salesVal;
                $chartVolume[] = $volVal;
                $chartNet[] = $salesVal;
                $chartAov[] = $volVal > 0 ? round($salesVal / $volVal) : 0;
            }
        } elseif ($timeframe === 'year' || $timeframe === '1Y') {
            for ($m = 1; $m <= 12; $m++) {
                $mStart = Carbon::createFromDate($year, $m, 1)->startOfMonth();
                $mEnd = $mStart->copy()->endOfMonth();
                $chartLabels[] = $mStart->translatedFormat('M');

                $mTrx = Transaction::whereYear('created_at', $year)
                    ->whereMonth('created_at', $m)
                    ->whereNotIn('order_status', ['draft', 'cancelled']);
                if ($branchId && $branchId !== 'all') {
                    $mTrx->where('branch_id', $branchId);
                }

                [$mSettleVal, $mSettleVol] = $this->sumIndexedSettlements($settleByDay, $settleIdsByDay, $mStart->toDateString(), $mEnd->toDateString());
                $salesVal = (float) $mTrx->sum('paid_amount') + $mSettleVal;
                $volVal = (int) $mTrx->count() + $mSettleVol;
                $chartSales[] = $salesVal;
                $chartVolume[] = $volVal;
                $chartNet[] = $salesVal;
                $chartAov[] = $volVal > 0 ? round($salesVal / $volVal) : 0;
            }
        } else {
            // Default / 'all': 6 months overview
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $mStart = $date->copy()->startOfMonth()->startOfDay();
                $mEnd = $date->copy()->endOfMonth()->endOfDay();
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

                $mSettlements = $this->fetchSettlementCashTransactions($mStart, $mEnd, $branchId, $mStart);
                $mSales = (float) $mSalesQuery->sum('paid_amount') + (float) $mSettlements->sum('jumlah');
                $mClickCharge = (float) CashTransaction::keluar()
                    ->whereHas('account', fn($q) => $q->where('kode_akun', '5-1100'))
                    ->whereYear('tanggal', $date->year)
                    ->whereMonth('tanggal', $date->month)
                    ->when($branchId && $branchId !== 'all', function($bq) use ($branchId) {
                        $bq->where('branch_id', $branchId);
                    })->sum('jumlah');
                $mHpp = (float) $mSalesQuery->sum('total_hpp') + $mClickCharge;
                $mOpex = (float) $mOpexQuery->whereDoesntHave('account', function($q) {
                    $q->whereIn('kode_akun', ['6-1000', '5-1100']);
                })->sum('jumlah');
                $mVol = (int) $mSalesQuery->count() + $mSettlements->pluck('transaction_id')->unique()->count();

                $chartSales[] = $mSales;
                $chartVolume[] = $mVol;
                $chartNet[] = $mSales - $mHpp - $mOpex;
                $chartAov[] = $mVol > 0 ? round($mSales / $mVol) : 0;
            }
        }

        $highestSales = !empty($chartSales) ? max($chartSales) : 0;
        $lowestSales = !empty($chartSales) ? min($chartSales) : 0;
        $avgSales = !empty($chartSales) ? round(array_sum($chartSales) / count($chartSales)) : 0;

        // Business Growth & Customer Trust Analytics
        $avgOrderValue = $totalTransactionsCount > 0 ? round($totalSales / $totalTransactionsCount) : 0;
        $activeDaysCount = count(array_filter($chartVolume, fn($v) => $v > 0));
        $dailyAvgTransactions = $activeDaysCount > 0 ? round($totalTransactionsCount / $activeDaysCount, 1) : 0;
        $dailyAvgSales = $activeDaysCount > 0 ? round($totalSales / $activeDaysCount) : 0;

        $maxVol = !empty($chartVolume) ? max($chartVolume) : 0;
        $maxVolIdx = !empty($chartVolume) ? array_search($maxVol, $chartVolume) : false;
        $peakVolumeLabel = ($maxVolIdx !== false && isset($chartLabels[$maxVolIdx]) && $maxVol > 0) ? $chartLabels[$maxVolIdx] . " ({$maxVol} Trx)" : '-';

        $maxSales = !empty($chartSales) ? max($chartSales) : 0;
        $maxSalesIdx = !empty($chartSales) ? array_search($maxSales, $chartSales) : false;
        $peakSalesLabel = ($maxSalesIdx !== false && isset($chartLabels[$maxSalesIdx]) && $maxSales > 0) ? $chartLabels[$maxSalesIdx] . " (Rp " . number_format($maxSales, 0, ',', '.') . ")" : '-';

        $recentTransactions = (clone $query)->with(['user', 'branch', 'transactionDetails.material', 'payments'])->orderBy('created_at', 'desc')->take(10)->get();
        $branches = Branch::all();
        $startDate = $startDateInput;
        $endDate = $endDateInput;

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
            'chartAov',
            'highestSales',
            'lowestSales',
            'avgSales',
            'avgOrderValue',
            'dailyAvgTransactions',
            'dailyAvgSales',
            'activeDaysCount',
            'peakVolumeLabel',
            'peakSalesLabel',
            'branches',
            'branchId',
            'timeframe',
            'month',
            'year',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export daily analytics data for Owner to Excel/CSV.
     */
    public function exportAnalytics(Request $request)
    {
        $timeframe = $request->input('timeframe', $request->input('period', 'month'));
        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);
        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');
        $branchId = $request->input('branch_id', 'all');

        $query = Transaction::query()->whereNotIn('order_status', ['draft', 'cancelled']);
        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        if ($timeframe === 'custom' && $startDateInput && $endDateInput) {
            $sDate = $this->parseFlexibleDate($startDateInput)?->startOfDay();
            $eDate = $this->parseFlexibleDate($endDateInput)?->endOfDay();
            if ($sDate && $eDate) {
                $query->whereBetween('created_at', [$sDate, $eDate]);
            }
        } elseif ($timeframe === 'today' || $timeframe === '1D') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($timeframe === '7days' || $timeframe === '7D') {
            $query->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay());
        } elseif ($timeframe === 'year' || $timeframe === '1Y') {
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, 12, 31)->endOfMonth();
            $query->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);
        } else {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
            $query->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);
        }

        $records = $query->with(['branch', 'payments'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy(fn($t) => $t->created_at->format('Y-m-d'));

        $filename = "Analisa_Pertumbuhan_Snaprint_" . date('Ymd_His') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Tanggal',
                'Jumlah Transaksi (Traffic)',
                'Total Omset Riil (Rp)',
                'Rata-rata Belanja per Pelanggan / AOV (Rp)',
                'Kas Tunai (Rp)',
                'QRIS (Rp)',
                'Transfer Bank (Rp)',
            ]);

            foreach ($records as $date => $items) {
                $trxCount = $items->count();
                $totalPaid = $items->sum('paid_amount');
                $aov = $trxCount > 0 ? round($totalPaid / $trxCount) : 0;
                $splitPayments = $items->filter(fn($t) => str_starts_with(strtolower($t->payment_method ?? ''), 'split'))
                    ->flatMap(fn($t) => $t->payments);
                $cash = $items->whereIn('payment_method', ['Cash', 'cash'])->sum('paid_amount')
                    + $splitPayments->filter(fn($p) => in_array(strtolower($p->payment_method), ['cash', 'tunai']))->sum('amount');
                $qris = $items->whereIn('payment_method', ['QRIS', 'qris'])->sum('paid_amount')
                    + $splitPayments->filter(fn($p) => in_array(strtolower($p->payment_method), ['qris']))->sum('amount');
                $tf = $items->whereIn('payment_method', ['Transfer', 'transfer'])->sum('paid_amount')
                    + $splitPayments->filter(fn($p) => in_array(strtolower($p->payment_method), ['transfer']))->sum('amount');

                fputcsv($file, [
                    Carbon::parse($date)->translatedFormat('d F Y'),
                    $trxCount,
                    $totalPaid,
                    $aov,
                    $cash,
                    $qris,
                    $tf,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Flexible date parser supporting DD/MM/YYYY, DD-MM-YYYY, YYYY-MM-DD.
     */
    protected function parseFlexibleDate($dateStr)
    {
        if (!$dateStr) return null;
        $clean = trim($dateStr);
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $clean, $m)) {
            return Carbon::createFromDate((int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $clean, $m)) {
            return Carbon::createFromDate((int)$m[1], (int)$m[2], (int)$m[3]);
        }
        try {
            return Carbon::parse($clean);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Fetch settlement cash-in records received in [bucketStart, bucketEnd] for orders created before olderThanDate.
     */
    protected function fetchSettlementCashTransactions(Carbon $bucketStart, Carbon $bucketEnd, $branchId = null, ?Carbon $olderThanDate = null)
    {
        $startDayStr = $bucketStart->toDateString();
        $endDayStr = $bucketEnd->toDateString();
        $cutoff = $olderThanDate ?: $bucketStart;

        return CashTransaction::where('tipe', 'masuk')
            ->whereNotNull('transaction_id')
            ->where(function ($q) use ($startDayStr, $endDayStr, $bucketStart, $bucketEnd) {
                $q->where(function ($dq) use ($startDayStr, $endDayStr) {
                    $dq->whereDate('tanggal', '>=', $startDayStr)
                       ->whereDate('tanggal', '<=', $endDayStr);
                })->orWhereBetween('created_at', [$bucketStart, $bucketEnd]);
            })
            ->whereHas('transaction', function ($t) use ($cutoff, $branchId) {
                $t->where('created_at', '<', $cutoff)
                  ->whereNotIn('order_status', ['draft', 'cancelled'])
                  ->when($branchId && $branchId !== 'all', fn($bq) => $bq->where('branch_id', $branchId));
            })
            ->with('transaction')
            ->get();
    }

    protected function resolveSettlementBucketDate(CashTransaction $cs, string $minDayStr, string $maxDayStr): string
    {
        if ($cs->tanggal) {
            $tDate = Carbon::parse($cs->tanggal)->toDateString();
            if ($tDate >= $minDayStr && $tDate <= $maxDayStr) {
                return $tDate;
            }
        }
        return $cs->created_at->toDateString();
    }

    protected function sumIndexedSettlements(array $settleByDay, array $settleIdsByDay, string $startDay, string $endDay): array
    {
        $sum = 0.0;
        $uniqueIds = [];
        foreach ($settleByDay as $d => $val) {
            if ($d >= $startDay && $d <= $endDay) {
                $sum += $val;
                foreach (($settleIdsByDay[$d] ?? []) as $tid => $_) {
                    $uniqueIds[$tid] = true;
                }
            }
        }
        return [$sum, count($uniqueIds)];
    }
}
