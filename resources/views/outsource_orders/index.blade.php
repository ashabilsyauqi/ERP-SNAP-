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
<div class="space-y-6 pb-12">

    <!-- METRIC STAT CARDS -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        
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
            <span class="text-[10.5px] text-amber-700 mt-1">Menunggu input HPP</span>
        </div>

        <!-- 2. Menunggu ACC Owner -->
        <div class="bg-white p-4 rounded-2xl border border-indigo-200 shadow-sm flex flex-col justify-between bg-indigo-50/20">
            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-800 flex items-center gap-1">
                <i class="fa-solid fa-user-clock text-indigo-600"></i> 2. Menunggu ACC
            </span>
            <div class="text-2xl font-black text-indigo-900 mt-2 font-mono">{{ $counts['pending_approval'] }}</div>
            <span class="text-[10.5px] text-indigo-700 mt-1">Antrean persetujuan</span>
        </div>

        <!-- 3. Sedang Dikerjakan -->
        <div class="bg-white p-4 rounded-2xl border border-blue-200 shadow-sm flex flex-col justify-between bg-blue-50/20">
            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-800 flex items-center gap-1">
                <i class="fa-solid fa-gears text-blue-600"></i> 3. Di Vendor
            </span>
            <div class="text-2xl font-black text-blue-900 mt-2 font-mono">{{ $counts['in_production'] }}</div>
            <span class="text-[10.5px] text-blue-700 mt-1">Proses cetak</span>
        </div>

        <!-- 4. Lolos QC / Sampai -->
        <div class="bg-white p-4 rounded-2xl border border-purple-200 shadow-sm flex flex-col justify-between bg-purple-50/20">
            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-800 flex items-center gap-1">
                <i class="fa-solid fa-clipboard-check text-purple-600"></i> 4. Lolos QC
            </span>
            <div class="text-2xl font-black text-purple-900 mt-2 font-mono">{{ $counts['qc_passed'] }}</div>
            <span class="text-[10.5px] text-purple-700 mt-1">Siap closing</span>
        </div>

        <!-- 5. Selesai (Closed) -->
        <div class="bg-white p-4 rounded-2xl border border-emerald-200 shadow-sm flex flex-col justify-between bg-emerald-50/20">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 flex items-center gap-1">
                <i class="fa-solid fa-circle-check text-emerald-600"></i> 5. Selesai
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
                    <i class="fa-solid fa-user-clock me-1"></i> Menunggu ACC ({{ $counts['pending_approval'] }})
                </a>
                <a href="{{ route('outsource-orders.index', array_merge(request()->query(), ['tab' => 'in_production'])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition border {{ $activeTab === 'in_production' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-blue-50 text-blue-800 hover:bg-blue-100 border-blue-200' }}">
                    <i class="fa-solid fa-gears me-1"></i> Sedang Dikerjakan ({{ $counts['in_production'] }})
                </a>
                <a href="{{ route('outsource-orders.index', array_merge(request()->query(), ['tab' => 'qc_passed'])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition border {{ $activeTab === 'qc_passed' ? 'bg-purple-600 text-white border-purple-600 shadow-sm' : 'bg-purple-50 text-purple-800 hover:bg-purple-100 border-purple-200' }}">
                    <i class="fa-solid fa-clipboard-check me-1"></i> Lolos QC ({{ $counts['qc_passed'] }})
                </a>
                <a href="{{ route('outsource-orders.index', array_merge(request()->query(), ['tab' => 'completed'])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition border {{ $activeTab === 'completed' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border-emerald-200' }}">
                    <i class="fa-solid fa-circle-check me-1"></i> Selesai ({{ $counts['completed'] }})
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
                                <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9.5px] mt-0.5">
                                    {{ $order->payment_method }} ({{ $order->payment_status }})
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
<!-- MODAL: LEMBAR KERJA ODOO-STYLE FORM SHEET & STATUSBAR                     -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalWorksheet" tabindex="-1" aria-labelledby="modalWorksheetLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 980px;">
        <div class="modal-content rounded-2xl border-0 shadow-2xl overflow-hidden bg-slate-100">
            
            <!-- 1. ODOO CONTROL PANEL / TOP STATUSBAR -->
            <div class="bg-white border-b border-slate-200 px-5 py-3 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                
                <!-- Left Action Buttons (Odoo Header Action Bar) -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" onclick="saveWorksheetChanges(false)" class="btn btn-sm btn-primary rounded-lg font-bold px-3 shadow-xs">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                    </button>

                    <div id="odoo_dynamic_action_buttons" class="flex items-center gap-1.5 flex-wrap">
                        <!-- Filled by JS depending on stage -->
                    </div>

                    <a href="#" id="odoo_btn_print_struk" target="_blank" class="btn btn-sm btn-outline-secondary rounded-lg font-semibold px-2.5">
                        <i class="fa-solid fa-print me-1"></i> Struk Customer
                    </a>
                </div>

                <!-- Right: ODOO STATUSBAR (Chevron Pipeline Arrow Bar) -->
                <div class="flex items-center justify-end w-full md:w-auto">
                    <div class="flex items-stretch border border-slate-300 rounded-lg overflow-hidden text-xs bg-slate-50 font-medium">
                        
                        <button type="button" onclick="switchOdooStage('draft_customer')" id="stage_tab_draft_customer" 
                                class="odoo-stage-btn px-3 py-1.5 transition border-r border-slate-300 flex items-center gap-1 font-bold bg-slate-800 text-white">
                            <span>1. Draft Customer</span>
                        </button>
                        
                        <button type="button" onclick="switchOdooStage('pending_approval')" id="stage_tab_pending_approval" 
                                class="odoo-stage-btn px-3 py-1.5 transition border-r border-slate-300 flex items-center gap-1 text-slate-600 hover:bg-slate-200">
                            <span>2. HPP & ACC</span>
                        </button>
                        
                        <button type="button" onclick="switchOdooStage('in_production')" id="stage_tab_in_production" 
                                class="odoo-stage-btn px-3 py-1.5 transition border-r border-slate-300 flex items-center gap-1 text-slate-600 hover:bg-slate-200">
                            <span>3. Di Vendor</span>
                        </button>
                        
                        <button type="button" onclick="switchOdooStage('qc_passed')" id="stage_tab_qc_passed" 
                                class="odoo-stage-btn px-3 py-1.5 transition border-r border-slate-300 flex items-center gap-1 text-slate-600 hover:bg-slate-200">
                            <span>4. Lolos QC</span>
                        </button>
                        
                        <button type="button" onclick="switchOdooStage('completed')" id="stage_tab_completed" 
                                class="odoo-stage-btn px-3 py-1.5 transition flex items-center gap-1 text-slate-600 hover:bg-slate-200">
                            <span>5. Selesai</span>
                        </button>
                    </div>

                    <button type="button" class="btn-close ms-3 text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- 2. ODOO FORM SHEET (CANVAS KERTAS DOKUMEN PUTIH) -->
            <div class="p-4 md:p-6 overflow-y-auto max-h-[calc(85vh-90px)]">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
                    <input type="hidden" id="ws_order_id">

                    <!-- Title & Reference Header -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center pb-4 border-b border-slate-200 gap-2">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block">Cetak di Luar / Vendor Outsource</span>
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

                    <!-- 2-Column Info Fields (Odoo Group Grid) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Left Group: Customer & Job -->
                        <div class="space-y-3.5">
                            <h6 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b pb-1.5 mb-2 text-primary">
                                <i class="fa-solid fa-user me-1 text-blue-600"></i> Informasi Pelanggan & Pekerjaan
                            </h6>

                            <div class="grid grid-cols-3 gap-2 items-center">
                                <label class="text-xs font-semibold text-slate-600">Pelanggan</label>
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
                                <label class="text-xs font-semibold text-slate-600">Nama Pekerjaan</label>
                                <div class="col-span-2">
                                    <input type="text" id="ws_job_title" class="form-control form-control-sm text-xs font-bold text-slate-900" placeholder="Nama pesanan / produk">
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 items-start">
                                <label class="text-xs font-semibold text-slate-600 pt-1">Spesifikasi</label>
                                <div class="col-span-2">
                                    <textarea id="ws_description" rows="2" class="form-control form-control-sm text-xs" placeholder="Ukuran, bahan, laminasi, finishing..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Right Group: Vendor & Payment -->
                        <div class="space-y-3.5">
                            <h6 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b pb-1.5 mb-2 text-indigo-700">
                                <i class="fa-solid fa-industry me-1 text-indigo-600"></i> Informasi Vendor & Pembayaran
                            </h6>

                            <div class="grid grid-cols-3 gap-2 items-center">
                                <label class="text-xs font-semibold text-slate-600">Vendor Rekanan</label>
                                <div class="col-span-2">
                                    <input type="text" id="ws_vendor_name" class="form-control form-control-sm text-xs font-semibold" placeholder="Nama vendor percetakan">
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 items-center">
                                <label class="text-xs font-semibold text-slate-600">Kontak Vendor</label>
                                <div class="col-span-2">
                                    <input type="text" id="ws_vendor_phone" class="form-control form-control-sm text-xs font-mono" placeholder="08xxxxxxxx">
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
                                <label class="text-xs font-semibold text-slate-600">Dibayar (Rp)</label>
                                <div class="col-span-2">
                                    <input type="number" id="ws_paid_amount" min="0" step="1000" class="form-control form-control-sm text-xs font-mono font-bold" placeholder="0">
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- 3. ODOO ORDER LINES TABLE (RINCIAN HARGA SATUAN & MODAL) -->
                    <div class="space-y-2 pt-2">
                        <div class="flex items-center justify-between">
                            <h6 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-0">
                                <i class="fa-solid fa-list-check me-1 text-slate-700"></i> Rincian Harga Satuan & HPP
                            </h6>
                            <span class="text-[11px] text-slate-400">Kalkulasi real-time per satuan</span>
                        </div>

                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                            <table class="table table-bordered table-sm align-middle mb-0 text-xs">
                                <thead class="bg-slate-100 text-slate-700 uppercase tracking-wider text-[10px] font-bold">
                                    <tr>
                                        <th style="width: 32%;">Item / Pekerjaan</th>
                                        <th style="width: 10%;" class="text-center">Qty</th>
                                        <th style="width: 10%;" class="text-center">Satuan</th>
                                        <th style="width: 24%;" class="text-end">Harga Jual Satuan (Rp)</th>
                                        <th style="width: 24%;" class="text-end">Modal Satuan Vendor (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="bg-white">
                                        <td>
                                            <span id="ws_table_item_name" class="font-bold text-slate-900 block">-</span>
                                            <span id="ws_table_item_desc" class="text-[10.5px] text-slate-500 block truncate max-w-xs">-</span>
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
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text text-[11px] bg-slate-50 font-mono">Rp</span>
                                                <input type="number" id="ws_vendor_unit_price" min="0" step="500" oninput="calcOdooTotals()" class="form-control font-mono font-bold text-rose-700 text-xs text-end" placeholder="0">
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 4. BOTTOM NOTES & SUMMARY SECTION (ODOO STYLE) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        
                        <!-- Left: Notes & Approval Log -->
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs font-bold text-slate-700 uppercase mb-1 block">Catatan Vendor / Pengerjaan</label>
                                <textarea id="ws_vendor_notes" rows="2" class="form-control form-control-sm text-xs" placeholder="Instruksi tambahan vendor..."></textarea>
                            </div>

                            <!-- QC Notes -->
                            <div>
                                <label class="text-xs font-bold text-slate-700 uppercase mb-1 block">Catatan Quality Control (QC)</label>
                                <textarea id="ws_qc_notes" rows="2" class="form-control form-control-sm text-xs" placeholder="Hasil QC barang saat sampai..."></textarea>
                            </div>

                            <!-- Rejection Reason if any -->
                            <div id="ws_rejection_banner" class="hidden p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-1">
                                <strong><i class="fa-solid fa-circle-xmark me-1"></i> Riwayat Penolakan Owner:</strong>
                                <p id="ws_rejection_text" class="mb-0 text-[11.5px]">-</p>
                            </div>
                        </div>

                        <!-- Right: Odoo Accounting Totals Calculation Box -->
                        <div class="space-y-2 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
                            
                            <!-- Subtotal Penjualan -->
                            <div class="flex justify-between items-center text-slate-600">
                                <span>Total Penjualan Customer:</span>
                                <strong id="ws_sum_omset" class="font-mono text-slate-900 text-sm">Rp 0</strong>
                            </div>

                            <!-- Subtotal Modal Vendor -->
                            <div class="flex justify-between items-center text-slate-600">
                                <span>Subtotal Modal Vendor:</span>
                                <span id="ws_sum_vendor_subtotal" class="font-mono text-rose-700 font-semibold">Rp 0</span>
                            </div>

                            <!-- Ongkos Kirim Input Row -->
                            <div class="flex justify-between items-center text-slate-600 pt-1">
                                <span class="flex items-center gap-1">
                                    <span>Ongkos Kirim:</span>
                                </span>
                                <div class="w-36">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text text-[10px] bg-white font-mono">Rp</span>
                                        <input type="number" id="ws_shipping_cost" min="0" step="1000" oninput="calcOdooTotals()" class="form-control form-control-sm text-xs font-mono text-end" placeholder="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Total HPP (Vendor + Shipping) -->
                            <div class="flex justify-between items-center text-slate-700 pt-1 border-t border-slate-200 font-bold">
                                <span>Total Modal HPP (Vendor + Ongkir):</span>
                                <span id="ws_sum_total_hpp" class="font-mono text-rose-700 text-sm">Rp 0</span>
                            </div>

                            <!-- ESTIMASI LABA BERSIH & MARGIN % (HIGHLIGHTED IN EMERALD) -->
                            <div class="flex justify-between items-center p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-900 font-bold mt-2">
                                <div>
                                    <span class="text-[10.5px] uppercase tracking-wider block text-emerald-800">Estimasi Laba Bersih</span>
                                    <span id="ws_sum_margin_badge" class="badge bg-emerald-600 text-white font-mono text-[10px]">Margin 0%</span>
                                </div>
                                <strong id="ws_sum_profit" class="text-lg font-black font-mono text-emerald-700">Rp 0</strong>
                            </div>

                        </div>

                    </div>

                </div>
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

                    <div class="grid grid-cols-2 gap-3 pt-1 border-t">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Metode Bayar</label>
                            <select name="payment_method" id="create_payment_method" class="form-select form-select-sm text-xs font-bold rounded-lg">
                                <option value="Cash">Tunai (Cash)</option>
                                <option value="Transfer">Transfer Bank</option>
                                <option value="QRIS">QRIS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Status Bayar</label>
                            <select name="is_dp" id="create_is_dp" onchange="toggleCreateDp(this.value)" class="form-select form-select-sm text-xs font-bold rounded-lg">
                                <option value="0">Lunas (100%)</option>
                                <option value="1">Uang Muka (DP)</option>
                            </select>
                        </div>
                    </div>

                    <div id="create_dp_container" class="hidden pt-1">
                        <label class="block text-xs font-bold text-amber-800 uppercase mb-1">Nominal DP Dibayar (Rp)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text font-bold text-xs bg-amber-50 text-amber-700">Rp</span>
                            <input type="number" name="paid_amount" id="create_paid_amount" min="0" step="1000" class="form-control font-mono font-bold text-amber-800 text-xs rounded-r-lg" placeholder="0">
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

    // Customer & Job Info
    document.getElementById('ws_customer_name').value = order.customer_name || '';
    document.getElementById('ws_customer_phone').value = order.customer_phone || '';
    document.getElementById('ws_job_title').value = order.job_title || '';
    document.getElementById('ws_description').value = order.description || '';

    // Vendor & Payment
    document.getElementById('ws_vendor_name').value = order.vendor_name || '';
    document.getElementById('ws_vendor_phone').value = order.vendor_phone || '';
    document.getElementById('ws_payment_method').value = order.payment_method || 'Cash';
    document.getElementById('ws_paid_amount').value = order.paid_amount || order.customer_price;

    // Order Line Inputs
    document.getElementById('ws_table_item_name').innerText = order.job_title || 'Item Cetak';
    document.getElementById('ws_table_item_desc').innerText = order.description || 'Spesifikasi cetak';
    document.getElementById('ws_qty').value = order.qty || 1;
    document.getElementById('ws_unit').value = order.unit || 'pcs';

    const custUnitPrice = order.customer_unit_price > 0 ? order.customer_unit_price : (order.qty > 0 ? (order.customer_price / order.qty) : order.customer_price);
    document.getElementById('ws_customer_unit_price').value = custUnitPrice;

    const vendUnitPrice = order.vendor_unit_price > 0 ? order.vendor_unit_price : (order.qty > 0 && order.vendor_cost > 0 ? (order.vendor_cost / order.qty) : '');
    document.getElementById('ws_vendor_unit_price').value = vendUnitPrice;

    document.getElementById('ws_shipping_cost').value = order.shipping_cost > 0 ? order.shipping_cost : '';
    document.getElementById('ws_vendor_notes').value = order.vendor_notes || '';
    document.getElementById('ws_qc_notes').value = order.qc_notes || '';

    // Rejection Banner
    const rejBox = document.getElementById('ws_rejection_banner');
    if (order.status === 'rejected' && order.rejection_reason) {
        rejBox.classList.remove('hidden');
        document.getElementById('ws_rejection_text').innerText = order.rejection_reason;
    } else {
        rejBox.classList.add('hidden');
    }

    // Update Statusbar Highlight & Status Badge
    updateOdooStatusbar(order.status);

    // Calculate Totals & Profit
    calcOdooTotals();
}

function updateOdooStatusbar(status) {
    const stages = ['draft_customer', 'pending_approval', 'in_production', 'qc_passed', 'completed'];
    const badge = document.getElementById('ws_order_status_badge');

    const statusMap = {
        'draft_customer': { label: '1. Draft Customer', color: 'bg-amber-100 text-amber-800 border-amber-200' },
        'pending_approval': { label: '2. Menunggu ACC Owner', color: 'bg-indigo-100 text-indigo-800 border-indigo-200' },
        'in_production': { label: '3. Sedang Dikerjakan di Vendor', color: 'bg-blue-100 text-blue-800 border-blue-200' },
        'qc_passed': { label: '4. Lolos QC / Sampai', color: 'bg-purple-100 text-purple-800 border-purple-200' },
        'completed': { label: '5. Selesai (Closed)', color: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
        'rejected': { label: 'Ditolak Owner', color: 'bg-rose-100 text-rose-800 border-rose-200' },
    };

    const cur = statusMap[status] || { label: status, color: 'bg-slate-100 text-slate-800 border-slate-200' };
    badge.className = `badge ${cur.color} border text-xs px-2.5 py-1`;
    badge.innerText = cur.label;

    // Highlight Statusbar Stage Buttons
    stages.forEach(st => {
        const btn = document.getElementById(`stage_tab_${st}`);
        if (!btn) return;

        if (st === status) {
            btn.className = 'odoo-stage-btn px-3 py-1.5 transition border-r border-slate-300 flex items-center gap-1 font-bold bg-slate-900 text-white shadow-xs';
        } else {
            btn.className = 'odoo-stage-btn px-3 py-1.5 transition border-r border-slate-300 flex items-center gap-1 text-slate-600 hover:bg-slate-200';
        }
    });

    // Render Dynamic Action Buttons in Header
    renderOdooHeaderActions(status);
}

function switchOdooStage(targetStage) {
    // When clicking statusbar stage buttons
    updateOdooStatusbar(targetStage);
}

function renderOdooHeaderActions(status) {
    const slot = document.getElementById('odoo_dynamic_action_buttons');
    slot.innerHTML = '';
    if (!currentWsOrder) return;

    if (status === 'draft_customer' || status === 'rejected') {
        slot.innerHTML = `
            <button type="button" onclick="submitWsHppToOwner()" class="btn btn-sm btn-warning rounded-lg font-bold text-slate-900 px-3 shadow-xs">
                <i class="fa-solid fa-paper-plane me-1"></i> Ajukan ke Owner
            </button>
        `;
    } else if (status === 'pending_approval') {
        if (isOwnerOrSuper) {
            slot.innerHTML = `
                <button type="button" onclick="promptWsReject()" class="btn btn-sm btn-outline-danger rounded-lg font-bold px-2.5">
                    <i class="fa-solid fa-xmark me-1"></i> Tolak
                </button>
                <button type="button" onclick="submitWsApprove()" class="btn btn-sm btn-success rounded-lg font-bold px-3.5 shadow-xs">
                    <i class="fa-solid fa-check-double me-1"></i> ACC / Setujui
                </button>
            `;
        } else {
            slot.innerHTML = `<span class="text-xs text-indigo-700 italic font-semibold px-2">Menunggu ACC Owner</span>`;
        }
    } else if (status === 'in_production') {
        slot.innerHTML = `
            <button type="button" onclick="submitWsPassQc()" class="btn btn-sm btn-purple rounded-lg font-bold text-white bg-purple-700 hover:bg-purple-800 px-3 shadow-xs">
                <i class="fa-solid fa-clipboard-check me-1"></i> Barang Sampai & Lolos QC
            </button>
        `;
    } else if (status === 'qc_passed') {
        slot.innerHTML = `
            <button type="button" onclick="submitWsCloseOrder()" class="btn btn-sm btn-success rounded-lg font-bold px-3.5 shadow-xs">
                <i class="fa-solid fa-cash-register me-1"></i> Closing / Selesai
            </button>
        `;
    } else if (status === 'completed' && currentWsOrder.transaction_id) {
        slot.innerHTML = `
            <a href="/sales/${currentWsOrder.transaction_id}/receipt" target="_blank" class="btn btn-sm btn-outline-success rounded-lg font-bold px-3">
                <i class="fa-solid fa-receipt me-1"></i> Buka Invoice POS
            </a>
        `;
    }
}

// Live Totals & Margin Calculator
function calcOdooTotals() {
    const qty = parseInt(document.getElementById('ws_qty').value) || 1;
    const unit = document.getElementById('ws_unit').value || 'pcs';

    document.getElementById('ws_table_item_name').innerText = document.getElementById('ws_job_title').value || 'Item Cetak';
    document.getElementById('ws_table_item_desc').innerText = document.getElementById('ws_description').value || `${qty} ${unit}`;

    const custUnitPrice = parseFloat(document.getElementById('ws_customer_unit_price').value) || 0;
    const totalOmset = qty * custUnitPrice;

    const vendUnitPrice = parseFloat(document.getElementById('ws_vendor_unit_price').value) || 0;
    const vendorSubtotal = qty * vendUnitPrice;
    const shipping = parseFloat(document.getElementById('ws_shipping_cost').value) || 0;
    const totalHpp = vendorSubtotal + shipping;

    const profit = totalOmset - totalHpp;
    const marginPct = totalOmset > 0 ? ((profit / totalOmset) * 100).toFixed(1) : '0';

    document.getElementById('ws_sum_omset').innerText = `Rp ${Number(totalOmset).toLocaleString('id-ID')}`;
    document.getElementById('ws_sum_vendor_subtotal').innerText = `Rp ${Number(vendorSubtotal).toLocaleString('id-ID')}`;
    document.getElementById('ws_sum_total_hpp').innerText = `Rp ${Number(totalHpp).toLocaleString('id-ID')}`;
    document.getElementById('ws_sum_profit').innerText = `Rp ${Number(profit).toLocaleString('id-ID')}`;

    const badge = document.getElementById('ws_sum_margin_badge');
    if (profit < 0) {
        badge.className = 'badge bg-rose-600 text-white font-mono text-[10px]';
        badge.innerText = `Rugi ${marginPct}%`;
    } else {
        badge.className = 'badge bg-emerald-600 text-white font-mono text-[10px]';
        badge.innerText = `Margin ${marginPct}%`;
    }
}

// --- SAVE WORKSHEET CHANGES (UPDATE ANYTIME) ---
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
                Swal.fire({ icon: 'success', title: 'Diajukan ke Owner!', text: res.message })
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
            Swal.fire({ icon: 'success', title: 'Order di-ACC!', text: res.message })
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
        inputPlaceholder: 'Misal: Modal vendor terlalu tinggi, cari vendor lain...',
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
    Swal.fire({
        title: 'Closing Pesanan Cetak Luar?',
        text: 'Data transaksi resmi akan otomatis dibukukan ke Penjualan Harian & Kas POS.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Closing Sekarang',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#059669'
    }).then(r => {
        if (r.isConfirmed) {
            fetch(`/cetak-luar/${currentWsOrder.id}/close`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pesanan Selesai!',
                        text: res.message,
                        showCancelButton: true,
                        confirmButtonText: 'Cetak Invoice POS',
                        cancelButtonText: 'Tutup'
                    }).then(action => {
                        if (action.isConfirmed && res.receipt_url) {
                            window.open(res.receipt_url, '_blank');
                        }
                        location.reload();
                    });
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
    document.getElementById('create_is_dp').value = '0';
    toggleCreateDp('0');
    calcCreateCustomerPrice();

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCreateOrder'));
    modal.show();
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
}

function toggleCreateDp(isDp) {
    const box = document.getElementById('create_dp_container');
    if (isDp == '1') box.classList.remove('hidden');
    else box.classList.add('hidden');
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
