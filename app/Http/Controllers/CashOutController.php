<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Account;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CashOutController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();

        if (!$isOwnerOrSuper) {
            // Store Managers and staff are strictly restricted to their own branch
            $branchId = $user->branch_id;
        } else {
            if ($request->has('branch_id')) {
                $branchId = $request->input('branch_id');
                session(['selected_branch_id' => $branchId]);
            } else {
                $branchId = session('selected_branch_id', 'all');
            }
        }

        $query = CashTransaction::keluar()->with(['account', 'branch', 'user']);

        if ($branchId && $branchId !== 'all') {
            $query->where(function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
                $centralBranch = Branch::where('nama_cabang', 'like', '%Pusat%')->first() ?: Branch::first();
                if ($centralBranch && (int)$branchId === (int)$centralBranch->id) {
                    $q->orWhereNull('branch_id');
                }
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $sDate = $this->parseFlexibleDate($request->start_date)?->toDateString();
            $eDate = $this->parseFlexibleDate($request->end_date)?->toDateString();
            if ($sDate && $eDate) {
                $query->whereBetween('tanggal', [$sDate, $eDate]);
            }
        } elseif ($request->filled('start_date')) {
            $sDate = $this->parseFlexibleDate($request->start_date)?->toDateString();
            if ($sDate) {
                $query->where('tanggal', '>=', $sDate);
            }
        } elseif ($request->filled('end_date')) {
            $eDate = $this->parseFlexibleDate($request->end_date)?->toDateString();
            if ($eDate) {
                $query->where('tanggal', '<=', $eDate);
            }
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
        $branches = Branch::orderBy('nama_cabang')->get();
        return view('cash-out.create', compact('accounts', 'branches'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();

        $rules = [
            'account_id' => 'required|exists:accounts,id',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric|min:1',
            'keterangan' => 'required|string',
            'bukti_transaksi' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
        ];

        if ($isOwnerOrSuper) {
            $rules['branch_id'] = 'nullable|exists:branches,id';
        }

        $validated = $request->validate($rules);

        if ($isOwnerOrSuper && !empty($validated['branch_id'])) {
            $branchId = $validated['branch_id'];
        } else {
            $branchId = $user->branch_id;
            if (!$branchId) {
                $centralBranch = Branch::where('nama_cabang', 'like', '%Pusat%')->first() ?: Branch::first();
                $branchId = $centralBranch ? $centralBranch->id : null;
            }
        }

        $buktiPath = null;
        if ($request->hasFile('bukti_transaksi')) {
            $buktiPath = $request->file('bukti_transaksi')->store('receipts', 'public');
        }

        $trx = CashTransaction::create([
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

        return redirect()->route('kas-keluar.index', [
            'start_date' => $validated['tanggal'],
            'end_date' => $validated['tanggal'],
            'branch_id' => $isOwnerOrSuper ? 'all' : $branchId,
        ])->with('success', "Kas keluar #{$trx->nomor_referensi} (Rp " . number_format($validated['jumlah'], 0, ',', '.') . ") tanggal " . \Carbon\Carbon::parse($validated['tanggal'])->format('d/m/Y') . " berhasil dicatat.");
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();
        if (!$isOwnerOrSuper && !$user->isManager()) {
            abort(403, 'Akses ditolak. Hanya Manager dan KINGAshabil / Owner yang dapat mengedit kas keluar.');
        }

        $cashTransaction = CashTransaction::findOrFail($id);

        if (!$isOwnerOrSuper && $cashTransaction->branch_id != $user->branch_id) {
            abort(403, 'Akses ditolak. Anda hanya dapat mengedit pengeluaran kas di cabang Anda.');
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'branch_id' => 'nullable|exists:branches,id',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric|min:1',
            'keterangan' => 'required|string',
            'bukti_transaksi' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
        ]);

        if (!$isOwnerOrSuper) {
            $validated['branch_id'] = $user->branch_id;
        }

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
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();
        if (!$isOwnerOrSuper && !$user->isManager()) {
            abort(403, 'Akses ditolak. Hanya Manager dan KINGAshabil / Owner yang dapat menghapus kas keluar.');
        }

        $cashTransaction = CashTransaction::findOrFail($id);

        if (!$isOwnerOrSuper && $cashTransaction->branch_id != $user->branch_id) {
            abort(403, 'Akses ditolak. Anda hanya dapat menghapus pengeluaran kas di cabang Anda.');
        }

        if ($cashTransaction->bukti_transaksi && Storage::disk('public')->exists($cashTransaction->bukti_transaksi)) {
            Storage::disk('public')->delete($cashTransaction->bukti_transaksi);
        }
        
        $ref = $cashTransaction->nomor_referensi;
        $cashTransaction->delete();
        return back()->with('success', "Kas keluar #{$ref} berhasil dihapus.");
    }

    /**
     * Flexible date parser supporting DD/MM/YYYY, DD-MM-YYYY, YYYY-MM-DD.
     */
    protected function parseFlexibleDate($dateStr)
    {
        if (!$dateStr) return null;
        $clean = trim($dateStr);
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $clean, $m)) {
            return Carbon::createFromDate((int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $clean, $m)) {
            return Carbon::createFromDate((int)$m[1], (int)$m[2], (int)$m[3]);
        }
        try {
            return Carbon::parse($clean);
        } catch (\Exception $e) {
            return null;
        }
    }
}
