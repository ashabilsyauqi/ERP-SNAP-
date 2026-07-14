<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $query = Account::query()->with('parent');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode_akun', 'like', "%{$search}%")
                  ->orWhere('nama_akun', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        $accounts = $query->orderBy('kode_akun')->paginate(15);

        return view('accounts.index', compact('accounts'));
    }

    public function create()
    {
        $parentAccounts = Account::orderBy('kode_akun')->get();
        return view('accounts.create', compact('parentAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_akun' => 'required|string|max:20|unique:accounts,kode_akun',
            'nama_akun' => 'required|string|max:100',
            'tipe' => 'required|in:aset,kewajiban,modal,pendapatan,beban',
            'parent_id' => 'nullable|exists:accounts,id',
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $validated['is_active'] = $request->has('is_active');

        Account::create($validated);

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(Account $account)
    {
        $parentAccounts = Account::where('id', '!=', $account->id)->orderBy('kode_akun')->get();
        return view('accounts.edit', compact('account', 'parentAccounts'));
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'kode_akun' => 'required|string|max:20|unique:accounts,kode_akun,' . $account->id,
            'nama_akun' => 'required|string|max:100',
            'tipe' => 'required|in:aset,kewajiban,modal,pendapatan,beban',
            'parent_id' => 'nullable|exists:accounts,id',
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $validated['is_active'] = $request->has('is_active');

        $account->update($validated);

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Account $account)
    {
        if ($account->cashTransactions()->exists() || $account->children()->exists()) {
            return redirect()->route('accounts.index')->with('error', 'Akun tidak dapat dihapus karena masih memiliki transaksi atau sub-akun.');
        }

        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil dihapus.');
    }

    public function toggleStatus(Account $account)
    {
        $account->update(['is_active' => !$account->is_active]);
        return back()->with('success', 'Status akun berhasil diubah.');
    }
}
