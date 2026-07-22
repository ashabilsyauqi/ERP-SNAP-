@extends('layouts.app')

@section('title', 'Laporan Kas Keluar')
@section('page-title', 'Laporan Kas Keluar')

@section('content')

<!-- Filter -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
    <form method="GET" action="{{ route('reports.cash-out') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mulai Tanggal</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        
        @if(Auth::user()->role === 'owner')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cabang</label>
            <select name="branch_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm">
                <option value="">Semua Cabang</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                @endforeach
            </select>
        </div>
        @endif
        

        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                Filter
            </button>
            <a href="{{ route('reports.cash-out') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none">
                Reset
            </a>
        </div>
    </form>
</div>

<!-- Mutasi Table -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 bg-gray-50/30 flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-900">Detail Kas Keluar</h3>
        @if(request('start_date'))
        <div class="text-sm">
            <span class="text-gray-500">Saldo Awal (sebelum {{ date('d M Y', strtotime(request('start_date'))) }}): </span>
            <span class="font-bold text-gray-900">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</span>
        </div>
        @endif
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider font-semibold">
                    <th class="px-6 py-4">Tgl & Ref</th>
                    <th class="px-6 py-4">Keterangan & Akun</th>
                    <th class="px-6 py-4 text-right">Masuk (Rp)</th>
                    <th class="px-6 py-4 text-right">Keluar (Rp)</th>
                    <th class="px-6 py-4 text-right bg-blue-50/30">Saldo Berjalan (Rp)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($mutations as $mut)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($mut->tanggal)->translatedFormat('d M Y') }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $mut->nomor_referensi }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 max-w-xs truncate" title="{{ $mut->keterangan }}">{{ $mut->keterangan }}</div>
                            <div class="text-xs text-primary-600 font-medium mt-1">{{ $mut->account->nama_akun }} • {{ $mut->branch->nama_cabang }}</div>
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-green-600">
                            {{ $mut->tipe === 'masuk' ? number_format($mut->jumlah, 0, ',', '.') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-rose-600">
                            {{ $mut->tipe === 'keluar' ? number_format($mut->jumlah, 0, ',', '.') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900 bg-blue-50/10">
                            {{ number_format($mut->running_balance, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            Tidak ada data mutasi pada periode yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($mutations->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $mutations->links() }}
        </div>
    @endif
</div>

@endsection
