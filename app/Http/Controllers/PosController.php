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
        $branchId = auth()->user()->branch_id;
        if (request()->filled('branch_id') && request('branch_id') !== 'all') {
            $branchId = request('branch_id');
        } elseif (!$branchId) {
            $branchId = \App\Models\Branch::first()->id ?? 1;
        }

        // Load all materials for this branch (excluding Tinta OPEX)
        $materials = Material::with('wholesalePrices')
            ->where('branch_id', $branchId)
            ->where('material_name', 'not like', '%tinta%')
            ->get()
            ->map(function($m) {
                if (empty($m->category)) {
                    $m->category = 'Lainnya';
                }
                return $m;
            })
            ->sortBy(['category', 'material_name'])
            ->values();

        $categories = $materials->pluck('category')->unique()->values();

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
        $isDraft = auth()->user()->isOperator() || $request->boolean('is_draft');

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
            'items.*.custom_unit_price' => 'nullable|numeric|min:0',
            'items.*.eyelet_count' => 'nullable|integer|min:0',
            'items.*.extra_eyelet_cost' => 'nullable|numeric|min:0',
            'items.*.finishing' => 'nullable|string|max:100',
            'items.*.dimension_text' => 'nullable|string|max:100',
            'items.*.qty' => 'required|integer|min:1',
            'payment_method' => $isDraft ? 'nullable|string' : 'required|string|in:Cash,Transfer,QRIS',
            'is_dp' => 'nullable|boolean',
            'dp_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'negotiation_notes' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:150',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:100',
            'due_date' => 'nullable|date',
            'production_notes' => 'nullable|string',
            'is_draft' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $accumulatedPrice = 0;
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
                        })
                        ->first();

                    if (!$custObj) {
                        $custObj = \App\Models\Customer::create([
                            'branch_id' => auth()->user()->branch_id,
                            'name' => $customerName,
                            'phone' => $customerPhone ?: null,
                            'email' => $customerEmail ?: null,
                        ]);
                    }
                    $customerId = $custObj->id;
                }
            }

            // Create Base Transaction
            $transaction = Transaction::create([
                'branch_id' => auth()->user()->branch_id ?: (\App\Models\Branch::first()->id ?? 1),
                'invoice_number' => 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'user_id' => auth()->id(),
                'customer_id' => $customerId,
                'customer_name' => $customerName ?: 'Pelanggan Umum',
                'customer_phone' => $customerPhone ?: null,
                'total_price' => 0,
                'total_hpp' => 0,
                'payment_method' => $isDraft ? 'Draft (Belum Bayar)' : $request->payment_method,
                'payment_status' => $isDraft ? 'UNPAID' : 'PAID',
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'order_status' => $isDraft ? 'draft' : 'completed',
                'due_date' => $request->due_date,
                'production_notes' => $request->production_notes,
            ]);

            $savedItems = [];

            foreach ($request->items as $item) {
                $qty = (int) $item['qty'];
                $isCustomBanner = !empty($item['is_custom_banner']);
                $materialId = $item['material_id'] ?? null;
                $materialName = $item['material_name_or_type'];

                $materialToDeduct = null;
                if ($materialId) {
                    $materialToDeduct = Material::find($materialId);
                }
                if (!$materialToDeduct) {
                    $materialToDeduct = Material::where('material_name', $materialName)
                        ->where('branch_id', auth()->user()->branch_id)
                        ->first();
                }

                if (!$materialToDeduct) {
                    throw new \Exception("Produk '{$materialName}' tidak ditemukan di katalog cabang aktif.");
                }

                $fixedLength = (float) ($item['fixed_length_m'] ?? 0);
                $customWidth = (float) ($item['custom_width_cm'] ?? 0);
                $areaM2 = (float) ($item['billable_area_m2'] ?? $item['area_m2'] ?? 0);
                $physicalAreaM2 = (float) ($item['area_m2'] ?? 0);
                $widthM = (float) ($item['width_m'] ?? 0);
                $lengthM = (float) ($item['length_m'] ?? 0);
                $dimensionText = $item['dimension_text'] ?? null;

                // Wholesale tier price lookup based on qty
                $baseUnitPrice = $materialToDeduct->retail_price;
                $applicableTier = MaterialWholesalePrice::where('material_id', $materialToDeduct->id)
                     ->where('min_qty', '<=', $qty)
                     ->orderBy('min_qty', 'desc')
                     ->first();

                if ($applicableTier) {
                    $baseUnitPrice = $applicableTier->wholesale_price;
                }

                // Eyelet (Mata Ayam) Rule: 4 pcs gratis, jika lebih dari 4 dikenakan 500 perak per pcs
                $eyeletCount = (int) ($item['eyelet_count'] ?? 0);
                $extraEyeletCost = 0;
                if ($eyeletCount > 4) {
                    $extraEyeletCost = ($eyeletCount - 4) * 500;
                }

                // Click charge calculation per unit (if product has machine click charge)
                $clickChargePerUnit = ($materialToDeduct->has_click_charge || (float)($materialToDeduct->click_charge ?? 0) > 0)
                    ? (float)($materialToDeduct->click_charge ?? 0)
                    : 0;

                // Price calculation
                if ($isCustomBanner && $areaM2 > 0) {
                    $unitPrice = round($areaM2 * $baseUnitPrice) + $extraEyeletCost;
                    $itemHpp = (round(($physicalAreaM2 ?: $areaM2) * $materialToDeduct->purchase_price) + $clickChargePerUnit) * $qty;
                } else {
                    $unitPrice = $baseUnitPrice;
                    $itemHpp = ($materialToDeduct->purchase_price + $clickChargePerUnit) * $qty;
                }

                // If negotiated per item: directly override unitPrice with negotiated custom_unit_price
                if (isset($item['custom_unit_price']) && is_numeric($item['custom_unit_price'])) {
                    $unitPrice = round((float) $item['custom_unit_price']);
                }

                $totalItemPrice = $qty * $unitPrice;
                $accumulatedPrice += $totalItemPrice;
                $totalHpp += $itemHpp;

                // Deduct stock only if not draft (stock is deducted upon payment)
                if (!$isDraft) {
                    $stockDeductUnits = $isCustomBanner ? max(1, (int)ceil($physicalAreaM2 * $qty)) : $qty;
                    $materialToDeduct->stock_qty = max(0, $materialToDeduct->stock_qty - $stockDeductUnits);
                    $materialToDeduct->save();
                }

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'material_id' => $materialToDeduct->id,
                    'qty_ordered' => $qty,
                    'selling_price' => $unitPrice,
                    'click_charge' => $clickChargePerUnit,
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

            // Apply Negotiation Discount
            $discountAmount = min($accumulatedPrice, max(0, (float) $request->input('discount_amount', 0)));
            $negotiationNotes = $request->input('negotiation_notes');
            $originalPrice = $accumulatedPrice;
            $totalPrice = max(0, $originalPrice - $discountAmount);

            // Determine Payment & Order Status
            if ($isDraft) {
                $paidAmount = 0;
                $remainingAmount = $totalPrice;
                $paymentStatus = 'UNPAID';
                $orderStatus = 'draft';
            } elseif ($isDp && $totalPrice >= 500000) {
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

            $transaction->original_price = $originalPrice;
            $transaction->discount_amount = $discountAmount;
            $transaction->negotiation_notes = $negotiationNotes;
            $transaction->total_price = $totalPrice;
            $transaction->total_hpp = $totalHpp;
            $transaction->paid_amount = $paidAmount;
            $transaction->remaining_amount = $remainingAmount;
            $transaction->payment_status = $paymentStatus;
            $transaction->order_status = $orderStatus;
            $transaction->save();

            // Record Cash Inflow for the actual paid amount (DP or Full) - Never for draft
            if (!$isDraft && $paidAmount > 0) {
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

            $message = $isDraft 
                ? "Draft pesanan (#{$transaction->invoice_number}) berhasil disimpan! Silakan serahkan nomor invoice ini ke Kasir untuk pembayaran."
                : (($paymentStatus === 'PARTIAL') 
                    ? "Pesanan DP tercatat! Uang muka Rp " . number_format($paidAmount, 0, ',', '.') . " diterima, sisa piutang Rp " . number_format($remainingAmount, 0, ',', '.') 
                    : "Transaksi lunas berhasil diproses. Invoice: " . $transaction->invoice_number);

            return response()->json([
                'status' => 'success',
                'success' => true,
                'is_draft' => $isDraft,
                'message' => $message,
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'customer_name' => $transaction->customer_name,
                'customer_phone' => $transaction->customer_phone,
                'original_price' => $transaction->original_price,
                'discount_amount' => $transaction->discount_amount,
                'negotiation_notes' => $transaction->negotiation_notes,
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
                'public_invoice_url' => route('invoices.public', $transaction->invoice_number),
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

    /**
     * Get pending draft orders for current branch (or all branches for Owner/SuperAdmin).
     */
    public function getDrafts(Request $request)
    {
        $user = auth()->user();
        $query = Transaction::with(['user', 'customer', 'branch', 'transactionDetails.material'])
            ->where('order_status', 'draft')
            ->where('payment_status', 'UNPAID');

        if ($request->filled('branch_id') && $request->branch_id === 'all') {
            // Explicitly requested all branches
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        } elseif ($user->isOwner() || $user->isSuperAdmin()) {
            // Owner / SuperAdmin sees drafts from all branches by default
        } else {
            // Cashiers see their assigned branch drafts (or null branch)
            $branchId = $user->branch_id ?: (\App\Models\Branch::first()->id ?? 1);
            $query->where(function($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            });
        }

        $drafts = $query->orderBy('created_at', 'desc')->get();
        $branches = \App\Models\Branch::orderBy('nama_cabang')->get(['id', 'nama_cabang']);

        return response()->json([
            'status' => 'success',
            'drafts' => $drafts,
            'branches' => $branches,
            'is_owner' => $user->isOwner() || $user->isSuperAdmin() || ($user->username === 'KINGAshabil'),
            'is_super_admin' => $user->isSuperAdmin() || ($user->username === 'KINGAshabil'),
            'is_cashier' => $user->isCashier(),
            'current_user_id' => $user->id,
            'user_branch_id' => $user->branch_id
        ]);
    }

    /**
     * Settle / Pay a draft order directly from POS.
     */
    public function settleDraft(Request $request, $id)
    {
        if (auth()->user()->isOperator()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun operator hanya berhak membuat draft. Pelunasan harus dilakukan oleh akun Kasir.'
            ], 403);
        }

        $request->validate([
            'payment_method' => 'required|string|in:Cash,Transfer,QRIS',
            'is_dp' => 'nullable|boolean',
            'dp_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $transaction = Transaction::with('transactionDetails.material')->findOrFail($id);

            if ($transaction->order_status !== 'draft') {
                throw new \Exception("Pesanan ini sudah bukan draft.");
            }

            // Deduct stock for all items
            foreach ($transaction->transactionDetails as $detail) {
                if ($detail->material) {
                    $material = $detail->material;
                    $isCustomBanner = $detail->area_m2 > 0 && $detail->custom_width_cm > 0;
                    $deduct = $isCustomBanner ? max(1, (int)ceil($detail->area_m2 * $detail->qty_ordered)) : $detail->qty_ordered;
                    $material->stock_qty = max(0, $material->stock_qty - $deduct);
                    $material->save();
                }
            }

            $totalPrice = (float) $transaction->total_price;
            $isDp = $request->boolean('is_dp');
            $requestedDp = $isDp ? (float) $request->input('dp_amount', 0) : 0;

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
                $orderStatus = 'completed';
            }

            $transaction->payment_method = $request->payment_method;
            $transaction->paid_amount = $paidAmount;
            $transaction->remaining_amount = $remainingAmount;
            $transaction->payment_status = $paymentStatus;
            $transaction->order_status = $orderStatus;
            $transaction->save();

            // Record cash inflow
            if ($paidAmount > 0) {
                $salesAccount = \App\Models\Account::where('kode_akun', '4-1000')->first();
                if ($salesAccount) {
                    $keterangan = ($paymentStatus === 'PARTIAL') 
                        ? "Penerimaan DP Uang Muka (#{$transaction->invoice_number}) dari " . ($transaction->customer_name ?: 'Pelanggan') . " (Sisa Piutang: Rp " . number_format($remainingAmount, 0, ',', '.') . ")"
                        : "Penjualan POS (#{$transaction->invoice_number}) dari " . ($transaction->customer_name ?: 'Pelanggan');

                    \App\Models\CashTransaction::create([
                        'branch_id' => auth()->user()->branch_id ?: $transaction->branch_id,
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
                'message' => "Pembayaran draft #{$transaction->invoice_number} berhasil diproses!",
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'receipt_url' => route('sales.receipt', $transaction->id),
                'public_invoice_url' => route('invoices.public', $transaction->invoice_number),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete a draft order (Super Admin KINGAshabil, Kasir, or Drafter).
     */
    public function deleteDraft(Request $request, $id)
    {
        $user = auth()->user();
        $transaction = Transaction::findOrFail($id);

        if ($transaction->order_status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pesanan ini sudah bukan draft dan tidak dapat dihapus melalui antrean draft.'
            ], 400);
        }

        // Authorization check:
        // 1. KINGAshabil / SuperAdmin / Owner
        // 2. Cashier (in their branch or global)
        // 3. Drafter (the user who created this draft: $transaction->user_id === $user->id)
        $isSuperAdmin = $user->isSuperAdmin() || $user->isOwner() || ($user->username === 'KINGAshabil');
        $isCashier = $user->isCashier();
        $isDrafter = ((int)$transaction->user_id === (int)$user->id);

        if (!$isSuperAdmin && !$isCashier && !$isDrafter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin menghapus draft ini. Hanya KINGAshabil, Kasir, atau pembuat draft yang berhak menghapus.'
            ], 403);
        }

        // Cashier branch check (SuperAdmin & Drafter can delete regardless of branch)
        if ($isCashier && !$isSuperAdmin && !$isDrafter) {
            if ($transaction->branch_id && $user->branch_id && $transaction->branch_id !== $user->branch_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kasir hanya dapat menghapus draft pesanan pada cabang sendiri.'
                ], 403);
            }
        }

        try {
            DB::beginTransaction();

            $invoiceNumber = $transaction->invoice_number;
            
            // Delete related details and transaction (draft stock was never deducted, so no stock rollback needed)
            $transaction->transactionDetails()->delete();
            $transaction->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Draft pesanan #{$invoiceNumber} berhasil dihapus."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus draft: ' . $e->getMessage()
            ], 500);
        }
    }
}
