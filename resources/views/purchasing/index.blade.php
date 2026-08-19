@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Purchasing & Material Procurement</h2>
            <p class="text-sm text-slate-500">Kelola master bahan baku, penerbitan Purchase Order (PO), dan riwayat pengadaan SAP Standard.</p>
        </div>
        <div class="flex items-center gap-4">
            @if(auth()->user()->isOwner())
            <form action="{{ route('purchasing.index') }}" method="GET" class="hidden sm:block">
                <select name="branch_id" onchange="this.form.submit()" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block w-full py-2.5 px-3">
                    <option value="all" {{ request('branch_id') == 'all' ? 'selected' : '' }}>Semua Cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->nama_cabang }} {{ $branch->trashed() ? '(Archived)' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
            @endif

            <a href="{{ route('purchasing.create') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 px-5 rounded-xl transition duration-150 shadow-sm flex items-center gap-2 cursor-pointer w-full sm:w-auto justify-center text-sm">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Buat Purchase Order Baru
            </a>
        </div>
    </div>

    <!-- KPI Summary Metrics Header Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 font-bold">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Belanja Terverifikasi</p>
                <h3 class="text-xl font-bold text-slate-900 mt-0.5">Rp {{ number_format($totalSpend, 0, ',', '.') }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 font-bold">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Menunggu Cek Gudang</p>
                <h3 class="text-2xl font-bold text-amber-600 mt-0.5">{{ number_format($pendingCount) }} <span class="text-xs font-normal text-slate-500">PO</span></h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Terverifikasi (GR Completed)</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-0.5">{{ number_format($receivedCount) }} <span class="text-xs font-normal text-slate-500">PO</span></h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 font-bold">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Ditolak / Retur</p>
                <h3 class="text-2xl font-bold text-rose-600 mt-0.5">{{ number_format($rejectedCount) }} <span class="text-xs font-normal text-slate-500">PO</span></h3>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden flex flex-col">
        
        <!-- Tab Navigation & Filters -->
        <div class="p-5 border-b border-slate-200/80 bg-white flex flex-col gap-4">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <!-- Tabs -->
                <div class="flex gap-1.5 p-1 bg-slate-100 rounded-xl overflow-x-auto w-full md:w-auto hide-scrollbar">
                    <button onclick="switchMainTab('history')" id="tab-history" class="px-5 py-2 font-semibold text-xs rounded-lg transition duration-200 bg-white text-slate-800 shadow-sm whitespace-nowrap">Riwayat PO & Pembelian (SAP Table)</button>
                    <button onclick="switchMainTab('inventory')" id="tab-inventory" class="px-5 py-2 font-semibold text-xs rounded-lg transition duration-200 text-slate-500 hover:text-slate-800 whitespace-nowrap">Master Bahan Baku</button>
                    <button onclick="switchMainTab('supplier')" id="tab-supplier" class="px-5 py-2 font-semibold text-xs rounded-lg transition duration-200 text-slate-500 hover:text-slate-800 whitespace-nowrap">Data Supplier</button>
                </div>

                <!-- Global Quick Search -->
                <div class="relative w-full md:w-72">
                    <input type="text" id="global-search" onkeyup="filterActiveTable()" placeholder="Cari No. PO, Supplier, Barang..." 
                        class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/15 focus:border-indigo-500 text-xs transition duration-150">
                    <div class="absolute left-3.5 top-2.5 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- SAP Server-side Filter & Instant Search Toolbar -->
            <div class="pt-3 border-t border-slate-100 space-y-3" id="po-wrapper" data-view-wrapper>
                <div class="flex justify-between items-center flex-wrap gap-2">
                    <div class="relative w-full sm:w-72">
                        <input type="text" class="table-search-input form-control w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-xs bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="🔍 Search No. PO, Supplier, Material...">
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Dual View Switcher Toggle Buttons -->
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-primary btn-view-list active font-semibold" onclick="toggleViewMode('list', 'po-wrapper')">
                                <i class="bi bi-list-task me-1"></i> List Table
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-view-grid font-semibold" onclick="toggleViewMode('grid', 'po-wrapper')">
                                <i class="bi bi-grid-3x3-gap-fill me-1"></i> Card Grid
                            </button>
                        </div>

                        <!-- 1-Click Excel Export Button -->
                        <button type="button" onclick="exportTableToExcel('po-table', 'Purchase_Orders_SnapPrint')" class="btn btn-sm btn-outline-success rounded-pill px-3 font-semibold d-inline-flex align-items-center">
                            <i class="bi bi-file-earmark-excel me-1"></i> Ekspor Excel
                        </button>
                    </div>
                </div>

                <form method="GET" action="{{ route('purchasing.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Mulai Tanggal</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Filter Supplier</label>
                        <select name="supplier_id" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-xs bg-white">
                            <option value="all">Semua Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Status Verifikasi</label>
                        <div class="flex gap-2">
                            <select name="status" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-xs bg-white">
                                <option value="all">Semua Status</option>
                                <option value="pending_verification" {{ request('status') == 'pending_verification' ? 'selected' : '' }}>Menunggu Cek</option>
                                <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Diterima (GR Completed)</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak / Retur</option>
                            </select>
                            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-3 py-1.5 rounded-lg font-medium text-xs transition">
                                Terapkan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 1. Riwayat Pembelian (SAP Document Table & Card Grid) -->
        <div id="view-history" class="tab-view animate-fade-in">
            <!-- Mode 1: Table List View -->
            <div class="table-view-container overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 search-table">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">No. PO (SAP Doc)</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">No. Faktur Supplier</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">Tgl PO & GR</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">Supplier</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">Barang / Material</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">Qty & Satuan</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">Harga Satuan</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">Total Biaya PO</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase sortable">Status PO & Gudang</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase no-sort">Aksi / Cetak</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($purchases as $purchase)
                            <tr class="hover:bg-slate-50/50 transition duration-150 search-row">
                                <!-- No. PO -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-700 search-target">
                                    {{ $purchase->po_number ?? ('PO-'.str_pad($purchase->id, 6, '0', STR_PAD_LEFT)) }}
                                </td>
                                <!-- No. Faktur Supplier -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-700 search-target">
                                    {{ $purchase->vendor_ref ?? '-' }}
                                </td>
                                <!-- Tgl Order & GR -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                    <div><span class="text-slate-400">PO:</span> {{ $purchase->created_at->format('d M Y') }}</div>
                                    @if($purchase->verified_at)
                                        <div class="text-[11px] text-emerald-700"><span class="text-slate-400">GR:</span> {{ \Carbon\Carbon::parse($purchase->verified_at)->format('d M Y') }}</div>
                                    @endif
                                </td>
                                <!-- Supplier -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-800 search-target">
                                    @if($purchase->supplier)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-800">
                                            {{ $purchase->supplier->name }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-xs">Tanpa Supplier</span>
                                    @endif
                                </td>
                                <!-- Barang -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900 search-target">
                                    {{ $purchase->material->material_name ?? 'N/A' }}
                                    @if($purchase->material && $purchase->material->fixed_size)
                                        <span class="text-xs text-slate-500 font-normal">({{ $purchase->material->fixed_size }}m)</span>
                                    @endif
                                </td>
                                <!-- Qty & Satuan -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-800">
                                    {{ number_format($purchase->qty_bought) }} Unit
                                </td>
                                <!-- Harga Satuan -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-medium">
                                    Rp {{ number_format($purchase->total_cost / max(1, $purchase->qty_bought), 0, ',', '.') }}
                                </td>
                                <!-- Total Biaya PO -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-700 font-bold">
                                    Rp {{ number_format($purchase->total_cost, 0, ',', '.') }}
                                </td>
                                <!-- Status PO & Gudang -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($purchase->status === 'waiting_approval')
                                        <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20 animate-pulse">
                                            ⏳ Menunggu ACC Manager
                                        </span>
                                    @elseif($purchase->status === 'approved' || $purchase->status === 'pending_verification')
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                            ✓ PO Disetujui (Cek Gudang)
                                        </span>
                                        @if($purchase->approvedBy)
                                            <div class="text-[10px] text-slate-400 mt-0.5">ACC: {{ $purchase->approvedBy->username }}</div>
                                        @endif
                                    @elseif($purchase->status === 'received')
                                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                            ✓ Diterima & Masuk Stok
                                        </span>
                                        <div class="text-[10px] text-slate-400 mt-0.5">by {{ $purchase->verifiedBy->username ?? 'Manager' }}</div>
                                    @elseif($purchase->status === 'rejected')
                                        <span class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                            ✕ Ditolak / Retur
                                        </span>
                                    @endif
                                </td>
                                <!-- Aksi / ACC & Cetak PO -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        @if($purchase->status === 'waiting_approval' && (auth()->user()->isOwner() || auth()->user()->isManager()))
                                            <form action="{{ route('purchasing.approve', $purchase->id) }}" method="POST" onsubmit="return confirm('Setujui Purchase Order #{{ $purchase->po_number }}? Tanda tangan digital Anda akan terstempel pada nota PO.');">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition shadow-sm">
                                                    ✓ Setujui PO
                                                </button>
                                            </form>
                                        @endif

                                        <button onclick="printPO('{{ $purchase->po_number ?? 'PO-'.$purchase->id }}', '{{ addslashes($purchase->supplier->name ?? 'Supplier') }}', '{{ addslashes($purchase->material->material_name ?? '-') }}', '{{ $purchase->qty_bought }}', '{{ number_format($purchase->total_cost, 0, ',', '.') }}', '{{ $purchase->created_at->format('d M Y') }}', '{{ addslashes($purchase->user->username ?? 'Staf Purchasing') }}', '{{ $purchase->user->signature_path ? asset('storage/'.$purchase->user->signature_path) : '' }}', '{{ addslashes($purchase->approvedBy->username ?? 'Manajer Toko') }}', '{{ $purchase->approvedBy && $purchase->approvedBy->signature_path ? asset('storage/'.$purchase->approvedBy->signature_path) : '' }}', '{{ $purchase->approved_at ? \Carbon\Carbon::parse($purchase->approved_at)->format('d M Y, H:i') : '' }}')" 
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                                            <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                            Cetak PO
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-sm text-slate-400">Belum ada data riwayat pembelian / PO.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mode 2: Grid / Card View (Dynamic Kotak-Kotak Gen-Z Style) -->
            <div class="grid-view-container d-none pt-4">
                <div class="row g-4">
                    @forelse($purchases as $purchase)
                        <div class="col-12 col-sm-6 col-md-4 grid-card">
                            <div class="card h-100 border rounded-4 shadow-sm hover-shadow transition">
                                <div class="card-header bg-light border-bottom p-3 d-flex justify-content-between align-items-center">
                                    <span class="font-mono fw-bold text-indigo fs-6">{{ $purchase->po_number ?? 'PO-'.$purchase->id }}</span>
                                    @if($purchase->status === 'waiting_approval')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                            ⏳ Menunggu ACC
                                        </span>
                                    @elseif($purchase->status === 'received')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            ✓ GR Completed
                                        </span>
                                    @else
                                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                                            ✓ PO Disetujui
                                        </span>
                                    @endif
                                </div>
                                <div class="card-body p-3 space-y-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <small class="text-muted text-xs d-block">Supplier:</small>
                                            <span class="fw-bold text-dark fs-6">{{ $purchase->supplier->name ?? 'Tanpa Supplier' }}</span>
                                        </div>
                                        <span class="badge bg-light text-dark border font-mono fs-7">{{ $purchase->vendor_ref ?? '-' }}</span>
                                    </div>

                                    <div class="p-2.5 bg-light rounded-3 space-y-1">
                                        <div class="d-flex justify-content-between text-xs">
                                            <span class="text-muted">Material:</span>
                                            <span class="fw-bold text-dark">{{ $purchase->material->material_name ?? 'N/A' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between text-xs">
                                            <span class="text-muted">Qty Order:</span>
                                            <span class="fw-bold text-dark">{{ number_format($purchase->qty_bought) }} Unit</span>
                                        </div>
                                        <div class="d-flex justify-content-between text-xs border-top pt-1 mt-1">
                                            <span class="text-muted">Total Nilai PO:</span>
                                            <span class="fw-bold text-indigo fs-6">Rp {{ number_format($purchase->total_cost, 0, ',', '.') }}</span>
                                        </div>
                                    </div>

                                    <div class="text-xs text-muted">
                                        <div>Order Date: <span class="fw-semibold text-dark">{{ $purchase->created_at->format('d M Y, H:i') }}</span></div>
                                        <div>Created By: <span class="fw-semibold text-dark">{{ $purchase->user->username ?? 'Staff' }}</span></div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                                    @if($purchase->status === 'waiting_approval' && (auth()->user()->isOwner() || auth()->user()->isManager()))
                                        <form action="{{ route('purchasing.approve', $purchase->id) }}" method="POST" onsubmit="return confirm('Setujui Purchase Order #{{ $purchase->po_number }}?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 fw-bold">
                                                ✓ Setujui PO
                                            </button>
                                        </form>
                                    @else
                                        <span></span>
                                    @endif

                                    <button onclick="printPO('{{ $purchase->po_number ?? 'PO-'.$purchase->id }}', '{{ addslashes($purchase->supplier->name ?? 'Supplier') }}', '{{ addslashes($purchase->material->material_name ?? '-') }}', '{{ $purchase->qty_bought }}', '{{ number_format($purchase->total_cost, 0, ',', '.') }}', '{{ $purchase->created_at->format('d M Y') }}', '{{ addslashes($purchase->user->username ?? 'Staf Purchasing') }}', '{{ $purchase->user->signature_path ? asset('storage/'.$purchase->user->signature_path) : '' }}', '{{ addslashes($purchase->approvedBy->username ?? 'Manajer Toko') }}', '{{ $purchase->approvedBy && $purchase->approvedBy->signature_path ? asset('storage/'.$purchase->approvedBy->signature_path) : '' }}', '{{ $purchase->approved_at ? \Carbon\Carbon::parse($purchase->approved_at)->format('d M Y, H:i') : '' }}')" 
                                        class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                        <i class="bi bi-printer me-1"></i> Cetak PO
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted">Belum ada data riwayat PO.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- 2. Master Barang (Inventory) -->
        <div id="view-inventory" class="overflow-x-auto tab-view hidden">
            <table class="min-w-full divide-y divide-slate-200 search-table">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Material Name</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Cabang</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Size</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">HPP (Modal)</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Retail Price</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Stock Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($materials as $material)
                        <tr class="hover:bg-slate-50/30 transition duration-150 search-row">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400 font-medium">#{{ $material->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-800 search-target">{{ $material->material_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium search-target">{{ $material->branch->nama_cabang ?? 'Global' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium">{{ $material->fixed_size ? $material->fixed_size . 'm' : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">Rp {{ number_format($material->purchase_price, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 font-bold">Rp {{ number_format($material->retail_price, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($material->stock_qty > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        {{ $material->stock_qty }} left
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-100">
                                        Out of stock
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-400">Belum ada data barang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 3. Data Supplier -->
        <div id="view-supplier" class="overflow-x-auto tab-view hidden">
            <table class="min-w-full divide-y divide-slate-200 search-table">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">ID Supplier</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Nama Supplier</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Kontak</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Alamat</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Tanggal Bergabung</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-slate-50/30 transition duration-150 search-row">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400 font-medium">SUP-{{ str_pad($supplier->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-800 search-target">{{ $supplier->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $supplier->kontak ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $supplier->alamat ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium">{{ $supplier->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-400">Belum ada data supplier.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
    let activeTabId = 'history';

    function switchMainTab(tab) {
        activeTabId = tab;
        document.querySelectorAll('.tab-view').forEach(el => el.classList.add('hidden'));
        
        const activeClass = "px-5 py-2 font-semibold text-xs rounded-lg transition duration-200 bg-white text-slate-800 shadow-sm whitespace-nowrap".split(" ");
        const inactiveClass = "px-5 py-2 font-semibold text-xs rounded-lg transition duration-200 text-slate-500 hover:text-slate-800 whitespace-nowrap".split(" ");
        
        ['history', 'inventory', 'supplier'].forEach(t => {
            const btn = document.getElementById('tab-' + t);
            if (!btn) return;
            btn.className = '';
            if (t === tab) {
                btn.classList.add(...activeClass);
                document.getElementById('view-' + t).classList.remove('hidden');
                document.getElementById('view-' + t).classList.add('animate-fade-in');
            } else {
                btn.classList.add(...inactiveClass);
            }
        });

        filterActiveTable();
    }

    function filterActiveTable() {
        const input = document.getElementById('global-search').value.toLowerCase();
        const activeView = document.getElementById('view-' + activeTabId);
        if(!activeView) return;

        const rows = activeView.querySelectorAll('.search-row');
        rows.forEach(row => {
            const targets = row.querySelectorAll('.search-target');
            let matched = false;
            
            targets.forEach(target => {
                if (target.innerText.toLowerCase().includes(input)) matched = true;
            });

            if(targets.length === 0) {
                if (row.innerText.toLowerCase().includes(input)) matched = true;
            }

            if (matched || input === '') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function printPO(poNum, supplierName, itemName, qty, totalCost, poDate, staffName = 'Staf Purchasing', staffSig = '', managerName = 'Manajer Toko', managerSig = '', approvedAt = '') {
        const printWindow = window.open('', '_blank');
        const staffSigHtml = staffSig ? `<img src="${staffSig}" style="max-height: 60px; max-width: 140px; margin: 0 auto 5px auto; display: block;">` : `<div style="height: 50px; line-height: 50px; font-style: italic; color: #94a3b8; font-size: 11px;">[ Tanda Tangan Digital ]</div>`;
        const managerSigHtml = managerSig ? `<img src="${managerSig}" style="max-height: 60px; max-width: 140px; margin: 0 auto 5px auto; display: block;">` : (approvedAt ? `<div style="border: 2px dashed #10b981; padding: 4px; color: #047857; font-size: 10px; font-weight: bold; border-radius: 6px; margin-bottom: 5px;">✓ VERIFIED DIGITAL STAMP<br><small style="font-weight:normal;">${approvedAt}</small></div>` : `<div style="height: 50px; line-height: 50px; font-style: italic; color: #94a3b8; font-size: 11px;">[ Menunggu ACC Manager ]</div>`);

        printWindow.document.write(`
            <html>
            <head>
                <title>Purchase Order - ${poNum}</title>
                <style>
                    body { font-family: 'Helvetica Neue', Arial, sans-serif; padding: 40px; color: #1e293b; }
                    .header { display: flex; justify-content: space-between; border-b: 2px solid #6366f1; padding-bottom: 20px; margin-bottom: 30px; }
                    .brand { font-size: 24px; font-weight: bold; color: #4338ca; }
                    .title { font-size: 20px; font-weight: bold; text-align: right; }
                    .info-table { width: 100%; margin-bottom: 30px; }
                    .info-table td { padding: 6px 0; font-size: 14px; }
                    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                    .items-table th, .items-table td { border: 1px solid #cbd5e1; padding: 12px; font-size: 14px; text-align: left; }
                    .items-table th { background: #f8fafc; }
                    .total { text-align: right; font-size: 18px; font-weight: bold; color: #4338ca; }
                    .footer { margin-top: 50px; display: flex; justify-content: space-between; }
                    .sig { text-align: center; width: 220px; border-top: 1px solid #94a3b8; padding-top: 10px; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="brand">SnapPrint ERP</div>
                    <div class="title">PURCHASE ORDER (PO)<br><small style="font-size:12px; font-weight:normal; color:#64748b;">Standar SAP ERP Management</small></div>
                </div>

                <table class="info-table">
                    <tr>
                        <td><strong>No. PO:</strong> ${poNum}</td>
                        <td style="text-align:right;"><strong>Tanggal Order:</strong> ${poDate}</td>
                    </tr>
                    <tr>
                        <td><strong>Supplier:</strong> ${supplierName}</td>
                        <td style="text-align:right;"><strong>Status:</strong> ${approvedAt ? 'Resmi Terbit & Disetujui' : 'Draft / Menunggu ACC'}</td>
                    </tr>
                </table>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Barang / Material</th>
                            <th>Kuantitas</th>
                            <th>Total Nilai PO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>${itemName}</td>
                            <td>${qty} Unit</td>
                            <td>Rp ${totalCost}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="total">Total Pengadaan: Rp ${totalCost}</div>

                <div class="footer">
                    <div class="sig">
                        ${staffSigHtml}
                        <strong>( ${staffName} )</strong><br>
                        <small style="color: #64748b;">Staf Purchasing</small>
                    </div>
                    <div class="sig">
                        ${managerSigHtml}
                        <strong>( ${managerName} )</strong><br>
                        <small style="color: #64748b;">Manajer Toko</small>
                    </div>
                </div>

                <script>
                    window.onload = function() { window.print(); }
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
</script>
@endsection
