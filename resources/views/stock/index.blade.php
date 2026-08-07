@extends('layouts.app')

@section('title', 'Manajemen Stok')
@section('page-title', 'Manajemen Stok & Verifikasi Penerimaan')

@section('content')
<div x-data="{ 
    activeTab: 'inventory',
    editOpen: false, 
    editMaterial: { id: '', name: '', stock_qty: 0, purchase_price: 0, retail_price: 0 },
    verifyOpen: false,
    verifyPurchase: { id: '', material_name: '', qty_bought: 0, supplier_name: '', total_cost: 0 },
    rejectOpen: false,
    rejectPurchase: { id: '', material_name: '' }
}" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Manajemen Stok & Gudang</h2>
            <p class="text-sm text-slate-500">Kelola inventaris bahan baku, opname stok, serta verifikasi penerimaan barang dari tim Purchasing.</p>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Jenis Bahan -->
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

        <!-- Card 2: Total Unit Stok -->
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

        <!-- Card 3: Menunggu Verifikasi (Pending GRN) -->
        <div @click="activeTab = 'verification'" class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4 cursor-pointer hover:border-indigo-300 transition">
            <div class="h-12 w-12 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600 font-bold relative">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                @if($pendingCount > 0)
                    <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white animate-pulse">{{ $pendingCount }}</span>
                @endif
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Menunggu Verifikasi</p>
                <h3 class="text-2xl font-bold text-purple-700 mt-0.5">{{ number_format($pendingCount) }} <span class="text-xs font-normal text-slate-500">Pengadaan</span></h3>
            </div>
        </div>

        <!-- Card 4: Stok Menipis -->
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

    <!-- TABS BAR -->
    <div class="border-b border-slate-200 flex space-x-8">
        <button @click="activeTab = 'inventory'" :class="activeTab === 'inventory' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 text-sm font-semibold border-b-2 transition flex items-center gap-2">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            Inventaris & Opname Stok
        </button>

        <button @click="activeTab = 'verification'" :class="activeTab === 'verification' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 text-sm font-semibold border-b-2 transition flex items-center gap-2 relative">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Verifikasi Penerimaan Barang
            @if($pendingCount > 0)
                <span class="ml-1 inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-700">
                    {{ $pendingCount }}
                </span>
            @endif
        </button>
    </div>

    <!-- ==================== TAB 1: INVENTARIS & OPNAME ==================== -->
    <div x-show="activeTab === 'inventory'" class="space-y-6">
        <!-- Filter & Search Section -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200/60 flex flex-col sm:flex-row justify-between items-center gap-4">
            <form method="GET" action="{{ route('stock.index') }}" class="w-full flex flex-col sm:flex-row items-center gap-3">
                <div class="relative w-full sm:w-80">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama bahan baku..." class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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

                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
                    Filter
                </button>
            </form>
        </div>

        <!-- Inventory Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
            <div class="overflow-x-auto">
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
                </table>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 2: VERIFIKASI PENERIMAAN BARANG ==================== -->
    <div x-show="activeTab === 'verification'" class="space-y-6" x-cloak>
        <div class="bg-indigo-50/60 border border-indigo-100 p-4 rounded-2xl flex items-start gap-3">
            <svg class="h-6 w-6 text-indigo-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-xs text-indigo-900 leading-relaxed">
                <span class="font-bold">Alur Pemeriksaan Fisik Gudang:</span> Pengadaan barang yang di-input oleh tim Purchasing tidak akan langsung menambah stok di sistem secara otomatis. Anda sebagai Manajer harus melakukan **inspeksi fisik fisik barang** yang tiba di cabang, lalu mengklik tombol **Verifikasi & Terima Stok** di bawah ini.
            </p>
        </div>

        <!-- Pending Verification Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-slate-900 text-base">Daftar Pengadaan Menunggu Verifikasi Manajer</h3>
                <span class="bg-purple-100 text-purple-700 font-bold text-xs px-2.5 py-1 rounded-full">{{ $pendingCount }} Transaksi</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-500 text-xs uppercase tracking-wider font-semibold">
                            <th class="px-6 py-4">Tanggal Pembelian</th>
                            <th class="px-6 py-4">Bahan Baku / Barang</th>
                            <th class="px-6 py-4">Supplier</th>
                            <th class="px-6 py-4">Qty Dibeli</th>
                            <th class="px-6 py-4">Total Biaya</th>
                            <th class="px-6 py-4">Purchasing Staff</th>
                            <th class="px-6 py-4 text-center">Status System</th>
                            <th class="px-6 py-4 text-center">Aksi Manajer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-150 text-slate-700">
                        @forelse($pendingPurchases as $purchase)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-slate-500">{{ $purchase->created_at->format('d M Y, H:i') }}</td>
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    {{ $purchase->material->material_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ $purchase->supplier->name ?? 'Tanpa Supplier' }}
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-indigo-700">
                                    {{ number_format($purchase->qty_bought) }} Unit
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">
                                    Rp {{ number_format($purchase->total_cost, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ $purchase->user->username ?? 'System' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                        Menunggu Cek Fisik
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center items-center gap-2">
                                        <button @click="
                                            verifyPurchase = {
                                                id: '{{ $purchase->id }}',
                                                material_name: '{{ addslashes($purchase->material->material_name ?? '') }}',
                                                qty_bought: '{{ $purchase->qty_bought }}',
                                                supplier_name: '{{ addslashes($purchase->supplier->name ?? '-') }}',
                                                total_cost: '{{ $purchase->total_cost }}'
                                            };
                                            verifyOpen = true;
                                        " class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold text-xs transition shadow-sm">
                                            ✓ Verifikasi & Terima
                                        </button>

                                        <button @click="
                                            rejectPurchase = {
                                                id: '{{ $purchase->id }}',
                                                material_name: '{{ addslashes($purchase->material->material_name ?? '') }}'
                                            };
                                            rejectOpen = true;
                                        " class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg font-semibold text-xs transition">
                                            ✕ Tolak / Retur
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-slate-400 text-sm">
                                    Tidak ada pengadaan barang yang menunggu verifikasi saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- History Verification Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden mt-6">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="font-bold text-slate-900 text-base">Riwayat Verifikasi Penerimaan Barang Terakhir</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-500 text-xs uppercase tracking-wider font-semibold">
                            <th class="px-6 py-4">Waktu Verifikasi</th>
                            <th class="px-6 py-4">Bahan Baku</th>
                            <th class="px-6 py-4">Qty</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Verifikator</th>
                            <th class="px-6 py-4">Catatan Opname Fisik</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-150 text-slate-700">
                        @forelse($historyPurchases as $history)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-slate-500">{{ $history->verified_at ? \Carbon\Carbon::parse($history->verified_at)->format('d M Y, H:i') : '-' }}</td>
                                <td class="px-6 py-4 font-semibold text-slate-900">{{ $history->material->material_name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-800">{{ number_format($history->qty_bought) }} Unit</td>
                                <td class="px-6 py-4">
                                    @if($history->status === 'received')
                                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Diterima & Masuk Stok</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">Ditolak / Retur</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $history->verifiedBy->username ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600 italic">{{ $history->verification_notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-400 text-sm">Belum ada riwayat verifikasi penerimaan barang.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Opname / Edit Stok -->
    <div x-show="editOpen" class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="editOpen = false"></div>
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg z-10">
            <form :action="'/stock/' + editMaterial.id" method="POST">
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

    <!-- Modal Verifikasi & Terima Stok -->
    <div x-show="verifyOpen" class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="verifyOpen = false"></div>
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg z-10">
            <form :action="'/stock/purchases/' + verifyPurchase.id + '/verify'" method="POST">
                @csrf
                <div class="bg-white px-6 pb-6 pt-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Verifikasi & Terima Stok Fisik</h3>
                        <button type="button" @click="verifyOpen = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-100 rounded-xl space-y-1">
                        <p class="text-xs text-emerald-700 font-semibold uppercase tracking-wider">Barang Pengadaan</p>
                        <p class="text-base font-bold text-emerald-950" x-text="verifyPurchase.material_name"></p>
                        <p class="text-xs text-emerald-800">Supplier: <span class="font-semibold" x-text="verifyPurchase.supplier_name"></span></p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Jumlah Barang Diterima (Fisik) <span class="text-rose-500">*</span></label>
                            <input type="number" name="qty_received" min="1" required :value="verifyPurchase.qty_bought" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-base font-bold text-slate-900">
                            <p class="mt-1 text-[11px] text-slate-400">Default sesuai order Purchasing (<span x-text="verifyPurchase.qty_bought"></span> Unit). Sesuaikan jika ada selisih.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Catatan Inspeksi Fisik</label>
                            <textarea name="verification_notes" rows="2" placeholder="Contoh: Barang fisik sesuai, segel aman, siap masuk stok..." class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 rounded-b-2xl">
                    <button type="submit" class="inline-flex justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition">✓ Terima & Tambahkan ke Stok</button>
                    <button type="button" @click="verifyOpen = false" class="inline-flex justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tolak / Retur -->
    <div x-show="rejectOpen" class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="rejectOpen = false"></div>
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg z-10">
            <form :action="'/stock/purchases/' + rejectPurchase.id + '/reject'" method="POST">
                @csrf
                <div class="bg-white px-6 pb-6 pt-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-rose-900">Tolak & Retur Pengadaan Barang</h3>
                        <button type="button" @click="rejectOpen = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Alasan Penolakan / Retur <span class="text-rose-500">*</span></label>
                            <textarea name="verification_notes" rows="3" required placeholder="Contoh: Fisik barang cacat/rusak saat pengiriman, tidak sesuai pesanan..." class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 rounded-b-2xl">
                    <button type="submit" class="inline-flex justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 transition">Tolak / Retur</button>
                    <button type="button" @click="rejectOpen = false" class="inline-flex justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">Batal</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
