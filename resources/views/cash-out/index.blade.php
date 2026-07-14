@extends('layouts.app')

@section('title', 'Kas Keluar')
@section('page-title', 'Data Kas Keluar')

@section('content')

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- Header / Filter -->
    <div class="p-6 border-b border-gray-100">
        <form method="GET" action="{{ route('kas-keluar.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari referensi/keterangan..." 
                    class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm">
            </div>

            <div>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="Mulai Tanggal">
            </div>
            <div>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="Sampai Tanggal">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-gray-800 rounded-lg hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Filter
                </button>
                <a href="{{ route('kas-keluar.create') }}" class="flex-1 text-center px-4 py-2 text-sm font-medium text-white bg-rose-600 rounded-lg hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 truncate">
                    + Tambah
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider font-semibold">
                    <th class="px-6 py-4">Tgl & Ref</th>
                    <th class="px-6 py-4">Akun Beban</th>
                    <th class="px-6 py-4">Keterangan</th>
                    <th class="px-6 py-4 text-right">Jumlah (Rp)</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($cashTransactions as $trx)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($trx->tanggal)->translatedFormat('d M Y') }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $trx->nomor_referensi }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $trx->account->nama_akun }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $trx->branch->nama_cabang }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                            {{ $trx->keterangan }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="font-bold text-rose-600">
                                {{ number_format($trx->jumlah, 0, ',', '.') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <form method="POST" action="{{ route('kas-keluar.destroy', $trx->id) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center p-2 text-rose-600 bg-rose-50 rounded-lg hover:bg-rose-100 transition-colors" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            Tidak ada transaksi kas keluar yang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($cashTransactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $cashTransactions->links() }}
        </div>
    @endif
</div>

@endsection
