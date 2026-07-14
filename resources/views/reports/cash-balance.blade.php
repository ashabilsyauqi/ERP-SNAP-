@extends('layouts.app')

@section('title', 'Laporan Saldo Kas')
@section('page-title', 'Laporan Saldo Kas')

@section('content')

<!-- Filter -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
    <form method="GET" action="{{ route('reports.cash-balance') }}" class="flex flex-col md:flex-row gap-4 items-end">
        <div class="w-full md:w-1/3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        <div class="w-full md:w-1/3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        <div class="w-full md:w-auto flex gap-2">
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                Tampilkan
            </button>
            <a href="{{ route('reports.cash-balance') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none">
                Reset
            </a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-green-100 flex items-center">
        <div class="p-3 bg-green-50 text-green-600 rounded-xl mr-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Total Pemasukan</p>
            <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</p>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-rose-100 flex items-center">
        <div class="p-3 bg-rose-50 text-rose-600 rounded-xl mr-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Total Pengeluaran</p>
            <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="bg-gradient-to-br from-primary-600 to-primary-800 rounded-2xl p-6 shadow-md text-white flex items-center">
        <div class="p-3 bg-white/20 rounded-xl mr-4 backdrop-blur-sm">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-primary-100 mb-1">Saldo Akhir</p>
            <p class="text-2xl font-bold">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
        </div>
    </div>
</div>

<!-- Table Cabang (Admin Only) -->
@if(Auth::user()->role === 'owner' && count($perBranch) > 0)
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 bg-gray-50/30">
        <h3 class="text-lg font-bold text-gray-900">Rincian Saldo per Cabang</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider font-semibold">
                    <th class="px-6 py-4">Nama Cabang</th>
                    <th class="px-6 py-4 text-right">Pemasukan (Rp)</th>
                    <th class="px-6 py-4 text-right">Pengeluaran (Rp)</th>
                    <th class="px-6 py-4 text-right">Saldo (Rp)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($perBranch as $branchData)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $branchData->nama_cabang }}</td>
                        <td class="px-6 py-4 text-right text-green-600">{{ number_format($branchData->masuk, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-rose-600">{{ number_format($branchData->keluar, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900">{{ number_format($branchData->saldo, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
