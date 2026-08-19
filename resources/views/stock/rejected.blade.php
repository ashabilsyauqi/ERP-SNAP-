@extends('layouts.app')

@section('title', 'Riwayat Retur & Reject')
@section('page-title', 'Riwayat Barang Ditolak & Retur')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Riwayat Retur & Reject (Quality Control Report)</h2>
            <p class="text-sm text-slate-500">Laporan audit pengadaan barang yang ditolak atau dikembalikan ke supplier karena cacat/ketidaksesuaian.</p>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4 max-w-sm">
        <div class="h-12 w-12 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 font-bold">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Item Ditolak / Retur</p>
            <h3 class="text-2xl font-bold text-rose-600 mt-0.5">{{ number_format($rejectedCount) }} <span class="text-xs font-normal text-slate-500">Transaksi</span></h3>
        </div>
    </div>

    <!-- Filter & Search Section -->
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200/60 flex flex-col sm:flex-row justify-between items-center gap-4">
        <form method="GET" action="{{ route('stock.rejected') }}" class="w-full flex flex-col sm:flex-row items-center gap-3">
            <div class="relative w-full sm:w-80">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. PO, Supplier, Barang..." class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500">
                <svg class="h-5 w-5 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            @if(auth()->user()->isOwner())
                <select name="branch_id" onchange="this.form.submit()" class="w-full sm:w-60 px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500 bg-white">
                    <option value="all" {{ request('branch_id') == 'all' ? 'selected' : '' }}>Semua Cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                    @endforeach
                </select>
            @endif

            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium text-sm transition">Filter</button>
        </form>
    </div>

    <!-- Rejected Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-500 text-xs uppercase tracking-wider font-semibold">
                        <th class="px-6 py-4">Waktu Penolakan</th>
                        <th class="px-6 py-4">No. PO & Faktur</th>
                        <th class="px-6 py-4">Barang / Material</th>
                        <th class="px-6 py-4">Supplier</th>
                        <th class="px-6 py-4">Qty Batal</th>
                        <th class="px-6 py-4">Total Biaya PO</th>
                        <th class="px-6 py-4">Verifikator Gudang</th>
                        <th class="px-6 py-4">Alasan Retur / Cacat Fisik</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-150 text-slate-700">
                    @forelse($rejectedPurchases as $rejected)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-slate-500">
                                {{ $rejected->verified_at ? \Carbon\Carbon::parse($rejected->verified_at)->format('d M Y, H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm font-mono">
                                <div class="font-bold text-rose-700">{{ $rejected->po_number ?? 'PO-'.$rejected->id }}</div>
                                <div class="text-xs text-slate-400">Ref: {{ $rejected->vendor_ref ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                {{ $rejected->material->material_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $rejected->supplier->name ?? 'Tanpa Supplier' }}
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-rose-700">
                                {{ number_format($rejected->qty_bought) }} Unit
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-800">
                                Rp {{ number_format($rejected->total_cost, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-700">
                                {{ $rejected->verifiedBy->username ?? 'Manager' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-rose-800 italic bg-rose-50/40 rounded-lg">
                                "{{ $rejected->verification_notes ?? 'Tidak ada alasan khusus.' }}"
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400 text-sm">
                                Tidak ada riwayat barang yang ditolak/diretur saat ini. Seluruh pengadaan berjalan lancar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
