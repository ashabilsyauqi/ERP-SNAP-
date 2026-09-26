@extends('layouts.app')

@section('title', 'Cetak di Luar (Vendor Outsource)')
@section('page-title', 'Modul Cetak di Luar (Outsource Pipeline)')

@section('action-buttons')
<button type="button" onclick="openCreateOrderWizard()" class="btn btn-primary btn-sm rounded-xl font-bold shadow-sm flex items-center gap-1.5">
    <i class="fa-solid fa-wand-magic-sparkles"></i>
    <span>Buat Lembar Kerja Baru (Wizard)</span>
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
                <i class="fa-solid fa-file-invoice text-amber-600"></i> Draft Customer
            </span>
            <div class="text-2xl font-black text-amber-900 mt-2 font-mono">{{ $counts['draft_customer'] }}</div>
            <span class="text-[10.5px] text-amber-700 mt-1">Menunggu input HPP</span>
        </div>

        <!-- 2. Menunggu ACC Owner -->
        <div class="bg-white p-4 rounded-2xl border border-indigo-200 shadow-sm flex flex-col justify-between bg-indigo-50/20">
            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-800 flex items-center gap-1">
                <i class="fa-solid fa-user-clock text-indigo-600"></i> Menunggu ACC
            </span>
            <div class="text-2xl font-black text-indigo-900 mt-2 font-mono">{{ $counts['pending_approval'] }}</div>
            <span class="text-[10.5px] text-indigo-700 mt-1">Antrean persetujuan</span>
        </div>

        <!-- 3. Sedang Dikerjakan -->
        <div class="bg-white p-4 rounded-2xl border border-blue-200 shadow-sm flex flex-col justify-between bg-blue-50/20">
            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-800 flex items-center gap-1">
                <i class="fa-solid fa-gears text-blue-600"></i> Di Vendor
            </span>
            <div class="text-2xl font-black text-blue-900 mt-2 font-mono">{{ $counts['in_production'] }}</div>
            <span class="text-[10.5px] text-blue-700 mt-1">Proses cetak</span>
        </div>

        <!-- 4. Lolos QC / Sampai -->
        <div class="bg-white p-4 rounded-2xl border border-purple-200 shadow-sm flex flex-col justify-between bg-purple-50/20">
            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-800 flex items-center gap-1">
                <i class="fa-solid fa-clipboard-check text-purple-600"></i> Lolos QC
            </span>
            <div class="text-2xl font-black text-purple-900 mt-2 font-mono">{{ $counts['qc_passed'] }}</div>
            <span class="text-[10.5px] text-purple-700 mt-1">Siap closing</span>
        </div>

        <!-- 5. Selesai (Closed) -->
        <div class="bg-white p-4 rounded-2xl border border-emerald-200 shadow-sm flex flex-col justify-between bg-emerald-50/20">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 flex items-center gap-1">
                <i class="fa-solid fa-circle-check text-emerald-600"></i> Selesai
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
                        <th class="py-3 px-4 text-center">Aksi / Alur Pipeline</th>
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
                                <strong class="font-mono text-blue-600 block">{{ $order->order_number }}</strong>
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
                                    
                                    <!-- Print Struk Customer (Always available) -->
                                    <a href="{{ route('outsource-orders.receipt', $order->id) }}" target="_blank" 
                                       class="btn btn-xs btn-outline-secondary py-1 px-2 rounded-lg font-semibold" title="Cetak Struk Pesanan Customer">
                                        <i class="fa-solid fa-print me-1"></i> Struk
                                    </a>

                                    <!-- STAGE 1 -> STAGE 2: Input HPP & Submit ACC -->
                                    @if(in_array($order->status, ['draft_customer', 'rejected']))
                                        <button type="button" onclick="openSubmitHppModal({{ json_encode($order) }})" 
                                                class="btn btn-xs btn-warning py-1 px-2 rounded-lg font-bold shadow-sm flex items-center gap-1 text-slate-900">
                                            <i class="fa-solid fa-hand-holding-dollar"></i>
                                            <span>{{ $order->status === 'rejected' ? 'Revisi HPP' : 'Input HPP & ACC' }}</span>
                                        </button>
                                    @endif

                                    <!-- STAGE 2: Approval by Owner/Manager -->
                                    @if($order->status === 'pending_approval')
                                        @if($isOwnerOrSuper || auth()->user()->isManager())
                                            <button type="button" onclick="openApprovalModal({{ json_encode($order) }})" 
                                                    class="btn btn-xs btn-primary py-1 px-2 rounded-lg font-bold shadow-sm flex items-center gap-1">
                                                <i class="fa-solid fa-circle-check"></i>
                                                <span>ACC / Tolak</span>
                                            </button>
                                        @else
                                            <span class="text-[10px] text-slate-400 italic">Menunggu ACC Owner</span>
                                        @endif
                                    @endif

                                    <!-- STAGE 3: Barang Sampai & QC -->
                                    @if($order->status === 'in_production')
                                        <button type="button" onclick="openQcModal({{ json_encode($order) }})" 
                                                class="btn btn-xs btn-info text-white py-1 px-2 rounded-lg font-bold shadow-sm flex items-center gap-1">
                                            <i class="fa-solid fa-box-open"></i>
                                            <span>Barang Sampai / QC</span>
                                        </button>
                                    @endif

                                    <!-- STAGE 4: Closing Order to Daily Sales -->
                                    @if($order->status === 'qc_passed')
                                        <button type="button" onclick="confirmClosingOrder({{ json_encode($order) }})" 
                                                class="btn btn-xs btn-success py-1 px-2 rounded-lg font-bold shadow-sm flex items-center gap-1">
                                            <i class="fa-solid fa-cash-register"></i>
                                            <span>Closing / Selesai</span>
                                        </button>
                                    @endif

                                    <!-- STAGE 5: View Completed Official Transaction -->
                                    @if($order->status === 'completed' && $order->transaction_id)
                                        <a href="{{ route('sales.receipt', $order->transaction_id) }}" target="_blank" 
                                           class="btn btn-xs btn-outline-success py-1 px-2 rounded-lg font-bold" title="Lihat Struk Penjualan POS Resmi">
                                            <i class="fa-solid fa-receipt me-1"></i> Invoice POS
                                        </a>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                                Belum ada pesanan cetak di luar. Klik tombol <strong>"Buat Lembar Kerja Baru"</strong> di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: LEMBAR KERJA MULTI-STEP WIZARD UI (STAGE 1: DRAFT CUSTOMER)      -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalCreateOrder" tabindex="-1" aria-labelledby="modalCreateOrderLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden" style="border-radius: 1.25rem;">
            
            <!-- Modal Header with Wizard Progress Bar -->
            <div class="px-5 py-3.5 bg-slate-900 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-md">
                            <i class="fa-solid fa-wand-magic-sparkles text-sm"></i>
                        </div>
                        <div>
                            <h6 class="text-sm font-bold mb-0 text-white">Lembar Kerja Cetak di Luar</h6>
                            <span class="text-[11px] text-slate-400">Form Wizard Tahap 1: Input Pesanan & Pembayaran</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Wizard Step Navigation Pills -->
                <div class="grid grid-cols-3 gap-2 mt-3.5 pt-3 border-t border-slate-800 text-center text-xs">
                    <div id="step-pill-1" class="wizard-step-pill active py-1.5 px-2 rounded-xl transition font-bold flex items-center justify-center gap-1.5 bg-blue-600 text-white">
                        <span class="w-4 h-4 rounded-full bg-white text-blue-600 text-[10px] flex items-center justify-center font-mono">1</span>
                        <span class="truncate">1. Produk & Satuan</span>
                    </div>
                    <div id="step-pill-2" class="wizard-step-pill py-1.5 px-2 rounded-xl transition font-semibold flex items-center justify-center gap-1.5 bg-slate-800 text-slate-400">
                        <span class="w-4 h-4 rounded-full bg-slate-700 text-slate-300 text-[10px] flex items-center justify-center font-mono">2</span>
                        <span class="truncate">2. Pelanggan</span>
                    </div>
                    <div id="step-pill-3" class="wizard-step-pill py-1.5 px-2 rounded-xl transition font-semibold flex items-center justify-center gap-1.5 bg-slate-800 text-slate-400">
                        <span class="w-4 h-4 rounded-full bg-slate-700 text-slate-300 text-[10px] flex items-center justify-center font-mono">3</span>
                        <span class="truncate">3. Bayar & Preview</span>
                    </div>
                </div>
            </div>

            <form id="formCreateOrder" onsubmit="submitCreateOrder(event)" class="bg-slate-50">
                <input type="hidden" name="customer_price" id="create_customer_price" value="0">

                <div class="p-5">
                    
                    <!-- ============================================== -->
                    <!-- STEP 1: PRODUK, QTY, & HARGA SATUAN            -->
                    <!-- ============================================== -->
                    <div id="wizard-step-1" class="wizard-step-content space-y-4">
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                Nama Pekerjaan / Produk <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="job_title" id="create_job_title" required 
                                   class="form-control text-xs font-semibold py-2.5 rounded-xl" 
                                   placeholder="Misal: Cetak Buku Agenda Kulit Foil, Banner Outdoor 3x1m, Box Makanan">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                    Jumlah / Qty <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" name="qty" id="create_qty" value="1" min="1" required 
                                       oninput="calcCustomerPriceLive()"
                                       class="form-control text-xs font-mono font-bold text-center py-2 rounded-xl">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Satuan</label>
                                <input type="text" name="unit" id="create_unit" value="pcs" 
                                       oninput="calcCustomerPriceLive()"
                                       class="form-control text-xs text-center py-2 rounded-xl" placeholder="pcs, rim, buku, box">
                            </div>
                        </div>

                        <!-- HARGA SATUAN (RP / UNIT) -->
                        <div class="bg-white p-3.5 rounded-2xl border border-blue-200 shadow-sm space-y-2">
                            <label class="block text-xs font-bold text-blue-900 uppercase">
                                <i class="fa-solid fa-tag text-blue-600 me-1"></i> Harga Jual Satuan (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text font-bold text-sm bg-blue-50 text-blue-700 border-blue-200">Rp</span>
                                <input type="number" name="customer_unit_price" id="create_unit_price" min="0" step="500" required 
                                       oninput="calcCustomerPriceLive()" 
                                       class="form-control font-mono font-black text-blue-700 text-base border-blue-200" placeholder="0">
                                <span class="input-group-text text-xs text-slate-500 bg-slate-50 border-blue-200 font-mono" id="create_unit_label">/ pcs</span>
                            </div>

                            <!-- LIVE CALCULATION PREVIEW BOX -->
                            <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-2.5 flex items-center justify-between text-xs">
                                <div class="text-slate-600">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Kalkulasi:</span>
                                    <span id="create_calc_preview_text" class="font-mono font-semibold text-slate-700">1 pcs x Rp 0</span>
                                </div>
                                <div class="text-end">
                                    <span class="text-[10px] uppercase font-bold text-blue-600 block">Total Jual Customer</span>
                                    <strong id="create_total_display" class="font-mono text-blue-800 text-sm">Rp 0</strong>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Catatan & Spesifikasi Cetak</label>
                            <textarea name="description" id="create_description" rows="2" 
                                      class="form-control text-xs rounded-xl" 
                                      placeholder="Ukuran kertas, laminasi doff/glossy, warna cover, nomor seri..."></textarea>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- STEP 2: DATA PELANGGAN (CUSTOMER)              -->
                    <!-- ============================================== -->
                    <div id="wizard-step-2" class="wizard-step-content hidden space-y-4">
                        
                        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                            <div class="flex items-center justify-between pb-2 border-b">
                                <span class="text-xs font-bold uppercase text-slate-700">Data Pelanggan</span>
                                <span class="text-[10.5px] text-slate-400">Pilih atau ketik baru</span>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Cari dari Data Pelanggan Terdaftar</label>
                                <select id="create_customer_select" onchange="fillExistingCustomer(this.value)" class="form-select form-select-sm text-xs rounded-xl">
                                    <option value="">-- Ketik Pelanggan Baru / Pilih dari List --</option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}" data-name="{{ $c->name }}" data-phone="{{ $c->phone }}">{{ $c->name }} ({{ $c->phone ?? 'No HP -' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3 pt-1">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                        Nama Pelanggan <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" name="customer_name" id="create_customer_name" required 
                                           class="form-control text-xs py-2 rounded-xl" placeholder="Nama pelanggan">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">No. WhatsApp / HP</label>
                                    <input type="text" name="customer_phone" id="create_customer_phone" 
                                           class="form-control text-xs font-mono py-2 rounded-xl" placeholder="08xxxxxxxx">
                                </div>
                            </div>
                        </div>

                        <!-- Ringkasan Singkat Pesanan yang sedang dibuat -->
                        <div class="bg-slate-100 p-3 rounded-2xl border border-slate-200 text-xs flex justify-between items-center">
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase font-bold block">Pekerjaan:</span>
                                <strong id="step2_job_summary" class="text-slate-800">-</strong>
                            </div>
                            <div class="text-end">
                                <span class="text-[10px] text-slate-500 uppercase font-bold block">Total Harga:</span>
                                <strong id="step2_total_summary" class="text-blue-700 font-mono text-sm">Rp 0</strong>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- STEP 3: PEMBAYARAN & PREVIEW LEMBAR KERJA      -->
                    <!-- ============================================== -->
                    <div id="wizard-step-3" class="wizard-step-content hidden space-y-4">
                        
                        <!-- Pilihan Metode & Status Pembayaran -->
                        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                            <span class="text-xs font-bold uppercase text-slate-700 block pb-1 border-b">
                                <i class="fa-solid fa-wallet text-emerald-600 me-1"></i> Pembayaran Customer
                            </span>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Metode Bayar</label>
                                    <select name="payment_method" id="create_payment_method" onchange="updateLiveSummaryCard()" class="form-select form-select-sm text-xs font-bold rounded-xl">
                                        <option value="Cash">Tunai (Cash)</option>
                                        <option value="Transfer">Transfer Bank</option>
                                        <option value="QRIS">QRIS</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Status Bayar</label>
                                    <select name="is_dp" id="create_is_dp" onchange="toggleDpAmount(this.value); updateLiveSummaryCard();" class="form-select form-select-sm text-xs font-bold rounded-xl">
                                        <option value="0">Lunas (100%)</option>
                                        <option value="1">Uang Muka (DP)</option>
                                    </select>
                                </div>
                            </div>

                            <div id="dp_amount_container" class="hidden pt-1">
                                <label class="block text-xs font-bold text-amber-800 uppercase mb-1">Nominal DP Dibayar (Rp)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text font-bold text-xs bg-amber-50 text-amber-700">Rp</span>
                                    <input type="number" name="paid_amount" id="create_paid_amount" min="0" step="1000" 
                                           oninput="updateLiveSummaryCard()" 
                                           class="form-control font-mono font-bold text-amber-800 text-xs rounded-r-xl" placeholder="0">
                                </div>
                            </div>
                        </div>

                        <!-- LIVE PREVIEW LEMBAR KERJA / STRUK -->
                        <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white p-4 rounded-2xl shadow-lg border border-slate-700 text-xs space-y-2.5">
                            <div class="flex items-center justify-between pb-2 border-b border-white/10">
                                <span class="text-slate-300 font-bold uppercase text-[10.5px] flex items-center gap-1.5">
                                    <i class="fa-solid fa-receipt text-blue-400"></i> Preview Lembar Kerja
                                </span>
                                <span id="prev_pay_badge" class="badge bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono text-[10px]">Lunas (100%)</span>
                            </div>

                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Pekerjaan:</span>
                                    <strong id="prev_job_title" class="text-white text-end">-</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Pelanggan:</span>
                                    <span id="prev_customer_name" class="text-slate-200 text-end">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Rincian Satuan:</span>
                                    <span id="prev_unit_detail" class="font-mono text-blue-300 text-end">1 pcs @ Rp 0</span>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-white/10 space-y-1">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-300 font-bold uppercase">Total Tagihan:</span>
                                    <strong id="prev_total_price" class="font-mono text-emerald-400 text-base">Rp 0</strong>
                                </div>
                                <div class="flex justify-between items-center text-slate-300">
                                    <span>Dibayar:</span>
                                    <span id="prev_paid_amount" class="font-mono font-bold">Rp 0</span>
                                </div>
                                <div id="prev_remaining_row" class="hidden flex justify-between items-center text-amber-300 font-bold">
                                    <span>Sisa Piutang:</span>
                                    <span id="prev_remaining_amount" class="font-mono">Rp 0</span>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- Modal Footer with Wizard Buttons -->
                <div class="px-5 py-3.5 bg-white border-t border-slate-200 flex justify-between items-center">
                    <button type="button" id="btnWizardPrev" onclick="navigateWizard(-1)" class="btn btn-sm btn-outline-secondary rounded-xl font-bold px-3">
                        <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                    </button>
                    
                    <div class="flex items-center gap-2">
                        <button type="button" class="btn btn-sm btn-light rounded-xl text-slate-500 text-xs" data-bs-dismiss="modal">Batal</button>
                        
                        <button type="button" id="btnWizardNext" onclick="navigateWizard(1)" class="btn btn-sm btn-primary rounded-xl font-bold shadow-sm px-4">
                            <span>Lanjut</span> <i class="fa-solid fa-arrow-right ms-1"></i>
                        </button>
                        
                        <button type="submit" id="btnSubmitCreateOrder" class="hidden btn btn-sm btn-success rounded-xl font-bold shadow-sm px-4">
                            <i class="fa-solid fa-check-double me-1"></i> Simpan & Cetak Struk
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: INPUT HPP VENDOR DENGAN HARGA SATUAN (STAGE 2)                  -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalSubmitHpp" tabindex="-1" aria-labelledby="modalSubmitHppLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden" style="border-radius: 1.25rem;">
            <div class="px-4 py-3 bg-gradient-to-r from-amber-600 to-amber-700 text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-white/20 text-white flex items-center justify-center">
                        <i class="fa-solid fa-hand-holding-dollar text-sm"></i>
                    </div>
                    <div>
                        <h6 class="text-sm font-bold mb-0 text-white" id="modalSubmitHppLabel">Input HPP Vendor & Ajukan ACC</h6>
                        <span class="text-[11px] text-amber-100" id="hpp_order_number_badge">Order #OUT-0000</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formSubmitHpp" onsubmit="submitHppToOwner(event)" class="p-4 space-y-3 bg-slate-50">
                <input type="hidden" id="hpp_order_id">
                <input type="hidden" id="hpp_order_qty" value="1">
                <input type="hidden" id="hpp_order_unit" value="pcs">
                <input type="hidden" id="hpp_vendor_cost" value="0">

                <!-- Job & Customer Summary -->
                <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm text-xs">
                    <div class="flex justify-between items-start mb-1">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Pekerjaan:</span>
                            <strong id="hpp_job_title_display" class="text-slate-900 text-sm block">-</strong>
                        </div>
                        <div class="text-end">
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Harga Customer:</span>
                            <span id="hpp_customer_price_display" class="badge bg-emerald-100 text-emerald-800 text-xs font-mono font-bold px-2 py-1">Rp 0</span>
                        </div>
                    </div>
                    <span id="hpp_customer_name_display" class="text-[11px] text-slate-500 block">Customer: -</span>
                </div>

                <!-- Vendor & Cost Inputs -->
                <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm space-y-2.5">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Vendor / Rekanan <span class="text-rose-500">*</span></label>
                            <input type="text" id="hpp_vendor_name" required class="form-control text-xs font-semibold rounded-xl" placeholder="Misal: Percetakan Prima Offset">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">No. Telp / WA Vendor</label>
                            <input type="text" id="hpp_vendor_phone" class="form-control text-xs font-mono rounded-xl" placeholder="08xxxxxxxx">
                        </div>
                    </div>

                    <!-- HARGA MODAL SATUAN VENDOR -->
                    <div class="bg-rose-50/50 p-3 rounded-xl border border-rose-100 space-y-2">
                        <label class="block text-xs font-bold text-rose-900 uppercase">
                            <i class="fa-solid fa-receipt text-rose-600 me-1"></i> Harga Modal Satuan Vendor (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text font-bold text-xs bg-rose-50 text-rose-700 border-rose-200">Rp</span>
                            <input type="number" id="hpp_vendor_unit_price" min="0" step="500" required 
                                   oninput="calcVendorHppLive()" 
                                   class="form-control font-mono font-bold text-rose-700 text-xs border-rose-200" placeholder="0">
                            <span class="input-group-text text-xs text-slate-500 bg-white border-rose-200 font-mono" id="hpp_unit_label">/ pcs</span>
                        </div>
                        
                        <div class="flex justify-between items-center text-[11px] text-slate-600 pt-1">
                            <span>Subtotal Modal Vendor (<span id="hpp_qty_multiply_text">1 pcs x Rp 0</span>):</span>
                            <strong id="hpp_subtotal_vendor_display" class="font-mono text-rose-700">Rp 0</strong>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Ongkir / Pengiriman (Opsional)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text font-bold text-xs bg-slate-100 text-slate-700">Rp</span>
                            <input type="number" id="hpp_shipping_cost" min="0" step="1000" oninput="calcVendorHppLive()" class="form-control font-mono text-slate-800 text-xs rounded-r-xl" placeholder="0">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Catatan Tambahan untuk Vendor</label>
                        <textarea id="hpp_vendor_notes" rows="2" class="form-control text-xs rounded-xl" placeholder="Spesifikasi vendor, janji selesai, warna cover..."></textarea>
                    </div>
                </div>

                <!-- Live Margin Simulation Card -->
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white p-3.5 rounded-2xl shadow-md border border-slate-700">
                    <div class="d-flex justify-content-between align-items-center pb-2 border-b border-white/10 text-xs">
                        <span class="text-slate-300 font-semibold">Simulasi Margin & Laba:</span>
                        <span id="hpp_margin_badge" class="badge bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-mono font-bold">Margin 0%</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-2 text-center text-xs">
                        <div class="bg-white/5 p-2 rounded-xl border border-white/10">
                            <span class="text-[9px] text-slate-400 block uppercase">Harga Jual</span>
                            <span id="hpp_live_omset" class="font-mono font-bold text-blue-300 block mt-0.5">Rp 0</span>
                        </div>
                        <div class="bg-white/5 p-2 rounded-xl border border-white/10">
                            <span class="text-[9px] text-slate-400 block uppercase">Total Modal</span>
                            <span id="hpp_live_cost" class="font-mono font-bold text-rose-300 block mt-0.5">Rp 0</span>
                        </div>
                        <div class="bg-emerald-500/10 p-2 rounded-xl border border-emerald-500/30">
                            <span class="text-[9px] text-emerald-300 block uppercase font-bold">Laba Bersih</span>
                            <span id="hpp_live_profit" class="font-mono font-black text-emerald-400 block mt-0.5">Rp 0</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl font-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitHpp" class="btn btn-sm btn-warning rounded-xl font-bold text-slate-900 shadow-sm px-4">
                        <i class="fa-solid fa-paper-plane me-1"></i> Ajukan Approval ke Owner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 3: APPROVAL / ACC OWNER (STAGE 3)                                  -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalApproval" tabindex="-1" aria-labelledby="modalApprovalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden" style="border-radius: 1.25rem;">
            <div class="px-4 py-3 bg-gradient-to-r from-indigo-900 to-slate-900 text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 flex items-center justify-center">
                        <i class="fa-solid fa-stamp text-sm"></i>
                    </div>
                    <div>
                        <h6 class="text-sm font-bold mb-0 text-white" id="modalApprovalLabel">Approval Pengajuan Cetak Luar</h6>
                        <span class="text-[11px] text-slate-300" id="appr_order_number">Order #OUT-0000</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="p-4 space-y-3.5 bg-slate-50">
                <input type="hidden" id="appr_order_id">

                <!-- Order & Cost Details -->
                <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm space-y-2 text-xs">
                    <div class="flex justify-between items-start pb-2 border-b">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Nama Pekerjaan:</span>
                            <strong id="appr_job_title" class="text-slate-900 text-sm block">-</strong>
                            <span id="appr_customer_info" class="text-[11px] text-slate-500 mt-0.5 block">Customer: -</span>
                        </div>
                        <span id="appr_branch_badge" class="badge bg-slate-100 text-slate-700 border text-[10px]">Cabang</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase block font-semibold">Vendor Rekanan:</span>
                            <strong id="appr_vendor_name" class="text-slate-900 text-xs block">-</strong>
                        </div>
                        <div class="text-end">
                            <span class="text-[10px] text-slate-400 uppercase block font-semibold">Harga Jual Customer:</span>
                            <strong id="appr_customer_price" class="text-emerald-700 text-xs font-mono block">Rp 0</strong>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1 border-t">
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase block font-semibold">Total Modal (HPP + Ongkir):</span>
                            <strong id="appr_total_cost" class="text-rose-700 text-xs font-mono block">Rp 0</strong>
                        </div>
                        <div class="text-end">
                            <span class="text-[10px] text-slate-400 uppercase block font-semibold">Estimasi Margin Laba:</span>
                            <strong id="appr_margin" class="text-emerald-700 text-xs font-mono block">Rp 0 (0%)</strong>
                        </div>
                    </div>

                    <div id="appr_notes_container" class="bg-slate-50 p-2 rounded-xl text-[11px] text-slate-600 border mt-1">
                        <strong>Catatan:</strong> <span id="appr_notes">-</span>
                    </div>
                </div>

                <!-- Form Tolak (Hidden by default) -->
                <div id="rejection_container" class="hidden bg-rose-50 border border-rose-200 p-3 rounded-2xl space-y-1.5">
                    <label class="block text-xs font-bold text-rose-900 uppercase">Alasan Penolakan <span class="text-rose-600">*</span></label>
                    <input type="text" id="appr_rejection_reason" class="form-control form-control-sm text-xs rounded-xl" placeholder="Misal: Modal vendor terlalu mahal, cari vendor lain...">
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between align-items-center pt-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl font-bold text-xs" data-bs-dismiss="modal">Tutup</button>
                    
                    <div class="d-flex gap-2">
                        <button type="button" id="btnShowReject" onclick="toggleRejectBox()" class="btn btn-sm btn-outline-danger rounded-xl font-bold text-xs">
                            <i class="fa-solid fa-xmark me-1"></i> Tolak
                        </button>
                        <button type="button" id="btnExecuteReject" onclick="submitRejectOrder()" class="hidden btn btn-sm btn-danger rounded-xl font-bold text-xs">
                            <i class="fa-solid fa-ban me-1"></i> Konfirmasi Tolak
                        </button>
                        <button type="button" onclick="submitApproveOrder()" class="btn btn-sm btn-success rounded-xl font-bold text-xs px-4 shadow-sm">
                            <i class="fa-solid fa-check-double me-1"></i> ACC / Setujui Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 4: BARANG SAMPAI & QUALITY CONTROL (STAGE 4)                        -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalQc" tabindex="-1" aria-labelledby="modalQcLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden" style="border-radius: 1.25rem;">
            <div class="px-4 py-3 bg-gradient-to-r from-purple-800 to-indigo-900 text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-white/20 text-white flex items-center justify-center">
                        <i class="fa-solid fa-box-open text-sm"></i>
                    </div>
                    <div>
                        <h6 class="text-sm font-bold mb-0 text-white" id="modalQcLabel">Konfirmasi Barang Sampai & QC</h6>
                        <span class="text-[11px] text-purple-200" id="qc_order_number">Order #OUT-0000</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="p-4 space-y-3 bg-slate-50">
                <input type="hidden" id="qc_order_id">

                <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm text-xs">
                    <span class="text-[10px] text-slate-400 font-bold uppercase block">Pekerjaan:</span>
                    <strong id="qc_job_title" class="text-slate-900 text-sm block">-</strong>
                    <span id="qc_vendor_display" class="text-[11px] text-indigo-700 font-semibold block mt-0.5">Vendor: -</span>
                </div>

                <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm space-y-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase">Catatan Hasil Pengecekan Kualitas (QC)</label>
                    <textarea id="qc_notes" rows="3" class="form-control text-xs rounded-xl" placeholder="Hasil cetak rapi, warna tajam, jumlah pas..."></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl font-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="button" onclick="submitPassQc()" class="btn btn-sm btn-purple rounded-xl font-bold text-white shadow-sm px-4 bg-purple-700 hover:bg-purple-800">
                        <i class="fa-solid fa-clipboard-check me-1"></i> Lolos QC & Siap Diambil
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentWizardStep = 1;
let currentSelectedCustomerPrice = 0;

// Open Wizard Modal
function openCreateOrderWizard() {
    currentWizardStep = 1;
    updateWizardUI();
    document.getElementById('formCreateOrder').reset();
    document.getElementById('create_qty').value = '1';
    document.getElementById('create_unit').value = 'pcs';
    document.getElementById('create_unit_price').value = '';
    document.getElementById('create_customer_price').value = '0';
    document.getElementById('create_is_dp').value = '0';
    toggleDpAmount('0');
    calcCustomerPriceLive();

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCreateOrder'));
    modal.show();
}

// Wizard Navigation (Next / Prev)
function navigateWizard(direction) {
    if (direction === 1) {
        // Validate Step 1
        if (currentWizardStep === 1) {
            const jobTitle = document.getElementById('create_job_title').value.trim();
            const qty = parseInt(document.getElementById('create_qty').value) || 0;
            const unitPrice = parseFloat(document.getElementById('create_unit_price').value) || 0;

            if (!jobTitle) {
                Swal.fire({ icon: 'warning', title: 'Nama Pekerjaan Wajib Diisi', text: 'Silakan isi nama pekerjaan atau produk cetak.' });
                return;
            }
            if (qty <= 0) {
                Swal.fire({ icon: 'warning', title: 'Qty Tidak Valid', text: 'Jumlah / Qty minimal 1.' });
                return;
            }
            if (unitPrice <= 0) {
                Swal.fire({ icon: 'warning', title: 'Harga Satuan Wajib Diisi', text: 'Silakan isi harga jual per satuan.' });
                return;
            }
        }
        // Validate Step 2
        else if (currentWizardStep === 2) {
            const customerName = document.getElementById('create_customer_name').value.trim();
            if (!customerName) {
                Swal.fire({ icon: 'warning', title: 'Nama Pelanggan Wajib Diisi', text: 'Silakan isi nama pelanggan.' });
                return;
            }
        }

        if (currentWizardStep < 3) {
            currentWizardStep++;
            updateWizardUI();
        }
    } else if (direction === -1) {
        if (currentWizardStep > 1) {
            currentWizardStep--;
            updateWizardUI();
        }
    }
}

function updateWizardUI() {
    // Show/Hide Step Content
    document.querySelectorAll('.wizard-step-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(`wizard-step-${currentWizardStep}`).classList.remove('hidden');

    // Update Stepper Pills
    for (let i = 1; i <= 3; i++) {
        const pill = document.getElementById(`step-pill-${i}`);
        if (i === currentWizardStep) {
            pill.className = 'wizard-step-pill active py-1.5 px-2 rounded-xl transition font-bold flex items-center justify-center gap-1.5 bg-blue-600 text-white shadow-sm';
            pill.querySelector('span:first-child').className = 'w-4 h-4 rounded-full bg-white text-blue-600 text-[10px] flex items-center justify-center font-mono font-bold';
        } else if (i < currentWizardStep) {
            pill.className = 'wizard-step-pill py-1.5 px-2 rounded-xl transition font-semibold flex items-center justify-center gap-1.5 bg-emerald-800 text-emerald-100';
            pill.querySelector('span:first-child').className = 'w-4 h-4 rounded-full bg-emerald-500 text-white text-[10px] flex items-center justify-center font-mono';
        } else {
            pill.className = 'wizard-step-pill py-1.5 px-2 rounded-xl transition font-semibold flex items-center justify-center gap-1.5 bg-slate-800 text-slate-400';
            pill.querySelector('span:first-child').className = 'w-4 h-4 rounded-full bg-slate-700 text-slate-300 text-[10px] flex items-center justify-center font-mono';
        }
    }

    // Button states
    const btnPrev = document.getElementById('btnWizardPrev');
    const btnNext = document.getElementById('btnWizardNext');
    const btnSubmit = document.getElementById('btnSubmitCreateOrder');

    btnPrev.style.visibility = (currentWizardStep === 1) ? 'hidden' : 'visible';

    if (currentWizardStep === 3) {
        btnNext.classList.add('hidden');
        btnSubmit.classList.remove('hidden');
        updateLiveSummaryCard();
    } else {
        btnNext.classList.remove('hidden');
        btnSubmit.classList.add('hidden');
    }

    if (currentWizardStep === 2) {
        const job = document.getElementById('create_job_title').value || '-';
        const qty = document.getElementById('create_qty').value || '1';
        const unit = document.getElementById('create_unit').value || 'pcs';
        const unitPrice = parseFloat(document.getElementById('create_unit_price').value) || 0;
        const total = qty * unitPrice;

        document.getElementById('step2_job_summary').innerText = `${job} (${qty} ${unit})`;
        document.getElementById('step2_total_summary').innerText = `Rp ${Number(total).toLocaleString('id-ID')}`;
    }
}

// Live calculation for Step 1
function calcCustomerPriceLive() {
    const qty = parseInt(document.getElementById('create_qty').value) || 0;
    const unit = document.getElementById('create_unit').value || 'pcs';
    const unitPrice = parseFloat(document.getElementById('create_unit_price').value) || 0;
    const total = qty * unitPrice;

    document.getElementById('create_customer_price').value = total;
    document.getElementById('create_unit_label').innerText = `/ ${unit}`;
    document.getElementById('create_calc_preview_text').innerText = `${qty} ${unit} x Rp ${Number(unitPrice).toLocaleString('id-ID')}`;
    document.getElementById('create_total_display').innerText = `Rp ${Number(total).toLocaleString('id-ID')}`;
}

// Existing customer select helper
function fillExistingCustomer(customerId) {
    const select = document.getElementById('create_customer_select');
    const opt = select.options[select.selectedIndex];
    if (customerId && opt) {
        document.getElementById('create_customer_name').value = opt.getAttribute('data-name') || '';
        document.getElementById('create_customer_phone').value = opt.getAttribute('data-phone') || '';
    }
}

// Toggle DP
function toggleDpAmount(isDp) {
    const container = document.getElementById('dp_amount_container');
    if (isDp == '1') {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
}

// Live Summary Card for Step 3
function updateLiveSummaryCard() {
    const job = document.getElementById('create_job_title').value || '-';
    const cust = document.getElementById('create_customer_name').value || '-';
    const phone = document.getElementById('create_customer_phone').value || '';
    const qty = parseInt(document.getElementById('create_qty').value) || 0;
    const unit = document.getElementById('create_unit').value || 'pcs';
    const unitPrice = parseFloat(document.getElementById('create_unit_price').value) || 0;
    const total = qty * unitPrice;

    const isDp = document.getElementById('create_is_dp').value == '1';
    const paid = isDp ? (parseFloat(document.getElementById('create_paid_amount').value) || 0) : total;
    const remaining = Math.max(0, total - paid);

    document.getElementById('prev_job_title').innerText = job;
    document.getElementById('prev_customer_name').innerText = cust + (phone ? ` (${phone})` : '');
    document.getElementById('prev_unit_detail').innerText = `${qty} ${unit} @ Rp ${Number(unitPrice).toLocaleString('id-ID')}`;
    document.getElementById('prev_total_price').innerText = `Rp ${Number(total).toLocaleString('id-ID')}`;
    document.getElementById('prev_paid_amount').innerText = `Rp ${Number(paid).toLocaleString('id-ID')}`;

    const badge = document.getElementById('prev_pay_badge');
    const remRow = document.getElementById('prev_remaining_row');

    if (isDp) {
        badge.className = 'badge bg-amber-500/20 text-amber-300 border border-amber-500/30 font-mono text-[10px]';
        badge.innerText = `Uang Muka (DP)`;
        remRow.classList.remove('hidden');
        document.getElementById('prev_remaining_amount').innerText = `Rp ${Number(remaining).toLocaleString('id-ID')}`;
    } else {
        badge.className = 'badge bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono text-[10px]';
        badge.innerText = `Lunas (100%)`;
        remRow.classList.add('hidden');
    }
}

// Submit Order (Stage 1)
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
        btn.innerHTML = `<i class="fa-solid fa-check-double me-1"></i> Simpan & Cetak Struk`;
        if (res.status === 'success' || res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Pesanan Berhasil Dibuat!',
                text: res.message,
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-print me-1"></i> Cetak Struk Customer',
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
        btn.innerHTML = `<i class="fa-solid fa-check-double me-1"></i> Simpan & Cetak Struk`;
        Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: err.message });
    });
}

// Modal 2: Input HPP with Unit Price
function openSubmitHppModal(order) {
    document.getElementById('hpp_order_id').value = order.id;
    document.getElementById('hpp_order_qty').value = order.qty || 1;
    document.getElementById('hpp_order_unit').value = order.unit || 'pcs';
    document.getElementById('hpp_unit_label').innerText = `/ ${order.unit || 'pcs'}`;
    
    document.getElementById('hpp_order_number_badge').innerText = `#${order.order_number}`;
    document.getElementById('hpp_job_title_display').innerText = `${order.job_title} (${order.qty} ${order.unit})`;
    document.getElementById('hpp_customer_price_display').innerText = `Rp ${Number(order.customer_price).toLocaleString('id-ID')}`;
    document.getElementById('hpp_customer_name_display').innerText = `Customer: ${order.customer_name} ${order.customer_phone ? '(' + order.customer_phone + ')' : ''}`;

    currentSelectedCustomerPrice = parseFloat(order.customer_price) || 0;

    document.getElementById('hpp_vendor_name').value = order.vendor_name || '';
    document.getElementById('hpp_vendor_phone').value = order.vendor_phone || '';
    
    const existingUnitPrice = order.vendor_unit_price > 0 ? order.vendor_unit_price : (order.vendor_cost > 0 && order.qty > 0 ? (order.vendor_cost / order.qty) : '');
    document.getElementById('hpp_vendor_unit_price').value = existingUnitPrice;
    document.getElementById('hpp_shipping_cost').value = (order.shipping_cost > 0) ? order.shipping_cost : '';
    document.getElementById('hpp_vendor_notes').value = order.vendor_notes || '';

    calcVendorHppLive();

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSubmitHpp'));
    modal.show();
}

function calcVendorHppLive() {
    const qty = parseInt(document.getElementById('hpp_order_qty').value) || 1;
    const unit = document.getElementById('hpp_order_unit').value || 'pcs';
    const unitCost = parseFloat(document.getElementById('hpp_vendor_unit_price')?.value) || 0;
    const subtotalCost = qty * unitCost;
    const shipping = parseFloat(document.getElementById('hpp_shipping_cost')?.value) || 0;
    const totalCost = subtotalCost + shipping;
    
    document.getElementById('hpp_vendor_cost').value = subtotalCost;
    document.getElementById('hpp_qty_multiply_text').innerText = `${qty} ${unit} x Rp ${Number(unitCost).toLocaleString('id-ID')}`;
    document.getElementById('hpp_subtotal_vendor_display').innerText = `Rp ${Number(subtotalCost).toLocaleString('id-ID')}`;

    const omset = currentSelectedCustomerPrice;
    const profit = omset - totalCost;
    const marginPct = omset > 0 ? ((profit / omset) * 100).toFixed(1) : 0;

    document.getElementById('hpp_live_omset').innerText = `Rp ${Number(omset).toLocaleString('id-ID')}`;
    document.getElementById('hpp_live_cost').innerText = `Rp ${Number(totalCost).toLocaleString('id-ID')}`;
    document.getElementById('hpp_live_profit').innerText = `Rp ${Number(profit).toLocaleString('id-ID')}`;

    const badge = document.getElementById('hpp_margin_badge');
    if (badge) {
        if (profit < 0) {
            badge.className = 'badge bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[10px] font-mono font-bold';
            badge.innerText = `Rugi ${marginPct}%`;
        } else {
            badge.className = 'badge bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-mono font-bold';
            badge.innerText = `Margin ${marginPct}%`;
        }
    }
}

function submitHppToOwner(e) {
    e.preventDefault();
    const orderId = document.getElementById('hpp_order_id').value;
    const btn = document.getElementById('btnSubmitHpp');
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Mengirim...`;

    fetch(`/cetak-luar/${orderId}/submit-vendor`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            vendor_name: document.getElementById('hpp_vendor_name').value,
            vendor_phone: document.getElementById('hpp_vendor_phone').value,
            vendor_unit_price: document.getElementById('hpp_vendor_unit_price').value,
            vendor_cost: document.getElementById('hpp_vendor_cost').value,
            shipping_cost: document.getElementById('hpp_shipping_cost').value,
            vendor_notes: document.getElementById('hpp_vendor_notes').value
        })
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = `<i class="fa-solid fa-paper-plane me-1"></i> Ajukan Approval ke Owner`;
        if (res.status === 'success' || res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Diajukan!',
                text: res.message
            }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = `<i class="fa-solid fa-paper-plane me-1"></i> Ajukan Approval ke Owner`;
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    });
}

// Modal 3: Approval Owner
function openApprovalModal(order) {
    document.getElementById('appr_order_id').value = order.id;
    document.getElementById('appr_order_number').innerText = `#${order.order_number}`;
    document.getElementById('appr_job_title').innerText = `${order.job_title} (${order.qty} ${order.unit})`;
    document.getElementById('appr_customer_info').innerText = `Customer: ${order.customer_name} ${order.customer_phone ? '(' + order.customer_phone + ')' : ''}`;
    document.getElementById('appr_branch_badge').innerText = order.branch ? order.branch.nama_cabang : 'Pusat';
    document.getElementById('appr_vendor_name').innerText = `${order.vendor_name || '-'} ${order.vendor_phone ? '(' + order.vendor_phone + ')' : ''}`;
    document.getElementById('appr_customer_price').innerText = `Rp ${Number(order.customer_price).toLocaleString('id-ID')}`;
    document.getElementById('appr_total_cost').innerText = `Rp ${Number(order.total_cost).toLocaleString('id-ID')}`;
    document.getElementById('appr_margin').innerText = `Rp ${Number(order.estimated_margin).toLocaleString('id-ID')} (${order.margin_percent}%)`;
    document.getElementById('appr_notes').innerText = order.vendor_notes || '-';

    document.getElementById('rejection_container').classList.add('hidden');
    document.getElementById('btnShowReject').classList.remove('hidden');
    document.getElementById('btnExecuteReject').classList.add('hidden');

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalApproval'));
    modal.show();
}

function toggleRejectBox() {
    document.getElementById('rejection_container').classList.remove('hidden');
    document.getElementById('btnShowReject').classList.add('hidden');
    document.getElementById('btnExecuteReject').classList.remove('hidden');
    document.getElementById('appr_rejection_reason').focus();
}

function submitApproveOrder() {
    const orderId = document.getElementById('appr_order_id').value;
    Swal.fire({
        title: 'Konfirmasi ACC Pesanan?',
        text: 'Pesanan akan beralih ke tahap "Sedang Dikerjakan" di vendor.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, ACC Sekarang',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#059669'
    }).then(r => {
        if (r.isConfirmed) {
            fetch(`/cetak-luar/${orderId}/approve`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    Swal.fire({ icon: 'success', title: 'Order Telah di-ACC!', text: data.message })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
                }
            });
        }
    });
}

function submitRejectOrder() {
    const orderId = document.getElementById('appr_order_id').value;
    const reason = (document.getElementById('appr_rejection_reason').value || '').trim();
    if (!reason) {
        Swal.fire({ icon: 'warning', title: 'Alasan Wajib Diisi', text: 'Silakan isi alasan penolakan pengajuan.' });
        return;
    }

    fetch(`/cetak-luar/${orderId}/reject`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ rejection_reason: reason })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            Swal.fire({ icon: 'info', title: 'Pengajuan Ditolak', text: data.message })
                .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
        }
    });
}

// Modal 4: QC
function openQcModal(order) {
    document.getElementById('qc_order_id').value = order.id;
    document.getElementById('qc_order_number').innerText = `#${order.order_number}`;
    document.getElementById('qc_job_title').innerText = `${order.job_title} (${order.qty} ${order.unit})`;
    document.getElementById('qc_vendor_display').innerText = `Vendor: ${order.vendor_name || 'Luar'}`;
    document.getElementById('qc_notes').value = 'Kualitas cetak sesuai pesanan.';

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalQc'));
    modal.show();
}

function submitPassQc() {
    const orderId = document.getElementById('qc_order_id').value;
    const notes = document.getElementById('qc_notes').value;

    fetch(`/cetak-luar/${orderId}/pass-qc`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ qc_notes: notes })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            Swal.fire({ icon: 'success', title: 'Lolos QC!', text: data.message })
                .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
        }
    });
}

// Stage 5: Closing
function confirmClosingOrder(order) {
    Swal.fire({
        title: 'Closing Pesanan Cetak Luar?',
        html: `
            <div class="text-left text-xs space-y-1.5 p-3 bg-slate-50 rounded-xl border">
                <div><strong>No. Order:</strong> #${order.order_number}</div>
                <div><strong>Pekerjaan:</strong> ${order.job_title}</div>
                <div><strong>Customer:</strong> ${order.customer_name}</div>
                <div><strong>Total Penjualan:</strong> Rp ${Number(order.customer_price).toLocaleString('id-ID')}</div>
                <div><strong>Total HPP Modal:</strong> Rp ${Number(order.total_cost).toLocaleString('id-ID')}</div>
                <div class="text-emerald-700 font-bold pt-1 border-t"><strong>Laba Bersih:</strong> Rp ${Number(order.estimated_margin).toLocaleString('id-ID')}</div>
            </div>
            <p class="text-xs text-slate-500 mt-2">Data ini otomatis dibukukan ke <strong>Laporan Penjualan Harian & Kas POS</strong>.</p>
        `,
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Ya, Closing & Bukukan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#059669'
    }).then(r => {
        if (r.isConfirmed) {
            fetch(`/cetak-luar/${order.id}/close`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pesanan Selesai!',
                        text: data.message,
                        showCancelButton: true,
                        confirmButtonText: 'Cetak Invoice POS',
                        cancelButtonText: 'Tutup'
                    }).then(resAction => {
                        if (resAction.isConfirmed && data.receipt_url) {
                            window.open(data.receipt_url, '_blank');
                        }
                        location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
                }
            });
        }
    });
}
</script>
@endsection
