@extends('layouts.app')

@section('title', 'Requests for Quotation & Purchase Orders')
@section('page-title', 'Requests for Quotation (RFQ)')

@section('action-buttons')
<a href="{{ route('purchasing.create') }}" class="btn-odoo-primary text-decoration-none">
    <i class="fa-solid fa-plus"></i>
    <span>New RFQ</span>
</a>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Top Stat Buttons (Odoo Enterprise Sheet Header) -->
    <div class="d-flex align-items-center gap-2 mb-3 overflow-x-auto pb-1">
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-wallet text-teal-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-teal-700">Rp {{ number_format($totalSpend, 0, ',', '.') }}</div>
                <div class="o_stat_text">Total Purchases</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-clock text-amber-500 fs-5"></i>
            <div>
                <div class="o_stat_value text-amber-600">{{ number_format($pendingCount) }}</div>
                <div class="o_stat_text">Waiting Verification</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-circle-check text-emerald-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-emerald-600">{{ number_format($receivedCount) }}</div>
                <div class="o_stat_text">Received / Completed</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-rotate-left text-rose-500 fs-5"></i>
            <div>
                <div class="o_stat_value text-rose-600">{{ number_format($rejectedCount) }}</div>
                <div class="o_stat_text">Rejected / Returns</div>
            </div>
        </div>
    </div>

    <!-- Main Odoo Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden">
        <!-- View Mode 1: Table List View (Odoo Tree View) -->
        <div class="table-view-container">
            <div class="table-responsive">
                <table class="table table-hover o_list_table mb-0" id="main-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="ps-3 text-center no-sort">
                                <input type="checkbox" class="form-check-input" id="checkAllPO">
                            </th>
                            <th class="sortable">Reference (No. PO)</th>
                            <th class="sortable">Order Date</th>
                            <th class="sortable">Vendor (Supplier)</th>
                            <th class="sortable">Product / Material</th>
                            <th class="sortable text-center">Quantity</th>
                            <th class="sortable text-end">Total Amount</th>
                            <th class="sortable text-center">Status</th>
                            <th class="text-center no-sort" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            <tr class="search-row">
                                <td class="ps-3 text-center">
                                    <input type="checkbox" class="form-check-input">
                                </td>
                                <td>
                                    <span class="font-mono fw-bold text-indigo-700">
                                        {{ $purchase->po_number ?? ('PO-'.str_pad($purchase->id, 6, '0', STR_PAD_LEFT)) }}
                                    </span>
                                    @if($purchase->vendor_ref)
                                        <div class="text-[11px] text-slate-400 font-mono">Ref: {{ $purchase->vendor_ref }}</div>
                                    @endif
                                </td>
                                <td class="text-slate-600 text-xs">
                                    <div>{{ $purchase->created_at->format('d M Y') }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $purchase->created_at->format('H:i') }}</div>
                                </td>
                                <td>
                                    @if($purchase->supplier)
                                        <span class="badge bg-slate-100 text-slate-800 border text-[11px] font-normal">
                                            <i class="fa-solid fa-building me-1 opacity-60"></i> {{ $purchase->supplier->name }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-slate-800">{{ $purchase->material->material_name ?? 'N/A' }}</div>
                                    <div class="text-[11px] text-slate-400">Cabang: {{ $purchase->branch->nama_cabang ?? 'Pusat' }}</div>
                                </td>
                                <td class="text-center font-bold text-slate-700">
                                    {{ number_format($purchase->qty_bought) }} Unit
                                </td>
                                <td class="text-end font-mono fw-bold text-slate-800">
                                    Rp {{ number_format($purchase->total_cost, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if($purchase->status === 'waiting_approval')
                                        <span class="badge bg-amber-50 text-amber-700 border border-amber-200 text-[11px] font-semibold">
                                            <i class="fa-solid fa-clock me-1"></i> 1. Menunggu ACC Manager
                                        </span>
                                    @elseif($purchase->status === 'approved' || $purchase->status === 'pending_verification')
                                        <span class="badge bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-semibold">
                                            <i class="fa-solid fa-truck me-1"></i> 2. PO Terbit (Menunggu Fisik Gudang)
                                        </span>
                                        @if($purchase->approvedBy)
                                            <div class="text-[10px] text-emerald-700 font-semibold mt-0.5">✓ Di-ACC: {{ $purchase->approvedBy->username }}</div>
                                        @endif
                                    @elseif($purchase->status === 'received')
                                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-semibold">
                                            <i class="fa-solid fa-circle-check me-1"></i> 3. Selesai (Masuk Stok Gudang)
                                        </span>
                                        @if($purchase->verifiedBy)
                                            <div class="text-[10px] text-slate-500 mt-0.5">Cek Fisik: {{ $purchase->verifiedBy->username }}</div>
                                        @endif
                                    @elseif($purchase->status === 'rejected')
                                        <span class="badge bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-semibold">
                                            <i class="fa-solid fa-ban me-1"></i> Ditolak / Retur
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Manager Approval Button -->
                                        @if(($purchase->status === 'waiting_approval') && (auth()->user()->isOwner() || auth()->user()->isManager()))
                                            <form action="{{ route('purchasing.approve', $purchase->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Setujui Purchase Order #{{ $purchase->po_number }}? Tanda tangan digital Anda akan terstempel pada nota PO.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2" title="Setujui (ACC) PO">
                                                    <i class="fa-solid fa-signature me-1"></i> ACC PO
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Print Document Button -->
                                        <button onclick="printPO(
                                            '{{ $purchase->po_number ?? 'PO-'.$purchase->id }}', 
                                            '{{ addslashes($purchase->supplier->name ?? 'Supplier') }}', 
                                            '{{ addslashes($purchase->material->material_name ?? '-') }}', 
                                            '{{ $purchase->qty_bought }}', 
                                            '{{ number_format($purchase->total_cost, 0, ',', '.') }}', 
                                            '{{ $purchase->created_at->format('d M Y') }}', 
                                            '{{ addslashes($purchase->user->username ?? 'Staf Purchasing') }}', 
                                            '{{ $purchase->user && $purchase->user->signature_path ? asset('storage/'.$purchase->user->signature_path) : '' }}', 
                                            '{{ addslashes($purchase->approvedBy->username ?? 'Manajer Toko') }}', 
                                            '{{ $purchase->approvedBy && $purchase->approvedBy->signature_path ? asset('storage/'.$purchase->approvedBy->signature_path) : '' }}', 
                                            '{{ $purchase->approved_at ? \Carbon\Carbon::parse($purchase->approved_at)->format('d M Y, H:i') : '' }}',
                                            '{{ addslashes($purchase->verifiedBy->username ?? 'Petugas Gudang') }}',
                                            '{{ $purchase->verifiedBy && $purchase->verifiedBy->signature_path ? asset('storage/'.$purchase->verifiedBy->signature_path) : '' }}',
                                            '{{ $purchase->verified_at ? \Carbon\Carbon::parse($purchase->verified_at)->format('d M Y, H:i') : '' }}'
                                        )" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Print PO Document">
                                            <i class="fa-solid fa-print text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="p-4">
                                        <i class="fa-solid fa-cart-shopping fs-1 text-slate-300 mb-2"></i>
                                        <p class="mb-0">Belum ada data Purchase Order (RFQ).</p>
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
                @forelse($purchases as $purchase)
                    <div class="o_kanban_record bg-white border rounded p-3 shadow-sm hover:shadow transition search-card" style="border-left: 4px solid {{ $purchase->status === 'received' ? '#059669' : ($purchase->status === 'waiting_approval' ? '#d97706' : '#2563eb') }} !important;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="font-mono fw-bold text-slate-900 text-xs">{{ $purchase->po_number ?? 'PO-'.$purchase->id }}</span>
                            <span class="badge {{ $purchase->status === 'received' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }} text-[10px]">
                                {{ ucfirst(str_replace('_', ' ', $purchase->status)) }}
                            </span>
                        </div>
                        <div class="fw-semibold text-slate-800 line-clamp-1 mb-1">{{ $purchase->material->material_name ?? 'N/A' }}</div>
                        <div class="text-[11px] text-slate-500 mb-2">Vendor: {{ $purchase->supplier->name ?? 'N/A' }}</div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-slate-100 mt-2">
                            <span class="fw-bold font-mono text-slate-800 text-xs">Rp {{ number_format($purchase->total_cost, 0, ',', '.') }}</span>
                            <button onclick="printPO(
                                '{{ $purchase->po_number ?? 'PO-'.$purchase->id }}', 
                                '{{ addslashes($purchase->supplier->name ?? 'Supplier') }}', 
                                '{{ addslashes($purchase->material->material_name ?? '-') }}', 
                                '{{ $purchase->qty_bought }}', 
                                '{{ number_format($purchase->total_cost, 0, ',', '.') }}', 
                                '{{ $purchase->created_at->format('d M Y') }}', 
                                '{{ addslashes($purchase->user->username ?? 'Staf Purchasing') }}', 
                                '{{ $purchase->user && $purchase->user->signature_path ? asset('storage/'.$purchase->user->signature_path) : '' }}', 
                                '{{ addslashes($purchase->approvedBy->username ?? 'Manajer Toko') }}', 
                                '{{ $purchase->approvedBy && $purchase->approvedBy->signature_path ? asset('storage/'.$purchase->approvedBy->signature_path) : '' }}', 
                                '{{ $purchase->approved_at ? \Carbon\Carbon::parse($purchase->approved_at)->format('d M Y, H:i') : '' }}',
                                '{{ addslashes($purchase->verifiedBy->username ?? 'Petugas Gudang') }}',
                                '{{ $purchase->verifiedBy && $purchase->verifiedBy->signature_path ? asset('storage/'.$purchase->verifiedBy->signature_path) : '' }}',
                                '{{ $purchase->verified_at ? \Carbon\Carbon::parse($purchase->verified_at)->format('d M Y, H:i') : '' }}'
                            )" class="btn btn-sm btn-light py-0 px-2 text-xs">
                                <i class="fa-solid fa-print"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">Belum ada data RFQ.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
    function printPO(poNum, supplierName, itemName, qty, totalCost, poDate, staffName, staffSig, managerName, managerSig, approvedAt, warehouseName, warehouseSig, verifiedAt) {
        const printWindow = window.open('', '_blank');
        
        // 1. Staff Purchasing Signature
        const staffSigHtml = staffSig 
            ? `<img src="${staffSig}" style="max-height: 55px; max-width: 130px; margin: 0 auto 5px auto; display: block;">` 
            : `<div style="height: 50px; line-height: 50px; font-style: italic; color: #94a3b8; font-size: 11px;">[ TTD Digital ]</div>`;
        
        // 2. Manager ACC Signature
        const managerSigHtml = managerSig 
            ? `<img src="${managerSig}" style="max-height: 55px; max-width: 130px; margin: 0 auto 5px auto; display: block;">` 
            : (approvedAt 
                ? `<div style="border: 1.5px dashed #008784; padding: 4px; color: #008784; font-size: 10px; font-weight: bold; border-radius: 4px; margin-bottom: 5px;">✓ APPROVED DIGITAL STAMP<br><small style="font-weight:normal;">${approvedAt}</small></div>` 
                : `<div style="height: 50px; line-height: 50px; font-style: italic; color: #94a3b8; font-size: 11px;">[ Menunggu ACC Manager ]</div>`);

        // 3. Warehouse Receipt Signature
        const warehouseSigHtml = warehouseSig
            ? `<img src="${warehouseSig}" style="max-height: 55px; max-width: 130px; margin: 0 auto 5px auto; display: block;">`
            : (verifiedAt
                ? `<div style="border: 1.5px dashed #059669; padding: 4px; color: #059669; font-size: 10px; font-weight: bold; border-radius: 4px; margin-bottom: 5px;">✓ RECEIVED IN WAREHOUSE<br><small style="font-weight:normal;">${verifiedAt}</small></div>`
                : `<div style="height: 50px; line-height: 50px; font-style: italic; color: #94a3b8; font-size: 11px;">[ Menunggu Fisik Gudang ]</div>`);

        printWindow.document.write(`
            <html>
            <head>
                <title>Purchase Order - ${poNum}</title>
                <style>
                    body { font-family: 'Helvetica Neue', Arial, sans-serif; padding: 40px; color: #1e293b; }
                    .header { display: flex; justify-content: space-between; border-bottom: 2px solid #714B67; padding-bottom: 20px; margin-bottom: 30px; }
                    .brand { font-size: 24px; font-weight: bold; color: #714B67; }
                    .title { font-size: 20px; font-weight: bold; text-align: right; }
                    .info-table { width: 100%; margin-bottom: 30px; }
                    .info-table td { padding: 6px 0; font-size: 14px; }
                    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                    .items-table th, .items-table td { border: 1px solid #cbd5e1; padding: 12px; font-size: 14px; text-align: left; }
                    .items-table th { background: #f8fafc; }
                    .total { text-align: right; font-size: 18px; font-weight: bold; color: #714B67; }
                    .footer { margin-top: 50px; display: flex; justify-content: space-between; gap: 20px; }
                    .sig { text-align: center; flex: 1; border-top: 1px solid #94a3b8; padding-top: 10px; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="brand">Snaprint ERP</div>
                    <div class="title">PURCHASE ORDER (PO)<br><small style="font-size:12px; font-weight:normal; color:#64748b;">Standar Odoo Enterprise</small></div>
                </div>

                <table class="info-table">
                    <tr>
                        <td><strong>No. PO:</strong> ${poNum}</td>
                        <td style="text-align:right;"><strong>Tanggal Order:</strong> ${poDate}</td>
                    </tr>
                    <tr>
                        <td><strong>Supplier:</strong> ${supplierName}</td>
                        <td style="text-align:right;"><strong>Status:</strong> ${verifiedAt ? 'Selesai Diterima Gudang' : (approvedAt ? 'Resmi Terbit (Disetujui Manajer)' : 'Draft / Menunggu ACC')}</td>
                    </tr>
                </table>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Barang / Material</th>
                            <th>Kuantitas</th>
                            <th>Total Nilai PO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>${itemName}</td>
                            <td>${qty} Unit</td>
                            <td>Rp ${totalCost}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="total">Total Pengadaan: Rp ${totalCost}</div>

                <div class="footer">
                    <div class="sig">
                        ${staffSigHtml}
                        <strong>( ${staffName || 'Staf Purchasing'} )</strong><br>
                        <small style="color: #64748b;">1. Dibuat (Purchasing)</small>
                    </div>
                    <div class="sig">
                        ${managerSigHtml}
                        <strong>( ${managerName || 'Manajer Toko'} )</strong><br>
                        <small style="color: #64748b;">2. Disetujui (Manajer Toko)</small>
                    </div>
                    <div class="sig">
                        ${warehouseSigHtml}
                        <strong>( ${warehouseName || 'Admin Gudang'} )</strong><br>
                        <small style="color: #64748b;">3. Diterima (Pemeriksa Gudang)</small>
                    </div>
                </div>

                <script>
                    window.onload = function() { window.print(); }
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
</script>
@endsection
