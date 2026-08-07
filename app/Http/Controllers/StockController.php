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
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Query Materials (Inventory)
        $materialQuery = Material::with(['supplier', 'branch', 'wholesalePrices'])->orderBy('material_name', 'asc');

        // 2. Query Pending Purchases (Goods Receipt)
        $pendingQuery = Purchase::with(['material', 'supplier', 'user', 'branch'])
            ->where('status', 'pending_verification')
            ->orderBy('created_at', 'desc');

        // 3. Query Receipt History
        $historyQuery = Purchase::with(['material', 'supplier', 'user', 'branch', 'verifiedBy'])
            ->whereIn('status', ['received', 'rejected'])
            ->orderBy('verified_at', 'desc');

        if ($user->isOwner()) {
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $materialQuery->where('branch_id', $request->branch_id);
                $pendingQuery->where('branch_id', $request->branch_id);
                $historyQuery->where('branch_id', $request->branch_id);
            }
        } else {
            $materialQuery->where('branch_id', $user->branch_id);
            $pendingQuery->where('branch_id', $user->branch_id);
            $historyQuery->where('branch_id', $user->branch_id);
        }

        if ($request->filled('search')) {
            $materialQuery->where('material_name', 'like', "%{$request->search}%");
        }

        $materials = $materialQuery->get();
        $pendingPurchases = $pendingQuery->get();
        $historyPurchases = $historyQuery->take(20)->get();

        // Summary Metrics
        $totalItems = $materials->count();
        $totalStockQty = $materials->sum('stock_qty');
        $totalAssetValue = $materials->sum(function ($m) {
            return $m->stock_qty * $m->purchase_price;
        });
        $lowStockCount = $materials->filter(function ($m) {
            return $m->stock_qty <= 5;
        })->count();
        $pendingCount = $pendingPurchases->count();

        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();

        return view('stock.index', compact(
            'materials',
            'pendingPurchases',
            'historyPurchases',
            'totalItems',
            'totalStockQty',
            'totalAssetValue',
            'lowStockCount',
            'pendingCount',
            'branches'
        ));
    }

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

    public function verify(Request $request, Purchase $purchase)
    {
        $user = Auth::user();

        if (!$user->isOwner() && $purchase->branch_id != $user->branch_id) {
            abort(403, 'Anda tidak berhak memverifikasi penerimaan barang cabang lain.');
        }

        if ($purchase->status !== 'pending_verification') {
            return redirect()->route('stock.index')->with('error', 'Transaksi pengadaan ini sudah diproses sebelumnya.');
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

        return redirect()->route('stock.index')->with('success', "Penerimaan barang #{$purchase->id} berhasil diverifikasi. Stok barang bertambah di sistem.");
    }

    public function reject(Request $request, Purchase $purchase)
    {
        $user = Auth::user();

        if (!$user->isOwner() && $purchase->branch_id != $user->branch_id) {
            abort(403, 'Anda tidak berhak menolak penerimaan barang cabang lain.');
        }

        if ($purchase->status !== 'pending_verification') {
            return redirect()->route('stock.index')->with('error', 'Transaksi pengadaan ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'verification_notes' => 'required|string|max:500',
        ]);

        $purchase->status = 'rejected';
        $purchase->verified_at = now();
        $purchase->verified_by = $user->id;
        $purchase->verification_notes = $validated['verification_notes'];
        $purchase->save();

        return redirect()->route('stock.index')->with('success', "Pengadaan barang #{$purchase->id} berhasil ditolak/retur.");
    }
}
