@extends('layouts.app')

@section('title', 'Products & Materials')
@section('page-title', 'Master Bahan Baku & Produk')

@section('action-buttons')
<button type="button" class="btn-odoo-primary" data-bs-toggle="modal" data-bs-target="#modalAddMaterial">
    <i class="fa-solid fa-plus"></i>
    <span>New</span>
</button>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <div class="o_form_sheet p-0 overflow-hidden">
        
        <!-- View Mode 1: Table List View (Odoo Tree View) -->
        <div class="table-view-container">
            <div class="table-responsive">
                <table class="table table-hover o_list_table mb-0" id="main-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="ps-3 text-center no-sort">
                                <input type="checkbox" class="form-check-input" id="checkAllMaterials">
                            </th>
                            <th class="sortable">Product Name (Nama Bahan)</th>
                            <th class="sortable">Vendor / Supplier</th>
                            <th class="sortable">Unit / Size</th>
                            <th class="sortable text-end">Cost Price (HPP)</th>
                            <th class="sortable text-end">Sales Price (Eceran)</th>
                            <th class="sortable">Wholesale Rules</th>
                            <th class="sortable text-center">On Hand (Stok)</th>
                            <th class="text-center no-sort" style="width: 90px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($materials as $mat)
                            <tr class="search-row">
                                <td class="ps-3 text-center">
                                    <input type="checkbox" class="form-check-input">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded bg-light border p-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                            <i class="fa-solid fa-box text-slate-400 text-xs"></i>
                                        </div>
                                        <div>
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#modalEditMaterial{{ $mat->id }}" class="fw-bold text-slate-800 text-decoration-none hover:text-teal-700">
                                                {{ $mat->material_name }}
                                            </a>
                                            <div class="text-slate-400 text-[11px] font-mono">Ref: #MAT-{{ $mat->id }} &bull; {{ $mat->branch->nama_cabang ?? 'Pusat' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($mat->supplier)
                                        <span class="badge bg-slate-100 text-slate-700 border text-[11px] font-normal">
                                            <i class="fa-solid fa-building me-1 opacity-60"></i> {{ $mat->supplier->name }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($mat->fixed_size)
                                        <span class="badge bg-sky-50 text-sky-700 border border-sky-200 text-[11px] font-normal">
                                            {{ $mat->fixed_size }} Meter
                                        </span>
                                    @else
                                        <span class="text-slate-500 text-[11px]">Per Pcs / Roll</span>
                                    @endif
                                </td>
                                <td class="text-end font-mono text-slate-700">
                                    Rp {{ number_format($mat->purchase_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-mono fw-bold text-teal-700">
                                    Rp {{ number_format($mat->retail_price, 0, ',', '.') }}
                                </td>
                                <td>
                                    @if($mat->wholesalePrices->count() > 0)
                                        <div class="d-flex flex-column gap-0.5">
                                            @foreach($mat->wholesalePrices as $wp)
                                                <span class="text-[10px] text-slate-600 font-mono">
                                                    &ge; {{ $wp->min_qty }} pcs: <strong>Rp {{ number_format($wp->wholesale_price ?? 0, 0, ',', '.') }}</strong>
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($mat->stock_qty <= 5)
                                        <span class="badge bg-rose-50 text-rose-700 border border-rose-200 font-bold px-2 py-1 text-[11px]">
                                            <i class="fa-solid fa-circle-exclamation me-1"></i> {{ number_format($mat->stock_qty) }}
                                        </span>
                                    @else
                                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold px-2 py-1 text-[11px]">
                                            {{ number_format($mat->stock_qty) }} Units
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalEditMaterial{{ $mat->id }}" title="Edit Product">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>
                                        <form action="{{ route('materials.destroy', $mat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus produk ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete Product">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="p-4">
                                        <i class="fa-solid fa-boxes-stacked fs-1 text-slate-300 mb-2"></i>
                                        <p class="mb-0">Belum ada data Master Bahan Baku & Produk.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- View Mode 2: Odoo Kanban Cards View -->
        <div class="grid-view-container d-none p-4 bg-slate-50 border-top">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @forelse($materials as $mat)
                    <div class="o_kanban_record bg-white border rounded p-3 shadow-sm hover:shadow transition relative d-flex flex-col justify-content-between search-card" style="border-left: 4px solid var(--o-accent-color) !important;">
                        <div>
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <h6 class="fw-bold text-slate-900 mb-0 line-clamp-1 fs-6">{{ $mat->material_name }}</h6>
                                @if($mat->stock_qty <= 5)
                                    <span class="badge bg-rose-50 text-rose-700 border border-rose-200 text-[10px]">
                                        Low: {{ $mat->stock_qty }}
                                    </span>
                                @else
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px]">
                                        {{ $mat->stock_qty }} Units
                                    </span>
                                @endif
                            </div>

                            <div class="text-[11px] text-slate-500 mb-3">
                                <i class="fa-solid fa-building me-1 opacity-70"></i> {{ $mat->supplier->name ?? 'Tanpa Vendor' }}
                            </div>

                            <div class="p-2 bg-slate-50 rounded border border-slate-100 mb-3 space-y-1">
                                <div class="d-flex justify-content-between text-[11px]">
                                    <span class="text-slate-500">Sales Price:</span>
                                    <span class="fw-bold text-teal-700">Rp {{ number_format($mat->retail_price, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between text-[11px]">
                                    <span class="text-slate-500">Cost (HPP):</span>
                                    <span class="text-slate-700 font-mono">Rp {{ number_format($mat->purchase_price, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-slate-100">
                            <span class="text-[10px] font-mono text-slate-400">#MAT-{{ $mat->id }}</span>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalEditMaterial{{ $mat->id }}">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                                <form action="{{ route('materials.destroy', $mat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus produk ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">Belum ada data Master Bahan Baku & Produk.</div>
                @endforelse
            </div>
        </div>

    </div>
</div>

<!-- Modal Add Material (Odoo Form Style) -->
<div class="modal fade" id="modalAddMaterial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-3 shadow-2xl border-0 overflow-hidden">
            <form action="{{ route('materials.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-slate-900 text-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="modal-title fs-6 fw-bold text-white mb-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-box-open text-teal-400"></i> Tambah Master Produk / Bahan Baku
                    </h5>
                    <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <!-- Product Name -->
                        <div class="col-12">
                            <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                <i class="fa-solid fa-box text-primary me-1"></i> Nama Bahan / Produk Cetak <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="material_name" class="form-control form-control-sm font-bold text-slate-900" placeholder="Contoh: Kertas Vinyl Glossy Roll A3+" required>
                        </div>

                        <!-- Category -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                <i class="fa-solid fa-layer-group text-primary me-1"></i> Kategori Produk <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="category" list="mat-category-list" class="form-control form-control-sm font-semibold" placeholder="Pilih atau ketik kategori..." required>
                            <datalist id="mat-category-list">
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

                        <!-- Branch -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                <i class="fa-solid fa-store text-primary me-1"></i> Cabang Penempatan
                            </label>
                            @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin())
                                <select name="branch_id" class="form-select form-select-sm font-semibold">
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}" {{ (auth()->user()->branch_id == $b->id || request('branch_id') == $b->id) ? 'selected' : '' }}>
                                            {{ $b->nama_cabang }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                                <input type="text" class="form-control form-control-sm bg-light font-semibold" value="{{ auth()->user()->branch->nama_cabang ?? 'Cabang Anda' }}" readonly>
                            @endif
                        </div>

                        <!-- Vendor / Supplier -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                <i class="fa-solid fa-truck text-secondary me-1"></i> Vendor / Supplier Utama
                            </label>
                            <select name="supplier_id" class="form-select form-select-sm">
                                <option value="">-- Pilih Vendor (Opsional) --</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Unit & Size -->
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
                            <input type="number" step="0.01" name="fixed_size" class="form-control form-control-sm" placeholder="Contoh: 50">
                        </div>

                        <!-- Initial Stock -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                <i class="fa-solid fa-cubes text-primary me-1"></i> Stok Awal (On Hand) <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="stock_qty" value="10" class="form-control form-control-sm fw-bold text-blue-900" required min="0">
                        </div>

                        <!-- Cost Price (HPP) -->
                        <div class="col-md-4">
                            <div class="p-2.5 rounded-3 bg-amber-50 border border-amber-300">
                                <label class="form-label fw-bold text-amber-900 text-xs text-uppercase mb-1 d-block">
                                    <i class="fa-solid fa-sack-dollar text-amber-600 me-1"></i> Modal HPP (Cost Price) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white fw-bold text-amber-800">Rp</span>
                                    <input type="number" name="purchase_price" class="form-control form-control-sm fw-bold font-monospace" placeholder="150000" required min="0">
                                </div>
                                <span class="text-[10px] text-amber-700 mt-1 d-block">Harga beli/modal per unit</span>
                            </div>
                        </div>

                        <!-- Sales Price (Eceran) -->
                        <div class="col-md-4">
                            <div class="p-2.5 rounded-3 bg-emerald-50 border border-emerald-300">
                                <label class="form-label fw-bold text-emerald-900 text-xs text-uppercase mb-1 d-block">
                                    <i class="fa-solid fa-tag text-emerald-600 me-1"></i> Harga Jual (Sales Price) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white fw-bold text-emerald-700">Rp</span>
                                    <input type="number" name="retail_price" class="form-control form-control-sm fw-bold text-emerald-800 font-monospace" placeholder="220000" required min="0">
                                </div>
                                <span class="text-[10px] text-emerald-700 mt-1 d-block">Harga normal eceran kasir</span>
                            </div>
                        </div>

                        <!-- Wholesale Pricing Tiers -->
                        <div class="col-12 mt-3">
                            <div class="card border border-indigo-200 bg-indigo-50/40 p-3 rounded-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <h6 class="fw-bold text-indigo-900 mb-0 d-flex align-items-center gap-1.5 fs-7">
                                            <i class="fa-solid fa-tags text-indigo-600"></i> Tiering Harga Grosir (Wholesale Prices)
                                        </h6>
                                        <span class="text-slate-500 text-[11px]">Diskon harga khusus untuk pembelian kuantitas banyak</span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary py-1 px-2.5 text-xs font-semibold" onclick="addWholesaleRowMat('wholesale-add-container')">
                                        <i class="fa-solid fa-plus me-1"></i> Tambah Tier Grosir
                                    </button>
                                </div>

                                <div id="wholesale-add-container" class="d-flex flex-column gap-2 mt-2">
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

<!-- Modals Edit Material -->
@foreach($materials as $mat)
    <div class="modal fade" id="modalEditMaterial{{ $mat->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-3 shadow-2xl border-0 overflow-hidden">
                <form action="{{ route('materials.update', $mat->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-slate-900 text-white py-3 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="modal-title fs-6 fw-bold text-white mb-0 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-pen-to-square text-teal-400"></i> Edit Produk: {{ $mat->material_name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 bg-white">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                    <i class="fa-solid fa-box text-primary me-1"></i> Nama Bahan / Produk Cetak <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="material_name" value="{{ $mat->material_name }}" class="form-control form-control-sm font-bold text-slate-900" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                    <i class="fa-solid fa-layer-group text-primary me-1"></i> Kategori Produk
                                </label>
                                <input type="text" name="category" value="{{ $mat->category }}" list="mat-category-list" class="form-control form-control-sm font-semibold">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                    <i class="fa-solid fa-truck text-secondary me-1"></i> Vendor / Supplier Utama
                                </label>
                                <select name="supplier_id" class="form-select form-select-sm">
                                    <option value="">-- Pilih Vendor --</option>
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->id }}" {{ $mat->supplier_id == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                    <i class="fa-solid fa-arrows-left-right text-secondary me-1"></i> Panjang / Ukuran (Meter)
                                </label>
                                <input type="number" step="0.01" name="fixed_size" value="{{ $mat->fixed_size }}" class="form-control form-control-sm">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-slate-800 text-xs text-uppercase mb-1">
                                    <i class="fa-solid fa-cubes text-primary me-1"></i> Stok Fisik (On Hand) <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="stock_qty" value="{{ $mat->stock_qty }}" class="form-control form-control-sm font-bold text-blue-900" required min="0">
                            </div>

                            <!-- Cost Price (HPP) -->
                            <div class="col-md-6">
                                <div class="p-2.5 rounded-3 bg-amber-50 border border-amber-300">
                                    <label class="form-label fw-bold text-amber-900 text-xs text-uppercase mb-1 d-block">
                                        <i class="fa-solid fa-sack-dollar text-amber-600 me-1"></i> Modal HPP (Cost Price) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white fw-bold text-amber-800">Rp</span>
                                        <input type="number" name="purchase_price" value="{{ $mat->purchase_price }}" class="form-control form-control-sm fw-bold font-monospace" required min="0">
                                    </div>
                                    <span class="text-[10px] text-amber-700 mt-1 d-block">Harga beli/modal per unit</span>
                                </div>
                            </div>

                            <!-- Sales Price (Eceran) -->
                            <div class="col-md-6">
                                <div class="p-2.5 rounded-3 bg-emerald-50 border border-emerald-300">
                                    <label class="form-label fw-bold text-emerald-900 text-xs text-uppercase mb-1 d-block">
                                        <i class="fa-solid fa-tag text-emerald-600 me-1"></i> Harga Jual Normal (Sales Price) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white fw-bold text-emerald-700">Rp</span>
                                        <input type="number" name="retail_price" value="{{ $mat->retail_price }}" class="form-control form-control-sm fw-bold text-emerald-800 font-monospace" required min="0">
                                    </div>
                                    <span class="text-[10px] text-emerald-700 mt-1 d-block">Harga normal eceran kasir</span>
                                </div>
                            </div>

                            <!-- Wholesale Pricing Tiers in Edit Modal -->
                            <div class="col-12 mt-3">
                                <div class="card border border-indigo-200 bg-indigo-50/40 p-3 rounded-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h6 class="fw-bold text-indigo-900 mb-0 d-flex align-items-center gap-1.5 fs-7">
                                                <i class="fa-solid fa-tags text-indigo-600"></i> Tiering Harga Grosir (Wholesale Prices)
                                            </h6>
                                            <span class="text-slate-500 text-[11px]">Diskon bertingkat untuk pembelian banyak</span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-primary py-1 px-2.5 text-xs font-semibold" onclick="addWholesaleRowMat('wholesale-edit-container-{{ $mat->id }}')">
                                            <i class="fa-solid fa-plus me-1"></i> Tambah Tier Grosir
                                        </button>
                                    </div>

                                    <div id="wholesale-edit-container-{{ $mat->id }}" class="d-flex flex-column gap-2 mt-2">
                                        @foreach($mat->wholesalePrices as $wIdx => $wp)
                                            <div class="d-flex align-items-center gap-2 p-2 bg-white rounded border">
                                                <div class="flex-fill">
                                                    <label class="form-label text-slate-500 text-[10px] fw-bold mb-0.5 d-block">Minimal Beli (Qty/Pcs):</label>
                                                    <input type="number" min="1" name="wholesale[{{ $wIdx }}][min_qty]" value="{{ $wp->min_qty }}" class="form-control form-control-sm" required>
                                                </div>
                                                <div class="flex-fill">
                                                    <label class="form-label text-slate-500 text-[10px] fw-bold mb-0.5 d-block">Harga Grosir Satuan (Rp):</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light">Rp</span>
                                                        <input type="number" min="0" name="wholesale[{{ $wIdx }}][price]" value="{{ $wp->wholesale_price }}" class="form-control form-control-sm font-monospace font-bold" required>
                                                    </div>
                                                </div>
                                                <div class="pt-3">
                                                    <button type="button" class="btn btn-sm btn-outline-danger border-0 py-1 px-2" onclick="this.closest('.d-flex').remove()" title="Hapus Tier">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-slate-50 border-top py-2.5 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn-odoo-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-odoo-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<script>
    function addWholesaleRowMat(containerId, minQty = '', price = '') {
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
