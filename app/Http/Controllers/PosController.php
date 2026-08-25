<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\MaterialWholesalePrice;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function index()
    {
        // Exclude Tinta (OPEX) from POS, filter by branch
        $materials = Material::with('wholesalePrices')
            ->where('branch_id', auth()->user()->branch_id)
            ->where('material_name', 'not like', '%tinta%')
            ->get();
        return view('pos.index', compact('materials'));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.material_name_or_type' => 'required|string',
            'items.*.requested_size' => 'nullable|numeric|min:0', // for banners
            'items.*.qty' => 'required|integer|min:1',
            'payment_method' => 'required|string|in:Cash,Transfer,QRIS',
            'is_dp' => 'nullable|boolean',
            'dp_amount' => 'nullable|numeric|min:0',
            'customer_name' => 'nullable|string|max:150',
            'customer_phone' => 'nullable|string|max:50',
            'due_date' => 'nullable|date',
            'production_notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $totalPrice = 0;
            $totalHpp = 0;

            $isDp = $request->boolean('is_dp');
            $requestedDp = $isDp ? (float) $request->input('dp_amount', 0) : 0;

            $transaction = Transaction::create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'user_id' => auth()->id(),
                'branch_id' => auth()->user()->branch_id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'total_price' => 0,
                'total_hpp' => 0,
                'payment_method' => $request->payment_method,
                'payment_status' => 'PAID',
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'order_status' => 'completed',
                'due_date' => $request->due_date,
                'production_notes' => $request->production_notes,
            ]);

            $savedItems = [];

            foreach ($request->items as $item) {
                $qty = $item['qty'];
                $requestedSize = $item['requested_size'] ?? null;
                
                $materialToDeduct = null;

                if ($requestedSize) {
                    $materialToDeduct = Material::where('branch_id', auth()->user()->branch_id)
                        ->where('material_name', 'like', '%' . $item['material_name_or_type'] . '%')
                        ->where('fixed_size', '>=', $requestedSize)
                        ->where('stock_qty', '>=', $qty)
                        ->orderBy('fixed_size', 'asc')
                        ->first();

                    if (!$materialToDeduct) {
                        throw new \Exception("Stok tidak mencukupi untuk {$item['material_name_or_type']} ukuran >= {$requestedSize}m (butuh $qty unit).");
                    }
                } else {
                    $materialToDeduct = Material::where('branch_id', auth()->user()->branch_id)
                        ->where('material_name', 'like', '%' . $item['material_name_or_type'] . '%')
                        ->where('stock_qty', '>=', $qty)
                        ->first();
                        
                    if (!$materialToDeduct) {
                        throw new \Exception("Stok tidak mencukupi untuk {$item['material_name_or_type']} (butuh $qty unit).");
                    }
                }

                // Wholesale Auto-Calculation Logic
                $unitPrice = $materialToDeduct->retail_price;
                
                // Fetch the highest min_qty tier that the requested qty satisfies
                $applicableTier = MaterialWholesalePrice::where('material_id', $materialToDeduct->id)
                     ->where('min_qty', '<=', $qty)
                     ->orderBy('min_qty', 'desc')
                     ->first();

                if ($applicableTier) {
                    $unitPrice = $applicableTier->wholesale_price;
                }

                $totalItemPrice = $qty * $unitPrice;
                $totalPrice += $totalItemPrice;

                // HPP Calculation
                $itemHpp = $materialToDeduct->purchase_price * $qty;
                $totalHpp += $itemHpp;

                // Deduct stock
                $materialToDeduct->stock_qty -= $qty;
                $materialToDeduct->save();

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'material_id' => $materialToDeduct->id,
                    'qty_ordered' => $qty,
                    'selling_price' => $unitPrice,
                ]);

                $savedItems[] = [
                    'material_name' => $materialToDeduct->material_name,
                    'qty_ordered' => $qty,
                    'selling_price' => $unitPrice,
                    'subtotal' => $totalItemPrice
                ];
            }

            // Determine Payment & Order Status based on DP
            if ($isDp && $requestedDp < $totalPrice && $requestedDp >= 0) {
                $paidAmount = $requestedDp;
                $remainingAmount = max(0, $totalPrice - $paidAmount);
                $paymentStatus = 'PARTIAL';
                $orderStatus = 'in_production';
            } else {
                $paidAmount = $totalPrice;
                $remainingAmount = 0;
                $paymentStatus = 'PAID';
                $orderStatus = $isDp ? 'in_production' : 'completed';
            }

            $transaction->total_price = $totalPrice;
            $transaction->total_hpp = $totalHpp;
            $transaction->paid_amount = $paidAmount;
            $transaction->remaining_amount = $remainingAmount;
            $transaction->payment_status = $paymentStatus;
            $transaction->order_status = $orderStatus;
            $transaction->save();

            // Record Cash Inflow for the actual paid amount (DP or Full)
            if ($paidAmount > 0) {
                $salesAccount = \App\Models\Account::where('kode_akun', '4-1000')->first();
                if ($salesAccount) {
                    $keterangan = ($paymentStatus === 'PARTIAL') 
                        ? "Penerimaan DP Uang Muka (#{$transaction->invoice_number}) dari " . ($transaction->customer_name ?: 'Pelanggan') . " (Sisa Piutang: Rp " . number_format($remainingAmount, 0, ',', '.') . ")"
                        : "Pemasukan Penjualan POS (#{$transaction->invoice_number}) dari " . ($transaction->customer_name ?: 'Pelanggan');

                    \App\Models\CashTransaction::create([
                        'branch_id' => auth()->user()->branch_id,
                        'account_id' => $salesAccount->id,
                        'user_id' => auth()->id(),
                        'tipe' => 'masuk',
                        'nomor_referensi' => \App\Models\CashTransaction::generateNomorReferensi('masuk'),
                        'tanggal' => now()->toDateString(),
                        'jumlah' => $paidAmount,
                        'keterangan' => $keterangan,
                        'transaction_id' => $transaction->id,
                    ]);
                }
            }

            // Record HPP Outflow (COGS)
            $hppAccount = \App\Models\Account::where('kode_akun', '6-1000')->first();
            if ($hppAccount && $totalHpp > 0) {
                \App\Models\CashTransaction::create([
                    'branch_id' => auth()->user()->branch_id,
                    'account_id' => $hppAccount->id,
                    'user_id' => auth()->id(),
                    'tipe' => 'keluar',
                    'nomor_referensi' => \App\Models\CashTransaction::generateNomorReferensi('keluar'),
                    'tanggal' => now()->toDateString(),
                    'jumlah' => $totalHpp,
                    'keterangan' => 'Harga Pokok Penjualan (HPP) dari invoice ' . $transaction->invoice_number,
                    'transaction_id' => $transaction->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => ($paymentStatus === 'PARTIAL') 
                    ? "Pesanan DP tercatat! Uang muka Rp " . number_format($paidAmount, 0, ',', '.') . " diterima, sisa piutang Rp " . number_format($remainingAmount, 0, ',', '.') 
                    : "Transaksi lunas berhasil diproses. Invoice: " . $transaction->invoice_number,
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'customer_name' => $transaction->customer_name,
                'customer_phone' => $transaction->customer_phone,
                'total_price' => $transaction->total_price,
                'paid_amount' => $transaction->paid_amount,
                'remaining_amount' => $transaction->remaining_amount,
                'payment_status' => $transaction->payment_status,
                'order_status' => $transaction->order_status,
                'payment_method' => $transaction->payment_method,
                'due_date' => $transaction->due_date ? $transaction->due_date->format('d M Y') : null,
                'production_notes' => $transaction->production_notes,
                'cashier_name' => auth()->user()->full_name ?: (auth()->user()->username ?? 'Kasir'),
                'branch_name' => auth()->user()->branch->nama_cabang ?? 'Pusat',
                'created_at' => $transaction->created_at->format('d M Y H:i'),
                'items' => $savedItems,
                'receipt_url' => route('sales.receipt', $transaction->id),
                'redirect' => route('pos.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
