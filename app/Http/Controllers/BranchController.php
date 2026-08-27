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
        $branchName = $branch->nama_cabang;
        $branch->delete();

        return redirect()->route('branches.index')->with('success', "Cabang '{$branchName}' berhasil dihapus.");
    }
}
