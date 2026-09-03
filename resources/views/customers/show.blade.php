@extends('layouts.app')

@section('title', 'Detail Pelanggan - ' . $customer->name)
@section('page-title', 'Data Pelanggan / ' . $customer->name . ' (Riwayat Transaksi & Buku Piutang)')

@section('action-buttons')
<a href="{{ route('customers.index') }}" class="btn-odoo-secondary">
    <i class="fa-solid fa-arrow-left"></i>
    <span>Kembali ke Daftar Pelanggan</span>
</a>
@endsection

@section('content')
<div class="space-y-4">

    <!-- Customer Profile Header Card -->
    <div class="o_form_sheet p-4 bg-white shadow-sm rounded-3 border">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 border-bottom pb-3 mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-700 text-white font-extrabold d-flex align-items-center justify-content-center text-xl shadow-md">
                    {{ strtoupper(substr($customer->name, 0, 2)) }}
                </div>
                <div>
                    <h4 class="fw-bold text-slate-900 mb-1 text-lg">{{ $customer->name }}</h4>
                    <div class="d-flex align-items-center gap-2 flex-wrap text-xs text-slate-500">
                        <span><i class="fa-solid fa-hashtag text-slate-400 me-0.5"></i> CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</span>
                        <span>&bull;</span>
                        <span><i class="fa-solid fa-shop text-blue-600 me-0.5"></i> {{ $customer->branch->nama_cabang ?? 'Pusat (Seluruh Cabang)' }}</span>
                        <span>&bull;</span>
                        <span><i class="fa-solid fa-calendar-check text-slate-400 me-0.5"></i> Terdaftar sejak {{ $customer->created_at->translatedFormat('d F Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if($customer->phone)
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank" class="btn btn-sm btn-outline-success font-semibold text-xs px-3 py-1.5 rounded-lg shadow-sm">
                    <i class="fa-brands fa-whatsapp me-1 fs-6"></i> Kirim WhatsApp
                </a>
                @endif
            </div>
        </div>

        <!-- Contact & Notes Details -->
        <div class="row g-3 text-xs">
            <div class="col-12 col-md-3">
                <span class="text-slate-400 font-semibold uppercase block text-[10px]">No. Telepon / WhatsApp</span>
                <span class="fw-bold text-slate-800 font-mono fs-6">{{ $customer->phone ?? '-' }}</span>
            </div>
            <div class="col-12 col-md-3">
                <span class="text-slate-400 font-semibold uppercase block text-[10px]">Email Pelanggan</span>
                <span class="fw-bold text-slate-800">{{ $customer->email ?? '-' }}</span>
            </div>
            <div class="col-12 col-md-3">
                <span class="text-slate-400 font-semibold uppercase block text-[10px]">Alamat Pengiriman</span>
                <span class="text-slate-700">{{ $customer->address ?? '-' }}</span>
            </div>
            <div class="col-12 col-md-3">
                <span class="text-slate-400 font-semibold uppercase block text-[10px]">Catatan Pelanggan</span>
                <span class="text-slate-700 italic">{{ $customer->notes ?? '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Financial KPI Summary Cards -->
    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="o_form_sheet p-3 bg-white h-100 shadow-sm border-start border-4 border-blue-600 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Total Pembelian (Omzet)</span>
                    <h4 class="text-xl font-extrabold text-blue-900 mb-0 font-mono">Rp {{ number_format($customer->total_spent, 0, ',', '.') }}</h4>
                    <span class="text-[11px] text-slate-400 font-medium">Akumulasi seluruh pesanan</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="o_form_sheet p-3 bg-white h-100 shadow-sm border-start border-4 border-emerald-600 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Frekuensi Pesanan</span>
                    <h4 class="text-xl font-extrabold text-emerald-800 mb-0 font-mono">{{ number_format($customer->total_orders) }} Pesanan</h4>
                    <span class="text-[11px] text-slate-400 font-medium">Transaksi berhasil tercatat</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="o_form_sheet p-3 bg-white h-100 shadow-sm border-start border-4 {{ $customer->total_receivables > 0 ? 'border-amber-500' : 'border-slate-300' }} flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Sisa Tagihan / Piutang DP</span>
                    <h4 class="text-xl font-extrabold {{ $customer->total_receivables > 0 ? 'text-amber-700' : 'text-slate-600' }} mb-0 font-mono">Rp {{ number_format($customer->total_receivables, 0, ',', '.') }}</h4>
                    <span class="text-[11px] {{ $customer->total_receivables > 0 ? 'text-amber-600 font-bold' : 'text-slate-400' }}">
                        {{ $customer->total_receivables > 0 ? 'Belum lunas (Piutang aktif)' : 'Seluruh tagihan lunas' }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-xl {{ $customer->total_receivables > 0 ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center text-lg">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase History Ledger Table -->
    <div class="o_form_sheet p-0 overflow-hidden bg-white shadow-sm rounded-3 border">
        <div class="p-3 bg-slate-50 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-slate-800 text-xs uppercase mb-0">
                <i class="fa-solid fa-clock-rotate-left text-blue-600 me-1.5"></i> Riwayat Pembelian & Buku Catatan Transaksi
            </h6>
            <span class="badge bg-white text-slate-600 border text-[11px]">
                {{ $transactions->total() }} Faktur Penjualan
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover o_list_table mb-0">
                <thead>
                    <tr>
                        <th class="ps-3 sortable">No. Invoice / SPK</th>
                        <th class="sortable">Tanggal Transaksi</th>
                        <th class="sortable">Petugas Kasir & Cabang</th>
                        <th class="sortable text-center">Status Pembayaran</th>
                        <th class="sortable text-center">Status Pengerjaan</th>
                        <th class="sortable text-end">Total Tagihan</th>
                        <th class="sortable text-end">Terbayar</th>
                        <th class="sortable text-end pe-3">Sisa Piutang</th>
                        <th class="text-center no-sort" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                        <tr>
                            <td class="ps-3 font-mono font-bold text-slate-900 text-xs">
                                <a href="javascript:void(0)" onclick="fetchInvoiceModal('{{ $trx->invoice_number }}')" class="text-blue-700 hover:text-blue-900 text-decoration-none">
                                    {{ $trx->invoice_number }}
                                </a>
                            </td>
                            <td class="text-xs text-slate-600">
                                {{ $trx->created_at->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="text-xs">
                                <span class="fw-semibold text-slate-800">{{ $trx->user->username ?? 'Kasir' }}</span>
                                <span class="text-slate-400 block text-[10px]">{{ $trx->branch->nama_cabang ?? 'Pusat' }}</span>
                            </td>
                            <td class="text-center">
                                @if($trx->payment_status === 'PAID')
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">LUNAS</span>
                                @elseif($trx->payment_status === 'PARTIAL')
                                    <span class="badge bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold">
                                        <i class="fa-solid fa-clock-rotate-left me-0.5"></i> UNPAID (DP)
                                    </span>
                                @else
                                    <span class="badge bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold">
                                        <i class="fa-solid fa-circle-xmark me-0.5"></i> UNPAID
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $statusStyle = match($trx->order_status) {
                                        'in_production' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'ready' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200'
                                    };
                                @endphp
                                <span class="badge {{ $statusStyle }} border text-[10px] font-semibold text-capitalize">
                                    {{ str_replace('_', ' ', $trx->order_status ?? 'completed') }}
                                </span>
                            </td>
                            <td class="text-end font-mono text-xs font-bold text-slate-900">
                                Rp {{ number_format($trx->total_price, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-mono text-xs text-emerald-700 font-semibold">
                                Rp {{ number_format($trx->paid_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-end pe-3 font-mono text-xs {{ $trx->remaining_amount > 0 ? 'text-rose-600 font-bold' : 'text-slate-400' }}">
                                Rp {{ number_format($trx->remaining_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <button type="button" 
                                        onclick="fetchInvoiceModal('{{ $trx->invoice_number }}')" 
                                        class="btn btn-sm btn-outline-primary py-0.5 px-2 text-xs" 
                                        title="Buka Invoice & SPK">
                                    <i class="fa-solid fa-receipt me-1"></i> Invoice
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                Pelanggan ini belum memiliki riwayat transaksi tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
        <div class="p-3 border-top bg-slate-50 d-flex justify-content-between align-items-center">
            <span class="text-xs text-slate-500">
                Menampilkan {{ $transactions->firstItem() }} - {{ $transactions->lastItem() }} dari {{ $transactions->total() }} riwayat
            </span>
            {{ $transactions->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
