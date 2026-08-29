<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\MaterialWholesalePrice;
use Illuminate\Support\Facades\Auth;

class MaterialController extends Controller
{
    public function index(?Request $request = null)
    {
        $request = $request ?? request();
        $user = Auth::user();
        $query = Material::with(['supplier', 'branch', 'wholesalePrices']);

        if ($user->isManager()) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('material_name', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $materials = $query->orderBy('created_at', 'desc')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $branches = Branch::all();

        return view('materials.index', compact('materials', 'suppliers', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'material_name' => 'required|string|max:255',
            'category'      => 'nullable|string|max:100',
            'supplier_id'   => 'nullable|exists:suppliers,id',
            'fixed_size'    => 'nullable|numeric|min:0',
            'purchase_price'=> 'required|numeric|min:0',
            'retail_price'  => 'required|numeric|min:0',
            'stock_qty'     => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $branchId = ($user->isOwner() || $user->isSuperAdmin()) && $request->filled('branch_id') && $request->branch_id !== 'all'
            ? $request->branch_id
            : ($user->branch_id ?: (\App\Models\Branch::first()->id ?? 1));

        $material = Material::create([
            'branch_id'     => $branchId,
            'category'      => $request->category ?: 'Lainnya',
            'supplier_id'   => $request->supplier_id,
            'material_name' => $request->material_name,
            'fixed_size'    => $request->fixed_size,
            'purchase_price'=> $request->purchase_price,
            'retail_price'  => $request->retail_price,
            'stock_qty'     => $request->stock_qty,
        ]);

        // Save wholesale tiers if provided
        $wholesaleTiers = [];
        if ($request->filled('wholesale_min_qty') && is_array($request->wholesale_min_qty)) {
            foreach ($request->wholesale_min_qty as $index => $minQty) {
                if ($minQty > 0 && isset($request->wholesale_price[$index]) && $request->wholesale_price[$index] > 0) {
                    $tierPrice = $request->wholesale_price[$index];
                    $wholesaleTiers[] = ['min_qty' => $minQty, 'wholesale_price' => $tierPrice];
                    MaterialWholesalePrice::create([
                        'material_id'     => $material->id,
                        'min_qty'         => $minQty,
                        'wholesale_price' => $tierPrice,
                    ]);
                }
            }
        }

        // Replicate to all other branches with stock_qty = 0
        $otherBranches = Branch::where('id', '!=', $branchId)->get();
        foreach ($otherBranches as $otherBranch) {
            $otherMat = Material::where('branch_id', $otherBranch->id)->where('material_name', $material->material_name)->first();
            if (!$otherMat) {
                $createdMat = Material::create([
                    'branch_id'     => $otherBranch->id,
                    'category'      => $material->category,
                    'supplier_id'   => $material->supplier_id,
                    'material_name' => $material->material_name,
                    'unit'          => $material->unit ?: 'Pcs',
                    'fixed_size'    => $material->fixed_size,
                    'purchase_price'=> $material->purchase_price,
                    'retail_price'  => $material->retail_price,
                    'stock_qty'     => 0, // initial stock 0 for other branches
                ]);

                foreach ($wholesaleTiers as $tier) {
                    MaterialWholesalePrice::create([
                        'material_id'     => $createdMat->id,
                        'min_qty'         => $tier['min_qty'],
                        'wholesale_price' => $tier['wholesale_price'],
                    ]);
                }
            }
        }

        return redirect()->route('materials.index')->with('success', 'Master Bahan Baku / Produk berhasil ditambahkan dan disinkronkan ke seluruh cabang!');
    }

    public function update(Request $request, Material $material)
    {
        $request->validate([
            'material_name' => 'required|string|max:255',
            'category'      => 'nullable|string|max:100',
            'supplier_id'   => 'nullable|exists:suppliers,id',
            'fixed_size'    => 'nullable|numeric|min:0',
            'purchase_price'=> 'required|numeric|min:0',
            'retail_price'  => 'required|numeric|min:0',
            'stock_qty'     => 'required|numeric|min:0',
        ]);

        $material->update([
            'category'      => $request->category ?: $material->category,
            'supplier_id'   => $request->supplier_id,
            'material_name' => $request->material_name,
            'fixed_size'    => $request->fixed_size,
            'purchase_price'=> $request->purchase_price,
            'retail_price'  => $request->retail_price,
            'stock_qty'     => $request->stock_qty,
        ]);

        // Refresh wholesale prices
        if ($request->has('wholesale_min_qty')) {
            $material->wholesalePrices()->delete();
            if (is_array($request->wholesale_min_qty)) {
                foreach ($request->wholesale_min_qty as $index => $minQty) {
                    if ($minQty > 0 && isset($request->wholesale_price[$index]) && $request->wholesale_price[$index] > 0) {
                        MaterialWholesalePrice::create([
                            'material_id'     => $material->id,
                            'min_qty'         => $minQty,
                            'wholesale_price' => $request->wholesale_price[$index],
                        ]);
                    }
                }
            }
        }

        return redirect()->route('materials.index')->with('success', 'Master Bahan Baku berhasil diperbarui!');
    }

    public function destroy(Material $material)
    {
        $material->delete();
        return redirect()->route('materials.index')->with('success', 'Master Bahan Baku berhasil dihapus dari Katalog!');
    }
}
