<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CashTransaction;
use App\Models\Account;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class CashInReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = CashTransaction::with(['account', 'branch'])->orderBy('tanggal', 'asc')->orderBy('created_at', 'asc');

        if ($user->role !== 'owner') {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        // Force only Cash In (masuk)
        $query->where('tipe', 'masuk');

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Hitung saldo awal
        $saldoAwal = 0;
        if ($request->filled('start_date')) {
            $prevQuery = CashTransaction::where('tanggal', '<', $request->start_date);
            if ($user->role !== 'owner') {
                $prevQuery->where('branch_id', $user->branch_id);
            } elseif ($request->filled('branch_id')) {
                $prevQuery->where('branch_id', $request->branch_id);
            }
            if ($request->filled('account_id')) {
                $prevQuery->where('account_id', $request->account_id);
            }
            
            $masukAwal = (clone $prevQuery)->where('tipe', 'masuk')->sum('jumlah');
            $keluarAwal = (clone $prevQuery)->where('tipe', 'keluar')->sum('jumlah');
            $saldoAwal = $masukAwal - $keluarAwal;
        }

        $allMutations = $query->get();
        
        $mutations = collect();
        $runningBalance = $saldoAwal;

        foreach ($allMutations as $mut) {
            if ($mut->tipe === 'masuk') {
                $runningBalance += $mut->jumlah;
            } else {
                $runningBalance -= $mut->jumlah;
            }
            
            $mut->running_balance = $runningBalance;
            $mutations->push($mut);
        }

        // For pagination in view, we'll just slice the collection or use manual paginator if needed, 
        // but for simplicity in reporting, returning all or chunking is fine.
        // To be safe with memory, let's paginate the result collection manually
        $perPage = 20;
        $page = $request->input('page', 1);
        $paginatedMutations = new \Illuminate\Pagination\LengthAwarePaginator(
            $mutations->forPage($page, $perPage),
            $mutations->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $accounts = Account::active()->orderBy('nama_akun')->get();
        $branches = Branch::orderBy('nama_cabang')->get();

        return view('reports.cash-in', [
            'mutations' => $paginatedMutations,
            'accounts' => $accounts,
            'branches' => $branches,
            'saldoAwal' => $saldoAwal
        ]);
    }
}
