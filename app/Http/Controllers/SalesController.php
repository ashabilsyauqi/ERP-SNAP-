<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Material;
use App\Models\MaterialWholesalePrice;
use App\Models\CashTransaction;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\TransactionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SalesController extends Controller
{
    /**
     * Display a listing of completed sales.
     */
    public function index(Request $request)
    {
        @ini_set('memory_limit', '512M');
        $user = auth()->user();
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();
        $statusFilter = $request->input('status', 'sales'); // 'sales' (default: completed/in_production/ready) or 'draft'

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

        $query = Transaction::with(['user', 'branch', 'transactionDetails.material', 'payments', 'cashTransactions'])
            ->orderBy('created_at', 'desc');

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        if ($statusFilter === 'draft') {
            $query->where('order_status', 'draft');
        } else {
            $query->whereNotIn('order_status', ['draft', 'cancelled']);
        }

        // Period filter (today, yesterday, 7days, this_month, monthly, all, or custom date range)
        $period = $request->input('period', 'today');
        $selectedMonth = (int) $request->input('month', now()->month);
        $selectedYear = (int) $request->input('year', now()->year);
        $startDate = null;
        $endDate = null;

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $startDate = $request->date_from . ' 00:00:00';
            $endDate = $request->date_to . ' 23:59:59';
            $period = 'custom';
        } elseif ($period === 'today') {
            $startDate = now()->startOfDay()->toDateTimeString();
            $endDate = now()->endOfDay()->toDateTimeString();
        } elseif ($period === 'yesterday') {
            $startDate = now()->subDay()->startOfDay()->toDateTimeString();
            $endDate = now()->subDay()->endOfDay()->toDateTimeString();
        } elseif ($period === '7days') {
            $startDate = now()->subDays(6)->startOfDay()->toDateTimeString();
            $endDate = now()->endOfDay()->toDateTimeString();
        } elseif ($period === 'this_month' || $period === 'monthly') {
            $startDate = \Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth()->startOfDay()->toDateTimeString();
            $endDate = \Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, 1)->endOfMonth()->endOfDay()->toDateTimeString();
        }

        if ($startDate && $endDate) {
            $startDayStr = substr($startDate, 0, 10);
            $endDayStr = substr($endDate, 0, 10);

            $cashTrxIds = CashTransaction::where('tipe', 'masuk')
                ->whereNotNull('transaction_id')
                ->where(function($q) use ($startDayStr, $endDayStr, $startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDayStr, $endDayStr])
                      ->orWhereBetween('created_at', [$startDate, $endDate]);
                })
                ->pluck('transaction_id')
                ->toArray();

            $paymentTrxIds = TransactionPayment::whereBetween('created_at', [$startDate, $endDate])
                ->pluck('transaction_id')
                ->toArray();

            $allMatchedIds = array_unique(array_filter(array_merge($cashTrxIds, $paymentTrxIds)));

            $query->where(function($q) use ($startDate, $endDate, $allMatchedIds) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->orWhereIn('id', $allMatchedIds);
            });
        }

        // Payment Method Filter
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        // Payment Status Filter
        if ($request->filled('payment_status') && $request->payment_status !== 'all') {
            $query->where('payment_status', $request->payment_status);
        }

        // Calculate Summary Statistics for confirmed sales & settlements in the period
        $summaryBaseQuery = Transaction::query()->whereNotIn('order_status', ['draft', 'cancelled']);
        if ($branchId && $branchId !== 'all') {
            $summaryBaseQuery->where('branch_id', $branchId);
        }

        if ($startDate && $endDate) {
            $startDayStr = substr($startDate, 0, 10);
            $endDayStr = substr($endDate, 0, 10);

            $directCash = (clone $summaryBaseQuery)->whereBetween('created_at', [$startDate, $endDate])->where('payment_method', 'Cash')->sum('paid_amount');
            $directQris = (clone $summaryBaseQuery)->whereBetween('created_at', [$startDate, $endDate])->where('payment_method', 'QRIS')->sum('paid_amount');
            $directTransfer = (clone $summaryBaseQuery)->whereBetween('created_at', [$startDate, $endDate])->where('payment_method', 'Transfer')->sum('paid_amount');
            $directReceivables = (clone $summaryBaseQuery)->whereBetween('created_at', [$startDate, $endDate])->sum('remaining_amount');
            $directTrxCount = (clone $summaryBaseQuery)->whereBetween('created_at', [$startDate, $endDate])->count();

            // Older transactions settlement cash inflows received during this period (strictly from unique CashTransaction records)
            $cashSettlements = CashTransaction::where('tipe', 'masuk')
                ->whereNotNull('transaction_id')
                ->where(function($q) use ($startDayStr, $endDayStr, $startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDayStr, $endDayStr])
                      ->orWhereBetween('created_at', [$startDate, $endDate]);
                })
                ->whereHas('transaction', function($t) use ($startDate, $branchId) {
                    $t->where('created_at', '<', $startDate)
                      ->whereNotIn('order_status', ['draft', 'cancelled'])
                      ->when($branchId && $branchId !== 'all', fn($bq) => $bq->where('branch_id', $branchId));
                })
                ->with('transaction')
                ->get();

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

            $settledTrxIds = array_unique(array_filter($settledTrxIds));
            $olderSettlementsCount = count($settledTrxIds);

            $cashTotal = $directCash + $settleCash;
            $qrisTotal = $directQris + $settleQris;
            $transferTotal = $directTransfer + $settleTransfer;
            $totalPaid = $cashTotal + $qrisTotal + $transferTotal;
            $totalOmset = $totalPaid;
            $totalReceivables = $directReceivables;
            $totalTrx = $directTrxCount + $olderSettlementsCount;
            $cashCount = (clone $summaryBaseQuery)->whereBetween('created_at', [$startDate, $endDate])->where('payment_method', 'Cash')->count() + ($settleCash > 0 ? 1 : 0);
            $qrisCount = (clone $summaryBaseQuery)->whereBetween('created_at', [$startDate, $endDate])->where('payment_method', 'QRIS')->count() + ($settleQris > 0 ? 1 : 0);
            $transferCount = (clone $summaryBaseQuery)->whereBetween('created_at', [$startDate, $endDate])->where('payment_method', 'Transfer')->count() + ($settleTransfer > 0 ? 1 : 0);
        } else {
            $cashTotal = (clone $summaryBaseQuery)->where('payment_method', 'Cash')->sum('paid_amount');
            $cashCount = (clone $summaryBaseQuery)->where('payment_method', 'Cash')->count();
            $qrisTotal = (clone $summaryBaseQuery)->where('payment_method', 'QRIS')->sum('paid_amount');
            $qrisCount = (clone $summaryBaseQuery)->where('payment_method', 'QRIS')->count();
            $transferTotal = (clone $summaryBaseQuery)->where('payment_method', 'Transfer')->sum('paid_amount');
            $transferCount = (clone $summaryBaseQuery)->where('payment_method', 'Transfer')->count();
            $totalOmset = (clone $summaryBaseQuery)->sum('paid_amount');
            $totalPaid = (clone $summaryBaseQuery)->sum('paid_amount');
            $totalReceivables = (clone $summaryBaseQuery)->sum('remaining_amount');
            $totalTrx = (clone $summaryBaseQuery)->count();
        }

        // Count pending drafts for badge display
        $pendingDraftCount = Transaction::query()
            ->where('order_status', 'draft')
            ->when(!$user->isOwner(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->count();

        $transactions = $query->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        if ($startDate && $endDate) {
            $startDayStr = substr($startDate, 0, 10);
            $endDayStr = substr($endDate, 0, 10);
            $transactions->each(function($t) use ($startDate, $endDate, $startDayStr, $endDayStr) {
                $isOlder = $t->created_at < $startDate;
                $t->is_older_settled = false;
                $t->period_settled_amount = 0;
                $t->effective_date = $t->created_at;
                
                if ($isOlder) {
                    // Check TransactionPayment first
                    $pSettle = $t->payments
                        ->filter(fn($p) => $p->created_at >= $startDate && $p->created_at <= $endDate)
                        ->first();

                    if ($pSettle) {
                        $t->is_older_settled = true;
                        $t->period_settled_amount = (float) $pSettle->amount;
                        $t->effective_date = $pSettle->created_at;
                    } else {
                        // Check CashTransaction fallback
                        $cSettle = $t->cashTransactions
                            ->where('tipe', 'masuk')
                            ->filter(function($c) use ($startDayStr, $endDayStr, $startDate, $endDate) {
                                $tgl = $c->tanggal ? substr((string)$c->tanggal, 0, 10) : '';
                                return ($tgl >= $startDayStr && $tgl <= $endDayStr) || ($c->created_at >= $startDate && $c->created_at <= $endDate);
                            })
                            ->first();

                        if ($cSettle) {
                            $t->is_older_settled = true;
                            $t->period_settled_amount = (float) $cSettle->jumlah;
                            $t->effective_date = $cSettle->created_at ?: \Carbon\Carbon::parse($cSettle->tanggal . ' 14:00:00');
                            if ($cSettle->user) {
                                $t->settle_user = $cSettle->user;
                            }
                        }
                    }
                }
            });

            // Re-sort transactions by effective_date so settlements appear chronologically in today's stream!
            $transactions = $transactions->sortByDesc('effective_date')->values();
        }

        $paymentSummary = [
            'period' => $period,
            'cash_total' => $cashTotal,
            'cash_count' => $cashCount,
            'qris_total' => $qrisTotal,
            'qris_count' => $qrisCount,
            'transfer_total' => $transferTotal,
            'transfer_count' => $transferCount,
            'total_omset' => $totalOmset,
            'total_paid' => $totalPaid,
            'total_receivables' => $totalReceivables,
            'total_trx' => $totalTrx,
            'pending_drafts' => $pendingDraftCount,
        ];

        return view('sales.index', compact('transactions', 'branches', 'paymentSummary', 'period', 'statusFilter', 'pendingDraftCount', 'branchId', 'selectedMonth', 'selectedYear'));
    }

    /**
     * Display a listing of Accounts Receivable & Down Payment orders.
     */
    public function receivables(Request $request)
    {
        $user = auth()->user();
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

        $query = Transaction::with(['user', 'branch', 'transactionDetails.material'])
            ->whereNotIn('order_status', ['draft', 'cancelled'])
            ->orderBy('created_at', 'desc');

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        // Filter tab: 'unpaid' (default: partial or unpaid), 'production', 'ready', 'all'
        $tab = $request->input('tab', 'unpaid');
        if ($tab === 'unpaid') {
            $query->where(function($q) {
                $q->where('payment_status', 'PARTIAL')
                  ->orWhere('payment_status', 'UNPAID')
                  ->orWhere('remaining_amount', '>', 0);
            });
        } elseif ($tab === 'production') {
            $query->where('order_status', 'in_production');
        } elseif ($tab === 'ready') {
            $query->where('order_status', 'ready');
        } elseif ($tab === 'paid') {
            $query->where('payment_status', 'PAID');
        }

        $transactions = $query->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        // Calculate KPI Statistics (exclude draft & cancelled)
        $baseStatQuery = Transaction::query()->whereNotIn('order_status', ['draft', 'cancelled']);
        if ($branchId && $branchId !== 'all') {
            $baseStatQuery->where('branch_id', $branchId);
        }

        $totalPiutang = (clone $baseStatQuery)->where('remaining_amount', '>', 0)->sum('remaining_amount');
        $totalDpDiterima = (clone $baseStatQuery)->where('payment_status', 'PARTIAL')->sum('paid_amount');
        $countInProduction = (clone $baseStatQuery)->where('order_status', 'in_production')->count();
        $countReady = (clone $baseStatQuery)->where('order_status', 'ready')->count();

        return view('sales.receivables', compact(
            'transactions',
            'branches',
            'totalPiutang',
            'totalDpDiterima',
            'countInProduction',
            'countReady',
            'tab',
            'branchId'
        ));
    }

    /**
     * Settle remaining receivables / Pelunasan Piutang.
     */
    public function settle(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:Cash,Transfer,QRIS',
            'keterangan' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $transaction = Transaction::findOrFail($id);

            if ($transaction->remaining_amount <= 0) {
                return back()->with('error', 'Transaksi ini sudah lunas sepenuhnya.');
            }

            $settleAmount = min((float) $request->amount, (float) $transaction->remaining_amount);
            $newPaidAmount = $transaction->paid_amount + $settleAmount;
            $newRemainingAmount = max(0, $transaction->total_price - $newPaidAmount);

            $transaction->paid_amount = $newPaidAmount;
            $transaction->remaining_amount = $newRemainingAmount;

            if ($newRemainingAmount <= 0) {
                $transaction->payment_status = 'PAID';
                if ($transaction->order_status === 'in_production' || $transaction->order_status === 'ready') {
                    $transaction->order_status = 'completed';
                }
            } else {
                $transaction->payment_status = 'PARTIAL';
            }

            $transaction->save();

            // Record Payment History & Inflow Cash Transaction for Settlement
            \App\Models\TransactionPayment::create([
                'transaction_id' => $transaction->id,
                'branch_id' => $transaction->branch_id,
                'payer_name' => $transaction->customer_name ?: 'Pelanggan Umum',
                'payment_method' => $request->payment_method ?: 'Cash',
                'amount' => $settleAmount,
                'reference_note' => $request->keterangan ?: "Pelunasan Piutang (#{$transaction->invoice_number})"
            ]);

            $salesAccount = Account::where('kode_akun', '4-1000')->first() ?? Account::where('kode_akun', '1-1300')->first();
            
            CashTransaction::create([
                'branch_id' => $transaction->branch_id,
                'account_id' => $salesAccount ? $salesAccount->id : 1,
                'user_id' => auth()->id(),
                'tipe' => 'masuk',
                'nomor_referensi' => CashTransaction::generateNomorReferensi('masuk'),
                'tanggal' => now()->toDateString(),
                'jumlah' => $settleAmount,
                'keterangan' => "Pelunasan Piutang (#{$transaction->invoice_number}) dari " . ($transaction->customer_name ?: 'Pelanggan') . " (Sisa: Rp " . number_format($newRemainingAmount, 0, ',', '.') . ")",
                'transaction_id' => $transaction->id,
            ]);

            DB::commit();

            return back()->with('success', "Pelunasan sebesar Rp " . number_format($settleAmount, 0, ',', '.') . " berhasil dicatat. Sisa piutang: Rp " . number_format($newRemainingAmount, 0, ',', '.'));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses pelunasan: ' . $e->getMessage());
        }
    }

    /**
     * Update order production status.
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'order_status' => 'required|string|in:in_production,ready,completed,cancelled'
        ]);

        $transaction = Transaction::findOrFail($id);
        $transaction->order_status = $request->order_status;
        $transaction->save();

        return back()->with('success', "Status pengerjaan pesanan #{$transaction->invoice_number} berhasil diperbarui menjadi: " . $transaction->order_status_label);
    }

    /**
     * Show the edit form for a transaction (Owner / Super Admin KINGAshabil).
     */
    public function edit($id)
    {
        $user = auth()->user();
        if (!$user->isOwner() && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $transaction = Transaction::with(['transactionDetails.material', 'branch', 'user'])->findOrFail($id);
        $branches = \App\Models\Branch::orderBy('nama_cabang')->get();
        return view('sales.edit', compact('transaction', 'branches'));
    }

    /**
     * Update the transaction details and adjust stock (Owner / Super Admin KINGAshabil).
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->isOwner() && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'payment_method' => 'required|string|in:Cash,Transfer,QRIS',
            'payment_status' => 'required|string|in:PAID,PARTIAL,UNPAID',
            'order_status' => 'required|string|in:draft,pending,in_production,completed,cancelled',
            'branch_id' => 'nullable|exists:branches,id',
            'due_date' => 'nullable|date',
            'production_notes' => 'nullable|string|max:1000',
            'paid_amount' => 'required|numeric|min:0',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:transaction_details,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.selling_price' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $transaction = Transaction::with('transactionDetails.material')->findOrFail($id);
            $wasDraft = ($transaction->order_status === 'draft');
            $transaction->customer_name = $request->customer_name;
            $transaction->customer_phone = $request->customer_phone;
            $transaction->payment_method = $request->payment_method;
            $transaction->order_status = $request->order_status;
            $transaction->production_notes = $request->production_notes;
            if ($request->filled('due_date')) {
                $transaction->due_date = $request->due_date;
            }
            if ($request->filled('branch_id') && ($user->isOwner() || $user->isSuperAdmin())) {
                $transaction->branch_id = $request->branch_id;
            }

            $totalPrice = 0;
            $totalHpp = 0;

            foreach ($request->items as $itemData) {
                $detail = TransactionDetail::with('material')->findOrFail($itemData['id']);
                $oldQty = $detail->qty_ordered;
                $newQty = (int) $itemData['qty'];

                if ($oldQty !== $newQty) {
                    $diff = $newQty - $oldQty;
                    $material = $detail->material;

                    if ($material) {
                        if ($diff > 0) {
                            $material->stock_qty -= $diff;
                        } else {
                            $material->stock_qty += abs($diff);
                        }
                        $material->save();
                    }

                    $detail->qty_ordered = $newQty;
                }

                if (isset($itemData['selling_price']) && $itemData['selling_price'] !== '') {
                    $detail->selling_price = (float) $itemData['selling_price'];
                }

                $detail->save();

                $totalPrice += ($detail->qty_ordered * $detail->selling_price);
                $totalHpp += ($detail->qty_ordered * ($detail->material->purchase_price ?? 0));
            }

            $transaction->total_price = $totalPrice;
            $transaction->total_hpp = $totalHpp;
            $transaction->paid_amount = (float) $request->paid_amount;
            $transaction->remaining_amount = max(0, $totalPrice - $transaction->paid_amount);
            $transaction->payment_status = $request->payment_status;

            if ($wasDraft && $request->order_status !== 'draft') {
                $transaction->created_at = now();
                foreach ($transaction->transactionDetails as $detail) {
                    $detail->created_at = now();
                    $detail->save();
                }
            }

            $transaction->save();

            DB::commit();

            return redirect()->route('sales.index')->with('success', "Transaksi #{$transaction->invoice_number} berhasil diperbarui oleh Super Admin.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memperbarui transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Void/Refund transaction and restore stock to material inventory (Cashier or Super Admin KINGAshabil).
     */
    public function refund($id)
    {
        $user = auth()->user();
        $transaction = Transaction::with('transactionDetails.material')->findOrFail($id);

        $isSuperAdmin = $user->isSuperAdmin() || $user->isOwner() || ($user->username === 'KINGAshabil');
        $isCashier = $user->isCashier();
        $isDrafter = ($transaction->order_status === 'draft' && (int)$transaction->user_id === (int)$user->id);

        if (!$isSuperAdmin && !$isCashier && !$isDrafter) {
            abort(403, 'Kegiatan pembatalan / penghapusan transaksi hanya dapat dilakukan oleh petugas Kasir, pembuat draft, atau Super Admin KINGAshabil.');
        }

        if (!$isSuperAdmin && !$isDrafter && $transaction->branch_id && $user->branch_id && $transaction->branch_id !== $user->branch_id) {
            abort(403, 'Anda hanya dapat mengelola transaksi pada cabang Anda.');
        }

        try {
            DB::beginTransaction();

            $isDraft = ($transaction->order_status === 'draft');

            // Restore all items stock ONLY IF NOT DRAFT (drafts never deducted stock)
            if (!$isDraft) {
                foreach ($transaction->transactionDetails as $detail) {
                    $material = $detail->material;
                    if ($material) {
                        $material->stock_qty += $detail->qty_ordered;
                        $material->save();
                    }
                }
            }

            $invoiceNumber = $transaction->invoice_number;
            $transaction->transactionDetails()->delete();
            $transaction->delete();

            DB::commit();

            $msg = $isDraft 
                ? "Draft pesanan {$invoiceNumber} berhasil dihapus."
                : "Transaksi {$invoiceNumber} berhasil dihapus/dibatalkan. Seluruh stok bahan telah dikembalikan.";

            return redirect()->back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses penghapusan transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Delete transaction alias (Super Admin KINGAshabil / Cashier).
     */
    public function destroy($id)
    {
        return $this->refund($id);
    }

    /**
     * Display print-optimized thermal receipt view.
     */
    public function receipt($id)
    {
        $transaction = Transaction::with(['user', 'branch', 'transactionDetails.material', 'payments'])->findOrFail($id);
        
        $paymentId = request('payment_id');
        $activePayment = null;
        if ($paymentId && $transaction->payments) {
            $activePayment = $transaction->payments->where('id', $paymentId)->first();
        }

        $allSplit = request()->boolean('all_split');

        return view('sales.receipt', compact('transaction', 'activePayment', 'allSplit'));
    }

    /**
     * Display public invoice page for WhatsApp sharing and direct PDF download.
     */
    public function publicInvoice($invoice_number)
    {
        $transaction = Transaction::with(['user', 'branch', 'transactionDetails.material', 'payments'])
            ->where('invoice_number', $invoice_number)
            ->firstOrFail();

        $paymentId = request('payment_id');
        $activePayment = null;
        if ($paymentId && $transaction->payments) {
            $activePayment = $transaction->payments->where('id', $paymentId)->first();
        }

        return view('sales.public_invoice', compact('transaction', 'activePayment'));
    }

    /**
     * Send PDF Invoice directly to customer WhatsApp via WhatsApp Gateway API (Fonnte).
     */
    public function sendWhatsAppPdf(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480',
            'phone' => 'required|string',
            'invoice_number' => 'required|string',
            'customer_name' => 'nullable|string',
        ]);

        $token = Setting::get('fonnte_token') ?: env('FONNTE_TOKEN');

        if (empty($token)) {
            return response()->json([
                'status' => 'unconfigured',
                'message' => 'Token WhatsApp Gateway (Fonnte) belum disetup. Silakan masukkan token di Pengaturan Profil toko.',
            ], 422);
        }

        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }

        $pdfFile = $request->file('file');
        $invoiceNumber = $request->invoice_number;
        $customerName = $request->customer_name ?: 'Pelanggan';

        $caption = "Halo Kak {$customerName},\nBerikut kami lampirkan berkas resmi Faktur Penjualan Snaprint #{$invoiceNumber}.\nTerima kasih atas kepercayaannya mencetak di Snaprint Digital Printing! 🙏✨";

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->timeout(45)->attach(
                'file',
                file_get_contents($pdfFile->getRealPath()),
                "Faktur_{$invoiceNumber}.pdf"
            )->post('https://api.fonnte.com/send', [
                'target' => $phone,
                'message' => $caption,
                'filename' => "Faktur_{$invoiceNumber}.pdf",
                'countryCode' => '62',
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['status']) && $result['status'] == true) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Berkas Faktur PDF #{$invoiceNumber} berhasil dikirim ke WhatsApp {$phone}!",
                    'detail' => $result,
                ]);
            } else {
                $reason = $result['reason'] ?? ($result['detail'] ?? 'WhatsApp Gateway menolak pengiriman file.');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal dari WhatsApp Gateway: ' . $reason,
                    'detail' => $result,
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kendala koneksi ke server WhatsApp: ' . $e->getMessage(),
            ], 500);
        }
    }
}
