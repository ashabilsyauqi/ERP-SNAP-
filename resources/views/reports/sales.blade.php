@extends('layouts.app')

@section('title', 'Sales Analysis')
@section('page-title', 'Analisis Pendapatan & Penjualan (Sales Analysis)')

@section('action-buttons')
<button type="button" onclick="exportTableToExcel('main-table', 'Sales_Analysis_Report')" class="btn-odoo-secondary">
    <i class="fa-solid fa-file-excel text-emerald-600"></i>
    <span>Export</span>
</button>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Filter Toolbar -->
    <div class="o_form_sheet mb-3 p-3 bg-white">
        <form method="GET" action="{{ route('reports.sales') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Kelompokkan Periode</label>
                <select name="period" class="form-select form-select-sm">
                    <option value="daily" {{ request('period', 'daily') == 'daily' ? 'selected' : '' }}>Harian (Daily)</option>
                    <option value="monthly" {{ request('period') == 'monthly' ? 'selected' : '' }}>Bulanan (Monthly)</option>
                    <option value="yearly" {{ request('period') == 'yearly' ? 'selected' : '' }}>Tahunan (Yearly)</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm">
            </div>
            
            @if(Auth::user()->role === 'owner')
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Cabang</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="all">Semua Cabang (Konsolidasi)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            
            <div class="col-12 col-md-2">
                <button type="submit" class="btn-odoo-primary w-100 py-1 text-xs">
                    <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Chart -->
    @if(count($salesData) > 0)
    <div class="o_form_sheet p-4 bg-white mb-3">
        <h6 class="fw-bold text-slate-800 text-xs uppercase mb-3"><i class="fa-solid fa-chart-line text-blue-600 me-1"></i> Tren Pendapatan Omzet Penjualan</h6>
        <div class="relative h-64 w-full">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
    @endif

    <!-- Data Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover o_list_table mb-0" id="main-table">
                <thead>
                    <tr>
                        <th class="ps-3 sortable">Periode / Tanggal</th>
                        <th class="sortable text-center">Jumlah Pesanan</th>
                        <th class="sortable text-end">Tunai (Cash)</th>
                        <th class="sortable text-end">QRIS</th>
                        <th class="sortable text-end">Transfer Bank</th>
                        <th class="sortable text-end pe-4">Total Omzet (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $grandTotal = 0; 
                        $grandCount = 0; 
                        $grandCash = 0;
                        $grandQris = 0;
                        $grandTransfer = 0;
                    @endphp
                    @forelse($salesData as $data)
                        @php 
                            $grandTotal += $data->total_sales;
                            $grandCount += $data->total_transactions;
                            $grandCash += $data->cash_sales ?? 0;
                            $grandQris += $data->qris_sales ?? 0;
                            $grandTransfer += $data->transfer_sales ?? 0;
                        @endphp
                        <tr class="search-row">
                            <td class="ps-3 fw-bold text-slate-800">{{ $data->period_date }}</td>
                            <td class="text-center font-bold text-slate-700">{{ number_format($data->total_transactions) }} Pesanan</td>
                            <td class="text-end font-mono text-slate-600">Rp {{ number_format($data->cash_sales ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end font-mono text-slate-600">Rp {{ number_format($data->qris_sales ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end font-mono text-slate-600">Rp {{ number_format($data->transfer_sales ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end pe-4 font-mono fw-bold text-blue-900 fs-6">
                                Rp {{ number_format($data->total_sales, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada data penjualan pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($salesData) > 0)
                <tfoot>
                    <tr class="fw-bold bg-slate-100 border-top">
                        <td class="ps-3 text-uppercase">Total Keseluruhan</td>
                        <td class="text-center text-slate-900">{{ number_format($grandCount) }} Pesanan</td>
                        <td class="text-end font-mono text-slate-900">Rp {{ number_format($grandCash, 0, ',', '.') }}</td>
                        <td class="text-end font-mono text-slate-900">Rp {{ number_format($grandQris, 0, ',', '.') }}</td>
                        <td class="text-end font-mono text-slate-900">Rp {{ number_format($grandTransfer, 0, ',', '.') }}</td>
                        <td class="text-end pe-4 font-mono text-blue-900 fs-6">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

@if(count($salesData) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const labels = {!! json_encode($chartData->labels ?? []) !!};
    const data = {!! json_encode($chartData->values ?? []) !!};

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Omzet Penjualan (Rp)',
                data: data,
                borderColor: '#1E3A8A',
                backgroundColor: 'rgba(30, 58, 138, 0.1)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#2563EB',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + Number(value).toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
</script>
@endif
@endsection
