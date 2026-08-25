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
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    /**
     * Display a listing of completed sales.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Transaction::with(['user', 'branch', 'transactionDetails.material'])
            ->orderBy('created_at', 'desc');

        if ($user->isOwner()) {
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $query->where('branch_id', $request->branch_id);
            }
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        // Period filter (today, yesterday, 7days, this_month, all, or custom date range)
        $period = $request->input('period', 'today');
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereDate('created_at', '>=', $request->date_from)
                  ->whereDate('created_at', '<=', $request->date_to);
            $period = 'custom';
        } elseif ($period === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($period === 'yesterday') {
            $query->whereDate('created_at', now()->subDay()->toDateString());
        } elseif ($period === '7days') {
            $query->where('created_at', '>=', now()->subDays(6)->startOfDay());
        } elseif ($period === 'this_month') {
            $query->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
        }

        // Payment Method Filter
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        // Payment Status Filter
        if ($request->filled('payment_status') && $request->payment_status !== 'all') {
            $query->where('payment_status', $request->payment_status);
        }

        // Calculate Summary Statistics for current filtered scope
        $summaryQuery = clone $query;
        $cashTotal = (clone $summaryQuery)->where('payment_method', 'Cash')->sum('paid_amount');
        $cashCount = (clone $summaryQuery)->where('payment_method', 'Cash')->count();

        $qrisTotal = (clone $summaryQuery)->where('payment_method', 'QRIS')->sum('paid_amount');
        $qrisCount = (clone $summaryQuery)->where('payment_method', 'QRIS')->count();

        $transferTotal = (clone $summaryQuery)->where('payment_method', 'Transfer')->sum('paid_amount');
        $transferCount = (clone $summaryQuery)->where('payment_method', 'Transfer')->count();

        $totalOmset = (clone $summaryQuery)->sum('total_price');
        $totalPaid = (clone $summaryQuery)->sum('paid_amount');
        $totalReceivables = (clone $summaryQuery)->sum('remaining_amount');
        $totalTrx = (clone $summaryQuery)->count();

        $transactions = $query->get();
        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();

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
        ];

        return view('sales.index', compact('transactions', 'branches', 'paymentSummary', 'period'));
    }

    /**
     * Display a listing of Accounts Receivable & Down Payment orders.
     */
    public function receivables(Request $request)
    {
        $user = auth()->user();
        $query = Transaction::with(['user', 'branch', 'transactionDetails.material'])
            ->orderBy('created_at', 'desc');

        if ($user->isOwner()) {
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $query->where('branch_id', $request->branch_id);
            }
        } else {
            $query->where('branch_id', $user->branch_id);
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
        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();

        // Calculate KPI Statistics
        $baseStatQuery = Transaction::query();
        if ($user->isOwner()) {
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $baseStatQuery->where('branch_id', $request->branch_id);
            }
        } else {
            $baseStatQuery->where('branch_id', $user->branch_id);
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
            'tab'
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

            // Record Inflow Cash Transaction for Settlement
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
     * Show the edit form for a transaction (Owner only).
     */
    public function edit($id)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Unauthorized access.');
        }

        $transaction = Transaction::with('transactionDetails.material')->findOrFail($id);
        return view('sales.edit', compact('transaction'));
    }

    /**
     * Update the transaction details and adjust stock (Owner only).
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'payment_method' => 'required|string|in:Cash,Transfer,QRIS',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:transaction_details,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $transaction = Transaction::with('transactionDetails.material')->findOrFail($id);
            $transaction->payment_method = $request->payment_method;

            foreach ($request->items as $itemData) {
                $detail = TransactionDetail::with('material')->findOrFail($itemData['id']);
                $oldQty = $detail->qty_ordered;
                $newQty = (int) $itemData['qty'];

                if ($oldQty !== $newQty) {
                    $diff = $newQty - $oldQty;
                    $material = $detail->material;

                    if ($material) {
                        if ($diff > 0) {
                            if ($material->stock_qty < $diff) {
                                throw new \Exception("Insufficient stock for {$material->material_name}. Need {$diff} more, but only {$material->stock_qty} left.");
                            }
                            $material->stock_qty -= $diff;
                        } else {
                            $material->stock_qty += abs($diff);
                        }
                        $material->save();
                    }

                    $detail->qty_ordered = $newQty;
                    $detail->save();
                }
            }

            // Reload details to compute fresh totals with correct wholesale tier pricing
            $transaction->load('transactionDetails.material');
            
            $totalPrice = 0;
            $totalHpp = 0;

            foreach ($transaction->transactionDetails as $d) {
                $unitPrice = $d->material->retail_price;
                
                $applicableTier = MaterialWholesalePrice::where('material_id', $d->material_id)
                    ->where('min_qty', '<=', $d->qty_ordered)
                    ->orderBy('min_qty', 'desc')
                    ->first();

                if ($applicableTier) {
                    $unitPrice = $applicableTier->wholesale_price;
                }

                $d->selling_price = $unitPrice;
                $d->save();

                $totalPrice += ($d->qty_ordered * $unitPrice);
                $totalHpp += ($d->qty_ordered * $d->material->purchase_price);
            }

            $transaction->total_price = $totalPrice;
            $transaction->total_hpp = $totalHpp;
            
            // Adjust remaining amount based on new total price and current paid amount
            $transaction->remaining_amount = max(0, $totalPrice - $transaction->paid_amount);
            if ($transaction->remaining_amount <= 0) {
                $transaction->payment_status = 'PAID';
            } else {
                $transaction->payment_status = 'PARTIAL';
            }

            $transaction->save();

            DB::commit();

            return redirect()->route('sales.index')->with('success', "Transaction {$transaction->invoice_number} updated successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Void/Refund transaction and restore stock to material inventory (Owner only).
     */
    public function refund($id)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Unauthorized access.');
        }

        try {
            DB::beginTransaction();

            $transaction = Transaction::with('transactionDetails.material')->findOrFail($id);

            // Restore all items stock
            foreach ($transaction->transactionDetails as $detail) {
                $material = $detail->material;
                if ($material) {
                    $material->stock_qty += $detail->qty_ordered;
                    $material->save();
                }
            }

            $invoiceNumber = $transaction->invoice_number;
            $transaction->delete();

            DB::commit();

            return redirect()->route('sales.index')->with('success', "Transaction {$invoiceNumber} has been successfully voided/refunded. All stocks restored.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('sales.index')->with('error', 'Failed to refund transaction: ' . $e->getMessage());
        }
    }

    /**
     * Display print-optimized thermal receipt view.
     */
    public function receipt($id)
    {
        $transaction = Transaction::with(['user', 'transactionDetails.material'])->findOrFail($id);
        return view('sales.receipt', compact('transaction'));
    }
}
