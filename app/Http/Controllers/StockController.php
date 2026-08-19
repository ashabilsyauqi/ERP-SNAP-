<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * 1. Data Stok & Opname (Inventory & Valuation)
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Material::with(['supplier', 'branch', 'wholesalePrices'])->orderBy('material_name', 'asc');

        if ($user->isOwner()) {
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $query->where('branch_id', $request->branch_id);
            }
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        if ($request->filled('search')) {
            $query->where('material_name', 'like', "%{$request->search}%");
        }

        $materials = $query->get();

        // Calculate summary metrics
        $totalItems = $materials->count();
        $totalStockQty = $materials->sum('stock_qty');
        $totalAssetValue = $materials->sum(function ($m) {
            return $m->stock_qty * $m->purchase_price;
        });
        $lowStockCount = $materials->filter(function ($m) {
            return $m->stock_qty <= 5;
        })->count();

        // Count pending inspection items for badge counter
        $pendingCount = Purchase::where('status', 'pending_verification')
            ->when(!$user->isOwner(), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })->count();

        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();

        return view('stock.index', compact(
            'materials',
            'totalItems',
            'totalStockQty',
            'totalAssetValue',
            'lowStockCount',
            'pendingCount',
            'branches'
        ));
    }

    /**
     * 2. Pemeriksaan Barang Masuk (Pending Inspection / GRN)
     */
    public function inspection(Request $request)
    {
        $user = Auth::user();

        $pendingQuery = Purchase::with(['material', 'supplier', 'user', 'branch'])
            ->where('status', 'pending_verification')
            ->orderBy('created_at', 'desc');

        if ($user->isOwner()) {
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $pendingQuery->where('branch_id', $request->branch_id);
            }
        } else {
            $pendingQuery->where('branch_id', $user->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $pendingQuery->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('vendor_ref', 'like', "%{$search}%")
                  ->orWhereHas('material', function ($mq) use ($search) {
                      $mq->where('material_name', 'like', "%{$search}%");
                  });
            });
        }

        $pendingPurchases = $pendingQuery->get();
        $pendingCount = $pendingPurchases->count();
        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();

        return view('stock.inspection', compact('pendingPurchases', 'pendingCount', 'branches'));
    }

    /**
     * 3. Riwayat Retur & Reject (Quality Control Return History)
     */
    public function rejected(Request $request)
    {
        $user = Auth::user();

        $rejectedQuery = Purchase::with(['material', 'supplier', 'user', 'branch', 'verifiedBy'])
            ->where('status', 'rejected')
            ->orderBy('verified_at', 'desc');

        if ($user->isOwner()) {
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $rejectedQuery->where('branch_id', $request->branch_id);
            }
        } else {
            $rejectedQuery->where('branch_id', $user->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $rejectedQuery->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('vendor_ref', 'like', "%{$search}%")
                  ->orWhereHas('material', function ($mq) use ($search) {
                      $mq->where('material_name', 'like', "%{$search}%");
                  });
            });
        }

        $rejectedPurchases = $rejectedQuery->get();
        $rejectedCount = $rejectedPurchases->count();
        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();

        return view('stock.rejected', compact('rejectedPurchases', 'rejectedCount', 'branches'));
    }

    /**
     * Update Stock Opname
     */
    public function update(Request $request, Material $material)
    {
        $user = Auth::user();

        if (!$user->isOwner() && $material->branch_id != $user->branch_id) {
            abort(403, 'Anda tidak berhak mengubah stok cabang lain.');
        }

        $validated = $request->validate([
            'stock_qty' => 'required|integer|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'retail_price' => 'nullable|numeric|min:0',
        ]);

        $material->stock_qty = $validated['stock_qty'];

        if ($request->filled('purchase_price')) {
            $material->purchase_price = $validated['purchase_price'];
        }
        if ($request->filled('retail_price')) {
            $material->retail_price = $validated['retail_price'];
        }

        $material->save();

        return redirect()->route('stock.index')->with('success', "Stok {$material->material_name} berhasil diperbarui.");
    }

    /**
     * Verify & Accept Goods Receipt
     */
    public function verify(Request $request, Purchase $purchase)
    {
        $user = Auth::user();

        if (!$user->isOwner() && $purchase->branch_id != $user->branch_id) {
            abort(403, 'Anda tidak berhak memverifikasi penerimaan barang cabang lain.');
        }

        if ($purchase->status !== 'pending_verification') {
            return redirect()->route('stock.inspection')->with('error', 'Transaksi pengadaan ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'qty_received' => 'required|integer|min:1',
            'verification_notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($purchase, $validated, $user) {
            $qtyReceived = (int) $validated['qty_received'];

            // 1. Increment Material Stock
            $material = $purchase->material;
            $material->stock_qty += $qtyReceived;
            $material->save();

            // 2. Update Purchase Status
            $purchase->status = 'received';
            $purchase->verified_at = now();
            $purchase->verified_by = $user->id;
            $purchase->verification_notes = $validated['verification_notes'] ?? 'Diterima & Sesuai fisik.';
            $purchase->save();
        });

        return redirect()->route('stock.inspection')->with('success', "Penerimaan barang #{$purchase->po_number} berhasil diverifikasi dan stok telah ditambahkan ke inventaris.");
    }

    /**
     * Reject Goods Receipt
     */
    public function reject(Request $request, Purchase $purchase)
    {
        $user = Auth::user();

        if (!$user->isOwner() && $purchase->branch_id != $user->branch_id) {
            abort(403, 'Anda tidak berhak menolak penerimaan barang cabang lain.');
        }

        if ($purchase->status !== 'pending_verification') {
            return redirect()->route('stock.inspection')->with('error', 'Transaksi pengadaan ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'verification_notes' => 'required|string|max:500',
        ]);

        $purchase->status = 'rejected';
        $purchase->verified_at = now();
        $purchase->verified_by = $user->id;
        $purchase->verification_notes = $validated['verification_notes'];
        $purchase->save();

        return redirect()->route('stock.rejected')->with('success', "Pengadaan barang #{$purchase->po_number} berhasil ditolak/retur dan dicatat pada riwayat retur.");
    }
}
