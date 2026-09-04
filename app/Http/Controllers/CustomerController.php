<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();

        if (!$isOwnerOrSuper) {
            $branchId = $user->branch_id;
        } else {
            if ($request->has('branch_id')) {
                $branchId = $request->input('branch_id');
                session(['selected_branch_id' => $branchId]);
            } else {
                $branchId = session('selected_branch_id', 'all');
            }
        }

        $query = Customer::with('branch');

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->withCount('transactions')
            ->withSum('transactions', 'total_price')
            ->orderBy('name', 'asc')
            ->paginate(20)
            ->withQueryString();

        $branches = Branch::orderBy('nama_cabang')->get();

        // Calculate KPI Metrics
        $totalCustomers = (clone $query)->count();
        $totalOmsetCustomers = (clone $query)->withSum('transactions', 'total_price')->get()->sum('transactions_sum_total_price');

        return view('customers.index', compact('customers', 'branches', 'totalCustomers', 'totalOmsetCustomers', 'branchId'));
    }

    /**
     * Display customer detail with purchase history.
     */
    public function show(Customer $customer)
    {
        $user = Auth::user();

        if (!$user->isOwner() && !$user->isSuperAdmin() && $customer->branch_id && $customer->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak ke data pelanggan cabang lain.');
        }

        $transactions = $customer->transactions()
            ->with(['user', 'branch', 'transactionDetails.material'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('customers.show', compact('customer', 'transactions'));
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if (!$user->isOwner() && !$user->isSuperAdmin()) {
            $validated['branch_id'] = $user->branch_id;
        }

        $customer = Customer::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'customer' => $customer
            ]);
        }

        return redirect()->route('customers.index')->with('success', "Pelanggan '{$customer->name}' berhasil ditambahkan.");
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer)
    {
        $user = Auth::user();

        if (!$user->isOwner() && !$user->isSuperAdmin() && $customer->branch_id && $customer->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if (!$user->isOwner() && !$user->isSuperAdmin()) {
            unset($validated['branch_id']);
        }

        $customer->update($validated);

        return redirect()->back()->with('success', "Data pelanggan '{$customer->name}' berhasil diperbarui.");
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer)
    {
        $user = Auth::user();

        if (!$user->isOwner() && !$user->isSuperAdmin() && $customer->branch_id && $customer->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak.');
        }

        $name = $customer->name;
        $customer->delete();

        return redirect()->route('customers.index')->with('success', "Pelanggan '{$name}' berhasil dihapus.");
    }

    /**
     * Quick search API for POS dropdown / autocomplete.
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        $q = $request->input('q', '');

        $query = Customer::query();

        if ($user->role !== 'owner') {
            $query->where(function($b) use ($user) {
                $b->where('branch_id', $user->branch_id)
                  ->orWhereNull('branch_id');
            });
        }

        if (!empty($q)) {
            $query->where(function($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $customers = $query->orderBy('name', 'asc')->take(30)->get();

        return response()->json($customers);
    }
}
