<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('branch')->orderBy('username')->get();
        $branches = Branch::orderBy('nama_cabang')->get();
        return view('users.index', compact('users', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users,username',
            'role' => 'required|string|in:owner,purchasing,cashier',
            'password' => 'required|string|min:6',
            'branch_id' => 'required_if:role,purchasing,cashier|nullable|exists:branches,id',
        ]);

        $validated['password'] = Hash::make($request->password);

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'role' => 'required|string|in:owner,purchasing,cashier',
            'password' => 'nullable|string|min:6',
            'branch_id' => 'required_if:role,purchasing,cashier|nullable|exists:branches,id',
        ]);

        $data = [
            'username' => $validated['username'],
            'role' => $validated['role'],
            'branch_id' => $validated['role'] === 'owner' ? null : $validated['branch_id'],
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if (Auth::id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->transactions()->exists() || $user->cashTransactions()->exists()) {
            return redirect()->route('users.index')->with('error', 'User tidak dapat dihapus karena sudah memiliki riwayat transaksi.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
