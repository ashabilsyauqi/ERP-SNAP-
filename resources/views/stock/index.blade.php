@extends('layouts.app')

@section('title', 'Stock & Physical Inventory')
@section('page-title', 'Stock Opname & Valuation')

@section('action-buttons')
@if(auth()->user()->isManager() || auth()->user()->isOwner() || auth()->user()->isSuperAdmin())
<button type="button" class="btn-odoo-primary border-0 shadow-sm d-inline-flex align-items-center gap-1.5 cursor-pointer" data-bs-toggle="modal" data-bs-target="#modalAddProductStock">
    <i class="fa-solid fa-plus"></i>
    <span>+ Add Product</span>
</button>
@endif
<a href="{{ route('stock.inspection') }}" class="btn-odoo-secondary text-decoration-none d-inline-flex align-items-center gap-1.5">
    <i class="fa-solid fa-truck-ramp-box"></i>
    <span>Incoming Receipts (GRN)</span>
    @if($pendingCount > 0)
        <span class="badge bg-rose-600 text-white rounded-pill px-1.5 py-0.5 text-[10px]">{{ $pendingCount }}</span>
    @endif
</a>
@endsection

@section('content')
<div x-data="{ 
    editOpen: false, 
    showAdvanced: false,
    editMaterial: { 
        id: '', 
        name: '', 
        branch: '',
        category: '', 
        system_stock: 0, 
        stock_qty: 0, 
        purchase_price: 0, 
        has_click_charge: false, 
        click_charge: 0, 
        retail_price: 0, 
        wholesale: [] 
    },
    get variance() {
        return (parseInt(this.editMaterial.stock_qty) || 0) - (parseInt(this.editMaterial.system_stock) || 0);
    },
    adjustQty(delta) {
        let current = parseInt(this.editMaterial.stock_qty) || 0;
        let next = current + delta;
        if (next < 0) next = 0;
        this.editMaterial.stock_qty = next;
    },
    addWholesaleTier() {
        if (!this.editMaterial.wholesale) this.editMaterial.wholesale = [];
        this.editMaterial.wholesale.push({ min_qty: '', price: '' });
    },
    removeWholesaleTier(idx) {
        this.editMaterial.wholesale.splice(idx, 1);
    }
}" id="main-view-wrapper" data-view-wrapper>

    <!-- Top Stat Buttons (Odoo Enterprise Stat Widgets) -->
    <div class="d-flex align-items-center gap-2 mb-3 overflow-x-auto pb-1">
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-boxes-stacked text-indigo-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-slate-900">{{ number_format($totalItems) }}</div>
                <div class="o_stat_text">Product Types</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-cubes text-emerald-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-emerald-700">{{ number_format($totalStockQty) }}</div>
                <div class="o_stat_text">Units on Hand</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-sack-dollar text-teal-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-teal-800">Rp {{ number_format($totalAssetValue, 0, ',', '.') }}</div>
                <div class="o_stat_text">Stock Valuation</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-triangle-exclamation text-amber-500 fs-5"></i>
            <div>
                <div class="o_stat_value text-amber-600">{{ number_format($lowStockCount) }}</div>
                <div class="o_stat_text">Low Stock Alert (&le;5)</div>
            </div>
        </div>
    </div>

    <!-- Category Filter Tabs (Pill Buttons) -->
    <div class="d-flex align-items-center gap-1.5 mb-3 overflow-x-auto pb-1 no-scrollbar">
        <a href="{{ route('stock.index', array_merge(request()->except(['category', 'page']), ['category' => 'all'])) }}" 
           class="btn btn-sm text-xs rounded-pill px-3 py-1 text-decoration-none d-flex align-items-center gap-1.5 fw-semibold transition {{ ($selectedCategory === 'all' || !$selectedCategory) ? 'btn-primary shadow-sm' : 'btn-outline-secondary bg-white text-slate-700' }}">
            <i class="fa-solid fa-layer-group text-xs"></i>
            <span>Semua Kategori</span>
            <span class="badge {{ ($selectedCategory === 'all' || !$selectedCategory) ? 'bg-white text-primary' : 'bg-slate-100 text-slate-600' }} rounded-pill text-[10px]">{{ $totalItems }}</span>
        </a>

        @php
            $catIcons = [
                'Print Dokumen dan Sticker' => ['icon' => 'fa-file-lines', 'badge' => 'bg-sky-50 text-sky-700 border-sky-200'],
                'Cetak Outdoor dan Indoor' => ['icon' => 'fa-panorama', 'badge' => 'bg-amber-50 text-amber-700 border-amber-200'],
                'Finishing' => ['icon' => 'fa-scissors', 'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
                'Merchandise Custom' => ['icon' => 'fa-gift', 'badge' => 'bg-rose-50 text-rose-700 border-rose-200'],
                'Stampel' => ['icon' => 'fa-stamp', 'badge' => 'bg-purple-50 text-purple-700 border-purple-200'],
                'Nota' => ['icon' => 'fa-receipt', 'badge' => 'bg-blue-50 text-blue-700 border-blue-200'],
                'Brosur' => ['icon' => 'fa-newspaper', 'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                'Tumbler' => ['icon' => 'fa-mug-hot', 'badge' => 'bg-teal-50 text-teal-700 border-teal-200'],
            ];
        @endphp

        @foreach($categories as $cat)
            @php
                $isActive = ($selectedCategory === $cat);
                $info = $catIcons[$cat] ?? ['icon' => 'fa-tag', 'badge' => 'bg-slate-50 text-slate-700 border-slate-200'];
            @endphp
            <a href="{{ route('stock.index', array_merge(request()->except(['category', 'page']), ['category' => $cat])) }}" 
               class="btn btn-sm text-xs rounded-pill px-3 py-1 text-decoration-none d-flex align-items-center gap-1.5 transition {{ $isActive ? 'btn-primary shadow-sm fw-bold' : 'btn-outline-secondary bg-white text-slate-700' }}">
                <i class="fa-solid {{ $info['icon'] }} text-xs"></i>
                <span>{{ $cat }}</span>
            </a>
        @endforeach
    </div>

    <!-- Main Odoo Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden">
        <!-- View Mode 1: Table List View (Odoo Tree View) -->
        <div class="table-view-container">
            <div class="table-responsive">
                <table class="table table-hover o_list_table mb-0" id="main-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="ps-3 text-center no-sort">
                                <input type="checkbox" class="form-check-input">
                            </th>
                            <th class="sortable">Product (Nama Bahan)</th>
                            <th class="sortable">Kategori</th>
                            <th class="sortable">Vendor</th>
                            <th class="sortable">Specification / Unit</th>
                            <th class="sortable text-end">Cost Price</th>
                            <th class="sortable text-end">Sales Price & Wholesale</th>
                            <th class="sortable text-center">On Hand Qty</th>
                            <th class="text-center no-sort" style="width: 120px;">Stock Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($materials as $material)
                            @php
                                $badgeStyle = match($material->category) {
                                    'Print Dokumen dan Sticker' => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'Cetak Outdoor dan Indoor' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'Finishing' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'Merchandise Custom' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    'Stampel' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'Nota' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'Brosur' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'Tumbler' => 'bg-teal-50 text-teal-700 border-teal-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200'
                                };
                            @endphp
                            <tr class="search-row">
                                <td class="ps-3 text-center">
                                    <input type="checkbox" class="form-check-input">
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800">{{ $material->material_name }}</div>
                                    <span class="text-slate-400 font-mono text-[10px]">#MAT-{{ $material->id }} &bull; {{ $material->branch->nama_cabang ?? 'Pusat' }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $badgeStyle }} border text-[11px] font-semibold px-2 py-0.5 rounded-md">
                                        {{ $material->category ?? 'Lainnya' }}
                                    </span>
                                </td>
                                <td>
                                    @if($material->supplier)
                                        <span class="badge bg-slate-100 text-slate-700 border text-[11px] font-normal">
                                            {{ $material->supplier->name }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($material->fixed_size)
                                        <span class="badge bg-sky-50 text-sky-700 border border-sky-200 text-[11px] font-normal">
                                            {{ $material->fixed_size }} Meter
                                        </span>
                                    @else
                                        <span class="text-slate-500 text-[11px]">Standard / Pcs</span>
                                    @endif
                                </td>
                                <td class="text-end font-mono text-slate-700">
                                    <div>Rp {{ number_format($material->purchase_price, 0, ',', '.') }}</div>
                                    @if($material->has_click_charge && $material->click_charge > 0)
                                        <div class="text-[10px] text-indigo-600 font-sans mt-0.5" title="Biaya Mesin per Lembar">
                                            <i class="fa-solid fa-print me-0.5"></i>+Klik Rp {{ number_format($material->click_charge, 0, ',', '.') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="font-mono fw-bold text-teal-700">
                                        Rp {{ number_format($material->retail_price, 0, ',', '.') }}
                                    </div>
                                    @if($material->wholesalePrices->count() > 0)
                                        <div class="text-[10px] text-indigo-600 font-semibold mt-0.5">
                                            {{ $material->wholesalePrices->count() }} Tier Grosir Aktif
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($material->stock_qty <= 0)
                                        <span class="badge bg-rose-50 text-rose-700 border border-rose-200 font-bold px-2 py-1 text-[11px]">
                                            Out of Stock (0)
                                        </span>
                                    @elseif($material->stock_qty <= 5)
                                        <span class="badge bg-amber-50 text-amber-700 border border-amber-200 font-bold px-2 py-1 text-[11px]">
                                            Low: {{ $material->stock_qty }} Units
                                        </span>
                                    @else
                                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold px-2 py-1 text-[11px]">
                                            {{ number_format($material->stock_qty) }} Units
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button @click="
                                        showAdvanced = false;
                                        editMaterial = {
                                            id: '{{ $material->id }}',
                                            name: '{{ addslashes($material->material_name) }}',
                                            branch: '{{ addslashes($material->branch->nama_cabang ?? 'Pusat') }}',
                                            category: '{{ addslashes($material->category ?? '') }}',
                                            system_stock: {{ (int)$material->stock_qty }},
                                            stock_qty: {{ (int)$material->stock_qty }},
                                            purchase_price: {{ (float)$material->purchase_price }},
                                            has_click_charge: {{ $material->has_click_charge ? 'true' : 'false' }},
                                            click_charge: {{ (float)($material->click_charge ?? 0) }},
                                            retail_price: {{ (float)$material->retail_price }},
                                            wholesale: {{ json_encode($material->wholesalePrices->map(fn($w) => ['min_qty' => $w->min_qty, 'price' => $w->wholesale_price])) }}
                                        };
                                        if (!editMaterial.wholesale) editMaterial.wholesale = [];
                                        editOpen = true;
                                    " class="btn btn-sm btn-odoo-secondary py-0.5 px-2 text-xs">
                                        <i class="fa-solid fa-sliders me-1"></i> Opname
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="p-4">
                                        <i class="fa-solid fa-boxes-stacked fs-1 text-slate-300 mb-2"></i>
                                        <p class="mb-0">Belum ada data stok bahan baku untuk filter ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- View Mode 2: Kanban Cards -->
        <div class="grid-view-container d-none p-4 bg-slate-50 border-top">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @forelse($materials as $material)
                    <div class="o_kanban_record bg-white border rounded p-3 shadow-sm hover:shadow transition search-card" style="border-left: 4px solid {{ $material->stock_qty <= 5 ? '#e11d48' : '#008784' }} !important;">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="badge bg-slate-100 text-slate-700 border text-[10px]">{{ $material->category ?? 'Bahan' }}</span>
                            <span class="badge {{ $material->stock_qty <= 5 ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }} text-[10px]">
                                {{ $material->stock_qty }} Units
                            </span>
                        </div>
                        <h6 class="fw-bold text-slate-900 mb-1 text-xs">{{ $material->material_name }}</h6>
                        <div class="text-[11px] text-slate-500 mb-2">
                            <span>Harga: <strong class="text-teal-700">Rp {{ number_format($material->retail_price, 0, ',', '.') }}</strong></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="text-slate-400 font-mono text-[10px]">#MAT-{{ $material->id }}</span>
                            <button @click="
                                showAdvanced = false;
                                editMaterial = {
                                    id: '{{ $material->id }}',
                                    name: '{{ addslashes($material->material_name) }}',
                                    branch: '{{ addslashes($material->branch->nama_cabang ?? 'Pusat') }}',
                                    category: '{{ addslashes($material->category ?? '') }}',
                                    system_stock: {{ (int)$material->stock_qty }},
                                    stock_qty: {{ (int)$material->stock_qty }},
                                    purchase_price: {{ (float)$material->purchase_price }},
                                    has_click_charge: {{ $material->has_click_charge ? 'true' : 'false' }},
                                    click_charge: {{ (float)($material->click_charge ?? 0) }},
                                    retail_price: {{ (float)$material->retail_price }},
                                    wholesale: {{ json_encode($material->wholesalePrices->map(fn($w) => ['min_qty' => $w->min_qty, 'price' => $w->wholesale_price])) }}
                                };
                                if (!editMaterial.wholesale) editMaterial.wholesale = [];
                                editOpen = true;
                            " class="btn btn-xs btn-odoo-secondary py-0.5 px-2 text-[10px]">
                                <i class="fa-solid fa-sliders me-1"></i> Opname
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">Belum ada data inventaris.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Modal Add Product Directly from Stock (Manager / Owner) -->
    <div class="modal fade" id="modalAddProductStock" tabindex="-1" aria-labelledby="modalAddProductStockLabel" aria-hidden="true" style="z-index: 999999 !important;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-3 shadow-2xl border-0 overflow-hidden">
                <form action="{{ route('stock.store') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-slate-900 text-white py-3 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="modal-title fs-6 fw-bold text-white mb-0 d-flex align-items-center gap-2" id="modalAddProductStockLabel">
                            <i class="fa-solid fa-box-open text-teal-400"></i> + Add Product (Master Bahan Baku)
                        </h5>
                        <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4 bg-white">
                        <div class="row g-3">
                            <!-- 1. Nama Bahan / Produk -->
                            <div class="col-12">
                                <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                    <i class="fa-solid fa-box text-primary me-1"></i> Nama Bahan / Produk Cetak <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="material_name" class="form-control form-control-sm font-bold text-slate-900" placeholder="Contoh: Spanduk Flexy Korea 440gsm Glossy" required>
                            </div>

                            <!-- 2. Kategori Produk -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                    <i class="fa-solid fa-layer-group text-primary me-1"></i> Kategori Produk <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="category" list="new-category-options-stock" class="form-control form-control-sm font-semibold" placeholder="Pilih atau ketik kategori..." required>
                                <datalist id="new-category-options-stock">
                                    <option value="Print Dokumen dan Sticker"></option>
                                    <option value="Cetak Outdoor dan Indoor"></option>
                                    <option value="Finishing"></option>
                                    <option value="Merchandise Custom"></option>
                                    <option value="Stampel"></option>
                                    <option value="Nota"></option>
                                    <option value="Brosur"></option>
                                    <option value="Tumbler"></option>
                                    <option value="Lainnya"></option>
                                </datalist>
                            </div>

                            <!-- 3. Cabang Penempatan -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                    <i class="fa-solid fa-store text-primary me-1"></i> Cabang Penempatan
                                </label>
                                @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin())
                                    <select name="branch_id" class="form-select form-select-sm font-semibold">
                                        @foreach($branches as $b)
                                            <option value="{{ $b->id }}" {{ (auth()->user()->branch_id == $b->id || (request('branch_id') == $b->id)) ? 'selected' : '' }}>
                                                {{ $b->nama_cabang }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                                    <input type="text" class="form-control form-control-sm bg-light font-semibold" value="{{ auth()->user()->branch->nama_cabang ?? 'Cabang Anda' }}" readonly>
                                @endif
                            </div>

                            <!-- 4. Vendor / Supplier -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                    <i class="fa-solid fa-truck text-secondary me-1"></i> Vendor / Supplier Utama
                                </label>
                                <select name="supplier_id" class="form-select form-select-sm">
                                    <option value="">-- Tanpa Vendor Khusus (Opsional) --</option>
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 5. Satuan & Ukuran -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                    <i class="fa-solid fa-ruler-combined text-secondary me-1"></i> Satuan
                                </label>
                                <select name="unit" class="form-select form-select-sm">
                                    <option value="Pcs">Pcs / Lembar</option>
                                    <option value="Meter">Meter (m²)</option>
                                    <option value="Roll">Roll</option>
                                    <option value="Rim">Rim</option>
                                    <option value="Pack">Pack</option>
                                    <option value="Set">Set</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                    <i class="fa-solid fa-arrows-left-right text-secondary me-1"></i> Panjang (Meter)
                                </label>
                                <input type="number" step="0.01" name="fixed_size" class="form-control form-control-sm" placeholder="Misal: 50 (opsional)">
                            </div>

                            <!-- 6. Stok Awal -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                    <i class="fa-solid fa-cubes text-primary me-1"></i> Stok Awal (On Hand) <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="stock_qty" value="10" class="form-control form-control-sm fw-bold text-blue-900" required min="0">
                            </div>

                            <!-- 7. Cost Price / Modal HPP -->
                            <div class="col-md-4">
                                <div class="p-2.5 rounded-3 bg-amber-50 border border-amber-300">
                                    <label class="form-label fw-bold text-amber-900 text-xs text-uppercase mb-1 d-block">
                                        <i class="fa-solid fa-sack-dollar text-amber-600 me-1"></i> Modal HPP (Cost Price) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white fw-bold text-amber-800">Rp</span>
                                        <input type="number" name="purchase_price" class="form-control form-control-sm fw-bold font-monospace" placeholder="18000" required min="0">
                                    </div>
                                    <span class="text-[10px] text-amber-700 mt-1 d-block">Harga beli/modal per unit</span>
                                </div>
                            </div>

                            <!-- 8. Sales Price / Harga Jual Normal Eceran -->
                            <div class="col-md-4">
                                <div class="p-2.5 rounded-3 bg-emerald-50 border border-emerald-300">
                                    <label class="form-label fw-bold text-emerald-900 text-xs text-uppercase mb-1 d-block">
                                        <i class="fa-solid fa-tag text-emerald-600 me-1"></i> Harga Jual (Sales Price) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white fw-bold text-emerald-700">Rp</span>
                                        <input type="number" name="retail_price" class="form-control form-control-sm fw-bold text-emerald-800 font-monospace" placeholder="35000" required min="0">
                                    </div>
                                    <span class="text-[10px] text-emerald-700 mt-1 d-block">Harga normal eceran kasir</span>
                                </div>
                            </div>

                            <!-- Click Charge Mesin (Opsional) -->
                            <div class="col-12">
                                <div class="p-3 rounded-3 bg-slate-50 border border-slate-200">
                                    <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                                        <input class="form-check-input cursor-pointer" type="checkbox" role="switch" id="toggleClickChargeStockAdd" name="has_click_charge" value="1" onchange="document.getElementById('clickChargeStockAddWrapper').style.display = this.checked ? 'block' : 'none'">
                                        <label class="form-check-label fw-bold text-slate-800 text-xs text-uppercase cursor-pointer mb-0" for="toggleClickChargeStockAdd">
                                            <i class="fa-solid fa-print text-indigo-600 me-1"></i> Biaya Klik Mesin Digital (Click Charge) [Opsional]
                                        </label>
                                    </div>
                                    <div id="clickChargeStockAddWrapper" style="display: none;" class="mt-2 pt-2 border-top border-slate-200">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-6">
                                                <label class="form-label text-slate-600 text-xs fw-semibold mb-1">Tarif Klik Mesin per Lembar / Unit (Rp):</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white fw-bold text-indigo-700">Rp</span>
                                                    <input type="number" name="click_charge" class="form-control form-control-sm fw-bold font-monospace" placeholder="1000" min="0" value="0">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="text-[11px] text-slate-500 mb-0 mt-3">
                                                    <i class="fa-solid fa-info-circle text-indigo-500 me-1"></i> Biaya klik mesin sewa per lembar (cth: Fuji Xerox/Konica). Otomatis ditambahkan ke HPP saat kasir checkout.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 9. Tiering Harga Grosir (Wholesale Price) -->
                            <div class="col-12 mt-2">
                                <div class="card border border-indigo-200 bg-indigo-50/40 p-3 rounded-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h6 class="fw-bold text-indigo-900 mb-0 d-flex align-items-center gap-1.5 fs-7">
                                                <i class="fa-solid fa-tags text-indigo-600"></i> Tiering Harga Grosir (Wholesale Prices)
                                            </h6>
                                            <span class="text-slate-500 text-[11px]">Diskon harga khusus untuk pembelian kuantitas banyak</span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-primary py-1 px-2.5 text-xs font-semibold" onclick="addWholesaleRowStock('wholesale-stock-container')">
                                            <i class="fa-solid fa-plus me-1"></i> Tambah Tier Grosir
                                        </button>
                                    </div>

                                    <div id="wholesale-stock-container" class="d-flex flex-column gap-2 mt-2">
                                        <!-- Dynamic rows -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-slate-50 border-top py-2.5 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn-odoo-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-odoo-primary">
                            <i class="fa-solid fa-plus me-1"></i> Simpan & Daftarkan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Stock Opname Modal (Seamless, Simple & Focused) -->
    <div x-show="editOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" style="display: none; position: fixed; inset: 0; z-index: 999999 !important;" x-cloak>
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden animate-fade-in" @click.away="editOpen = false">
            <form :action="'/stock/materials/' + editMaterial.id" method="POST">
                @csrf
                @method('PUT')
                
                <!-- Modal Header -->
                <div class="bg-slate-900 text-white px-4 py-3.5 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-teal-500/20 text-teal-300 border border-teal-500/30 text-[10px] px-2 py-0.5 uppercase tracking-wide">Stock Opname</span>
                            <span class="text-slate-400 text-xs font-mono" x-text="'#MAT-' + editMaterial.id"></span>
                            <span class="text-slate-400 text-xs" x-text="'&bull; ' + (editMaterial.branch || 'Pusat')"></span>
                        </div>
                        <h5 class="fs-6 fw-bold text-white mb-0 mt-1 line-clamp-1" x-text="editMaterial.name"></h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white text-xs" @click="editOpen = false"></button>
                </div>

                <div class="p-4 space-y-3 text-xs bg-white">
                    <!-- Core Opname Card: System vs Physical vs Variance -->
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                        <div class="row g-3 align-items-center">
                            <!-- Stok Tercatat Sistem -->
                            <div class="col-5">
                                <label class="text-slate-500 text-[11px] fw-bold text-uppercase d-block mb-1">
                                    <i class="fa-solid fa-database text-slate-400 me-1"></i> Stok Sistem
                                </label>
                                <div class="bg-white border rounded-lg px-3 py-2 text-center">
                                    <div class="fs-4 fw-black text-slate-700 font-mono" x-text="editMaterial.system_stock"></div>
                                    <div class="text-[10px] text-slate-400 text-uppercase fw-semibold">Tercatat</div>
                                </div>
                            </div>

                            <!-- Panah Transformasi -->
                            <div class="col-2 text-center">
                                <div class="w-8 h-8 mx-auto rounded-full bg-slate-200 d-flex align-items-center justify-center text-slate-500">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </div>
                            </div>

                            <!-- Input Fisik Aktual -->
                            <div class="col-5">
                                <label class="text-slate-800 text-[11px] fw-bold text-uppercase d-block mb-1">
                                    <i class="fa-solid fa-clipboard-check text-teal-600 me-1"></i> Fisik Aktual <span class="text-danger">*</span>
                                </label>
                                <div class="bg-white border-2 border-teal-500 rounded-lg p-1 text-center shadow-sm">
                                    <input type="number" name="stock_qty" x-model="editMaterial.stock_qty" 
                                        class="form-control form-control-lg border-0 text-center fw-black text-teal-800 font-mono p-0 fs-3 focus-none shadow-none" 
                                        required min="0">
                                    <div class="text-[10px] text-teal-600 text-uppercase fw-semibold">Hitungan Fisik</div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Adjust Buttons -->
                        <div class="d-flex justify-content-center gap-1.5 mt-3 pt-2 border-top border-slate-200/80">
                            <button type="button" @click="adjustQty(-10)" class="btn btn-sm btn-outline-secondary px-2.5 py-0.5 text-xs fw-bold rounded-lg">-10</button>
                            <button type="button" @click="adjustQty(-1)" class="btn btn-sm btn-outline-secondary px-2.5 py-0.5 text-xs fw-bold rounded-lg">-1</button>
                            <button type="button" @click="editMaterial.stock_qty = editMaterial.system_stock" class="btn btn-sm btn-light border px-2.5 py-0.5 text-[11px] text-slate-600 rounded-lg" title="Samakan dengan stok sistem">Reset Sama</button>
                            <button type="button" @click="adjustQty(1)" class="btn btn-sm btn-outline-secondary px-2.5 py-0.5 text-xs fw-bold rounded-lg">+1</button>
                            <button type="button" @click="adjustQty(10)" class="btn btn-sm btn-outline-secondary px-2.5 py-0.5 text-xs fw-bold rounded-lg">+10</button>
                        </div>
                    </div>

                    <!-- Live Selisih / Variance Alert Card -->
                    <div>
                        <template x-if="variance === 0">
                            <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-circle-check text-emerald-600 fs-5"></i>
                                    <div>
                                        <div class="fw-bold text-xs">Stok Sesuai (Match)</div>
                                        <div class="text-[11px] text-emerald-700">Tidak ada selisih antara sistem & fisik gudang.</div>
                                    </div>
                                </div>
                                <span class="badge bg-emerald-600 text-white px-2.5 py-1 text-xs font-mono font-bold">Selisih: 0</span>
                            </div>
                        </template>

                        <template x-if="variance < 0">
                            <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-circle-xmark text-rose-600 fs-5"></i>
                                    <div>
                                        <div class="fw-bold text-xs">Stok Kurang / Hilang (Defisit)</div>
                                        <div class="text-[11px] text-rose-700">Fisik lebih sedikit <strong x-text="Math.abs(variance) + ' unit'"></strong> dari sistem.</div>
                                    </div>
                                </div>
                                <span class="badge bg-rose-600 text-white px-2.5 py-1 text-xs font-mono font-bold" x-text="variance + ' Unit'"></span>
                            </div>
                        </template>

                        <template x-if="variance > 0">
                            <div class="p-2.5 rounded-xl bg-sky-50 border border-sky-200 text-sky-800 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-circle-plus text-sky-600 fs-5"></i>
                                    <div>
                                        <div class="fw-bold text-xs">Stok Lebih (Surplus)</div>
                                        <div class="text-[11px] text-sky-700">Fisik lebih banyak <strong x-text="'+' + variance + ' unit'"></strong> dari sistem.</div>
                                    </div>
                                </div>
                                <span class="badge bg-sky-600 text-white px-2.5 py-1 text-xs font-mono font-bold" x-text="'+' + variance + ' Unit'"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Keterangan / Alasan Penyesuaian (Opsional) -->
                    <div>
                        <label class="form-label text-slate-700 fw-semibold text-xs mb-1">
                            <i class="fa-solid fa-note-sticky text-slate-400 me-1"></i> Catatan Opname / Alasan Selisih (Opsional)
                        </label>
                        <input type="text" name="opname_notes" class="form-control form-control-sm" placeholder="Contoh: 2 lembar rusak tertekuk saat cetak / salah hitung">
                    </div>

                    <!-- Collapsible: Penyetelan Harga & Tier Grosir -->
                    <div class="border rounded-xl p-3 bg-slate-50/60">
                        <button type="button" @click="showAdvanced = !showAdvanced" class="w-full d-flex justify-content-between align-items-center text-slate-700 hover:text-slate-900 border-0 bg-transparent p-0 text-left cursor-pointer">
                            <span class="fw-bold text-xs d-flex align-items-center gap-2">
                                <i class="fa-solid fa-tags text-teal-600"></i>
                                <span>Penyetelan Harga, Mesin & Grosir (Opsional)</span>
                            </span>
                            <div class="d-flex align-items-center gap-1 text-slate-500 text-[11px]">
                                <span x-text="showAdvanced ? 'Tutup' : 'Buka Pengaturan'"></span>
                                <i class="fa-solid" :class="showAdvanced ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </div>
                        </button>

                        <div x-show="showAdvanced" x-transition class="mt-3 pt-3 border-top border-slate-200 space-y-3" style="display: none;">
                            <!-- Category -->
                            <div>
                                <label class="form-label font-semibold text-slate-700 text-[11px] uppercase mb-1">Kategori Produk</label>
                                <input type="text" name="category" x-model="editMaterial.category" list="category-options-opname" class="form-control form-control-sm font-semibold">
                                <datalist id="category-options-opname">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}"></option>
                                    @endforeach
                                </datalist>
                            </div>

                            <div class="row g-2">
                                <!-- Modal HPP Bahan -->
                                <div class="col-6">
                                    <label class="form-label font-semibold text-slate-700 text-[11px] uppercase mb-1">Modal Bahan (HPP)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white text-slate-500">Rp</span>
                                        <input type="number" name="purchase_price" x-model="editMaterial.purchase_price" class="form-control form-control-sm font-mono" min="0">
                                    </div>
                                </div>

                                <!-- Sales Price -->
                                <div class="col-6">
                                    <label class="form-label font-semibold text-teal-800 text-[11px] uppercase mb-1">Harga Jual Eceran</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white text-teal-600">Rp</span>
                                        <input type="number" name="retail_price" x-model="editMaterial.retail_price" class="form-control form-control-sm font-mono font-bold text-teal-800" min="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Click Charge Toggle & Input -->
                            <div class="p-2.5 rounded-lg bg-white border border-slate-200">
                                <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                                    <input class="form-check-input cursor-pointer" type="checkbox" role="switch" id="toggleClickChargeOpname" name="has_click_charge" value="1" x-model="editMaterial.has_click_charge">
                                    <label class="form-check-label fw-bold text-slate-800 text-[11px] text-uppercase cursor-pointer mb-0" for="toggleClickChargeOpname">
                                        <i class="fa-solid fa-print text-indigo-600 me-1"></i> Biaya Klik Mesin (Click Charge)
                                    </label>
                                </div>
                                <div x-show="editMaterial.has_click_charge" class="mt-2 pt-2 border-top border-slate-100">
                                    <label class="form-label text-slate-600 text-[11px] fw-semibold mb-1">Tarif Klik per Lembar (Rp):</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light text-indigo-700 fw-bold">Rp</span>
                                        <input type="number" name="click_charge" x-model="editMaterial.click_charge" class="form-control form-control-sm font-mono font-bold" placeholder="1000" min="0">
                                    </div>
                                    <small class="text-[10px] text-slate-500 mt-1 d-block">
                                        Total HPP kasir: Rp <span x-text="(parseFloat(editMaterial.purchase_price || 0) + parseFloat(editMaterial.click_charge || 0)).toLocaleString('id-ID')"></span>
                                    </small>
                                </div>
                            </div>

                            <!-- Wholesale Pricing Tiers -->
                            <div class="border-t border-slate-200 pt-2 space-y-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label font-semibold text-slate-700 text-[11px] uppercase mb-0">
                                        <i class="fa-solid fa-tags text-indigo-600 me-1"></i> Tiering Harga Grosir
                                    </label>
                                    <button type="button" @click="addWholesaleTier()" class="btn btn-sm btn-outline-primary py-0.5 px-2 text-[10px] font-semibold">
                                        <i class="fa-solid fa-plus me-1"></i> Tambah Tier
                                    </button>
                                </div>

                                <template x-if="!editMaterial.wholesale || editMaterial.wholesale.length === 0">
                                    <div class="text-[11px] text-slate-400 italic bg-white p-2 rounded text-center border">
                                        Belum ada tier harga grosir untuk produk ini.
                                    </div>
                                </template>

                                <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                                    <template x-for="(tier, idx) in editMaterial.wholesale" :key="idx">
                                        <div class="d-flex align-items-center gap-2 bg-white p-1.5 rounded-lg border">
                                            <div class="flex-fill">
                                                <label class="form-label text-slate-500 text-[9px] fw-bold mb-0">Min Qty:</label>
                                                <input type="number" min="1" :name="'wholesale[' + idx + '][min_qty]'" x-model="tier.min_qty" placeholder="10" 
                                                    class="form-control form-control-sm text-xs py-0.5" required>
                                            </div>
                                            <div class="flex-fill">
                                                <label class="form-label text-slate-500 text-[9px] fw-bold mb-0">Harga Satuan (Rp):</label>
                                                <input type="number" min="0" :name="'wholesale[' + idx + '][price]'" x-model="tier.price" placeholder="45000" 
                                                    class="form-control form-control-sm font-mono text-xs py-0.5" required>
                                            </div>
                                            <div class="pt-2">
                                                <button type="button" @click="removeWholesaleTier(idx)" class="btn btn-sm text-rose-500 hover:text-rose-700 p-0 border-0 bg-transparent" title="Hapus Tier">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-slate-50 border-top px-4 py-3 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn-odoo-secondary" @click="editOpen = false">Batal</button>
                    <button type="submit" class="btn-odoo-primary px-4">
                        <i class="fa-solid fa-floppy-disk me-1.5"></i> Simpan Hasil Opname
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    function addWholesaleRowStock(containerId, minQty = '', price = '') {
        const container = document.getElementById(containerId);
        if (!container) return;
        const index = container.children.length;
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2 p-2 bg-white rounded border';
        row.innerHTML = `
            <div class="flex-fill">
                <label class="form-label text-slate-500 text-[10px] fw-bold mb-0.5 d-block">Minimal Beli (Qty/Pcs):</label>
                <input type="number" min="1" name="wholesale[${index}][min_qty]" value="${minQty}" class="form-control form-control-sm" placeholder="Contoh: 10" required>
            </div>
            <div class="flex-fill">
                <label class="form-label text-slate-500 text-[10px] fw-bold mb-0.5 d-block">Harga Grosir Satuan (Rp):</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light">Rp</span>
                    <input type="number" min="0" name="wholesale[${index}][price]" value="${price}" class="form-control form-control-sm font-monospace font-bold" placeholder="Contoh: 180000" required>
                </div>
            </div>
            <div class="pt-3">
                <button type="button" class="btn btn-sm btn-outline-danger border-0 py-1 px-2" onclick="this.closest('.d-flex').remove()" title="Hapus Tier">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
    }
</script>
@endsection
