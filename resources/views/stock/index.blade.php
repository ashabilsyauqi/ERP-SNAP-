@extends('layouts.app')

@section('title', 'Stock & Physical Inventory')
@section('page-title', 'Stock Opname & Valuation')

@section('action-buttons')
<a href="{{ route('stock.inspection') }}" class="btn-odoo-primary text-decoration-none">
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
    editMaterial: { id: '', name: '', stock_qty: 0, purchase_price: 0, retail_price: 0 }
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
                            <th class="sortable">Vendor</th>
                            <th class="sortable">Specification / Unit</th>
                            <th class="sortable text-end">Cost Price</th>
                            <th class="sortable text-end">Sales Price</th>
                            <th class="sortable text-center">On Hand Qty</th>
                            <th class="text-center no-sort" style="width: 120px;">Stock Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($materials as $material)
                            <tr class="search-row">
                                <td class="ps-3 text-center">
                                    <input type="checkbox" class="form-check-input">
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800">{{ $material->material_name }}</div>
                                    <span class="text-slate-400 font-mono text-[10px]">#MAT-{{ $material->id }} &bull; {{ $material->branch->nama_cabang ?? 'Pusat' }}</span>
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
                                    Rp {{ number_format($material->purchase_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-mono fw-bold text-teal-700">
                                    Rp {{ number_format($material->retail_price, 0, ',', '.') }}
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
                                        editMaterial = {
                                            id: '{{ $material->id }}',
                                            name: '{{ addslashes($material->material_name) }}',
                                            stock_qty: '{{ $material->stock_qty }}',
                                            purchase_price: '{{ $material->purchase_price }}',
                                            retail_price: '{{ $material->retail_price }}'
                                        };
                                        editOpen = true;
                                    " class="btn btn-sm btn-odoo-secondary py-0.5 px-2 text-xs">
                                        <i class="fa-solid fa-sliders me-1"></i> Opname
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="p-4">
                                        <i class="fa-solid fa-boxes-stacked fs-1 text-slate-300 mb-2"></i>
                                        <p class="mb-0">Belum ada data stok bahan baku.</p>
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
                            <span class="font-mono text-slate-400 text-[10px]">#MAT-{{ $material->id }}</span>
                            <span class="badge {{ $material->stock_qty <= 5 ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }} text-[10px]">
                                {{ $material->stock_qty }} Units
                            </span>
                        </div>
                        <h6 class="fw-bold text-slate-900 line-clamp-1 mb-1">{{ $material->material_name }}</h6>
                        <div class="text-[11px] text-slate-500 mb-2">Vendor: {{ $material->supplier->name ?? '-' }}</div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-slate-100">
                            <span class="fw-bold font-mono text-teal-700 text-xs">Rp {{ number_format($material->retail_price, 0, ',', '.') }}</span>
                            <button @click="
                                editMaterial = {
                                    id: '{{ $material->id }}',
                                    name: '{{ addslashes($material->material_name) }}',
                                    stock_qty: '{{ $material->stock_qty }}',
                                    purchase_price: '{{ $material->purchase_price }}',
                                    retail_price: '{{ $material->retail_price }}'
                                };
                                editOpen = true;
                            " class="btn btn-sm btn-light py-0 px-2 text-xs">
                                Opname
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">Belum ada data inventaris.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Stock Opname Modal (Odoo Form Style) -->
    <div x-show="editOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" style="display: none;" x-cloak>
        <div class="bg-white rounded-2 shadow-2xl border w-full max-w-lg overflow-hidden" @click.away="editOpen = false">
            <form :action="'/stock/materials/' + editMaterial.id" method="POST">
                @csrf
                @method('PUT')
                <div class="bg-slate-50 border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fs-6 fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-sliders text-teal-600"></i> Stock Opname / Inventory Adjustment
                    </h5>
                    <button type="button" class="btn-close text-xs" @click="editOpen = false"></button>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="form-label font-semibold text-slate-700 text-xs uppercase">Nama Bahan Baku</label>
                        <input type="text" x-model="editMaterial.name" class="form-control form-control-sm bg-light" readonly>
                    </div>
                    <div>
                        <label class="form-label font-semibold text-slate-700 text-xs uppercase">Sisa Stok Fisik (Real On Hand)</label>
                        <input type="number" name="stock_qty" x-model="editMaterial.stock_qty" class="form-control form-control-sm" required min="0">
                        <small class="text-slate-400 text-[11px]">Masukkan jumlah fisik aktual hasil perhitungan opname gudang.</small>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Harga Modal HPP (Rp)</label>
                            <input type="number" name="purchase_price" x-model="editMaterial.purchase_price" class="form-control form-control-sm" required min="0">
                        </div>
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Harga Jual Eceran (Rp)</label>
                            <input type="number" name="retail_price" x-model="editMaterial.retail_price" class="form-control form-control-sm" required min="0">
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 border-top px-4 py-2.5 d-flex justify-content-end gap-2">
                    <button type="button" class="btn-odoo-secondary" @click="editOpen = false">Cancel</button>
                    <button type="submit" class="btn-odoo-primary">Validate Adjustment</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
