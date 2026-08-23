@extends('layouts.app')

@section('title', 'Scrap & Rejected Goods')
@section('page-title', 'Scrap & Returns History')

@section('action-buttons')
<a href="{{ route('stock.inspection') }}" class="btn-odoo-secondary text-decoration-none">
    <i class="fa-solid fa-truck-ramp-box text-teal-600"></i>
    <span>Pending Inspections</span>
</a>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Top Stat Widgets -->
    <div class="d-flex align-items-center gap-2 mb-3">
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-rotate-left text-rose-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-rose-700">{{ number_format($rejectedCount) }}</div>
                <div class="o_stat_text">Total Scrapped Operations</div>
            </div>
        </div>
    </div>

    <!-- Main Odoo Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover o_list_table mb-0" id="main-table">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="ps-3 text-center no-sort">
                            <input type="checkbox" class="form-check-input">
                        </th>
                        <th class="sortable">PO Reference</th>
                        <th class="sortable">Date Rejection</th>
                        <th class="sortable">Vendor</th>
                        <th class="sortable">Product (Material)</th>
                        <th class="sortable text-center">Rejected Qty</th>
                        <th class="sortable">Rejection Reason / Notes</th>
                        <th class="sortable text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rejectedPurchases as $purchase)
                        <tr class="search-row">
                            <td class="ps-3 text-center">
                                <input type="checkbox" class="form-check-input">
                            </td>
                            <td>
                                <span class="font-mono fw-bold text-rose-700">{{ $purchase->po_number ?? 'PO-'.$purchase->id }}</span>
                                <div class="text-[10px] text-slate-400">By: {{ $purchase->user->username ?? 'Purchasing' }}</div>
                            </td>
                            <td class="text-slate-600 text-xs">
                                {{ $purchase->rejected_at ? \Carbon\Carbon::parse($purchase->rejected_at)->format('d M Y, H:i') : $purchase->updated_at->format('d M Y') }}
                            </td>
                            <td>
                                <span class="badge bg-slate-100 text-slate-700 border text-[11px] font-normal">
                                    {{ $purchase->supplier->name ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-slate-800">{{ $purchase->material->material_name ?? 'N/A' }}</div>
                                <span class="text-[10px] text-slate-400">Cabang: {{ $purchase->branch->nama_cabang ?? 'Pusat' }}</span>
                            </td>
                            <td class="text-center font-bold text-rose-700">
                                {{ number_format($purchase->qty_bought) }} Units
                            </td>
                            <td class="text-slate-700 text-xs">
                                <div class="p-1.5 bg-rose-50 border border-rose-100 rounded text-rose-800">
                                    <i class="fa-solid fa-triangle-exclamation me-1 text-rose-500"></i>
                                    {{ $purchase->rejection_reason ?? 'Barang cacat/tidak sesuai' }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-rose-100 text-rose-800 border border-rose-200 text-[11px] font-semibold">
                                    <i class="fa-solid fa-ban me-1"></i> Rejected
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="p-4">
                                    <i class="fa-solid fa-shield-halved fs-1 text-emerald-400 mb-2"></i>
                                    <p class="mb-0 fw-semibold text-slate-600">Tidak ada riwayat barang reject / ditolak.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
