<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

class OwnerController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $query = Transaction::query();

        if ($user->isManager()) {
            $query->where('branch_id', $user->branch_id);
        }

        $totalSales = (clone $query)->sum('total_price');
        $totalHpp = (clone $query)->sum('total_hpp');
        $grossProfit = $totalSales - $totalHpp;
        
        // Placeholder for Operational Expenses
        $totalOpex = 0; 
        
        $netProfit = $grossProfit - $totalOpex;

        $recentTransactions = (clone $query)->with('user')->orderBy('created_at', 'desc')->take(10)->get();

        return view('owner.dashboard', compact('totalSales', 'totalHpp', 'grossProfit', 'netProfit', 'totalOpex', 'recentTransactions'));
    }
}
