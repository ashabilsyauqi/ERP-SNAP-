@extends('layouts.app')

@section('title', 'Accounting Dashboard')
@section('page-title', 'Overview & Jurnal Finansial (Accounting Dashboard)')

@section('action-buttons')
<a href="{{ route('kas-masuk.create') }}" class="btn-odoo-primary text-decoration-none">
    <i class="fa-solid fa-plus"></i>
    <span>Tambah Kas Masuk</span>
</a>
<a href="{{ route('kas-keluar.create') }}" class="btn-odoo-secondary text-decoration-none">
    <i class="fa-solid fa-arrow-right-from-bracket text-rose-600"></i>
    <span>Tambah Kas Keluar</span>
</a>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Top Stat Widgets -->
    <div class="d-flex align-items-center gap-2 mb-3 overflow-x-auto pb-1">
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-cart-shopping text-blue-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-blue-800">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</div>
                <div class="o_stat_text">Penjualan Bulan Ini</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-circle-arrow-down text-emerald-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-emerald-700">Rp {{ number_format($totalKasMasuk, 0, ',', '.') }}</div>
                <div class="o_stat_text">Total Kas Masuk</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-circle-arrow-up text-rose-500 fs-5"></i>
            <div>
                <div class="o_stat_value text-rose-600">Rp {{ number_format($totalKasKeluar, 0, ',', '.') }}</div>
                <div class="o_stat_text">Total Kas Keluar</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-vault text-blue-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-blue-900">Rp {{ number_format($saldoKas, 0, ',', '.') }}</div>
                <div class="o_stat_text">Saldo Kas Aktif</div>
            </div>
        </div>
    </div>

    <!-- Journal Overview Cards -->
    <div class="row g-3">
        <!-- Journal 1: Bank & Cash -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="o_form_sheet p-3 h-100 bg-white" style="border-top: 4px solid #1E3A8A !important;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-bold text-slate-800 fs-6 mb-0 d-flex align-items-center gap-1.5">
                        <i class="fa-solid fa-building-columns text-blue-700 text-sm"></i>
                        <span>Kas & Bank (Cash Journal)</span>
                    </h6>
                    <span class="badge bg-blue-50 text-blue-700 border border-blue-200 text-[10px]">Bank</span>
                </div>
                <p class="text-[11px] text-slate-400 mb-3">Arus kas tunai operasional kasir & rekening utama cabang</p>

                <div class="p-2.5 bg-slate-50 rounded border border-slate-100 mb-3">
                    <span class="text-slate-500 text-[11px]">Balance in General Ledger:</span>
                    <h5 class="fw-bold text-blue-900 mb-0 font-mono">Rp {{ number_format($saldoKas, 0, ',', '.') }}</h5>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('kas-masuk.index') }}" class="btn-odoo-secondary text-xs flex-1 text-center text-decoration-none">
                        Kas Masuk
                    </a>
                    <a href="{{ route('kas-keluar.index') }}" class="btn-odoo-secondary text-xs flex-1 text-center text-decoration-none">
                        Kas Keluar
                    </a>
                </div>
            </div>
        </div>

        <!-- Journal 2: Customer Invoices & POS Sales -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="o_form_sheet p-3 h-100 bg-white" style="border-top: 4px solid #2563EB !important;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-bold text-slate-800 fs-6 mb-0 d-flex align-items-center gap-1.5">
                        <i class="fa-solid fa-cash-register text-blue-600 text-sm"></i>
                        <span>Customer Invoices (POS)</span>
                    </h6>
                    <span class="badge bg-blue-50 text-blue-700 border border-blue-200 text-[10px]">Sales</span>
                </div>
                <p class="text-[11px] text-slate-400 mb-3">Penjualan nota kasir & omset transaksi retail cetak</p>

                <div class="p-2.5 bg-slate-50 rounded border border-slate-100 mb-3">
                    <div class="d-flex justify-content-between text-[11px] mb-1">
                        <span class="text-slate-500">Invoices Processed:</span>
                        <span class="fw-bold text-slate-800">{{ number_format($jumlahTransaksi) }} Transaksi</span>
                    </div>
                    <div class="d-flex justify-content-between text-[11px]">
                        <span class="text-slate-500">Total Billed:</span>
                        <span class="fw-bold text-blue-900 font-mono">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</span>
                    </div>
                </div>

                <a href="{{ route('reports.sales') }}" class="btn-odoo-primary text-xs w-100 text-center text-decoration-none d-block">
                    View Sales Report
                </a>
            </div>
        </div>

        <!-- Journal 3: Vendor Bills & Operational Expenses -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="o_form_sheet p-3 h-100 bg-white" style="border-top: 4px solid #e11d48 !important;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-bold text-slate-800 fs-6 mb-0 d-flex align-items-center gap-1.5">
                        <i class="fa-solid fa-file-invoice text-rose-600 text-sm"></i>
                        <span>Vendor Bills & Expenses</span>
                    </h6>
                    <span class="badge bg-rose-50 text-rose-700 border border-rose-200 text-[10px]">Expenses</span>
                </div>
                <p class="text-[11px] text-slate-400 mb-3">Biaya operasional bulanan, gaji, listrik, dan beban sewa</p>

                <div class="p-2.5 bg-slate-50 rounded border border-slate-100 mb-3">
                    <span class="text-slate-500 text-[11px]">Total Expenses Billed:</span>
                    <h5 class="fw-bold text-rose-700 mb-0 font-mono">Rp {{ number_format($totalKasKeluar, 0, ',', '.') }}</h5>
                </div>

                <a href="{{ route('reports.expenses') }}" class="btn-odoo-secondary text-xs w-100 text-center text-decoration-none d-block">
                    View Expense Analysis
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Cash Transactions Table -->
    <div class="o_form_sheet p-0 overflow-hidden mt-4 bg-white">
        <div class="p-3 bg-slate-50 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-slate-800 mb-0 fs-6 d-flex align-items-center gap-2">
                <i class="fa-solid fa-book text-slate-600"></i> Mutasi Kas Terbaru (Recent Journal Entries)
            </h6>
            <a href="{{ route('reports.cash-mutation') }}" class="btn btn-sm btn-link text-blue-700 text-decoration-none fw-bold text-xs p-0">
                Buka Buku Besar (General Ledger) &rarr;
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover o_list_table mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal / No. Referensi</th>
                        <th>Akun Keuangan & Cabang</th>
                        <th>Keterangan / Dokumen Referensi</th>
                        <th>Tipe</th>
                        <th class="text-end pe-4">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $trx)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-slate-800 font-mono text-xs">{{ $trx->nomor_referensi }}</div>
                                <span class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($trx->tanggal)->format('d M Y') }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-slate-800">{{ $trx->account->nama_akun ?? 'Akun' }}</div>
                                <span class="text-[10px] text-slate-400">Cabang: {{ $trx->branch->nama_cabang ?? 'Pusat' }}</span>
                            </td>
                            <td class="text-xs text-slate-700">
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
                            <td>
                                @if($trx->tipe === 'masuk')
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px]">
                                        <i class="fa-solid fa-circle-arrow-down me-1"></i> Kas Masuk
                                    </span>
                                @else
                                    <span class="badge bg-rose-50 text-rose-700 border border-rose-200 text-[11px]">
                                        <i class="fa-solid fa-circle-arrow-up me-1"></i> Kas Keluar
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4 font-mono fw-bold {{ $trx->tipe === 'masuk' ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $trx->tipe === 'masuk' ? '+' : '-' }} Rp {{ number_format($trx->jumlah, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi kas terbaru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
