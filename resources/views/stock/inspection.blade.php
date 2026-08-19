@extends('layouts.app')

@section('title', 'Pemeriksaan Barang Masuk')
@section('page-title', 'Pemeriksaan & Inspeksi Barang Masuk')

@section('content')
<div x-data="{ 
    verifyOpen: false,
    verifyPurchase: { id: '', po_number: '', material_name: '', qty_bought: 0, supplier_name: '', total_cost: 0 },
    rejectOpen: false,
    rejectPurchase: { id: '', po_number: '', material_name: '' }
}" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Pemeriksaan Barang Masuk (Goods Receipt Verification)</h2>
            <p class="text-sm text-slate-500">Lakukan inspeksi fisik barang dari Purchasing sebelum barang resmi dimasukkan ke dalam stok toko.</p>
        </div>
    </div>

    <!-- Info Banner -->
    <div class="bg-indigo-50/70 border border-indigo-100 p-4 rounded-2xl flex items-start gap-3">
        <svg class="h-6 w-6 text-indigo-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-xs text-indigo-900 leading-relaxed">
            <span class="font-bold">Standar Inspeksi Fisik:</span> Seluruh pengadaan yang dilakukan oleh staf Purchasing wajib melalui tahap verifikasi Manajer di halaman ini. Klik tombol <span class="font-bold text-emerald-700">✓ Verifikasi & Terima</span> setelah memastikan fisik barang di gudang sesuai, atau klik <span class="font-bold text-rose-700">✕ Tolak / Retur</span> jika fisik barang cacat/rusak.
        </p>
    </div>

    <!-- Filter & Search Section -->
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200/60 flex flex-col sm:flex-row justify-between items-center gap-4">
        <form method="GET" action="{{ route('stock.inspection') }}" class="w-full flex flex-col sm:flex-row items-center gap-3">
            <div class="relative w-full sm:w-80">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. PO, Supplier, Barang..." class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                <svg class="h-5 w-5 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            @if(auth()->user()->isOwner())
                <select name="branch_id" onchange="this.form.submit()" class="w-full sm:w-60 px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="all" {{ request('branch_id') == 'all' ? 'selected' : '' }}>Semua Cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                    @endforeach
                </select>
            @endif

            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium text-sm transition">Filter</button>
        </form>
    </div>

    <!-- Table Pending Inspection -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-slate-900 text-base">Daftar Barang Menunggu Verifikasi Manajer</h3>
            <span class="bg-amber-100 text-amber-800 font-bold text-xs px-3 py-1 rounded-full">{{ number_format($pendingCount) }} Transaksi</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-500 text-xs uppercase tracking-wider font-semibold">
                        <th class="px-6 py-4">No. PO & Tanggal</th>
                        <th class="px-6 py-4">No. Faktur Supplier</th>
                        <th class="px-6 py-4">Barang / Material</th>
                        <th class="px-6 py-4">Supplier</th>
                        <th class="px-6 py-4">Qty Order</th>
                        <th class="px-6 py-4">Total Biaya</th>
                        <th class="px-6 py-4">Pemesan (Purchasing)</th>
                        <th class="px-6 py-4 text-center">Inspeksi & Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-150 text-slate-700">
                    @forelse($pendingPurchases as $purchase)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                <div class="font-bold text-indigo-700">{{ $purchase->po_number ?? ('PO-'.str_pad($purchase->id, 6, '0', STR_PAD_LEFT)) }}</div>
                                <div class="text-xs text-slate-400">{{ $purchase->created_at->format('d M Y, H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-700">
                                {{ $purchase->vendor_ref ?? '-' }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                {{ $purchase->material->material_name ?? 'N/A' }}
                                @if($purchase->material && $purchase->material->fixed_size)
                                    <span class="text-xs text-slate-500 font-normal">({{ $purchase->material->fixed_size }}m)</span>
                                @endif
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
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <div class="flex justify-center items-center gap-2">
                                    <button @click="
                                        verifyPurchase = {
                                            id: '{{ $purchase->id }}',
                                            po_number: '{{ $purchase->po_number }}',
                                            material_name: '{{ addslashes($purchase->material->material_name ?? '') }}',
                                            qty_bought: '{{ $purchase->qty_bought }}',
                                            supplier_name: '{{ addslashes($purchase->supplier->name ?? '-') }}',
                                            total_cost: '{{ number_format($purchase->total_cost, 0, ',', '.') }}'
                                        };
                                        verifyOpen = true;
                                    " class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold text-xs transition shadow-sm flex items-center gap-1">
                                        ✓ Verifikasi & Terima
                                    </button>

                                    <button @click="
                                        rejectPurchase = {
                                            id: '{{ $purchase->id }}',
                                            po_number: '{{ $purchase->po_number }}',
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
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400 text-sm">
                                Tidak ada pengadaan barang yang menunggu verifikasi saat ini. Seluruh fisik barang di gudang telah terverifikasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
                        <p class="text-xs text-emerald-700 font-semibold uppercase tracking-wider">No. PO: <span x-text="verifyPurchase.po_number"></span></p>
                        <p class="text-base font-bold text-emerald-950" x-text="verifyPurchase.material_name"></p>
                        <p class="text-xs text-emerald-800">Supplier: <span class="font-semibold" x-text="verifyPurchase.supplier_name"></span> | Total PO: Rp <span x-text="verifyPurchase.total_cost"></span></p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Jumlah Fisik Diterima <span class="text-rose-500">*</span></label>
                            <input type="number" name="qty_received" min="1" required :value="verifyPurchase.qty_bought" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-base font-bold text-slate-900">
                            <p class="mt-1 text-[11px] text-slate-400">Default sesuai PO Purchasing (<span x-text="verifyPurchase.qty_bought"></span> Unit). Sesuaikan jika ada selisih.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Catatan Opname Fisik</label>
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
                    <button type="submit" class="inline-flex justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 transition">Tolak & Record Retur</button>
                    <button type="button" @click="rejectOpen = false" class="inline-flex justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">Batal</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
