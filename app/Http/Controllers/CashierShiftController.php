<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashierShift;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\TransactionPayment;

class CashierShiftController extends Controller
{
    public function openShift(Request $request)
    {
        $request->validate([
            'opening_cash' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        // Check if shift is already open
        $activeShift = CashierShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($activeShift) {
            return redirect()->back()->with('info', 'Shift kasir Anda sudah aktif.');
        }

        CashierShift::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'opened_at' => now(),
            'opening_cash' => $request->opening_cash,
            'status' => 'open',
        ]);

        return redirect()->back()->with('success', 'Shift kasir berhasil dibuka dengan Kas Awal Rp ' . number_format($request->opening_cash, 0, ',', '.'));
    }

    public function closeShift(Request $request)
    {
        $request->validate([
            'actual_closing_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();

        $activeShift = CashierShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if (!$activeShift) {
            return redirect()->back()->with('error', 'Tidak ada shift aktif yang perlu ditutup.');
        }

        // Calculate expected closing cash = opening cash + total cash sales during shift (direct cash + split payments cash)
        $shiftBranch = $activeShift->branch_id ?: $user->branch_id;
        $directCashSales = Transaction::where('user_id', $user->id)
            ->where('branch_id', $shiftBranch)
            ->whereIn('payment_method', ['Cash', 'cash'])
            ->whereNotIn('order_status', ['draft', 'cancelled'])
            ->whereBetween('created_at', [$activeShift->opened_at, now()])
            ->sum('paid_amount');

        $splitCashSales = TransactionPayment::whereHas('transaction', function($q) use ($user, $activeShift, $shiftBranch) {
            $q->where('user_id', $user->id)
              ->where('branch_id', $shiftBranch)
              ->whereNotIn('order_status', ['draft', 'cancelled'])
              ->whereBetween('created_at', [$activeShift->opened_at, now()]);
        })->whereIn('payment_method', ['Cash', 'cash'])->sum('amount');

        $cashSalesDuringShift = (float) $directCashSales + (float) $splitCashSales;

        $expectedClosingCash = $activeShift->opening_cash + $cashSalesDuringShift;
        $discrepancy = $request->actual_closing_cash - $expectedClosingCash;

        $activeShift->closed_at = now();
        $activeShift->expected_closing_cash = $expectedClosingCash;
        $activeShift->actual_closing_cash = $request->actual_closing_cash;
        $activeShift->discrepancy = $discrepancy;
        $activeShift->status = 'closed';
        $activeShift->notes = $request->notes;
        $activeShift->save();

        $msg = 'Shift kasir berhasil ditutup.';
        if ($discrepancy != 0) {
            $msg .= ' Terdeteksi selisih kas fisik: Rp ' . number_format($discrepancy, 0, ',', '.');
        }

        return redirect()->back()->with('success', $msg);
    }
}
