@extends('layouts.app')

@section('title', 'Dashboard Enterprise')
@section('page-title', 'Dashboard Monitoring ERP Enterprise')

@section('content')
<div class="row">
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Bladewind KPI 1: Total Omset -->
    <x-bladewind::statistic 
        number="Rp {{ number_format($totalSales, 0, ',', '.') }}" 
        label="Total Omset Penjualan" 
        has_shadow="true">
        <x-slot name="icon">
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
                <i class="bi bi-currency-dollar text-2xl"></i>
            </div>
        </x-slot>
    </x-bladewind::statistic>

    <!-- Bladewind KPI 2: Laba Kotor -->
    <x-bladewind::statistic 
        number="Rp {{ number_format($grossProfit, 0, ',', '.') }}" 
        label="Laba Kotor (Gross Profit)" 
        has_shadow="true">
        <x-slot name="icon">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl">
                <i class="bi bi-graph-up-arrow text-2xl"></i>
            </div>
        </x-slot>
    </x-bladewind::statistic>

    <!-- Bladewind KPI 3: Transaksi POS -->
    <x-bladewind::statistic 
        number="{{ number_format($totalTransactionsCount) }}" 
        label="Total Transaksi POS" 
        has_shadow="true">
        <x-slot name="icon">
            <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                <i class="bi bi-cart-check-fill text-2xl"></i>
            </div>
        </x-slot>
    </x-bladewind::statistic>

    <!-- Bladewind KPI 4: Pending PO & Low Stock -->
    <x-bladewind::statistic 
        number="{{ number_format($pendingPOCount) }} PO" 
        label="{{ number_format($lowStockCount) }} Item Menipis" 
        has_shadow="true">
        <x-slot name="icon">
            <div class="p-3 bg-rose-50 text-rose-600 rounded-2xl">
                <i class="bi bi-exclamation-triangle-fill text-2xl"></i>
            </div>
        </x-slot>
    </x-bladewind::statistic>
</div>
</div>

<!-- ApexCharts Interactive Widgets Row -->
<div class="row">
    <!-- Chart 1: Revenue & Profit Trend -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i> Performa Penjualan & Profitabilitas</h5>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool text-muted" data-lte-toggle="card-collapse"><i class="bi bi-dash-lg"></i></button>
                    <button type="button" class="btn btn-tool text-muted" data-lte-toggle="card-maximize"><i class="bi bi-fullscreen"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div id="revenue-chart" style="min-height: 300px;"></div>
            </div>
        </div>
    </div>

    <!-- Chart 2: Payment Method Distribution Donut -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold mb-0 text-dark"><i class="bi bi-pie-chart-fill text-success me-2"></i> Distribusi Pembayaran</h5>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool text-muted" data-lte-toggle="card-collapse"><i class="bi bi-dash-lg"></i></button>
                </div>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div id="payment-chart" class="w-100" style="min-height: 280px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions Table Card -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-indigo me-2"></i> Transaksi Penjualan Terkini</h5>
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold">Lihat Semua Sales</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7 text-muted">
                            <tr>
                                <th class="ps-4">No. Invoice</th>
                                <th>Waktu</th>
                                <th>Cabang</th>
                                <th>Kasir</th>
                                <th>Metode Bayar</th>
                                <th>Total Belanja</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $tx)
                                <tr>
                                    <td class="ps-4 font-mono fw-bold text-primary">#{{ $tx->id }}</td>
                                    <td class="text-muted fs-7">{{ $tx->created_at->format('d M Y, H:i') }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $tx->branch->nama_cabang ?? 'Pusat' }}</span></td>
                                    <td class="fw-semibold text-dark">{{ $tx->user->username ?? 'Kasir' }}</td>
                                    <td>
                                        @if($tx->payment_method === 'cash')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Cash</span>
                                        @elseif($tx->payment_method === 'qris')
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">QRIS</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">Transfer</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-dark">Rp {{ number_format($tx->total_price, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('sales.receipt', $tx->id) }}" target="_blank" class="btn btn-sm btn-light border rounded-pill px-3">
                                            <i class="bi bi-printer me-1"></i> Struk
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada transaksi penjualan terbaru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ApexCharts Script Initialization -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Revenue & Gross Profit Bar/Line Chart
        const revenueChartOptions = {
            series: [{
                name: 'Omset Sales (Rp)',
                data: [{{ $totalSales }}]
            }, {
                name: 'Laba Kotor (Rp)',
                data: [{{ $grossProfit }}]
            }],
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false }
            },
            colors: ['#4f46e5', '#10b981'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '45%',
                    borderRadius: 6
                },
            },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: {
                categories: ['Periode Saat Ini'],
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                    }
                }
            },
            fill: { opacity: 1 },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                    }
                }
            }
        };
        const revenueChart = new ApexCharts(document.querySelector("#revenue-chart"), revenueChartOptions);
        revenueChart.render();

        // 2. Payment Method Distribution Donut Chart
        const paymentChartOptions = {
            series: [{{ $cashSales }}, {{ $qrisSales }}, {{ $transferSales }}],
            labels: ['Cash / Tunai', 'QRIS', 'Transfer Bank'],
            chart: {
                type: 'donut',
                height: 280
            },
            colors: ['#10b981', '#3b82f6', '#f59e0b'],
            legend: { position: 'bottom' },
            dataLabels: { enabled: true },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                    }
                }
            }
        };
        const paymentChart = new ApexCharts(document.querySelector("#payment-chart"), paymentChartOptions);
        paymentChart.render();
    });
</script>
@endsection
