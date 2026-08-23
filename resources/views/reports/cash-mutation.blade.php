@extends('layouts.app')

@section('title', 'Cash Mutation & General Ledger')
@section('page-title', 'Buku Kas & Mutasi Buku Besar (General Ledger)')

@section('action-buttons')
<button type="button" onclick="exportTableToExcel('main-table', 'Cash_Mutation_Ledger')" class="btn-odoo-secondary">
    <i class="fa-solid fa-file-excel text-emerald-600"></i>
    <span>Export</span>
</button>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Filter Toolbar -->
    <div class="o_form_sheet mb-3 p-3 bg-white">
        <form method="GET" action="{{ route('reports.cash-mutation') }}" class="row g-2 align-items-end">
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
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Tipe Mutasi</label>
                <select name="tipe" class="form-select form-select-sm">
                    <option value="Semua">Semua Mutasi</option>
                    <option value="masuk" {{ request('tipe') == 'masuk' ? 'selected' : '' }}>Kas Masuk (Debit)</option>
                    <option value="keluar" {{ request('tipe') == 'keluar' ? 'selected' : '' }}>Kas Keluar (Kredit)</option>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Pilih Akun</label>
                <select name="account_id" class="form-select form-select-sm">
                    <option value="">Semua Akun</option>
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

    <!-- Stat Widgets -->
    <div class="d-flex align-items-center gap-2 mb-3 overflow-x-auto pb-1">
        @if(request('start_date'))
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-clock-rotate-left text-slate-500 fs-5"></i>
            <div>
                <div class="o_stat_value text-slate-800">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</div>
                <div class="o_stat_text">Saldo Awal</div>
            </div>
        </div>
        @endif
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-circle-arrow-down text-emerald-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-emerald-700">+ Rp {{ number_format($totalMasuk, 0, ',', '.') }}</div>
                <div class="o_stat_text">Total Kas Masuk</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-circle-arrow-up text-rose-500 fs-5"></i>
            <div>
                <div class="o_stat_value text-rose-600">- Rp {{ number_format($totalKeluar, 0, ',', '.') }}</div>
                <div class="o_stat_text">Total Kas Keluar</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-vault text-blue-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-blue-900">Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</div>
                <div class="o_stat_text">Saldo Kas Akhir</div>
            </div>
        </div>
    </div>

    <!-- Ledger Table Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden bg-white">
        <div class="p-3 bg-slate-50 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-slate-800 mb-0 fs-6 d-flex align-items-center gap-2">
                <i class="fa-solid fa-book-bookmark text-blue-700"></i> Mutasi Buku Kas & Jurnal Transaksi
            </h6>
        </div>

        <div class="table-responsive">
            <table class="table table-hover o_list_table mb-0" id="main-table">
                <thead>
                    <tr>
                        <th class="ps-3 sortable">No. Ref & Tanggal</th>
                        <th class="sortable">Akun Keuangan & Cabang</th>
                        <th class="sortable">Keterangan / Dokumen Reference</th>
                        <th class="sortable text-end">Debit (Masuk)</th>
                        <th class="sortable text-end">Kredit (Keluar)</th>
                        <th class="sortable text-end pe-4">Saldo Kumulatif</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mutasi as $trx)
                        <tr class="search-row">
                            <td class="ps-3">
                                <span class="fw-bold font-mono text-blue-700 text-xs">{{ $trx->nomor_referensi }}</span>
                                <div class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($trx->tanggal)->format('d M Y') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-slate-800">{{ $trx->account->nama_akun ?? 'Akun' }}</div>
                                <span class="text-[10px] text-slate-400">Cabang: {{ $trx->branch->nama_cabang ?? 'Pusat' }}</span>
                            </td>
                            <td class="text-slate-700 text-xs">
                                <div>{{ $trx->keterangan ?? '-' }}</div>
                                @if($trx->transaction)
                                    @php
                                        $invItems = $trx->transaction->transactionDetails->map(function($d) {
                                            return [
                                                'material_name' => $d->material->material_name ?? 'Bahan Cetak',
                                                'qty_ordered' => $d->qty_ordered,
                                                'selling_price' => $d->selling_price,
                                                'subtotal' => $d->qty_ordered * $d->selling_price,
                                            ];
                                        });
                                        $invPayload = [
                                            'invoice_number' => $trx->transaction->invoice_number,
                                            'created_at' => $trx->transaction->created_at->format('d M Y H:i'),
                                            'cashier_name' => $trx->transaction->user->username ?? 'Kasir',
                                            'branch_name' => $trx->branch->nama_cabang ?? 'Pusat',
                                            'payment_method' => $trx->transaction->payment_method ?? 'Cash',
                                            'payment_status' => 'PAID',
                                            'total_price' => $trx->transaction->total_price,
                                            'items' => $invItems
                                        ];
                                    @endphp
                                    <button type="button" 
                                            class="btn btn-sm btn-light border text-[11px] py-0 px-2 mt-1 text-blue-700 d-inline-flex align-items-center gap-1 font-mono"
                                            onclick='openSnapPrintInvoice(@json($invPayload))'>
                                        <i class="fa-solid fa-file-invoice text-blue-600"></i>
                                        <span>Invoice: {{ $trx->transaction->invoice_number }}</span>
                                        <span class="badge bg-emerald-100 text-emerald-800 text-[9px] px-1 py-0">PAID</span>
                                    </button>
                                @endif
                            </td>
                            <td class="text-end font-mono {{ $trx->tipe === 'masuk' ? 'text-emerald-700 fw-bold' : 'text-slate-400' }}">
                                {{ $trx->tipe === 'masuk' ? '+ Rp ' . number_format($trx->jumlah, 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-end font-mono {{ $trx->tipe === 'keluar' ? 'text-rose-700 fw-bold' : 'text-slate-400' }}">
                                {{ $trx->tipe === 'keluar' ? '- Rp ' . number_format($trx->jumlah, 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-end pe-4 font-mono fw-bold text-blue-950">
                                Rp {{ number_format($trx->running_balance, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada data mutasi kas pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
