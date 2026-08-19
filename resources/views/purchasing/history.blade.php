@extends('layouts.app')

@section('title', 'Riwayat Pembelian & Log Record')
@section('page-title', 'Riwayat Pembelian (Purchase Order Logs)')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Riwayat Pembelian & Log Record SAP</h2>
            <p class="text-sm text-slate-500">Daftar rekap log penerbitan Purchase Order (PO), verifikasi penerimaan gudang, dan rincian transaksi pengadaan.</p>
        </div>
        <div class="flex items-center gap-4">
            @if(auth()->user()->isOwner())
            <form action="{{ route('purchasing.history') }}" method="GET" class="hidden sm:block">
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
                Buat PO Baru
            </a>
        </div>
    </div>

    <!-- KPI Summary Metrics Cards -->
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
        
        <!-- SAP Server-side Filter Toolbar -->
        <div class="p-5 border-b border-slate-200/80 bg-white">
            <form method="GET" action="{{ route('purchasing.history') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Mulai Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Filter Supplier</label>
                    <select name="supplier_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white">
                        <option value="all">Semua Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Status Verifikasi</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white">
                        <option value="all">Semua Status</option>
                        <option value="pending_verification" {{ request('status') == 'pending_verification' ? 'selected' : '' }}>Menunggu Cek Gudang</option>
                        <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Diterima & Sesuai</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak / Retur</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. PO, Ref..." class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-xl font-medium text-xs transition shadow-sm">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Table Log Records -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/70">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">No. PO (SAP Doc)</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">No. Faktur Supplier</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Waktu PO & GR</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Supplier & Cabang</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Bahan Baku / Produk</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Qty & Satuan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Harga Satuan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Total Biaya PO</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Pemesan (Purchasing)</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase">Status Cek Gudang</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase">Aksi / Cetak</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($purchases as $purchase)
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <!-- No. PO -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-700">
                                {{ $purchase->po_number ?? ('PO-'.str_pad($purchase->id, 6, '0', STR_PAD_LEFT)) }}
                            </td>
                            <!-- No. Faktur Supplier -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-700">
                                {{ $purchase->vendor_ref ?? '-' }}
                            </td>
                            <!-- Waktu PO & GR -->
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                <div><span class="text-slate-400">Order:</span> {{ $purchase->created_at->format('d M Y, H:i') }}</div>
                                @if($purchase->verified_at)
                                    <div class="text-[11px] text-emerald-700 mt-0.5"><span class="text-slate-400">GR:</span> {{ \Carbon\Carbon::parse($purchase->verified_at)->format('d M Y, H:i') }}</div>
                                @endif
                            </td>
                            <!-- Supplier & Cabang -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-800">
                                @if($purchase->supplier)
                                    <div class="font-semibold text-slate-900">{{ $purchase->supplier->name }}</div>
                                @else
                                    <div class="text-slate-400 italic text-xs">Tanpa Supplier</div>
                                @endif
                                <div class="text-xs text-slate-500">{{ $purchase->branch->nama_cabang ?? 'Global' }}</div>
                            </td>
                            <!-- Barang -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900">
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
                            <!-- Pemesan -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-medium">
                                {{ $purchase->user->username ?? 'System' }}
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
                            <td colspan="11" class="px-6 py-12 text-center text-sm text-slate-400">Belum ada riwayat transaksi pembelian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
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
