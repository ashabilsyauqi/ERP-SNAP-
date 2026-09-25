@extends('layouts.app')

@section('title', 'Rekap Penjualan Mesin')
@section('page-title', 'Laporan Rekapitulasi Penjualan Mesin')

@section('action-buttons')
<div class="d-flex align-items-center gap-2">
    <a href="{{ route('reports.machine-sales.export-excel', request()->all()) }}" class="btn-odoo-secondary text-xs text-decoration-none d-inline-flex align-items-center gap-1.5 bg-emerald-50 text-emerald-800 border-emerald-300 hover:bg-emerald-100 shadow-2xs" title="Unduh data laporan dalam format Excel (.xls)">
        <i class="fa-solid fa-file-excel text-emerald-600"></i>
        <span>Export Excel</span>
    </a>
    <button type="button" onclick="window.print()" class="btn-odoo-secondary text-xs">
        <i class="fa-solid fa-print me-1.5"></i> Cetak Laporan / PDF
    </button>
</div>
@endsection

@section('content')

<!-- Top Filter Bar (Branch, Machine Tag, Timeframe & Date Picker) -->
<div class="bg-white border border-slate-200 rounded-2xl mb-4 p-3 shadow-sm print:hidden">
    <form method="GET" action="{{ route('reports.machine-sales') }}" id="machine-filter-form" class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-0">
        
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <!-- Branch Filter -->
            @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin())
            <div class="d-flex align-items-center gap-1.5">
                <label class="fw-bold text-slate-700 text-xs d-flex align-items-center mb-0">
                    <i class="fa-solid fa-building text-blue-600 me-1"></i> Cabang:
                </label>
                <select name="branch_id" onchange="document.getElementById('machine-filter-form').submit()" class="form-select form-select-sm fw-bold border-slate-300 rounded-xl text-xs" style="min-width: 170px;">
                    <option value="all" {{ ($branchId ?? 'all') == 'all' ? 'selected' : '' }}>Semua Cabang (Global)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ ($branchId ?? '') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->nama_cabang }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Machine Tag Filter -->
            <div class="d-flex align-items-center gap-1.5">
                <label class="fw-bold text-slate-700 text-xs d-flex align-items-center mb-0">
                    <i class="fa-solid fa-gear text-purple-600 me-1"></i> Label Mesin:
                </label>
                <select name="machine_tag" onchange="document.getElementById('machine-filter-form').submit()" class="form-select form-select-sm fw-bold border-purple-300 bg-purple-50/50 text-purple-900 rounded-xl text-xs" style="min-width: 180px;">
                    <option value="all" {{ ($selectedTag ?? '') === 'all' ? 'selected' : '' }}>Semua Mesin Berlabel</option>
                    @foreach($allMachineTags as $tag)
                        <option value="{{ $tag }}" {{ ($selectedTag ?? 'Mesin Pak Gunawan') === $tag ? 'selected' : '' }}>
                            {{ $tag }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Timeframe Hidden Inputs -->
            <input type="hidden" name="timeframe" id="timeframe-input" value="{{ $timeframe ?? 'month' }}">
            <input type="hidden" name="month" id="month-input" value="{{ $month ?? date('n') }}">
            <input type="hidden" name="year" id="year-input" value="{{ $year ?? date('Y') }}">

            <!-- Date Range Picker -->
            <div class="d-flex align-items-center gap-1.5 bg-slate-50 border {{ ($timeframe ?? '') === 'custom' ? 'border-blue-500 ring-2 ring-blue-100 bg-blue-50/50' : 'border-slate-200' }} p-1 rounded-xl">
                <div class="d-flex align-items-center gap-1 px-1">
                    <i class="fa-regular fa-calendar text-blue-600 text-xs"></i>
                    <span class="text-[11px] font-bold text-slate-700 uppercase">Rentang:</span>
                </div>
                <input type="date" name="start_date" id="filter-start-date" value="{{ $startDateInput ?? '' }}" class="form-control form-control-sm py-0.5 px-2 text-xs border-slate-300 rounded-lg font-mono font-semibold" style="width: 130px;" title="Dari Tanggal">
                <span class="text-slate-400 text-xs font-bold">-</span>
                <input type="date" name="end_date" id="filter-end-date" value="{{ $endDateInput ?? '' }}" class="form-control form-control-sm py-0.5 px-2 text-xs border-slate-300 rounded-lg font-mono font-semibold" style="width: 130px;" title="Sampai Tanggal">
                <button type="button" onclick="applyCustomDateRange()" class="btn btn-sm btn-primary text-xs px-2.5 py-1 rounded-lg font-bold shadow-2xs d-flex align-items-center gap-1">
                    <i class="fa-solid fa-filter text-[10px]"></i>
                    <span>Terapkan</span>
                </button>
                @if(($timeframe ?? '') === 'custom')
                <button type="button" onclick="setTimeframe('month')" class="btn btn-sm btn-light border text-slate-500 hover:text-rose-600 text-xs px-2 py-1 rounded-lg" title="Reset ke Bulan Ini">
                    <i class="fa-solid fa-xmark me-0.5"></i> Reset
                </button>
                @endif
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-purple-100 text-purple-800 border border-purple-200 rounded-lg px-2.5 py-1.5 font-bold text-xs">
                <i class="fa-solid fa-tag me-1"></i> {{ $selectedTag === 'all' ? 'Semua Label' : $selectedTag }}
            </span>
        </div>
    </form>
</div>

<!-- Siklus Bulanan Kalender Navigation -->
<div class="print:hidden">
@include('partials.monthly-lifecycle-bar', [
    'selectedMonth' => $month ?? date('n'),
    'selectedYear' => $year ?? date('Y'),
    'timeframe' => $timeframe ?? 'month',
    'showAllYear' => true,
    'route' => 'reports.machine-sales',
    'extraParams' => [
        'branch_id' => $branchId ?? 'all',
        'machine_tag' => $selectedTag ?? 'Mesin Pak Gunawan'
    ]
])
</div>

<!-- Printable Settlement Header (Visible on print) -->
<div class="d-none print:block mb-4">
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3">
        <div>
            <h3 class="fw-bold text-slate-900 mb-1">Snaprint Digital Printing</h3>
            <h5 class="fw-bold text-purple-900 mb-0">LAPORAN REKAPITULASI PENJUALAN MESIN</h5>
            <div class="text-xs text-slate-600 mt-1">Identifikasi Mesin: <strong>{{ $selectedTag }}</strong> &bull; Cabang: <strong>{{ $branchName }}</strong></div>
        </div>
        <div class="text-end">
            <div class="text-xs text-slate-500">Periode Laporan:</div>
            <div class="fw-bold text-slate-800 fs-6">{{ $periodLabel }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}</div>
        </div>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <!-- Card 1: Total Omzet Mesin -->
    <div class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex items-center justify-between" style="border-left: 4px solid #7c3aed !important;">
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Total Omzet Mesin</p>
            <h4 class="text-base font-extrabold text-purple-950 font-mono mb-0">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</h4>
            <small class="text-slate-400 text-[10px]">{{ $uniqueInvoicesCount }} Invoice transaksi</small>
        </div>
        <div class="p-2.5 bg-purple-50 text-purple-700 rounded-xl">
            <i class="fa-solid fa-coins text-lg"></i>
        </div>
    </div>

    <!-- Card 2: Total Qty Terjual -->
    <div class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex items-center justify-between" style="border-left: 4px solid #2563eb !important;">
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Total Volume Cetak</p>
            <h4 class="text-base font-extrabold text-blue-950 font-mono mb-0">{{ number_format($totalItemsSold) }} <span class="text-xs font-sans text-slate-500">Lembar/Pcs</span></h4>
            <small class="text-slate-400 text-[10px]">{{ count($productsMap) }} varian produk mesin</small>
        </div>
        <div class="p-2.5 bg-blue-50 text-blue-700 rounded-xl">
            <i class="fa-solid fa-layer-group text-lg"></i>
        </div>
    </div>

    <!-- Card 3: Modal Bahan (HPP) -->
    <div class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex items-center justify-between" style="border-left: 4px solid #f59e0b !important;">
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Total Modal Bahan (HPP)</p>
            <h4 class="text-base font-extrabold text-amber-950 font-mono mb-0">Rp {{ number_format($totalHpp, 0, ',', '.') }}</h4>
            <small class="text-slate-400 text-[10px]">Biaya bahan & produksi</small>
        </div>
        <div class="p-2.5 bg-amber-50 text-amber-700 rounded-xl">
            <i class="fa-solid fa-boxes-stacked text-lg"></i>
        </div>
    </div>

    <!-- Card 4: Laba Kotor Mesin -->
    <div class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex items-center justify-between" style="border-left: 4px solid #10b981 !important;">
        <div>
            <div class="d-flex align-items-center gap-1.5 mb-1">
                <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold px-1.5 py-0.5 rounded">Margin {{ $marginPercentage }}%</span>
                <span class="text-[10px] text-emerald-800 uppercase font-bold tracking-wider">Laba Kotor Mesin</span>
            </div>
            <h4 class="text-base font-extrabold text-emerald-950 font-mono mb-0">Rp {{ number_format($grossProfit, 0, ',', '.') }}</h4>
            <small class="text-slate-500 font-medium text-[10px]">Omzet dikurangi HPP Bahan</small>
        </div>
        <div class="p-2.5 bg-emerald-50 text-emerald-700 rounded-xl">
            <i class="fa-solid fa-chart-line text-lg"></i>
        </div>
    </div>
</div>

<!-- Table 1: Rekap Penjualan per Produk Mesin -->
<div class="o_form_sheet p-0 overflow-hidden mb-4">
    <div class="bg-slate-900 text-white px-4 py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-list-check text-teal-400"></i>
            <h6 class="fw-bold mb-0 text-white">REKAPITULASI PENJUALAN PER PRODUK ({{ $selectedTag }})</h6>
        </div>
        <span class="badge bg-slate-800 border border-slate-700 text-slate-300 text-xs">Periode: {{ $periodLabel }}</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover o_list_table mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 40px;" class="ps-3 text-center">No</th>
                    <th>Nama Produk / Bahan</th>
                    <th>Kategori</th>
                    <th>Label Mesin</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-center">Qty Terjual</th>
                    <th class="text-end">Total Omzet</th>
                    <th class="text-end">Total HPP</th>
                    <th class="text-end text-emerald-700 pe-3">Laba Kotor</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productsMap as $idx => $p)
                    <tr>
                        <td class="ps-3 text-center font-mono text-slate-400 text-xs">{{ $loop->iteration }}</td>
                        <td>
                            <div class="fw-bold text-slate-900 text-xs">{{ $p['product_name'] }}</div>
                            <small class="text-slate-400 text-[10px] font-mono">Ref #MAT-{{ $p['material_id'] }}</small>
                        </td>
                        <td>
                            <span class="badge bg-slate-100 text-slate-700 border text-[10px]">{{ $p['category'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-purple-50 text-purple-700 border border-purple-200 text-[10px]">
                                <i class="fa-solid fa-gear me-1"></i>{{ $p['machine_tag'] }}
                            </span>
                        </td>
                        <td class="text-end font-mono text-xs">Rp {{ number_format($p['unit_price'], 0, ',', '.') }}</td>
                        <td class="text-center font-mono font-bold text-xs">{{ number_format($p['qty_sold']) }}</td>
                        <td class="text-end font-mono font-bold text-purple-950 text-xs">Rp {{ number_format($p['total_omzet'], 0, ',', '.') }}</td>
                        <td class="text-end font-mono text-slate-600 text-xs">Rp {{ number_format($p['total_hpp'], 0, ',', '.') }}</td>
                        <td class="text-end font-mono font-bold text-emerald-700 text-xs pe-3">Rp {{ number_format($p['gross_profit'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <div class="p-3">
                                <i class="fa-solid fa-gear text-slate-300 fs-1 mb-2"></i>
                                <p class="mb-0 text-xs">Belum ada data penjualan untuk produk mesin <strong>{{ $selectedTag }}</strong> pada periode {{ $periodLabel }}.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($productsMap) > 0)
            <tfoot class="bg-slate-50 font-bold border-top border-2 text-xs">
                <tr>
                    <td colspan="5" class="ps-3 text-end uppercase text-slate-600">TOTAL KESELURUHAN:</td>
                    <td class="text-center font-mono text-blue-900">{{ number_format($totalItemsSold) }}</td>
                    <td class="text-end font-mono text-purple-900">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</td>
                    <td class="text-end font-mono text-slate-700">Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
                    <td class="text-end font-mono text-emerald-800 pe-3">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<!-- Table 2: Rincian Log Transaksi / Invoice Terkait -->
<div class="o_form_sheet p-0 overflow-hidden mb-4">
    <div class="bg-slate-100 border-bottom px-4 py-2.5 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-receipt text-indigo-600 text-xs"></i>
            <h6 class="fw-bold mb-0 text-slate-800 text-xs">RINCIAN INVOICE / STRUK TRANSAKSI TERKAIT ({{ $transactionDetails->count() }} Baris)</h6>
        </div>
    </div>

    <div class="table-responsive max-h-96 overflow-y-auto">
        <table class="table table-hover o_list_table mb-0 align-middle text-xs">
            <thead>
                <tr>
                    <th style="width: 40px;" class="ps-3 text-center">No</th>
                    <th>No. Invoice</th>
                    <th>Tanggal & Waktu</th>
                    <th>Pelanggan</th>
                    <th>Produk Mesin</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Subtotal Omzet</th>
                    <th>Cabang</th>
                    <th class="pe-3">Kasir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactionDetails as $d)
                    @php
                        $subtotal = ($d->area_m2 > 0) ? ($d->area_m2 * $d->selling_price) : ($d->qty_ordered * $d->selling_price);
                    @endphp
                    <tr>
                        <td class="ps-3 text-center text-slate-400 font-mono">{{ $loop->iteration }}</td>
                        <td>
                            <a href="{{ route('sales.receipt', $d->transaction_id) }}" target="_blank" class="fw-bold font-mono text-blue-700 text-decoration-none hover:underline">
                                {{ $d->transaction->invoice_number ?? '-' }}
                            </a>
                        </td>
                        <td class="text-slate-600 font-mono">{{ $d->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $d->transaction->customer_name ?: 'Pelanggan Umum' }}</td>
                        <td>
                            <div class="fw-bold text-slate-800">{{ $d->material->material_name ?? ($d->dimension_text ?: '-') }}</div>
                        </td>
                        <td class="text-center font-mono font-bold">{{ $d->qty_ordered }}</td>
                        <td class="text-end font-mono text-slate-600">Rp {{ number_format($d->selling_price, 0, ',', '.') }}</td>
                        <td class="text-end font-mono font-bold text-purple-950">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge bg-slate-100 text-slate-700 border text-[10px]">{{ $d->transaction->branch->nama_cabang ?? 'Pusat' }}</span>
                        </td>
                        <td class="pe-3 text-slate-600">{{ $d->transaction->user->full_name ?? ($d->transaction->user->username ?? '-') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-slate-400">Belum ada transaksi terkait.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Settlement Signature Section for Print -->
<div class="d-none print:block mt-5 pt-4 border-top">
    <div class="d-flex justify-content-between text-center px-4">
        <div>
            <div class="text-xs text-slate-500 mb-5">Dibuat Oleh (Kasir / Finance)</div>
            <div class="fw-bold text-slate-900 mt-5">( __________________________ )</div>
            <div class="text-[10px] text-slate-400 mt-1">Staff Snaprint</div>
        </div>
        <div>
            <div class="text-xs text-slate-500 mb-5">Diketahui & Diverifikasi Manajemen</div>
            <div class="fw-bold text-slate-900 mt-5">( {{ $selectedTag }} )</div>
            <div class="text-[10px] text-slate-400 mt-1">Penanggung Jawab Mesin</div>
        </div>
    </div>
</div>

<script>
    function applyCustomDateRange() {
        const startDate = document.getElementById('filter-start-date').value;
        const endDate = document.getElementById('filter-end-date').value;
        if (!startDate || !endDate) {
            alert('Silakan pilih kedua tanggal (Dari dan Sampai Tanggal) terlebih dahulu.');
            return;
        }
        if (startDate > endDate) {
            alert('Tanggal awal tidak boleh lebih besar dari tanggal akhir.');
            return;
        }
        document.getElementById('timeframe-input').value = 'custom';
        document.getElementById('machine-filter-form').submit();
    }

    function setTimeframe(tf) {
        document.getElementById('timeframe-input').value = tf;
        if (tf !== 'custom') {
            const startInput = document.getElementById('filter-start-date');
            const endInput = document.getElementById('filter-end-date');
            if (startInput) startInput.value = '';
            if (endInput) endInput.value = '';
        }
        document.getElementById('machine-filter-form').submit();
    }
</script>

@endsection
