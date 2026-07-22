<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class PurchasingController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $materialQuery = Material::with('wholesalePrices')->orderBy('material_name', 'asc');
        $purchaseQuery = Purchase::with(['material', 'supplier', 'user', 'branch'])->orderBy('created_at', 'desc');

        if ($user->isOwner()) {
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $materialQuery->where('branch_id', $request->branch_id);
                $purchaseQuery->where('branch_id', $request->branch_id);
            }
        } else {
            $materialQuery->where('branch_id', $user->branch_id);
            $purchaseQuery->where('branch_id', $user->branch_id);
        }

        $materials = $materialQuery->get();
        $purchases = $purchaseQuery->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $branches = \App\Models\Branch::orderBy('nama_cabang')->get();

        return view('purchasing.index', compact('materials', 'purchases', 'suppliers', 'branches'));
    }

    public function create()
    {
        $user = auth()->user();
        $materialQuery = Material::with('wholesalePrices')->orderBy('material_name', 'asc');
        
        if (!$user->isOwner()) {
            $materialQuery->where('branch_id', $user->branch_id);
        }

        $materials = $materialQuery->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $branches = \App\Models\Branch::orderBy('nama_cabang')->get();
        
        return view('purchasing.create', compact('materials', 'suppliers', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'material_name' => 'required|string',
            'supplier_name' => 'nullable|string',
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

            if (!$material) {
                $material = Material::create([
                    'branch_id' => $targetBranchId,
                    'material_name' => $request->material_name,
                    'fixed_size' => $request->fixed_size,
                    'purchase_price' => $request->purchase_price,
                    'retail_price' => $request->retail_price,
                    'stock_qty' => 0
                ]);
            } else {
                // Update pricing to the latest
                $material->purchase_price = $request->purchase_price;
                $material->retail_price = $request->retail_price;
            }
            
            // Add stock
            $material->stock_qty += $request->qty_bought;
            $material->save();

            // Handle Supplier
            $supplierId = null;
            if ($request->filled('supplier_name')) {
                $supplier = Supplier::firstOrCreate(['name' => trim($request->supplier_name)]);
                $supplierId = $supplier->id;
            }

            // Log purchase
            Purchase::create([
                'branch_id' => $targetBranchId,
                'material_id' => $material->id,
                'user_id' => $user->id,
                'supplier_id' => $supplierId,
                'qty_bought' => $request->qty_bought,
                'total_cost' => $request->qty_bought * $request->purchase_price,
            ]);

            // Sync Wholesale Tiers — filter out empty rows
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

        return redirect()->route('purchasing.index')->with('success', 'Stock updated and purchase recorded successfully.');
    }
}
