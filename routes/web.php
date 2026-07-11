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
    Route::middleware(['role:owner'])->group(function () {
        Route::get('/owner/dashboard', [OwnerController::class, 'dashboard'])->name('owner.dashboard');
        
        // Owner only sales actions
        Route::get('/sales/{id}/edit', [SalesController::class, 'edit'])->name('sales.edit');
        Route::put('/sales/{id}', [SalesController::class, 'update'])->name('sales.update');
        Route::post('/sales/{id}/refund', [SalesController::class, 'refund'])->name('sales.refund');
    });

    Route::middleware(['role:purchasing,owner'])->group(function () {
        Route::get('/purchasing', [PurchasingController::class, 'index'])->name('purchasing.index');
        Route::post('/purchasing', [PurchasingController::class, 'store'])->name('purchasing.store');
    });

    Route::middleware(['role:cashier,owner'])->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        
        // Cashier and Owner sales views
        Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
        Route::get('/sales/{id}/receipt', [SalesController::class, 'receipt'])->name('sales.receipt');
    });
});
