@extends('layouts.app')

@section('title', 'Cash & Bank Balances')
@section('page-title', 'Saldo Kas & Bank (Balance Sheet)')

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
        <form method="GET" action="{{ route('reports.cash-balance') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm">
            </div>
            @if(auth()->user()->isOwner())
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Pilih Cabang</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="all">Semua Cabang (Konsolidasi)</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->nama_cabang }}</option>
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
        <h5 class="fw-bold text-blue-900 mb-1">LAPORAN SALDO KAS & BANK (BALANCE SHEET)</h5>
        <p class="text-xs text-slate-500 mb-0">
            Periode: {{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d M Y') : 'Awal' }} s/d {{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d M Y') : 'Sekarang' }}
            @if(request('branch_id') && request('branch_id') !== 'all')
                &bull; Cabang: {{ $branches->firstWhere('id', request('branch_id'))->nama_cabang ?? '' }}
            @else
                &bull; Semua Cabang (Konsolidasi)
            @endif
        </p>
    </div>

    <!-- Stat Widgets -->
    <div class="d-flex align-items-center gap-2 mb-3 overflow-x-auto pb-1 print:hidden">
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-arrow-down text-emerald-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-emerald-700">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</div>
                <div class="o_stat_text">Total Kas Masuk</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-arrow-up text-rose-500 fs-5"></i>
            <div>
                <div class="o_stat_value text-rose-600">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</div>
                <div class="o_stat_text">Total Kas Keluar</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-vault text-blue-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-blue-700">Rp {{ number_format($saldo, 0, ',', '.') }}</div>
                <div class="o_stat_text">Saldo Kas Bersih</div>
            </div>
        </div>
    </div>

    <!-- Data Sheet: Account Breakdown -->
    <div class="o_form_sheet p-0 overflow-hidden bg-white mb-4">
        <div class="p-3 bg-slate-50 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-slate-800 mb-0 fs-6 d-flex align-items-center gap-2">
                <i class="fa-solid fa-layer-group text-blue-600"></i> Rincian Saldo per Akun Keuangan (Chart of Accounts)
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover o_list_table mb-0" id="main-table">
                <thead>
                    <tr>
                        <th class="ps-3 sortable">Akun Kas / Bank / Beban</th>
                        <th class="sortable">Tipe Akun</th>
                        <th class="sortable text-end">Total Debit (Masuk)</th>
                        <th class="sortable text-end">Total Kredit (Keluar)</th>
                        <th class="sortable text-end pe-4">Saldo Berjalan (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $acc)
                        <tr class="search-row">
                            <td class="ps-3">
                                <div class="fw-bold text-slate-800">{{ $acc->nama_akun }}</div>
                                <span class="font-mono text-[10px] text-slate-400">{{ $acc->kode_akun }}</span>
                            </td>
                            <td>
                                <span class="badge bg-slate-100 text-slate-700 border text-[11px] text-uppercase">
                                    {{ $acc->tipe }}
                                </span>
                            </td>
                            <td class="text-end font-mono text-emerald-700">
                                + Rp {{ number_format($acc->inflow ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-mono text-rose-700">
                                - Rp {{ number_format($acc->outflow ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="text-end pe-4 font-mono fw-bold {{ ($acc->balance ?? 0) >= 0 ? 'text-blue-800' : 'text-rose-800' }}">
                                Rp {{ number_format($acc->balance ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Belum ada akun kas aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold bg-slate-100 border-top">
                        <td colspan="2" class="ps-3 text-uppercase">Total Saldo Konsolidasi</td>
                        <td class="text-end font-mono text-emerald-800">+ Rp {{ number_format($totalMasuk, 0, ',', '.') }}</td>
                        <td class="text-end font-mono text-rose-800">- Rp {{ number_format($totalKeluar, 0, ',', '.') }}</td>
                        <td class="text-end pe-4 font-mono text-blue-900 fs-6">Rp {{ number_format($saldo, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Data Sheet: Branch Breakdown -->
    @if(count($perBranch) > 1)
    <div class="o_form_sheet p-0 overflow-hidden bg-white">
        <div class="p-3 bg-slate-50 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-slate-800 mb-0 fs-6 d-flex align-items-center gap-2">
                <i class="fa-solid fa-shop text-blue-600"></i> Rekapitulasi Saldo Kas per Cabang Toko
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover o_list_table mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Nama Cabang</th>
                        <th class="text-end">Kas Masuk (Rp)</th>
                        <th class="text-end">Kas Keluar (Rp)</th>
                        <th class="text-end pe-4">Saldo Kas Cabang (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($perBranch as $b)
                        <tr>
                            <td class="ps-3 fw-bold text-slate-800">{{ $b->nama_cabang }}</td>
                            <td class="text-end font-mono text-emerald-700">+ Rp {{ number_format($b->masuk, 0, ',', '.') }}</td>
                            <td class="text-end font-mono text-rose-700">- Rp {{ number_format($b->keluar, 0, ',', '.') }}</td>
                            <td class="text-end pe-4 font-mono fw-bold text-blue-900">Rp {{ number_format($b->saldo, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
