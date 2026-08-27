<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Account;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CashInController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = CashTransaction::masuk()->with(['account', 'branch', 'user', 'transaction.transactionDetails.material', 'transaction.user']);

        if ($user->role !== 'owner') {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('nomor_referensi', 'like', "%{$request->search}%")
                  ->orWhere('keterangan', 'like', "%{$request->search}%");
            });
        }

        $cashTransactions = $query->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->paginate(15);
        $accounts = Account::whereIn('tipe', ['pendapatan', 'aset'])->active()->orderBy('nama_akun')->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        return view('cash-in.index', compact('cashTransactions', 'accounts', 'branches'));
    }

    public function create()
    {
        $accounts = Account::whereIn('tipe', ['pendapatan', 'aset'])->active()->orderBy('nama_akun')->get();
        return view('cash-in.create', compact('accounts'));
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
            'tipe' => 'masuk',
            'nomor_referensi' => CashTransaction::generateNomorReferensi('masuk'),
            'tanggal' => $validated['tanggal'],
            'jumlah' => $validated['jumlah'],
            'keterangan' => $validated['keterangan'],
        ]);

        return redirect()->route('kas-masuk.index')->with('success', 'Kas masuk berhasil ditambahkan.');
    }

    public function destroy(CashTransaction $cashTransaction)
    {
        if ($cashTransaction->transaction_id) {
            return back()->with('error', 'Kas masuk ini terhubung dengan transaksi penjualan dan tidak dapat dihapus.');
        }
        
        $cashTransaction->delete();
        return back()->with('success', 'Kas masuk berhasil dihapus.');
    }
}
