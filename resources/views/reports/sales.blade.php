@extends('layouts.app')

@section('title', 'Laporan Penjualan')
@section('page-title', 'Laporan Analisa Penjualan')

@section('content')

<!-- Filter -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
    <form method="GET" action="{{ route('reports.sales') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Periode Group</label>
            <select name="period" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm">
                <option value="daily" {{ request('period', 'daily') == 'daily' ? 'selected' : '' }}>Harian</option>
                <option value="monthly" {{ request('period') == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                <option value="yearly" {{ request('period') == 'yearly' ? 'selected' : '' }}>Tahunan</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
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
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }} {{ $branch->trashed() ? '(Archived)' : '' }}</option>
                @endforeach
            </select>
        </div>
        @else
        <div class="hidden"></div>
        @endif
        
        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                Filter
            </button>
        </div>
    </form>
</div>

@if(count($salesData) > 0)
    <!-- Chart -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <h3 class="text-lg font-bold text-gray-900 mb-6">Grafik Tren Penjualan</h3>
        <div class="relative h-72 w-full">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
@endif

<!-- Table -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 bg-gray-50/30 flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-900">Data Penjualan</h3>
        <div class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
            Total: Rp {{ number_format($salesData->sum('total_penjualan'), 0, ',', '.') }}
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider font-semibold">
                    <th class="px-6 py-4">Periode ({{ ucfirst($period) }})</th>
                    <th class="px-6 py-4 text-center">Jml Transaksi</th>
                    @if($period === 'monthly')
                    <th class="px-6 py-4 text-right">Rata-rata Harian (Rp)</th>
                    @endif
                    <th class="px-6 py-4 text-right">Total Penjualan (Rp)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($salesData as $data)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $data->label }}</td>
                        <td class="px-6 py-4 text-center text-gray-600">{{ $data->jumlah_transaksi }}</td>
                        @if($period === 'monthly')
                        <td class="px-6 py-4 text-right text-gray-600">{{ number_format($data->rata_rata_harian, 0, ',', '.') }}</td>
                        @endif
                        <td class="px-6 py-4 text-right font-bold text-green-600">
                            {{ number_format($data->total_penjualan, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $period === 'monthly' ? 4 : 3 }}" class="px-6 py-8 text-center text-gray-500">
                            Tidak ada data penjualan pada periode yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(count($salesData) > 0)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartData->labels) !!},
                datasets: [{
                    label: 'Total Penjualan (Rp)',
                    data: {!! json_encode($chartData->values) !!},
                    borderColor: '#10b981', // emerald-500
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [2, 4], color: '#f3f4f6' },
                        ticks: {
                            callback: function(value, index, values) {
                                return 'Rp ' + (value / 1000000) + 'Jt';
                            }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    });
</script>
@endif

@endsection
