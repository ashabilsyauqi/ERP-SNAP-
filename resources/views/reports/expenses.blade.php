@extends('layouts.app')

@section('title', 'Expenses Analysis')
@section('page-title', 'Analisis Beban & Biaya Operasional')

@section('action-buttons')
<button type="button" onclick="window.print()" class="btn-odoo-primary" title="Cetak Laporan PDF / Print">
    <i class="fa-solid fa-print"></i>
    <span>Print Laporan</span>
</button>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Filter Toolbar -->
    <div class="o_form_sheet mb-3 p-3 bg-white print:hidden">
        <form method="GET" action="{{ route('reports.expenses') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm">
            </div>
            
            @if(Auth::user()->isOwner() || Auth::user()->isSuperAdmin())
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Cabang</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="all" {{ ($branchId ?? 'all') === 'all' ? 'selected' : '' }}>Semua Cabang (Konsolidasi)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ ($branchId ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            
            <div class="col-12 col-md-3">
                <button type="submit" class="btn-odoo-primary w-100 py-1 text-xs">
                    <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Header Dokumen Cetak (Hanya Tampil Saat Print) -->
    <div class="d-none d-print-block p-4 text-center border-bottom mb-3 bg-white rounded">
        <h4 class="fw-bold text-slate-900 mb-0 uppercase tracking-wide">SNAPPRINT ERP &bull; PERCETAKAN</h4>
        <h5 class="fw-bold text-rose-800 mb-1">LAPORAN ANALISIS BEBAN & BIAYA OPERASIONAL</h5>
        <p class="text-xs text-slate-500 mb-0">
            Periode: {{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d M Y') : 'Awal' }} s/d {{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d M Y') : 'Sekarang' }}
            @if(($branchId ?? request('branch_id')) && ($branchId ?? request('branch_id')) !== 'all')
                &bull; Cabang: {{ $branches->firstWhere('id', ($branchId ?? request('branch_id')))->nama_cabang ?? '' }}
            @else
                &bull; Semua Cabang (Konsolidasi)
            @endif
        </p>
    </div>

    <div class="row g-3">
        <!-- Chart -->
        <div class="col-12 col-lg-4 print:hidden">
            <div class="o_form_sheet p-4 bg-white h-100 flex flex-col justify-between">
                <h6 class="fw-bold text-slate-800 text-xs uppercase mb-3"><i class="fa-solid fa-chart-pie text-blue-600 me-1"></i> Komposisi Beban Operasional</h6>
                @if($totalExpenses > 0)
                    <div class="relative h-60 w-full">
                        <canvas id="expenseChart"></canvas>
                    </div>
                    <div class="mt-3 p-3 bg-slate-50 rounded border text-center">
                        <span class="text-xs text-slate-500">Total Biaya Operasional Tercatat</span>
                        <h4 class="fw-bold text-rose-700 font-mono mb-0">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</h4>
                    </div>
                @else
                    <div class="h-60 flex items-center justify-center text-slate-400 text-xs">
                        Belum ada data pengeluaran beban.
                    </div>
                @endif
            </div>
        </div>

        <!-- Table -->
        <div class="col-12 col-lg-8">
            <div class="o_form_sheet p-0 overflow-hidden bg-white h-100">
                <div class="table-responsive">
                    <table class="table table-hover o_list_table mb-0" id="main-table">
                        <thead>
                            <tr>
                                <th class="ps-3 sortable">Akun Beban Operasional</th>
                                <th class="sortable text-center">Jumlah Trx</th>
                                <th class="sortable text-end">Total Pengeluaran (Rp)</th>
                                <th class="sortable text-end pe-4">Porsi (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $exp)
                                <tr class="search-row">
                                    <td class="ps-3">
                                        <div class="fw-bold text-slate-800">{{ $exp->nama_akun }}</div>
                                        <span class="font-mono text-[10px] text-slate-400">{{ $exp->kode_akun }}</span>
                                    </td>
                                    <td class="text-center font-bold text-slate-700">{{ number_format($exp->total_count) }} Trx</td>
                                    <td class="text-end font-mono fw-bold text-rose-700">Rp {{ number_format($exp->total_amount, 0, ',', '.') }}</td>
                                    <td class="text-end pe-4 font-mono text-slate-600">
                                        {{ $exp->percentage }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">Belum ada pengeluaran beban tercatat pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($totalExpenses > 0)
                        <tfoot>
                            <tr class="fw-bold bg-slate-100 border-top">
                                <td class="ps-3 text-uppercase">Total Beban Operasional</td>
                                <td class="text-center text-slate-900">{{ number_format($expenses->sum('total_count')) }} Trx</td>
                                <td class="text-end font-mono text-rose-700 fs-6">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                                <td class="text-end pe-4 font-mono text-slate-900">100%</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($totalExpenses > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('expenseChart').getContext('2d');
    const labels = {!! json_encode($expenses->pluck('nama_akun')) !!};
    const data = {!! json_encode($expenses->pluck('total_amount')) !!};

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#1E3A8A', '#2563EB', '#0284C7', '#059669', '#E11D48', '#D97706', '#7C3AED'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 10 } }
                }
            }
        }
    });
</script>
@endif
@endsection
