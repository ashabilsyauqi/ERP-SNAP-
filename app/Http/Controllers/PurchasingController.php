<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class PurchasingController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $materialQuery = Material::with(['wholesalePrices', 'supplier'])->orderBy('material_name', 'asc');
        $purchaseQuery = Purchase::with(['material', 'supplier', 'user', 'branch', 'verifiedBy', 'approvedBy'])->orderBy('created_at', 'desc');

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

        if ($branchId && $branchId !== 'all') {
            $materialQuery->where('branch_id', $branchId);
            $purchaseQuery->where('branch_id', $branchId);
        }

        // SAP Filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $purchaseQuery->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        if ($request->filled('supplier_id') && $request->supplier_id !== 'all') {
            $purchaseQuery->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $purchaseQuery->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $purchaseQuery->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('vendor_ref', 'like', "%{$search}%")
                  ->orWhereHas('material', function ($mq) use ($search) {
                      $mq->where('material_name', 'like', "%{$search}%");
                  });
            });
        }

        $materials = $materialQuery->get();
        $purchases = $purchaseQuery->get();

        // Calculate KPI Metrics
        $totalSpend = $purchases->where('status', 'received')->sum('total_cost');
        $waitingApprovalCount = $purchases->where('status', 'waiting_approval')->count();
        $pendingCount = $purchases->whereIn('status', ['pending_verification', 'approved'])->count();
        $receivedCount = $purchases->where('status', 'received')->count();
        $rejectedCount = $purchases->where('status', 'rejected')->count();

        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        return view('purchasing.index', compact(
            'materials',
            'purchases',
            'suppliers',
            'branches',
            'totalSpend',
            'waitingApprovalCount',
            'pendingCount',
            'receivedCount',
            'rejectedCount'
        ));
    }

    public function create()
    {
        $user = auth()->user();
        $materialQuery = Material::with(['wholesalePrices', 'supplier'])->orderBy('material_name', 'asc');
        
        if (!$user->isOwner()) {
            $materialQuery->where('branch_id', $user->branch_id);
        }

        $materials = $materialQuery->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $branches = Branch::orderBy('nama_cabang')->get();
        
        return view('purchasing.create', compact('materials', 'suppliers', 'branches'));
    }

    /**
     * Dedicated Purchase Order History Log Records
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        
        $purchaseQuery = Purchase::with(['material', 'supplier', 'user', 'branch', 'verifiedBy', 'approvedBy'])->orderBy('created_at', 'desc');

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

        if ($branchId && $branchId !== 'all') {
            $purchaseQuery->where('branch_id', $branchId);
        }

        // SAP Filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $purchaseQuery->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        if ($request->filled('supplier_id') && $request->supplier_id !== 'all') {
            $purchaseQuery->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $purchaseQuery->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $purchaseQuery->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('vendor_ref', 'like', "%{$search}%")
                  ->orWhereHas('material', function ($mq) use ($search) {
                      $mq->where('material_name', 'like', "%{$search}%");
                  });
            });
        }

        $purchases = $purchaseQuery->get();

        // Calculate KPI Metrics
        $totalSpend = $purchases->where('status', 'received')->sum('total_cost');
        $waitingApprovalCount = $purchases->where('status', 'waiting_approval')->count();
        $pendingCount = $purchases->whereIn('status', ['pending_verification', 'approved'])->count();
        $receivedCount = $purchases->where('status', 'received')->count();
        $rejectedCount = $purchases->where('status', 'rejected')->count();

        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        return view('purchasing.history', compact(
            'purchases',
            'suppliers',
            'branches',
            'totalSpend',
            'waitingApprovalCount',
            'pendingCount',
            'receivedCount',
            'rejectedCount'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'material_name' => 'required|string',
            'supplier_name' => 'nullable|string',
            'vendor_ref' => 'nullable|string|max:100',
            'fixed_size' => 'nullable|numeric|min:0',
            'qty_bought' => 'required|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'wholesale' => 'nullable|array',
            'wholesale.*.min_qty' => 'nullable|integer|min:1',
            'wholesale.*.price' => 'nullable|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        $user = auth()->user();
        $targetBranchId = $user->isOwner() && $request->filled('branch_id') ? $request->branch_id : $user->branch_id;

        DB::transaction(function () use ($request, $user, $targetBranchId) {
            // Find existing or create new Material
            $material = Material::where('material_name', $request->material_name)
                ->where('branch_id', $targetBranchId)
                ->where('fixed_size', $request->fixed_size)
                ->first();

            // Handle Supplier
            $supplierId = null;
            if ($request->filled('supplier_name')) {
                $supplier = Supplier::firstOrCreate(['name' => trim($request->supplier_name)]);
                $supplierId = $supplier->id;
            }

            if (!$material) {
                $material = Material::create([
                    'branch_id' => $targetBranchId,
                    'supplier_id' => $supplierId,
                    'material_name' => $request->material_name,
                    'fixed_size' => $request->fixed_size,
                    'purchase_price' => $request->purchase_price,
                    'retail_price' => $request->retail_price,
                    'stock_qty' => 0
                ]);
            } else {
                $material->purchase_price = $request->purchase_price;
                $material->retail_price = $request->retail_price;
                if ($supplierId) {
                    $material->supplier_id = $supplierId;
                }
                $material->save();
            }

            // Generate SAP PO Number
            $poNumber = Purchase::generatePoNumber();

            // Log purchase with status waiting_approval (Pre-Order request waiting Manager ACC)
            Purchase::create([
                'po_number' => $poNumber,
                'vendor_ref' => $request->vendor_ref ?: null,
                'branch_id' => $targetBranchId,
                'material_id' => $material->id,
                'user_id' => $user->id,
                'supplier_id' => $supplierId,
                'qty_bought' => $request->qty_bought,
                'total_cost' => $request->qty_bought * $request->purchase_price,
                'status' => 'waiting_approval',
            ]);

            // Sync Wholesale Tiers
            $material->wholesalePrices()->delete();
            if ($request->has('wholesale') && is_array($request->wholesale)) {
                foreach ($request->wholesale as $tier) {
                    if (!empty($tier['min_qty']) && !empty($tier['price'])) {
                        $material->wholesalePrices()->create([
                            'min_qty' => $tier['min_qty'],
                            'wholesale_price' => $tier['price']
                        ]);
                    }
                }
            }
        });

        return redirect()->route('purchasing.history')->with('success', 'Pengajuan Purchase Order (PO) berhasil dibuat! Status PO saat ini: Menunggu Persetujuan (ACC) Manajer Toko.');
    }

    /**
     * Approve Purchase Order (Manager / Owner ACC)
     */
    public function approve(Request $request, Purchase $purchase)
    {
        $user = auth()->user();

        if (!$user->isOwner() && !$user->isSuperAdmin() && !$user->isManager()) {
            abort(403, 'Hanya Manajer atau Owner yang dapat memberikan persetujuan PO.');
        }

        if (!$user->isOwner() && !$user->isSuperAdmin() && $user->isManager() && $purchase->branch_id != $user->branch_id) {
            abort(403, 'Anda hanya dapat menyetujui PO cabang Anda sendiri.');
        }

        $request->validate([
            'approval_notes' => 'nullable|string|max:500',
        ]);

        $purchase->status = 'pending_verification'; // Approved & now waiting physical goods receipt at warehouse
        $purchase->approved_by = $user->id;
        $purchase->approved_at = now();
        $purchase->approval_notes = $request->approval_notes ?? 'Disetujui oleh Manajer Toko.';
        $purchase->save();

        return redirect()->back()->with('success', "Purchase Order #{$purchase->po_number} berhasil DISETUJUI (ACC)! Nota PO resmi dapat dicetak dengan Tanda Tangan Digital Manajer.");
    }

    /**
     * Delete Purchase Order (Super Admin KINGAshabil / Owner)
     */
    public function destroy(Purchase $purchase)
    {
        $user = auth()->user();

        if (!$user->isOwner() && !$user->isSuperAdmin()) {
            abort(403, 'Hanya Owner atau Super Admin yang berhak menghapus Purchase Order.');
        }

        $poNumber = $purchase->po_number;

        // If items were already added to stock, decrement it back
        if ($purchase->status === 'received' && $purchase->material) {
            $purchase->material->decrement('stock_qty', $purchase->qty);
        }

        $purchase->delete();

        return redirect()->back()->with('success', "Purchase Order #{$poNumber} berhasil dihapus dari sistem.");
    }
}
