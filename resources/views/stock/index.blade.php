@extends('layouts.app')

@section('title', 'Data Stok & Opname')
@section('page-title', 'Data Stok & Opname Barang')

@section('content')
<div x-data="{ 
    editOpen: false, 
    editMaterial: { id: '', name: '', stock_qty: 0, purchase_price: 0, retail_price: 0 }
}" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Data Stok & Opname Barang</h2>
            <p class="text-sm text-slate-500">Pantau inventaris fisik bahan baku aktif, nilai modal/jual, serta lakukan penyesuaian stok opname.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('stock.inspection') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2 px-4 rounded-xl text-xs flex items-center gap-2 transition shadow-sm">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Pemeriksaan Barang Masuk
                @if($pendingCount > 0)
                    <span class="bg-rose-500 text-white font-bold text-[10px] px-1.5 py-0.5 rounded-full animate-pulse">{{ $pendingCount }}</span>
                @endif
            </a>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Item Bahan</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ number_format($totalItems) }} <span class="text-xs font-normal text-slate-500">Jenis</span></h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Unit Stok</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ number_format($totalStockQty) }} <span class="text-xs font-normal text-slate-500">Unit</span></h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 font-bold">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Nilai Aset Stok</p>
                <h3 class="text-xl font-bold text-slate-900 mt-0.5">Rp {{ number_format($totalAssetValue, 0, ',', '.') }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 font-bold">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Stok Menipis (≤5)</p>
                <h3 class="text-2xl font-bold text-amber-600 mt-0.5">{{ number_format($lowStockCount) }} <span class="text-xs font-normal text-slate-500">Item</span></h3>
            </div>
        </div>
    </div>

    <!-- Filter & Search Section with View Switcher -->
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200/60 flex flex-col sm:flex-row justify-between items-center gap-4" id="stock-wrapper" data-view-wrapper>
        <form method="GET" action="{{ route('stock.index') }}" class="w-full flex flex-col sm:flex-row items-center gap-3">
            <div class="relative w-full sm:w-80">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Cari nama bahan baku..." class="table-search-input w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <svg class="h-5 w-5 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            @if(auth()->user()->isOwner())
                <select name="branch_id" onchange="this.form.submit()" class="w-full sm:w-60 px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="all" {{ request('branch_id') == 'all' ? 'selected' : '' }}>Semua Cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                    @endforeach
                </select>
            @endif

            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium text-sm transition">Filter</button>
        </form>

        <!-- Dual View Switcher Toggle Buttons -->
        <div class="btn-group btn-group-sm ms-auto" role="group">
            <button type="button" class="btn btn-primary btn-view-list active font-semibold" onclick="toggleViewMode('list', 'stock-wrapper')">
                <i class="bi bi-list-task me-1"></i> List Table
            </button>
            <button type="button" class="btn btn-outline-secondary btn-view-grid font-semibold" onclick="toggleViewMode('grid', 'stock-wrapper')">
                <i class="bi bi-grid-3x3-gap-fill me-1"></i> Card Grid
            </button>
        </div>
    </div>

    <!-- Inventory Table & Card Grid Container -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
        <!-- Mode 1: Table List View -->
        <div class="table-view-container overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-500 text-xs uppercase tracking-wider font-semibold">
                        <th class="px-6 py-4">No</th>
                        <th class="px-6 py-4">Nama Bahan / Produk</th>
                        <th class="px-6 py-4">Supplier</th>
                        <th class="px-6 py-4">Ukuran / Spesifikasi</th>
                        <th class="px-6 py-4">Harga Beli Modal</th>
                        <th class="px-6 py-4">Harga Jual</th>
                        <th class="px-6 py-4 text-center">Sisa Stok Fisik</th>
                        <th class="px-6 py-4 text-center">Aksi / Opname</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-150 text-slate-700">
                    @forelse($materials as $material)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-900">{{ $material->material_name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                @if($material->supplier)
                                    <span class="inline-flex items-center gap-1 text-indigo-700 font-medium bg-indigo-50 px-2 py-0.5 rounded text-xs">
                                        {{ $material->supplier->name }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-xs">Tanpa Supplier</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                @if($material->fixed_size)
                                    <span class="font-medium text-slate-800">{{ $material->fixed_size }} Meter</span>
                                @else
                                    <span class="text-slate-400 italic">Standar</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-700">
                                Rp {{ number_format($material->purchase_price, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-emerald-700">
                                Rp {{ number_format($material->retail_price, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($material->stock_qty <= 0)
                                    <span class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 ring-1 ring-inset ring-rose-600/20">Habis (0)</span>
                                @elseif($material->stock_qty <= 5)
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20">Menipis ({{ $material->stock_qty }})</span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Aman ({{ $material->stock_qty }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button @click="
                                    editMaterial = {
                                        id: '{{ $material->id }}',
                                        name: '{{ addslashes($material->material_name) }}',
                                        stock_qty: '{{ $material->stock_qty }}',
                                        purchase_price: '{{ $material->purchase_price }}',
                                        retail_price: '{{ $material->retail_price }}'
                                    };
                                    editOpen = true;
                                " class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg font-semibold text-xs transition">
                                    Opname / Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400 text-sm">Belum ada data stok bahan baku.</td>
                        </tr>
                    @endforelse
                </tbody>
        </div>

        <!-- Mode 2: Grid / Card View (Dynamic Kotak-Kotak Gen-Z Style) -->
        <div class="grid-view-container d-none p-4">
            <div class="row g-4">
                @forelse($materials as $material)
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 grid-card">
                        <div class="card h-100 border rounded-4 shadow-sm hover-shadow transition">
                            <div class="card-header bg-light border-bottom p-3 d-flex justify-content-between align-items-center">
                                <span class="badge bg-light text-dark border font-mono">#MAT-{{ $material->id }}</span>
                                @if($material->stock_qty <= 5)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                        ⚠️ Stok {{ $material->stock_qty }} (Menipis)
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        Stok {{ number_format($material->stock_qty) }} Unit
                                    </span>
                                @endif
                            </div>
                            <div class="card-body p-3 space-y-3">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0 fs-6">{{ $material->material_name }}</h6>
                                    <small class="text-muted text-xs d-block mt-0.5">Supplier: {{ $material->supplier->name ?? '-' }}</small>
                                </div>

                                <div class="p-2.5 bg-light rounded-3 space-y-1">
                                    <div class="d-flex justify-content-between text-xs">
                                        <span class="text-muted">Nilai Modal (HPP):</span>
                                        <span class="fw-bold text-dark">Rp {{ number_format($material->purchase_price, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-xs">
                                        <span class="text-muted">Harga Jual (Eceran):</span>
                                        <span class="fw-bold text-success">Rp {{ number_format($material->retail_price, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-xs border-top pt-1 mt-1">
                                        <span class="text-muted">Total Nilai Aset:</span>
                                        <span class="fw-bold text-indigo">Rp {{ number_format($material->stock_qty * $material->purchase_price, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top p-3 text-end">
                                <button type="button" @click="
                                    editMaterial = {
                                        id: '{{ $material->id }}',
                                        name: '{{ addslashes($material->material_name) }}',
                                        stock_qty: '{{ $material->stock_qty }}',
                                        purchase_price: '{{ $material->purchase_price }}',
                                        retail_price: '{{ $material->retail_price }}'
                                    };
                                    editOpen = true;
                                " class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                                    <i class="bi bi-sliders me-1"></i> Opname / Edit
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">Belum ada data stok bahan baku.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Modal Opname / Edit Stok -->
    <div x-show="editOpen" class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="editOpen = false"></div>
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg z-10">
            <form :action="'/stock/materials/' + editMaterial.id" method="POST">
                @csrf
                @method('PUT')
                <div class="bg-white px-6 pb-6 pt-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Penyesuaian Stok (Opname)</h3>
                        <button type="button" @click="editOpen = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mb-4 p-3 bg-indigo-50/70 border border-indigo-100 rounded-xl">
                        <p class="text-xs text-indigo-700 font-semibold uppercase tracking-wider">Nama Barang</p>
                        <p class="text-base font-bold text-indigo-950 mt-0.5" x-text="editMaterial.name"></p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Jumlah Stok Fisik saat ini <span class="text-rose-500">*</span></label>
                            <input type="number" name="stock_qty" min="0" required x-model="editMaterial.stock_qty" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-base font-bold text-indigo-700">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Harga Beli Modal (Rp)</label>
                            <input type="number" name="purchase_price" min="0" x-model="editMaterial.purchase_price" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Harga Jual Eceran (Rp)</label>
                            <input type="number" name="retail_price" min="0" x-model="editMaterial.retail_price" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 rounded-b-2xl">
                    <button type="submit" class="inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">Simpan Penyesuaian</button>
                    <button type="button" @click="editOpen = false" class="inline-flex justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
