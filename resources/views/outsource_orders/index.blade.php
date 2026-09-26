@extends('layouts.app')

@section('title', 'Cetak di Luar (Vendor Outsource)')
@section('page-title', 'Modul Cetak di Luar (Outsource Pipeline)')

@section('action-buttons')
<button type="button" onclick="openCreateOrderModal()" class="btn btn-primary btn-sm rounded-xl font-bold shadow-sm flex items-center gap-1.5">
    <i class="fa-solid fa-plus"></i>
    <span>Pesanan Baru (Customer)</span>
</button>
<a href="{{ route('pos.index') }}" class="btn btn-outline-secondary btn-sm rounded-xl font-bold shadow-sm">
    <i class="fa-solid fa-cash-register me-1"></i> Terminal POS
</a>
@endsection

@section('content')
<style>
.odoo-stage-panel {
    display: none !important;
}
.odoo-stage-panel.active {
    display: block !important;
}
.odoo-stage-btn {
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease-in-out;
}
.odoo-stage-btn.active {
    background-color: #0f172a !important;
    color: #ffffff !important;
    font-weight: 700 !important;
}
.odoo-stage-btn:hover:not(.active) {
    background-color: #e2e8f0 !important;
}
</style>
<div class="space-y-6 pb-12">

    <!-- METRIC STAT CARDS (4-STAGE PIPELINE) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        
        <!-- Total Pesanan -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Semua Order</span>
            <div class="text-2xl font-black text-slate-900 mt-2 font-mono">{{ $counts['all'] }}</div>
            <span class="text-[10.5px] text-slate-400 mt-1">Total diarsip</span>
        </div>

        <!-- 1. Draft Customer -->
        <div class="bg-white p-4 rounded-2xl border border-amber-200 shadow-sm flex flex-col justify-between bg-amber-50/20">
            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1">
                <i class="fa-solid fa-file-invoice text-amber-600"></i> 1. Draft Customer
            </span>
            <div class="text-2xl font-black text-amber-900 mt-2 font-mono">{{ $counts['draft_customer'] }}</div>
            <span class="text-[10.5px] text-amber-700 mt-1">Order customer baru</span>
        </div>

        <!-- 2. Pengajuan ke Direksi -->
        <div class="bg-white p-4 rounded-2xl border border-indigo-200 shadow-sm flex flex-col justify-between bg-indigo-50/20">
            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-800 flex items-center gap-1">
                <i class="fa-solid fa-user-clock text-indigo-600"></i> 2. Pengajuan Direksi
            </span>
            <div class="text-2xl font-black text-indigo-900 mt-2 font-mono">{{ $counts['pending_approval'] }}</div>
            <span class="text-[10.5px] text-indigo-700 mt-1">Antrean ACC HPP</span>
        </div>

        <!-- 3. Proses QC & Pengerjaan -->
        <div class="bg-white p-4 rounded-2xl border border-purple-200 shadow-sm flex flex-col justify-between bg-purple-50/20">
            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-800 flex items-center gap-1">
                <i class="fa-solid fa-clipboard-check text-purple-600"></i> 3. Proses QC
            </span>
            <div class="text-2xl font-black text-purple-900 mt-2 font-mono">{{ $counts['in_production'] + $counts['qc_passed'] }}</div>
            <span class="text-[10.5px] text-purple-700 mt-1">Pengerjaan & QC</span>
        </div>

        <!-- 4. Close (Selesai) -->
        <div class="bg-white p-4 rounded-2xl border border-emerald-200 shadow-sm flex flex-col justify-between bg-emerald-50/20">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 flex items-center gap-1">
                <i class="fa-solid fa-circle-check text-emerald-600"></i> 4. Close (Selesai)
            </span>
            <div class="text-2xl font-black text-emerald-900 mt-2 font-mono">{{ $counts['completed'] }}</div>
            <span class="text-[10.5px] text-emerald-700 mt-1">Laba: <strong>Rp {{ number_format($counts['total_realized_profit'], 0, ',', '.') }}</strong></span>
        </div>

    </div>

    <!-- FILTER & PIPELINE TABS -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-3">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
            
            <!-- Tab Filter Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar w-full md:w-auto">
                <a href="{{ route('outsource-orders.index', array_merge(request()->query(), ['tab' => 'all'])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition border {{ $activeTab === 'all' ? 'bg-slate-900 text-white border-slate-900 shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border-slate-200' }}">
                    <span>Semua ({{ $counts['all'] }})</span>
                </a>
                <a href="{{ route('outsource-orders.index', array_merge(request()->query(), ['tab' => 'draft_customer'])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition border {{ $activeTab === 'draft_customer' ? 'bg-amber-600 text-white border-amber-600 shadow-sm' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border-amber-200' }}">
                    <i class="fa-solid fa-file-invoice me-1"></i> Draft Customer ({{ $counts['draft_customer'] }})
                </a>
                <a href="{{ route('outsource-orders.index', array_merge(request()->query(), ['tab' => 'pending_approval'])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition border {{ $activeTab === 'pending_approval' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-indigo-50 text-indigo-800 hover:bg-indigo-100 border-indigo-200' }}">
                    <i class="fa-solid fa-user-clock me-1"></i> Pengajuan Direksi ({{ $counts['pending_approval'] }})
                </a>
                <a href="{{ route('outsource-orders.index', array_merge(request()->query(), ['tab' => 'in_production'])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition border {{ $activeTab === 'in_production' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-blue-50 text-blue-800 hover:bg-blue-100 border-blue-200' }}">
                    <i class="fa-solid fa-gears me-1"></i> Proses QC ({{ $counts['in_production'] + $counts['qc_passed'] }})
                </a>
                <a href="{{ route('outsource-orders.index', array_merge(request()->query(), ['tab' => 'completed'])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition border {{ $activeTab === 'completed' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border-emerald-200' }}">
                    <i class="fa-solid fa-circle-check me-1"></i> Close / Selesai ({{ $counts['completed'] }})
                </a>
            </div>

            <!-- Search Input -->
            <form method="GET" action="{{ route('outsource-orders.index') }}" class="flex items-center gap-2 w-full md:w-72">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nota, customer, vendor..." 
                           class="form-control form-control-sm text-xs pl-8 rounded-xl">
                </div>
                @if(request('search'))
                    <a href="{{ route('outsource-orders.index', ['tab' => $activeTab]) }}" class="btn btn-sm btn-light border rounded-xl" title="Reset cari">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </form>

        </div>
    </div>

    <!-- ORDERS LIST / MASTER TABLE -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="table table-hover align-middle mb-0 text-xs">
                <thead class="bg-slate-50 text-slate-700 uppercase tracking-wider text-[10px] font-bold border-b">
                    <tr>
                        <th class="py-3 px-4">No. Order & Tanggal</th>
                        <th class="py-3 px-4">Pelanggan & Kasir</th>
                        <th class="py-3 px-4">Pekerjaan / Produk</th>
                        <th class="py-3 px-4 text-end">Harga Customer</th>
                        <th class="py-3 px-4 text-end">HPP Vendor + Ongkir</th>
                        <th class="py-3 px-4 text-end">Estimasi Margin</th>
                        <th class="py-3 px-4 text-center">Stage / Status</th>
                        <th class="py-3 px-4 text-center">Aksi / Lembar Kerja</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($orders as $order)
                        @php
                            $statusInfo = $order->status_label;
                            $unitPrice = $order->customer_unit_price > 0 ? $order->customer_unit_price : ($order->qty > 0 ? ($order->customer_price / $order->qty) : $order->customer_price);
                            $vendorUnitPrice = $order->vendor_unit_price > 0 ? $order->vendor_unit_price : ($order->qty > 0 ? ($order->vendor_cost / $order->qty) : $order->vendor_cost);
                        @endphp
                        <tr>
                            <!-- No Order & Date -->
                            <td class="py-3 px-4">
                                <button type="button" onclick="openOrderWorksheet({{ $order->id }})" 
                                        class="font-mono text-blue-600 font-bold hover:underline block text-start">
                                    {{ $order->order_number }}
                                </button>
                                <span class="text-[10.5px] text-slate-400 block">{{ $order->created_at->format('d M Y H:i') }}</span>
                                <span class="badge bg-slate-100 text-slate-600 border text-[9.5px] font-normal mt-0.5">{{ $order->branch->nama_cabang ?? 'Pusat' }}</span>
                            </td>

                            <!-- Customer & Cashier -->
                            <td class="py-3 px-4">
                                <strong class="text-slate-900 block">{{ $order->customer_name }}</strong>
                                @if($order->customer_phone)
                                    <span class="text-[10.5px] text-slate-500 font-mono block">{{ $order->customer_phone }}</span>
                                @endif
                                <span class="text-[10px] text-slate-400 block">Kasir: {{ $order->user->full_name ?? ($order->user->username ?? 'Kasir') }}</span>
                            </td>

                            <!-- Job Title & Specs -->
                            <td class="py-3 px-4">
                                <strong class="text-slate-900 block">{{ $order->job_title }}</strong>
                                <span class="text-[11px] text-slate-600 font-semibold font-mono block">
                                    {{ $order->qty }} {{ $order->unit }}
                                </span>
                                @if($order->vendor_name)
                                    <span class="text-[10px] text-indigo-700 font-semibold block mt-0.5">
                                        <i class="fa-solid fa-industry me-0.5"></i> {{ $order->vendor_name }}
                                    </span>
                                @endif
                            </td>

                            <!-- Customer Price & Payment -->
                            <td class="py-3 px-4 text-end font-mono">
                                <span class="text-[10.5px] text-slate-500 block">
                                    @ Rp {{ number_format($unitPrice, 0, ',', '.') }} / {{ $order->unit }}
                                </span>
                                <strong class="text-slate-900 block text-xs">
                                    Total: Rp {{ number_format($order->customer_price, 0, ',', '.') }}
                                </strong>
                                <span class="badge {{ $order->payment_status === 'PAID' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($order->payment_status === 'PARTIAL' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200') }} border text-[9.5px] mt-0.5">
                                    {{ $order->payment_method }} ({{ $order->payment_status === 'PAID' ? 'LUNAS' : ($order->payment_status === 'PARTIAL' ? 'DP' : 'BELUM BAYAR') }})
                                </span>
                            </td>

                            <!-- Vendor Cost & Shipping -->
                            <td class="py-3 px-4 text-end font-mono">
                                @if($order->total_cost > 0)
                                    <span class="text-[10.5px] text-slate-500 block">
                                        @ Rp {{ number_format($vendorUnitPrice, 0, ',', '.') }} / {{ $order->unit }}
                                    </span>
                                    <strong class="text-rose-700 block">
                                        Total: Rp {{ number_format($order->total_cost, 0, ',', '.') }}
                                    </strong>
                                    @if($order->shipping_cost > 0)
                                        <span class="text-[9.5px] text-slate-400 block">
                                            (Termasuk Ongkir Rp {{ number_format($order->shipping_cost, 0, ',', '.') }})
                                        </span>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Belum diinput</span>
                                @endif
                            </td>

                            <!-- Margin & Profit -->
                            <td class="py-3 px-4 text-end font-mono">
                                @if($order->total_cost > 0)
                                    <strong class="text-emerald-700 block">+ Rp {{ number_format($order->estimated_margin, 0, ',', '.') }}</strong>
                                    <span class="badge bg-emerald-100 text-emerald-800 text-[9.5px] font-bold font-mono">
                                        {{ $order->margin_percent }}%
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">-</span>
                                @endif
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3 px-4 text-center">
                                <span class="badge {{ $statusInfo['color'] }} border text-[10.5px] font-bold px-2 py-1 inline-flex items-center gap-1">
                                    <i class="fa-solid {{ $statusInfo['icon'] }}"></i>
                                    <span>{{ $statusInfo['label'] }}</span>
                                </span>
                                @if($order->status === 'rejected' && $order->rejection_reason)
                                    <span class="text-[10px] text-rose-600 block mt-1 max-w-xs truncate" title="{{ $order->rejection_reason }}">
                                        Alasan: {{ $order->rejection_reason }}
                                    </span>
                                @endif
                            </td>

                            <!-- Action Buttons by Stage -->
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    
                                    <!-- Open Lembar Kerja Odoo Form -->
                                    <button type="button" onclick="openOrderWorksheet({{ $order->id }})" 
                                            class="btn btn-xs btn-primary py-1 px-2.5 rounded-lg font-bold shadow-sm flex items-center gap-1">
                                        <i class="fa-solid fa-file-lines"></i>
                                        <span>Lembar Kerja</span>
                                    </button>

                                    <!-- Print Struk Customer -->
                                    <a href="{{ route('outsource-orders.receipt', $order->id) }}" target="_blank" 
                                       class="btn btn-xs btn-outline-secondary py-1 px-2 rounded-lg font-semibold" title="Cetak Struk Pesanan Customer">
                                        <i class="fa-solid fa-print"></i>
                                    </a>

                                    <!-- Completed Official Invoice Link -->
                                    @if($order->status === 'completed' && $order->transaction_id)
                                        <a href="{{ route('sales.receipt', $order->transaction_id) }}" target="_blank" 
                                           class="btn btn-xs btn-outline-success py-1 px-2 rounded-lg font-bold" title="Lihat Struk Penjualan POS Resmi">
                                            <i class="fa-solid fa-receipt"></i>
                                        </a>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                                Belum ada pesanan cetak di luar. Klik tombol <strong>"Pesanan Baru"</strong> di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: LEMBAR KERJA ODOO-STYLE FORM SHEET & DYNAMIC 4-STAGE PIPELINE      -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalWorksheet" tabindex="-1" aria-labelledby="modalWorksheetLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 980px;">
        <div class="modal-content rounded-2xl border-0 shadow-2xl overflow-hidden bg-slate-100">
            
            <!-- 1. ODOO CONTROL PANEL / TOP STATUSBAR (4-STAGE PIPELINE) -->
            <div class="bg-white border-b border-slate-200 px-5 py-3 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                
                <!-- Left Action Buttons (Odoo Header Action Bar) -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" onclick="saveWorksheetChanges(true)" class="btn btn-sm btn-primary rounded-lg font-bold px-3 shadow-xs">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                    </button>

                    <a href="#" id="odoo_btn_print_struk" target="_blank" class="btn btn-sm btn-outline-secondary rounded-lg font-semibold px-2.5">
                        <i class="fa-solid fa-print me-1"></i> Print Struk Customer
                    </a>

                    <div id="odoo_dynamic_action_buttons" class="flex items-center gap-1.5 flex-wrap">
                        <!-- Filled by JS depending on active stage -->
                    </div>
                </div>

                <!-- Right: ODOO STATUSBAR (Chevron Pipeline: Draft -> Pengajuan Direksi -> Proses QC -> Close) -->
                <div class="flex items-center justify-end w-full md:w-auto">
                    <div class="flex items-stretch border border-slate-300 rounded-lg overflow-hidden text-xs bg-slate-50 font-medium shadow-xs">
                        
                        <!-- Stage 1 Button -->
                        <button type="button" onclick="switchOdooStage(1)" id="stage_tab_1" 
                                class="odoo-stage-btn px-3.5 py-1.5 transition border-r border-slate-300 flex items-center gap-1 font-bold bg-slate-900 text-white">
                            <span>1. Draft Customer</span>
                        </button>
                        
                        <!-- Stage 2 Button -->
                        <button type="button" onclick="switchOdooStage(2)" id="stage_tab_2" 
                                class="odoo-stage-btn px-3.5 py-1.5 transition border-r border-slate-300 flex items-center gap-1 text-slate-600 hover:bg-slate-200">
                            <span>2. Pengajuan ke Direksi</span>
                        </button>
                        
                        <!-- Stage 3 Button -->
                        <button type="button" onclick="switchOdooStage(3)" id="stage_tab_3" 
                                class="odoo-stage-btn px-3.5 py-1.5 transition border-r border-slate-300 flex items-center gap-1 text-slate-600 hover:bg-slate-200">
                            <span>3. Proses QC</span>
                        </button>
                        
                        <!-- Stage 4 Button -->
                        <button type="button" onclick="switchOdooStage(4)" id="stage_tab_4" 
                                class="odoo-stage-btn px-3.5 py-1.5 transition flex items-center gap-1 text-slate-600 hover:bg-slate-200">
                            <span>4. Close (Selesai)</span>
                        </button>
                    </div>

                    <button type="button" class="btn-close ms-3 text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- 2. ODOO FORM SHEET CANVAS (DYNAMIC STAGE PANELS) -->
            <div class="p-4 md:p-6 overflow-y-auto max-h-[calc(85vh-90px)]">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
                    <input type="hidden" id="ws_order_id">

                    <!-- Document Header Bar -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center pb-4 border-b border-slate-200 gap-2">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block">Lembar Kerja / Cetak di Luar (Vendor Outsource)</span>
                            <div class="flex items-center gap-2.5 mt-0.5">
                                <h2 class="text-2xl font-black text-slate-900 font-mono tracking-tight mb-0" id="ws_order_number_title">OUT-00000000-0000</h2>
                                <span id="ws_order_status_badge" class="badge bg-amber-100 text-amber-800 border border-amber-200 text-xs px-2.5 py-1">1. Draft Customer</span>
                            </div>
                        </div>

                        <div class="text-start md:text-end text-xs text-slate-500 space-y-0.5">
                            <div>Tanggal: <strong id="ws_order_date" class="text-slate-800">-</strong></div>
                            <div>Kasir: <strong id="ws_order_cashier" class="text-slate-800">-</strong> (<span id="ws_order_branch">Cabang</span>)</div>
                        </div>
                    </div>

                    <!-- ===================================================================== -->
                    <!-- PANEL TAHAP 1: DRAFT CUSTOMER (PRODUK, HARGA JUAL & PELANGGAN)        -->
                    <!-- ===================================================================== -->
                    <div id="odoo_panel_1" class="odoo-stage-panel active space-y-5">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-file-invoice text-blue-600"></i> Tahap 1: Pesanan Pelanggan & Rincian Harga Satuan
                            </span>
                            <span class="badge bg-blue-50 text-blue-700 border border-blue-200 text-[10.5px]">Draft Order Kasir</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Left: Customer Details -->
                            <div class="space-y-3.5 bg-slate-50/70 p-4 rounded-xl border border-slate-200">
                                <h6 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b pb-1.5 text-blue-800">
                                    <i class="fa-solid fa-user me-1 text-blue-600"></i> Data Pelanggan
                                </h6>

                                <div class="grid grid-cols-3 gap-2 items-center">
                                    <label class="text-xs font-semibold text-slate-600">Nama Pelanggan <span class="text-rose-500">*</span></label>
                                    <div class="col-span-2">
                                        <input type="text" id="ws_customer_name" class="form-control form-control-sm text-xs font-semibold" placeholder="Nama pelanggan">
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-2 items-center">
                                    <label class="text-xs font-semibold text-slate-600">No. WhatsApp</label>
                                    <div class="col-span-2">
                                        <input type="text" id="ws_customer_phone" class="form-control form-control-sm text-xs font-mono" placeholder="08xxxxxxxx">
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-2 items-center">
                                    <label class="text-xs font-semibold text-slate-600">Metode Bayar</label>
                                    <div class="col-span-2">
                                        <select id="ws_payment_method" class="form-select form-select-sm text-xs font-bold">
                                            <option value="Cash">💵 Tunai (Cash)</option>
                                            <option value="Transfer">🏦 Transfer Bank</option>
                                            <option value="QRIS">📱 QRIS</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-2 items-center">
                                    <label class="text-xs font-semibold text-slate-600">Opsi Bayar</label>
                                    <div class="col-span-2">
                                        <select id="ws_payment_type" onchange="handleWsPaymentTypeChange(this.value)" class="form-select form-select-sm text-xs font-bold">
                                            <option value="PAID">🟢 Lunas (100%)</option>
                                            <option value="DP">🟡 Uang Muka (DP)</option>
                                            <option value="UNPAID">🔴 Belum Bayar (Pelunasan Nanti)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-2 items-center">
                                    <label class="text-xs font-semibold text-slate-600">Nominal Bayar</label>
                                    <div class="col-span-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text text-[11px] bg-white font-mono">Rp</span>
                                            <input type="number" id="ws_paid_amount" min="0" step="1000" oninput="handleWsPaidAmountInput()" class="form-control form-control-sm text-xs font-mono font-bold" placeholder="0">
                                        </div>
                                        <div class="flex justify-between items-center text-[10.5px] mt-1">
                                            <span class="text-slate-500">Sisa Tagihan:</span>
                                            <span id="ws_remaining_amount_display" class="font-mono font-bold text-emerald-700">Rp 0 (Lunas)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Job Details & Specifications -->
                            <div class="space-y-3.5 bg-slate-50/70 p-4 rounded-xl border border-slate-200">
                                <h6 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b pb-1.5 text-blue-800">
                                    <i class="fa-solid fa-cube me-1 text-blue-600"></i> Pekerjaan / Produk Cetak
                                </h6>

                                <div class="grid grid-cols-3 gap-2 items-center">
                                    <label class="text-xs font-semibold text-slate-600">Pekerjaan <span class="text-rose-500">*</span></label>
                                    <div class="col-span-2">
                                        <input type="text" id="ws_job_title" class="form-control form-control-sm text-xs font-bold text-slate-900" placeholder="Misal: Cetak Buku Agenda, Brosur A4">
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-2 items-start">
                                    <label class="text-xs font-semibold text-slate-600 pt-1">Spesifikasi</label>
                                    <div class="col-span-2">
                                        <textarea id="ws_description" rows="3" class="form-control form-control-sm text-xs" placeholder="Ukuran kertas, laminasi doff/glossy, warna cover..."></textarea>
                                    </div>
                                </div>

                                <!-- Live Calculation Pill -->
                                <div class="p-3 bg-blue-100/60 border border-blue-200 rounded-xl flex justify-between items-center text-xs">
                                    <div>
                                        <span class="text-[10px] text-blue-900 uppercase font-bold block">Total Nilai Tagihan Customer:</span>
                                        <span id="ws_calc_preview_pill" class="font-mono text-slate-700 font-semibold">1 pcs x Rp 0</span>
                                    </div>
                                    <strong id="ws_total_omset_display" class="font-mono text-blue-800 text-base">Rp 0</strong>
                                </div>
                            </div>

                        </div>

                        <!-- Order Line Table for Step 1 -->
                        <div class="space-y-2 pt-1">
                            <h6 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-1">
                                <i class="fa-solid fa-tag me-1 text-slate-700"></i> Penetapan Harga Jual Satuan
                            </h6>
                            <div class="border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                                <table class="table table-bordered table-sm align-middle mb-0 text-xs">
                                    <thead class="bg-slate-100 text-slate-700 uppercase tracking-wider text-[10px] font-bold">
                                        <tr>
                                            <th style="width: 40%;">Item / Pekerjaan</th>
                                            <th style="width: 15%;" class="text-center">Jumlah / Qty</th>
                                            <th style="width: 15%;" class="text-center">Satuan</th>
                                            <th style="width: 30%;" class="text-end">Harga Jual Satuan (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="bg-white">
                                            <td>
                                                <strong id="ws_line_job_title" class="text-slate-900 block">-</strong>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" id="ws_qty" min="1" value="1" oninput="calcOdooTotals()" class="form-control form-control-sm text-xs font-mono font-bold text-center py-1">
                                            </td>
                                            <td class="text-center">
                                                <input type="text" id="ws_unit" value="pcs" oninput="calcOdooTotals()" class="form-control form-control-sm text-xs text-center py-1" placeholder="pcs">
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text text-[11px] bg-slate-50 font-mono">Rp</span>
                                                    <input type="number" id="ws_customer_unit_price" min="0" step="500" oninput="calcOdooTotals()" class="form-control font-mono font-bold text-blue-700 text-xs text-end" placeholder="0">
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ===================================================================== -->
                    <!-- PANEL TAHAP 2: PENGAJUAN KE DIREKSI (HPP VENDOR & PERSETUJUAN OWNER)  -->
                    <!-- ===================================================================== -->
                    <div id="odoo_panel_2" class="odoo-stage-panel space-y-5">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-hand-holding-dollar text-indigo-600"></i> Tahap 2: Input Modal Vendor (HPP) & Pengajuan ke Direksi
                            </span>
                            <span id="ws_direksi_badge" class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10.5px]">Menunggu ACC</span>
                        </div>

                        <!-- Vendor Details & Cost Inputs -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Vendor Details Form -->
                            <div class="space-y-3.5 bg-slate-50/70 p-4 rounded-xl border border-slate-200">
                                <h6 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b pb-1.5 text-indigo-800">
                                    <i class="fa-solid fa-industry me-1 text-indigo-600"></i> Data Vendor Rekanan
                                </h6>

                                <div class="grid grid-cols-3 gap-2 items-center">
                                    <label class="text-xs font-semibold text-slate-600">Nama Vendor <span class="text-rose-500">*</span></label>
                                    <div class="col-span-2">
                                        <input type="text" id="ws_vendor_name" class="form-control form-control-sm text-xs font-semibold" placeholder="Misal: Percetakan Prima Offset">
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-2 items-center">
                                    <label class="text-xs font-semibold text-slate-600">Kontak Vendor</label>
                                    <div class="col-span-2">
                                        <input type="text" id="ws_vendor_phone" class="form-control form-control-sm text-xs font-mono" placeholder="08xxxxxxxx">
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-2 items-start">
                                    <label class="text-xs font-semibold text-slate-600 pt-1">Catatan Vendor</label>
                                    <div class="col-span-2">
                                        <textarea id="ws_vendor_notes" rows="3" class="form-control form-control-sm text-xs" placeholder="Spesifikasi ke vendor, deadline pengerjaan..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Cost Breakdown & Unit Price -->
                            <div class="space-y-3.5 bg-slate-50/70 p-4 rounded-xl border border-slate-200">
                                <h6 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b pb-1.5 text-rose-800">
                                    <i class="fa-solid fa-calculator me-1 text-rose-600"></i> Biaya Modal & Ongkos Kirim
                                </h6>

                                <div class="grid grid-cols-3 gap-2 items-center">
                                    <label class="text-xs font-semibold text-slate-600">Modal Satuan <span class="text-rose-500">*</span></label>
                                    <div class="col-span-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text text-[11px] bg-rose-50 text-rose-700 font-mono">Rp</span>
                                            <input type="number" id="ws_vendor_unit_price" min="0" step="500" oninput="calcOdooTotals()" class="form-control form-control-sm text-xs font-mono font-bold text-rose-700 text-end" placeholder="0">
                                            <span class="input-group-text text-xs text-slate-500 bg-white font-mono" id="ws_vendor_unit_label">/ pcs</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-2 items-center">
                                    <label class="text-xs font-semibold text-slate-600">Ongkos Kirim</label>
                                    <div class="col-span-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text text-[11px] bg-white font-mono">Rp</span>
                                            <input type="number" id="ws_shipping_cost" min="0" step="1000" oninput="calcOdooTotals()" class="form-control form-control-sm text-xs font-mono text-end" placeholder="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- Subtotal Modal Vendor Pill -->
                                <div class="p-3 bg-rose-100/60 border border-rose-200 rounded-xl flex justify-between items-center text-xs">
                                    <div>
                                        <span class="text-[10px] text-rose-900 uppercase font-bold block">Total HPP Modal (Vendor + Ongkir):</span>
                                        <span id="ws_vendor_calc_preview_pill" class="font-mono text-slate-700 font-semibold">1 pcs x Rp 0</span>
                                    </div>
                                    <strong id="ws_sum_total_hpp" class="font-mono text-rose-800 text-base">Rp 0</strong>
                                </div>
                            </div>

                        </div>

                        <!-- Live Margin & Approval Card (HIGH CONTRAST & CLEAR READABILITY) -->
                        <div class="p-5 rounded-2xl shadow-sm border border-slate-700 space-y-3.5" style="background-color: #0f172a !important; color: #ffffff !important;">
                            <div class="flex justify-between items-center pb-2.5 border-b border-slate-700 text-xs">
                                <span class="font-black uppercase tracking-wider flex items-center gap-1.5" style="color: #f8fafc !important;">
                                    <i class="fa-solid fa-chart-line text-amber-400"></i> SIMULASI MARGIN & KEUNTUNGAN
                                </span>
                                <span id="ws_sum_margin_badge" class="badge bg-emerald-500 text-white font-mono font-bold text-xs px-3 py-1 shadow-xs">Margin 0%</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-center">
                                <div class="p-3.5 rounded-xl border border-blue-500/50 shadow-xs" style="background-color: #1e293b !important;">
                                    <span class="text-[11px] font-bold block uppercase tracking-wider" style="color: #93c5fd !important;">Harga Jual Customer</span>
                                    <strong id="ws_sum_omset" class="font-mono font-black text-xl block mt-1" style="color: #60a5fa !important;">Rp 0</strong>
                                </div>
                                <div class="p-3.5 rounded-xl border border-rose-500/50 shadow-xs" style="background-color: #1e293b !important;">
                                    <span class="text-[11px] font-bold block uppercase tracking-wider" style="color: #fca5a5 !important;">Total Modal (HPP + Ongkir)</span>
                                    <strong id="ws_sum_hpp_card" class="font-mono font-black text-xl block mt-1" style="color: #f87171 !important;">Rp 0</strong>
                                </div>
                                <div class="p-3.5 rounded-xl border border-emerald-500/50 shadow-xs" style="background-color: #1e293b !important;">
                                    <span class="text-[11px] font-bold block uppercase tracking-wider" style="color: #86efac !important;">Estimasi Laba Bersih</span>
                                    <strong id="ws_sum_profit" class="font-mono font-black text-2xl block mt-1" style="color: #4ade80 !important;">Rp 0</strong>
                                </div>
                            </div>

                            <!-- Approval status info & logs -->
                            <div id="ws_approval_log_box" class="pt-2.5 border-t border-slate-700 text-xs flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
                                <span id="ws_approval_log_text" class="font-semibold" style="color: #e2e8f0 !important;">
                                    <i class="fa-solid fa-clock me-1 text-amber-400"></i> Menunggu persetujuan (ACC) dari Direksi/Owner.
                                </span>
                                <div id="ws_owner_action_btns" class="flex items-center gap-2"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ===================================================================== -->
                    <!-- PANEL TAHAP 3: PROSES QC (DI VENDOR & BARANG SAMPAI)                  -->
                    <!-- ===================================================================== -->
                    <div id="odoo_panel_3" class="odoo-stage-panel space-y-5">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-clipboard-check text-purple-600"></i> Tahap 3: Pengerjaan di Vendor & Quality Control (QC)
                            </span>
                            <span id="ws_qc_status_badge" class="badge bg-blue-100 text-blue-800 border border-blue-200 text-[10.5px]">Sedang Dikerjakan</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Vendor & Job Progress Summary -->
                            <div class="space-y-3 bg-slate-50/70 p-4 rounded-xl border border-slate-200 text-xs">
                                <h6 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b pb-1.5 text-purple-800">
                                    <i class="fa-solid fa-truck-ramp-box me-1 text-purple-600"></i> Ringkasan Pengerjaan
                                </h6>
                                <div class="flex justify-between py-1 border-b">
                                    <span class="text-slate-500">Vendor:</span>
                                    <strong id="ws_qc_vendor_name" class="text-slate-900">-</strong>
                                </div>
                                <div class="flex justify-between py-1 border-b">
                                    <span class="text-slate-500">Pekerjaan:</span>
                                    <strong id="ws_qc_job_title" class="text-slate-900">-</strong>
                                </div>
                                <div class="flex justify-between py-1">
                                    <span class="text-slate-500">Total Modal HPP:</span>
                                    <strong id="ws_qc_total_hpp" class="text-rose-700 font-mono">-</strong>
                                </div>
                            </div>

                            <!-- Quality Control Notes Form -->
                            <div class="space-y-3 bg-slate-50/70 p-4 rounded-xl border border-slate-200">
                                <h6 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b pb-1.5 text-purple-800">
                                    <i class="fa-solid fa-magnifying-glass-check me-1 text-purple-600"></i> Pengecekan Kualitas Fisik (QC)
                                </h6>
                                <div>
                                    <label class="text-xs font-semibold text-slate-700 mb-1 block">Catatan Pemeriksaan Barang Sampai</label>
                                    <textarea id="ws_qc_notes" rows="3" class="form-control form-control-sm text-xs" placeholder="Hasil cek fisik: cetakan tajam, foil presisi, jumlah lengkap..."></textarea>
                                </div>
                            </div>

                        </div>

                        <div id="ws_qc_action_banner" class="p-4 bg-purple-50 border border-purple-200 rounded-xl flex justify-between items-center text-xs">
                            <div class="text-purple-900">
                                <strong>Status:</strong> <span id="ws_qc_banner_text">Barang sedang dalam proses cetak di vendor.</span>
                            </div>
                            <div id="ws_qc_action_slot">
                                <button type="button" onclick="submitWsPassQc()" class="btn btn-sm btn-purple rounded-lg font-bold text-white bg-purple-700 hover:bg-purple-800 px-3.5 shadow-xs">
                                    <i class="fa-solid fa-clipboard-check me-1"></i> Konfirmasi Barang Sampai & Lolos QC
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ===================================================================== -->
                    <!-- PANEL TAHAP 4: CLOSE (SELESAI & INTEGRASI PEMBUKUAN PENJUALAN POS)    -->
                    <!-- ===================================================================== -->
                    <div id="odoo_panel_4" class="odoo-stage-panel space-y-5">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i> Tahap 4: Closing Pesanan & Pembukuan Kas Penjualan
                            </span>
                            <span id="ws_closing_status_badge" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10.5px]">Siap Closing</span>
                        </div>

                        <!-- 1. RINCIAN KEUNTUNGAN & MARGIN (PROFIT STATEMENT TABLE) -->
                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                            <div class="px-4 py-3 bg-slate-900 text-white flex justify-between items-center" style="background-color: #0f172a !important; color: #ffffff !important;">
                                <span class="text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fa-solid fa-file-invoice-dollar text-amber-400"></i> Rincian Realisasi Keuntungan Toko (Profit Statement)
                                </span>
                                <span id="ws_closing_margin_pill" class="badge bg-emerald-500 text-white font-mono font-bold text-xs px-2.5 py-1 shadow-xs">Margin 0%</span>
                            </div>

                            <div class="p-4 space-y-4 text-xs">
                                <!-- 1. Omset Penjualan -->
                                <div>
                                    <div class="flex justify-between items-center font-bold text-slate-800 pb-1.5 border-b border-slate-200">
                                        <span class="flex items-center gap-1.5 text-blue-800 uppercase tracking-wide">
                                            <i class="fa-solid fa-arrow-trend-up text-blue-600"></i> 1. Pendapatan Penjualan Customer (Omset)
                                        </span>
                                        <span id="ws_table_tot_omset" class="font-mono text-blue-800 font-extrabold text-sm">Rp 0</span>
                                    </div>
                                    <div class="py-2.5 px-3.5 bg-blue-50/60 rounded-xl mt-1.5 flex justify-between items-center border border-blue-100">
                                        <div>
                                            <strong id="ws_table_job_title" class="text-slate-900 block text-xs">Item Cetak</strong>
                                            <span id="ws_table_cust_calc_breakdown" class="text-slate-500 text-[11px] font-mono">1 pcs x @ Rp 0</span>
                                        </div>
                                        <strong id="ws_table_cust_subtotal" class="font-mono text-blue-900 font-bold text-sm">Rp 0</strong>
                                    </div>
                                </div>

                                <!-- 2. Modal HPP & Pengeluaran -->
                                <div>
                                    <div class="flex justify-between items-center font-bold text-slate-800 pb-1.5 border-b border-slate-200">
                                        <span class="flex items-center gap-1.5 text-rose-800 uppercase tracking-wide">
                                            <i class="fa-solid fa-arrow-trend-down text-rose-600"></i> 2. Pengeluaran Modal Kerja (HPP Vendor & Ekspedisi)
                                        </span>
                                        <span id="ws_table_tot_hpp" class="font-mono text-rose-800 font-extrabold text-sm">Rp 0</span>
                                    </div>
                                    <div class="space-y-1.5 mt-1.5">
                                        <div class="py-2.5 px-3.5 bg-rose-50/60 rounded-xl flex justify-between items-center border border-rose-100">
                                            <div>
                                                <strong class="text-slate-900 block text-xs">Biaya Cetak Vendor (<span id="ws_table_vendor_name">-</span>)</strong>
                                                <span id="ws_table_vendor_calc_breakdown" class="text-slate-500 text-[11px] font-mono">1 pcs x @ Rp 0</span>
                                            </div>
                                            <strong id="ws_table_vendor_cost" class="font-mono text-rose-700 font-bold text-sm">- Rp 0</strong>
                                        </div>
                                        <div class="py-2 px-3.5 bg-rose-50/60 rounded-xl flex justify-between items-center border border-rose-100">
                                            <div>
                                                <strong class="text-slate-900 block text-xs">Biaya Pengiriman / Ongkir</strong>
                                                <span class="text-slate-500 text-[11px]">Pengiriman vendor ke cabang</span>
                                            </div>
                                            <strong id="ws_table_shipping_cost" class="font-mono text-rose-700 font-bold text-sm">- Rp 0</strong>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. Realisasi Laba Bersih Real -->
                                <div class="p-3.5 bg-emerald-50 rounded-xl border border-emerald-300 flex justify-between items-center" style="background-color: #ecfdf5 !important; border-color: #6ee7b7 !important;">
                                    <div>
                                        <span class="text-[10.5px] uppercase font-black text-emerald-900 tracking-wider block">Realisasi Keuntungan Bersih (Net Profit Toko):</span>
                                        <span id="ws_table_profit_formula" class="text-xs text-slate-600 font-mono">Omset (Rp 0) - Total HPP (Rp 0)</span>
                                    </div>
                                    <div class="text-end">
                                        <strong id="ws_table_net_profit" class="font-mono font-black text-emerald-800 text-xl block" style="color: #047857 !important;">+ Rp 0</strong>
                                        <span id="ws_table_margin_pct" class="text-[11px] font-bold text-emerald-700 font-mono">Margin: 0%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. STATUS PEMBAYARAN & PELUNASAN CUSTOMER -->
                        <div id="ws_closing_settlement_section" class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-xs space-y-3.5">
                            <div class="flex justify-between items-center pb-2 border-b border-slate-200">
                                <span class="font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider">
                                    <i class="fa-solid fa-wallet text-blue-600"></i> Status Pembayaran & Pelunasan Customer
                                </span>
                                <span id="ws_closing_pay_badge" class="badge bg-amber-200 text-amber-900 border border-amber-300 font-bold">DP (Belum Lunas)</span>
                            </div>

                            <!-- 3 Mini Boxes -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-center">
                                <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs">
                                    <span class="text-[10.5px] text-slate-500 uppercase block font-bold">Total Nilai Tagihan</span>
                                    <strong id="ws_closing_tot_tagihan" class="font-mono text-slate-900 text-base block mt-0.5">Rp 0</strong>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs">
                                    <span class="text-[10.5px] text-slate-500 uppercase block font-bold">Telah Dibayar (DP Awal)</span>
                                    <strong id="ws_closing_dp_paid" class="font-mono text-emerald-700 text-base block mt-0.5">Rp 0</strong>
                                </div>
                                <div id="ws_closing_sisa_box" class="p-3 bg-amber-50 rounded-xl border border-amber-300 shadow-2xs">
                                    <span class="text-[10.5px] text-amber-900 uppercase block font-black">Sisa Wajib Dilunasi</span>
                                    <strong id="ws_closing_sisa_tagihan" class="font-mono text-amber-900 text-base block mt-0.5">Rp 0</strong>
                                </div>
                            </div>

                            <!-- Notice jika Full Payment sejak awal -->
                            <div id="ws_closing_full_paid_notice" class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-900 flex items-start gap-2.5">
                                <i class="fa-solid fa-circle-check text-emerald-600 text-base mt-0.5"></i>
                                <div>
                                    <strong class="block">Pembayaran Sudah Lunas Penuh (100%) Sejak Awal.</strong>
                                    <span class="text-[11px] text-emerald-700">Customer telah memegang struk lunas dari awal pemesanan. Tidak perlu input pelunasan ataupun cetak ulang struk saat closing.</span>
                                </div>
                            </div>

                            <!-- Form Input Pelunasan jika sebelumnya DP -->
                            <div id="ws_closing_settlement_form" class="p-3.5 bg-amber-100/60 rounded-xl border border-amber-200 grid grid-cols-1 md:grid-cols-2 gap-3 items-center">
                                <div>
                                    <label class="text-xs font-bold text-amber-950 mb-1 block">Metode Pembayaran Pelunasan</label>
                                    <select id="ws_settlement_method" class="form-select form-select-sm text-xs font-bold bg-white">
                                        <option value="Cash">💵 Tunai / Cash</option>
                                        <option value="Transfer Bank">🏦 Transfer Bank</option>
                                        <option value="QRIS">📱 QRIS</option>
                                        <option value="EDC / Debit">💳 Kartu Debit / EDC</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-amber-950 mb-1 block">Nominal Pelunasan Diterima Saat Ambil Barang</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white font-mono text-[11px] font-bold">Rp</span>
                                        <input type="number" id="ws_settlement_paid_input" min="0" step="1000" class="form-control form-control-sm text-xs font-mono font-bold text-slate-900 bg-white" placeholder="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. TOMBOL AKSI CLOSING & HASIL TRANSAKSI -->
                        <div id="ws_closing_action_box" class="p-5 bg-white rounded-2xl border border-slate-200 space-y-3 text-center shadow-xs">
                            <div id="ws_closing_prompt_text">
                                <h6 class="text-sm font-bold text-slate-800 mb-1" id="ws_closing_prompt_title">Pesanan Siap Ditutup & Diserahkan ke Customer</h6>
                                <p class="text-xs text-slate-500 mb-0" id="ws_closing_prompt_subtitle">Klik tombol di bawah untuk membukukan transaksi ke <strong>Laporan Penjualan Harian & Kas POS</strong>.</p>
                            </div>
                            
                            <div id="ws_closing_btn_slot" class="pt-1">
                                <button type="button" id="ws_btn_do_close" onclick="submitWsCloseOrder()" class="btn btn-md btn-success rounded-xl font-bold px-5 shadow-sm">
                                    <i class="fa-solid fa-cash-register me-1.5"></i> Closing & Lunasi Pembayaran
                                </button>
                            </div>

                            <div id="ws_completed_invoice_box" class="hidden pt-2 space-y-2">
                                <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs px-3.5 py-1.5 font-bold">
                                    <i class="fa-solid fa-circle-check me-1"></i> Transaksi Penjualan Telah Selesai: <strong id="ws_completed_inv_number">-</strong>
                                </span>
                                <div class="flex justify-center items-center gap-2 pt-1">
                                    <a href="#" id="ws_btn_open_customer_receipt" target="_blank" class="btn btn-sm btn-outline-secondary font-bold rounded-xl px-3.5">
                                        <i class="fa-solid fa-print me-1"></i> Cetak Ulang Struk Pelunasan
                                    </a>
                                    <a href="#" id="ws_btn_open_pos_inv" target="_blank" class="btn btn-sm btn-outline-success font-bold rounded-xl px-3.5">
                                        <i class="fa-solid fa-receipt me-1"></i> Buka Faktur POS Resmi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- 3. ODOO FOOTER (PREV / NEXT NAVIGATION) -->
            <div class="px-6 py-3.5 bg-white border-t border-slate-200 flex justify-between items-center">
                <button type="button" id="odoo_btn_prev" onclick="navigateOdooStage(-1)" class="btn btn-sm btn-outline-secondary rounded-lg font-bold px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Sebelumnya
                </button>

                <div class="text-xs text-slate-400 font-semibold" id="odoo_stage_indicator_text">
                    Tahap 1 dari 4: Draft Customer
                </div>

                <button type="button" id="odoo_btn_next" onclick="navigateOdooStage(1)" class="btn btn-sm btn-primary rounded-lg font-bold px-4 shadow-xs">
                    <span>Seterusnya</span> <i class="fa-solid fa-arrow-right ms-1"></i>
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: BUAT PESANAN CUSTOMER BARU (STAGE 1 DRAFT)                         -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalCreateOrder" tabindex="-1" aria-labelledby="modalCreateOrderLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
        <div class="modal-content rounded-2xl border-0 shadow-2xl overflow-hidden bg-white">
            
            <!-- Header -->
            <div class="px-5 py-3.5 bg-slate-900 text-white flex justify-between items-center">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold">
                        <i class="fa-solid fa-file-invoice text-sm"></i>
                    </div>
                    <div>
                        <h6 class="text-sm font-bold mb-0 text-white">Pesanan Cetak di Luar Baru</h6>
                        <span class="text-[11px] text-slate-400">Input data pesanan & harga satuan customer</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formCreateOrder" onsubmit="submitCreateOrder(event)" class="p-5 space-y-4 bg-slate-50">
                <input type="hidden" name="customer_price" id="create_customer_price" value="0">

                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                            Nama Pekerjaan / Produk <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="job_title" id="create_job_title" required 
                               class="form-control text-xs font-semibold py-2 rounded-lg" 
                               placeholder="Misal: Cetak Buku Agenda Hardcover Foil, Banner Outdoor">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                Jumlah / Qty <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" name="qty" id="create_qty" value="1" min="1" required 
                                   oninput="calcCreateCustomerPrice()"
                                   class="form-control text-xs font-mono font-bold text-center py-2 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Satuan</label>
                            <input type="text" name="unit" id="create_unit" value="pcs" 
                                   oninput="calcCreateCustomerPrice()"
                                   class="form-control text-xs text-center py-2 rounded-lg" placeholder="pcs, rim, buku">
                        </div>
                    </div>

                    <!-- HARGA SATUAN -->
                    <div class="bg-blue-50/50 p-3 rounded-xl border border-blue-200 space-y-1.5">
                        <label class="block text-xs font-bold text-blue-900 uppercase">
                            <i class="fa-solid fa-tag text-blue-600 me-1"></i> Harga Jual Satuan (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text font-bold text-xs bg-white text-blue-700 border-blue-200">Rp</span>
                            <input type="number" name="customer_unit_price" id="create_unit_price" min="0" step="500" required 
                                   oninput="calcCreateCustomerPrice()" 
                                   class="form-control font-mono font-black text-blue-700 text-sm border-blue-200" placeholder="0">
                            <span class="input-group-text text-xs text-slate-500 bg-white border-blue-200 font-mono" id="create_unit_label">/ pcs</span>
                        </div>
                        <div class="flex justify-between items-center text-xs text-slate-600 pt-1">
                            <span id="create_calc_preview_text">1 pcs x Rp 0</span>
                            <strong id="create_total_display" class="font-mono text-blue-800 text-sm">Total: Rp 0</strong>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Spesifikasi & Catatan Cetak</label>
                        <textarea name="description" id="create_description" rows="2" class="form-control text-xs rounded-lg" placeholder="Ukuran, bahan, warna, finishing..."></textarea>
                    </div>
                </div>

                <!-- Customer & Payment -->
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Customer <span class="text-rose-500">*</span></label>
                            <input type="text" name="customer_name" id="create_customer_name" required class="form-control text-xs py-2 rounded-lg" placeholder="Nama pelanggan">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">No. WhatsApp / HP</label>
                            <input type="text" name="customer_phone" id="create_customer_phone" class="form-control text-xs font-mono py-2 rounded-lg" placeholder="08xxxxxxxx">
                        </div>
                    </div>

                    <!-- Payment Method, Payment Type (LUNAS/DP/TEMPO), & Paid Amount -->
                    <div class="grid grid-cols-3 gap-2 pt-1 border-t">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Metode Bayar</label>
                            <select name="payment_method" id="create_payment_method" class="form-select form-select-sm text-xs font-bold rounded-lg">
                                <option value="Cash">Tunai (Cash)</option>
                                <option value="Transfer">Transfer Bank</option>
                                <option value="QRIS">QRIS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Opsi Bayar</label>
                            <select name="payment_type" id="create_payment_type" onchange="handleCreatePaymentTypeChange(this.value)" class="form-select form-select-sm text-xs font-bold rounded-lg">
                                <option value="PAID">🟢 Lunas (100%)</option>
                                <option value="DP">🟡 Uang Muka (DP)</option>
                                <option value="UNPAID">🔴 Belum Bayar</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Nominal Bayar (Rp)</label>
                            <input type="number" name="paid_amount" id="create_paid_amount" min="0" step="1000" class="form-control form-control-sm text-xs font-mono font-bold rounded-lg" placeholder="0">
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-2">
                    <button type="button" class="btn btn-sm btn-light rounded-lg font-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitCreateOrder" class="btn btn-sm btn-primary rounded-lg font-bold shadow-sm px-4">
                        <i class="fa-solid fa-check me-1"></i> Simpan & Cetak Struk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentActiveStageIndex = 1; // 1: Draft, 2: Pengajuan Direksi, 3: Proses QC, 4: Close
let currentWsOrder = null;
const isOwnerOrSuper = {{ ($isOwnerOrSuper || auth()->user()->isManager()) ? 'true' : 'false' }};

// --- OPEN ODOO WORKSHEET ---
function openOrderWorksheet(orderId) {
    fetch(`/cetak-luar/${orderId}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.order) {
            currentWsOrder = res.order;
            populateOdooWorksheet(currentWsOrder);

            // Determine initial active stage
            let initialStage = 1;
            if (currentWsOrder.status === 'pending_approval' || currentWsOrder.status === 'rejected') {
                initialStage = 2;
            } else if (currentWsOrder.status === 'in_production' || currentWsOrder.status === 'qc_passed') {
                initialStage = 3;
            } else if (currentWsOrder.status === 'completed') {
                initialStage = 4;
            } else if (currentWsOrder.vendor_cost > 0) {
                initialStage = 2;
            }

            switchOdooStage(initialStage);

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalWorksheet'));
            modal.show();
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Pesanan tidak ditemukan' });
        }
    })
    .catch(err => {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    });
}

function populateOdooWorksheet(order) {
    document.getElementById('ws_order_id').value = order.id;
    document.getElementById('ws_order_number_title').innerText = order.order_number;
    document.getElementById('ws_order_date').innerText = new Date(order.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    document.getElementById('ws_order_cashier').innerText = order.user ? (order.user.full_name || order.user.username) : 'Kasir';
    document.getElementById('ws_order_branch').innerText = order.branch ? order.branch.nama_cabang : 'Pusat';
    document.getElementById('odoo_btn_print_struk').href = `/cetak-luar/${order.id}/receipt`;

    // Stage 1 Fields (Customer & Job)
    document.getElementById('ws_customer_name').value = order.customer_name || '';
    document.getElementById('ws_customer_phone').value = order.customer_phone || '';
    document.getElementById('ws_job_title').value = order.job_title || '';
    document.getElementById('ws_line_job_title').innerText = order.job_title || 'Item Cetak';
    document.getElementById('ws_description').value = order.description || '';
    document.getElementById('ws_payment_method').value = order.payment_method || 'Cash';
    document.getElementById('ws_paid_amount').value = order.paid_amount || 0;

    // Payment Type Option
    const custPrice = parseFloat(order.customer_price) || 0;
    const paidAmt = parseFloat(order.paid_amount) || 0;
    const paySelect = document.getElementById('ws_payment_type');
    if (order.payment_status === 'PAID' || paidAmt >= custPrice) {
        paySelect.value = 'PAID';
    } else if (order.payment_status === 'PARTIAL' || paidAmt > 0) {
        paySelect.value = 'DP';
    } else {
        paySelect.value = 'UNPAID';
    }

    document.getElementById('ws_qty').value = order.qty || 1;
    document.getElementById('ws_unit').value = order.unit || 'pcs';

    const custUnitPrice = order.customer_unit_price > 0 ? order.customer_unit_price : (order.qty > 0 ? (order.customer_price / order.qty) : order.customer_price);
    document.getElementById('ws_customer_unit_price').value = custUnitPrice;

    // Stage 2 Fields (Vendor & HPP)
    document.getElementById('ws_vendor_name').value = order.vendor_name || '';
    document.getElementById('ws_vendor_phone').value = order.vendor_phone || '';
    const vendUnitPrice = order.vendor_unit_price > 0 ? order.vendor_unit_price : (order.qty > 0 && order.vendor_cost > 0 ? (order.vendor_cost / order.qty) : '');
    document.getElementById('ws_vendor_unit_price').value = vendUnitPrice;
    document.getElementById('ws_shipping_cost').value = order.shipping_cost > 0 ? order.shipping_cost : '';
    document.getElementById('ws_vendor_notes').value = order.vendor_notes || '';

    // Top Status Badge
    const badgeTop = document.getElementById('ws_order_status_badge');
    const statusMap = {
        'draft_customer': { text: '1. Draft Customer', cls: 'badge bg-amber-100 text-amber-900 border border-amber-300 text-xs px-2.5 py-1 font-bold' },
        'pending_approval': { text: '2. Menunggu ACC Direksi', cls: 'badge bg-indigo-100 text-indigo-900 border border-indigo-300 text-xs px-2.5 py-1 font-bold' },
        'in_production': { text: '3. Sedang Dikerjakan (QC)', cls: 'badge bg-purple-100 text-purple-900 border border-purple-300 text-xs px-2.5 py-1 font-bold' },
        'qc_passed': { text: '3. Lolos QC (Siap Close)', cls: 'badge bg-blue-100 text-blue-900 border border-blue-300 text-xs px-2.5 py-1 font-bold' },
        'completed': { text: '4. Selesai (Closed)', cls: 'badge bg-emerald-100 text-emerald-900 border border-emerald-300 text-xs px-2.5 py-1 font-bold' },
        'rejected': { text: 'Ditolak Direksi', cls: 'badge bg-rose-100 text-rose-900 border border-rose-300 text-xs px-2.5 py-1 font-bold' }
    };
    if (badgeTop && statusMap[order.status]) {
        badgeTop.innerText = statusMap[order.status].text;
        badgeTop.className = statusMap[order.status].cls;
    }

    // Stage 2 Status Badge & Log Text
    const direksiBadge = document.getElementById('ws_direksi_badge');
    const approvalLogText = document.getElementById('ws_approval_log_text');
    if (order.status === 'draft_customer') {
        if (direksiBadge) {
            direksiBadge.innerText = 'Draft (Belum Diajukan)';
            direksiBadge.className = 'badge bg-amber-100 text-amber-900 border border-amber-300 text-xs font-bold';
        }
        if (approvalLogText) {
            approvalLogText.innerHTML = '<i class="fa-solid fa-circle-info text-amber-400 me-1"></i> Data vendor belum diajukan. Silakan input modal vendor lalu klik <strong>"Ajukan ke Direksi"</strong>.';
        }
    } else if (order.status === 'pending_approval') {
        if (direksiBadge) {
            direksiBadge.innerText = '🔒 Menunggu ACC Direksi';
            direksiBadge.className = 'badge bg-indigo-100 text-indigo-900 border border-indigo-300 text-xs font-bold';
        }
        if (approvalLogText) {
            approvalLogText.innerHTML = '<i class="fa-solid fa-clock text-amber-400 me-1"></i> Pengajuan sedang <strong>di-hold menunggu persetujuan (ACC) Owner/Direksi</strong>.';
        }
    } else if (order.status === 'rejected') {
        if (direksiBadge) {
            direksiBadge.innerText = '❌ Ditolak Direksi';
            direksiBadge.className = 'badge bg-rose-100 text-rose-900 border border-rose-300 text-xs font-bold';
        }
        if (approvalLogText) {
            approvalLogText.innerHTML = `<i class="fa-solid fa-circle-xmark text-rose-400 me-1"></i> Pengajuan ditolak: <em>"${order.rejection_reason || '-'}"</em>. Silakan perbaiki dan ajukan kembali.`;
        }
    } else {
        if (direksiBadge) {
            direksiBadge.innerText = '✅ Disetujui (ACC)';
            direksiBadge.className = 'badge bg-emerald-100 text-emerald-900 border border-emerald-300 text-xs font-bold';
        }
        if (approvalLogText) {
            approvalLogText.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-400 me-1"></i> HPP disetujui (ACC) oleh Direksi. Pengerjaan & QC dapat dijalankan.';
        }
    }

    // Stage 3 Fields (QC)
    document.getElementById('ws_qc_vendor_name').innerText = `${order.vendor_name || 'Vendor Luar'} ${order.vendor_phone ? '(' + order.vendor_phone + ')' : ''}`;
    document.getElementById('ws_qc_job_title').innerText = `${order.job_title} (${order.qty} ${order.unit})`;
    document.getElementById('ws_qc_notes').value = order.qc_notes || 'Hasil QC barang saat sampai...';

    // Stage 4 Fields (Closing & Completed)
    const promptText = document.getElementById('ws_closing_prompt_text');
    const btnSlot = document.getElementById('ws_closing_btn_slot');
    const compBox = document.getElementById('ws_completed_invoice_box');
    const statusBadge = document.getElementById('ws_closing_status_badge');

    // Stage 4 Settlement Info
    const totTagihan = parseFloat(order.customer_price) || 0;
    const dpPaid = parseFloat(order.paid_amount) || 0;
    const remainingToPay = Math.max(0, totTagihan - dpPaid);

    const elTotTagihan = document.getElementById('ws_closing_tot_tagihan');
    const elDpPaid = document.getElementById('ws_closing_dp_paid');
    const elSisaTagihan = document.getElementById('ws_closing_sisa_tagihan');
    const elPayBadge = document.getElementById('ws_closing_pay_badge');
    const settlementForm = document.getElementById('ws_closing_settlement_form');
    const fullPaidNotice = document.getElementById('ws_closing_full_paid_notice');
    const settlementPaidInput = document.getElementById('ws_settlement_paid_input');
    const sisaBox = document.getElementById('ws_closing_sisa_box');

    const promptTitle = document.getElementById('ws_closing_prompt_title');
    const promptSubtitle = document.getElementById('ws_closing_prompt_subtitle');
    const btnDoClose = document.getElementById('ws_btn_do_close');

    if (elTotTagihan) elTotTagihan.innerText = `Rp ${Number(totTagihan).toLocaleString('id-ID')}`;
    if (elDpPaid) elDpPaid.innerText = `Rp ${Number(dpPaid).toLocaleString('id-ID')}`;
    if (elSisaTagihan) elSisaTagihan.innerText = `Rp ${Number(remainingToPay).toLocaleString('id-ID')}`;
    if (settlementPaidInput) settlementPaidInput.value = remainingToPay;

    // Check if originally full payment or DP
    const wasFullPaid = (remainingToPay <= 0 || order.payment_status === 'PAID');

    if (order.status === 'completed') {
        if (elPayBadge) {
            elPayBadge.innerText = 'LUNAS (100%)';
            elPayBadge.className = 'badge bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold';
        }
        if (settlementForm) settlementForm.style.display = 'none';
        if (fullPaidNotice) fullPaidNotice.style.display = 'none';
        if (sisaBox) sisaBox.className = 'p-3 bg-emerald-50 rounded-xl border border-emerald-200 shadow-2xs';
    } else if (wasFullPaid) {
        if (elPayBadge) {
            elPayBadge.innerText = 'Lunas 100% (Full Payment)';
            elPayBadge.className = 'badge bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold';
        }
        if (settlementForm) settlementForm.style.display = 'none';
        if (fullPaidNotice) fullPaidNotice.style.display = 'flex';
        if (sisaBox) sisaBox.className = 'p-3 bg-emerald-50 rounded-xl border border-emerald-200 shadow-2xs';

        if (promptTitle) promptTitle.innerText = 'Pesanan Siap Selesai & Diserahkan ke Customer';
        if (promptSubtitle) promptSubtitle.innerHTML = 'Pembayaran sudah <strong>lunas sejak awal</strong>. Klik tombol di bawah untuk menutup pesanan dan membukukan ke POS.';
        if (btnDoClose) {
            btnDoClose.innerHTML = '<i class="fa-solid fa-circle-check me-1.5"></i> Closing & Selesaikan Pesanan';
            btnDoClose.className = 'btn btn-md btn-primary rounded-xl font-bold px-5 shadow-sm';
        }
    } else {
        if (elPayBadge) {
            elPayBadge.innerText = `DP (Belum Lunas - Sisa: Rp ${Number(remainingToPay).toLocaleString('id-ID')})`;
            elPayBadge.className = 'badge bg-amber-200 text-amber-900 border border-amber-300 font-bold';
        }
        if (settlementForm) settlementForm.style.display = 'grid';
        if (fullPaidNotice) fullPaidNotice.style.display = 'none';
        if (sisaBox) sisaBox.className = 'p-3 bg-amber-50 rounded-xl border border-amber-300 shadow-2xs';

        if (promptTitle) promptTitle.innerText = 'Pesanan Siap Dilunasi & Diserahkan ke Customer';
        if (promptSubtitle) promptSubtitle.innerHTML = 'Input pelunasan di atas lalu klik tombol untuk membukukan transaksi & <strong>otomatis mencetak struk pelunasan</strong>.';
        if (btnDoClose) {
            btnDoClose.innerHTML = '<i class="fa-solid fa-receipt me-1.5"></i> Closing & Lunasi (Cetak Struk)';
            btnDoClose.className = 'btn btn-md btn-success rounded-xl font-bold px-5 shadow-sm';
        }
    }

    if (order.status === 'completed' && order.transaction) {
        if (promptText) promptText.style.display = 'none';
        if (btnSlot) btnSlot.style.display = 'none';
        if (compBox) compBox.style.display = 'block';
        document.getElementById('ws_completed_inv_number').innerText = order.transaction.invoice_number;
        document.getElementById('ws_btn_open_pos_inv').href = `/sales/${order.transaction_id}/receipt`;
        const btnCustReceipt = document.getElementById('ws_btn_open_customer_receipt');
        if (btnCustReceipt) btnCustReceipt.href = `/cetak-luar/${order.id}/receipt`;

        if (statusBadge) {
            statusBadge.innerText = 'Selesai (Closed)';
            statusBadge.className = 'badge bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10.5px]';
        }
    } else {
        if (promptText) promptText.style.display = 'block';
        if (btnSlot) btnSlot.style.display = 'block';
        if (compBox) compBox.style.display = 'none';
        if (statusBadge) {
            statusBadge.innerText = 'Siap Closing';
            statusBadge.className = 'badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10.5px]';
        }
    }

    // Calculate Totals & Profit
    calcOdooTotals();
}

// --- STAGE ACCESS GUARD (HOLD DATA UNTIL OWNER ACC) ---
function canAccessStage(targetStage) {
    if (!currentWsOrder) return true;
    if (targetStage <= 2) return true; // Tahap 1 & 2 selalu dapat diakses untuk view/edit

    const approvedStatuses = ['in_production', 'qc_passed', 'completed'];
    if (!approvedStatuses.includes(currentWsOrder.status)) {
        if (currentWsOrder.status === 'pending_approval') {
            Swal.fire({
                icon: 'info',
                title: 'Data Masih di-Hold (Menunggu ACC)',
                html: '<div class="text-xs text-slate-700 mt-2 text-start bg-slate-50 p-3 rounded-xl border border-slate-200"><p class="mb-1.5 font-bold text-slate-900">🔒 Tahap Pengerjaan & QC Belum Dibuka</p><p class="mb-0">Pesanan ini sedang dalam antrean <strong>Persetujuan Direksi/Owner</strong>. Silakan tunggu Owner menyetujui (ACC) pengajuan HPP terlebih dahulu.</p></div>',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#0f172a'
            });
        } else if (currentWsOrder.status === 'rejected') {
            Swal.fire({
                icon: 'error',
                title: 'Pengajuan Ditolak Direksi',
                html: `<div class="text-xs text-slate-700 mt-2 text-start bg-rose-50 p-3 rounded-xl border border-rose-200"><p class="mb-1.5 font-bold text-rose-900">❌ Alasan Penolakan:</p><p class="mb-0 italic text-rose-800">"${currentWsOrder.rejection_reason || '-'}"</p></div><p class="text-xs text-slate-500 mt-2">Silakan perbaiki data vendor/modal di Tahap 2 lalu ajukan kembali.</p>`,
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#0f172a'
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Belum Diajukan ke Direksi',
                html: '<p class="text-xs text-slate-600">Silakan lengkapi data HPP vendor pada <strong>Tahap 2</strong> dan klik tombol <strong>"Ajukan ke Direksi"</strong> terlebih dahulu.</p>',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#0f172a'
            });
        }
        return false;
    }

    if (targetStage === 4 && currentWsOrder.status === 'in_production') {
        Swal.fire({
            icon: 'warning',
            title: 'Barang Belum Lolos QC',
            html: '<p class="text-xs text-slate-600">Barang masih dalam proses pengerjaan di vendor.<br>Silakan lakukan pemeriksaan fisik dan klik <strong>"Konfirmasi Barang Sampai & Lolos QC"</strong> pada <strong>Tahap 3</strong> sebelum melakukan closing.</p>',
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#0f172a'
        });
        return false;
    }

    return true;
}

// --- DYNAMIC STAGE SWITCHING (DRAFT -> PENGAJUAN DIREKSI -> PROSES QC -> CLOSE) ---
function switchOdooStage(stageIndex) {
    if (!canAccessStage(stageIndex)) {
        return;
    }

    currentActiveStageIndex = stageIndex;

    // 1. Hide all panels, show the selected stage panel explicitly
    for (let i = 1; i <= 4; i++) {
        const panel = document.getElementById(`odoo_panel_${i}`);
        const btn = document.getElementById(`stage_tab_${i}`);
        if (panel) {
            if (i === stageIndex) {
                panel.style.setProperty('display', 'block', 'important');
                panel.classList.add('active');
                panel.classList.remove('d-none', 'hidden');
            } else {
                panel.style.setProperty('display', 'none', 'important');
                panel.classList.remove('active');
                panel.classList.add('d-none', 'hidden');
            }
        }
        if (btn) {
            if (i === stageIndex) {
                btn.className = 'odoo-stage-btn active px-3.5 py-1.5 transition border-r border-slate-300 flex items-center gap-1 font-bold bg-slate-900 text-white shadow-xs';
            } else {
                btn.className = 'odoo-stage-btn px-3.5 py-1.5 transition border-r border-slate-300 flex items-center gap-1 text-slate-600 hover:bg-slate-200';
            }
        }
    }

    const lastBtn = document.getElementById('stage_tab_4');
    if (lastBtn && currentActiveStageIndex !== 4) {
        lastBtn.classList.remove('border-r');
    }

    // 2. Update stage indicator text
    const stageTitles = {
        1: 'Tahap 1 dari 4: Draft Customer (Data & Harga Jual)',
        2: 'Tahap 2 dari 4: Pengajuan ke Direksi (HPP & Approval)',
        3: 'Tahap 3 dari 4: Proses QC & Pengerjaan di Vendor',
        4: 'Tahap 4 dari 4: Close (Selesai & Pembukuan Penjualan)'
    };
    const indicator = document.getElementById('odoo_stage_indicator_text');
    if (indicator) {
        indicator.innerText = stageTitles[stageIndex] || '';
    }

    // 3. Update Prev / Next button states
    const prevBtn = document.getElementById('odoo_btn_prev');
    const nextBtn = document.getElementById('odoo_btn_next');
    if (prevBtn) prevBtn.style.visibility = (stageIndex === 1) ? 'hidden' : 'visible';
    if (nextBtn) {
        if (stageIndex === 4) {
            nextBtn.style.visibility = 'hidden';
        } else {
            nextBtn.style.visibility = 'visible';
            if (stageIndex === 1) {
                nextBtn.innerHTML = `<span>Input HPP Vendor</span> <i class="fa-solid fa-arrow-right ms-1"></i>`;
                nextBtn.className = 'btn btn-sm btn-primary rounded-lg font-bold px-4 shadow-xs';
            } else if (stageIndex === 2) {
                const isApproved = ['in_production', 'qc_passed', 'completed'].includes(currentWsOrder.status);
                if (isApproved) {
                    nextBtn.innerHTML = `<span>Lanjut ke QC</span> <i class="fa-solid fa-arrow-right ms-1"></i>`;
                    nextBtn.className = 'btn btn-sm btn-primary rounded-lg font-bold px-4 shadow-xs';
                } else {
                    nextBtn.innerHTML = `<i class="fa-solid fa-lock me-1 text-amber-300"></i> <span>Menunggu ACC</span> <i class="fa-solid fa-arrow-right ms-1 opacity-50"></i>`;
                    nextBtn.className = 'btn btn-sm btn-secondary rounded-lg font-bold px-4 shadow-xs';
                }
            } else if (stageIndex === 3) {
                nextBtn.innerHTML = `<span>Tahap Closing</span> <i class="fa-solid fa-arrow-right ms-1"></i>`;
                nextBtn.className = 'btn btn-sm btn-primary rounded-lg font-bold px-4 shadow-xs';
            }
        }
    }

    // 4. Update Header Dynamic Actions based on current stage
    renderHeaderStageActions(stageIndex);
}

function navigateOdooStage(direction) {
    const target = currentActiveStageIndex + direction;
    if (target >= 1 && target <= 4) {
        switchOdooStage(target);
    }
}

function renderHeaderStageActions(stageIndex) {
    const slot = document.getElementById('odoo_dynamic_action_buttons');
    slot.innerHTML = '';
    if (!currentWsOrder) return;

    if (stageIndex === 1) {
        slot.innerHTML = `
            <button type="button" onclick="navigateOdooStage(1)" class="btn btn-sm btn-warning rounded-lg font-bold text-slate-900 px-3 shadow-xs">
                <span>Input HPP Vendor ➔</span>
            </button>
        `;
    } else if (stageIndex === 2) {
        const ownerSlot = document.getElementById('ws_owner_action_btns');
        ownerSlot.innerHTML = '';

        if (currentWsOrder.status === 'draft_customer' || currentWsOrder.status === 'rejected') {
            slot.innerHTML = `
                <button type="button" onclick="submitWsHppToOwner()" class="btn btn-sm btn-warning rounded-lg font-bold text-slate-900 px-3.5 shadow-xs">
                    <i class="fa-solid fa-paper-plane me-1"></i> Ajukan ke Direksi
                </button>
            `;
        } else if (currentWsOrder.status === 'pending_approval') {
            if (isOwnerOrSuper) {
                ownerSlot.innerHTML = `
                    <button type="button" onclick="promptWsReject()" class="btn btn-sm btn-outline-danger rounded-lg font-bold px-3">
                        <i class="fa-solid fa-xmark me-1"></i> Tolak
                    </button>
                    <button type="button" onclick="submitWsApprove()" class="btn btn-sm btn-success rounded-lg font-bold px-3.5 shadow-xs">
                        <i class="fa-solid fa-check-double me-1"></i> ACC / Setujui
                    </button>
                `;
                slot.innerHTML = `
                    <button type="button" onclick="submitWsApprove()" class="btn btn-sm btn-success rounded-lg font-bold px-3.5 shadow-xs">
                        <i class="fa-solid fa-check-double me-1"></i> ACC / Setujui Order
                    </button>
                `;
            }
        }
    } else if (stageIndex === 3) {
        if (currentWsOrder.status === 'in_production') {
            slot.innerHTML = `
                <button type="button" onclick="submitWsPassQc()" class="btn btn-sm btn-purple rounded-lg font-bold text-white bg-purple-700 hover:bg-purple-800 px-3.5 shadow-xs">
                    <i class="fa-solid fa-clipboard-check me-1"></i> Konfirmasi Lolos QC
                </button>
            `;
        }
    } else if (stageIndex === 4) {
        if (currentWsOrder.status !== 'completed') {
            slot.innerHTML = `
                <button type="button" onclick="submitWsCloseOrder()" class="btn btn-sm btn-success rounded-lg font-bold px-3.5 shadow-xs">
                    <i class="fa-solid fa-cash-register me-1"></i> Closing / Selesai
                </button>
            `;
        }
    }
}

// --- CALCULATION LOGIC ---
function calcOdooTotals() {
    const qty = parseInt(document.getElementById('ws_qty').value) || 1;
    const unit = document.getElementById('ws_unit').value || 'pcs';

    document.getElementById('ws_line_job_title').innerText = document.getElementById('ws_job_title').value || 'Item Cetak';
    document.getElementById('ws_vendor_unit_label').innerText = `/ ${unit}`;

    const custUnitPrice = parseFloat(document.getElementById('ws_customer_unit_price').value) || 0;
    const totalOmset = qty * custUnitPrice;

    const vendUnitPrice = parseFloat(document.getElementById('ws_vendor_unit_price').value) || 0;
    const vendorSubtotal = qty * vendUnitPrice;
    const shipping = parseFloat(document.getElementById('ws_shipping_cost').value) || 0;
    const totalHpp = vendorSubtotal + shipping;

    const profit = totalOmset - totalHpp;
    const marginPct = totalOmset > 0 ? ((profit / totalOmset) * 100).toFixed(1) : '0';

    // Auto-update paid amount if LUNAS
    const payType = document.getElementById('ws_payment_type').value;
    if (payType === 'PAID') {
        document.getElementById('ws_paid_amount').value = totalOmset;
    }

    const paidAmt = parseFloat(document.getElementById('ws_paid_amount').value) || 0;
    const remaining = Math.max(0, totalOmset - paidAmt);

    const remDisplay = document.getElementById('ws_remaining_amount_display');
    if (remaining === 0 && totalOmset > 0) {
        remDisplay.innerText = 'Rp 0 (Lunas)';
        remDisplay.className = 'font-mono font-bold text-emerald-700';
    } else if (paidAmt > 0) {
        remDisplay.innerText = `Rp ${Number(remaining).toLocaleString('id-ID')} (Sisa Pelunasan)`;
        remDisplay.className = 'font-mono font-bold text-amber-700';
    } else {
        remDisplay.innerText = `Rp ${Number(totalOmset).toLocaleString('id-ID')} (Sisa Pelunasan)`;
        remDisplay.className = 'font-mono font-bold text-rose-700';
    }

    // Stage 1 Displays
    document.getElementById('ws_calc_preview_pill').innerText = `${qty} ${unit} x Rp ${Number(custUnitPrice).toLocaleString('id-ID')}`;
    document.getElementById('ws_total_omset_display').innerText = `Rp ${Number(totalOmset).toLocaleString('id-ID')}`;

    // Stage 2 Displays
    document.getElementById('ws_vendor_calc_preview_pill').innerText = `${qty} ${unit} x Rp ${Number(vendUnitPrice).toLocaleString('id-ID')}`;
    document.getElementById('ws_sum_omset').innerText = `Rp ${Number(totalOmset).toLocaleString('id-ID')}`;
    document.getElementById('ws_sum_hpp_card').innerText = `Rp ${Number(totalHpp).toLocaleString('id-ID')}`;
    document.getElementById('ws_sum_total_hpp').innerText = `Rp ${Number(totalHpp).toLocaleString('id-ID')}`;
    document.getElementById('ws_sum_profit').innerText = `Rp ${Number(profit).toLocaleString('id-ID')}`;

    const badge = document.getElementById('ws_sum_margin_badge');
    if (profit < 0) {
        badge.className = 'badge bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-mono font-bold px-2.5 py-1';
        badge.innerText = `Rugi ${marginPct}%`;
    } else {
        badge.className = 'badge bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-mono font-bold px-2.5 py-1';
        badge.innerText = `Margin ${marginPct}%`;
    }

    // Stage 3 Displays
    document.getElementById('ws_qc_total_hpp').innerText = `Rp ${Number(totalHpp).toLocaleString('id-ID')}`;

    // Stage 4 Displays & Profit Statement Table
    if (document.getElementById('ws_final_omset')) {
        document.getElementById('ws_final_omset').innerText = `Rp ${Number(totalOmset).toLocaleString('id-ID')}`;
        document.getElementById('ws_final_hpp').innerText = `Rp ${Number(totalHpp).toLocaleString('id-ID')}`;
        document.getElementById('ws_final_profit').innerText = `Rp ${Number(profit).toLocaleString('id-ID')}`;
    }

    if (document.getElementById('ws_table_tot_omset')) {
        const jobTitle = document.getElementById('ws_job_title')?.value || 'Item Cetak';
        const vendorName = document.getElementById('ws_vendor_name')?.value || 'Vendor Rekanan';

        document.getElementById('ws_table_tot_omset').innerText = `Rp ${Number(totalOmset).toLocaleString('id-ID')}`;
        document.getElementById('ws_table_job_title').innerText = jobTitle;
        document.getElementById('ws_table_cust_calc_breakdown').innerText = `${qty} ${unit} x @ Rp ${Number(custUnitPrice).toLocaleString('id-ID')}`;
        document.getElementById('ws_table_cust_subtotal').innerText = `Rp ${Number(totalOmset).toLocaleString('id-ID')}`;

        document.getElementById('ws_table_tot_hpp').innerText = `Rp ${Number(totalHpp).toLocaleString('id-ID')}`;
        document.getElementById('ws_table_vendor_name').innerText = vendorName;
        document.getElementById('ws_table_vendor_calc_breakdown').innerText = `${qty} ${unit} x @ Rp ${Number(vendUnitPrice).toLocaleString('id-ID')}`;
        document.getElementById('ws_table_vendor_cost').innerText = `- Rp ${Number(vendorSubtotal).toLocaleString('id-ID')}`;
        document.getElementById('ws_table_shipping_cost').innerText = `- Rp ${Number(shipping).toLocaleString('id-ID')}`;

        document.getElementById('ws_table_profit_formula').innerText = `Omset (Rp ${Number(totalOmset).toLocaleString('id-ID')}) - Total HPP (Rp ${Number(totalHpp).toLocaleString('id-ID')})`;
        document.getElementById('ws_table_net_profit').innerText = (profit >= 0 ? '+ ' : '') + `Rp ${Number(profit).toLocaleString('id-ID')}`;
        document.getElementById('ws_table_margin_pct').innerText = `Margin: ${marginPct}%`;

        const marginPill = document.getElementById('ws_closing_margin_pill');
        if (marginPill) {
            marginPill.innerText = `Margin ${marginPct}%`;
            marginPill.className = (profit >= 0) ? 'badge bg-emerald-500 text-white font-mono font-bold text-xs px-2.5 py-1 shadow-xs' : 'badge bg-rose-600 text-white font-mono font-bold text-xs px-2.5 py-1 shadow-xs';
        }
    }
}

function handleWsPaymentTypeChange(type) {
    const qty = parseInt(document.getElementById('ws_qty').value) || 1;
    const custUnitPrice = parseFloat(document.getElementById('ws_customer_unit_price').value) || 0;
    const totalOmset = qty * custUnitPrice;

    const paidInput = document.getElementById('ws_paid_amount');
    if (type === 'PAID') {
        paidInput.value = totalOmset;
    } else if (type === 'UNPAID') {
        paidInput.value = 0;
    } else if (type === 'DP') {
        const cur = parseFloat(paidInput.value) || 0;
        if (cur <= 0 || cur >= totalOmset) {
            paidInput.value = Math.round(totalOmset / 2);
        }
    }
    calcOdooTotals();
}

function handleWsPaidAmountInput() {
    const qty = parseInt(document.getElementById('ws_qty').value) || 1;
    const custUnitPrice = parseFloat(document.getElementById('ws_customer_unit_price').value) || 0;
    const totalOmset = qty * custUnitPrice;
    const paidAmt = parseFloat(document.getElementById('ws_paid_amount').value) || 0;

    const paySelect = document.getElementById('ws_payment_type');
    if (paidAmt >= totalOmset && totalOmset > 0) {
        paySelect.value = 'PAID';
    } else if (paidAmt > 0) {
        paySelect.value = 'DP';
    } else {
        paySelect.value = 'UNPAID';
    }
    calcOdooTotals();
}

// --- SAVE / UPDATE DATA ---
function saveWorksheetChanges(showNotification = true) {
    if (!currentWsOrder) return;

    const qty = parseInt(document.getElementById('ws_qty').value) || 1;
    const custUnitPrice = parseFloat(document.getElementById('ws_customer_unit_price').value) || 0;
    const customerPrice = qty * custUnitPrice;

    const vendUnitPrice = parseFloat(document.getElementById('ws_vendor_unit_price').value) || 0;
    const vendorCost = qty * vendUnitPrice;
    const shipping = parseFloat(document.getElementById('ws_shipping_cost').value) || 0;

    const payload = {
        job_title: document.getElementById('ws_job_title').value,
        description: document.getElementById('ws_description').value,
        qty: qty,
        unit: document.getElementById('ws_unit').value,
        customer_unit_price: custUnitPrice,
        customer_price: customerPrice,
        customer_name: document.getElementById('ws_customer_name').value,
        customer_phone: document.getElementById('ws_customer_phone').value,
        payment_method: document.getElementById('ws_payment_method').value,
        paid_amount: document.getElementById('ws_paid_amount').value,
        vendor_name: document.getElementById('ws_vendor_name').value,
        vendor_phone: document.getElementById('ws_vendor_phone').value,
        vendor_unit_price: vendUnitPrice,
        vendor_cost: vendorCost,
        shipping_cost: shipping,
        vendor_notes: document.getElementById('ws_vendor_notes').value,
        qc_notes: document.getElementById('ws_qc_notes').value,
    };

    fetch(`/cetak-luar/${currentWsOrder.id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.order) {
            currentWsOrder = res.order;
            if (showNotification) {
                Swal.fire({
                    icon: 'success',
                    title: 'Tersimpan!',
                    text: res.message,
                    timer: 1400,
                    showConfirmButton: false
                });
            }
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
        }
    })
    .catch(err => {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    });
}

// Stage Actions
function submitWsHppToOwner() {
    saveWorksheetChanges(false);
    setTimeout(() => {
        const qty = parseInt(document.getElementById('ws_qty').value) || 1;
        const vendUnitPrice = parseFloat(document.getElementById('ws_vendor_unit_price').value) || 0;

        fetch(`/cetak-luar/${currentWsOrder.id}/submit-vendor`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                vendor_name: document.getElementById('ws_vendor_name').value,
                vendor_phone: document.getElementById('ws_vendor_phone').value,
                vendor_unit_price: vendUnitPrice,
                vendor_cost: qty * vendUnitPrice,
                shipping_cost: document.getElementById('ws_shipping_cost').value,
                vendor_notes: document.getElementById('ws_vendor_notes').value
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'Diajukan ke Direksi!', text: res.message })
                    .then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
            }
        });
    }, 300);
}

function submitWsApprove() {
    fetch(`/cetak-luar/${currentWsOrder.id}/approve`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Order Telah di-ACC!', text: res.message })
                .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
        }
    });
}

function promptWsReject() {
    Swal.fire({
        title: 'Tolak Pengajuan Vendor?',
        input: 'text',
        inputLabel: 'Alasan Penolakan',
        inputPlaceholder: 'Misal: Modal vendor terlalu tinggi...',
        showCancelButton: true,
        confirmButtonText: 'Konfirmasi Tolak',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#e11d48',
        inputValidator: (value) => {
            if (!value) return 'Alasan penolakan wajib diisi!';
        }
    }).then(result => {
        if (result.isConfirmed) {
            fetch(`/cetak-luar/${currentWsOrder.id}/reject`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ rejection_reason: result.value })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({ icon: 'info', title: 'Pengajuan Ditolak', text: res.message })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                }
            });
        }
    });
}

function submitWsPassQc() {
    const notes = document.getElementById('ws_qc_notes').value;
    fetch(`/cetak-luar/${currentWsOrder.id}/pass-qc`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ qc_notes: notes })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Lolos QC!', text: res.message })
                .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
        }
    });
}

function submitWsCloseOrder() {
    const totTagihan = parseFloat(currentWsOrder.customer_price) || 0;
    const dpPaid = parseFloat(currentWsOrder.paid_amount) || 0;
    const sisa = Math.max(0, totTagihan - dpPaid);
    const wasDp = (sisa > 0 || currentWsOrder.payment_status === 'PARTIAL');

    const settlementPaid = wasDp ? (parseFloat(document.getElementById('ws_settlement_paid_input')?.value) || sisa) : 0;
    const settlementMethod = wasDp ? (document.getElementById('ws_settlement_method')?.value || 'Cash') : currentWsOrder.payment_method;

    let htmlPrompt = '';
    if (wasDp) {
        htmlPrompt = `
            <div class="text-xs text-start bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-slate-700 space-y-1.5">
                <div class="flex justify-between pb-1 border-b">
                    <span>Total Tagihan:</span> <strong class="font-mono text-slate-900">Rp ${Number(totTagihan).toLocaleString('id-ID')}</strong>
                </div>
                <div class="flex justify-between pb-1 border-b">
                    <span>DP yang telah dibayar:</span> <strong class="font-mono text-emerald-700">Rp ${Number(dpPaid).toLocaleString('id-ID')}</strong>
                </div>
                <div class="flex justify-between text-amber-900 font-bold pb-1 border-b">
                    <span>Pelunasan Diterima:</span> <strong class="font-mono text-emerald-700">Rp ${Number(settlementPaid).toLocaleString('id-ID')} (${settlementMethod})</strong>
                </div>
                <div class="text-[11px] text-slate-500 pt-1">
                    <i class="fa-solid fa-print text-blue-600 me-1"></i> Setelah closing, <strong>struk pelunasan customer akan otomatis dicetak</strong> sebagai bukti pengambilan barang.
                </div>
            </div>
        `;
    } else {
        htmlPrompt = `
            <div class="text-xs text-start bg-emerald-50 p-3.5 rounded-xl border border-emerald-200 text-emerald-900 space-y-1.5">
                <div class="flex justify-between pb-1 border-b border-emerald-200">
                    <span>Total Omset Penjualan:</span> <strong class="font-mono text-emerald-900">Rp ${Number(totTagihan).toLocaleString('id-ID')}</strong>
                </div>
                <div class="flex justify-between pb-1 border-b border-emerald-200">
                    <span>Status Pembayaran:</span> <strong class="text-emerald-800 font-bold">Lunas (100%) sejak awal</strong>
                </div>
                <div class="text-[11px] text-emerald-700 pt-1">
                    Pesanan full payment akan langsung dibukukan ke <strong>Laporan Penjualan Harian & Kas POS</strong> (tidak perlu cetak ulang struk customer).
                </div>
            </div>
        `;
    }

    Swal.fire({
        title: wasDp ? 'Closing & Lunasi Pembayaran?' : 'Closing & Selesaikan Pesanan?',
        html: htmlPrompt,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: wasDp ? 'Ya, Closing & Lunasi' : 'Ya, Closing Pesanan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#059669'
    }).then(r => {
        if (r.isConfirmed) {
            fetch(`/cetak-luar/${currentWsOrder.id}/close`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    settlement_paid_amount: settlementPaid,
                    settlement_payment_method: settlementMethod
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    if (res.was_dp) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Pelunasan Berhasil & Pesanan Selesai!',
                            text: 'Pelunasan telah dibukukan. Membuka struk tanda terima pelunasan customer...',
                            confirmButtonText: 'Cetak Struk Pelunasan',
                            confirmButtonColor: '#059669'
                        }).then(() => {
                            if (res.customer_receipt_url) {
                                window.open(res.customer_receipt_url, '_blank');
                            }
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'Pesanan Selesai Ditutup!',
                            text: res.message,
                            confirmButtonText: 'Selesai',
                            confirmButtonColor: '#0f172a'
                        }).then(() => {
                            location.reload();
                        });
                    }
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                }
            });
        }
    });
}

// --- CREATE ORDER MODAL ---
function openCreateOrderModal() {
    document.getElementById('formCreateOrder').reset();
    document.getElementById('create_qty').value = '1';
    document.getElementById('create_unit').value = 'pcs';
    document.getElementById('create_unit_price').value = '';
    document.getElementById('create_customer_price').value = '0';
    document.getElementById('create_payment_type').value = 'PAID';
    calcCreateCustomerPrice();

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCreateOrder'));
    modal.show();
}

function handleCreatePaymentTypeChange(type) {
    const qty = parseInt(document.getElementById('create_qty').value) || 0;
    const unitPrice = parseFloat(document.getElementById('create_unit_price').value) || 0;
    const total = qty * unitPrice;

    const paidInput = document.getElementById('create_paid_amount');
    if (type === 'PAID') {
        paidInput.value = total;
    } else if (type === 'UNPAID') {
        paidInput.value = 0;
    } else if (type === 'DP') {
        paidInput.value = total > 0 ? Math.round(total / 2) : 0;
    }
}

function calcCreateCustomerPrice() {
    const qty = parseInt(document.getElementById('create_qty').value) || 0;
    const unit = document.getElementById('create_unit').value || 'pcs';
    const unitPrice = parseFloat(document.getElementById('create_unit_price').value) || 0;
    const total = qty * unitPrice;

    document.getElementById('create_customer_price').value = total;
    document.getElementById('create_unit_label').innerText = `/ ${unit}`;
    document.getElementById('create_calc_preview_text').innerText = `${qty} ${unit} x Rp ${Number(unitPrice).toLocaleString('id-ID')}`;
    document.getElementById('create_total_display').innerText = `Total: Rp ${Number(total).toLocaleString('id-ID')}`;

    const payType = document.getElementById('create_payment_type').value;
    if (payType === 'PAID') {
        document.getElementById('create_paid_amount').value = total;
    }
}

function submitCreateOrder(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitCreateOrder');
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...`;

    const form = document.getElementById('formCreateOrder');
    const formData = new FormData(form);

    fetch('{{ route("outsource-orders.store") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = `<i class="fa-solid fa-check me-1"></i> Simpan & Cetak Struk`;
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Pesanan Berhasil Dibuat!',
                text: res.message,
                showCancelButton: true,
                confirmButtonText: 'Cetak Struk Customer',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#2563eb'
            }).then((r) => {
                if (r.isConfirmed && res.receipt_url) {
                    window.open(res.receipt_url, '_blank');
                }
                location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = `<i class="fa-solid fa-check me-1"></i> Simpan & Cetak Struk`;
        Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: err.message });
    });
}
</script>
@endsection
