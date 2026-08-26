<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchasePlan;
use App\Models\PurchasePlanItem;
use App\Models\Purchase;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\MaterialWholesalePrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchasePlanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = PurchasePlan::with(['branch', 'user', 'approvedBy', 'rejectedBy', 'items.material', 'items.supplier', 'purchases.verifiedBy'])
            ->orderBy('created_at', 'desc');

        if ($user->isOwner()) {
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $query->where('branch_id', $request->branch_id);
            }
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('plan_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('items', function ($iq) use ($search) {
                      $iq->where('material_name', 'like', "%{$search}%")
                         ->orWhere('supplier_name', 'like', "%{$search}%");
                  });
            });
        }

        $plans = $query->get();

        // Calculate KPI Metrics
        $totalPlannedCost = $plans->whereIn('status', ['waiting_owner_approval', 'approved_by_owner', 'completed'])->sum('total_estimated_cost');
        $waitingApprovalCount = $plans->where('status', 'waiting_owner_approval')->count();
        $approvedCount = $plans->whereIn('status', ['approved_by_owner', 'completed'])->count();
        $rejectedCount = $plans->where('status', 'rejected_by_owner')->count();
        $draftCount = $plans->where('status', 'draft')->count();

        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();
        $paymentAccounts = \App\Models\Account::where('tipe', 'aset')->where('is_active', true)->orderBy('kode_akun')->get();

        return view('purchasing.plans.index', compact(
            'plans',
            'totalPlannedCost',
            'waitingApprovalCount',
            'approvedCount',
            'rejectedCount',
            'draftCount',
            'branches',
            'paymentAccounts'
        ));
    }

    public function create()
    {
        $user = Auth::user();
        
        $materialQuery = Material::with(['wholesalePrices', 'supplier'])->orderBy('material_name', 'asc');
        if (!$user->isOwner()) {
            $materialQuery->where('branch_id', $user->branch_id);
        }
        
        $materials = $materialQuery->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        return view('purchasing.plans.create', compact('materials', 'suppliers', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'target_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
            'action_type' => 'required|in:draft,submit_rfq',
            'items' => 'required|array|min:1',
            'items.*.material_name' => 'required|string|max:255',
            'items.*.supplier_name' => 'nullable|string|max:255',
            'items.*.fixed_size' => 'nullable|numeric|min:0',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.estimated_unit_price' => 'required|numeric|min:0',
            'items.*.retail_price' => 'nullable|numeric|min:0',
            'items.*.wholesale' => 'nullable|array',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $targetBranchId = $user->isOwner() && $request->filled('branch_id') ? $request->branch_id : $user->branch_id;

        DB::transaction(function () use ($request, $user, $targetBranchId) {
            $totalEstimatedCost = 0;
            foreach ($request->items as $item) {
                $totalEstimatedCost += ($item['qty'] * $item['estimated_unit_price']);
            }

            $status = $request->action_type === 'submit_rfq' ? 'waiting_owner_approval' : 'draft';

            $plan = PurchasePlan::create([
                'plan_number' => PurchasePlan::generatePlanNumber(),
                'branch_id' => $targetBranchId,
                'user_id' => $user->id,
                'title' => $request->title,
                'target_date' => $request->target_date,
                'total_estimated_cost' => $totalEstimatedCost,
                'status' => $status,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $itemData) {
                $subtotal = $itemData['qty'] * $itemData['estimated_unit_price'];

                // Check if supplier exists or create
                $supplierId = null;
                if (!empty($itemData['supplier_name'])) {
                    $supplier = Supplier::firstOrCreate(['name' => trim($itemData['supplier_name'])]);
                    $supplierId = $supplier->id;
                }

                // Check if material already exists in branch
                $material = Material::where('branch_id', $targetBranchId)
                    ->where('material_name', $itemData['material_name'])
                    ->where('fixed_size', $itemData['fixed_size'] ?? null)
                    ->first();

                $wholesaleClean = [];
                if (!empty($itemData['wholesale']) && is_array($itemData['wholesale'])) {
                    foreach ($itemData['wholesale'] as $w) {
                        if (!empty($w['min_qty']) && !empty($w['price'])) {
                            $wholesaleClean[] = [
                                'min_qty' => (int) $w['min_qty'],
                                'price' => (float) $w['price'],
                            ];
                        }
                    }
                }

                PurchasePlanItem::create([
                    'purchase_plan_id' => $plan->id,
                    'material_id' => $material ? $material->id : null,
                    'material_name' => $itemData['material_name'],
                    'supplier_id' => $supplierId,
                    'supplier_name' => $itemData['supplier_name'] ?? null,
                    'fixed_size' => $itemData['fixed_size'] ?? null,
                    'qty' => $itemData['qty'],
                    'estimated_unit_price' => $itemData['estimated_unit_price'],
                    'subtotal' => $subtotal,
                    'retail_price' => $itemData['retail_price'] ?? null,
                    'wholesale_prices' => !empty($wholesaleClean) ? $wholesaleClean : null,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }
        });

        $msg = $request->action_type === 'submit_rfq' 
            ? 'Purchase Plan & RFQ Bundle berhasil diajukan untuk persetujuan Owner!'
            : 'Draft Purchase Plan berhasil disimpan.';

        return redirect()->route('purchasing.plans.index')->with('success', $msg);
    }

    public function show(PurchasePlan $plan)
    {
        $plan->load(['branch', 'user', 'approvedBy', 'rejectedBy', 'items.material', 'items.supplier', 'purchases']);
        return response()->json($plan);
    }

    public function approve(Request $request, PurchasePlan $plan)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Hanya Owner yang berhak menyetujui (ACC) Purchase Plan & RFQ Bundle ini.');
        }

        if ($plan->status !== 'waiting_owner_approval') {
            return redirect()->back()->with('error', 'Purchase Plan ini tidak dalam status menunggu persetujuan.');
        }

        $request->validate([
            'approval_notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $plan, $user) {
            $plan->status = 'approved_by_owner';
            $plan->approved_by = $user->id;
            $plan->approved_at = now();
            $plan->approval_notes = $request->approval_notes ?? 'Disetujui oleh Owner.';
            $plan->save();

            // Convert each bundle item into an official Purchase Order in the warehouse inspection pipeline
            foreach ($plan->items as $item) {
                // Find or create material
                $material = Material::where('branch_id', $plan->branch_id)
                    ->where('material_name', $item->material_name)
                    ->where('fixed_size', $item->fixed_size)
                    ->first();

                if (!$material) {
                    $material = Material::create([
                        'branch_id' => $plan->branch_id,
                        'supplier_id' => $item->supplier_id,
                        'material_name' => $item->material_name,
                        'fixed_size' => $item->fixed_size,
                        'purchase_price' => $item->estimated_unit_price,
                        'retail_price' => $item->retail_price ?? ($item->estimated_unit_price * 1.3),
                        'stock_qty' => 0,
                    ]);
                } else {
                    $material->purchase_price = $item->estimated_unit_price;
                    if ($item->retail_price) {
                        $material->retail_price = $item->retail_price;
                    }
                    if ($item->supplier_id) {
                        $material->supplier_id = $item->supplier_id;
                    }
                    $material->save();
                }

                // If wholesale price tiers were defined in the plan, save them
                if (!empty($item->wholesale_prices) && is_array($item->wholesale_prices)) {
                    $material->wholesalePrices()->delete();
                    foreach ($item->wholesale_prices as $w) {
                        if (!empty($w['min_qty']) && !empty($w['price'])) {
                            $material->wholesalePrices()->create([
                                'min_qty' => $w['min_qty'],
                                'wholesale_price' => $w['price'],
                            ]);
                        }
                    }
                }

                $poNumber = Purchase::generatePoNumber();

                // Create official PO in pending_verification (waiting physical receipt inspection by warehouse)
                $purchase = Purchase::create([
                    'po_number' => $poNumber,
                    'vendor_ref' => $plan->plan_number,
                    'branch_id' => $plan->branch_id,
                    'material_id' => $material->id,
                    'user_id' => $plan->user_id,
                    'supplier_id' => $item->supplier_id,
                    'purchase_plan_id' => $plan->id,
                    'qty_bought' => $item->qty,
                    'total_cost' => $item->subtotal,
                    'status' => 'pending_verification',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_notes' => "Disetujui dari RFQ Plan #{$plan->plan_number}. " . ($request->approval_notes ?? ''),
                ]);

                $item->purchase_id = $purchase->id;
                $item->material_id = $material->id;
                $item->save();
            }
        });

        return redirect()->back()->with('success', "Purchase Plan #{$plan->plan_number} BERHASIL DISETUJUI! Seluruh PO pembelian telah diterbitkan dan langsung diteruskan ke bagian Gudang untuk Pemeriksaan Barang Masuk (GRN).");
    }

    public function edit(PurchasePlan $plan)
    {
        $user = Auth::user();

        if ($plan->status === 'approved_by_owner' || $plan->status === 'completed') {
            return redirect()->route('purchasing.plans.index')->with('error', 'Purchase Plan yang sudah disetujui tidak dapat diedit kembali.');
        }

        if (!$user->isOwner() && $plan->branch_id != $user->branch_id) {
            abort(403, 'Anda tidak berhak mengedit Purchase Plan cabang lain.');
        }

        $plan->load(['items.material', 'items.supplier']);

        $materialQuery = Material::with(['wholesalePrices', 'supplier'])->orderBy('material_name', 'asc');
        if (!$user->isOwner()) {
            $materialQuery->where('branch_id', $user->branch_id);
        }
        
        $materials = $materialQuery->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        $initialPlanItems = $plan->items->map(function ($it) {
            return [
                'material_name' => $it->material_name,
                'supplier_name' => $it->supplier_name,
                'fixed_size' => $it->fixed_size,
                'qty' => $it->qty,
                'estimated_unit_price' => (float) $it->estimated_unit_price,
                'retail_price' => (float) $it->retail_price,
            ];
        })->values()->all();

        return view('purchasing.plans.edit', compact('plan', 'materials', 'suppliers', 'branches', 'initialPlanItems'));
    }

    public function update(Request $request, PurchasePlan $plan)
    {
        $user = Auth::user();

        if ($plan->status === 'approved_by_owner' || $plan->status === 'completed') {
            return redirect()->route('purchasing.plans.index')->with('error', 'Purchase Plan yang sudah disetujui tidak dapat diubah.');
        }

        if (!$user->isOwner() && $plan->branch_id != $user->branch_id) {
            abort(403, 'Anda tidak berhak mengubah Purchase Plan cabang lain.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'target_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
            'action_type' => 'required|in:draft,submit_rfq',
            'items' => 'required|array|min:1',
            'items.*.material_name' => 'required|string|max:255',
            'items.*.supplier_name' => 'nullable|string|max:255',
            'items.*.fixed_size' => 'nullable|numeric|min:0',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.estimated_unit_price' => 'required|numeric|min:0',
            'items.*.retail_price' => 'nullable|numeric|min:0',
            'items.*.wholesale' => 'nullable|array',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        $targetBranchId = $user->isOwner() && $request->filled('branch_id') ? $request->branch_id : $plan->branch_id;

        DB::transaction(function () use ($request, $plan, $targetBranchId) {
            $totalEstimatedCost = 0;
            foreach ($request->items as $item) {
                $totalEstimatedCost += ($item['qty'] * $item['estimated_unit_price']);
            }

            $status = $request->action_type === 'submit_rfq' ? 'waiting_owner_approval' : 'draft';

            $plan->update([
                'title' => $request->title,
                'target_date' => $request->target_date,
                'branch_id' => $targetBranchId,
                'total_estimated_cost' => $totalEstimatedCost,
                'status' => $status,
                'notes' => $request->notes,
                'rejection_notes' => null, // Reset rejection notes on re-submission
            ]);

            // Delete old items and re-create updated items
            $plan->items()->delete();

            foreach ($request->items as $itemData) {
                $subtotal = $itemData['qty'] * $itemData['estimated_unit_price'];

                $supplierId = null;
                if (!empty($itemData['supplier_name'])) {
                    $supplier = Supplier::firstOrCreate(['name' => trim($itemData['supplier_name'])]);
                    $supplierId = $supplier->id;
                }

                $material = Material::where('branch_id', $targetBranchId)
                    ->where('material_name', $itemData['material_name'])
                    ->where('fixed_size', $itemData['fixed_size'] ?? null)
                    ->first();

                $wholesaleClean = [];
                if (!empty($itemData['wholesale']) && is_array($itemData['wholesale'])) {
                    foreach ($itemData['wholesale'] as $w) {
                        if (!empty($w['min_qty']) && !empty($w['price'])) {
                            $wholesaleClean[] = [
                                'min_qty' => (int) $w['min_qty'],
                                'price' => (float) $w['price'],
                            ];
                        }
                    }
                }

                PurchasePlanItem::create([
                    'purchase_plan_id' => $plan->id,
                    'material_id' => $material ? $material->id : null,
                    'material_name' => $itemData['material_name'],
                    'supplier_id' => $supplierId,
                    'supplier_name' => $itemData['supplier_name'] ?? null,
                    'fixed_size' => $itemData['fixed_size'] ?? null,
                    'qty' => $itemData['qty'],
                    'estimated_unit_price' => $itemData['estimated_unit_price'],
                    'subtotal' => $subtotal,
                    'retail_price' => $itemData['retail_price'] ?? null,
                    'wholesale_prices' => !empty($wholesaleClean) ? $wholesaleClean : null,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }
        });

        $msg = $request->action_type === 'submit_rfq' 
            ? "Purchase Plan #{$plan->plan_number} berhasil diperbarui dan diajukan ke Owner untuk persetujuan (RFQ)!"
            : "Draft Purchase Plan #{$plan->plan_number} berhasil diperbarui.";

        return redirect()->route('purchasing.plans.index')->with('success', $msg);
    }

    public function submitRfq(PurchasePlan $plan)
    {
        $user = Auth::user();

        if ($plan->status === 'approved_by_owner' || $plan->status === 'completed') {
            return redirect()->back()->with('error', 'Purchase Plan ini sudah disetujui sebelumnya.');
        }

        if (!$user->isOwner() && $plan->branch_id != $user->branch_id) {
            abort(403, 'Anda tidak berhak mengajukan Purchase Plan cabang lain.');
        }

        $plan->status = 'waiting_owner_approval';
        $plan->rejection_notes = null;
        $plan->save();

        return redirect()->back()->with('success', "Purchase Plan #{$plan->plan_number} berhasil DIAJUKAN untuk persetujuan (RFQ) Owner!");
    }

    public function destroy(PurchasePlan $plan)
    {
        $user = Auth::user();

        if ($plan->status === 'approved_by_owner' || $plan->status === 'completed') {
            return redirect()->back()->with('error', 'Purchase Plan yang sudah disetujui tidak dapat dihapus.');
        }

        if (!$user->isOwner() && $plan->user_id != $user->id) {
            abort(403, 'Anda hanya dapat menghapus draft rencana yang Anda buat sendiri.');
        }

        $planNumber = $plan->plan_number;
        $plan->delete();

        return redirect()->route('purchasing.plans.index')->with('success', "Draft Purchase Plan #{$planNumber} berhasil dihapus.");
    }

    public function pay(Request $request, PurchasePlan $plan)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Hanya Owner yang berhak mencatat/melakukan pembayaran tagihan supplier.');
        }

        if ($plan->status !== 'approved_by_owner' && $plan->status !== 'completed') {
            return redirect()->back()->with('error', 'Tagihan hanya dapat dibayar jika Purchase Plan telah disetujui (ACC) oleh Owner.');
        }

        $request->validate([
            'account_id' => 'nullable|exists:accounts,id',
            'payment_method' => 'required|string|max:100',
            'payment_reference' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $plan, $user) {
            $plan->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'paid_by' => $user->id,
                'payment_method' => $request->payment_method,
                'account_id' => $request->account_id,
                'payment_reference' => $request->payment_reference,
                'payment_notes' => $request->payment_notes,
            ]);

            // Update linked purchases
            $plan->purchases()->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'paid_by' => $user->id,
                'payment_method' => $request->payment_method,
                'account_id' => $request->account_id,
                'payment_reference' => $request->payment_reference,
            ]);

            // If an asset account is selected (Cash / Bank), record a cash transaction (Kas Keluar)
            if ($request->filled('account_id')) {
                \App\Models\CashTransaction::create([
                    'branch_id' => $plan->branch_id,
                    'account_id' => $request->account_id,
                    'user_id' => $user->id,
                    'tipe' => 'keluar',
                    'nomor_referensi' => \App\Models\CashTransaction::generateNomorReferensi('keluar'),
                    'tanggal' => now()->toDateString(),
                    'jumlah' => $plan->total_estimated_cost,
                    'keterangan' => "Pembayaran Tagihan Supplier untuk Purchase Plan #{$plan->plan_number} (" . ($request->payment_method) . ($request->payment_reference ? " - Ref: {$request->payment_reference}" : '') . ")",
                ]);
            }
        });

        return redirect()->back()->with('success', "Tagihan Purchase Plan #{$plan->plan_number} BERHASIL DIBAYAR! Status tagihan supplier kini telah LUNAS.");
    }

    public function reject(Request $request, PurchasePlan $plan)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Hanya Owner yang berhak menolak Purchase Plan ini.');
        }

        if ($plan->status !== 'waiting_owner_approval') {
            return redirect()->back()->with('error', 'Purchase Plan ini tidak dalam status menunggu persetujuan.');
        }

        $request->validate([
            'rejection_notes' => 'required|string|max:500',
        ]);

        $plan->status = 'rejected_by_owner';
        $plan->rejected_by = $user->id;
        $plan->rejected_at = now();
        $plan->rejection_notes = $request->rejection_notes;
        $plan->save();

        return redirect()->back()->with('success', "Purchase Plan #{$plan->plan_number} telah DITOLAK.");
    }
}
