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
                                <span class="text-[11px] text-slate-500 font-mono block">{{ $order->qty }} {{ $order->unit }}</span>
                                @if($order->vendor_name)
                                    <span class="text-[10px] text-indigo-700 font-semibold block mt-0.5">
                                        <i class="fa-solid fa-industry me-0.5"></i> {{ $order->vendor_name }}
                                    </span>
                                @endif
                            </td>

                            <!-- Customer Price & Payment -->
                            <td class="py-3 px-4 text-end font-mono">
                                <strong class="text-slate-900 block text-xs">Rp {{ number_format($order->customer_price, 0, ',', '.') }}</strong>
                                <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9.5px] mt-0.5">
                                    {{ $order->payment_method }} ({{ $order->payment_status }})
                                </span>
                            </td>

                            <!-- Vendor Cost & Shipping -->
                            <td class="py-3 px-4 text-end font-mono">
                                @if($order->total_cost > 0)
                                    <strong class="text-rose-700 block">Rp {{ number_format($order->total_cost, 0, ',', '.') }}</strong>
                                    <span class="text-[9.5px] text-slate-400 block">
                                        Modal: {{ number_format($order->vendor_cost, 0, ',', '.') }} 
                                        @if($order->shipping_cost > 0) + Ongkir: {{ number_format($order->shipping_cost, 0, ',', '.') }} @endif
                                    </span>
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
                                <span class="text-sm font-semibold block">Belum ada pesanan cetak di luar pada filter ini.</span>
                                <button type="button" onclick="openCreateOrderModal()" class="btn btn-sm btn-primary rounded-xl font-bold mt-2">
                                    <i class="fa-solid fa-plus me-1"></i> Buat Pesanan Customer Baru
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 1: BUAT PESANAN CUSTOMER BARU (STAGE 1)           -->
<!-- ======================================================== -->
<div class="modal fade" id="modalCreateOrder" tabindex="-1" aria-labelledby="modalCreateOrderLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden" style="border-radius: 1.25rem;">
            <div class="px-4 py-3 bg-slate-900 text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 border border-blue-400/30 flex items-center justify-center">
                        <i class="fa-solid fa-file-signature text-sm"></i>
                    </div>
                    <div>
                        <h6 class="text-sm font-bold mb-0 text-white" id="modalCreateOrderLabel">Pesanan Cetak di Luar Baru</h6>
                        <span class="text-[11px] text-slate-400">Tahap 1: Input pesanan & terima pembayaran customer</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formCreateOrder" onsubmit="submitCreateOrder(event)" class="p-4 space-y-3 bg-slate-50">
                <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm space-y-2.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Pekerjaan / Produk <span class="text-rose-500">*</span></label>
                        <input type="text" name="job_title" id="create_job_title" required class="form-control text-xs font-semibold" placeholder="Misal: Cetak Offset Brosur A4 3 Rim, Nota NCR 10 Buku">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Jumlah / Qty <span class="text-rose-500">*</span></label>
                            <input type="number" name="qty" id="create_qty" value="1" min="1" required class="form-control text-xs font-mono font-bold text-center">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Satuan</label>
                            <input type="text" name="unit" id="create_unit" value="pcs" class="form-control text-xs text-center" placeholder="pcs, rim, buku">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-emerald-800 uppercase mb-1">Total Harga Jual ke Customer (Rp) <span class="text-rose-500">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text font-bold text-sm bg-emerald-50 text-emerald-700">Rp</span>
                            <input type="number" name="customer_price" id="create_customer_price" min="0" step="1000" required class="form-control font-mono font-black text-emerald-700 text-base" placeholder="0">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Catatan / Spesifikasi Cetak</label>
                        <textarea name="description" id="create_description" rows="2" class="form-control text-xs" placeholder="Ukuran kertas, laminasi, warna, nomor seri, dll..."></textarea>
                    </div>
                </div>

                <!-- Customer & Payment Details -->
                <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm space-y-2.5">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Customer <span class="text-rose-500">*</span></label>
                            <input type="text" name="customer_name" id="create_customer_name" required class="form-control text-xs" placeholder="Nama pelanggan">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">No. WhatsApp / HP</label>
                            <input type="text" name="customer_phone" id="create_customer_phone" class="form-control text-xs font-mono" placeholder="08xxxxxxxx">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1 border-t">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Metode Bayar</label>
                            <select name="payment_method" id="create_payment_method" class="form-select form-select-sm text-xs font-bold">
                                <option value="Cash">Tunai (Cash)</option>
                                <option value="Transfer">Transfer Bank</option>
                                <option value="QRIS">QRIS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Status Bayar</label>
                            <select name="is_dp" id="create_is_dp" onchange="toggleDpAmount(this.value)" class="form-select form-select-sm text-xs font-bold">
                                <option value="0">Lunas (100%)</option>
                                <option value="1">Uang Muka (DP)</option>
                            </select>
                        </div>
                    </div>

                    <div id="dp_amount_container" class="hidden">
                        <label class="block text-xs font-bold text-amber-800 uppercase mb-1">Nominal DP Dibayar (Rp)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text font-bold text-xs bg-amber-50 text-amber-700">Rp</span>
                            <input type="number" name="paid_amount" id="create_paid_amount" min="0" step="1000" class="form-control font-mono font-bold text-amber-800 text-xs" placeholder="0">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl font-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitCreateOrder" class="btn btn-sm btn-primary rounded-xl font-bold shadow-sm px-4">
                        <i class="fa-solid fa-check me-1"></i> Simpan & Cetak Struk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 2: INPUT HPP VENDOR & PENGAJUAN KE OWNER (STAGE 2) -->
<!-- ======================================================== -->
<div class="modal fade" id="modalSubmitHpp" tabindex="-1" aria-labelledby="modalSubmitHppLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
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

                <!-- Job & Customer Summary -->
                <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm text-xs">
                    <div class="flex justify-between items-start mb-1">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Pekerjaan:</span>
                            <strong id="hpp_job_title_display" class="text-slate-900 text-sm block">-</strong>
                        </div>
                        <span id="hpp_customer_price_display" class="badge bg-emerald-100 text-emerald-800 text-xs font-mono font-bold px-2 py-1">Rp 0</span>
                    </div>
                    <span id="hpp_customer_name_display" class="text-[11px] text-slate-500 block">Customer: -</span>
                </div>

                <!-- Vendor & Cost Inputs -->
                <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm space-y-2.5">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Vendor / Rekanan <span class="text-rose-500">*</span></label>
                            <input type="text" id="hpp_vendor_name" required class="form-control text-xs font-semibold" placeholder="Misal: Percetakan Prima Offset">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">No. Telp / WA Vendor</label>
                            <input type="text" id="hpp_vendor_phone" class="form-control text-xs font-mono" placeholder="08xxxxxxxx">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-rose-800 uppercase mb-1">Harga Modal Vendor (HPP) <span class="text-rose-500">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text font-bold text-xs bg-rose-50 text-rose-700">Rp</span>
                                <input type="number" id="hpp_vendor_cost" min="0" step="1000" required oninput="calcHppMarginLive()" class="form-control font-mono font-bold text-rose-700 text-xs" placeholder="0">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Ongkir / Pengiriman</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text font-bold text-xs bg-slate-100 text-slate-700">Rp</span>
                                <input type="number" id="hpp_shipping_cost" min="0" step="1000" oninput="calcHppMarginLive()" class="form-control font-mono text-slate-800 text-xs" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Catatan Tambahan untuk Vendor</label>
                        <textarea id="hpp_vendor_notes" rows="2" class="form-control text-xs" placeholder="Spesifikasi vendor, janji selesai, dll..."></textarea>
                    </div>
                </div>

                <!-- Live Margin Simulation Card -->
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white p-3.5 rounded-2xl shadow-md border border-slate-700">
                    <div class="d-flex justify-content-between align-items-center pb-2 border-b border-white/10 text-xs">
                        <span class="text-slate-300 font-semibold">Simulasi Margin Laba:</span>
                        <span id="hpp_margin_badge" class="badge bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-mono font-bold">Margin 0%</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-2 text-center text-xs">
                        <div class="bg-white/5 p-2 rounded-xl border border-white/10">
                            <span class="text-[9px] text-slate-400 block uppercase">Harga Jual</span>
                            <span id="hpp_live_omset" class="font-mono font-bold text-blue-300 block mt-0.5">Rp 0</span>
                        </div>
                        <div class="bg-white/5 p-2 rounded-xl border border-white/10">
                            <span class="text-[9px] text-slate-400 block uppercase">Total Modal (HPP)</span>
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

<!-- ======================================================== -->
<!-- MODAL 3: APPROVAL / ACC OWNER (STAGE 3)                  -->
<!-- ======================================================== -->
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
                    <input type="text" id="appr_rejection_reason" class="form-control form-control-sm text-xs" placeholder="Misal: Modal vendor terlalu mahal, cari vendor lain...">
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

<!-- ======================================================== -->
<!-- MODAL 4: BARANG SAMPAI & QUALITY CONTROL (STAGE 4)       -->
<!-- ======================================================== -->
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

                <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm space-y-2 text-xs">
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase block">Pekerjaan / Produk:</span>
                        <strong id="qc_job_title" class="text-slate-900 text-sm block">-</strong>
                        <span id="qc_vendor_display" class="text-[11px] text-indigo-700 font-semibold block mt-0.5">Vendor: -</span>
                    </div>

                    <div class="pt-2 border-t">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Catatan Pemeriksaan QC</label>
                        <textarea id="qc_notes" rows="2" class="form-control text-xs" placeholder="Hasil cetakan tajam, finishing rapi, jumlah lengkap..."></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl font-bold text-xs" data-bs-dismiss="modal">Batal</button>
                    <button type="button" onclick="submitPassQc()" class="btn btn-sm btn-primary bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-bold text-xs px-4 shadow-sm">
                        <i class="fa-solid fa-clipboard-check me-1"></i> Konfirmasi Lolos QC (Siap Diambil)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentSelectedCustomerPrice = 0;

function openCreateOrderModal() {
    document.getElementById('formCreateOrder').reset();
    document.getElementById('dp_amount_container').classList.add('hidden');
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCreateOrder'));
    modal.show();
}

function toggleDpAmount(val) {
    const container = document.getElementById('dp_amount_container');
    if (val === '1') {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
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
        if (res.status === 'success' || res.success) {
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

// Modal 2: Input HPP
function openSubmitHppModal(order) {
    document.getElementById('hpp_order_id').value = order.id;
    document.getElementById('hpp_order_number_badge').innerText = `#${order.order_number}`;
    document.getElementById('hpp_job_title_display').innerText = `${order.job_title} (${order.qty} ${order.unit})`;
    document.getElementById('hpp_customer_price_display').innerText = `Rp ${Number(order.customer_price).toLocaleString('id-ID')}`;
    document.getElementById('hpp_customer_name_display').innerText = `Customer: ${order.customer_name} ${order.customer_phone ? '(' + order.customer_phone + ')' : ''}`;

    currentSelectedCustomerPrice = parseFloat(order.customer_price) || 0;

    document.getElementById('hpp_vendor_name').value = order.vendor_name || '';
    document.getElementById('hpp_vendor_phone').value = order.vendor_phone || '';
    document.getElementById('hpp_vendor_cost').value = (order.vendor_cost > 0) ? order.vendor_cost : '';
    document.getElementById('hpp_shipping_cost').value = (order.shipping_cost > 0) ? order.shipping_cost : '';
    document.getElementById('hpp_vendor_notes').value = order.vendor_notes || '';

    calcHppMarginLive();

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSubmitHpp'));
    modal.show();
}

function calcHppMarginLive() {
    const cost = parseFloat(document.getElementById('hpp_vendor_cost')?.value) || 0;
    const shipping = parseFloat(document.getElementById('hpp_shipping_cost')?.value) || 0;
    const totalCost = cost + shipping;
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
