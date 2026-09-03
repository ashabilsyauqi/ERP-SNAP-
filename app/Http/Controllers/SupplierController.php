<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers,name',
            'perusahaan' => 'nullable|string|max:255',
            'kontak' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:255',
        ]);

        Supplier::create($validated);
        return redirect()->route('suppliers.index')->with('success', 'Supplier & data rekening berhasil ditambahkan.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers,name,' . $supplier->id,
            'perusahaan' => 'nullable|string|max:255',
            'kontak' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:255',
        ]);

        $supplier->update($validated);
        return redirect()->route('suppliers.index')->with('success', 'Data Supplier & rekening berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        $user = auth()->user();
        if ($supplier->purchases()->exists() && (!$user || !$user->isSuperAdmin())) {
            return back()->with('error', 'Supplier tidak dapat dihapus karena masih terhubung dengan data pembelian.');
        }

        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil dihapus.');
    }
}
