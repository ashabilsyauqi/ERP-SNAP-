@extends('layouts.app')

@section('title', 'Cash Disbursements')
@section('page-title', 'Pengeluaran Kas Keluar (Disbursements)')

@section('action-buttons')
<a href="{{ route('kas-keluar.create') }}" class="btn-odoo-primary text-decoration-none">
    <i class="fa-solid fa-plus"></i>
    <span>Tambah Kas Keluar</span>
</a>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Filter Toolbar -->
    <div class="o_form_sheet mb-3 p-3 bg-white">
        <form method="GET" action="{{ route('kas-keluar.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm">
            </div>
            
            @if(Auth::user()->role === 'owner')
            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Cabang</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">Semua Cabang</option>
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
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Cari Keterangan / Ref</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..." class="form-control form-control-sm">
            </div>

            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn-odoo-primary flex-grow-1 py-1 text-xs justify-center">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                <a href="{{ route('kas-keluar.index') }}" class="btn-odoo-secondary py-1 text-xs" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Main Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden bg-white">
        <div class="table-view-container">
            <div class="table-responsive">
                <table class="table table-hover o_list_table mb-0" id="main-table">
                    <thead>
                        <tr>
                            <th class="ps-3 sortable">No. Referensi / Tanggal</th>
                            <th class="sortable">Akun Beban & Operasional</th>
                            <th class="sortable">Cabang Toko</th>
                            <th class="sortable">Keterangan / Keperluan</th>
                            <th class="sortable text-end pe-3">Jumlah Keluar (Rp)</th>
                            <th class="text-center pe-3" style="width: 70px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashTransactions as $trx)
                            <tr class="search-row">
                                <td class="ps-3">
                                    <span class="font-mono fw-bold text-rose-700">{{ $trx->nomor_referensi }}</span>
                                    <div class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($trx->tanggal)->format('d M Y') }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800">{{ $trx->account->nama_akun ?? 'Akun Beban' }}</div>
                                    <span class="font-mono text-[10px] text-slate-400">{{ $trx->account->kode_akun ?? '' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-slate-100 text-slate-700 border text-[11px] font-normal">
                                        {{ $trx->branch->nama_cabang ?? 'Pusat' }}
                                    </span>
                                </td>
                                <td class="text-slate-700 text-xs">
                                    {{ $trx->keterangan ?? '-' }}
                                </td>
                                <td class="text-end pe-3 font-mono fw-bold text-rose-700 fs-6">
                                    - Rp {{ number_format($trx->jumlah, 0, ',', '.') }}
                                </td>
                                <td class="text-center pe-3">
                                    <form action="{{ route('kas-keluar.destroy', $trx->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data kas keluar ini?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger p-1 border" title="Hapus Kas Keluar">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="p-4">
                                        <i class="fa-solid fa-circle-arrow-up fs-1 text-slate-300 mb-2"></i>
                                        <p class="mb-0">Belum ada transaksi pengeluaran kas keluar.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($cashTransactions->hasPages())
                <div class="p-3 bg-slate-50 border-top">
                    {{ $cashTransactions->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
