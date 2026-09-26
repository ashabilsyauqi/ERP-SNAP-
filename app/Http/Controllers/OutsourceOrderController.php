<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OutsourceOrder;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\CashTransaction;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OutsourceOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();

        // Branch filter
        if (!$isOwnerOrSuper) {
            $branchId = $user->branch_id;
        } else {
            $branchId = $request->input('branch_id', session('selected_branch_id', 'all'));
            if ($request->has('branch_id')) {
                session(['selected_branch_id' => $branchId]);
            }
        }

        $branches = Branch::all();
        $selectedBranch = ($branchId && $branchId !== 'all') ? Branch::find($branchId) : null;

        // Base Query
        $query = OutsourceOrder::with(['branch', 'user', 'customer', 'approver', 'qcUser', 'completedBy', 'transaction']);

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('job_title', 'like', "%{$search}%")
                  ->orWhere('vendor_name', 'like', "%{$search}%");
            });
        }

        // Tab / Status Filter
        $activeTab = $request->input('tab', 'all');
        if ($activeTab !== 'all') {
            $query->where('status', $activeTab);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        // Summary Metric Counts
        $countQuery = OutsourceOrder::query();
        if ($branchId && $branchId !== 'all') {
            $countQuery->where('branch_id', $branchId);
        }

        $allOrdersForCounts = $countQuery->get();
        $counts = [
            'all' => $allOrdersForCounts->count(),
            'draft_customer' => $allOrdersForCounts->where('status', 'draft_customer')->count(),
            'pending_approval' => $allOrdersForCounts->where('status', 'pending_approval')->count(),
            'in_production' => $allOrdersForCounts->where('status', 'in_production')->count(),
            'qc_passed' => $allOrdersForCounts->where('status', 'qc_passed')->count(),
            'completed' => $allOrdersForCounts->where('status', 'completed')->count(),
            'rejected' => $allOrdersForCounts->where('status', 'rejected')->count(),
            'total_customer_omset' => $allOrdersForCounts->whereIn('status', ['in_production', 'qc_passed', 'completed'])->sum('customer_price'),
            'total_realized_profit' => $allOrdersForCounts->where('status', 'completed')->sum('estimated_margin'),
        ];

        // Customers for dropdown
        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone', 'email']);

        return view('outsource_orders.index', compact(
            'orders',
            'branches',
            'selectedBranch',
            'branchId',
            'activeTab',
            'counts',
            'customers',
            'isOwnerOrSuper'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'job_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'qty' => 'required|integer|min:1',
            'unit' => 'nullable|string|max:50',
            'customer_unit_price' => 'nullable|numeric|min:0',
            'customer_price' => 'required|numeric|min:0',
            'customer_name' => 'required|string|max:150',
            'customer_phone' => 'nullable|string|max:50',
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method' => 'required|string|in:Cash,Transfer,QRIS',
            'is_dp' => 'nullable|boolean',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $branchId = $user->branch_id ?: (\App\Models\Branch::first()->id ?? 1);

            // Auto-create or resolve Customer
            $customerId = $request->input('customer_id');
            $customerName = trim($request->input('customer_name'));
            $customerPhone = trim($request->input('customer_phone', ''));

            if (!empty($customerId)) {
                $cust = Customer::find($customerId);
                if ($cust) {
                    $customerName = $cust->name;
                    if (empty($customerPhone)) $customerPhone = $cust->phone;
                }
            } elseif (!empty($customerName)) {
                $cust = Customer::firstOrCreate(
                    ['name' => $customerName],
                    ['branch_id' => $branchId, 'phone' => $customerPhone ?: null]
                );
                $customerId = $cust->id;
            }

            $qty = (int) $request->input('qty', 1);
            $customerPrice = (float) $request->input('customer_price', 0);
            $customerUnitPrice = (float) $request->input('customer_unit_price', ($qty > 0 ? round($customerPrice / $qty, 2) : $customerPrice));

            $isDp = $request->boolean('is_dp');
            $paidAmount = $isDp ? (float) $request->input('paid_amount', 0) : $customerPrice;
            $remainingAmount = max(0, $customerPrice - $paidAmount);
            $paymentStatus = ($paidAmount >= $customerPrice) ? 'PAID' : ($paidAmount > 0 ? 'PARTIAL' : 'UNPAID');

            $order = OutsourceOrder::create([
                'order_number' => OutsourceOrder::generateOrderNumber(),
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone ?: null,
                'job_title' => $request->input('job_title'),
                'description' => $request->input('description'),
                'qty' => $qty,
                'unit' => $request->input('unit', 'pcs') ?: 'pcs',
                'customer_unit_price' => $customerUnitPrice,
                'customer_price' => $customerPrice,
                'payment_method' => $request->input('payment_method', 'Cash'),
                'payment_status' => $paymentStatus,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'status' => 'draft_customer',
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "Pesanan Customer #{$order->order_number} berhasil dibuat!",
                'order' => $order,
                'receipt_url' => route('outsource-orders.receipt', $order->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Gagal membuat pesanan: ' . $e->getMessage()
            ], 400);
        }
    }

    public function show($id)
    {
        $order = OutsourceOrder::with(['branch', 'user', 'customer', 'approver', 'qcUser', 'completedBy', 'transaction'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'success' => true,
            'order' => $order,
            'receipt_url' => route('outsource-orders.receipt', $order->id),
            'pos_receipt_url' => $order->transaction_id ? route('sales.receipt', $order->transaction_id) : null,
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $order = OutsourceOrder::findOrFail($id);

            // If updating customer job specs
            if ($request->has('job_title')) {
                $order->job_title = $request->input('job_title', $order->job_title);
            }
            if ($request->has('description')) {
                $order->description = $request->input('description');
            }
            if ($request->has('qty')) {
                $order->qty = (int) $request->input('qty', $order->qty);
            }
            if ($request->has('unit')) {
                $order->unit = $request->input('unit', $order->unit);
            }
            if ($request->has('customer_unit_price')) {
                $order->customer_unit_price = (float) $request->input('customer_unit_price', $order->customer_unit_price);
            }
            if ($request->has('customer_price')) {
                $order->customer_price = (float) $request->input('customer_price', $order->customer_price);
            } elseif ($request->has('customer_unit_price') && $request->has('qty')) {
                $order->customer_price = $order->qty * $order->customer_unit_price;
            }

            if ($request->has('customer_name')) {
                $order->customer_name = trim($request->input('customer_name', $order->customer_name));
            }
            if ($request->has('customer_phone')) {
                $order->customer_phone = trim($request->input('customer_phone', '')) ?: null;
            }

            if ($request->has('payment_method')) {
                $order->payment_method = $request->input('payment_method', $order->payment_method);
            }
            if ($request->has('paid_amount')) {
                $order->paid_amount = (float) $request->input('paid_amount', $order->paid_amount);
                $order->remaining_amount = max(0, $order->customer_price - $order->paid_amount);
                $order->payment_status = ($order->paid_amount >= $order->customer_price) ? 'PAID' : ($order->paid_amount > 0 ? 'PARTIAL' : 'UNPAID');
            }

            // If updating vendor HPP
            if ($request->has('vendor_name')) {
                $order->vendor_name = trim($request->input('vendor_name', $order->vendor_name));
            }
            if ($request->has('vendor_phone')) {
                $order->vendor_phone = trim($request->input('vendor_phone', '')) ?: null;
            }
            if ($request->has('vendor_unit_price')) {
                $order->vendor_unit_price = (float) $request->input('vendor_unit_price', $order->vendor_unit_price);
            }
            if ($request->has('vendor_cost')) {
                $order->vendor_cost = (float) $request->input('vendor_cost', $order->vendor_cost);
            } elseif ($request->has('vendor_unit_price')) {
                $order->vendor_cost = $order->qty * $order->vendor_unit_price;
            }
            if ($request->has('shipping_cost')) {
                $order->shipping_cost = (float) $request->input('shipping_cost', $order->shipping_cost);
            }
            if ($request->has('vendor_notes')) {
                $order->vendor_notes = $request->input('vendor_notes');
            }

            // Recalculate totals and margin
            $order->total_cost = $order->vendor_cost + $order->shipping_cost;
            $order->estimated_margin = $order->customer_price - $order->total_cost;

            $order->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "Lembar kerja pesanan #{$order->order_number} berhasil diperbarui!",
                'order' => $order->fresh(['branch', 'user', 'customer', 'approver', 'qcUser', 'completedBy', 'transaction'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Gagal memperbarui pesanan: ' . $e->getMessage()
            ], 400);
        }
    }

    public function receipt($id)
    {
        $order = OutsourceOrder::with(['branch', 'user', 'customer'])->findOrFail($id);
        
        if (!$order->receipt_printed_at) {
            $order->receipt_printed_at = now();
            $order->save();
        }

        return view('outsource_orders.receipt', compact('order'));
    }

    public function submitVendorHpp(Request $request, $id)
    {
        $request->validate([
            'vendor_name' => 'required|string|max:150',
            'vendor_phone' => 'nullable|string|max:50',
            'vendor_unit_price' => 'nullable|numeric|min:0',
            'vendor_cost' => 'required|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'vendor_notes' => 'nullable|string',
        ]);

        try {
            $order = OutsourceOrder::findOrFail($id);

            $vendorCost = (float) $request->input('vendor_cost', 0);
            $qty = $order->qty > 0 ? $order->qty : 1;
            $vendorUnitPrice = (float) $request->input('vendor_unit_price', ($qty > 0 ? round($vendorCost / $qty, 2) : $vendorCost));
            $shippingCost = (float) $request->input('shipping_cost', 0);
            $totalCost = $vendorCost + $shippingCost;
            $estimatedMargin = $order->customer_price - $totalCost;

            $order->vendor_name = trim($request->input('vendor_name'));
            $order->vendor_phone = trim($request->input('vendor_phone', '')) ?: null;
            $order->vendor_unit_price = $vendorUnitPrice;
            $order->vendor_cost = $vendorCost;
            $order->shipping_cost = $shippingCost;
            $order->total_cost = $totalCost;
            $order->estimated_margin = $estimatedMargin;
            $order->vendor_notes = $request->input('vendor_notes');
            $order->status = 'pending_approval';
            $order->save();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "HPP Vendor berhasil disimpan dan pengajuan telah dikirim ke Owner untuk di-ACC!",
                'order' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Gagal mengajukan ke Owner: ' . $e->getMessage()
            ], 400);
        }
    }

    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isSuperAdmin() && !$user->isManager()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya Owner atau Manager yang berhak menyetujui (ACC) pengajuan ini.'
            ], 403);
        }

        try {
            $order = OutsourceOrder::findOrFail($id);

            if ($order->status !== 'pending_approval') {
                throw new \Exception("Pesanan ini tidak berada dalam antrean persetujuan (Status: {$order->status}).");
            }

            $order->status = 'in_production';
            $order->approved_by = $user->id;
            $order->approved_at = now();
            $order->rejection_reason = null;
            $order->save();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "Pengajuan pesanan #{$order->order_number} berhasil di-ACC! Status berpindah ke Sedang Dikerjakan.",
                'order' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Gagal memproses approval: ' . $e->getMessage()
            ], 400);
        }
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isSuperAdmin() && !$user->isManager()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya Owner atau Manager yang berhak menolak pengajuan ini.'
            ], 403);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        try {
            $order = OutsourceOrder::findOrFail($id);

            $order->status = 'rejected';
            $order->approved_by = $user->id;
            $order->approved_at = now();
            $order->rejection_reason = $request->input('rejection_reason');
            $order->save();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "Pengajuan pesanan #{$order->order_number} ditolak. Kasir dapat merevisi HPP vendor.",
                'order' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Gagal menolak pengajuan: ' . $e->getMessage()
            ], 400);
        }
    }

    public function passQc(Request $request, $id)
    {
        try {
            $order = OutsourceOrder::findOrFail($id);

            if (!in_array($order->status, ['in_production', 'pending_approval'])) {
                throw new \Exception("Pesanan belum dalam tahap pengerjaan.");
            }

            $order->status = 'qc_passed';
            $order->qc_by = Auth::id();
            $order->qc_at = now();
            $order->qc_notes = $request->input('qc_notes', 'Kualitas barang sesuai pesanan.');
            $order->save();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "Barang telah sampai dari vendor & lolos Quality Control (QC)! Siap untuk diserahkan ke customer.",
                'order' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Gagal konfirmasi QC: ' . $e->getMessage()
            ], 400);
        }
    }

    public function closeOrder(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $order = OutsourceOrder::findOrFail($id);

            if ($order->status === 'completed') {
                throw new \Exception("Pesanan ini sudah selesai dan sudah pernah di-closing.");
            }

            // 1. Create official sales transaction
            $invoiceNumber = 'INV-OUT-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            $transaction = Transaction::create([
                'branch_id' => $order->branch_id,
                'invoice_number' => $invoiceNumber,
                'user_id' => Auth::id(),
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer_name ?: 'Pelanggan Cetak Luar',
                'customer_phone' => $order->customer_phone,
                'total_price' => $order->customer_price,
                'original_price' => $order->customer_price,
                'total_hpp' => $order->total_cost,
                'payment_method' => $order->payment_method ?: 'Cash',
                'payment_status' => 'PAID',
                'paid_amount' => $order->customer_price,
                'remaining_amount' => 0,
                'order_status' => 'completed',
                'production_notes' => "Cetak di Luar: {$order->job_title} (Vendor: {$order->vendor_name})",
            ]);

            // 2. Create Transaction Detail
            $unitPrice = ($order->qty > 0) ? round($order->customer_price / $order->qty, 2) : $order->customer_price;
            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'material_id' => null,
                'qty_ordered' => $order->qty,
                'selling_price' => $unitPrice,
                'is_vendor_job' => true,
                'vendor_name' => $order->vendor_name,
                'vendor_cost' => $order->vendor_cost,
                'shipping_cost' => $order->shipping_cost,
                'vendor_notes' => $order->vendor_notes,
                'dimension_text' => "Cetak di Luar: {$order->job_title} [Vendor: " . ($order->vendor_name ?: 'Luar') . "]",
            ]);

            // 3. Record Cash Inflow for POS sales
            $salesAccount = Account::where('kode_akun', '4-1000')->first();
            if ($salesAccount) {
                CashTransaction::create([
                    'branch_id' => $order->branch_id,
                    'account_id' => $salesAccount->id,
                    'user_id' => Auth::id(),
                    'tipe' => 'masuk',
                    'nomor_referensi' => CashTransaction::generateNomorReferensi('masuk'),
                    'tanggal' => now()->toDateString(),
                    'jumlah' => $order->customer_price,
                    'keterangan' => "Penjualan Cetak Luar (#{$transaction->invoice_number} / {$order->order_number}) - {$order->customer_name}",
                    'transaction_id' => $transaction->id,
                ]);
            }

            // 4. Update Outsource Order status
            $order->status = 'completed';
            $order->completed_by = Auth::id();
            $order->completed_at = now();
            $order->transaction_id = $transaction->id;
            $order->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "Pesanan #{$order->order_number} berhasil di-closing! Data penjualan resmi telah tercatat di Laporan Penjualan Harian (#{$invoiceNumber}).",
                'order' => $order,
                'invoice_number' => $invoiceNumber,
                'receipt_url' => route('sales.receipt', $transaction->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Gagal closing pesanan: ' . $e->getMessage()
            ], 400);
        }
    }
}
