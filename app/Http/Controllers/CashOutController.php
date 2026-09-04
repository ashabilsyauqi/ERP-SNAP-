<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Account;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CashOutController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isManager = $user->isManager();
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();
        
        if ($request->has('branch_id')) {
            $branchId = $request->input('branch_id');
            session(['selected_branch_id' => $branchId]);
        } else {
            $branchId = session('selected_branch_id', 'all');
        }

        if (!$isOwnerOrSuper && !$isManager) {
            $branchId = $user->branch_id;
        }

        $query = CashTransaction::keluar()->with(['account', 'branch', 'user']);

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('tanggal', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('tanggal', '<=', $request->end_date);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_referensi', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        $cashTransactions = $query->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $accounts = Account::where('tipe', 'beban')->active()->orderBy('nama_akun')->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        return view('cash-out.index', compact('cashTransactions', 'accounts', 'branches', 'branchId'));
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
            'bukti_transaksi' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
        ]);

        $user = Auth::user();
        $branchId = $user->branch_id;
        if (!$branchId) {
            $centralBranch = Branch::where('nama_cabang', 'like', '%Pusat%')->first() ?: Branch::first();
            $branchId = $centralBranch ? $centralBranch->id : null;
        }

        $buktiPath = null;
        if ($request->hasFile('bukti_transaksi')) {
            $buktiPath = $request->file('bukti_transaksi')->store('receipts', 'public');
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
            'bukti_transaksi' => $buktiPath,
        ]);

        return redirect()->route('kas-keluar.index')->with('success', 'Kas keluar beserta bukti nota/struk berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isOwner() && !$user->isManager()) {
            abort(403, 'Akses ditolak. Hanya Manager dan KINGAshabil / Owner yang dapat mengedit kas keluar.');
        }

        $cashTransaction = CashTransaction::findOrFail($id);

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'branch_id' => 'nullable|exists:branches,id',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric|min:1',
            'keterangan' => 'required|string',
            'bukti_transaksi' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
        ]);

        if ($request->hasFile('bukti_transaksi')) {
            if ($cashTransaction->bukti_transaksi && Storage::disk('public')->exists($cashTransaction->bukti_transaksi)) {
                Storage::disk('public')->delete($cashTransaction->bukti_transaksi);
            }
            $validated['bukti_transaksi'] = $request->file('bukti_transaksi')->store('receipts', 'public');
        }

        $cashTransaction->update($validated);
        return back()->with('success', "Kas keluar #{$cashTransaction->nomor_referensi} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isOwner() && !$user->isManager()) {
            abort(403, 'Akses ditolak. Hanya Manager dan KINGAshabil / Owner yang dapat menghapus kas keluar.');
        }

        $cashTransaction = CashTransaction::findOrFail($id);

        if ($cashTransaction->bukti_transaksi && Storage::disk('public')->exists($cashTransaction->bukti_transaksi)) {
            Storage::disk('public')->delete($cashTransaction->bukti_transaksi);
        }
        
        $ref = $cashTransaction->nomor_referensi;
        $cashTransaction->delete();
        return back()->with('success', "Kas keluar #{$ref} berhasil dihapus.");
    }
}
