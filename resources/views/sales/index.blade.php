@extends('layouts.app')

@section('title', 'POS Orders & Invoices')
@section('page-title', 'Riwayat Pesanan & Faktur Penjualan (Sales Invoices)')

@section('action-buttons')
<a href="{{ route('pos.index') }}" class="btn-odoo-primary text-decoration-none">
    <i class="fa-solid fa-cash-register"></i>
    <span>Transaksi Baru (POS)</span>
</a>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Main Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden bg-white">
        <!-- View Mode 1: Table List View -->
        <div class="table-view-container">
            <div class="table-responsive">
                <table class="table table-hover o_list_table mb-0" id="main-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="ps-3 text-center no-sort">
                                <input type="checkbox" class="form-check-input">
                            </th>
                            <th class="sortable">Nomor Invoice</th>
                            <th class="sortable">Tanggal & Jam</th>
                            <th class="sortable">Cabang Toko</th>
                            <th class="sortable">Petugas Kasir</th>
                            <th class="sortable text-end">Total Pembayaran</th>
                            <th class="sortable text-center">Metode & Status</th>
                            <th class="text-center no-sort" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $trx)
                            @php
                                $invItems = $trx->transactionDetails->map(function($d) {
                                    return [
                                        'material_name' => $d->material->material_name ?? 'Bahan Cetak',
                                        'qty_ordered' => $d->qty_ordered,
                                        'selling_price' => $d->selling_price,
                                        'subtotal' => $d->qty_ordered * $d->selling_price,
                                    ];
                                });
                                $invPayload = [
                                    'invoice_number' => $trx->invoice_number,
                                    'created_at' => $trx->created_at->format('d M Y H:i'),
                                    'cashier_name' => $trx->user->full_name ?: ($trx->user->username ?? 'Kasir'),
                                    'branch_name' => $trx->branch->nama_cabang ?? 'Pusat',
                                    'payment_method' => $trx->payment_method ?? 'Cash',
                                    'payment_status' => 'PAID',
                                    'total_price' => $trx->total_price,
                                    'items' => $invItems
                                ];
                            @endphp
                            <tr class="search-row">
                                <td class="ps-3 text-center">
                                    <input type="checkbox" class="form-check-input">
                                </td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-link p-0 fw-bold font-mono text-blue-700 text-decoration-none hover:underline d-inline-flex align-items-center gap-1.5"
                                            onclick='openSnapPrintInvoice(@json($invPayload))'>
                                        <i class="fa-solid fa-file-invoice text-blue-600 text-xs"></i>
                                        <span>{{ $trx->invoice_number }}</span>
                                    </button>
                                </td>
                                <td class="text-slate-600 text-xs">
                                    {{ $trx->created_at->format('d M Y, H:i') }}
                                </td>
                                <td>
                                    <span class="badge bg-slate-100 text-slate-700 border text-[11px] font-normal">
                                        {{ $trx->branch->nama_cabang ?? 'Pusat' }}
                                    </span>
                                </td>
                                <td class="text-slate-700 text-xs">
                                    <i class="fa-solid fa-user-circle text-slate-400 me-1"></i>
                                    {{ $trx->user->full_name ?: ($trx->user->username ?? 'Kasir') }}
                                </td>
                                <td class="text-end font-mono fw-bold text-blue-900">
                                    Rp {{ number_format($trx->total_price, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <span class="badge bg-light text-slate-800 border px-2 py-0.5 text-[11px] font-mono">
                                            {{ strtoupper($trx->payment_method) }}
                                        </span>
                                        <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 px-1.5 py-0.5 text-[10px] font-bold">
                                            PAID
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-sm btn-light border py-0 px-2 text-blue-700" title="Buka Dokumen Faktur" onclick='openSnapPrintInvoice(@json($invPayload))'>
                                            <i class="fa-solid fa-file-invoice text-xs"></i>
                                        </button>
                                        <a href="{{ route('sales.receipt', $trx->id) }}" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Cetak Struk Thermal POS" target="_blank">
                                            <i class="fa-solid fa-receipt text-xs"></i>
                                        </a>
                                        @if(auth()->user()->isOwner())
                                            <form action="{{ route('sales.refund', $trx->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Refund invoice ini? Stok akan dikembalikan dan transaksi kas akan dibatalkan.');">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-danger py-0 px-2" type="submit" title="Batalkan / Refund Transaksi">
                                                    <i class="fa-solid fa-rotate-left text-xs"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="p-4">
                                        <i class="fa-solid fa-receipt fs-1 text-slate-300 mb-2"></i>
                                        <p class="mb-0">Belum ada data transaksi penjualan POS.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- View Mode 2: Kanban Cards -->
        <div class="grid-view-container d-none p-4 bg-slate-50 border-top">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @forelse($transactions as $trx)
                    @php
                        $invItems = $trx->transactionDetails->map(function($d) {
                            return [
                                'material_name' => $d->material->material_name ?? 'Bahan Cetak',
                                'qty_ordered' => $d->qty_ordered,
                                'selling_price' => $d->selling_price,
                                'subtotal' => $d->qty_ordered * $d->selling_price,
                            ];
                        });
                        $invPayload = [
                            'invoice_number' => $trx->invoice_number,
                            'created_at' => $trx->created_at->format('d M Y H:i'),
                            'cashier_name' => $trx->user->full_name ?: ($trx->user->username ?? 'Kasir'),
                            'branch_name' => $trx->branch->nama_cabang ?? 'Pusat',
                            'payment_method' => $trx->payment_method ?? 'Cash',
                            'payment_status' => 'PAID',
                            'total_price' => $trx->total_price,
                            'items' => $invItems
                        ];
                    @endphp
                    <div class="o_kanban_record bg-white border rounded p-3 shadow-sm hover:shadow transition search-card" style="border-left: 4px solid var(--o-accent-color) !important;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <button type="button" 
                                    class="btn btn-link p-0 font-mono fw-bold text-slate-900 text-xs text-decoration-none text-start hover:underline"
                                    onclick='openSnapPrintInvoice(@json($invPayload))'>
                                {{ $trx->invoice_number }}
                            </button>
                            <span class="badge bg-emerald-100 text-emerald-800 text-[10px]">
                                PAID
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-500 mb-1">
                            <i class="fa-solid fa-clock me-1 opacity-70"></i> {{ $trx->created_at->format('d M Y, H:i') }}
                        </div>
                        <div class="text-[11px] text-slate-500 mb-2">
                            <i class="fa-solid fa-building me-1 opacity-70"></i> {{ $trx->branch->nama_cabang ?? 'Pusat' }} &bull; {{ $trx->user->username ?? 'Kasir' }}
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-slate-100 mt-2">
                            <span class="fw-bold font-mono text-blue-900 text-xs">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</span>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-light py-0 px-2 text-xs" onclick='openSnapPrintInvoice(@json($invPayload))'>
                                    <i class="fa-solid fa-file-invoice text-blue-600"></i>
                                </button>
                                <a href="{{ route('sales.receipt', $trx->id) }}" class="btn btn-sm btn-light py-0 px-2 text-xs" target="_blank">
                                    <i class="fa-solid fa-receipt"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">Belum ada transaksi.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
