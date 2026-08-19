@extends('layouts.app')

@section('title', 'Master Bahan Baku & Produk')
@section('page-title', 'Katalog Master Bahan Baku & Produk (Inventory App)')

@section('content')
<div class="row">
    <div class="col-12 mb-4" id="materials-wrapper" data-view-wrapper>
        <div class="card shadow-sm border-0">
            <!-- Header Toolbar -->
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="bi bi-boxes text-warning me-2"></i> Master Bahan Baku & Produk
                    </h5>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                        {{ $materials->count() }} Items Registered
                    </span>
                </div>

                <div class="d-flex items-center gap-2 flex-wrap">
                    <!-- Real-Time Search Bar -->
                    <div class="input-group input-group-sm" style="width: 260px;">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control table-search-input border-start-0 ps-0" placeholder="🔍 Cari material, supplier..." aria-label="Search">
                    </div>

                    <!-- Dual View Switcher Toggle Buttons (Standard List vs Gen-Z Cards) -->
                    <div class="btn-group btn-group-sm" role="group" aria-label="View Switcher">
                        <button type="button" class="btn btn-primary btn-view-list active fw-semibold" onclick="toggleViewMode('list', 'materials-wrapper')" title="Tampilan Tabel Standard">
                            <i class="bi bi-list-task me-1"></i> List Table
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-view-grid fw-semibold" onclick="toggleViewMode('grid', 'materials-wrapper')" title="Tampilan Kotak-Kotak Gen-Z">
                            <i class="bi bi-grid-3x3-gap-fill me-1"></i> Card Grid
                        </button>
                    </div>

                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 font-semibold d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalAddMaterial">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Material Baru
                    </button>
                </div>
            </div>

            <!-- View Mode 1: Table List View (Standard ERP) -->
            <div class="table-view-container">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="materials-table">
                        <thead class="table-light text-uppercase fs-7 text-muted">
                            <tr>
                                <th class="ps-4 sortable">ID</th>
                                <th class="sortable">Nama Bahan Baku / Produk</th>
                                <th class="sortable">Supplier Main</th>
                                <th class="sortable">Ukuran / Satuan</th>
                                <th class="sortable">Harga Beli (HPP Modal)</th>
                                <th class="sortable">Harga Jual (Eceran)</th>
                                <th class="sortable">Tier Wholesale (Grosir)</th>
                                <th class="sortable text-center">Stok Gudang</th>
                                <th class="text-center no-sort">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materials as $mat)
                                <tr class="search-row">
                                    <td class="ps-4 font-mono fw-bold text-primary">#MAT-{{ $mat->id }}</td>
                                    <td>
                                        <div class="fw-bold text-dark fs-6">{{ $mat->material_name }}</div>
                                        <div class="text-muted text-xs">Cabang: {{ $mat->branch->nama_cabang ?? 'Pusat' }}</div>
                                    </td>
                                    <td>
                                        @if($mat->supplier)
                                            <span class="badge bg-light text-dark border"><i class="bi bi-building me-1"></i> {{ $mat->supplier->name }}</span>
                                        @else
                                            <span class="text-muted italic text-xs">Tanpa Supplier</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($mat->fixed_size)
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $mat->fixed_size }} Meter</span>
                                        @else
                                            <span class="text-muted text-xs">Custom / Per Pcs</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-indigo">
                                        Rp {{ number_format($mat->purchase_price, 0, ',', '.') }}
                                    </td>
                                    <td class="fw-bold text-success">
                                        Rp {{ number_format($mat->retail_price, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        @if($mat->wholesalePrices->count() > 0)
                                            <div class="d-flex flex-column gap-1">
                                                @foreach($mat->wholesalePrices as $wp)
                                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle text-start">
                                                        ≥ {{ $wp->min_qty }} Unit: Rp {{ number_format($wp->price, 0, ',', '.') }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted text-xs italic">Tanpa Tier Grosir</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($mat->stock_qty <= 5)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 fw-bold">
                                                ⚠️ {{ number_format($mat->stock_qty) }} (Menipis)
                                            </span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 fw-bold">
                                                {{ number_format($mat->stock_qty) }} Unit
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalEditMaterial{{ $mat->id }}">
                                                <i class="bi bi-pencil me-1"></i> Edit
                                            </button>
                                            <form action="{{ route('materials.destroy', $mat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus material ini dari Katalog Master?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger rounded-pill px-2">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">Belum ada data Master Bahan Baku & Produk.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View Mode 2: Grid / Card View (Dynamic Kotak-Kotak Gen-Z Style) -->
            <div class="grid-view-container d-none p-4">
                <div class="row g-4">
                    @forelse($materials as $mat)
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3 grid-card">
                            <div class="card h-100 border rounded-4 shadow-sm hover-shadow transition">
                                <div class="card-header bg-light border-bottom p-3 d-flex justify-content-between align-items-center">
                                    <span class="font-mono fw-bold text-primary text-xs">#MAT-{{ $mat->id }}</span>
                                    @if($mat->stock_qty <= 5)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            ⚠️ Stok {{ $mat->stock_qty }}
                                        </span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            Stok {{ number_format($mat->stock_qty) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="card-body p-3 space-y-3">
                                    <div class="d-flex align-items-start gap-2">
                                        <div class="rounded-3 bg-indigo-50 text-indigo-600 p-2 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                                            <i class="bi bi-box-seam fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0 line-clamp-2">{{ $mat->material_name }}</h6>
                                            <small class="text-muted text-xs d-block mt-0.5">Supplier: {{ $mat->supplier->name ?? '-' }}</small>
                                        </div>
                                    </div>

                                    <!-- Price Tag Grid -->
                                    <div class="p-2.5 bg-light rounded-3 space-y-1">
                                        <div class="d-flex justify-content-between text-xs">
                                            <span class="text-muted">Modal/HPP:</span>
                                            <span class="fw-bold text-dark">Rp {{ number_format($mat->purchase_price, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between text-xs">
                                            <span class="text-muted">Eceran:</span>
                                            <span class="fw-bold text-success">Rp {{ number_format($mat->retail_price, 0, ',', '.') }}</span>
                                        </div>
                                        @if($mat->fixed_size)
                                        <div class="d-flex justify-content-between text-xs border-top pt-1 mt-1">
                                            <span class="text-muted">Panjang:</span>
                                            <span class="fw-semibold text-info">{{ $mat->fixed_size }} Meter</span>
                                        </div>
                                        @endif
                                    </div>

                                    @if($mat->wholesalePrices->count() > 0)
                                    <div>
                                        <small class="text-muted font-mono text-xs d-block mb-1">Tier Grosir:</small>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($mat->wholesalePrices as $wp)
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 10px;">
                                                    ≥{{ $wp->min_qty }}: Rp {{ number_format($wp->price, 0, ',', '.') }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalEditMaterial{{ $mat->id }}">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </button>
                                    <form action="{{ route('materials.destroy', $mat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus material ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2">
                                            <i class="bi bi-trash"></i>
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
</div>

<!-- Modals Edit Material -->
@foreach($materials as $mat)
    <div class="modal fade" id="modalEditMaterial{{ $mat->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow border-0">
                <form action="{{ route('materials.update', $mat->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-light rounded-top-4">
                        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Master Material #{{ $mat->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 space-y-3">
                        <div>
                            <label class="form-label font-semibold text-dark text-sm">Nama Bahan Baku / Produk</label>
                            <input type="text" name="material_name" value="{{ $mat->material_name }}" class="form-control" required>
                        </div>
                        <div>
                            <label class="form-label font-semibold text-dark text-sm">Supplier Utama</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}" {{ $mat->supplier_id == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label font-semibold text-dark text-sm">Harga Beli / Modal (Rp)</label>
                                <input type="number" name="purchase_price" value="{{ $mat->purchase_price }}" class="form-control" required min="0">
                            </div>
                            <div class="col-6">
                                <label class="form-label font-semibold text-dark text-sm">Harga Jual Eceran (Rp)</label>
                                <input type="number" name="retail_price" value="{{ $mat->retail_price }}" class="form-control" required min="0">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label font-semibold text-dark text-sm">Panjang / Ukuran (m)</label>
                                <input type="number" step="0.01" name="fixed_size" value="{{ $mat->fixed_size }}" class="form-control" placeholder="Opsional">
                            </div>
                            <div class="col-6">
                                <label class="form-label font-semibold text-dark text-sm">Stok Gudang (Unit)</label>
                                <input type="number" name="stock_qty" value="{{ $mat->stock_qty }}" class="form-control" required min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<!-- Modal Add Material -->
<div class="modal fade" id="modalAddMaterial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow border-0">
            <form action="{{ route('materials.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i> Tambah Master Bahan Baku Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 space-y-3">
                    <div>
                        <label class="form-label font-semibold text-dark text-sm">Nama Bahan Baku / Produk</label>
                        <input type="text" name="material_name" class="form-control" placeholder="Contoh: Kertas Vinyl Glossy Roll A3" required>
                    </div>
                    <div>
                        <label class="form-label font-semibold text-dark text-sm">Supplier Utama</label>
                        <select name="supplier_id" class="form-select">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label font-semibold text-dark text-sm">Harga Beli / Modal (Rp)</label>
                            <input type="number" name="purchase_price" class="form-control" placeholder="150000" required min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label font-semibold text-dark text-sm">Harga Jual Eceran (Rp)</label>
                            <input type="number" name="retail_price" class="form-control" placeholder="220000" required min="0">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label font-semibold text-dark text-sm">Panjang / Ukuran (m)</label>
                            <input type="number" step="0.01" name="fixed_size" class="form-control" placeholder="50">
                        </div>
                        <div class="col-6">
                            <label class="form-label font-semibold text-dark text-sm">Stok Awal Gudang</label>
                            <input type="number" name="stock_qty" value="10" class="form-control" required min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold">Tambah ke Master Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
