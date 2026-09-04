<?php

namespace App\Http\Controllers;

use App\Models\DailyClosingReport;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\CashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyClosingController extends Controller
{
    /**
     * List all Daily Closing Reports.
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

        $query = DailyClosingReport::with(['branch', 'manager', 'owner'])->orderBy('closing_date', 'desc')->orderBy('id', 'desc');

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('closing_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('closing_date', '<=', $request->date_to);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $reports = $query->paginate(15);
        $branches = Branch::orderBy('nama_cabang')->get();

        $pendingCount = DailyClosingReport::where('status', 'submitted')->count();

        return view('finance.daily_closing.index', compact('reports', 'branches', 'pendingCount', 'branchId'));
    }

    /**
     * Show form to create a new Daily Closing Report.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $targetDate = $request->input('closing_date', date('Y-m-d'));
        
        $branchId = $user->branch_id;
        if (($user->isOwner() || $user->isSuperAdmin()) && $request->filled('branch_id')) {
            $branchId = $request->branch_id;
        } elseif (!$branchId) {
            $branchId = Branch::first()->id ?? 1;
        }

        $branch = Branch::find($branchId);
        $branches = Branch::orderBy('nama_cabang')->get();

        // 1. Calculate sales from transactions for this branch and date
        $transactions = Transaction::with('payments')
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $targetDate)
            ->whereNotIn('order_status', ['draft', 'cancelled'])
            ->get();

        $totalOrdersCount = $transactions->count();
        $directTransactions = $transactions->filter(fn($t) => $t->payments->isEmpty());
        $splitPayments = $transactions->flatMap->payments;

        $totalCashSales = (float) $directTransactions->whereIn('payment_method', ['Cash', 'cash'])->sum('paid_amount')
            + (float) $splitPayments->whereIn('payment_method', ['Cash', 'cash'])->sum('amount');
        $totalTransferSales = (float) $directTransactions->whereIn('payment_method', ['Transfer', 'transfer'])->sum('paid_amount')
            + (float) $splitPayments->whereIn('payment_method', ['Transfer', 'transfer'])->sum('amount');
        $totalQrisSales = (float) $directTransactions->whereIn('payment_method', ['QRIS', 'qris'])->sum('paid_amount')
            + (float) $splitPayments->whereIn('payment_method', ['QRIS', 'qris'])->sum('amount');
        $totalSales = $totalCashSales + $totalTransferSales + $totalQrisSales;

        // 2. Calculate Cash In and Cash Out
        $cashIn = CashTransaction::where('branch_id', $branchId)
            ->where('tipe', 'masuk')
            ->whereDate('tanggal', $targetDate)
            ->whereNull('transaction_id')
            ->sum('jumlah');

        $cashOut = CashTransaction::where('branch_id', $branchId)
            ->where('tipe', 'keluar')
            ->whereDate('tanggal', $targetDate)
            ->sum('jumlah');

        // Opening cash from previous closing or default 0
        $lastReport = DailyClosingReport::where('branch_id', $branchId)
            ->where('closing_date', '<', $targetDate)
            ->orderBy('closing_date', 'desc')
            ->first();

        $openingCash = $lastReport ? $lastReport->actual_cash : 0;
        $expectedCash = $openingCash + $totalCashSales + $cashIn - $cashOut;

        return view('finance.daily_closing.create', compact(
            'branches',
            'branch',
            'targetDate',
            'totalOrdersCount',
            'totalCashSales',
            'totalTransferSales',
            'totalQrisSales',
            'totalSales',
            'cashIn',
            'cashOut',
            'openingCash',
            'expectedCash'
        ));
    }

    /**
     * Store Daily Closing Report and auto-sign Manager signature.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'closing_date' => 'required|date',
            'shift_type' => 'required|string|max:50',
            'opening_cash' => 'required|numeric|min:0',
            'actual_cash' => 'required|numeric|min:0',
            'total_cash_sales' => 'required|numeric|min:0',
            'total_transfer_sales' => 'required|numeric|min:0',
            'total_qris_sales' => 'required|numeric|min:0',
            'total_cash_in' => 'required|numeric|min:0',
            'total_cash_out' => 'required|numeric|min:0',
            'click_counter_start' => 'nullable|integer|min:0',
            'click_counter_end' => 'nullable|integer|min:0',
            'production_notes' => 'nullable|string|max:1000',
        ]);

        $branch = Branch::findOrFail($request->branch_id);

        $totalSales = $request->total_cash_sales + $request->total_transfer_sales + $request->total_qris_sales;
        $expectedCash = $request->opening_cash + $request->total_cash_sales + $request->total_cash_in - $request->total_cash_out;
        $cashDifference = $request->actual_cash - $expectedCash;

        $clickCount = null;
        if ($request->filled('click_counter_start') && $request->filled('click_counter_end')) {
            $clickCount = max(0, $request->click_counter_end - $request->click_counter_start);
        }

        $reportNumber = DailyClosingReport::generateReportNumber($branch->nama_cabang);

        $report = DailyClosingReport::create([
            'report_number' => $reportNumber,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'closing_date' => $request->closing_date,
            'shift_type' => $request->shift_type,
            'total_orders_count' => (int) $request->input('total_orders_count', 0),
            'total_cash_sales' => $request->total_cash_sales,
            'total_transfer_sales' => $request->total_transfer_sales,
            'total_qris_sales' => $request->total_qris_sales,
            'total_sales' => $totalSales,
            'total_cash_in' => $request->total_cash_in,
            'total_cash_out' => $request->total_cash_out,
            'opening_cash' => $request->opening_cash,
            'expected_cash' => $expectedCash,
            'actual_cash' => $request->actual_cash,
            'cash_difference' => $cashDifference,
            'click_counter_start' => $request->click_counter_start,
            'click_counter_end' => $request->click_counter_end,
            'click_count_total' => $clickCount,
            'production_notes' => $request->production_notes,
            'manager_signature_path' => $user->signature_path,
            'manager_signed_at' => Carbon::now(),
            'status' => 'submitted',
        ]);

        return redirect()->route('daily-closing.show', $report->id)->with('success', "Laporan Tutup Hari #{$report->report_number} berhasil dibuat dan ditandatangani digital oleh Manager.");
    }

    /**
     * Show detailed Daily Closing Report.
     */
    public function show($id)
    {
        $report = DailyClosingReport::with(['branch', 'manager', 'owner'])->findOrFail($id);
        return view('finance.daily_closing.show', compact('report'));
    }

    /**
     * Verify and digitally sign Daily Closing Report (Owner only).
     */
    public function verify(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isSuperAdmin()) {
            abort(403, 'Hanya Owner yang berwenang menandatangani penerimaan Laporan Tutup Hari.');
        }

        $report = DailyClosingReport::findOrFail($id);
        $report->owner_id = $user->id;
        $report->owner_signature_path = $user->signature_path;
        $report->owner_signed_at = Carbon::now();
        $report->owner_notes = $request->input('owner_notes');
        $report->status = 'verified';
        $report->save();

        return redirect()->back()->with('success', "Laporan Tutup Hari #{$report->report_number} berhasil diverifikasi & ditandatangani oleh Owner.");
    }

    /**
     * Delete Daily Closing Report (Super Admin / Owner).
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $report = DailyClosingReport::findOrFail($id);
        $report->delete();

        return redirect()->route('daily-closing.index')->with('success', 'Laporan Tutup Hari berhasil dihapus.');
    }
}
