@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-receipt text-cyan-600"></i>
                <span>Riwayat Transaksi Penjualan (POS)</span>
            </h2>
            <p class="text-sm text-slate-500 mb-0">Log transaksi penjualan harian dan invoice kasir seluruh cabang</p>
        </div>
        
        <div class="flex items-center gap-3 flex-wrap">
            <div class="relative w-64">
                <input type="text" class="table-search-input form-control w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Cari invoice, kasir, cabang...">
            </div>

            <!-- 1-Click Excel Export Button -->
            <button type="button" onclick="exportTableToExcel('sales-table', 'Log_Penjualan_POS_SnapPrint')" class="btn btn-sm btn-outline-success rounded-pill px-3 font-semibold d-inline-flex align-items-center">
                <i class="fa-solid fa-file-excel me-1.5 text-emerald-600"></i> Ekspor Excel
            </button>

            @if(auth()->user()->isOwner())
            <form action="{{ route('sales.index') }}" method="GET" class="hidden sm:block">
                <select name="branch_id" onchange="this.form.submit()" class="bg-white border border-slate-200 text-slate-700 text-xs rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block py-2 px-3 shadow-sm">
                    <option value="all" {{ request('branch_id') == 'all' ? 'selected' : '' }}>Semua Cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->nama_cabang }} {{ $branch->trashed() ? '(Archived)' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
            @endif
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 mb-0" id="sales-table">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">Waktu Transaksi</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">No. Invoice</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">Cabang</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">Kasir</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">Total Harga</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase sortable">Metode</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($transactions as $trx)
                        <tr class="hover:bg-slate-50/30 transition duration-150 search-row">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium">{{ $trx->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-800">
                                <a href="{{ route('sales.receipt', $trx->id) }}" class="text-indigo-600 hover:text-indigo-800 underline font-mono">
                                    {{ $trx->invoice_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $trx->branch->nama_cabang ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $trx->user->username ?? $trx->user->name ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-emerald-600">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                <span class="badge bg-slate-100 text-slate-700 border px-2.5 py-1">{{ $trx->payment_method }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Print -->
                                    <a href="{{ route('sales.receipt', $trx->id) }}" class="btn btn-sm btn-outline-indigo rounded-pill px-3 d-inline-flex align-items-center" title="Lihat & Cetak Nota">
                                        <i class="fa-solid fa-print me-1"></i> Cetak
                                    </a>
                                    @if(auth()->user()->isOwner())
                                        <form action="{{ route('sales.refund', $trx->id) }}" method="POST" onsubmit="return confirm('Refund invoice ini? Stok akan dikembalikan dan transaksi kas akan dibatalkan.');">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger rounded-pill px-2.5 d-inline-flex align-items-center" type="submit" title="Refund Invoice">
                                                <i class="fa-solid fa-rotate-left me-1"></i> Refund
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-400">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
