<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Account;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashOutController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = CashTransaction::keluar()->with(['account', 'branch', 'user']);

        if ($user->role !== 'owner') {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('search')) {
            $query->where('nomor_referensi', 'like', "%{$request->search}%")
                  ->orWhere('keterangan', 'like', "%{$request->search}%");
        }

        $cashTransactions = $query->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->paginate(15);
        $accounts = Account::where('tipe', 'beban')->active()->orderBy('nama_akun')->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        return view('cash-out.index', compact('cashTransactions', 'accounts', 'branches'));
    }

    public function create()
    {
        $accounts = Account::where('tipe', 'beban')->active()->orderBy('nama_akun')->get();
        return view('cash-out.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric|min:1',
            'keterangan' => 'required|string',
        ]);

        $user = Auth::user();
        $branchId = $user->branch_id;
        if (!$branchId) {
            $centralBranch = Branch::where('nama_cabang', 'like', '%Pusat%')->first() ?: Branch::first();
            $branchId = $centralBranch ? $centralBranch->id : null;
        }

        CashTransaction::create([
            'branch_id' => $branchId,
            'account_id' => $validated['account_id'],
            'user_id' => $user->id,
            'tipe' => 'keluar',
            'nomor_referensi' => CashTransaction::generateNomorReferensi('keluar'),
            'tanggal' => $validated['tanggal'],
            'jumlah' => $validated['jumlah'],
            'keterangan' => $validated['keterangan'],
        ]);

        return redirect()->route('kas-keluar.index')->with('success', 'Kas keluar berhasil ditambahkan.');
    }

    public function destroy(CashTransaction $cashTransaction)
    {
        $cashTransaction->delete();
        return back()->with('success', 'Kas keluar berhasil dihapus.');
    }
}
