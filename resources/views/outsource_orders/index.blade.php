@extends('layouts.app')

@section('title', 'Cetak di Luar (Vendor Outsource)')
@section('page-title', 'Modul Cetak di Luar (Outsource Pipeline)')

@section('action-buttons')
<button type="button" onclick="openCreateOrderWizard()" class="btn btn-primary btn-sm rounded-xl font-bold shadow-sm flex items-center gap-1.5">
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
                                    
                                    <!-- Open Lembar Kerja Wizard Modal -->
                                    <button type="button" onclick="openOrderWorksheet({{ $order->id }})" 
                                            class="btn btn-xs btn-primary py-1 px-2.5 rounded-lg font-bold shadow-sm flex items-center gap-1">
                                        <i class="fa-solid fa-layer-group"></i>
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
<!-- MODAL: LEMBAR KERJA MULTI-STEP PIPELINE WIZARD (INTERAKTIF 5-TAHAP)       -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalWorksheet" tabindex="-1" aria-labelledby="modalWorksheetLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 780px;">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden" style="border-radius: 1.25rem;">
            
            <!-- Modal Header with Stepper -->
            <div class="px-6 py-4 bg-slate-900 text-white border-b border-slate-800">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-md">
                            <i class="fa-solid fa-layer-group text-lg"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h5 class="text-base font-bold mb-0 text-white" id="ws_order_title">Lembar Kerja Pesanan</h5>
                                <span id="ws_order_badge" class="badge bg-blue-500/20 text-blue-300 border border-blue-400/30 text-[10px] font-mono">#OUT-0000</span>
                            </div>
                            <span class="text-xs text-slate-400" id="ws_order_subtitle">Multi-Step Workflow Pipeline & Tracking</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- 5-STAGE PROGRESS STEPPER (Connecting Line + Circles) -->
                <div class="pt-4 px-2">
                    <div class="relative flex items-center justify-between">
                        <!-- Horizontal Connecting Background Line -->
                        <div class="absolute left-6 right-6 top-1/2 -translate-y-1/2 h-1 bg-slate-700 z-0"></div>
                        <!-- Active Filled Progress Line -->
                        <div id="ws_progress_bar" class="absolute left-6 top-1/2 -translate-y-1/2 h-1 bg-blue-500 transition-all duration-300 z-0" style="width: 0%;"></div>

                        <!-- Step 1 Circle -->
                        <button type="button" onclick="goToWorksheetStep(1)" class="ws-step-node relative z-10 flex flex-col items-center group cursor-pointer focus:outline-none">
                            <div id="ws_node_1" class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold font-mono transition-all duration-200 bg-blue-600 text-white ring-4 ring-slate-900 shadow-md">
                                1
                            </div>
                            <span id="ws_label_1" class="text-[10.5px] font-bold text-white mt-1.5 whitespace-nowrap">Draft Customer</span>
                        </button>

                        <!-- Step 2 Circle -->
                        <button type="button" onclick="goToWorksheetStep(2)" class="ws-step-node relative z-10 flex flex-col items-center group cursor-pointer focus:outline-none">
                            <div id="ws_node_2" class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold font-mono transition-all duration-200 bg-slate-800 text-slate-400 ring-4 ring-slate-900">
                                2
                            </div>
                            <span id="ws_label_2" class="text-[10.5px] font-medium text-slate-400 mt-1.5 whitespace-nowrap">HPP Vendor</span>
                        </button>

                        <!-- Step 3 Circle -->
                        <button type="button" onclick="goToWorksheetStep(3)" class="ws-step-node relative z-10 flex flex-col items-center group cursor-pointer focus:outline-none">
                            <div id="ws_node_3" class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold font-mono transition-all duration-200 bg-slate-800 text-slate-400 ring-4 ring-slate-900">
                                3
                            </div>
                            <span id="ws_label_3" class="text-[10.5px] font-medium text-slate-400 mt-1.5 whitespace-nowrap">ACC Owner</span>
                        </button>

                        <!-- Step 4 Circle -->
                        <button type="button" onclick="goToWorksheetStep(4)" class="ws-step-node relative z-10 flex flex-col items-center group cursor-pointer focus:outline-none">
                            <div id="ws_node_4" class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold font-mono transition-all duration-200 bg-slate-800 text-slate-400 ring-4 ring-slate-900">
                                4
                            </div>
                            <span id="ws_label_4" class="text-[10.5px] font-medium text-slate-400 mt-1.5 whitespace-nowrap">QC & Sampai</span>
                        </button>

                        <!-- Step 5 Circle -->
                        <button type="button" onclick="goToWorksheetStep(5)" class="ws-step-node relative z-10 flex flex-col items-center group cursor-pointer focus:outline-none">
                            <div id="ws_node_5" class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold font-mono transition-all duration-200 bg-slate-800 text-slate-400 ring-4 ring-slate-900">
                                5
                            </div>
                            <span id="ws_label_5" class="text-[10.5px] font-medium text-slate-400 mt-1.5 whitespace-nowrap">Selesai (Closing)</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Body: Step Panels -->
            <div class="p-6 bg-slate-50">
                <input type="hidden" id="ws_order_id">

                <!-- ======================================================== -->
                <!-- STAGE 1: DRAFT & PRODUK CUSTOMER                        -->
                <!-- ======================================================== -->
                <div id="ws-panel-1" class="ws-step-panel space-y-4">
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b">
                            <span class="text-xs font-bold uppercase text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-cube text-blue-600"></i> Rincian Pesanan Customer
                            </span>
                            <span class="text-[11px] text-slate-400">Dapat diedit jika ada revisi</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Pekerjaan / Produk <span class="text-rose-500">*</span></label>
                            <input type="text" id="ws_job_title" class="form-control text-xs font-semibold rounded-xl" placeholder="Nama produk / pekerjaan">
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Qty <span class="text-rose-500">*</span></label>
                                <input type="number" id="ws_qty" min="1" oninput="calcWsCustomerPrice()" class="form-control text-xs font-mono font-bold text-center rounded-xl">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Satuan</label>
                                <input type="text" id="ws_unit" oninput="calcWsCustomerPrice()" class="form-control text-xs text-center rounded-xl" placeholder="pcs, rim, buku">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-blue-900 uppercase mb-1">Harga Satuan (Rp) <span class="text-rose-500">*</span></label>
                                <input type="number" id="ws_customer_unit_price" min="0" step="500" oninput="calcWsCustomerPrice()" class="form-control text-xs font-mono font-bold text-blue-700 rounded-xl" placeholder="0">
                            </div>
                        </div>

                        <div class="bg-blue-50/70 p-2.5 rounded-xl border border-blue-100 flex justify-between items-center text-xs">
                            <span class="text-slate-600 font-medium" id="ws_customer_calc_text">1 pcs x Rp 0</span>
                            <strong class="font-mono text-blue-800 text-sm" id="ws_customer_total_display">Total Jual: Rp 0</strong>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Spesifikasi & Catatan Cetak</label>
                            <textarea id="ws_description" rows="2" class="form-control text-xs rounded-xl" placeholder="Bahan, ukuran, laminasi, warna..."></textarea>
                        </div>
                    </div>

                    <!-- Customer & Payment Info -->
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                        <span class="text-xs font-bold uppercase text-slate-800 block pb-2 border-b">
                            <i class="fa-solid fa-user-tag text-emerald-600 me-1"></i> Data Pelanggan & Pembayaran
                        </span>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Customer <span class="text-rose-500">*</span></label>
                                <input type="text" id="ws_customer_name" class="form-control text-xs rounded-xl" placeholder="Nama customer">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">No. WhatsApp / Telepon</label>
                                <input type="text" id="ws_customer_phone" class="form-control text-xs font-mono rounded-xl" placeholder="08xxxxxxxx">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-1 border-t">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Metode Bayar</label>
                                <select id="ws_payment_method" class="form-select form-select-sm text-xs font-bold rounded-xl">
                                    <option value="Cash">Tunai (Cash)</option>
                                    <option value="Transfer">Transfer Bank</option>
                                    <option value="QRIS">QRIS</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Nominal Dibayar (Rp)</label>
                                <input type="number" id="ws_paid_amount" min="0" step="1000" class="form-control text-xs font-mono font-bold rounded-xl" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- STAGE 2: HPP VENDOR & MARGIN KALKULATOR                 -->
                <!-- ======================================================== -->
                <div id="ws-panel-2" class="ws-step-panel hidden space-y-4">
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b">
                            <span class="text-xs font-bold uppercase text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-hand-holding-dollar text-amber-600"></i> Rincian Biaya Vendor (HPP)
                            </span>
                            <span class="text-[11px] text-slate-400">Input HPP untuk persetujuan Owner</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Vendor / Rekanan <span class="text-rose-500">*</span></label>
                                <input type="text" id="ws_vendor_name" class="form-control text-xs font-semibold rounded-xl" placeholder="Misal: Bintang Offset">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">No. HP / WA Vendor</label>
                                <input type="text" id="ws_vendor_phone" class="form-control text-xs font-mono rounded-xl" placeholder="08xxxxxxxx">
                            </div>
                        </div>

                        <!-- Harga Satuan Vendor -->
                        <div class="bg-rose-50/50 p-3.5 rounded-xl border border-rose-100 space-y-2">
                            <label class="block text-xs font-bold text-rose-900 uppercase">
                                <i class="fa-solid fa-tag text-rose-600 me-1"></i> Harga Modal Satuan Vendor (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text font-bold text-xs bg-rose-50 text-rose-700 border-rose-200">Rp</span>
                                <input type="number" id="ws_vendor_unit_price" min="0" step="500" oninput="calcWsVendorMargin()" class="form-control font-mono font-bold text-rose-700 text-xs border-rose-200" placeholder="0">
                                <span class="input-group-text text-xs text-slate-500 bg-white border-rose-200 font-mono" id="ws_vendor_unit_label">/ pcs</span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-slate-600 pt-1">
                                <span>Subtotal Modal Vendor (<span id="ws_vendor_calc_text">1 pcs x Rp 0</span>):</span>
                                <strong id="ws_vendor_subtotal_display" class="font-mono text-rose-700">Rp 0</strong>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Ongkos Kirim / Pengiriman (Opsional)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text font-bold text-xs bg-slate-100 text-slate-700">Rp</span>
                                <input type="number" id="ws_shipping_cost" min="0" step="1000" oninput="calcWsVendorMargin()" class="form-control font-mono text-slate-800 text-xs rounded-r-xl" placeholder="0">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Catatan Tambahan untuk Vendor</label>
                            <textarea id="ws_vendor_notes" rows="2" class="form-control text-xs rounded-xl" placeholder="Spesifikasi vendor, janji selesai..."></textarea>
                        </div>
                    </div>

                    <!-- Live Margin Simulation Card -->
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white p-4 rounded-2xl shadow-md border border-slate-700 text-xs space-y-2">
                        <div class="flex justify-between items-center pb-2 border-b border-white/10">
                            <span class="text-slate-300 font-semibold">Simulasi Keuntungan & Margin:</span>
                            <span id="ws_margin_badge" class="badge bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10.5px] font-mono font-bold">Margin 0%</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 mt-2 text-center">
                            <div class="bg-white/5 p-2 rounded-xl border border-white/10">
                                <span class="text-[9.5px] text-slate-400 block uppercase">Harga Jual Customer</span>
                                <span id="ws_live_omset" class="font-mono font-bold text-blue-300 block mt-0.5">Rp 0</span>
                            </div>
                            <div class="bg-white/5 p-2 rounded-xl border border-white/10">
                                <span class="text-[9.5px] text-slate-400 block uppercase">Total Modal (HPP)</span>
                                <span id="ws_live_cost" class="font-mono font-bold text-rose-300 block mt-0.5">Rp 0</span>
                            </div>
                            <div class="bg-emerald-500/10 p-2 rounded-xl border border-emerald-500/30">
                                <span class="text-[9.5px] text-emerald-300 block uppercase font-bold">Estimasi Laba Bersih</span>
                                <span id="ws_live_profit" class="font-mono font-black text-emerald-400 block mt-0.5">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- STAGE 3: APPROVAL / ACC OWNER                           -->
                <!-- ======================================================== -->
                <div id="ws-panel-3" class="ws-step-panel hidden space-y-4">
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3 text-xs">
                        <div class="flex items-center justify-between pb-2 border-b">
                            <span class="text-xs font-bold uppercase text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-stamp text-indigo-600"></i> Status Persetujuan Owner
                            </span>
                            <span id="ws_appr_status_badge" class="badge bg-indigo-100 text-indigo-800 border text-[10px]">Menunggu ACC</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 rounded-xl border">
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Harga Jual:</span>
                                <strong id="ws_appr_omset" class="text-blue-700 font-mono text-sm block">Rp 0</strong>
                            </div>
                            <div class="text-end">
                                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Total Modal HPP:</span>
                                <strong id="ws_appr_cost" class="text-rose-700 font-mono text-sm block">Rp 0</strong>
                            </div>
                            <div class="pt-2 border-t col-span-2 flex justify-between items-center">
                                <span class="text-slate-600 font-bold uppercase">Margin Keuntungan:</span>
                                <strong id="ws_appr_margin" class="text-emerald-700 font-mono text-base">Rp 0 (0%)</strong>
                            </div>
                        </div>

                        <div id="ws_appr_log_container" class="hidden p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800">
                            <i class="fa-solid fa-circle-check me-1"></i> <span id="ws_appr_log_text">Telah di-ACC oleh Owner pada ...</span>
                        </div>

                        <!-- Form Alasan Tolak -->
                        <div id="ws_reject_form" class="hidden bg-rose-50 border border-rose-200 p-3 rounded-xl space-y-2">
                            <label class="block text-xs font-bold text-rose-900 uppercase">Alasan Penolakan <span class="text-rose-600">*</span></label>
                            <input type="text" id="ws_rejection_reason" class="form-control form-control-sm text-xs rounded-xl" placeholder="Misal: Modal vendor terlalu tinggi, cari vendor lain...">
                        </div>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- STAGE 4: PENGERJAAN VENDOR & QUALITY CONTROL            -->
                <!-- ======================================================== -->
                <div id="ws-panel-4" class="ws-step-panel hidden space-y-4">
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3 text-xs">
                        <div class="flex items-center justify-between pb-2 border-b">
                            <span class="text-xs font-bold uppercase text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-clipboard-check text-purple-600"></i> Status Pengerjaan & QC Barang
                            </span>
                            <span id="ws_qc_status_badge" class="badge bg-blue-100 text-blue-800 border text-[10px]">Sedang Dikerjakan</span>
                        </div>

                        <div class="p-3 bg-slate-50 rounded-xl border space-y-1">
                            <div><strong>Vendor:</strong> <span id="ws_qc_vendor">-</span></div>
                            <div><strong>Pekerjaan:</strong> <span id="ws_qc_job">-</span></div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Catatan Hasil Pengecekan Kualitas (QC)</label>
                            <textarea id="ws_qc_notes" rows="3" class="form-control text-xs rounded-xl" placeholder="Barang sampai lengkap, cetakan tajam, foil rapi..."></textarea>
                        </div>

                        <div id="ws_qc_log_container" class="hidden p-3 bg-purple-50 border border-purple-200 rounded-xl text-purple-800">
                            <i class="fa-solid fa-check-double me-1"></i> <span id="ws_qc_log_text">Lolos QC oleh Kasir</span>
                        </div>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- STAGE 5: CLOSING & SELESAI                              -->
                <!-- ======================================================== -->
                <div id="ws-panel-5" class="ws-step-panel hidden space-y-4">
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3 text-xs">
                        <div class="flex items-center justify-between pb-2 border-b">
                            <span class="text-xs font-bold uppercase text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i> Closing Pesanan & Pembukuan Kas
                            </span>
                            <span id="ws_closing_badge" class="badge bg-emerald-100 text-emerald-800 border text-[10px]">Selesai</span>
                        </div>

                        <div class="p-3.5 bg-slate-50 rounded-2xl border space-y-2">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Total Penjualan:</span>
                                <strong id="ws_close_omset" class="font-mono text-slate-900">Rp 0</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Total HPP Modal:</span>
                                <strong id="ws_close_cost" class="font-mono text-rose-700">Rp 0</strong>
                            </div>
                            <div class="pt-2 border-t flex justify-between items-center">
                                <span class="font-bold text-slate-800 uppercase">Laba Bersih Realisasi:</span>
                                <strong id="ws_close_profit" class="font-mono text-emerald-700 text-base">Rp 0</strong>
                            </div>
                        </div>

                        <div id="ws_close_tx_info" class="hidden p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 space-y-1">
                            <div><i class="fa-solid fa-file-invoice text-emerald-600 me-1"></i> Telah dibukukan ke Penjualan Harian POS: <strong id="ws_close_invoice">-</strong></div>
                            <div class="pt-1">
                                <a href="#" id="ws_btn_view_pos_receipt" target="_blank" class="btn btn-xs btn-outline-emerald font-bold rounded-lg">
                                    <i class="fa-solid fa-receipt me-1"></i> Buka Struk POS Resmi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer: Navigation & Dynamic Action Buttons -->
            <div class="px-6 py-3.5 bg-white border-t border-slate-200 flex justify-between items-center">
                <button type="button" id="ws_btn_prev" onclick="navigateWorksheet(-1)" class="btn btn-sm btn-outline-secondary rounded-xl font-bold px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Sebelumnya
                </button>

                <!-- Center: Save Changes button (Available for editing anytime) -->
                <button type="button" onclick="saveWorksheetChanges()" class="btn btn-sm btn-light border text-slate-700 rounded-xl font-bold px-3 shadow-xs">
                    <i class="fa-solid fa-floppy-disk me-1 text-blue-600"></i> Simpan Data Lembar Kerja
                </button>

                <!-- Right: Stage-Specific Action / Next Button -->
                <div class="flex items-center gap-2">
                    <div id="ws_action_slot"></div>

                    <button type="button" id="ws_btn_next" onclick="navigateWorksheet(1)" class="btn btn-sm btn-primary rounded-xl font-bold shadow-sm px-4">
                        <span>Seterusnya</span> <i class="fa-solid fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: BUAT PESANAN BARU (WIZARD STEPPER)                                 -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalCreateOrder" tabindex="-1" aria-labelledby="modalCreateOrderLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden" style="border-radius: 1.25rem;">
            
            <!-- Modal Header -->
            <div class="px-5 py-3.5 bg-slate-900 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-md">
                            <i class="fa-solid fa-wand-magic-sparkles text-sm"></i>
                        </div>
                        <div>
                            <h6 class="text-sm font-bold mb-0 text-white">Buat Lembar Kerja Baru</h6>
                            <span class="text-[11px] text-slate-400">Tahap 1: Input Pesanan & Pembayaran Customer</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <form id="formCreateOrder" onsubmit="submitCreateOrder(event)" class="bg-slate-50">
                <input type="hidden" name="customer_price" id="create_customer_price" value="0">

                <div class="p-5 space-y-4">
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                            Nama Pekerjaan / Produk <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="job_title" id="create_job_title" required 
                               class="form-control text-xs font-semibold py-2.5 rounded-xl" 
                               placeholder="Misal: Cetak Buku Agenda Kulit Foil, Banner Outdoor 3x1m">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                Jumlah / Qty <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" name="qty" id="create_qty" value="1" min="1" required 
                                   oninput="calcCreateCustomerPrice()"
                                   class="form-control text-xs font-mono font-bold text-center py-2 rounded-xl">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Satuan</label>
                            <input type="text" name="unit" id="create_unit" value="pcs" 
                                   oninput="calcCreateCustomerPrice()"
                                   class="form-control text-xs text-center py-2 rounded-xl" placeholder="pcs, rim, buku">
                        </div>
                    </div>

                    <!-- HARGA SATUAN -->
                    <div class="bg-white p-3.5 rounded-2xl border border-blue-200 shadow-sm space-y-2">
                        <label class="block text-xs font-bold text-blue-900 uppercase">
                            <i class="fa-solid fa-tag text-blue-600 me-1"></i> Harga Jual Satuan (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text font-bold text-sm bg-blue-50 text-blue-700 border-blue-200">Rp</span>
                            <input type="number" name="customer_unit_price" id="create_unit_price" min="0" step="500" required 
                                   oninput="calcCreateCustomerPrice()" 
                                   class="form-control font-mono font-black text-blue-700 text-base border-blue-200" placeholder="0">
                            <span class="input-group-text text-xs text-slate-500 bg-slate-50 border-blue-200 font-mono" id="create_unit_label">/ pcs</span>
                        </div>

                        <!-- LIVE CALCULATION PREVIEW -->
                        <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-2.5 flex items-center justify-between text-xs">
                            <span id="create_calc_preview_text" class="font-mono font-semibold text-slate-700">1 pcs x Rp 0</span>
                            <strong id="create_total_display" class="font-mono text-blue-800 text-sm">Total Jual: Rp 0</strong>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Catatan & Spesifikasi Cetak</label>
                        <textarea name="description" id="create_description" rows="2" class="form-control text-xs rounded-xl" placeholder="Bahan kertas, laminasi, warna, finishing..."></textarea>
                    </div>

                    <!-- Customer & Payment Details -->
                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                        <div class="flex items-center justify-between pb-1 border-b">
                            <span class="text-xs font-bold uppercase text-slate-700">Data Pelanggan & Bayar</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Customer <span class="text-rose-500">*</span></label>
                                <input type="text" name="customer_name" id="create_customer_name" required class="form-control text-xs py-2 rounded-xl" placeholder="Nama pelanggan">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">No. WhatsApp / HP</label>
                                <input type="text" name="customer_phone" id="create_customer_phone" class="form-control text-xs font-mono py-2 rounded-xl" placeholder="08xxxxxxxx">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-1 border-t">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Metode Bayar</label>
                                <select name="payment_method" id="create_payment_method" class="form-select form-select-sm text-xs font-bold rounded-xl">
                                    <option value="Cash">Tunai (Cash)</option>
                                    <option value="Transfer">Transfer Bank</option>
                                    <option value="QRIS">QRIS</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Status Bayar</label>
                                <select name="is_dp" id="create_is_dp" onchange="toggleCreateDp(this.value)" class="form-select form-select-sm text-xs font-bold rounded-xl">
                                    <option value="0">Lunas (100%)</option>
                                    <option value="1">Uang Muka (DP)</option>
                                </select>
                            </div>
                        </div>

                        <div id="create_dp_container" class="hidden pt-1">
                            <label class="block text-xs font-bold text-amber-800 uppercase mb-1">Nominal DP Dibayar (Rp)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text font-bold text-xs bg-amber-50 text-amber-700">Rp</span>
                                <input type="number" name="paid_amount" id="create_paid_amount" min="0" step="1000" class="form-control font-mono font-bold text-amber-800 text-xs rounded-r-xl" placeholder="0">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="px-5 py-3.5 bg-white border-t border-slate-200 flex justify-between items-center">
                    <button type="button" class="btn btn-sm btn-light rounded-xl font-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitCreateOrder" class="btn btn-sm btn-primary rounded-xl font-bold shadow-sm px-4">
                        <i class="fa-solid fa-check me-1"></i> Simpan & Cetak Struk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentWsStep = 1;
let currentWsOrder = null;
const isOwnerOrSuper = {{ ($isOwnerOrSuper || auth()->user()->isManager()) ? 'true' : 'false' }};

// --- OPEN WORKSHEET FOR EXISTING ORDER ---
function openOrderWorksheet(orderId) {
    fetch(`/cetak-luar/${orderId}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.order) {
            currentWsOrder = res.order;
            populateWorksheetData(currentWsOrder);

            // Determine initial active step based on order status
            let initialStep = 1;
            if (currentWsOrder.status === 'pending_approval') initialStep = 3;
            else if (currentWsOrder.status === 'in_production') initialStep = 4;
            else if (currentWsOrder.status === 'qc_passed' || currentWsOrder.status === 'completed') initialStep = 5;
            else if (currentWsOrder.status === 'rejected') initialStep = 2;
            else if (currentWsOrder.vendor_cost > 0) initialStep = 2;

            goToWorksheetStep(initialStep);

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

function populateWorksheetData(order) {
    document.getElementById('ws_order_id').value = order.id;
    document.getElementById('ws_order_title').innerText = `Lembar Kerja: ${order.job_title}`;
    document.getElementById('ws_order_badge').innerText = `#${order.order_number}`;
    document.getElementById('ws_order_subtitle').innerText = `Customer: ${order.customer_name} | Kasir: ${order.user ? (order.user.full_name || order.user.username) : 'Kasir'}`;

    // Step 1: Customer Specs
    document.getElementById('ws_job_title').value = order.job_title || '';
    document.getElementById('ws_qty').value = order.qty || 1;
    document.getElementById('ws_unit').value = order.unit || 'pcs';
    const custUnitPrice = order.customer_unit_price > 0 ? order.customer_unit_price : (order.qty > 0 ? (order.customer_price / order.qty) : order.customer_price);
    document.getElementById('ws_customer_unit_price').value = custUnitPrice;
    document.getElementById('ws_description').value = order.description || '';
    document.getElementById('ws_customer_name').value = order.customer_name || '';
    document.getElementById('ws_customer_phone').value = order.customer_phone || '';
    document.getElementById('ws_payment_method').value = order.payment_method || 'Cash';
    document.getElementById('ws_paid_amount').value = order.paid_amount || order.customer_price;

    calcWsCustomerPrice();

    // Step 2: Vendor HPP
    document.getElementById('ws_vendor_name').value = order.vendor_name || '';
    document.getElementById('ws_vendor_phone').value = order.vendor_phone || '';
    const vendUnitPrice = order.vendor_unit_price > 0 ? order.vendor_unit_price : (order.qty > 0 && order.vendor_cost > 0 ? (order.vendor_cost / order.qty) : '');
    document.getElementById('ws_vendor_unit_price').value = vendUnitPrice;
    document.getElementById('ws_shipping_cost').value = order.shipping_cost > 0 ? order.shipping_cost : '';
    document.getElementById('ws_vendor_notes').value = order.vendor_notes || '';

    calcWsVendorMargin();

    // Step 3: Approval Log
    document.getElementById('ws_appr_omset').innerText = `Rp ${Number(order.customer_price).toLocaleString('id-ID')}`;
    document.getElementById('ws_appr_cost').innerText = `Rp ${Number(order.total_cost).toLocaleString('id-ID')}`;
    document.getElementById('ws_appr_margin').innerText = `Rp ${Number(order.estimated_margin).toLocaleString('id-ID')} (${order.margin_percent}%)`;
    
    const apprBadge = document.getElementById('ws_appr_status_badge');
    const apprLogBox = document.getElementById('ws_appr_log_container');
    if (order.status === 'in_production' || order.status === 'qc_passed' || order.status === 'completed') {
        apprBadge.className = 'badge bg-emerald-100 text-emerald-800 border text-[10px]';
        apprBadge.innerText = 'Sudah di-ACC';
        apprLogBox.classList.remove('hidden');
        document.getElementById('ws_appr_log_text').innerText = `Telah disetujui (ACC) pada ${order.approved_at ? new Date(order.approved_at).toLocaleString('id-ID') : 'sebelumnya'}`;
    } else if (order.status === 'rejected') {
        apprBadge.className = 'badge bg-rose-100 text-rose-800 border text-[10px]';
        apprBadge.innerText = 'Ditolak';
        apprLogBox.classList.add('hidden');
    } else {
        apprBadge.className = 'badge bg-indigo-100 text-indigo-800 border text-[10px]';
        apprBadge.innerText = 'Menunggu ACC';
        apprLogBox.classList.add('hidden');
    }

    // Step 4: QC
    document.getElementById('ws_qc_vendor').innerText = `${order.vendor_name || 'Vendor Luar'} ${order.vendor_phone ? '(' + order.vendor_phone + ')' : ''}`;
    document.getElementById('ws_qc_job').innerText = `${order.job_title} (${order.qty} ${order.unit})`;
    document.getElementById('ws_qc_notes').value = order.qc_notes || 'Kualitas cetak sesuai pesanan.';
    const qcLogBox = document.getElementById('ws_qc_log_container');
    if (order.status === 'qc_passed' || order.status === 'completed') {
        document.getElementById('ws_qc_status_badge').className = 'badge bg-emerald-100 text-emerald-800 border text-[10px]';
        document.getElementById('ws_qc_status_badge').innerText = 'Lolos QC';
        qcLogBox.classList.remove('hidden');
    } else {
        document.getElementById('ws_qc_status_badge').className = 'badge bg-blue-100 text-blue-800 border text-[10px]';
        document.getElementById('ws_qc_status_badge').innerText = 'Dalam Pengerjaan';
        qcLogBox.classList.add('hidden');
    }

    // Step 5: Closing
    document.getElementById('ws_close_omset').innerText = `Rp ${Number(order.customer_price).toLocaleString('id-ID')}`;
    document.getElementById('ws_close_cost').innerText = `Rp ${Number(order.total_cost).toLocaleString('id-ID')}`;
    document.getElementById('ws_close_profit').innerText = `Rp ${Number(order.estimated_margin).toLocaleString('id-ID')} (${order.margin_percent}%)`;
    const closeTxInfo = document.getElementById('ws_close_tx_info');
    if (order.status === 'completed' && order.transaction) {
        closeTxInfo.classList.remove('hidden');
        document.getElementById('ws_close_invoice').innerText = order.transaction.invoice_number;
        document.getElementById('ws_btn_view_pos_receipt').href = `/sales/${order.transaction_id}/receipt`;
    } else {
        closeTxInfo.classList.add('hidden');
    }
}

// --- STEPPER INTERACTIVE NAVIGATION (BOLAK BALIK) ---
function goToWorksheetStep(step) {
    currentWsStep = step;

    // Show/Hide Panels
    document.querySelectorAll('.ws-step-panel').forEach(p => p.classList.add('hidden'));
    document.getElementById(`ws-panel-${step}`).classList.remove('hidden');

    // Update Connecting Progress Bar Width
    const progressBar = document.getElementById('ws_progress_bar');
    const percentMap = { 1: '0%', 2: '25%', 3: '50%', 4: '75%', 5: '100%' };
    progressBar.style.width = percentMap[step] || '0%';

    // Update Circle Nodes
    for (let i = 1; i <= 5; i++) {
        const node = document.getElementById(`ws_node_${i}`);
        const label = document.getElementById(`ws_label_${i}`);

        if (i === currentWsStep) {
            node.className = 'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold font-mono transition-all duration-200 bg-blue-600 text-white ring-4 ring-slate-900 shadow-md scale-110';
            node.innerHTML = i;
            label.className = 'text-[10.5px] font-bold text-white mt-1.5 whitespace-nowrap';
        } else if (i < currentWsStep) {
            node.className = 'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold font-mono transition-all duration-200 bg-emerald-600 text-white ring-4 ring-slate-900';
            node.innerHTML = '<i class="fa-solid fa-check text-[11px]"></i>';
            label.className = 'text-[10.5px] font-medium text-emerald-300 mt-1.5 whitespace-nowrap';
        } else {
            node.className = 'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold font-mono transition-all duration-200 bg-slate-800 text-slate-400 ring-4 ring-slate-900';
            node.innerHTML = i;
            label.className = 'text-[10.5px] font-medium text-slate-400 mt-1.5 whitespace-nowrap';
        }
    }

    // Prev / Next button states
    document.getElementById('ws_btn_prev').style.visibility = (step === 1) ? 'hidden' : 'visible';
    document.getElementById('ws_btn_next').style.visibility = (step === 5) ? 'hidden' : 'visible';

    // Render Stage-Specific Action Buttons in Action Slot
    renderWsActionSlot(step);
}

function navigateWorksheet(direction) {
    const nextStep = currentWsStep + direction;
    if (nextStep >= 1 && nextStep <= 5) {
        goToWorksheetStep(nextStep);
    }
}

// Stage Specific Action Slot
function renderWsActionSlot(step) {
    const slot = document.getElementById('ws_action_slot');
    slot.innerHTML = '';
    if (!currentWsOrder) return;

    if (step === 1) {
        slot.innerHTML = `
            <a href="/cetak-luar/${currentWsOrder.id}/receipt" target="_blank" class="btn btn-sm btn-outline-secondary rounded-xl font-bold px-3">
                <i class="fa-solid fa-print me-1"></i> Print Struk
            </a>
        `;
    } else if (step === 2) {
        if (currentWsOrder.status === 'draft_customer' || currentWsOrder.status === 'rejected') {
            slot.innerHTML = `
                <button type="button" onclick="submitWsHppToOwner()" class="btn btn-sm btn-warning rounded-xl font-bold text-slate-900 shadow-sm px-3.5">
                    <i class="fa-solid fa-paper-plane me-1"></i> Ajukan ke Owner
                </button>
            `;
        }
    } else if (step === 3) {
        if (currentWsOrder.status === 'pending_approval') {
            if (isOwnerOrSuper) {
                slot.innerHTML = `
                    <button type="button" onclick="toggleWsRejectBox()" class="btn btn-sm btn-outline-danger rounded-xl font-bold px-2.5">
                        <i class="fa-solid fa-xmark me-1"></i> Tolak
                    </button>
                    <button type="button" onclick="submitWsApprove()" class="btn btn-sm btn-success rounded-xl font-bold px-3.5 shadow-sm">
                        <i class="fa-solid fa-check-double me-1"></i> ACC / Setujui
                    </button>
                `;
            }
        }
    } else if (step === 4) {
        if (currentWsOrder.status === 'in_production' || currentWsOrder.status === 'pending_approval') {
            slot.innerHTML = `
                <button type="button" onclick="submitWsPassQc()" class="btn btn-sm btn-purple rounded-xl font-bold text-white bg-purple-700 hover:bg-purple-800 shadow-sm px-3.5">
                    <i class="fa-solid fa-clipboard-check me-1"></i> Lolos QC
                </button>
            `;
        }
    } else if (step === 5) {
        if (currentWsOrder.status === 'qc_passed') {
            slot.innerHTML = `
                <button type="button" onclick="submitWsCloseOrder()" class="btn btn-sm btn-success rounded-xl font-bold px-4 shadow-sm">
                    <i class="fa-solid fa-cash-register me-1"></i> Closing & Bukukan Penjualan
                </button>
            `;
        }
    }
}

// --- CALCULATION HELPERS FOR WORKSHEET ---
function calcWsCustomerPrice() {
    const qty = parseInt(document.getElementById('ws_qty').value) || 1;
    const unit = document.getElementById('ws_unit').value || 'pcs';
    const unitPrice = parseFloat(document.getElementById('ws_customer_unit_price').value) || 0;
    const total = qty * unitPrice;

    document.getElementById('ws_vendor_unit_label').innerText = `/ ${unit}`;
    document.getElementById('ws_customer_calc_text').innerText = `${qty} ${unit} x Rp ${Number(unitPrice).toLocaleString('id-ID')}`;
    document.getElementById('ws_customer_total_display').innerText = `Total Jual: Rp ${Number(total).toLocaleString('id-ID')}`;

    calcWsVendorMargin();
}

function calcWsVendorMargin() {
    const qty = parseInt(document.getElementById('ws_qty').value) || 1;
    const unit = document.getElementById('ws_unit').value || 'pcs';
    const custUnitPrice = parseFloat(document.getElementById('ws_customer_unit_price').value) || 0;
    const omset = qty * custUnitPrice;

    const vendorUnitPrice = parseFloat(document.getElementById('ws_vendor_unit_price').value) || 0;
    const vendorCost = qty * vendorUnitPrice;
    const shipping = parseFloat(document.getElementById('ws_shipping_cost').value) || 0;
    const totalCost = vendorCost + shipping;
    const profit = omset - totalCost;
    const marginPct = omset > 0 ? ((profit / omset) * 100).toFixed(1) : 0;

    document.getElementById('ws_vendor_calc_text').innerText = `${qty} ${unit} x Rp ${Number(vendorUnitPrice).toLocaleString('id-ID')}`;
    document.getElementById('ws_vendor_subtotal_display').innerText = `Rp ${Number(vendorCost).toLocaleString('id-ID')}`;

    document.getElementById('ws_live_omset').innerText = `Rp ${Number(omset).toLocaleString('id-ID')}`;
    document.getElementById('ws_live_cost').innerText = `Rp ${Number(totalCost).toLocaleString('id-ID')}`;
    document.getElementById('ws_live_profit').innerText = `Rp ${Number(profit).toLocaleString('id-ID')}`;

    const badge = document.getElementById('ws_margin_badge');
    if (badge) {
        if (profit < 0) {
            badge.className = 'badge bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[10.5px] font-mono font-bold';
            badge.innerText = `Rugi ${marginPct}%`;
        } else {
            badge.className = 'badge bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10.5px] font-mono font-bold';
            badge.innerText = `Margin ${marginPct}%`;
        }
    }
}

// --- SAVE / UPDATE WORKSHEET DATA (ANYTIME) ---
function saveWorksheetChanges() {
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
            populateWorksheetData(currentWsOrder);
            Swal.fire({
                icon: 'success',
                title: 'Data Tersimpan!',
                text: res.message,
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
        }
    })
    .catch(err => {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    });
}

// Actions from Worksheet
function submitWsHppToOwner() {
    saveWorksheetChanges();
    setTimeout(() => {
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
                vendor_unit_price: document.getElementById('ws_vendor_unit_price').value,
                vendor_cost: (parseInt(document.getElementById('ws_qty').value) || 1) * (parseFloat(document.getElementById('ws_vendor_unit_price').value) || 0),
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
    }, 400);
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

function toggleWsRejectBox() {
    const box = document.getElementById('ws_reject_form');
    if (box.classList.contains('hidden')) {
        box.classList.remove('hidden');
        document.getElementById('ws_rejection_reason').focus();
    } else {
        const reason = document.getElementById('ws_rejection_reason').value.trim();
        if (!reason) {
            Swal.fire({ icon: 'warning', title: 'Alasan Diperlukan', text: 'Silakan isi alasan penolakan.' });
            return;
        }
        fetch(`/cetak-luar/${currentWsOrder.id}/reject`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ rejection_reason: reason })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                Swal.fire({ icon: 'info', title: 'Ditolak', text: res.message })
                    .then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
            }
        });
    }
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
function openCreateOrderWizard() {
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
    document.getElementById('create_total_display').innerText = `Total Jual: Rp ${Number(total).toLocaleString('id-ID')}`;
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
