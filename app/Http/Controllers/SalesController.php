<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Material;
use App\Models\MaterialWholesalePrice;
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
            // Owner can see all history, or filter by branch
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $query->where('branch_id', $request->branch_id);
            }
        } else {
            // Cashier can only see transactions from their own branch
            $query->where('branch_id', $user->branch_id);
        }

        $transactions = $query->get();
        $branches = \App\Models\Branch::orderBy('nama_cabang')->get();

        return view('sales.index', compact('transactions', 'branches'));
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
                            // Increasing quantity ordered -> deduct stock
                            if ($material->stock_qty < $diff) {
                                throw new \Exception("Insufficient stock for {$material->material_name}. Need {$diff} more, but only {$material->stock_qty} left.");
                            }
                            $material->stock_qty -= $diff;
                        } else {
                            // Decreasing quantity ordered -> restore stock (diff is negative)
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
                
                // Fetch the highest min_qty tier for the updated qty
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

            // Deleting the transaction cascades details deletions
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
