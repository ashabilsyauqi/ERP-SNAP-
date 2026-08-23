<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PurchasingController;
use App\Http\Controllers\SalesController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::middleware(['role:owner,manager'])->group(function () {
        Route::get('/owner/dashboard', [OwnerController::class, 'dashboard'])->name('owner.dashboard');
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::resource('materials', \App\Http\Controllers\MaterialController::class)->except(['create', 'show', 'edit']);
    });

    Route::middleware(['role:owner'])->group(function () {
        // Owner only sales actions
        Route::get('/sales/{id}/edit', [SalesController::class, 'edit'])->name('sales.edit');
        Route::put('/sales/{id}', [SalesController::class, 'update'])->name('sales.update');
        Route::post('/sales/{id}/refund', [SalesController::class, 'refund'])->name('sales.refund');

        // Branch Management (Owner Only - No Manager Access)
        Route::resource('branches', \App\Http\Controllers\BranchController::class)->except(['create', 'show', 'edit']);
    });

    // Profile & Digital Signature
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile/biodata', [\App\Http\Controllers\ProfileController::class, 'updateBiodata'])->name('profile.biodata');
    Route::post('/profile/signature', [\App\Http\Controllers\ProfileController::class, 'updateSignature'])->name('profile.signature');
    Route::post('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::middleware(['role:purchasing,owner,manager'])->group(function () {
        Route::get('/purchasing', [PurchasingController::class, 'index'])->name('purchasing.index');
        Route::get('/purchasing/create', [PurchasingController::class, 'create'])->name('purchasing.create');
        Route::get('/purchasing/history', [PurchasingController::class, 'history'])->name('purchasing.history');
        Route::post('/purchasing', [PurchasingController::class, 'store'])->name('purchasing.store');
        Route::post('/purchasing/{purchase}/approve', [PurchasingController::class, 'approve'])->name('purchasing.approve');
        
        Route::resource('suppliers', \App\Http\Controllers\SupplierController::class)->except(['create', 'show', 'edit']);
    });

    Route::middleware(['role:cashier,owner,manager'])->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::post('/cashier-shift/open', [\App\Http\Controllers\CashierShiftController::class, 'openShift'])->name('cashier-shift.open');
        Route::post('/cashier-shift/close', [\App\Http\Controllers\CashierShiftController::class, 'closeShift'])->name('cashier-shift.close');
        
        // Cashier and Owner sales views
        Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
        Route::get('/sales/{id}/receipt', [SalesController::class, 'receipt'])->name('sales.receipt');
    });

    // ==========================================
    // FINANCE MODULE (Owner & Manager)
    // ==========================================
    Route::middleware(['role:owner,manager'])->group(function () {
        Route::get('/finance-dashboard', [\App\Http\Controllers\FinanceDashboardController::class, 'index'])->name('dashboard');
        
        // Master Akun
        Route::resource('accounts', \App\Http\Controllers\AccountController::class);
        Route::patch('accounts/{account}/toggle-status', [\App\Http\Controllers\AccountController::class, 'toggleStatus'])->name('accounts.toggle-status');
        
        // Transaksi Kas
        Route::resource('kas-masuk', \App\Http\Controllers\CashInController::class)->except(['edit', 'update']);
        Route::resource('kas-keluar', \App\Http\Controllers\CashOutController::class)->except(['edit', 'update']);
        
        // Laporan
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/cash-balance', [\App\Http\Controllers\Report\CashBalanceController::class, 'index'])->name('cash-balance');
            Route::get('/cash-mutation', [\App\Http\Controllers\Report\CashMutationController::class, 'index'])->name('cash-mutation');
            Route::get('/cash-in', [\App\Http\Controllers\Report\CashInReportController::class, 'index'])->name('cash-in');
            Route::get('/cash-out', [\App\Http\Controllers\Report\CashOutReportController::class, 'index'])->name('cash-out');
            Route::get('/sales', [\App\Http\Controllers\Report\SalesReportController::class, 'index'])->name('sales');
            Route::get('/expenses', [\App\Http\Controllers\Report\ExpenseReportController::class, 'index'])->name('expenses');
            Route::get('/profit-loss', [\App\Http\Controllers\Report\ProfitLossController::class, 'index'])->name('profit-loss');
        });
    });

    // ==========================================
    // STOCK MODULE (Manager & Owner Dropdown)
    // ==========================================
    Route::middleware(['role:owner,manager'])->prefix('stock')->name('stock.')->group(function () {
        Route::get('/inventory', [\App\Http\Controllers\StockController::class, 'index'])->name('index');
        Route::get('/inspection', [\App\Http\Controllers\StockController::class, 'inspection'])->name('inspection');
        Route::get('/rejected', [\App\Http\Controllers\StockController::class, 'rejected'])->name('rejected');
        Route::put('/materials/{material}', [\App\Http\Controllers\StockController::class, 'update'])->name('update');
        Route::post('/purchases/{purchase}/verify', [\App\Http\Controllers\StockController::class, 'verify'])->name('purchases.verify');
        Route::post('/purchases/{purchase}/reject', [\App\Http\Controllers\StockController::class, 'reject'])->name('purchases.reject');
    });
});
