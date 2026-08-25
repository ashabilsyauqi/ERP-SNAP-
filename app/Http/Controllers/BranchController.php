<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withTrashed()->orderBy('nama_cabang')->get();
        return view('branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_cabang' => 'required|string|max:255|unique:branches,nama_cabang',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:50',
        ]);

        Branch::create($validated);

        return redirect()->route('branches.index')->with('success', 'Cabang baru berhasil ditambahkan.');
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
        // Soft delete all users linked to this branch
        \App\Models\User::where('branch_id', $branch->id)->delete();

        // Soft delete all materials/products linked to this branch
        \App\Models\Material::where('branch_id', $branch->id)->delete();

        // Soft delete the branch itself
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Cabang dan seluruh data user serta produk di dalamnya berhasil dihapus dan diarsipkan.');
    }

    public function restore($id)
    {
        $branch = Branch::withTrashed()->findOrFail($id);
        $branch->restore();

        // Restore users and materials linked to this branch
        \App\Models\User::withTrashed()->where('branch_id', $branch->id)->restore();
        \App\Models\Material::withTrashed()->where('branch_id', $branch->id)->restore();

        return redirect()->route('branches.index')->with('success', "Cabang {$branch->nama_cabang} berhasil diaktifkan kembali.");
    }
}
