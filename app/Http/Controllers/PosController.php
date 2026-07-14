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
        // Exclude Tinta (OPEX) from POS
        $materials = Material::with('wholesalePrices')->where('material_name', 'not like', '%tinta%')->get();
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
        ]);

        try {
            DB::beginTransaction();

            $totalPrice = 0;
            $totalHpp = 0;

            $transaction = Transaction::create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'user_id' => auth()->id(),
                'branch_id' => auth()->user()->branch_id,
                'total_price' => 0,
                'total_hpp' => 0,
                'payment_method' => $request->payment_method,
            ]);

            foreach ($request->items as $item) {
                $qty = $item['qty'];
                $requestedSize = $item['requested_size'] ?? null;
                
                $materialToDeduct = null;

                if ($requestedSize) {
                    $materialToDeduct = Material::where('material_name', 'like', '%' . $item['material_name_or_type'] . '%')
                        ->where('fixed_size', '>=', $requestedSize)
                        ->where('stock_qty', '>=', $qty)
                        ->orderBy('fixed_size', 'asc')
                        ->first();

                    if (!$materialToDeduct) {
                        throw new \Exception("Insufficient stock for {$item['material_name_or_type']} with size >= {$requestedSize}m (need $qty units).");
                    }
                } else {
                    $materialToDeduct = Material::where('material_name', 'like', '%' . $item['material_name_or_type'] . '%')
                        ->where('stock_qty', '>=', $qty)
                        ->first();
                        
                    if (!$materialToDeduct) {
                        throw new \Exception("Insufficient stock for {$item['material_name_or_type']}.");
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
            }

            $transaction->total_price = $totalPrice;
            $transaction->total_hpp = $totalHpp;
            $transaction->save();

            // Record Cash Inflow (Sales)
            $salesAccount = \App\Models\Account::where('kode_akun', '4-1000')->first();
            if ($salesAccount) {
                \App\Models\CashTransaction::create([
                    'branch_id' => auth()->user()->branch_id,
                    'account_id' => $salesAccount->id,
                    'user_id' => auth()->id(),
                    'tipe' => 'masuk',
                    'nomor_referensi' => \App\Models\CashTransaction::generateNomorReferensi('masuk'),
                    'tanggal' => now()->toDateString(),
                    'jumlah' => $totalPrice,
                    'keterangan' => 'Pemasukan POS dari invoice ' . $transaction->invoice_number,
                    'transaction_id' => $transaction->id,
                ]);
            }

            // Record HPP Outflow (COGS)
            $hppAccount = \App\Models\Account::where('kode_akun', '6-1000')->first();
            if ($hppAccount) {
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
                'success' => true,
                'message' => 'Checkout successful. Invoice: ' . $transaction->invoice_number,
                'transaction_id' => $transaction->id,
                'redirect' => route('pos.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
