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
        $branchId = auth()->user()->branch_id;

        // Exclude Tinta (OPEX) from POS, filter by branch
        $materials = Material::with('wholesalePrices')
            ->where('branch_id', $branchId)
            ->where('material_name', 'not like', '%tinta%')
            ->orderBy('category', 'asc')
            ->orderBy('material_name', 'asc')
            ->get();

        $categories = Material::where('branch_id', $branchId)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->where('material_name', 'not like', '%tinta%')
            ->distinct()
            ->pluck('category');

        return view('pos.index', compact('materials', 'categories'));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.material_name_or_type' => 'required|string',
            'items.*.requested_size' => 'nullable|numeric|min:0',
            'items.*.fixed_length_m' => 'nullable|numeric|min:0',
            'items.*.custom_width_cm' => 'nullable|numeric|min:0',
            'items.*.area_m2' => 'nullable|numeric|min:0',
            'items.*.dimension_text' => 'nullable|string|max:100',
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
                $fixedLength = !empty($item['fixed_length_m']) ? (float)$item['fixed_length_m'] : (!empty($item['requested_size']) ? (float)$item['requested_size'] : null);
                $customWidth = !empty($item['custom_width_cm']) ? (float)$item['custom_width_cm'] : null;
                $areaM2 = !empty($item['area_m2']) ? (float)$item['area_m2'] : null;
                
                // Find material by name & branch
                $materialQuery = Material::where('branch_id', auth()->user()->branch_id)
                    ->where('material_name', 'like', '%' . $item['material_name_or_type'] . '%');

                if ($fixedLength) {
                    $materialToDeduct = (clone $materialQuery)
                        ->where(function($q) use ($fixedLength) {
                            $q->where('fixed_size', '>=', $fixedLength)
                              ->orWhereNull('fixed_size');
                        })
                        ->orderBy('fixed_size', 'asc')
                        ->first();
                } else {
                    $materialToDeduct = $materialQuery->first();
                }

                if (!$materialToDeduct) {
                    $materialToDeduct = Material::where('branch_id', auth()->user()->branch_id)->first();
                    if (!$materialToDeduct) {
                        throw new \Exception("Bahan {$item['material_name_or_type']} tidak ditemukan di cabang ini.");
                    }
                }

                // Calculate dimensions if it is a banner with custom size
                $isCustomBanner = ($fixedLength && $customWidth);
                if ($isCustomBanner) {
                    if (!$areaM2) {
                        $areaM2 = round($fixedLength * ($customWidth / 100), 3);
                    }
                    $dimensionText = $item['dimension_text'] ?? "{$fixedLength}m x {$customWidth}cm ({$areaM2} m²)";
                } else {
                    $dimensionText = $item['dimension_text'] ?? ($fixedLength ? "Ukuran: {$fixedLength}m" : null);
                }

                // Wholesale tier price lookup based on qty
                $baseUnitPrice = $materialToDeduct->retail_price;
                $applicableTier = MaterialWholesalePrice::where('material_id', $materialToDeduct->id)
                     ->where('min_qty', '<=', $qty)
                     ->orderBy('min_qty', 'desc')
                     ->first();

                if ($applicableTier) {
                    $baseUnitPrice = $applicableTier->wholesale_price;
                }

                // Price calculation
                if ($isCustomBanner && $areaM2 > 0) {
                    $unitPrice = round($areaM2 * $baseUnitPrice);
                    $itemHpp = round($areaM2 * $materialToDeduct->purchase_price) * $qty;
                } else {
                    $unitPrice = $baseUnitPrice;
                    $itemHpp = $materialToDeduct->purchase_price * $qty;
                }

                $totalItemPrice = $qty * $unitPrice;
                $totalPrice += $totalItemPrice;
                $totalHpp += $itemHpp;

                // Deduct stock
                $stockDeductUnits = $isCustomBanner ? max(1, (int)ceil($areaM2 * $qty)) : $qty;
                $materialToDeduct->stock_qty = max(0, $materialToDeduct->stock_qty - $stockDeductUnits);
                $materialToDeduct->save();

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'material_id' => $materialToDeduct->id,
                    'qty_ordered' => $qty,
                    'selling_price' => $unitPrice,
                    'fixed_length_m' => $fixedLength,
                    'custom_width_cm' => $customWidth,
                    'area_m2' => $areaM2,
                    'dimension_text' => $dimensionText,
                ]);

                $savedItems[] = [
                    'material_name' => $materialToDeduct->material_name,
                    'qty_ordered' => $qty,
                    'selling_price' => $unitPrice,
                    'dimension_text' => $dimensionText,
                    'subtotal' => $totalItemPrice
                ];
            }

            // Determine Payment & Order Status based on DP (Hanya berlaku untuk transaksi besar >= Rp 500.000 dan minimal DP 50%)
            if ($isDp && $totalPrice >= 500000) {
                $minDp = round($totalPrice * 0.5);
                if ($requestedDp < $minDp) {
                    throw new \Exception("Nominal Uang Muka (DP) minimal 50% dari total pesanan (Minimal Rp " . number_format($minDp, 0, ',', '.') . ").");
                }
                $paidAmount = $requestedDp;
                $remainingAmount = max(0, $totalPrice - $paidAmount);
                $paymentStatus = 'PARTIAL';
                $orderStatus = 'in_production';
            } else {
                $paidAmount = $totalPrice;
                $remainingAmount = 0;
                $paymentStatus = 'PAID';
                $orderStatus = ($isDp && $totalPrice >= 500000) ? 'in_production' : 'completed';
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
