@extends('layouts.app')

@section('title', 'Cash Mutation & General Ledger')
@section('page-title', 'Buku Kas & Mutasi Buku Besar (General Ledger)')

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
        <!-- Siklus Bulanan Kalender (Januari - Desember) Navigation -->
        @include('partials.monthly-lifecycle-bar', [
            'selectedMonth' => $month ?? date('n'),
            'selectedYear' => $year ?? date('Y'),
            'timeframe' => $timeframe ?? 'month',
            'showAllYear' => true,
            'route' => 'reports.cash-mutation',
            'extraParams' => ['branch_id' => $branchId ?? 'all', 'tipe' => request('tipe'), 'account_id' => request('account_id')]
        ])

        <form method="GET" action="{{ route('reports.cash-mutation') }}" class="row g-2 align-items-end">
            <input type="hidden" name="month" value="{{ $month ?? date('n') }}">
            <input type="hidden" name="year" value="{{ $year ?? date('Y') }}">
            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate ?? request('start_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate ?? request('end_date') }}" class="form-control form-control-sm">
            </div>
            
            @if(Auth::user()->isOwner() || Auth::user()->isSuperAdmin())
            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Cabang</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="all" {{ ($branchId ?? 'all') === 'all' || ($branchId ?? '') === '' ? 'selected' : '' }}>Semua Cabang (Konsolidasi)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ ($branchId ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
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

    <!-- Header Dokumen Cetak (Hanya Tampil Saat Print) -->
    <div class="d-none d-print-block p-4 text-center border-bottom mb-3 bg-white rounded">
        <h4 class="fw-bold text-slate-900 mb-0 uppercase tracking-wide">SNAPPRINT ERP &bull; PERCETAKAN</h4>
        <h5 class="fw-bold text-blue-900 mb-1">BUKU KAS & MUTASI BUKU BESAR (GENERAL LEDGER)</h5>
        <p class="text-xs text-slate-500 mb-0">
            Periode: {{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d M Y') : 'Awal' }} s/d {{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d M Y') : 'Sekarang' }}
            @if(($branchId ?? request('branch_id')) && ($branchId ?? request('branch_id')) !== 'all')
                &bull; Cabang: {{ $branches->firstWhere('id', ($branchId ?? request('branch_id')))->nama_cabang ?? '' }}
            @else
                &bull; Semua Cabang (Konsolidasi)
            @endif
        </p>
    </div>

    <!-- Stat Widgets -->
    <div class="d-flex align-items-center gap-2 mb-3 overflow-x-auto pb-1 print:hidden">
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
                                @if($trx->bukti_transaksi)
                                    <div class="mt-1">
                                        @if($trx->isBuktiPdf())
                                            <a href="{{ $trx->bukti_url }}" target="_blank" class="badge bg-red-50 text-red-700 border border-red-200 text-[10px] text-decoration-none" title="Buka Bukti PDF">
                                                <i class="fa-solid fa-file-pdf me-1"></i> Bukti PDF
                                            </a>
                                        @else
                                            <a href="{{ $trx->bukti_url }}" target="_blank" class="badge bg-blue-50 text-blue-700 border border-blue-200 text-[10px] text-decoration-none" title="Lihat Foto Bukti Nota">
                                                <i class="fa-solid fa-receipt me-1"></i> Bukti Nota
                                            </a>
                                        @endif
                                    </div>
                                @endif
                                @if($trx->transaction)
                                    @php
                                        $invItems = $trx->transaction->transactionDetails ? $trx->transaction->transactionDetails->map(function($d) {
                                            return [
                                                'material_name' => $d->material->material_name ?? 'Bahan Cetak',
                                                'qty_ordered' => $d->qty_ordered ?? 1,
                                                'selling_price' => $d->selling_price ?? 0,
                                                'subtotal' => ($d->qty_ordered ?? 1) * ($d->selling_price ?? 0),
                                            ];
                                        }) : collect();
                                        $invPayload = [
                                            'invoice_number' => $trx->transaction->invoice_number ?? 'INV-000',
                                            'created_at' => $trx->transaction->created_at ? $trx->transaction->created_at->format('d M Y H:i') : '-',
                                            'cashier_name' => $trx->transaction->user->username ?? 'Kasir',
                                            'branch_name' => $trx->branch->nama_cabang ?? 'Pusat',
                                            'payment_method' => $trx->transaction->payment_method ?? 'Cash',
                                            'payment_status' => $trx->transaction->payment_status ?? 'PAID',
                                            'paid_amount' => $trx->transaction->paid_amount ?? $trx->jumlah,
                                            'remaining_amount' => $trx->transaction->remaining_amount ?? 0,
                                            'customer_name' => $trx->transaction->customer_name ?? null,
                                            'customer_phone' => $trx->transaction->customer_phone ?? null,
                                            'total_price' => (float) ($trx->transaction->total_price ?? $trx->jumlah),
                                            'items' => $invItems
                                        ];
                                    @endphp
                                    <button type="button" 
                                            class="btn btn-sm btn-light border text-[11px] py-0 px-2 mt-1 text-blue-700 d-inline-flex align-items-center gap-1 font-mono"
                                            onclick='openSnaprintInvoice(@json($invPayload))'>
                                        <i class="fa-solid fa-file-invoice text-blue-600"></i>
                                        <span>Invoice: {{ $trx->transaction->invoice_number }}</span>
                                        @if($trx->transaction->isPaid())
                                            <span class="badge bg-emerald-100 text-emerald-800 text-[9px] px-1 py-0">PAID</span>
                                        @elseif($trx->transaction->isPartial())
                                            <span class="badge bg-rose-100 text-rose-800 text-[9px] px-1 py-0 font-bold">UNPAID (DP)</span>
                                        @else
                                            <span class="badge bg-rose-100 text-rose-800 text-[9px] px-1 py-0 font-bold">UNPAID</span>
                                        @endif
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
        @if($mutasi->hasPages())
            <div class="p-3 border-top bg-slate-50 d-flex justify-content-between align-items-center">
                <span class="text-xs text-slate-500">Menampilkan {{ $mutasi->firstItem() }} - {{ $mutasi->lastItem() }} dari {{ $mutasi->total() }} data</span>
                <div>{{ $mutasi->links('pagination::bootstrap-4') }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
