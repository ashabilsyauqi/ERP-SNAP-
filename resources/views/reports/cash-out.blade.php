@extends('layouts.app')

@section('title', 'Cash Outflow Report')
@section('page-title', 'Laporan Pengeluaran Kas Keluar (Disbursements)')

@section('action-buttons')
<button type="button" onclick="window.print()" class="btn-odoo-primary" title="Cetak Laporan PDF / Print">
    <i class="fa-solid fa-print"></i>
    <span>Print Laporan</span>
</button>
<button type="button" onclick="exportTableToExcel('main-table', 'Cash_Outflow_Report')" class="btn-odoo-secondary">
    <i class="fa-solid fa-file-excel text-emerald-600"></i>
    <span>Export</span>
</button>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Filter Toolbar -->
    <div class="o_form_sheet mb-3 p-3 bg-white print:hidden">
        <form method="GET" action="{{ route('reports.cash-out') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm">
            </div>
            
            @if(Auth::user()->role === 'owner')
            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Cabang</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="all">Semua Cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Pilih Akun Beban</label>
                <select name="account_id" class="form-select form-select-sm">
                    <option value="">Semua Akun Beban</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->nama_akun }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-2">
                <button type="submit" class="btn-odoo-primary w-100 py-1 text-xs">
                    <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Data Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden bg-white print:p-0 print:border-0 print:shadow-none">
        
        <!-- Header Dokumen Cetak (Hanya Tampil Saat Print) -->
        <div class="d-none d-print-block p-4 text-center border-bottom mb-3">
            <h4 class="fw-bold text-slate-900 mb-0 uppercase tracking-wide">SNAPPRINT ERP &bull; PERCETAKAN</h4>
            <h5 class="fw-bold text-rose-800 mb-1">LAPORAN PENGELUARAN KAS KELUAR (DISBURSEMENTS)</h5>
            <p class="text-xs text-slate-500 mb-0">
                Periode: {{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d M Y') : 'Awal' }} s/d {{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d M Y') : 'Sekarang' }}
                @if(request('branch_id') && request('branch_id') !== 'all')
                    &bull; Cabang: {{ $branches->firstWhere('id', request('branch_id'))->nama_cabang ?? '' }}
                @else
                    &bull; Semua Cabang (Konsolidasi)
                @endif
            </p>
        </div>

        <div class="table-responsive">
            <table class="table table-hover o_list_table mb-0" id="main-table">
                <thead>
                    <tr>
                        <th class="ps-3 sortable">No. Referensi / Tanggal</th>
                        <th class="sortable">Akun Beban & Pengeluaran</th>
                        <th class="sortable">Cabang</th>
                        <th class="sortable">Keterangan / Keperluan</th>
                        <th class="sortable text-end pe-4">Jumlah Keluar (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashTransactions as $trx)
                        <tr class="search-row">
                            <td class="ps-3">
                                <span class="fw-bold font-mono text-rose-700 text-xs">{{ $trx->nomor_referensi }}</span>
                                <div class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($trx->tanggal)->format('d M Y') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-slate-800">{{ $trx->account->nama_akun ?? 'Akun Beban' }}</div>
                                <span class="text-[10px] text-slate-400">{{ $trx->account->kode_akun ?? '' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-slate-100 text-slate-700 border text-[11px] font-normal">
                                    {{ $trx->branch->nama_cabang ?? 'Pusat' }}
                                </span>
                            </td>
                            <td class="text-slate-700 text-xs">
                                {{ $trx->keterangan ?? '-' }}
                            </td>
                            <td class="text-end pe-4 font-mono fw-bold text-rose-700 fs-6">
                                - Rp {{ number_format($trx->jumlah, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Belum ada data kas keluar pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($cashTransactions) > 0)
                <tfoot>
                    <tr class="fw-bold bg-rose-50 border-top border-rose-200">
                        <td colspan="4" class="ps-3 text-uppercase text-rose-900 fw-bold">TOTAL PENGELUARAN KAS KELUAR</td>
                        <td class="text-end pe-4 font-mono fw-extrabold text-emerald-600 fs-5">- Rp {{ number_format($totalKeluar, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
