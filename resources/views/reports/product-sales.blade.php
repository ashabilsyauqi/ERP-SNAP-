@extends('layouts.app')

@section('title', 'Laporan Produk Terjual & Pemakaian Bahan')
@section('page-title', 'Laporan Produk Terjual & Pemakaian Bahan Baku')

@section('action-buttons')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <!-- Unduh Ringkasan PDF Bulanan -->
    <a href="{{ route('reports.product-sales.export-pdf', array_merge(request()->all(), ['month' => $month, 'year' => $year])) }}" class="btn-odoo-primary text-decoration-none d-inline-flex align-items-center gap-1.5" target="_blank" title="Unduh berkas PDF resmi laporan periode ini">
        <i class="fa-solid fa-file-pdf text-rose-300"></i>
        <span>Unduh PDF Bulanan</span>
    </a>

    <!-- Simpan ke Arsip Bulanan Button (Buka Modal) -->
    <button type="button" class="btn-odoo-secondary text-slate-800 d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#archiveModal" title="Simpan snapshot laporan bulanan ini ke daftar arsip server">
        <i class="fa-solid fa-box-archive text-blue-600"></i>
        <span>Simpan ke Arsip Bulanan</span>
    </button>

    <!-- Cetak Halaman -->
    <button type="button" onclick="window.print()" class="btn-odoo-secondary text-slate-700">
        <i class="fa-solid fa-print"></i>
        <span>Cetak</span>
    </button>
</div>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-xl mb-3 d-flex align-items-center gap-2 text-xs" role="alert">
        <i class="fa-solid fa-circle-check fs-6 text-emerald-600"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-xl mb-3 d-flex align-items-center gap-2 text-xs" role="alert">
        <i class="fa-solid fa-circle-exclamation fs-6 text-rose-600"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Filter Toolbar -->
    <div class="o_form_sheet mb-4 p-3 bg-white print:hidden">
        <form method="GET" action="{{ route('reports.product-sales') }}" class="row g-2 align-items-end" id="filter-form">
            <!-- Period Type Selector -->
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label font-bold text-slate-700 text-xs uppercase mb-1">
                    <i class="fa-solid fa-calendar-days text-blue-600 me-1"></i> Mode Periode
                </label>
                <select name="period_type" id="period_type" class="form-select form-select-sm fw-bold border-slate-300">
                    <option value="daily" {{ $periodType == 'daily' ? 'selected' : '' }}>Harian (Daily)</option>
                    <option value="weekly" {{ $periodType == 'weekly' ? 'selected' : '' }}>Mingguan (Weekly)</option>
                    <option value="monthly" {{ $periodType == 'monthly' ? 'selected' : '' }}>Bulanan (Monthly)</option>
                </select>
            </div>

            <!-- Daily Date Selector -->
            <div class="col-12 col-sm-6 col-md-3" id="daily_selector" style="display: {{ $periodType == 'daily' ? 'block' : 'none' }}">
                <label class="form-label font-bold text-slate-700 text-xs uppercase mb-1">Pilih Tanggal</label>
                <input type="date" name="date" class="form-select form-select-sm fw-bold border-slate-300" value="{{ $date }}">
            </div>

            <!-- Weekly Date Selector -->
            <div class="col-12 col-sm-6 col-md-3" id="weekly_selector" style="display: {{ $periodType == 'weekly' ? 'block' : 'none' }}">
                <label class="form-label font-bold text-slate-700 text-xs uppercase mb-1">Pilih Minggu (Tanggal Acuan)</label>
                <input type="date" name="week_date" class="form-select form-select-sm fw-bold border-slate-300" value="{{ request('week_date', date('Y-m-d')) }}">
            </div>
            
            <!-- Monthly Selector (Bulan & Tahun) -->
            <div class="col-12 col-sm-6 col-md-2" id="month_selector" style="display: {{ $periodType == 'monthly' ? 'block' : 'none' }}">
                <label class="form-label font-bold text-slate-700 text-xs uppercase mb-1">Bulan</label>
                <select name="month" class="form-select form-select-sm fw-bold border-slate-300">
                    @for($i=1; $i<=12; $i++)
                        <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>
                            {{ date('F', mktime(0,0,0,$i,1)) }}
                        </option>
                    @endfor
                </select>
            </div>
            
            <div class="col-12 col-sm-6 col-md-2" id="year_selector" style="display: {{ $periodType == 'monthly' ? 'block' : 'none' }}">
                <label class="form-label font-bold text-slate-700 text-xs uppercase mb-1">Tahun</label>
                <select name="year" class="form-select form-select-sm fw-bold border-slate-300">
                    @php $startYear = date('Y') - 5; @endphp
                    @for($i = date('Y'); $i >= $startYear; $i--)
                        <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>

            <!-- Branch Filter (Owner / SuperAdmin) -->
            @if(Auth::user()->isOwner() || Auth::user()->isSuperAdmin())
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label font-bold text-slate-700 text-xs uppercase mb-1">
                    <i class="fa-solid fa-building text-blue-600 me-1"></i> Cabang
                </label>
                <select name="branch_id" class="form-select form-select-sm fw-bold border-slate-300">
                    <option value="all" {{ ($branchId ?? 'all') === 'all' ? 'selected' : '' }}>Semua Cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ ($branchId ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-12 col-sm-6 col-md-2">
                <button type="submit" class="btn-odoo-primary w-100 py-1.5 text-xs font-bold">
                    <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4 print:hidden">
        <!-- Card 1: Total Qty Terjual -->
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between border-l-4 border-l-blue-600">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Produk Terjual</span>
                <span class="font-mono font-extrabold text-blue-900 text-base">{{ number_format($totalItemsSold, 0, ',', '.') }} <span class="text-xs font-medium text-slate-500">pcs</span></span>
                <span class="text-[11px] text-slate-500 d-block mt-0.5">{{ count($productsSold) }} jenis produk aktif</span>
            </div>
            <div class="p-2.5 rounded-xl bg-blue-50 text-blue-600">
                <i class="fa-solid fa-boxes-stacked text-base"></i>
            </div>
        </div>

        <!-- Card 2: Total Omzet Penjualan -->
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between border-l-4 border-l-sky-500">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Omzet Penjualan</span>
                <span class="font-mono font-extrabold text-sky-800 text-base">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</span>
                <a href="{{ route('reports.sales') }}" class="text-[11px] text-sky-600 hover:underline font-medium d-block mt-0.5">
                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px] me-1"></i>Rincian Transaksi POS
                </a>
            </div>
            <div class="p-2.5 rounded-xl bg-sky-50 text-sky-600">
                <i class="fa-solid fa-cash-register text-base"></i>
            </div>
        </div>

        <!-- Card 3: Total Biaya Bahan Terpakai -->
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between border-l-4 border-l-amber-500">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Biaya Bahan Terpakai</span>
                <span class="font-mono font-extrabold text-amber-800 text-base">Rp {{ number_format($totalMaterialCost, 0, ',', '.') }}</span>
                <span class="text-[11px] text-slate-500 d-block mt-0.5">{{ $totalMaterialsCount }} bahan baku terpakai</span>
            </div>
            <a href="{{ route('materials.index') }}" class="p-2.5 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100 transition" title="Kelola Bahan Baku">
                <i class="fa-solid fa-layer-group text-base"></i>
            </a>
        </div>

        <!-- Card 4: Gross Profit & Margin -->
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between border-l-4 border-l-emerald-500">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Laba Kotor (Gross Profit)</span>
                <span class="font-mono font-extrabold text-emerald-800 text-base">Rp {{ number_format($grossProfit, 0, ',', '.') }}</span>
                <span class="badge bg-emerald-100 text-emerald-800 font-bold text-[10px] mt-0.5">
                    Margin: {{ number_format($grossMarginPct, 1) }}%
                </span>
            </div>
            <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600">
                <i class="fa-solid fa-chart-pie text-base"></i>
            </div>
        </div>
    </div>

    <!-- Subheader Info Banner -->
    <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 mb-4 flex flex-wrap items-center justify-between gap-2 text-xs">
        <div class="d-flex items-center gap-2">
            <span class="badge bg-blue-600 text-white font-semibold uppercase text-[10px] px-2 py-1">
                {{ strtoupper($periodType) }}
            </span>
            <span class="text-slate-700 font-semibold">
                Periode: <strong class="text-slate-900">{{ $periodLabel }}</strong>
            </span>
            <span class="text-slate-400">|</span>
            <span class="text-slate-600">
                <i class="fa-solid fa-building text-blue-600 me-1"></i> Cabang: <strong>{{ $branchName }}</strong>
            </span>
        </div>
        <div class="text-slate-500 text-[11px]">
            Diperbarui: {{ \Carbon\Carbon::now()->translatedFormat('d M Y, H:i') }} WIB
        </div>
    </div>

    <!-- Nav Tabs Component -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-5">
        <div class="border-b border-slate-200 px-4 pt-3 bg-slate-50/70">
            <ul class="nav nav-tabs border-bottom-0 gap-2" id="reportTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active font-bold text-xs py-2.5 px-3.5 rounded-t-lg border-0 border-b-2 border-b-blue-600 bg-white text-blue-700 d-inline-flex align-items-center gap-2" id="tab-products-btn" data-bs-toggle="tab" data-bs-target="#tab-products" type="button" role="tab">
                        <i class="fa-solid fa-basket-shopping text-blue-600"></i>
                        <span>Produk Terjual ({{ count($productsSold) }})</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link font-bold text-xs py-2.5 px-3.5 rounded-t-lg border-0 text-slate-600 hover:text-slate-900 d-inline-flex align-items-center gap-2" id="tab-materials-btn" data-bs-toggle="tab" data-bs-target="#tab-materials" type="button" role="tab">
                        <i class="fa-solid fa-cubes-stacked text-amber-600"></i>
                        <span>Bahan Terpakai &amp; Biaya Bahan ({{ count($materialsUsed) }})</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link font-bold text-xs py-2.5 px-3.5 rounded-t-lg border-0 text-slate-600 hover:text-slate-900 d-inline-flex align-items-center gap-2" id="tab-archives-btn" data-bs-toggle="tab" data-bs-target="#tab-archives" type="button" role="tab">
                        <i class="fa-solid fa-box-archive text-emerald-600"></i>
                        <span>Arsip Laporan Bulanan ({{ $archives->count() }})</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content p-4" id="reportTabsContent">
            
            <!-- TAB 1: PRODUK TERJUAL -->
            <div class="tab-pane fade show active" id="tab-products" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="font-extrabold text-slate-800 text-sm mb-0.5">Rincian Penjualan Produk &amp; Layanan Kasir</h6>
                        <p class="text-xs text-slate-500 mb-0">Akumulasi volume barang yang terjual, total omzet kotor, serta estimasi modal bahan</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0 text-xs">
                        <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[11px] border-b border-slate-200">
                            <tr>
                                <th class="ps-3 py-2.5 text-center" style="width: 4%;">No</th>
                                <th class="py-2.5" style="width: 28%;">Nama Produk / Jasa</th>
                                <th class="py-2.5" style="width: 14%;">Kategori</th>
                                <th class="py-2.5 text-center" style="width: 12%;">Volume Terjual</th>
                                <th class="py-2.5 text-end" style="width: 14%;">Total Omzet</th>
                                <th class="py-2.5 text-end" style="width: 14%;">Biaya HPP Bahan</th>
                                <th class="py-2.5 text-end" style="width: 14%;">Laba Kotor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($productsSold as $index => $prod)
                            @php
                                $pMargin = $prod['total_omzet'] > 0 ? (($prod['gross_profit'] / $prod['total_omzet']) * 100) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="ps-3 text-center text-slate-400 font-medium">{{ $index + 1 }}</td>
                                <td>
                                    <div class="font-bold text-slate-900 text-xs">{{ $prod['product_name'] }}</div>
                                    @if($prod['material_id'])
                                        <a href="{{ route('materials.index', ['search' => $prod['product_name']]) }}" class="text-[10px] text-blue-600 hover:underline inline-flex items-center gap-1 mt-0.5">
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i> Cek Master Bahan
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-slate-100 text-slate-700 border text-[10px] px-2 py-0.5 font-medium">
                                        {{ $prod['category'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="font-bold text-slate-800">{{ number_format($prod['qty_sold'], 0, ',', '.') }}</span>
                                    <span class="text-slate-500 text-[10px]">pcs</span>
                                    @if($prod['is_area_based'] && $prod['area_sold'] > 0)
                                        <div class="text-[10px] text-slate-500 font-mono">({{ number_format($prod['area_sold'], 2, ',', '.') }} m²)</div>
                                    @endif
                                </td>
                                <td class="text-end font-mono font-bold text-blue-900">
                                    Rp {{ number_format($prod['total_omzet'], 0, ',', '.') }}
                                </td>
                                <td class="text-end font-mono text-amber-800">
                                    Rp {{ number_format($prod['total_material_cost'], 0, ',', '.') }}
                                </td>
                                <td class="text-end font-mono">
                                    <span class="font-bold {{ $prod['gross_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        Rp {{ number_format($prod['gross_profit'], 0, ',', '.') }}
                                    </span>
                                    <div class="text-[10px] text-slate-500 font-sans">
                                        Margin: <span class="font-semibold text-emerald-700">{{ number_format($pMargin, 1) }}%</span>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-slate-400">
                                    <i class="fa-solid fa-basket-shopping fs-3 text-slate-300 d-block mb-2"></i>
                                    <span>Belum ada transaksi penjualan produk yang tercatat pada periode ini.</span>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($productsSold) > 0)
                        <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-300 text-slate-900">
                            <tr>
                                <td colspan="3" class="ps-3 py-2.5 uppercase tracking-wide">TOTAL SELURUH PRODUK:</td>
                                <td class="text-center py-2.5 font-mono text-xs">{{ number_format($totalItemsSold, 0, ',', '.') }} pcs</td>
                                <td class="text-end py-2.5 font-mono text-blue-900 text-xs">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</td>
                                <td class="text-end py-2.5 font-mono text-amber-800 text-xs">Rp {{ number_format($totalMaterialCost, 0, ',', '.') }}</td>
                                <td class="text-end py-2.5 font-mono text-emerald-700 text-xs">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- TAB 2: BAHAN BAKU TERPAKAI & BIAYA BAHAN -->
            <div class="tab-pane fade" id="tab-materials" role="tabpanel">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-3 text-xs text-amber-900 d-flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-info text-amber-600 fs-6 mt-0.5"></i>
                    <div>
                        <strong>Formulasi Biaya Pemakaian Bahan Baku:</strong><br>
                        <span>Total Biaya Bahan = <code>(Volume Bahan Baku Terpakai × Harga Beli Bahan) + (Click Charge Mesin × Qty Cetak)</code>. Menghitung akumulasi pemakaian fisik bahan mentah untuk memantau efisiensi stok dan biaya modal percetakan.</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0 text-xs">
                        <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[11px] border-b border-slate-200">
                            <tr>
                                <th class="ps-3 py-2.5 text-center" style="width: 4%;">No</th>
                                <th class="py-2.5" style="width: 25%;">Nama Bahan Baku</th>
                                <th class="py-2.5" style="width: 14%;">Kategori</th>
                                <th class="py-2.5 text-center" style="width: 10%;">Satuan</th>
                                <th class="py-2.5 text-center" style="width: 12%;">Qty Terpakai</th>
                                <th class="py-2.5 text-end" style="width: 13%;">Harga Beli Satuan</th>
                                <th class="py-2.5 text-end" style="width: 14%;">Total Biaya Bahan</th>
                                <th class="py-2.5 text-center" style="width: 8%;">Sisa Stok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php $accMaterialCost = 0; @endphp
                            @forelse($materialsUsed as $index => $mat)
                            @php $accMaterialCost += $mat['total_material_cost']; @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="ps-3 text-center text-slate-400 font-medium">{{ $index + 1 }}</td>
                                <td>
                                    <div class="font-bold text-slate-900 text-xs">{{ $mat['material_name'] }}</div>
                                    <a href="{{ route('materials.index', ['search' => $mat['material_name']]) }}" class="text-[10px] text-blue-600 hover:underline inline-flex items-center gap-1 mt-0.5">
                                        <i class="fa-solid fa-boxes-stacked text-[9px]"></i> Lihat Kartu Stok
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-amber-50 text-amber-800 border border-amber-200 text-[10px] px-2 py-0.5 font-medium">
                                        {{ $mat['category'] }}
                                    </span>
                                </td>
                                <td class="text-center font-medium text-slate-600">{{ $mat['unit'] }}</td>
                                <td class="text-center font-bold text-slate-900 font-mono">
                                    {{ $mat['is_area'] ? number_format($mat['usage_qty'], 2, ',', '.') : number_format($mat['usage_qty'], 0, ',', '.') }}
                                </td>
                                <td class="text-end font-mono text-slate-700">
                                    Rp {{ number_format($mat['purchase_price'], 0, ',', '.') }}
                                    @if($mat['click_charge'] > 0)
                                        <div class="text-[10px] text-amber-700 font-sans">+Click Rp{{ number_format($mat['click_charge'], 0, ',', '.') }}</div>
                                    @endif
                                </td>
                                <td class="text-end font-mono font-bold text-amber-900 text-xs">
                                    Rp {{ number_format($mat['total_material_cost'], 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $mat['current_stock'] <= 5 ? 'bg-rose-100 text-rose-700 border border-rose-300' : 'bg-slate-100 text-slate-700 border' }} font-bold text-[10px] px-2 py-0.5">
                                        {{ number_format($mat['current_stock'], 0, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-slate-400">
                                    <i class="fa-solid fa-layer-group fs-3 text-slate-300 d-block mb-2"></i>
                                    <span>Belum ada bahan baku yang terpakai pada transaksi periode ini.</span>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($materialsUsed) > 0)
                        <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-300 text-slate-900">
                            <tr>
                                <td colspan="6" class="ps-3 py-2.5 uppercase tracking-wide">TOTAL BIAYA SELURUH BAHAN TERPAKAI:</td>
                                <td class="text-end py-2.5 font-mono text-amber-900 text-xs">Rp {{ number_format($accMaterialCost, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- TAB 3: ARSIP LAPORAN BULANAN (MONTHLY REPORT PDF ARCHIVE) -->
            <div class="tab-pane fade" id="tab-archives" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="font-extrabold text-slate-800 text-sm mb-0.5">Daftar Arsip Laporan Bulanan (Monthly Report Archives)</h6>
                        <p class="text-xs text-slate-500 mb-0">Rangkuman laporan penjualan produk dan pemakaian bahan bulanan yang telah dibukukan dalam format PDF resmi</p>
                    </div>
                    <button type="button" class="btn-odoo-primary py-1.5 px-3 text-xs font-bold d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#archiveModal">
                        <i class="fa-solid fa-circle-plus"></i>
                        <span>Arsipkan Bulan Berjalan</span>
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0 text-xs">
                        <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[11px] border-b border-slate-200">
                            <tr>
                                <th class="ps-3 py-2.5" style="width: 14%;">Tanggal Arsip</th>
                                <th class="py-2.5" style="width: 16%;">Periode Laporan</th>
                                <th class="py-2.5" style="width: 12%;">Cabang</th>
                                <th class="py-2.5 text-center" style="width: 10%;">Produk (Pcs)</th>
                                <th class="py-2.5 text-end" style="width: 12%;">Total Omzet</th>
                                <th class="py-2.5 text-end" style="width: 12%;">Biaya Bahan</th>
                                <th class="py-2.5 text-end" style="width: 12%;">Laba Kotor</th>
                                <th class="pe-3 py-2.5 text-center" style="width: 12%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($archives as $arc)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="ps-3 text-slate-500 font-medium whitespace-nowrap">
                                    {{ $arc->created_at->translatedFormat('d M Y, H:i') }}
                                </td>
                                <td class="font-bold text-slate-900 whitespace-nowrap">
                                    <i class="fa-solid fa-calendar-check text-blue-600 me-1"></i>
                                    {{ $arc->period_label }}
                                </td>
                                <td class="whitespace-nowrap">
                                    <span class="badge bg-slate-100 text-slate-700 border text-[10px]">
                                        {{ $arc->branch->nama_cabang ?? 'Semua Cabang' }}
                                    </span>
                                </td>
                                <td class="text-center font-mono font-bold text-slate-800 whitespace-nowrap">
                                    {{ number_format($arc->total_items_sold, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-mono font-bold text-sky-800 whitespace-nowrap">
                                    Rp {{ number_format($arc->total_omzet, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-mono text-amber-800 whitespace-nowrap">
                                    Rp {{ number_format($arc->total_material_cost, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-mono font-extrabold text-emerald-800 whitespace-nowrap">
                                    Rp {{ number_format($arc->gross_profit, 0, ',', '.') }}
                                </td>
                                <td class="pe-3 text-center whitespace-nowrap">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <a href="{{ route('reports.product-sales.archive.download', $arc->id) }}" class="btn btn-sm btn-outline-primary py-0.5 px-2 text-[11px] font-bold d-inline-flex align-items-center gap-1" title="Unduh Berkas PDF Arsip">
                                            <i class="fa-solid fa-file-arrow-down text-rose-600"></i>
                                            <span>PDF</span>
                                        </a>
                                        @if(Auth::user()->isOwner() || Auth::user()->isSuperAdmin() || Auth::user()->isManager())
                                        <form method="POST" action="{{ route('reports.product-sales.archive.destroy', $arc->id) }}" class="d-inline mb-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus berkas arsip laporan produk bulanan ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border py-0.5 px-1.5 text-rose-600 hover:bg-rose-50" title="Hapus Arsip">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-slate-400">
                                    <i class="fa-solid fa-box-archive fs-3 text-slate-300 d-block mb-2"></i>
                                    <span>Belum ada arsip laporan bulanan yang disimpan. Klik tombol <strong>"Simpan ke Arsip Bulanan"</strong> untuk membukukan laporan bulanan ke file PDF permanen.</span>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- Modal Simpan Arsip Bulanan -->
<div class="modal fade" id="archiveModal" tabindex="-1" aria-labelledby="archiveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl border-0 shadow-lg">
            <form method="POST" action="{{ route('reports.product-sales.archive.store') }}">
                @csrf
                <div class="modal-header border-b border-slate-100 pb-3">
                    <h5 class="modal-title font-extrabold text-slate-800 text-sm d-flex align-items-center gap-2" id="archiveModalLabel">
                        <i class="fa-solid fa-box-archive text-blue-600"></i>
                        <span>Simpan Snapshot Laporan Bulanan ke Arsip</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-xs">
                    <p class="text-slate-600 mb-3">
                        Sistem akan mengunci ringkasan produk terjual dan biaya bahan baku periode ini, kemudian menghasilkan berkas fisik PDF resmi yang disimpan ke arsip digital.
                    </p>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label font-bold text-slate-700">Pilih Bulan</label>
                            <select name="month" class="form-select form-select-sm fw-bold border-slate-300">
                                @for($i=1; $i<=12; $i++)
                                    <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>
                                        {{ date('F', mktime(0,0,0,$i,1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label font-bold text-slate-700">Pilih Tahun</label>
                            <select name="year" class="form-select form-select-sm fw-bold border-slate-300">
                                @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                                    <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    @if(Auth::user()->isOwner() || Auth::user()->isSuperAdmin())
                    <div class="mb-3">
                        <label class="form-label font-bold text-slate-700">Cabang</label>
                        <select name="branch_id" class="form-select form-select-sm fw-bold border-slate-300">
                            <option value="all" {{ ($branchId ?? 'all') === 'all' ? 'selected' : '' }}>Semua Cabang (Konsolidasi)</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ ($branchId ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <input type="hidden" name="branch_id" value="{{ Auth::user()->branch_id }}">
                    @endif

                    <div class="mb-2">
                        <label class="form-label font-bold text-slate-700">Catatan Tambahan (Opsional)</label>
                        <textarea name="notes" class="form-control form-control-sm border-slate-300" rows="2" placeholder="Contoh: Laporan tutup buku bulanan Q3 telah diverifikasi manager..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-t border-slate-100 pt-3">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-odoo-primary py-1.5 px-3 text-xs font-bold d-inline-flex align-items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Generate PDF &amp; Arsipkan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const periodTypeSelect = document.getElementById('period_type');
        const dailySel = document.getElementById('daily_selector');
        const weeklySel = document.getElementById('weekly_selector');
        const monthSel = document.getElementById('month_selector');
        const yearSel = document.getElementById('year_selector');

        function updateSelectors() {
            const val = periodTypeSelect.value;
            dailySel.style.display = (val === 'daily') ? 'block' : 'none';
            weeklySel.style.display = (val === 'weekly') ? 'block' : 'none';
            monthSel.style.display = (val === 'monthly') ? 'block' : 'none';
            yearSel.style.display = (val === 'monthly') ? 'block' : 'none';
        }

        if (periodTypeSelect) {
            periodTypeSelect.addEventListener('change', updateSelectors);
        }

        // Tab persistence in URL hash
        const triggerTabList = [].slice.call(document.querySelectorAll('#reportTabs button'));
        triggerTabList.forEach(function (triggerEl) {
            triggerEl.addEventListener('click', function (event) {
                const targetId = triggerEl.getAttribute('data-bs-target');
                if (targetId) {
                    history.replaceState(null, null, targetId);
                }
            });
        });

        const activeHash = window.location.hash;
        if (activeHash) {
            const activeTabBtn = document.querySelector(`button[data-bs-target="${activeHash}"]`);
            if (activeTabBtn) {
                const tab = new bootstrap.Tab(activeTabBtn);
                tab.show();
            }
        }
    });
</script>
@endsection
