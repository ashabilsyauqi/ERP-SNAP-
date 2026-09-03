<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::user();
        
        if ($authUser->isManager()) {
            // Manager only sees users belonging to their own assigned branch
            $users = User::with('branch')
                ->where('branch_id', $authUser->branch_id)
                ->orderBy('username')
                ->get();
            $branches = Branch::where('id', $authUser->branch_id)->get();
            $selectedBranchId = $authUser->branch_id;
        } else {
            // Owner sees all users from all branches or can filter by branch
            $selectedBranchId = $request->input('branch_id', 'all');
            $query = User::with('branch')->orderBy('username');

            if ($selectedBranchId !== 'all') {
                $query->where('branch_id', $selectedBranchId);
            }

            $users = $query->get();
            $branches = Branch::orderBy('nama_cabang')->get();
        }

        return view('users.index', compact('users', 'branches', 'selectedBranchId'));
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();
        $allowedRoles = $authUser->isManager() ? 'manager,purchasing,cashier,operator' : 'owner,manager,purchasing,cashier,operator';

        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users,username',
            'role' => 'required|string|in:' . $allowedRoles,
            'password' => 'required|string|min:6',
            'branch_id' => $authUser->isManager() ? 'nullable|exists:branches,id' : 'required_if:role,manager,purchasing,cashier,operator|nullable|exists:branches,id',
        ]);

        if ($authUser->isManager()) {
            $validated['branch_id'] = $authUser->branch_id;
        }

        $validated['password'] = Hash::make($request->password);

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isSuperAdmin() && $authUser->isManager() && $user->branch_id != $authUser->branch_id) {
            abort(403, 'Anda tidak berhak mengedit user cabang lain.');
        }

        $allowedRoles = ($authUser->isManager() && !$authUser->isSuperAdmin()) 
            ? 'manager,purchasing,cashier,operator' 
            : 'owner,manager,purchasing,cashier,operator';

        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'role' => 'required|string|in:' . $allowedRoles,
            'password' => 'nullable|string|min:6',
            'branch_id' => ($authUser->isManager() && !$authUser->isSuperAdmin()) ? 'nullable|exists:branches,id' : 'required_if:role,manager,purchasing,cashier,operator|nullable|exists:branches,id',
        ]);

        $data = [
            'username' => $validated['username'],
            'role' => $validated['role'],
            'branch_id' => ($authUser->isManager() && !$authUser->isSuperAdmin()) ? $authUser->branch_id : ($validated['role'] === 'owner' ? null : $validated['branch_id']),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isSuperAdmin() && $authUser->isManager() && $user->branch_id != $authUser->branch_id) {
            abort(403, 'Anda tidak berhak menghapus user cabang lain.');
        }

        if (Auth::id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        }

        if (!$authUser->isSuperAdmin() && $user->isOwner() && User::where('role', 'owner')->whereNull('deleted_at')->count() <= 1) {
            return redirect()->route('users.index')->with('error', 'Tidak dapat menghapus satu-satunya akun Owner aktif dalam sistem.');
        }

        try {
            $username = $user->username;
            $user->delete();
            return redirect()->route('users.index')->with('success', "User '{$username}' berhasil dinonaktifkan / dihapus.");
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Gagal menghapus user: ' . $e->getMessage());
        }
    }
}
