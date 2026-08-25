@extends('layouts.app')

@section('title', 'Sales Analysis')
@section('page-title', 'Analisis Pendapatan & Penjualan (Sales Analysis)')

@section('action-buttons')
<button type="button" onclick="window.print()" class="btn-odoo-primary" title="Cetak Laporan PDF / Print">
    <i class="fa-solid fa-print"></i>
    <span>Print Laporan</span>
</button>
<button type="button" onclick="exportTableToExcel('main-table', 'Sales_Analysis_Report')" class="btn-odoo-secondary">
    <i class="fa-solid fa-file-excel text-emerald-600"></i>
    <span>Export</span>
</button>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Filter Toolbar -->
    <div class="o_form_sheet mb-3 p-3 bg-white print:hidden">
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

    <!-- Branch Summary Cards (Only shown when viewing Semua Cabang / Konsolidasi) -->
    @if(!empty($isAllBranches) && $branchBreakdown->isNotEmpty())
    <div class="row g-3 mb-3 print:hidden">
        @foreach($branchBreakdown as $bStat)
        <div class="col-12 col-md-4">
            <div class="o_form_sheet p-3 bg-white h-100 shadow-sm border-start border-4 border-blue-600">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-xs text-slate-700 text-uppercase tracking-wider">
                        <i class="fa-solid fa-store text-blue-600 me-1"></i> {{ $bStat->branch_name }}
                    </span>
                    <span class="badge bg-blue-50 text-blue-700 border border-blue-200 text-[10px]">
                        {{ number_format($bStat->total_orders) }} Pesanan
                    </span>
                </div>
                <div class="fs-5 fw-extrabold text-blue-900 font-mono mb-0">
                    Rp {{ number_format($bStat->total_omzet, 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500 mt-1">
                    Rata-rata / Pesanan: <strong>Rp {{ number_format($bStat->avg_order_value, 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Chart -->
    @if(count($salesData) > 0)
    <div class="o_form_sheet p-4 bg-white mb-3 print:hidden">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-slate-800 text-xs uppercase mb-0">
                <i class="fa-solid fa-chart-line text-blue-600 me-1"></i> Tren Pendapatan Omzet Penjualan {{ $isAllBranches ? '(Perbandingan Multi-Cabang)' : '' }}
            </h6>
            @if(!empty($isAllBranches))
            <span class="badge bg-slate-100 text-slate-600 border text-[11px]">
                <i class="fa-solid fa-layer-group me-1"></i> Multi-Line Dataset Aktif
            </span>
            @endif
        </div>
        <div class="relative h-72 w-full">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
    @endif

    <!-- Data Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden bg-white print:p-0 print:border-0 print:shadow-none">
        
        <!-- Header Dokumen Cetak (Hanya Tampil Saat Print) -->
        <div class="d-none d-print-block p-4 text-center border-bottom mb-3">
            <h4 class="fw-bold text-slate-900 mb-0 uppercase tracking-wide">SNAPRINT &bull; PERCETAKAN</h4>
            <h5 class="fw-bold text-blue-900 mb-1">LAPORAN ANALISIS PENJUALAN & OMZET</h5>
            <p class="text-xs text-slate-500 mb-0">
                Periode: {{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d M Y') : 'Awal' }} s/d {{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d M Y') : 'Sekarang' }}
                @if(request('branch_id') && request('branch_id') !== 'all')
                    &bull; Cabang: {{ $branches->firstWhere('id', request('branch_id'))->nama_cabang ?? '' }}
                @else
                    &bull; Semua Cabang (Konsolidasi Multi-Cabang)
                @endif
            </p>
        </div>

        <div class="table-responsive">
            <table class="table table-hover o_list_table mb-0" id="main-table">
                <thead>
                    <tr>
                        <th class="ps-3 sortable">Periode / Tanggal</th>
                        @if(!empty($isAllBranches))
                        <th class="sortable">Cabang Toko</th>
                        @endif
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
                            @if(!empty($isAllBranches))
                            <td>
                                @php
                                    $badgeColor = match($data->branch_name) {
                                        'Cabang Grand Wisata (Pusat)' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'Cabang BTR Bekasi' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'Cabang Tambun' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200'
                                    };
                                @endphp
                                <span class="badge {{ $badgeColor }} border text-[11px] font-semibold">
                                    <i class="fa-solid fa-store text-[9px] me-1 opacity-70"></i>
                                    {{ $data->branch_name }}
                                </span>
                            </td>
                            @endif
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
                            <td colspan="{{ !empty($isAllBranches) ? 7 : 6 }}" class="text-center py-5 text-muted">Belum ada data penjualan pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($salesData) > 0)
                <tfoot>
                    <tr class="fw-bold bg-slate-100 border-top">
                        <td colspan="{{ !empty($isAllBranches) ? 2 : 1 }}" class="ps-3 text-uppercase">Total Keseluruhan</td>
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
    const datasets = {!! json_encode($chartData->datasets ?? []) !!};

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: { family: 'Plus Jakarta Sans', size: 11, weight: 'bold' },
                        usePointStyle: true,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'Rp ' + Number(context.parsed.y).toLocaleString('id-ID');
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + Number(value).toLocaleString('id-ID');
                        },
                        font: { family: 'monospace', size: 10 }
                    },
                    grid: {
                        color: 'rgba(226, 232, 240, 0.6)'
                    }
                },
                x: {
                    ticks: {
                        font: { family: 'Plus Jakarta Sans', size: 10 }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>
@endif
@endsection
