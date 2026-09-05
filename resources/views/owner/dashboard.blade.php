@extends('layouts.app')

@php
    $isStoreManager = auth()->check() && auth()->user()->isManager() && !auth()->user()->isOwner();
    $isSpecificBranch = ($branchId ?? 'all') !== 'all';
    $currentBranchName = $isSpecificBranch ? ($branches->firstWhere('id', $branchId)->nama_cabang ?? 'Cabang') : 'Seluruh Cabang (Konsolidasi)';
@endphp

@section('title', $isStoreManager ? 'Dashboard Toko' : ($isSpecificBranch ? ('Dashboard Toko - ' . $currentBranchName) : 'Dashboard Enterprise'))
@section('page-title', $isStoreManager ? 'Dashboard Monitoring Toko' : ($isSpecificBranch ? ('Dashboard Monitoring Toko: ' . $currentBranchName) : 'Dashboard Monitoring ERP Enterprise (Konsolidasi)'))

@section('content')

<!-- Trading-Platform Style Top Bar (Branch Selector & Quick Timeframe Switchers) -->
<div class="bg-white border border-slate-200 rounded-2xl mb-4 p-3 shadow-sm">
    <form method="GET" action="{{ route('owner.dashboard') }}" id="dashboard-filter-form" class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-0">
        
        <div class="d-flex align-items-center gap-3 flex-wrap">
            @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin())
            <div class="d-flex align-items-center gap-2">
                <label class="fw-bold text-slate-700 text-xs d-flex align-items-center mb-0">
                    <i class="fa-solid fa-building text-blue-600 me-1.5"></i> Cabang:
                </label>
                <select name="branch_id" onchange="document.getElementById('dashboard-filter-form').submit()" class="form-select form-select-sm fw-bold border-slate-300 rounded-xl text-xs" style="min-width: 180px;">
                    <option value="all" {{ ($branchId ?? 'all') == 'all' ? 'selected' : '' }}>Semua Cabang (Global)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ ($branchId ?? '') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->nama_cabang }}
                        </option>
                    @endforeach
                </select>
            </div>
            @else
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-xl px-3 py-1.5 font-bold text-xs">
                    <i class="fa-solid fa-store me-1.5"></i> {{ auth()->user()->branch->nama_cabang ?? 'Cabang Toko' }}
                </span>
            </div>
            @endif

            <!-- Trading Timeframe Switcher Tabs (1D, 7D, 1M, 1Y, ALL) -->
            <div class="d-flex align-items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200">
                <input type="hidden" name="timeframe" id="timeframe-input" value="{{ $timeframe ?? 'month' }}">
                
                <button type="button" onclick="setTimeframe('today')" class="btn btn-sm text-xs px-2.5 py-1 rounded-lg font-bold transition {{ ($timeframe ?? '') === 'today' || ($timeframe ?? '') === '1D' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    1D (Hari Ini)
                </button>
                <button type="button" onclick="setTimeframe('7days')" class="btn btn-sm text-xs px-2.5 py-1 rounded-lg font-bold transition {{ ($timeframe ?? '') === '7days' || ($timeframe ?? '') === '7D' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    7D
                </button>
                <button type="button" onclick="setTimeframe('month')" class="btn btn-sm text-xs px-2.5 py-1 rounded-lg font-bold transition {{ ($timeframe ?? 'month') === 'month' || ($timeframe ?? '') === '1M' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    1M (Bulan Ini)
                </button>
                <button type="button" onclick="setTimeframe('year')" class="btn btn-sm text-xs px-2.5 py-1 rounded-lg font-bold transition {{ ($timeframe ?? '') === 'year' || ($timeframe ?? '') === '1Y' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    1Y (Tahun Ini)
                </button>
                <button type="button" onclick="setTimeframe('all')" class="btn btn-sm text-xs px-2.5 py-1 rounded-lg font-bold transition {{ ($timeframe ?? '') === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    All
                </button>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if(($branchId ?? 'all') === 'all')
                <span class="badge bg-blue-50 text-blue-700 border border-blue-200 rounded-lg px-2.5 py-1.5 font-bold text-xs">
                    <i class="fa-solid fa-globe me-1"></i> Konsolidasi Seluruh Cabang
                </span>
            @else
                <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg px-2.5 py-1.5 font-bold text-xs">
                    <i class="fa-solid fa-shop me-1"></i> {{ $branches->firstWhere('id', $branchId)->nama_cabang ?? 'Cabang Aktif' }}
                </span>
            @endif
        </div>
    </form>
</div>

<!-- Financial KPI Grid (5 Columns) with Percentages -->
<div class="o_form_sheet">
    @php
        $omsetBase = (float) ($totalSales ?? 0);
        $calcHppPct = $hppPct ?? ($omsetBase > 0 ? round((($totalHpp ?? 0) / $omsetBase) * 100, 1) : 0);
        $calcGrossPct = $grossPct ?? ($omsetBase > 0 ? round((($grossProfit ?? 0) / $omsetBase) * 100, 1) : 0);
        $calcOpexPct = $opexPct ?? ($omsetBase > 0 ? round((($totalOpex ?? 0) / $omsetBase) * 100, 1) : 0);
        $calcNetPct = $netPct ?? ($omsetBase > 0 ? round((($netProfit ?? 0) / $omsetBase) * 100, 1) : 0);
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-3">
        <!-- Card 1: Revenue -->
        <a href="{{ route('sales.index') }}" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex items-center justify-between hover:shadow-md hover:-translate-y-0.5 transition duration-200 text-decoration-none group cursor-pointer" style="border-left: 4px solid #1e40af !important;" title="Klik untuk membuka Laporan & Riwayat Penjualan POS">
            <div class="min-w-0 flex-grow me-2">
                <div class="flex items-center justify-between gap-1 mb-1">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0 truncate group-hover:text-blue-700">Total Omset</p>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-800 flex-shrink-0">100%</span>
                </div>
                <h4 class="text-base font-extrabold text-slate-900 font-mono truncate mb-0 group-hover:text-blue-900">Rp {{ number_format($totalSales, 0, ',', '.') }}</h4>
                <div class="flex items-center justify-between mt-1">
                    <small class="text-slate-400 text-[10px] truncate">{{ $totalTransactionsCount }} Transaksi tercatat</small>
                    <span class="text-[9px] font-bold text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity">Buka &rarr;</span>
                </div>
            </div>
            <div class="p-2.5 bg-blue-50 text-blue-700 rounded-xl flex-shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                <i class="fa-solid fa-coins text-lg"></i>
            </div>
        </a>

        <!-- Card 2: HPP -->
        <a href="{{ route('reports.profit-loss') }}" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex items-center justify-between hover:shadow-md hover:-translate-y-0.5 transition duration-200 text-decoration-none group cursor-pointer" style="border-left: 4px solid #f59e0b !important;" title="Klik untuk rincian HPP Bahan & Click Charge di Laporan Laba Rugi">
            <div class="min-w-0 flex-grow me-2">
                <div class="flex items-center justify-between gap-1 mb-1">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0 truncate group-hover:text-amber-700">HPP (COGS)</p>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 flex-shrink-0">{{ $calcHppPct }}%</span>
                </div>
                <h4 class="text-base font-extrabold text-slate-900 font-mono truncate mb-0 group-hover:text-amber-900">Rp {{ number_format($totalHpp, 0, ',', '.') }}</h4>
                <div class="flex items-center justify-between mt-1">
                    <small class="text-slate-400 text-[10px] truncate">Biaya bahan & click charge</small>
                    <span class="text-[9px] font-bold text-amber-600 opacity-0 group-hover:opacity-100 transition-opacity">Rincian &rarr;</span>
                </div>
            </div>
            <div class="p-2.5 bg-amber-50 text-amber-700 rounded-xl flex-shrink-0 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                <i class="fa-solid fa-boxes-packing text-lg"></i>
            </div>
        </a>

        <!-- Card 3: Gross Profit -->
        <a href="{{ route('reports.profit-loss') }}" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex items-center justify-between hover:shadow-md hover:-translate-y-0.5 transition duration-200 text-decoration-none group cursor-pointer" style="border-left: 4px solid #059669 !important;" title="Klik untuk melihat Laporan Laba Kotor & Laba Rugi">
            <div class="min-w-0 flex-grow me-2">
                <div class="flex items-center justify-between gap-1 mb-1">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0 truncate group-hover:text-emerald-700">Gross Profit</p>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 flex-shrink-0">{{ $calcGrossPct }}%</span>
                </div>
                <h4 class="text-base font-extrabold text-emerald-700 font-mono truncate mb-0 group-hover:text-emerald-800">Rp {{ number_format($grossProfit, 0, ',', '.') }}</h4>
                <div class="flex items-center justify-between mt-1">
                    <small class="text-slate-400 text-[10px] truncate">Omset &minus; Total HPP</small>
                    <span class="text-[9px] font-bold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">Buka &rarr;</span>
                </div>
            </div>
            <div class="p-2.5 bg-emerald-50 text-emerald-700 rounded-xl flex-shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                <i class="fa-solid fa-chart-line text-lg"></i>
            </div>
        </a>

        <!-- Card 4: OPEX -->
        <a href="{{ route('kas-keluar.index') }}" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex items-center justify-between hover:shadow-md hover:-translate-y-0.5 transition duration-200 text-decoration-none group cursor-pointer" style="border-left: 4px solid #e11d48 !important;" title="Klik untuk melihat Daftar & Bukti Kas Keluar (Beban Operasional)">
            <div class="min-w-0 flex-grow me-2">
                <div class="flex items-center justify-between gap-1 mb-1">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0 truncate group-hover:text-rose-700">OPEX (Beban)</p>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-800 flex-shrink-0">{{ $calcOpexPct }}%</span>
                </div>
                <h4 class="text-base font-extrabold text-rose-700 font-mono truncate mb-0 group-hover:text-rose-800">Rp {{ number_format($totalOpex, 0, ',', '.') }}</h4>
                <div class="flex items-center justify-between mt-1">
                    <small class="text-slate-400 text-[10px] truncate">Pengeluaran kas keluar</small>
                    <span class="text-[9px] font-bold text-rose-600 opacity-0 group-hover:opacity-100 transition-opacity">Kas Keluar &rarr;</span>
                </div>
            </div>
            <div class="p-2.5 bg-rose-50 text-rose-700 rounded-xl flex-shrink-0 group-hover:bg-rose-600 group-hover:text-white transition-colors">
                <i class="fa-solid fa-receipt text-lg"></i>
            </div>
        </a>

        <!-- Card 5: Net Profit -->
        <a href="{{ route('reports.profit-loss') }}" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex items-center justify-between hover:shadow-md hover:-translate-y-0.5 transition duration-200 text-decoration-none group cursor-pointer" style="border-left: 4px solid #6366f1 !important;" title="Klik untuk membuka Laporan Laba Rugi Komprehensif & Unduh PDF">
            <div class="min-w-0 flex-grow me-2">
                <div class="flex items-center justify-between gap-1 mb-1">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0 truncate group-hover:text-indigo-700">Net Profit</p>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold {{ $netProfit >= 0 ? 'bg-indigo-50 text-indigo-800' : 'bg-rose-50 text-rose-800' }} flex-shrink-0">{{ $calcNetPct }}%</span>
                </div>
                <h4 class="text-base font-extrabold font-mono truncate mb-0 {{ $netProfit >= 0 ? 'text-indigo-900 group-hover:text-indigo-950' : 'text-rose-700 group-hover:text-rose-800' }}">Rp {{ number_format($netProfit, 0, ',', '.') }}</h4>
                <div class="flex items-center justify-between mt-1">
                    <small class="text-slate-400 text-[10px] truncate">Gross &minus; OPEX</small>
                    <span class="text-[9px] font-bold text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity">PDF & Arsip &rarr;</span>
                </div>
            </div>
            <div class="p-2.5 bg-indigo-50 text-indigo-700 rounded-xl flex-shrink-0 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                <i class="fa-solid fa-wallet text-lg"></i>
            </div>
        </a>
    </div>

    <!-- Quick Operational Reference Links Bar -->
    <div class="bg-slate-50 rounded-xl p-2 border border-slate-200 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap text-xs">
            <span class="text-slate-500 font-bold uppercase text-[10px] tracking-wider me-1">
                <i class="fa-solid fa-link text-blue-600 me-1"></i> Tautan Modul:
            </span>
            <a href="{{ route('sales.index') }}" class="badge bg-white text-slate-700 border hover:border-blue-400 hover:text-blue-700 py-1.5 px-2.5 font-bold text-decoration-none transition">
                <i class="fa-solid fa-cart-shopping text-blue-600 me-1"></i> Penjualan POS ({{ $totalTransactionsCount }})
            </a>
            <a href="{{ route('stock.index') }}" class="badge bg-white text-slate-700 border hover:border-amber-400 hover:text-amber-800 py-1.5 px-2.5 font-bold text-decoration-none transition">
                <i class="fa-solid fa-boxes-stacked text-amber-600 me-1"></i> Stok ({{ $totalMaterialsCount }} Bahan @if($lowStockCount > 0)<span class="text-rose-600 ms-0.5">• {{ $lowStockCount }} Menipis</span>@endif)
            </a>
            <a href="{{ route('purchasing.plans.index') }}" class="badge bg-white text-slate-700 border hover:border-purple-400 hover:text-purple-800 py-1.5 px-2.5 font-bold text-decoration-none transition">
                <i class="fa-solid fa-clipboard-check text-purple-600 me-1"></i> Pengadaan PO ({{ $pendingPOCount }} ACC)
            </a>
            <a href="{{ route('daily-closing.index') }}" class="badge bg-white text-slate-700 border hover:border-emerald-400 hover:text-emerald-800 py-1.5 px-2.5 font-bold text-decoration-none transition">
                <i class="fa-solid fa-file-invoice-dollar text-emerald-600 me-1"></i> Tutup Kas Harian
            </a>
            <a href="{{ route('dashboard') }}" class="badge bg-white text-slate-700 border hover:border-blue-400 hover:text-blue-800 py-1.5 px-2.5 font-bold text-decoration-none transition">
                <i class="fa-solid fa-building-columns text-blue-600 me-1"></i> Jurnal Kas & Saldo
            </a>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('reports.product-sales') }}" class="btn btn-sm btn-outline-primary rounded-lg py-1 px-2.5 text-xs font-bold text-decoration-none d-inline-flex align-items-center gap-1.5 bg-white shadow-xs" title="Laporan Produk Terjual, Pemakaian Bahan & Arsip Bulanan">
                <i class="fa-solid fa-boxes-stacked text-blue-600"></i>
                <span>Produk & Bahan &rarr;</span>
            </a>
            <a href="{{ route('reports.profit-loss') }}" class="btn btn-sm btn-primary rounded-lg py-1 px-3 text-xs font-bold text-decoration-none d-inline-flex align-items-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-file-pdf"></i>
                <span>Laba Rugi & Arsip PDF &rarr;</span>
            </a>
        </div>
    </div>
</div>

<!-- Interactive Trading-Platform Style Chart Section -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm h-100">
            <!-- Chart Header with Trading Metrics -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pb-3 mb-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-slate-900 text-white d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fa-solid fa-chart-candlestick text-xs"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 font-extrabold text-slate-900 text-sm">Grafik Performa Penjualan (Live Chart)</h6>
                        <span class="text-[10px] text-slate-400 font-semibold">Tampilan Omset & Volume Transaksi</span>
                    </div>
                </div>

                <!-- Trading Highlights (High, Low, Avg) -->
                <div class="d-flex align-items-center gap-2 text-xs">
                    <div class="px-2 py-1 bg-slate-50 rounded-lg border text-slate-600">
                        <span class="text-[9px] text-slate-400 font-bold uppercase block">High</span>
                        <span class="font-mono font-bold text-emerald-700">Rp {{ number_format($highestSales, 0, ',', '.') }}</span>
                    </div>
                    <div class="px-2 py-1 bg-slate-50 rounded-lg border text-slate-600">
                        <span class="text-[9px] text-slate-400 font-bold uppercase block">Low</span>
                        <span class="font-mono font-bold text-rose-700">Rp {{ number_format($lowestSales, 0, ',', '.') }}</span>
                    </div>
                    <div class="px-2 py-1 bg-blue-50 rounded-lg border border-blue-200 text-blue-900">
                        <span class="text-[9px] text-blue-600 font-bold uppercase block">Avg</span>
                        <span class="font-mono font-bold text-blue-950">Rp {{ number_format($avgSales, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- ApexCharts Container -->
            <div id="trading-main-chart" style="min-height: 330px;"></div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm h-100 d-flex flex-column justify-between">
            <div class="d-flex justify-content-between align-items-center pb-2 border-bottom mb-3">
                <h6 class="mb-0 font-extrabold text-slate-900 text-sm d-flex align-items-center gap-1.5">
                    <i class="fa-solid fa-chart-pie text-emerald-600"></i> Distribusi Pembayaran
                </h6>
                <span class="badge bg-slate-100 text-slate-600 text-[10px]">{{ $timeframe ?? 'month' }}</span>
            </div>

            <div id="payment-donut-chart" class="w-100 flex-grow-1 d-flex align-items-center justify-content-center" style="min-height: 240px;"></div>

            <div class="grid grid-cols-3 gap-2 text-center text-xs pt-3 border-top mt-2">
                <a href="{{ route('sales.index', ['payment_method' => 'Cash']) }}" class="p-2 rounded-xl bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 hover:shadow-xs transition text-decoration-none group d-block" title="Klik untuk memfilter transaksi Cash (Tunai)">
                    <span class="text-[9px] font-bold text-emerald-800 uppercase block group-hover:underline">Cash <i class="fa-solid fa-arrow-up-right-from-square text-[8px] ms-0.5"></i></span>
                    <span class="font-mono font-bold text-emerald-950 text-[11px] block mt-0.5">Rp {{ number_format($cashSales, 0, ',', '.') }}</span>
                </a>
                <a href="{{ route('sales.index', ['payment_method' => 'QRIS']) }}" class="p-2 rounded-xl bg-blue-50 border border-blue-200 hover:bg-blue-100 hover:shadow-xs transition text-decoration-none group d-block" title="Klik untuk memfilter transaksi QRIS">
                    <span class="text-[9px] font-bold text-blue-800 uppercase block group-hover:underline">QRIS <i class="fa-solid fa-arrow-up-right-from-square text-[8px] ms-0.5"></i></span>
                    <span class="font-mono font-bold text-blue-950 text-[11px] block mt-0.5">Rp {{ number_format($qrisSales, 0, ',', '.') }}</span>
                </a>
                <a href="{{ route('sales.index', ['payment_method' => 'Transfer']) }}" class="p-2 rounded-xl bg-amber-50 border border-amber-200 hover:bg-amber-100 hover:shadow-xs transition text-decoration-none group d-block" title="Klik untuk memfilter transaksi Transfer Bank">
                    <span class="text-[9px] font-bold text-amber-800 uppercase block group-hover:underline">Transfer <i class="fa-solid fa-arrow-up-right-from-square text-[8px] ms-0.5"></i></span>
                    <span class="font-mono font-bold text-amber-950 text-[11px] block mt-0.5">Rp {{ number_format($transferSales, 0, ',', '.') }}</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions Table Card -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-4">
    <div class="p-3.5 bg-white border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 font-extrabold text-slate-900 text-xs d-flex align-items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
            <span>Transaksi Penjualan Terkini</span>
        </h6>
        <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary rounded-xl px-3 py-1 text-xs font-bold d-inline-flex align-items-center gap-1">
            <span>Lihat Semua Penjualan</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-xs">
            <thead class="bg-slate-50 text-slate-600 border-bottom font-bold">
                <tr>
                    <th class="ps-3 py-2.5">No. Invoice</th>
                    <th class="py-2.5">Waktu</th>
                    <th class="py-2.5">Cabang</th>
                    <th class="py-2.5">Kasir</th>
                    <th class="py-2.5">Metode Bayar</th>
                    <th class="py-2.5 text-end">Total Belanja</th>
                    <th class="pe-3 py-2.5 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTransactions as $tx)
                    @php
                        $invItems = $tx->transactionDetails->map(function($d) {
                            return [
                                'material_name' => $d->material->material_name ?? 'Bahan Cetak',
                                'qty_ordered' => $d->qty_ordered,
                                'selling_price' => $d->selling_price,
                                'subtotal' => $d->qty_ordered * $d->selling_price,
                            ];
                        });
                        $invPayload = [
                            'invoice_number' => $tx->invoice_number,
                            'created_at' => $tx->created_at->format('d M Y H:i'),
                            'cashier_name' => $tx->user->full_name ?: ($tx->user->username ?? 'Kasir'),
                            'branch_name' => $tx->branch->nama_cabang ?? 'Pusat',
                            'payment_method' => $tx->payment_method ?? 'Cash',
                            'payment_status' => $tx->payment_status,
                            'paid_amount' => $tx->paid_amount,
                            'remaining_amount' => $tx->remaining_amount,
                            'customer_name' => $tx->customer_name,
                            'customer_phone' => $tx->customer_phone,
                            'total_price' => $tx->total_price,
                            'items' => $invItems
                        ];
                    @endphp
                    <tr>
                        <td class="ps-3 font-mono font-bold text-blue-900">
                            <button type="button" 
                                    class="btn btn-link p-0 font-mono font-bold text-blue-700 text-decoration-none d-inline-flex align-items-center gap-1 hover:underline text-xs"
                                    onclick='openSnaprintInvoice(@json($invPayload))'>
                                <i class="fa-solid fa-file-invoice text-blue-600 text-xs"></i>
                                <span>{{ $tx->invoice_number }}</span>
                            </button>
                        </td>
                        <td class="text-slate-500 font-semibold">{{ $tx->created_at->format('d M Y, H:i') }}</td>
                        <td>
                            <span class="badge bg-slate-100 text-slate-700 border text-[10px]">
                                {{ $tx->branch->nama_cabang ?? 'Pusat' }}
                            </span>
                        </td>
                        <td class="font-semibold text-slate-700">{{ $tx->user->full_name ?: ($tx->user->username ?? '-') }}</td>
                        <td>
                            @if($tx->payment_method === 'Cash')
                                <span class="badge bg-emerald-100 text-emerald-800 text-[10px]">💵 Cash</span>
                            @elseif($tx->payment_method === 'QRIS')
                                <span class="badge bg-blue-100 text-blue-800 text-[10px]">📱 QRIS</span>
                            @else
                                <span class="badge bg-amber-100 text-amber-800 text-[10px]">🏦 Transfer</span>
                            @endif
                        </td>
                        <td class="text-end font-mono font-bold text-slate-900">
                            Rp {{ number_format($tx->total_price, 0, ',', '.') }}
                        </td>
                        <td class="pe-3 text-center">
                            <button type="button" class="btn btn-sm btn-light border py-0 px-2 text-blue-700" title="Buka Dokumen Faktur" onclick='openSnaprintInvoice(@json($invPayload))'>
                                <i class="fa-solid fa-eye text-xs"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-slate-400">Belum ada data transaksi penjualan pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    function setTimeframe(tf) {
        document.getElementById('timeframe-input').value = tf;
        document.getElementById('dashboard-filter-form').submit();
    }

    document.addEventListener("DOMContentLoaded", function () {
        // 1. Trading-Style Multi-Axis Chart (Omset Area + Volume Bar)
        const tradingChartOptions = {
            series: [
                {
                    name: 'Total Omset (Rp)',
                    type: 'area',
                    data: @json($chartSales)
                },
                {
                    name: 'Volume Transaksi (Trx)',
                    type: 'column',
                    data: @json($chartVolume)
                }
            ],
            chart: {
                height: 330,
                type: 'line',
                stacked: false,
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            stroke: {
                width: [3, 0],
                curve: 'smooth'
            },
            plotOptions: {
                bar: {
                    columnWidth: '35%',
                    borderRadius: 4
                }
            },
            colors: ['#1e40af', '#94a3b8'],
            fill: {
                type: ['gradient', 'solid'],
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#3b82f6'],
                    inverseColors: false,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 100]
                }
            },
            labels: @json($chartLabels),
            markers: {
                size: 4,
                colors: ['#1e40af'],
                strokeColors: '#ffffff',
                strokeWidth: 2,
                hover: { size: 6 }
            },
            xaxis: {
                type: 'category',
                labels: {
                    style: { fontSize: '10px', fontWeight: 600, colors: '#64748b' }
                }
            },
            yaxis: [
                {
                    title: { text: 'Omset Penjualan (Rp)', style: { fontSize: '11px', fontWeight: 700, color: '#1e40af' } },
                    labels: {
                        formatter: function (val) {
                            return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                        },
                        style: { fontSize: '10px', colors: '#64748b' }
                    }
                },
                {
                    opposite: true,
                    title: { text: 'Volume (Trx)', style: { fontSize: '11px', fontWeight: 700, color: '#94a3b8' } },
                    labels: {
                        formatter: function (val) {
                            return Math.round(val) + " Trx";
                        },
                        style: { fontSize: '10px', colors: '#64748b' }
                    }
                }
            ],
            tooltip: {
                shared: true,
                intersect: false,
                theme: 'dark',
                y: [
                    {
                        formatter: function (y) {
                            return typeof y !== "undefined" ? "Rp " + new Intl.NumberFormat('id-ID').format(y) : y;
                        }
                    },
                    {
                        formatter: function (y) {
                            return typeof y !== "undefined" ? Math.round(y) + " Transaksi" : y;
                        }
                    }
                ]
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 4
            }
        };

        const mainChart = new ApexCharts(document.querySelector("#trading-main-chart"), tradingChartOptions);
        mainChart.render();

        // 2. Payment Donut Chart
        const paymentChartOptions = {
            series: [{{ (float)$cashSales }}, {{ (float)$qrisSales }}, {{ (float)$transferSales }}],
            labels: ['Cash (Tunai)', 'QRIS', 'Transfer Bank'],
            chart: {
                type: 'donut',
                height: 240
            },
            colors: ['#059669', '#2563eb', '#d97706'],
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { enabled: true },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                    }
                }
            }
        };
        const paymentChart = new ApexCharts(document.querySelector("#payment-donut-chart"), paymentChartOptions);
        paymentChart.render();
    });
</script>
@endsection
