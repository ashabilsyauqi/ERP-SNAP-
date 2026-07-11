<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

class OwnerController extends Controller
{
    public function dashboard()
    {
        $totalSales = Transaction::sum('total_price');
        $totalHpp = Transaction::sum('total_hpp');
        $grossProfit = $totalSales - $totalHpp;
        
        // Placeholder for Operational Expenses
        $totalOpex = 0; 
        
        $netProfit = $grossProfit - $totalOpex;

        $recentTransactions = Transaction::with('user')->orderBy('created_at', 'desc')->take(10)->get();

        return view('owner.dashboard', compact('totalSales', 'totalHpp', 'grossProfit', 'netProfit', 'totalOpex', 'recentTransactions'));
    }
}
