@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Riwayat Penjualan</h2>
            <p class="text-sm text-slate-500">Log transaksi POS</p>
        </div>
        @if(auth()->user()->isOwner())
        <div class="flex gap-4">
            <form action="{{ route('sales.index') }}" method="GET" class="hidden sm:block">
                <select name="branch_id" onchange="this.form.submit()" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block w-full py-2.5 px-3">
                    <option value="all" {{ request('branch_id') == 'all' ? 'selected' : '' }}>Semua Cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->nama_cabang }} {{ $branch->trashed() ? '(Archived)' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        @endif
    </div>

    <!-- Main Content Container -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Waktu</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Invoice</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Cabang</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Kasir</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Total</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Metode</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($transactions as $trx)
                        <tr class="hover:bg-slate-50/30 transition duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium">{{ $trx->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-800">
                                <a href="{{ route('sales.receipt', $trx->id) }}" class="text-indigo-600 hover:text-indigo-800 underline">
                                    {{ $trx->invoice_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $trx->branch->nama_cabang ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $trx->user->name ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-emerald-600">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $trx->payment_method }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex gap-2">
                                <!-- Print -->
                                <a href="{{ route('sales.receipt', $trx->id) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg inline-block text-center">Print</a>
                                @if(auth()->user()->isOwner())
                                    <form action="{{ route('sales.refund', $trx->id) }}" method="POST" onsubmit="return confirm('Refund invoice ini? Stok akan dikembalikan dan transaksi kas akan dihapus/dibatalkan.');">
                                        @csrf
                                        <button class="text-rose-600 hover:text-rose-900 bg-rose-50 p-2 rounded-lg" type="submit">Refund</button>
                                    </form>
                                @endif
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
