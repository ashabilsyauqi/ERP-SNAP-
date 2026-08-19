<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class OwnerController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $query = Transaction::query();
        $materialQuery = Material::query();
        $purchaseQuery = Purchase::query();

        if ($user->isManager()) {
            $query->where('branch_id', $user->branch_id);
            $materialQuery->where('branch_id', $user->branch_id);
            $purchaseQuery->where('branch_id', $user->branch_id);
        }

        $totalSales = (clone $query)->sum('total_price');
        $totalHpp = (clone $query)->sum('total_hpp');
        $grossProfit = $totalSales - $totalHpp;
        
        $totalOpex = 0; 
        $netProfit = $grossProfit - $totalOpex;

        $totalTransactionsCount = (clone $query)->count();
        $totalMaterialsCount = (clone $materialQuery)->count();
        $lowStockCount = (clone $materialQuery)->where('stock_qty', '<=', 5)->count();
        $pendingPOCount = (clone $purchaseQuery)->whereIn('status', ['waiting_approval', 'pending_verification'])->count();

        // Payment Method Breakdown
        $cashSales = (clone $query)->where('payment_method', 'cash')->sum('total_price');
        $qrisSales = (clone $query)->where('payment_method', 'qris')->sum('total_price');
        $transferSales = (clone $query)->where('payment_method', 'transfer')->sum('total_price');

        // Sales Per Branch Data
        $branchSalesData = Branch::withTrashed()->get()->map(function ($branch) {
            $sales = Transaction::where('branch_id', $branch->id)->sum('total_price');
            return [
                'name' => $branch->nama_cabang,
                'sales' => $sales,
            ];
        });

        $recentTransactions = (clone $query)->with(['user', 'branch'])->orderBy('created_at', 'desc')->take(10)->get();

        return view('owner.dashboard', compact(
            'totalSales',
            'totalHpp',
            'grossProfit',
            'netProfit',
            'totalOpex',
            'totalTransactionsCount',
            'totalMaterialsCount',
            'lowStockCount',
            'pendingPOCount',
            'cashSales',
            'qrisSales',
            'transferSales',
            'branchSalesData',
            'recentTransactions'
        ));
    }
}
