@extends('layouts.app')

@section('title', 'Laporan Pengeluaran')
@section('page-title', 'Analisa Pengeluaran Operasional')

@section('content')

<!-- Filter -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
    <form method="GET" action="{{ route('reports.expenses') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    
    <!-- Chart -->
    <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-center">
        <h3 class="text-lg font-bold text-gray-900 mb-6 text-center">Komposisi Pengeluaran</h3>
        @if($totalExpenses > 0)
            <div class="relative h-64 w-full">
                <canvas id="expenseChart"></canvas>
            </div>
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500 mb-1">Total Pengeluaran Beban</p>
                <p class="text-2xl font-bold text-rose-600">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</p>
            </div>
        @else
            <div class="h-64 flex items-center justify-center text-gray-400 text-sm">
                Tidak ada data.
            </div>
        @endif
    </div>

    <!-- Table -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50/30 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900">Rincian per Akun Beban</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider font-semibold">
                        <th class="px-6 py-4">Akun Beban</th>
                        <th class="px-6 py-4 text-center">Jml Trx</th>
                        <th class="px-6 py-4">Persentase</th>
                        <th class="px-6 py-4 text-right">Total (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($expenseData as $data)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $data->nama_akun }}</td>
                            <td class="px-6 py-4 text-center text-gray-600">{{ $data->count }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-full bg-gray-200 rounded-full h-2 max-w-[100px]">
                                        <div class="bg-rose-500 h-2 rounded-full" style="width: {{ $data->percentage }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 w-8">{{ $data->percentage }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-rose-600">
                                {{ number_format($data->total, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                Tidak ada data pengeluaran beban.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@if($totalExpenses > 0)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('expenseChart').getContext('2d');
        
        // Generate appealing colors based on count
        const colors = [
            '#f43f5e', '#ef4444', '#f97316', '#f59e0b', '#eab308', 
            '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#64748b'
        ];
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($chartData->labels) !!},
                datasets: [{
                    data: {!! json_encode($chartData->values) !!},
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endif

@endsection
