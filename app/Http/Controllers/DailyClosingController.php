<?php

namespace App\Http\Controllers;

use App\Models\DailyClosingReport;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\CashTransaction;
use App\Models\BossCashWithdrawal;
use App\Models\Account;
use App\Models\DailyClosingWaste;
use App\Models\Material;
use App\Models\MaterialRoll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyClosingController extends Controller
{
    /**
     * Check if current user is allowed to switch branch (KINGAshabil or SWANTO or superadmin).
     */
    private function canChangeBranch($user): bool
    {
        $allowedUsernames = ['kingashabil', 'swanto'];
        return in_array(strtolower($user->username ?? ''), $allowedUsernames) || $user->isSuperAdmin();
    }

    /**
     * List all Daily Closing Reports with Petty Cash Accumulation & Boss Withdrawals.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $canChange = $this->canChangeBranch($user);

        if (!$canChange) {
            $branchId = (int)($user->branch_id ?: 1);
        } else {
            if ($request->has('branch_id')) {
                $branchId = $request->input('branch_id');
                session(['selected_branch_id' => $branchId]);
            } else {
                $branchId = session('selected_branch_id', 'all');
            }
        }

        $query = DailyClosingReport::with(['branch', 'manager', 'owner'])->orderBy('closing_date', 'desc')->orderBy('id', 'desc');

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('closing_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('closing_date', '<=', $request->date_to);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $reports = $query->paginate(20);
        $branches = Branch::orderBy('nama_cabang')->get();

        $pendingCount = DailyClosingReport::where('status', 'submitted')->count();

        // Calculate Cumulative Petty Cash & Boss Withdrawals
        $withdrawalsQuery = BossCashWithdrawal::with(['branch', 'user'])->orderBy('withdrawal_date', 'desc')->orderBy('id', 'desc');
        $historicalReportsQuery = DailyClosingReport::query();

        if ($branchId && $branchId !== 'all') {
            $withdrawalsQuery->where('branch_id', $branchId);
            $historicalReportsQuery->where('branch_id', $branchId);
        }

        $allWithdrawals = $withdrawalsQuery->get();
        $allHistoricalReports = $historicalReportsQuery->orderBy('closing_date', 'asc')->orderBy('id', 'asc')->get();

        // Group withdrawals by date string Y-m-d
        $withdrawalsByDate = [];
        foreach ($allWithdrawals as $w) {
            $dStr = Carbon::parse($w->withdrawal_date)->toDateString();
            $withdrawalsByDate[$dStr][] = $w;
        }

        // Chronological running balance calculation per report
        $cumulativeBalance = 0;
        $runningBalances = [];
        $appliedWithdrawalDates = [];

        foreach ($allHistoricalReports as $rep) {
            $dStr = Carbon::parse($rep->closing_date)->toDateString();
            $cumulativeBalance += (float) $rep->actual_cash;

            // Apply boss withdrawals on or before this date
            if (isset($withdrawalsByDate[$dStr]) && !isset($appliedWithdrawalDates[$dStr])) {
                foreach ($withdrawalsByDate[$dStr] as $w) {
                    $cumulativeBalance -= (float) $w->amount;
                }
                $appliedWithdrawalDates[$dStr] = true;
            }

            $runningBalances[$rep->id] = $cumulativeBalance;
        }

        // Attach running balance & day withdrawals to paginated reports
        foreach ($reports as $r) {
            $r->running_petty_cash = $runningBalances[$r->id] ?? (float)$r->actual_cash;
            $dStr = Carbon::parse($r->closing_date)->toDateString();
            $r->day_withdrawals = $withdrawalsByDate[$dStr] ?? [];
            $r->total_withdrawn_today = isset($withdrawalsByDate[$dStr]) ? collect($withdrawalsByDate[$dStr])->sum('amount') : 0;
            $r->daily_remaining_cash = max(0, (float)$r->actual_cash - (float)$r->total_withdrawn_today);
        }

        // Overall Peti Cash Balance
        $totalCashInAllTime = (float) $allHistoricalReports->sum('actual_cash');
        $totalWithdrawnAllTime = (float) $allWithdrawals->sum('amount');
        $totalLivePettyCash = max(0, $totalCashInAllTime - $totalWithdrawnAllTime);

        // Current month metrics
        $currentMonth = date('Y-m');
        $monthCashIn = (float) $allHistoricalReports->filter(fn($r) => Carbon::parse($r->closing_date)->format('Y-m') === $currentMonth)->sum('actual_cash');
        $monthWithdrawn = (float) $allWithdrawals->filter(fn($w) => Carbon::parse($w->withdrawal_date)->format('Y-m') === $currentMonth)->sum('amount');

        // Live Petty Cash per Branch
        $branchLivePettyCash = [];
        foreach ($branches as $b) {
            $bIn = (float) $allHistoricalReports->where('branch_id', $b->id)->sum('actual_cash');
            $bOut = (float) $allWithdrawals->where('branch_id', $b->id)->sum('amount');
            $branchLivePettyCash[$b->id] = max(0, $bIn - $bOut);
        }

        $recentWithdrawals = $allWithdrawals->take(15);

        // Waste Records for Search & Summary
        $wastesQuery = DailyClosingWaste::with(['material', 'materialRoll', 'branch', 'user', 'dailyClosingReport'])
            ->orderBy('created_at', 'desc');

        if ($branchId && $branchId !== 'all') {
            $wastesQuery->where('branch_id', $branchId);
        }

        $allWastes = $wastesQuery->take(100)->get();
        $monthWasteHpp = (float) DailyClosingWaste::whereMonth('created_at', date('m'))
            ->whereYear('created_at', date('Y'))
            ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
            ->sum('total_hpp');

        return view('finance.daily_closing.index', compact(
            'reports',
            'branches',
            'pendingCount',
            'branchId',
            'canChange',
            'totalLivePettyCash',
            'monthCashIn',
            'monthWithdrawn',
            'recentWithdrawals',
            'branchLivePettyCash',
            'allWastes',
            'monthWasteHpp'
        ));
    }

    /**
     * Store Boss Cash Withdrawal ("Diambil Boss") with PIN Verification (PIN: 2209).
     */
    public function storeBossWithdrawal(Request $request)
    {
        $user = Auth::user();
        $canChange = $this->canChangeBranch($user);

        $branchId = ($canChange && $request->filled('branch_id')) 
            ? (int)$request->branch_id 
            : (int)($user->branch_id ?: 1);

        $request->validate([
            'withdrawal_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'taken_by' => 'required|string|max:100',
            'purpose' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'daily_closing_report_id' => 'nullable|exists:daily_closing_reports,id',
            'boss_pin' => 'required',
        ]);

        // Verifikasi PIN Otorisasi (2209)
        if (trim($request->input('boss_pin')) !== '2209') {
            return redirect()->back()->with('error', 'PIN Otorisasi Penarikan Kas Salah! Uang kas tidak dapat ditarik.');
        }

        $withdrawal = BossCashWithdrawal::create([
            'branch_id' => $branchId,
            'user_id' => $user->id,
            'withdrawal_date' => $request->withdrawal_date,
            'amount' => $request->amount,
            'taken_by' => $request->taken_by,
            'purpose' => $request->purpose ?: 'Setor Kas / Pengambilan Boss',
            'notes' => $request->notes,
            'daily_closing_report_id' => $request->input('daily_closing_report_id'),
        ]);

        $branch = Branch::find($branchId);
        $branchName = $branch ? $branch->nama_cabang : 'Cabang';

        return redirect()->back()->with('success', "PIN Terverifikasi! Kas sebesar Rp " . number_format($request->amount, 0, ',', '.') . " di {$branchName} berhasil dicatat ditarik oleh Boss.");
    }

    /**
     * Delete Boss Cash Withdrawal.
     */
    public function destroyBossWithdrawal($id)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isSuperAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized.');
        }

        $withdrawal = BossCashWithdrawal::findOrFail($id);
        $amountStr = number_format($withdrawal->amount, 0, ',', '.');
        $withdrawal->delete();

        return redirect()->back()->with('success', "Data penarikan 'Diambil Boss' sebesar Rp {$amountStr} berhasil dihapus.");
    }

    /**
     * Show form to create a new Daily Closing Report (Lembar Kerja).
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $targetDate = $request->input('closing_date', date('Y-m-d'));
        $canChangeBranch = $this->canChangeBranch($user);

        if ($canChangeBranch && $request->filled('branch_id')) {
            $branchId = (int)$request->branch_id;
        } else {
            $branchId = (int)($user->branch_id ?: 1);
        }

        $branch = Branch::find($branchId) ?: Branch::first();
        $branches = Branch::orderBy('nama_cabang')->get();

        // 1. Calculate sales from transactions created on targetDate
        $transactions = Transaction::with('payments')
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $targetDate)
            ->whereNotIn('order_status', ['draft', 'cancelled'])
            ->get();

        $totalOrdersCount = $transactions->count();
        $directTransactions = $transactions->filter(fn($t) => $t->payments->isEmpty());
        $splitPayments = $transactions->flatMap->payments;

        $targetDateStr = Carbon::parse($targetDate)->toDateString();
        $splitPaymentsToday = $splitPayments->filter(fn($p) => $p->created_at && Carbon::parse($p->created_at)->toDateString() === $targetDateStr);

        $directCash = (float) $directTransactions->whereIn('payment_method', ['Cash', 'cash'])->sum('paid_amount')
            + (float) $splitPaymentsToday->whereIn('payment_method', ['Cash', 'cash'])->sum('amount');
        $directTransfer = (float) $directTransactions->whereIn('payment_method', ['Transfer', 'transfer'])->sum('paid_amount')
            + (float) $splitPaymentsToday->whereIn('payment_method', ['Transfer', 'transfer'])->sum('amount');
        $directQris = (float) $directTransactions->whereIn('payment_method', ['QRIS', 'qris'])->sum('paid_amount')
            + (float) $splitPaymentsToday->whereIn('payment_method', ['QRIS', 'qris'])->sum('amount');

        // 2. Include settlements (Pelunasan Piutang) received on targetDate for older transactions
        $olderSettlementPayments = TransactionPayment::where('branch_id', $branchId)
            ->whereDate('created_at', $targetDate)
            ->whereHas('transaction', fn($q) => $q->whereDate('created_at', '<', $targetDate))
            ->get();

        $settleCash = (float) $olderSettlementPayments->whereIn('payment_method', ['Cash', 'cash'])->sum('amount');
        $settleTransfer = (float) $olderSettlementPayments->whereIn('payment_method', ['Transfer', 'transfer'])->sum('amount');
        $settleQris = (float) $olderSettlementPayments->whereIn('payment_method', ['QRIS', 'qris'])->sum('amount');

        // Fallback for settlements tracked in CashTransaction
        $cashTxSettlements = CashTransaction::where('branch_id', $branchId)
            ->where('tipe', 'masuk')
            ->whereDate('tanggal', $targetDate)
            ->whereNotNull('transaction_id')
            ->where('keterangan', 'like', 'Pelunasan%')
            ->whereNotIn('transaction_id', $olderSettlementPayments->pluck('transaction_id'))
            ->get();

        foreach ($cashTxSettlements as $cTx) {
            $t = $cTx->transaction;
            $pm = $t ? strtolower($t->payment_method) : 'transfer';
            if (str_contains($pm, 'cash')) {
                $settleCash += (float) $cTx->jumlah;
            } elseif (str_contains($pm, 'qris')) {
                $settleQris += (float) $cTx->jumlah;
            } else {
                $settleTransfer += (float) $cTx->jumlah;
            }
        }

        $totalCashSales = $directCash + $settleCash;
        $totalTransferSales = $directTransfer + $settleTransfer;
        $totalQrisSales = $directQris + $settleQris;
        $totalSales = $totalCashSales + $totalTransferSales + $totalQrisSales;

        // 3. Existing cash expenses recorded on targetDate
        $cashOut = (float) CashTransaction::where('branch_id', $branchId)
            ->where('tipe', 'keluar')
            ->whereDate('tanggal', $targetDate)
            ->sum('jumlah');

        $existingExpenses = CashTransaction::with('account')
            ->where('branch_id', $branchId)
            ->where('tipe', 'keluar')
            ->whereDate('tanggal', $targetDate)
            ->orderBy('id', 'asc')
            ->get();

        // Expense accounts list for dynamic addition
        $expenseAccounts = Account::where('tipe', 'beban')
            ->active()
            ->orderBy('kode_akun')
            ->get();

        // 4. Existing boss withdrawals recorded on targetDate
        $existingBossWithdrawals = BossCashWithdrawal::where('branch_id', $branchId)
            ->whereDate('withdrawal_date', $targetDate)
            ->get();
        $existingBossWithdrawalTotal = (float) $existingBossWithdrawals->sum('amount');

        // Expected cash purely = total Cash Sales minus Cash Out minus Boss Withdrawals
        $openingCash = 0;
        $cashIn = 0;
        $expectedCash = max(0, $totalCashSales - $cashOut - $existingBossWithdrawalTotal);

        // Branch Materials & Rolls for Waste Recording
        $branchMaterials = Material::with(['rolls' => function($q) {
                $q->orderBy('roll_width_m', 'asc');
            }])
            ->where('branch_id', $branchId)
            ->orderBy('category')
            ->orderBy('material_name')
            ->get(['id', 'branch_id', 'material_name', 'category', 'unit', 'purchase_price', 'stock_qty']);

        // Fetch any waste recorded today in Stock menu
        $existingTodayWastes = DailyClosingWaste::with(['material', 'materialRoll', 'user'])
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $targetDate)
            ->get();
        $existingTodayWasteTotalHpp = (float)$existingTodayWastes->sum('total_hpp');

        return view('finance.daily_closing.create', compact(
            'branches',
            'branch',
            'canChangeBranch',
            'targetDate',
            'totalOrdersCount',
            'totalCashSales',
            'totalTransferSales',
            'totalQrisSales',
            'totalSales',
            'cashIn',
            'cashOut',
            'existingExpenses',
            'expenseAccounts',
            'existingBossWithdrawals',
            'existingBossWithdrawalTotal',
            'openingCash',
            'expectedCash',
            'branchMaterials',
            'existingTodayWastes',
            'existingTodayWasteTotalHpp'
        ));
    }

    /**
     * Store Daily Closing Report, record dynamic expenses, boss withdrawal, and auto-sign Manager signature.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $canChangeBranch = $this->canChangeBranch($user);

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'closing_date' => 'required|date',
            'shift_type' => 'nullable|string|max:50',
            'actual_cash' => 'required|numeric|min:0',
            'total_cash_sales' => 'required|numeric|min:0',
            'total_transfer_sales' => 'required|numeric|min:0',
            'total_qris_sales' => 'required|numeric|min:0',
            'boss_withdrawal_amount' => 'nullable|numeric|min:0',
            'boss_withdrawal_taken_by' => 'nullable|string|max:100',
            'boss_withdrawal_purpose' => 'nullable|string|max:255',
            'boss_withdrawal_notes' => 'nullable|string|max:500',
            'click_counter_start' => 'nullable|integer|min:0',
            'click_counter_end' => 'nullable|integer|min:0',
            'production_notes' => 'nullable|string|max:1000',
            'denominations' => 'nullable|array',
            'new_expenses' => 'nullable|array',
            'new_expenses.*' => 'nullable',
            'wastes' => 'nullable|array',
            'wastes.*' => 'nullable',
        ]);

        // Verifikasi PIN jika di lembar kerja ada penarikan kas oleh Boss
        $bossWithdrawalAmount = (float) $request->input('boss_withdrawal_amount', 0);
        if ($bossWithdrawalAmount > 0) {
            if (trim($request->input('boss_pin')) !== '2209') {
                return redirect()->back()->withInput()->with('error', 'PIN Otorisasi Penarikan Kas Salah! Masukkan PIN otorisasi yang valid.');
            }
        }

        if ($canChangeBranch && $request->filled('branch_id')) {
            $branchId = (int)$request->branch_id;
        } else {
            $branchId = (int)($user->branch_id ?: 1);
        }

        $branch = Branch::findOrFail($branchId);

        return DB::transaction(function () use ($request, $user, $branch, $bossWithdrawalAmount) {
            // 1. Process and record dynamic new expenses into CashTransaction (tipe: keluar)
            $newExpenses = $request->input('new_expenses', []);
            $defaultAccount = Account::where('tipe', 'beban')->first();
            $defaultAccountId = $defaultAccount ? $defaultAccount->id : 11;

            if (is_array($newExpenses)) {
                foreach ($newExpenses as $exp) {
                    $amount = isset($exp['amount']) ? (float)$exp['amount'] : 0;
                    $keterangan = trim($exp['keterangan'] ?? '');
                    $accountId = !empty($exp['account_id']) ? (int)$exp['account_id'] : $defaultAccountId;

                    if ($amount > 0 && !empty($keterangan)) {
                        CashTransaction::create([
                            'branch_id' => $branch->id,
                            'account_id' => $accountId,
                            'user_id' => $user->id,
                            'tipe' => 'keluar',
                            'nomor_referensi' => CashTransaction::generateNomorReferensi('keluar'),
                            'tanggal' => $request->closing_date,
                            'jumlah' => $amount,
                            'keterangan' => $keterangan,
                        ]);
                    }
                }
            }

            // 2. Fetch all expenses recorded on this date for audit trail
            $allExpenses = CashTransaction::with('account')
                ->where('branch_id', $branch->id)
                ->where('tipe', 'keluar')
                ->whereDate('tanggal', $request->closing_date)
                ->orderBy('id', 'asc')
                ->get()
                ->map(fn($e) => [
                    'id' => $e->id,
                    'nomor_referensi' => $e->nomor_referensi,
                    'keterangan' => $e->keterangan,
                    'account_name' => $e->account->nama_akun ?? 'Beban Operasional',
                    'jumlah' => (float)$e->jumlah,
                ])
                ->values()
                ->toArray();

            $totalCashOut = CashTransaction::where('branch_id', $branch->id)
                ->where('tipe', 'keluar')
                ->whereDate('tanggal', $request->closing_date)
                ->sum('jumlah');

            // 3. Process cash denominations breakdown (Lembaran & Koin)
            $denominationsInput = $request->input('denominations', []);
            $billsConfig = [100000, 50000, 20000, 10000, 5000, 2000, 1000];
            $coinsConfig = [1000, 500, 200, 100];

            $billsData = [];
            $totalBills = 0;
            foreach ($billsConfig as $nom) {
                $qty = isset($denominationsInput['bills'][$nom]) ? max(0, (int)$denominationsInput['bills'][$nom]) : 0;
                $subtotal = $qty * $nom;
                $billsData[$nom] = [
                    'nominal' => $nom,
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                ];
                $totalBills += $subtotal;
            }

            $coinsData = [];
            $totalCoins = 0;
            foreach ($coinsConfig as $nom) {
                $qty = isset($denominationsInput['coins'][$nom]) ? max(0, (int)$denominationsInput['coins'][$nom]) : 0;
                $subtotal = $qty * $nom;
                $coinsData[$nom] = [
                    'nominal' => $nom,
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                ];
                $totalCoins += $subtotal;
            }

            $totalDenominationCash = $totalBills + $totalCoins;

            $cashDenominations = [
                'bills' => $billsData,
                'total_bills' => $totalBills,
                'coins' => $coinsData,
                'total_coins' => $totalCoins,
                'grand_total' => $totalDenominationCash,
            ];

            // If user counted via denominations, synchronize actual cash
            $actualCash = $totalDenominationCash > 0 ? $totalDenominationCash : (float)$request->actual_cash;

            // 4. Boss Withdrawal handling directly on work sheet
            $bossWithdrawalTakenBy = $request->input('boss_withdrawal_taken_by') ?: 'Boss SWANTO';
            $bossWithdrawalPurpose = $request->input('boss_withdrawal_purpose') ?: 'Setor Kas / Pengambilan Boss';
            $bossWithdrawalNotes = $request->input('boss_withdrawal_notes');

            // Expected cash = POS cash sales - Total cash out - Boss withdrawal
            $totalSales = (float)$request->total_cash_sales + (float)$request->total_transfer_sales + (float)$request->total_qris_sales;
            $expectedCash = max(0, (float)$request->total_cash_sales - (float)$totalCashOut - $bossWithdrawalAmount);
            $cashDifference = $actualCash - $expectedCash;

            $clickCount = null;
            if ($request->filled('click_counter_start') && $request->filled('click_counter_end')) {
                $clickCount = max(0, (int)$request->click_counter_end - (int)$request->click_counter_start);
            }

            // Process Waste Bahan (Trial & Error Mesin)
            $wastesInput = $request->input('wastes', []);
            $wasteRecords = [];
            $totalWasteHpp = 0;

            if (is_array($wastesInput)) {
                foreach ($wastesInput as $w) {
                    $materialId = !empty($w['material_id']) ? (int)$w['material_id'] : null;
                    $wasteQty = isset($w['waste_qty']) ? (float)$w['waste_qty'] : 0;
                    $reason = trim($w['reason'] ?? 'Trial Alignment / Error');
                    $notes = trim($w['notes'] ?? '');
                    $rollId = !empty($w['material_roll_id']) ? (int)$w['material_roll_id'] : null;

                    if ($materialId && $wasteQty > 0) {
                        $mat = Material::find($materialId);
                        if ($mat) {
                            $roll = $rollId ? MaterialRoll::find($rollId) : null;
                            
                            if ($roll) {
                                $unit = 'm';
                                $rollWidth = (float)$roll->roll_width_m ?: 1.0;
                                $hppPerUnit = (float)$mat->purchase_price * $rollWidth;
                                $subtotalHpp = round($wasteQty * $hppPerUnit, 2);

                                // Kurangi sisa panjang roll di gudang
                                $newLength = max(0, round((float)$roll->current_length_m - $wasteQty, 2));
                                $roll->update(['current_length_m' => $newLength]);
                            } else {
                                $unit = $mat->unit ?: 'pcs';
                                $hppPerUnit = (float)$mat->purchase_price;
                                $subtotalHpp = round($wasteQty * $hppPerUnit, 2);

                                // Kurangi stok fisik bahan & sinkronkan varian linked
                                $newStock = max(0, round((float)$mat->stock_qty - $wasteQty, 2));
                                $mat->syncStockToLinked($newStock);
                            }

                            $totalWasteHpp += $subtotalHpp;

                            $wasteRecords[] = [
                                'material_id' => $mat->id,
                                'material_name' => $mat->material_name,
                                'material_roll_id' => $roll ? $roll->id : null,
                                'roll_name' => $roll ? ($roll->roll_name ?: "Roll {$roll->roll_width_m}m") : null,
                                'waste_qty' => $wasteQty,
                                'unit' => $unit,
                                'hpp_per_unit' => $hppPerUnit,
                                'total_hpp' => $subtotalHpp,
                                'reason' => $reason,
                                'notes' => $notes,
                            ];
                        }
                    }
                }
            }

            // Include any unlinked wastes recorded earlier today from the Stock page
            $unlinkedTodayWastes = DailyClosingWaste::with(['material', 'materialRoll'])
                ->where('branch_id', $branch->id)
                ->whereDate('created_at', $request->closing_date)
                ->whereNull('daily_closing_report_id')
                ->get();

            foreach ($unlinkedTodayWastes as $uw) {
                $totalWasteHpp += (float)$uw->total_hpp;
                $wasteRecords[] = [
                    'material_id' => $uw->material_id,
                    'material_name' => $uw->material->material_name ?? '-',
                    'material_roll_id' => $uw->material_roll_id,
                    'roll_name' => $uw->materialRoll ? ($uw->materialRoll->roll_name ?: "Roll {$uw->materialRoll->roll_width_m}m") : null,
                    'waste_qty' => (float)$uw->waste_qty,
                    'unit' => $uw->unit,
                    'hpp_per_unit' => (float)$uw->hpp_per_unit,
                    'total_hpp' => (float)$uw->total_hpp,
                    'reason' => $uw->reason,
                    'notes' => $uw->notes,
                ];
            }

            $reportNumber = DailyClosingReport::generateReportNumber($branch->nama_cabang);

            $report = DailyClosingReport::create([
                'report_number' => $reportNumber,
                'branch_id' => $branch->id,
                'user_id' => $user->id,
                'closing_date' => $request->closing_date,
                'shift_type' => 'Full Day',
                'total_orders_count' => (int) $request->input('total_orders_count', 0),
                'total_cash_sales' => $request->total_cash_sales,
                'total_transfer_sales' => $request->total_transfer_sales,
                'total_qris_sales' => $request->total_qris_sales,
                'total_sales' => $totalSales,
                'total_cash_in' => 0,
                'total_cash_out' => $totalCashOut,
                'opening_cash' => 0,
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'cash_difference' => $cashDifference,
                'cash_denominations' => $cashDenominations,
                'expenses_data' => $allExpenses,
                'total_waste_hpp' => $totalWasteHpp,
                'waste_data' => $wasteRecords,
                'click_counter_start' => $request->click_counter_start,
                'click_counter_end' => $request->click_counter_end,
                'click_count_total' => $clickCount,
                'production_notes' => $request->production_notes,
                'manager_signature_path' => $user->signature_path,
                'manager_signed_at' => Carbon::now(),
                'status' => 'submitted',
            ]);

            // Save any newly added wastes from closing form
            foreach ($wasteRecords as $wr) {
                // If it wasn't already in unlinkedTodayWastes
                $alreadyExists = $unlinkedTodayWastes->contains(function($u) use ($wr) {
                    return $u->material_id == $wr['material_id'] 
                        && $u->material_roll_id == $wr['material_roll_id']
                        && (float)$u->waste_qty == (float)$wr['waste_qty'];
                });

                if (!$alreadyExists) {
                    DailyClosingWaste::create([
                        'daily_closing_report_id' => $report->id,
                        'branch_id' => $branch->id,
                        'user_id' => $user->id,
                        'material_id' => $wr['material_id'],
                        'material_roll_id' => $wr['material_roll_id'],
                        'waste_qty' => $wr['waste_qty'],
                        'unit' => $wr['unit'],
                        'hpp_per_unit' => $wr['hpp_per_unit'],
                        'total_hpp' => $wr['total_hpp'],
                        'reason' => $wr['reason'],
                        'notes' => $wr['notes'],
                    ]);
                }
            }

            // Link existing unlinked wastes from today
            DailyClosingWaste::where('branch_id', $branch->id)
                ->whereDate('created_at', $request->closing_date)
                ->whereNull('daily_closing_report_id')
                ->update(['daily_closing_report_id' => $report->id]);

            // Save Boss Withdrawal if entered on work sheet
            if ($bossWithdrawalAmount > 0) {
                BossCashWithdrawal::create([
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                    'withdrawal_date' => $request->closing_date,
                    'amount' => $bossWithdrawalAmount,
                    'taken_by' => $bossWithdrawalTakenBy,
                    'purpose' => $bossWithdrawalPurpose,
                    'notes' => $bossWithdrawalNotes,
                    'daily_closing_report_id' => $report->id,
                ]);
            }

            return redirect()->route('daily-closing.show', $report->id)->with('success', "Laporan Tutup Hari #{$report->report_number} berhasil disimpan dengan rekonsiliasi kas dan pencatatan penarikan boss.");
        });
    }

    /**
     * Show detailed Daily Closing Report.
     */
    public function show($id)
    {
        $report = DailyClosingReport::with(['branch', 'manager', 'owner', 'wastes.material', 'wastes.materialRoll'])->findOrFail($id);
        $dayWithdrawals = BossCashWithdrawal::where('branch_id', $report->branch_id)
            ->whereDate('withdrawal_date', $report->closing_date)
            ->get();
            
        return view('finance.daily_closing.show', compact('report', 'dayWithdrawals'));
    }

    /**
     * Verify and digitally sign Daily Closing Report (Owner only).
     */
    public function verify(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isSuperAdmin()) {
            abort(403, 'Hanya Owner yang berwenang menandatangani penerimaan Laporan Tutup Hari.');
        }

        $report = DailyClosingReport::findOrFail($id);
        $report->owner_id = $user->id;
        $report->owner_signature_path = $user->signature_path;
        $report->owner_signed_at = Carbon::now();
        $report->owner_notes = $request->input('owner_notes');
        $report->status = 'verified';
        $report->save();

        return redirect()->back()->with('success', "Laporan Tutup Hari #{$report->report_number} berhasil diverifikasi & ditandatangani oleh Owner.");
    }

    /**
     * Delete Daily Closing Report (Super Admin / Owner).
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $report = DailyClosingReport::with('wastes')->findOrFail($id);
        
        // Restore stock deducted by waste
        foreach ($report->wastes as $w) {
            if ($w->material_roll_id) {
                MaterialRoll::where('id', $w->material_roll_id)->increment('current_length_m', (float)$w->waste_qty);
            } elseif ($w->material_id) {
                $mat = Material::find($w->material_id);
                if ($mat) {
                    $newStock = round((float)$mat->stock_qty + (float)$w->waste_qty, 2);
                    $mat->syncStockToLinked($newStock);
                }
            }
        }

        $report->wastes()->delete();
        $report->delete();

        return redirect()->route('daily-closing.index')->with('success', 'Laporan Tutup Hari berhasil dihapus.');
    }
}
