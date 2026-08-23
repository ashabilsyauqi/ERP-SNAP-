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
        <div class="modal-content rounded-2 shadow-lg border-0">
            <form action="{{ route('materials.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-slate-50 border-bottom py-2.5 px-4">
                    <h5 class="modal-title fs-6 fw-bold text-slate-800 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-box text-teal-600"></i> New Product (Master Bahan Baku)
                    </h5>
                    <button type="button" class="btn-close text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase tracking-wide">Product Name / Nama Bahan</label>
                            <input type="text" name="material_name" class="form-control form-control-sm" placeholder="e.g. Kertas Vinyl Glossy Roll A3+" required>
                        </div>
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase tracking-wide">Vendor / Supplier Utama</label>
                            <select name="supplier_id" class="form-select form-select-sm">
                                <option value="">-- Pilih Vendor --</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase tracking-wide">Panjang / Ukuran (Meter)</label>
                            <input type="number" step="0.01" name="fixed_size" class="form-control form-control-sm" placeholder="Contoh: 50">
                        </div>
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase tracking-wide">Cost Price / Modal HPP (Rp)</label>
                            <input type="number" name="purchase_price" class="form-control form-control-sm" placeholder="150000" required min="0">
                        </div>
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase tracking-wide">Sales Price / Harga Eceran (Rp)</label>
                            <input type="number" name="retail_price" class="form-control form-control-sm" placeholder="220000" required min="0">
                        </div>
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase tracking-wide">Initial Stock / Stok Awal</label>
                            <input type="number" name="stock_qty" value="10" class="form-control form-control-sm" required min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-slate-50 border-top py-2.5 px-4">
                    <button type="button" class="btn-odoo-secondary" data-bs-dismiss="modal">Discard</button>
                    <button type="submit" class="btn-odoo-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modals Edit Material -->
@foreach($materials as $mat)
    <div class="modal fade" id="modalEditMaterial{{ $mat->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-2 shadow-lg border-0">
                <form action="{{ route('materials.update', $mat->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-slate-50 border-bottom py-2.5 px-4">
                        <h5 class="modal-title fs-6 fw-bold text-slate-800 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-pen-to-square text-teal-600"></i> Edit Product: {{ $mat->material_name }}
                        </h5>
                        <button type="button" class="btn-close text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="form-label font-semibold text-slate-700 text-xs uppercase tracking-wide">Product Name / Nama Bahan</label>
                                <input type="text" name="material_name" value="{{ $mat->material_name }}" class="form-control form-control-sm" required>
                            </div>
                            <div>
                                <label class="form-label font-semibold text-slate-700 text-xs uppercase tracking-wide">Vendor / Supplier Utama</label>
                                <select name="supplier_id" class="form-select form-select-sm">
                                    <option value="">-- Pilih Vendor --</option>
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->id }}" {{ $mat->supplier_id == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label font-semibold text-slate-700 text-xs uppercase tracking-wide">Panjang / Ukuran (Meter)</label>
                                <input type="number" step="0.01" name="fixed_size" value="{{ $mat->fixed_size }}" class="form-control form-control-sm">
                            </div>
                            <div>
                                <label class="form-label font-semibold text-slate-700 text-xs uppercase tracking-wide">Cost Price / Modal HPP (Rp)</label>
                                <input type="number" name="purchase_price" value="{{ $mat->purchase_price }}" class="form-control form-control-sm" required min="0">
                            </div>
                            <div>
                                <label class="form-label font-semibold text-slate-700 text-xs uppercase tracking-wide">Sales Price / Harga Eceran (Rp)</label>
                                <input type="number" name="retail_price" value="{{ $mat->retail_price }}" class="form-control form-control-sm" required min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-slate-50 border-top py-2.5 px-4">
                        <button type="button" class="btn-odoo-secondary" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn-odoo-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection
