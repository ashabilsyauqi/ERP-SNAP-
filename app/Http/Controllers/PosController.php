<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\MaterialWholesalePrice;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class PosController extends Controller
{
    public function index()
    {
        if (!auth()->user()->isSuperAdmin() && (auth()->user()->role === 'owner' || auth()->user()->isManager())) {
            return redirect()->route('owner.dashboard')->with('info', 'Fitur terminal kasir POS khusus untuk akun Kasir. Silakan gunakan menu Penjualan atau Dashboard Toko untuk memantau transaksi cabang.');
        }

        $branchId = auth()->user()->branch_id;
        if (request()->filled('branch_id') && request('branch_id') !== 'all') {
            $branchId = request('branch_id');
        } elseif (!$branchId) {
            $branchId = \App\Models\Branch::first()->id ?? 1;
        }

        // Exclude Tinta (OPEX) from POS, filter by branch
        $materials = Material::with('wholesalePrices')
            ->where('branch_id', $branchId)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->where('material_name', 'not like', '%tinta%')
            ->orderBy('category')
            ->orderBy('material_name')
            ->get();

        $categories = Material::where('branch_id', $branchId)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->where('material_name', 'not like', '%tinta%')
            ->distinct()
            ->pluck('category');

        $customers = collect();
        if (Schema::hasTable('customers')) {
            $customers = \App\Models\Customer::where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)->orWhereNull('branch_id');
                })
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'phone', 'email']);
        }

        return view('pos.index', compact('materials', 'categories', 'customers'));
    }

    public function checkout(Request $request)
    {
        if (!auth()->user()->isSuperAdmin() && (auth()->user()->role === 'owner' || auth()->user()->isManager())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaksi kasir POS hanya dapat diproses oleh akun Kasir.'
            ], 403);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'nullable|integer',
            'items.*.material_name_or_type' => 'required|string',
            'items.*.requested_size' => 'nullable|numeric|min:0',
            'items.*.width_m' => 'nullable|numeric|min:0',
            'items.*.length_m' => 'nullable|numeric|min:0',
            'items.*.fixed_length_m' => 'nullable|numeric|min:0',
            'items.*.custom_width_cm' => 'nullable|numeric|min:0',
            'items.*.area_m2' => 'nullable|numeric|min:0',
            'items.*.billable_area_m2' => 'nullable|numeric|min:0',
            'items.*.is_custom_banner' => 'nullable|boolean',
            'items.*.finishing' => 'nullable|string|max:100',
            'items.*.dimension_text' => 'nullable|string|max:100',
            'items.*.qty' => 'required|integer|min:1',
            'payment_method' => 'required|string|in:Cash,Transfer,QRIS',
            'is_dp' => 'nullable|boolean',
            'dp_amount' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:150',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:100',
            'due_date' => 'nullable|date',
            'production_notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $totalPrice = 0;
            $totalHpp = 0;

            $isDp = $request->boolean('is_dp');
            $requestedDp = $isDp ? (float) $request->input('dp_amount', 0) : 0;

            // Resolve or Auto-Create Customer Record
            $customerId = $request->input('customer_id');
            $customerName = trim($request->input('customer_name', ''));
            $customerPhone = trim($request->input('customer_phone', ''));
            $customerEmail = trim($request->input('customer_email', ''));

            if (Schema::hasTable('customers')) {
                if (!empty($customerId)) {
                    $custObj = \App\Models\Customer::find($customerId);
                    if ($custObj) {
                        $customerName = $custObj->name;
                        if (empty($customerPhone)) $customerPhone = $custObj->phone;
                        if (empty($customerEmail)) $customerEmail = $custObj->email;
                    }
                } elseif (!empty($customerName)) {
                    // Search if customer already exists by exact name in current branch / global
                    $custObj = \App\Models\Customer::where('name', $customerName)
                        ->where(function($b) {
                            $b->where('branch_id', auth()->user()->branch_id)->orWhereNull('branch_id');
                        })->first();

                    if (!$custObj && !empty($customerPhone)) {
                        $custObj = \App\Models\Customer::where('phone', $customerPhone)->first();
                    }

                    if (!$custObj) {
                        // Auto-create new customer!
                        $custObj = \App\Models\Customer::create([
                            'name' => $customerName,
                            'phone' => $customerPhone ?: null,
                            'email' => $customerEmail ?: null,
                            'branch_id' => auth()->user()->branch_id,
                        ]);
                    } else {
                        // Update contact details if provided
                        if (empty($custObj->phone) && !empty($customerPhone)) {
                            $custObj->phone = $customerPhone;
                        }
                        if (empty($custObj->email) && !empty($customerEmail)) {
                            $custObj->email = $customerEmail;
                        }
                        $custObj->save();
                    }

                    $customerId = $custObj->id;
                }
            }

            $transaction = Transaction::create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'user_id' => auth()->id(),
                'branch_id' => auth()->user()->branch_id,
                'customer_id' => $customerId ?: null,
                'customer_name' => $customerName ?: null,
                'customer_phone' => $customerPhone ?: null,
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
                $qty = (int)$item['qty'];
                
                // Deterministic material lookup: by material_id, exact name, or partial name
                $materialToDeduct = null;
                if (!empty($item['material_id'])) {
                    $materialToDeduct = Material::where('branch_id', auth()->user()->branch_id)->find($item['material_id']);
                }

                if (!$materialToDeduct) {
                    $materialToDeduct = Material::where('branch_id', auth()->user()->branch_id)
                        ->where('material_name', $item['material_name_or_type'])
                        ->first();
                }

                if (!$materialToDeduct) {
                    $materialToDeduct = Material::where('branch_id', auth()->user()->branch_id)
                        ->where('material_name', 'like', '%' . $item['material_name_or_type'] . '%')
                        ->first();
                }

                if (!$materialToDeduct) {
                    $materialToDeduct = Material::where('branch_id', auth()->user()->branch_id)->first();
                }

                if (!$materialToDeduct) {
                    throw new \Exception("Bahan {$item['material_name_or_type']} tidak ditemukan di cabang ini.");
                }

                // Calculate dimensions if it is a banner with custom size
                $widthM = !empty($item['width_m']) ? (float)$item['width_m'] : (!empty($item['fixed_length_m']) ? (float)$item['fixed_length_m'] : null);
                $lengthM = !empty($item['length_m']) ? (float)$item['length_m'] : (!empty($item['custom_width_cm']) ? (float)($item['custom_width_cm'] / 100) : null);
                $customWidth = !empty($item['custom_width_cm']) ? (float)$item['custom_width_cm'] : ($lengthM ? round($lengthM * 100) : null);
                $fixedLength = !empty($item['fixed_length_m']) ? (float)$item['fixed_length_m'] : (!empty($item['requested_size']) ? (float)$item['requested_size'] : ($widthM ?: null));
                $isCustomBanner = !empty($item['is_custom_banner']) || ($widthM && $lengthM && $widthM > 0 && $lengthM > 0);

                if ($isCustomBanner) {
                    $billableWidth = max(1.0, $widthM ?: 1.0);
                    $billableLength = max(1.0, $lengthM ?: 1.0);
                    $billableAreaM2 = !empty($item['billable_area_m2']) ? (float)$item['billable_area_m2'] : round($billableWidth * $billableLength, 3);
                    $physicalAreaM2 = !empty($item['area_m2']) ? (float)$item['area_m2'] : round(($widthM ?: 1.0) * ($lengthM ?: 1.0), 3);
                    
                    $dimensionText = $item['dimension_text'] ?? "{$widthM}m x {$lengthM}m ({$physicalAreaM2} m²)";
                    $areaM2 = $billableAreaM2;
                } else {
                    $dimensionText = $item['dimension_text'] ?? ($fixedLength ? "Ukuran: {$fixedLength}m" : null);
                    $physicalAreaM2 = !empty($item['area_m2']) ? (float)$item['area_m2'] : null;
                    $areaM2 = $physicalAreaM2;
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
                    $itemHpp = round(($physicalAreaM2 ?: $areaM2) * $materialToDeduct->purchase_price) * $qty;
                } else {
                    $unitPrice = $baseUnitPrice;
                    $itemHpp = $materialToDeduct->purchase_price * $qty;
                }

                $totalItemPrice = $qty * $unitPrice;
                $totalPrice += $totalItemPrice;
                $totalHpp += $itemHpp;

                // Deduct stock (based on physical area consumed)
                $stockDeductUnits = $isCustomBanner ? max(1, (int)ceil($physicalAreaM2 * $qty)) : $qty;
                $materialToDeduct->stock_qty = max(0, $materialToDeduct->stock_qty - $stockDeductUnits);
                $materialToDeduct->save();

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'material_id' => $materialToDeduct->id,
                    'qty_ordered' => $qty,
                    'selling_price' => $unitPrice,
                    'fixed_length_m' => $widthM ?: $fixedLength,
                    'custom_width_cm' => $customWidth,
                    'area_m2' => $physicalAreaM2 ?: $areaM2,
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
                        : "Penjualan POS (#{$transaction->invoice_number}) dari " . ($transaction->customer_name ?: 'Pelanggan');

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
