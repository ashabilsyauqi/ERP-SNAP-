@extends('layouts.app')

@section('title', (auth()->check() && auth()->user()->isManager()) ? 'Dashboard Toko' : 'Dashboard Enterprise')
@section('page-title', (auth()->check() && auth()->user()->isManager()) ? 'Dashboard Monitoring Toko' : 'Dashboard Monitoring ERP Enterprise')

@section('content')

<!-- Branch Filter Dropdown (Owner Only) -->
@if(auth()->user()->isOwner())
<div class="bg-white border border-slate-200 rounded-lg mb-4 p-3 shadow-sm">
    <form method="GET" action="{{ route('owner.dashboard') }}" class="row align-items-center g-3 mb-0">
        <div class="col-auto">
            <label class="col-form-label fw-bold text-dark text-xs d-flex align-items-center">
                <i class="fa-solid fa-building text-indigo-600 me-2"></i>
                <span>Pilih Cabang Analisis:</span>
            </label>
        </div>
        <div class="col-auto">
            <select name="branch_id" onchange="this.form.submit()" class="form-select form-select-sm fw-semibold border-slate-300 rounded-lg text-xs">
                <option value="all" {{ $branchId == 'all' ? 'selected' : '' }}>Semua Cabang (Konsolidasi)</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                        {{ $branch->nama_cabang }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto ms-auto">
            @if($branchId === 'all')
                <span class="badge bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-pill px-3 py-1.5 font-bold text-xs">
                    <i class="fa-solid fa-globe me-1"></i> Data Konsolidasi Enterprise (Seluruh Cabang)
                </span>
            @else
                <span class="badge bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-pill px-3 py-1.5 font-bold text-xs">
                    <i class="fa-solid fa-code-branch me-1"></i> Data Cabang Mandiri
                </span>
            @endif
        </div>
    </form>
</div>
@endif

<!-- Overhauled Financial KPI Grid (5 Columns) -->
<div class="o_form_sheet">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Revenue -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/60 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Omset (Revenue)</p>
            <h3 class="text-lg font-extrabold text-slate-900">Rp {{ number_format($totalSales, 0, ',', '.') }}</h3>
            <small class="text-slate-400 text-[10px]">Total penjualan kotor POS</small>
        </div>
        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
            <i class="fa-solid fa-coins text-xl"></i>
        </div>
    </div>

    <!-- Card 2: HPP -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/60 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">HPP Modal (COGS)</p>
            <h3 class="text-lg font-extrabold text-slate-900">Rp {{ number_format($totalHpp, 0, ',', '.') }}</h3>
            <small class="text-slate-400 text-[10px]">Biaya bahan baku terpakai</small>
        </div>
        <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
            <i class="fa-solid fa-boxes-stacked text-xl"></i>
        </div>
    </div>

    <!-- Card 3: Gross Profit -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/60 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Laba Kotor</p>
            <h3 class="text-lg font-extrabold text-emerald-600">Rp {{ number_format($grossProfit, 0, ',', '.') }}</h3>
            <small class="text-slate-400 text-[10px]">Omset dikurangi HPP</small>
        </div>
        <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl">
            <i class="fa-solid fa-chart-line text-xl"></i>
        </div>
    </div>

    <!-- Card 4: OPEX -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/60 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Beban Operasional</p>
            <h3 class="text-lg font-extrabold text-rose-600">Rp {{ number_format($totalOpex, 0, ',', '.') }}</h3>
            <small class="text-slate-400 text-[10px]">Gaji, Listrik, Sewa, Maint.</small>
        </div>
        <div class="p-3 bg-rose-50 text-rose-600 rounded-2xl">
            <i class="fa-solid fa-wallet text-xl"></i>
        </div>
    </div>

    <!-- Card 5: Net Profit -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/60 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Laba Bersih (Net)</p>
            <h3 class="text-lg font-extrabold {{ $netProfit >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                Rp {{ number_format($netProfit, 0, ',', '.') }}
            </h3>
            <small class="text-slate-400 text-[10px]">Laba kotor dikurangi OPEX</small>
        </div>
        <div class="p-3 {{ $netProfit >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} rounded-2xl">
            <i class="fa-solid fa-scale-balanced text-xl"></i>
        </div>
    </div>
</div>

<!-- ApexCharts Section -->
@if($branchId === 'all')
    <!-- Consolidated View Layout (Full Trend on Top, Breakdown Below) -->
    <div class="row g-4 mb-6">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold mb-0 text-dark d-flex align-items-center">
                        <i class="fa-solid fa-chart-column text-indigo-600 me-2"></i>
                        <span>Tren Keuangan 6 Bulan Terakhir (Konsolidasi Seluruh Cabang)</span>
                    </h5>
                    <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-pill px-3 py-1 font-semibold text-xs">
                        Maret - Agustus 2026
                    </span>
                </div>
                <div class="card-body p-4">
                    <div id="financial-trend-chart" style="min-height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-6">
        <!-- Branch Comparison -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100 rounded-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0 text-dark d-flex align-items-center">
                        <i class="fa-solid fa-code-branch text-purple-600 me-2"></i>
                        <span>Performa Penjualan Antar Cabang</span>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div id="branch-comparison-chart" style="min-height: 280px;"></div>
                </div>
            </div>
        </div>

        <!-- Payment Method Distribution -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100 rounded-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0 text-dark d-flex align-items-center">
                        <i class="fa-solid fa-chart-pie text-emerald-600 me-2"></i>
                        <span>Metode Pembayaran POS</span>
                    </h5>
                </div>
                <div class="card-body p-4 d-flex align-items-center justify-content-center">
                    <div id="payment-chart" class="w-100" style="min-height: 280px;"></div>
                </div>
            </div>
        </div>
    </div>
@else
    <!-- Specific Branch View Layout (Side-by-Side) -->
    <div class="row g-4 mb-6">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100 rounded-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold mb-0 text-dark d-flex align-items-center">
                        <i class="fa-solid fa-chart-column text-indigo-600 me-2"></i>
                        <span>Tren Keuangan Cabang</span>
                    </h5>
                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-pill px-3 py-1 font-semibold text-xs">
                        Maret - Agustus 2026
                    </span>
                </div>
                <div class="card-body p-4">
                    <div id="financial-trend-chart" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100 rounded-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0 text-dark d-flex align-items-center">
                        <i class="fa-solid fa-chart-pie text-emerald-600 me-2"></i>
                        <span>Metode Pembayaran</span>
                    </h5>
                </div>
                <div class="card-body p-4 d-flex align-items-center justify-content-center">
                    <div id="payment-chart" class="w-100" style="min-height: 280px;"></div>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Recent Transactions Table Card -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold mb-0 text-dark d-flex align-items-center">
                    <i class="fa-solid fa-clock-rotate-left text-indigo-600 me-2"></i>
                    <span>Transaksi Penjualan Terkini</span>
                </h5>
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold d-inline-flex align-items-center">
                    <span>Lihat Semua Penjualan</span>
                    <i class="fa-solid fa-arrow-right ms-1.5 text-xs"></i>
                </a>
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
                                @php
                                    $invItems = $tx->transactionDetails->map(function($d) {
                                        return [
                                            'material_name' => $d->material->material_name ?? 'Bahan Cetak',
                                            'qty_ordered' => $d->qty_ordered,
                                            'selling_price' => $d->selling_price,
                                            'subtotal' => $d->qty_ordered * $d->selling_price,
                                        ];
                                    });
                                    $invPayload = [
                                        'invoice_number' => $tx->invoice_number,
                                        'created_at' => $tx->created_at->format('d M Y H:i'),
                                        'cashier_name' => $tx->user->full_name ?: ($tx->user->username ?? 'Kasir'),
                                        'branch_name' => $tx->branch->nama_cabang ?? 'Pusat',
                                        'payment_method' => $tx->payment_method ?? 'Cash',
                                        'payment_status' => 'PAID',
                                        'total_price' => $tx->total_price,
                                        'items' => $invItems
                                    ];
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <button type="button" 
                                                class="btn btn-link p-0 font-mono fw-bold text-blue-700 text-decoration-none d-inline-flex align-items-center gap-1 hover:underline"
                                                onclick='openSnaprintInvoice(@json($invPayload))'>
                                            <i class="fa-solid fa-file-invoice text-blue-600 text-xs"></i>
                                            <span>{{ $tx->invoice_number }}</span>
                                        </button>
                                    </td>
                                    <td class="text-muted fs-7">{{ $tx->created_at->format('d M Y, H:i') }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $tx->branch->nama_cabang ?? 'Pusat' }}</span></td>
                                    <td class="fw-semibold text-dark">{{ $tx->user->full_name ?: ($tx->user->username ?? 'Kasir') }}</td>
                                    <td>
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <span class="badge bg-slate-100 text-slate-700 border px-2 py-0.5 text-[11px]">{{ $tx->payment_method }}</span>
                                            <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold">PAID</span>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-blue-900 font-mono">Rp {{ number_format($tx->total_price, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-sm btn-light border py-0 px-2 text-blue-700" title="Buka Dokumen Faktur" onclick='openSnaprintInvoice(@json($invPayload))'>
                                                <i class="fa-solid fa-file-invoice text-xs"></i>
                                            </button>
                                            <a href="{{ route('sales.receipt', $tx->id) }}" target="_blank" class="btn btn-sm btn-light border py-0 px-2" title="Cetak Struk POS">
                                                <i class="fa-solid fa-receipt text-xs"></i>
                                            </a>
                                        </div>
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
</div>

<!-- ApexCharts Script Initialization -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Combo Chart: Financial Trend (Sales vs OPEX vs Net Profit)
        const trendChartOptions = {
            series: [{
                name: 'Omset Penjualan (Revenue)',
                type: 'column',
                data: @json($monthlySales)
            }, {
                name: 'Beban Operasional (OPEX)',
                type: 'column',
                data: @json($monthlyOpex)
            }, {
                name: 'Laba Bersih (Net Profit)',
                type: 'line',
                data: @json($monthlyNetProfit)
            }],
            chart: {
                height: 350,
                type: 'line',
                stacked: false,
                toolbar: { show: false }
            },
            stroke: {
                width: [0, 0, 4],
                curve: 'smooth'
            },
            plotOptions: {
                bar: {
                    columnWidth: '50%',
                    borderRadius: 5
                }
            },
            colors: ['#714B67', '#e11d48', '#008784'],
            fill: {
                opacity: [0.85, 0.85, 1],
                gradient: {
                    inverseColors: false,
                    shade: 'light',
                    type: "vertical",
                    opacityFrom: 0.85,
                    opacityTo: 0.55,
                    stops: [0, 100, 100, 100]
                }
            },
            labels: @json($months),
            markers: {
                size: 5
            },
            xaxis: {
                type: 'category'
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                    }
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (y) {
                        if (typeof y !== "undefined") {
                            return "Rp " + new Intl.NumberFormat('id-ID').format(y);
                        }
                        return y;
                    }
                }
            }
        };

        const trendChart = new ApexCharts(document.querySelector("#financial-trend-chart"), trendChartOptions);
        trendChart.render();

        // 2. Payment Method Distribution Donut Chart
        const paymentChartOptions = {
            series: [{{ (float)$cashSales }}, {{ (float)$qrisSales }}, {{ (float)$transferSales }}],
            labels: ['Cash / Tunai', 'QRIS', 'Transfer Bank'],
            chart: {
                type: 'donut',
                height: 280
            },
            colors: ['#008784', '#714B67', '#f59e0b'],
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

        // 3. Branch Sales Comparison (Only in Consolidated View)
        @if($branchId === 'all')
            const branchComparisonOptions = {
                series: [{
                    name: 'Total Penjualan (Rp)',
                    data: @json($branchSalesData->pluck('sales'))
                }],
                chart: {
                    type: 'bar',
                    height: 280,
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        barHeight: '60%',
                        distributed: true,
                        horizontal: true,
                        borderRadius: 4
                    }
                },
                colors: ['#714B67', '#008784', '#3b82f6', '#f59e0b'],
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                    }
                },
                xaxis: {
                    categories: @json($branchSalesData->pluck('name')),
                    labels: {
                        formatter: function (val) {
                            return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                        }
                    }
                },
                legend: { show: false },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                        }
                    }
                }
            };
            const branchChart = new ApexCharts(document.querySelector("#branch-comparison-chart"), branchComparisonOptions);
            branchChart.render();
        @endif
    });
</script>
@endsection
