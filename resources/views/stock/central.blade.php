@extends('layouts.app')

@section('title', 'Lihat Seluruh Cabang - Master Produk Sentral')
@section('breadcrumb', 'Inventory / Lihat Seluruh Cabang')

@section('action-buttons')
<div class="d-flex flex-wrap align-items-center gap-2">
    <a href="{{ route('stock.index') }}" class="btn btn-sm btn-outline-secondary bg-white shadow-sm d-inline-flex align-items-center gap-1.5 text-nowrap fw-semibold">
        <i class="fa-solid fa-warehouse text-slate-600"></i>
        <span>Stock Cabang</span>
    </a>

    @if($isOwnerOrSuper)
    <a href="{{ route('materials.index') }}" class="btn-odoo-primary border-0 shadow-sm d-inline-flex align-items-center gap-1.5 text-decoration-none text-nowrap">
        <i class="fa-solid fa-boxes-stacked"></i>
        <span>Master Bahan</span>
    </a>
    @endif

    {{-- Filter Cabang Ringkas Inline --}}
    <form method="GET" action="{{ route('stock.central') }}" class="d-inline-flex align-items-center gap-1.5 m-0">
        @if($selectedCategory !== 'all')
            <input type="hidden" name="category" value="{{ $selectedCategory }}">
        @endif
        @if($selectedStatus !== 'all')
            <input type="hidden" name="stock_status" value="{{ $selectedStatus }}">
        @endif
        <select name="branch_id" onchange="this.form.submit()" class="form-select form-select-sm bg-white shadow-sm text-xs fw-semibold text-slate-700 text-nowrap" style="width: auto; border-radius: 6px; padding-top: 4px; padding-bottom: 4px;">
            <option value="all">Semua Cabang ({{ $branches->count() }})</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ (string) $selectedBranchId === (string) $b->id ? 'selected' : '' }}>
                    {{ $b->nama_cabang }}
                </option>
            @endforeach
        </select>
    </form>

    <a href="{{ route('stock.central', array_merge(request()->except(['stock_status', 'page']), ['stock_status' => $selectedStatus === 'low' ? 'all' : 'low'])) }}"
       class="btn btn-sm shadow-sm d-inline-flex align-items-center gap-1.5 text-nowrap fw-semibold {{ $selectedStatus === 'low' ? 'btn-warning text-dark border-warning' : 'btn-outline-secondary bg-white text-slate-700' }}"
       title="Filter produk yang memiliki cabang stok menipis (<= 5)">
        <i class="fa-solid fa-triangle-exclamation {{ $selectedStatus === 'low' ? 'text-dark' : 'text-amber-500' }}"></i>
        <span>Menipis (&le;5)</span>
        @if($summary['products_with_low_branch'] > 0)
            <span class="badge bg-amber-500 text-white rounded-pill text-[10px]">{{ $summary['products_with_low_branch'] }}</span>
        @endif
    </a>

    <a href="{{ route('stock.central', array_merge(request()->except(['stock_status', 'page']), ['stock_status' => $selectedStatus === 'empty' ? 'all' : 'empty'])) }}"
       class="btn btn-sm shadow-sm d-inline-flex align-items-center gap-1.5 text-nowrap fw-semibold {{ $selectedStatus === 'empty' ? 'btn-danger text-white border-danger' : 'btn-outline-secondary bg-white text-slate-700' }}"
       title="Filter produk yang memiliki cabang stok kosong (<= 0)">
        <i class="fa-solid fa-circle-xmark {{ $selectedStatus === 'empty' ? 'text-white' : 'text-rose-500' }}"></i>
        <span>Kosong (0)</span>
        @if($summary['products_with_empty_branch'] > 0)
            <span class="badge bg-rose-600 text-white rounded-pill text-[10px]">{{ $summary['products_with_empty_branch'] }}</span>
        @endif
    </a>
</div>
@endsection

@section('content')
@php
    $centralModalMap = [];
@endphp

<div x-data="{
    modalOpen: false,
    activeProduct: null,
    openStockModal(key) {
        const data = window.__centralProductsMap ? window.__centralProductsMap[key] : null;
        if (!data) return;
        this.activeProduct = data;
        this.modalOpen = true;
    },
    closeStockModal() {
        this.modalOpen = false;
    },
    formatRupiah(val) {
        const num = Math.round(Number(val) || 0);
        return 'Rp ' + num.toLocaleString('id-ID');
    }
}"
@keydown.escape.window="closeStockModal()"
id="main-view-wrapper" data-view-wrapper>

    <!-- 3 Main Stat Cards (Odoo Enterprise Stat Widgets) -->
    <div class="d-flex align-items-center gap-2 mb-3 overflow-x-auto pb-1">
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-sack-dollar text-teal-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-teal-800 text-nowrap">Rp {{ number_format($summary['total_asset_value'], 0, ',', '.') }}</div>
                <div class="o_stat_text text-nowrap">Overall Asset Value</div>
            </div>
        </div>

        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-store text-indigo-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-indigo-700 text-nowrap">{{ number_format($summary['total_branches']) }} Cabang</div>
                <div class="o_stat_text text-nowrap">Total Cabang</div>
            </div>
        </div>

        <a href="{{ route('stock.central') }}" class="o_stat_button bg-white shadow-sm text-decoration-none" title="Klik untuk tampilkan semua master produk">
            <i class="fa-solid fa-boxes-stacked text-emerald-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-slate-900 text-nowrap">
                    {{ number_format($summary['total_master_products']) }} Produk
                    <span class="text-xs font-normal text-slate-500">({{ number_format($summary['total_combined_stock'], 0, ',', '.') }} unit)</span>
                </div>
                <div class="o_stat_text text-nowrap">Master Produk Sentral</div>
            </div>
        </a>
    </div>

    <!-- Category Filter Tabs (Pill Buttons) -->
    <div class="d-flex align-items-center gap-1.5 mb-3 overflow-x-auto pb-1 no-scrollbar">
        <a href="{{ route('stock.central', array_merge(request()->except(['category', 'page']), ['category' => 'all'])) }}"
           class="btn btn-sm text-xs rounded-pill px-3 py-1 text-decoration-none d-flex align-items-center gap-1.5 fw-semibold transition text-nowrap {{ ($selectedCategory === 'all' || !$selectedCategory) ? 'btn-primary shadow-sm' : 'btn-outline-secondary bg-white text-slate-700' }}">
            <i class="fa-solid fa-layer-group text-xs"></i>
            <span>Semua Kategori</span>
            <span class="badge {{ ($selectedCategory === 'all' || !$selectedCategory) ? 'bg-white text-primary' : 'bg-slate-100 text-slate-600' }} rounded-pill text-[10px]">{{ $summary['total_master_products'] }}</span>
        </a>

        @php
            $catIcons = [
                'Print Dokumen dan Sticker' => ['icon' => 'fa-file-lines', 'badge' => 'bg-sky-50 text-sky-700 border-sky-200'],
                'Cetak Outdoor dan Indoor' => ['icon' => 'fa-panorama', 'badge' => 'bg-amber-50 text-amber-700 border-amber-200'],
                'Finishing' => ['icon' => 'fa-scissors', 'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
                'Merchandise Custom' => ['icon' => 'fa-gift', 'badge' => 'bg-rose-50 text-rose-700 border-rose-200'],
                'Stampel' => ['icon' => 'fa-stamp', 'badge' => 'bg-purple-50 text-purple-700 border-purple-200'],
                'Nota' => ['icon' => 'fa-receipt', 'badge' => 'bg-blue-50 text-blue-700 border-blue-200'],
                'Kartu Nama' => ['icon' => 'fa-id-card', 'badge' => 'bg-cyan-50 text-cyan-700 border-cyan-200'],
                'Buku Yasin' => ['icon' => 'fa-book-open', 'badge' => 'bg-violet-50 text-violet-700 border-violet-200'],
                'Brosur' => ['icon' => 'fa-newspaper', 'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                'Tumbler' => ['icon' => 'fa-mug-hot', 'badge' => 'bg-teal-50 text-teal-700 border-teal-200'],
            ];
        @endphp

        @foreach($categories as $cat)
            @php
                $isActive = ($selectedCategory === $cat);
                $info = $catIcons[$cat] ?? ['icon' => 'fa-tag', 'badge' => 'bg-slate-50 text-slate-700 border-slate-200'];
            @endphp
            <a href="{{ route('stock.central', array_merge(request()->except(['category', 'page']), ['category' => $cat])) }}"
               class="btn btn-sm text-xs rounded-pill px-3 py-1 text-decoration-none d-flex align-items-center gap-1.5 transition text-nowrap {{ $isActive ? 'btn-primary shadow-sm fw-bold' : 'btn-outline-secondary bg-white text-slate-700' }}">
                <i class="fa-solid {{ $info['icon'] }} text-xs"></i>
                <span>{{ $cat }}</span>
            </a>
        @endforeach
    </div>

    <!-- Main Odoo Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden">
        <div class="table-view-container">
            <div class="table-responsive">
                <table class="table table-hover o_list_table mb-0" id="main-table">
                    <thead>
                        <tr>
                            <th style="width: 52px;" class="ps-3 text-center sortable text-nowrap">No</th>
                            <th class="sortable text-nowrap">Nama Barang</th>
                            <th class="no-sort text-nowrap">Cabang</th>
                            <th class="sortable text-nowrap">Kategori</th>
                            <th class="sortable text-center text-nowrap">Total Stok</th>
                            <th class="sortable text-end pe-3 text-nowrap">Nilai Aset</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($masterProducts as $idx => $item)
                            @php
                                $rowNo = ($masterProducts->currentPage() - 1) * $masterProducts->perPage() + $idx + 1;
                                $badgeStyle = match($item['category']) {
                                    'Print Dokumen dan Sticker' => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'Cetak Outdoor dan Indoor' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'Finishing' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'Merchandise Custom' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    'Stampel' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'Nota' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'Kartu Nama' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                    'Buku Yasin' => 'bg-violet-50 text-violet-700 border-violet-200',
                                    'Brosur' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'Tumbler' => 'bg-teal-50 text-teal-700 border-teal-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200'
                                };
                                $isKm = stripos((string) $item['machine_tag'], 'KM') !== false || $item['has_click_charge'];
                                $totalFormatted = fmod((float) $item['total_stock'], 1.0) == 0.0
                                    ? number_format($item['total_stock'], 0, ',', '.')
                                    : number_format($item['total_stock'], 2, ',', '.');

                                $rowKey = 'prod_' . $rowNo;
                                $branchPopupRows = [];
                                foreach ($item['branch_stocks'] as $bs) {
                                    $qtyStr = fmod((float) $bs['stock_qty'], 1.0) == 0.0
                                        ? number_format($bs['stock_qty'], 0, ',', '.')
                                        : number_format($bs['stock_qty'], 2, ',', '.');
                                    $isOwnBranch = !$isOwnerOrSuper && ((int) auth()->user()->branch_id === (int) $bs['branch_id']);
                                    $canClickBranch = $isOwnerOrSuper || $isOwnBranch;
                                    $targetUrl = $isOwnerOrSuper
                                        ? route('stock.index', ['branch_id' => $bs['branch_id'], 'search' => $item['material_name']])
                                        : route('stock.index', ['search' => $item['material_name']]);

                                    $branchPopupRows[] = [
                                        'branch_id' => $bs['branch_id'],
                                        'branch_name' => $bs['branch_name'],
                                        'short_name' => $bs['short_name'],
                                        'stock_qty' => $bs['stock_qty'],
                                        'qty_formatted' => $qtyStr,
                                        'unit' => $bs['unit'],
                                        'status' => $bs['status'],
                                        'roll_count' => $bs['roll_count'],
                                        'roll_meters' => round((float) $bs['roll_meters'], 1),
                                        'asset_value' => max(0, (float) $bs['stock_qty']) * (float) $bs['purchase_price'],
                                        'retail_price' => (float) $bs['retail_price'],
                                        'purchase_price' => (float) $bs['purchase_price'],
                                        'is_own_branch' => $isOwnBranch,
                                        'can_click' => $canClickBranch,
                                        'target_url' => $targetUrl,
                                        'updated_at' => $bs['updated_at'] ? $bs['updated_at']->format('d M Y H:i') : '-',
                                    ];
                                }

                                $centralModalMap[$rowKey] = [
                                    'material_name' => $item['material_name'],
                                    'category' => $item['category'],
                                    'unit' => $item['unit'],
                                    'total_formatted' => $totalFormatted,
                                    'total_asset_value' => $item['total_asset_value'],
                                    'branch_count' => $item['branch_count'],
                                    'empty_branches' => $item['empty_branches'],
                                    'low_branches' => $item['low_branches'],
                                    'healthy_branches' => $item['healthy_branches'],
                                    'branches' => $branchPopupRows,
                                ];

                                $hasIssue = $item['empty_branches'] > 0 || $item['low_branches'] > 0;
                            @endphp
                            <tr class="search-row">
                                <td class="ps-3 text-center text-slate-500 font-mono text-xs fw-semibold text-nowrap">
                                    {{ $rowNo }}
                                </td>
                                <td class="text-nowrap">
                                    <div class="fw-bold text-slate-800">{{ $item['material_name'] }}</div>
                                    <div class="d-inline-flex align-items-center gap-1.5 flex-nowrap">
                                        <span class="text-slate-400 font-mono text-[10px]">{{ strtoupper($item['unit']) }} &bull; Jual Rp {{ number_format($item['max_retail_price'] ?: $item['retail_price'], 0, ',', '.') }}</span>
                                        @if($isKm || $item['machine_tag'])
                                            <span class="badge bg-amber-50 text-amber-800 border border-amber-200 text-nowrap" style="font-size: 10px;">
                                                <i class="fa-solid fa-print me-0.5"></i>{{ $item['machine_tag'] ?: 'KM' }}
                                            </span>
                                        @endif
                                        @if($item['is_linked_variant'])
                                            <span class="badge bg-purple-50 text-purple-700 border border-purple-200 text-nowrap" style="font-size: 10px;" title="Berbagi stok fisik dengan {{ $item['parent_name'] }}">
                                                <i class="fa-solid fa-link me-0.5"></i>Linked 2 Sisi
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-nowrap">
                                    <div class="d-inline-flex align-items-center gap-2 flex-nowrap">
                                        <button type="button"
                                                @click="openStockModal('{{ $rowKey }}')"
                                                class="btn btn-xs rounded-md fw-semibold text-[11px] px-2.5 py-1 border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 d-inline-flex align-items-center gap-1.5 text-nowrap shadow-2xs">
                                            <i class="fa-solid fa-eye"></i>
                                            <span>Lihat Stok ({{ $item['branch_count'] }} Cabang)</span>
                                        </button>

                                        @if($item['empty_branches'] > 0)
                                            <span class="badge bg-rose-50 text-rose-700 border border-rose-200 text-[10px] fw-semibold px-2 py-0.5 text-nowrap">
                                                {{ $item['empty_branches'] }} Kosong
                                            </span>
                                        @elseif($item['low_branches'] > 0)
                                            <span class="badge bg-amber-50 text-amber-800 border border-amber-300 text-[10px] fw-semibold px-2 py-0.5 text-nowrap">
                                                {{ $item['low_branches'] }} Menipis
                                            </span>
                                        @else
                                            <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] fw-semibold px-2 py-0.5 text-nowrap">
                                                Semua Aman
                                            </span>
                                        @endif
                                    </div>
                                    <span class="d-none">{{ implode(' ', array_column($item['branch_stocks'], 'branch_name')) }}</span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="badge {{ $badgeStyle }} border text-[11px] font-semibold px-2 py-0.5 rounded-md text-nowrap">
                                        {{ $item['category'] }}
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">
                                    @if($item['total_rolls'] > 0)
                                        <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold px-2.5 py-1 text-[11px] text-nowrap">
                                            <i class="fa-solid fa-scroll me-1"></i>{{ $item['total_rolls'] }} Roll ({{ number_format($item['total_roll_meters'], 1, ',', '.') }}m)
                                        </span>
                                    @elseif($item['total_stock'] <= 0)
                                        <span class="badge bg-rose-50 text-rose-700 border border-rose-200 font-bold px-2.5 py-1 text-[11px] text-nowrap">
                                            Out of Stock (0)
                                        </span>
                                    @elseif($hasIssue)
                                        <span class="badge bg-amber-50 text-amber-800 border border-amber-300 font-bold px-2.5 py-1 text-[11px] text-nowrap">
                                            {{ $totalFormatted }} {{ $item['unit'] }}
                                        </span>
                                    @else
                                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold px-2.5 py-1 text-[11px] text-nowrap">
                                            {{ $totalFormatted }} {{ $item['unit'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    <div class="font-mono fw-bold text-teal-700">
                                        Rp {{ number_format($item['total_asset_value'], 0, ',', '.') }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5 text-nowrap">
                                        Modal: Rp {{ number_format($item['purchase_price'], 0, ',', '.') }}/{{ $item['unit'] }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-slate-500">
                                    <i class="fa-solid fa-box-open fs-2 text-slate-300 mb-2 d-block"></i>
                                    Tidak ada data produk sentral untuk filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($masterProducts->hasPages())
        <div class="p-3 border-top bg-light">
            {{ $masterProducts->links() }}
        </div>
        @endif
    </div>

    <!-- Pop-up Modal Rincian Stok Per Cabang -->
    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background-color: rgba(15, 23, 42, 0.55); backdrop-filter: blur(2px);"
         @click.self="closeStockModal()">
        <div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden"
             @click.stop>
            {{-- Modal Header --}}
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="mb-0 fw-bold text-slate-900" x-text="activeProduct ? activeProduct.material_name : ''"></h5>
                        <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 text-[11px] fw-semibold text-nowrap"
                              x-text="activeProduct ? activeProduct.category : ''"></span>
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        Rincian stok terbaru di <strong x-text="activeProduct ? activeProduct.branch_count : 0"></strong> cabang
                    </div>
                </div>
                <button type="button" @click="closeStockModal()" class="btn btn-sm btn-light border rounded-circle" style="width: 30px; height: 30px; padding: 0;">
                    <i class="fa-solid fa-xmark text-slate-600"></i>
                </button>
            </div>

            {{-- Summary Strip --}}
            <div class="px-4 py-2.5 bg-white border-b border-slate-100 grid grid-cols-3 gap-2 text-xs">
                <div class="p-2 rounded-lg bg-slate-50 border border-slate-200">
                    <div class="text-[10px] text-slate-500 text-uppercase fw-semibold">Total Stok Gabungan</div>
                    <div class="fw-bold text-slate-900 fs-6 text-nowrap">
                        <span x-text="activeProduct ? activeProduct.total_formatted : '0'"></span>
                        <span class="text-xs fw-normal text-slate-500" x-text="activeProduct ? activeProduct.unit : ''"></span>
                    </div>
                </div>
                <div class="p-2 rounded-lg bg-teal-50/70 border border-teal-200">
                    <div class="text-[10px] text-teal-700 text-uppercase fw-semibold">Total Nilai Aset</div>
                    <div class="fw-bold text-teal-800 fs-6 font-mono text-nowrap"
                         x-text="activeProduct ? formatRupiah(activeProduct.total_asset_value) : 'Rp 0'"></div>
                </div>
                <div class="p-2 rounded-lg bg-indigo-50/70 border border-indigo-200">
                    <div class="text-[10px] text-indigo-700 text-uppercase fw-semibold">Status Cabang</div>
                    <div class="fw-bold text-indigo-900 fs-6 text-nowrap">
                        <span x-text="activeProduct ? activeProduct.healthy_branches : 0"></span> Aman
                        <template x-if="activeProduct && (activeProduct.low_branches + activeProduct.empty_branches) > 0">
                            <span class="text-xs text-rose-600 ms-1">
                                (<span x-text="activeProduct.low_branches + activeProduct.empty_branches"></span> Alert)
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Branch Breakdown List --}}
            <div class="max-h-96 overflow-y-auto">
                <table class="table table-hover mb-0 text-xs">
                    <thead class="bg-slate-50 text-slate-600 text-[11px] uppercase sticky-top">
                        <tr>
                            <th class="ps-4 py-2.5 text-nowrap">Cabang</th>
                            <th class="py-2.5 text-center text-nowrap">Status</th>
                            <th class="py-2.5 text-end text-nowrap">Jumlah Stok</th>
                            <th class="py-2.5 text-end text-nowrap">Nilai Aset</th>
                            <th class="py-2.5 text-end text-nowrap">Update</th>
                            <th class="pe-4 py-2.5 text-end text-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="b in (activeProduct ? activeProduct.branches : [])" :key="b.branch_id">
                            <tr>
                                <td class="ps-4 py-2.5 text-nowrap">
                                    <div class="fw-semibold text-slate-800 d-flex align-items-center gap-1.5">
                                        <span x-text="b.branch_name"></span>
                                        <template x-if="b.is_own_branch">
                                            <span class="badge bg-primary text-white text-[9px] px-1.5 py-0.5 rounded-pill">Cabang Anda</span>
                                        </template>
                                    </div>
                                    <template x-if="b.roll_count > 0">
                                        <div class="text-[10px] text-indigo-600 mt-0.5">
                                            <i class="fa-solid fa-scroll me-0.5"></i>
                                            <span x-text="b.roll_count"></span> Roll aktif (<span x-text="b.roll_meters"></span>m)
                                        </div>
                                    </template>
                                </td>
                                <td class="py-2.5 text-center text-nowrap">
                                    <span class="badge border text-[10px] fw-semibold px-2 py-0.5 rounded-pill"
                                          :class="{
                                              'bg-rose-50 text-rose-700 border-rose-200': b.status === 'empty',
                                              'bg-amber-50 text-amber-800 border-amber-300': b.status === 'low',
                                              'bg-emerald-50 text-emerald-700 border-emerald-200': b.status === 'healthy'
                                          }"
                                          x-text="b.status === 'empty' ? 'Kosong' : (b.status === 'low' ? 'Menipis' : 'Aman')"></span>
                                </td>
                                <td class="py-2.5 text-end font-mono fw-bold text-slate-900 text-nowrap">
                                    <span x-text="b.qty_formatted"></span>
                                    <span class="text-slate-500 fw-normal ms-0.5" x-text="b.unit"></span>
                                </td>
                                <td class="py-2.5 text-end font-mono text-teal-700 fw-semibold text-nowrap"
                                    x-text="formatRupiah(b.asset_value)"></td>
                                <td class="py-2.5 text-end text-slate-500 text-[11px] text-nowrap"
                                    x-text="b.updated_at"></td>
                                <td class="pe-4 py-2.5 text-end text-nowrap">
                                    <template x-if="b.can_click">
                                        <a :href="b.target_url" class="btn btn-xs btn-outline-primary text-[11px] px-2 py-0.5 text-nowrap text-decoration-none">
                                            Buka <i class="fa-solid fa-arrow-up-right-from-square ms-0.5 text-[9px]"></i>
                                        </a>
                                    </template>
                                    <template x-if="!b.can_click">
                                        <span class="text-slate-400 text-[11px]">Lihat</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="closeStockModal()" class="btn btn-sm btn-secondary px-3 text-xs fw-semibold">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.__centralProductsMap = @json($centralModalMap);
</script>
@endsection
