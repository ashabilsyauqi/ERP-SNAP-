@extends('layouts.app')

@section('title', 'Cash Inflow Report')
@section('page-title', 'Laporan Penerimaan Kas Masuk (Inflow)')

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
            'route' => 'reports.cash-in',
            'extraParams' => ['branch_id' => $branchId ?? 'all', 'account_id' => request('account_id')]
        ])

        <form method="GET" action="{{ route('reports.cash-in') }}" class="row g-2 align-items-end">
            <input type="hidden" name="month" value="{{ $month ?? date('n') }}">
            <input type="hidden" name="year" value="{{ $year ?? date('Y') }}">
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate ?? request('start_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate ?? request('end_date') }}" class="form-control form-control-sm">
            </div>
            
            @if(Auth::user()->isOwner() || Auth::user()->isSuperAdmin())
            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Cabang</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="all" {{ ($branchId ?? 'all') === 'all' ? 'selected' : '' }}>Semua Cabang (Konsolidasi)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ ($branchId ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Pilih Akun</label>
                <select name="account_id" class="form-select form-select-sm">
                    <option value="">Semua Akun Kas/Bank</option>
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
            <h5 class="fw-bold text-emerald-800 mb-1">LAPORAN PENERIMAAN KAS MASUK (INFLOW)</h5>
            <p class="text-xs text-slate-500 mb-0">
                Periode: {{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d M Y') : 'Awal' }} s/d {{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d M Y') : 'Sekarang' }}
                @if(($branchId ?? request('branch_id')) && ($branchId ?? request('branch_id')) !== 'all')
                    &bull; Cabang: {{ $branches->firstWhere('id', ($branchId ?? request('branch_id')))->nama_cabang ?? '' }}
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
                        <th class="sortable">Akun Kas & Bank</th>
                        <th class="sortable">Cabang</th>
                        <th class="sortable">Keterangan / Dokumen Reference</th>
                        <th class="sortable text-end pe-4">Jumlah Masuk (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashTransactions as $trx)
                        <tr class="search-row">
                            <td class="ps-3">
                                <span class="fw-bold font-mono text-blue-700 text-xs">{{ $trx->nomor_referensi }}</span>
                                <div class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($trx->tanggal)->format('d M Y') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-slate-800">{{ $trx->account->nama_akun ?? 'Akun' }}</div>
                                <span class="text-[10px] text-slate-400">{{ $trx->account->kode_akun ?? '' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-slate-100 text-slate-700 border text-[11px] font-normal">
                                    {{ $trx->branch->nama_cabang ?? 'Pusat' }}
                                </span>
                            </td>
                            <td class="text-slate-700 text-xs">
                                <div>{{ $trx->keterangan ?? '-' }}</div>
                                @if($trx->transaction)
                                    @php
                                        $invItems = $trx->transaction->transactionDetails->map(function($d) {
                                            return [
                                                'material_name' => $d->material->material_name ?? 'Item Cetak',
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
                                            'payment_status' => $trx->transaction->payment_status,
                                            'paid_amount' => $trx->transaction->paid_amount,
                                            'remaining_amount' => $trx->transaction->remaining_amount,
                                            'customer_name' => $trx->transaction->customer_name,
                                            'customer_phone' => $trx->transaction->customer_phone,
                                            'total_price' => $trx->transaction->total_price,
                                            'items' => $invItems
                                        ];
                                    @endphp
                                    <button type="button" 
                                            class="btn btn-sm btn-light border text-[11px] py-0 px-2 mt-1 text-blue-700 d-inline-flex align-items-center gap-1 font-mono d-print-none"
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
                                    <span class="d-none d-print-inline text-[11px] font-mono text-slate-500">Ref Invoice: #{{ $trx->transaction->invoice_number }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-4 font-mono fw-bold text-emerald-600 fs-6">
                                + Rp {{ number_format($trx->jumlah, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Belum ada data kas masuk pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($cashTransactions) > 0)
                <tfoot>
                    <tr class="fw-bold bg-emerald-50 border-top border-emerald-200">
                        <td colspan="4" class="ps-3 text-uppercase text-emerald-900 fw-bold">TOTAL PENERIMAAN KAS MASUK</td>
                        <td class="text-end pe-4 font-mono fw-extrabold text-emerald-600 fs-5">+ Rp {{ number_format($totalMasuk, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if($cashTransactions->hasPages())
            <div class="p-3 border-top bg-slate-50 d-flex justify-content-between align-items-center">
                <span class="text-xs text-slate-500">Menampilkan {{ $cashTransactions->firstItem() }} - {{ $cashTransactions->lastItem() }} dari {{ $cashTransactions->total() }} data</span>
                <div>{{ $cashTransactions->links('pagination::bootstrap-4') }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
