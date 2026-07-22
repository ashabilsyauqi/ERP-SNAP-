@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Overview Keuangan')

@section('content')

<!-- Branch Filter (Owner Only) -->
@if(auth()->user()->isOwner())
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-4">
        <label class="block text-sm font-medium text-gray-700">Cabang:</label>
        <select name="branch_id" onchange="this.form.submit()" class="w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm">
            <option value="all" {{ request('branch_id') == 'all' ? 'selected' : '' }}>Semua Cabang (Konsolidasi)</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                    {{ $branch->nama_cabang }}
                </option>
            @endforeach
        </select>
    </form>
</div>
@endif

<!-- Stats Row -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Penjualan -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
        <div class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 rounded-full bg-emerald-500/10 transition-transform duration-500 group-hover:scale-150"></div>
        <div class="flex items-center justify-between mb-4 relative z-10">
            <h3 class="text-gray-500 text-sm font-medium">Total Penjualan <span class="text-xs font-normal ml-1">(Bulan Ini)</span></h3>
            <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            </div>
        </div>
        <div class="relative z-10">
            <p class="text-3xl font-bold text-gray-900 tracking-tight">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Kas Masuk -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
        <div class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 rounded-full bg-green-500/10 transition-transform duration-500 group-hover:scale-150"></div>
        <div class="flex items-center justify-between mb-4 relative z-10">
            <h3 class="text-gray-500 text-sm font-medium">Total Kas Masuk <span class="text-xs font-normal ml-1">(Bulan Ini)</span></h3>
            <div class="p-2 bg-green-50 text-green-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
            </div>
        </div>
        <div class="relative z-10">
            <p class="text-3xl font-bold text-gray-900 tracking-tight">Rp {{ number_format($totalKasMasuk, 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Kas Keluar -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
        <div class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 rounded-full bg-rose-500/10 transition-transform duration-500 group-hover:scale-150"></div>
        <div class="flex items-center justify-between mb-4 relative z-10">
            <h3 class="text-gray-500 text-sm font-medium">Total Kas Keluar <span class="text-xs font-normal ml-1">(Bulan Ini)</span></h3>
            <div class="p-2 bg-rose-50 text-rose-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
            </div>
        </div>
        <div class="relative z-10">
            <p class="text-3xl font-bold text-gray-900 tracking-tight">Rp {{ number_format($totalKasKeluar, 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Saldo Kas -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
        <div class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 rounded-full bg-blue-500/10 transition-transform duration-500 group-hover:scale-150"></div>
        <div class="flex items-center justify-between mb-4 relative z-10">
            <h3 class="text-gray-500 text-sm font-medium">Saldo Kas <span class="text-xs font-normal ml-1">(Semua)</span></h3>
            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <div class="relative z-10">
            <p class="text-3xl font-bold {{ $saldoKas >= 0 ? 'text-gray-900' : 'text-red-600' }} tracking-tight">
                Rp {{ number_format($saldoKas, 0, ',', '.') }}
            </p>
        </div>
    </div>

</div>

<!-- Content Area -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Recent Transactions Table -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-white">
            <h3 class="text-lg font-bold text-gray-900">Transaksi Kas Terbaru</h3>
            <a href="{{ route('reports.cash-mutation') }}" class="text-sm font-medium text-primary-600 hover:text-primary-800 transition-colors">Lihat Semua &rarr;</a>
        </div>
        
        <div class="overflow-x-auto flex-1">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider font-semibold">
                        <th class="px-6 py-4">Tanggal / Ref</th>
                        <th class="px-6 py-4">Tipe & Akun</th>
                        <th class="px-6 py-4 text-right">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentTransactions as $trx)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($trx->tanggal)->translatedFormat('d M Y') }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $trx->nomor_referensi }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 mb-1">
                                    @if($trx->tipe === 'masuk')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Masuk
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800">
                                            Keluar
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-700 truncate max-w-[200px]">{{ $trx->account->nama_akun }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="font-semibold {{ $trx->tipe === 'masuk' ? 'text-green-600' : 'text-rose-600' }}">
                                    {{ $trx->tipe === 'masuk' ? '+' : '-' }} {{ number_format($trx->jumlah, 0, ',', '.') }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500 text-sm">
                                Belum ada transaksi kas tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info Column -->
    <div class="space-y-6">
        
        <!-- Info Card: POS Transaksi -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 text-white shadow-md relative overflow-hidden">
            <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-white opacity-5 blur-xl"></div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-white/10 rounded-lg backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white">Aktivitas POS (Bulan Ini)</h3>
                </div>
                
                <div class="mb-4">
                    <p class="text-slate-300 text-sm font-medium mb-1">Jumlah Transaksi</p>
                    <p class="text-4xl font-bold tracking-tight">{{ $jumlahTransaksi }}</p>
                </div>
                
                <div>
                    <a href="{{ route('reports.sales') }}" class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-slate-900 bg-white rounded-lg hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-white/50">
                        Lihat Laporan Penjualan
                    </a>
                </div>
            </div>
        </div>

        <!-- Info Card: Cabang -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-center">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-primary-50 text-primary-600 rounded-xl shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <h3 class="text-gray-500 text-sm font-medium mb-1">Cabang Aktif</h3>
                    <p class="text-lg font-bold text-gray-900">{{ Auth::user()->branch->nama_cabang }}</p>
                    <p class="text-sm text-gray-500 mt-2">{{ Auth::user()->branch->alamat }}</p>
                </div>
            </div>
        </div>
        
    </div>
</div>

@endsection
