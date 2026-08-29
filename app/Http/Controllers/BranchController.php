<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('nama_cabang')->get();
        return view('branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_cabang' => 'required|string|max:255|unique:branches,nama_cabang',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:50',
        ]);

        $validated['alamat'] = $validated['alamat'] ?? '-';
        $validated['telepon'] = $validated['telepon'] ?? '-';

        $branch = Branch::create($validated);

        // Auto-replicate master materials to this new branch with stock_qty = 0
        $masterBranch = Branch::withCount('materials')->where('id', '!=', $branch->id)->orderBy('materials_count', 'desc')->first();
        if ($masterBranch) {
            $masterMaterials = \App\Models\Material::where('branch_id', $masterBranch->id)->with('wholesalePrices')->get();
            foreach ($masterMaterials as $m) {
                $newMat = \App\Models\Material::create([
                    'branch_id'      => $branch->id,
                    'category'       => $m->category ?: 'Lainnya',
                    'supplier_id'    => $m->supplier_id,
                    'material_name'  => $m->material_name,
                    'unit'           => $m->unit ?: 'Pcs',
                    'fixed_size'     => $m->fixed_size,
                    'purchase_price' => $m->purchase_price,
                    'retail_price'   => $m->retail_price,
                    'stock_qty'      => 0, // initial stock 0 for new branch
                ]);

                foreach ($m->wholesalePrices as $wp) {
                    \App\Models\MaterialWholesalePrice::create([
                        'material_id'     => $newMat->id,
                        'min_qty'         => $wp->min_qty,
                        'wholesale_price' => $wp->wholesale_price,
                    ]);
                }
            }
        }

        return redirect()->route('branches.index')->with('success', "Cabang '{$branch->nama_cabang}' berhasil ditambahkan dan katalog master produk otomatis disinkronkan!");
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'nama_cabang' => 'required|string|max:255|unique:branches,nama_cabang,' . $branch->id,
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:50',
        ]);

        $branch->update($validated);

        return redirect()->route('branches.index')->with('success', 'Data cabang berhasil diperbarui.');
    }

    public function destroy(Branch $branch)
    {
        $branchName = $branch->nama_cabang;
        $branch->delete();

        return redirect()->route('branches.index')->with('success', "Cabang '{$branchName}' berhasil dihapus.");
    }

    public function syncCatalog()
    {
        \Illuminate\Support\Facades\Artisan::call('snaprint:sync-catalog');
        return redirect()->back()->with('success', 'Seluruh master produk & bahan baku berhasil disinkronkan ke seluruh cabang!');
    }
}
