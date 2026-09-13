@extends('layouts.app')

@section('title', 'Purchase Orders History & Logs')
@section('page-title', 'Purchase Orders (PO) & Logs')

@section('action-buttons')
<a href="{{ route('purchasing.create') }}" class="btn-odoo-primary text-decoration-none">
    <i class="fa-solid fa-plus"></i>
    <span>Buat PO Baru</span>
</a>
@endsection

@section('content')
<div x-data="{ 
    payOpen: false,
    payPurchase: null,
    payStep: 'step_1',
    payAmount: 0,
    payTotalCost: 0,
    payPaidAmount: 0,
    payRemainingAmount: 0,
    openPayModal(purchase) {
        this.payPurchase = purchase;
        this.payTotalCost = Number(purchase.total_cost || 0);
        this.payPaidAmount = Number(purchase.paid_amount || 0);
        this.payRemainingAmount = Math.max(0, this.payTotalCost - this.payPaidAmount);

        const paymentsCount = (purchase.payments || []).length;
        if (this.payPaidAmount <= 0) {
            this.payStep = 'step_1';
            this.payAmount = Math.round(this.payRemainingAmount * 0.2); // Default 20% DP
        } else if (paymentsCount === 1) {
            this.payStep = 'step_2';
            this.payAmount = this.payRemainingAmount;
        } else {
            this.payStep = 'pelunasan';
            this.payAmount = this.payRemainingAmount;
        }
        this.payOpen = true;
    },
    setPayStep(step) {
        this.payStep = step;
        if (step === 'pelunasan') {
            this.payAmount = this.payRemainingAmount;
        } else if (this.payAmount > this.payRemainingAmount || this.payAmount <= 0) {
            this.payAmount = Math.round(this.payRemainingAmount * (step === 'step_1' ? 0.2 : 0.5));
        }
    },
    setPercent(pct) {
        this.payAmount = Math.round(this.payTotalCost * (pct / 100));
        if (this.payAmount > this.payRemainingAmount) {
            this.payAmount = this.payRemainingAmount;
        }
    },
    setFullRemaining() {
        this.payStep = 'pelunasan';
        this.payAmount = this.payRemainingAmount;
    }
}" id="main-view-wrapper" data-view-wrapper>
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
                <div class="o_stat_value text-amber-600">{{ number_format($waitingApprovalCount) }}</div>
                <div class="o_stat_text">Waiting Manager ACC</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-truck-ramp-box text-blue-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-blue-600">{{ number_format($pendingCount) }}</div>
                <div class="o_stat_text">Waiting Warehouse Check</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-circle-check text-emerald-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-emerald-600">{{ number_format($receivedCount) }}</div>
                <div class="o_stat_text">Received / Completed</div>
            </div>
        </div>
    </div>

    <!-- Main Odoo Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden">
        <div class="table-view-container">
            <div class="table-responsive">
                <table class="table table-hover o_list_table mb-0" id="main-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="ps-3 text-center no-sort">
                                <input type="checkbox" class="form-check-input">
                            </th>
                            <th class="sortable">PO Reference</th>
                            <th class="sortable">Order Date</th>
                            <th class="sortable">Vendor / Supplier</th>
                            <th class="sortable">Product (Material)</th>
                            <th class="sortable text-center">Quantity</th>
                            <th class="sortable text-end">Total Amount</th>
                            <th class="sortable text-center">Alur Pengadaan</th>
                            <th class="sortable text-center">Status Tagihan</th>
                            <th class="text-center no-sort" style="width: 160px;">Aksi</th>
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
                                    <div class="text-[10px] text-slate-400">Oleh: {{ $purchase->user->username ?? 'Staf Purchasing' }}</div>
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
                                            <i class="fa-solid fa-clock me-1"></i> 1. Menunggu ACC
                                        </span>
                                    @elseif($purchase->status === 'approved' || $purchase->status === 'pending_verification')
                                        <span class="badge bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-semibold">
                                            <i class="fa-solid fa-truck me-1"></i> 2. PO Terbit (Cek Gudang)
                                        </span>
                                        @if($purchase->approvedBy)
                                            <div class="text-[10px] text-emerald-700 font-semibold mt-0.5">✓ Di-ACC: {{ $purchase->approvedBy->username }}</div>
                                        @endif
                                    @elseif($purchase->status === 'received')
                                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-semibold">
                                            <i class="fa-solid fa-circle-check me-1"></i> 3. Masuk Stok Gudang
                                        </span>
                                        @if($purchase->verifiedBy)
                                            <div class="text-[10px] text-slate-500 mt-0.5">Cek: {{ $purchase->verifiedBy->username }}</div>
                                        @endif
                                    @elseif($purchase->status === 'rejected')
                                        <span class="badge bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-semibold">
                                            <i class="fa-solid fa-ban me-1"></i> Ditolak / Retur
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($purchase->isPaid())
                                        <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 text-[11px] font-bold">
                                            <i class="fa-solid fa-check-double me-1"></i> LUNAS
                                        </span>
                                    @elseif($purchase->isPartiallyPaid())
                                        <div>
                                            <span class="badge bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-bold">
                                                <i class="fa-solid fa-hourglass-half me-1"></i> DIBAYAR SEBAGIAN (DP)
                                            </span>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                                                Rp {{ number_format($purchase->paid_amount, 0, ',', '.') }} / {{ number_format($purchase->total_cost, 0, ',', '.') }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-semibold">
                                            <i class="fa-solid fa-circle-exclamation me-1"></i> BELUM DIBAYAR
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex items-center justify-center gap-1">
                                        <!-- Manager Approval Button -->
                                        @if(($purchase->status === 'waiting_approval') && (auth()->user()->isOwner() || auth()->user()->isManager()))
                                            <form action="{{ route('purchasing.approve', $purchase->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Setujui Purchase Order #{{ $purchase->po_number }}? Tanda tangan digital Anda akan terstempel pada nota PO.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2" title="Setujui (ACC) PO">
                                                    <i class="fa-solid fa-signature me-1"></i> ACC
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Multi-Stage Payment Button (DP / Termin / Pelunasan) -->
                                        @if((auth()->user()->isOwner() || auth()->user()->isSuperAdmin() || auth()->user()->isManager()) && !$purchase->isPaid())
                                            <button type="button" 
                                                    @click="openPayModal({{ json_encode($purchase) }})" 
                                                    class="btn btn-sm btn-outline-primary py-0 px-2 font-semibold text-xs" 
                                                    title="Bayar Tagihan Vendor (DP / Termin / Pelunasan)">
                                                <i class="fa-solid fa-wallet me-1"></i> {{ $purchase->isPartiallyPaid() ? 'Termin' : 'Bayar DP' }}
                                            </button>
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
                                            '{{ $purchase->verified_at ? \Carbon\Carbon::parse($purchase->verified_at)->format('d M Y, H:i') : '' }}',
                                            {{ json_encode($purchase->payments ?? []) }},
                                            '{{ number_format($purchase->paid_amount, 0, ',', '.') }}',
                                            '{{ number_format($purchase->remaining_amount ?? $purchase->total_cost, 0, ',', '.') }}',
                                            '{{ $purchase->payment_status }}'
                                        )" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Cetak Dokumen PO">
                                            <i class="fa-solid fa-print text-xs"></i>
                                        </button>

                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isOwner())
                                            <form action="{{ route('purchasing.destroy', $purchase->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data Purchase Order #{{ $purchase->po_number }} ini dari sistem?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus PO (Super Admin)">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <div class="p-4">
                                        <i class="fa-solid fa-cart-shopping fs-1 text-slate-300 mb-2"></i>
                                        <p class="mb-0">Belum ada data riwayat Purchase Order.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Pembayaran Tagihan PO Satuan (Transfer Kas/Bank) -->
    <div x-show="payOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" style="display: none; position: fixed; inset: 0; z-index: 999999 !important;" x-cloak>
        <div class="bg-white rounded-xl shadow-2xl border w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden" @click.away="payOpen = false" x-if="payPurchase">
            <form :action="'/purchasing/' + (payPurchase ? payPurchase.id : '') + '/pay'" method="POST" class="flex flex-col h-full mb-0">
                @csrf
                <div class="bg-slate-900 text-white px-4 py-3 d-flex justify-content-between align-items-center flex-shrink-0">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-credit-card text-emerald-400 fs-5"></i>
                        <div>
                            <h6 class="fw-bold mb-0 text-white font-mono" x-text="'BAYAR TAGIHAN PO: ' + (payPurchase ? payPurchase.po_number : '')"></h6>
                            <span class="text-[11px] text-slate-300">Pencatatan Pembayaran Bertahap (DP / Termin / Pelunasan)</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white text-xs" @click="payOpen = false"></button>
                </div>
                
                <div class="p-4 space-y-3.5 text-xs overflow-y-auto flex-1">
                    <!-- Summary 3 Cards: Total, Paid, Remaining -->
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="text-[10px] text-slate-500 uppercase font-semibold">Total Tagihan</div>
                            <div class="font-mono font-bold text-slate-900 text-xs mt-0.5" x-text="'Rp ' + Number(payTotalCost).toLocaleString('id-ID')"></div>
                        </div>
                        <div class="p-2.5 bg-emerald-50 rounded-xl border border-emerald-200">
                            <div class="text-[10px] text-emerald-700 uppercase font-semibold">Sudah Terbayar</div>
                            <div class="font-mono font-bold text-emerald-800 text-xs mt-0.5" x-text="'Rp ' + Number(payPaidAmount).toLocaleString('id-ID')"></div>
                        </div>
                        <div class="p-2.5 bg-amber-50 rounded-xl border border-amber-300 ring-1 ring-amber-300">
                            <div class="text-[10px] text-amber-800 uppercase font-semibold">Sisa Tagihan</div>
                            <div class="font-mono font-bold text-amber-950 text-xs mt-0.5" x-text="'Rp ' + Number(payRemainingAmount).toLocaleString('id-ID')"></div>
                        </div>
                    </div>

                    <!-- 3 Payment Stage Option Selector (Pembayaran 1, Pembayaran ke 2, Pelunasan) -->
                    <div>
                        <label class="form-label font-bold text-slate-800 text-xs uppercase mb-1.5 d-flex align-items-center justify-content-between">
                            <span><i class="fa-solid fa-list-ol text-indigo-600 me-1"></i> Pilih Tahap Pembayaran <span class="text-rose-500">*</span></span>
                            <span class="text-[11px] font-normal text-slate-500">Klik salah satu pilihan</span>
                        </label>
                        <input type="hidden" name="payment_step" :value="payStep">
                        <div class="grid grid-cols-3 gap-2">
                            <!-- Option 1: Pembayaran 1 (DP) -->
                            <button type="button" 
                                    @click="setPayStep('step_1')"
                                    :class="payStep === 'step_1' ? 'border-indigo-600 bg-indigo-50/90 ring-2 ring-indigo-500 text-indigo-950 font-bold shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                    class="p-2.5 rounded-xl border text-left transition relative flex flex-col justify-between cursor-pointer">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge text-[10px]" :class="payStep === 'step_1' ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-700'">Tahap 1</span>
                                        <i class="fa-solid fa-circle-check text-indigo-600" x-show="payStep === 'step_1'"></i>
                                    </div>
                                    <div class="font-bold text-xs">1. Pembayaran 1</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">DP / Uang Muka</div>
                                </div>
                            </button>

                            <!-- Option 2: Pembayaran ke-2 (Termin 2) -->
                            <button type="button" 
                                    @click="setPayStep('step_2')"
                                    :class="payStep === 'step_2' ? 'border-indigo-600 bg-indigo-50/90 ring-2 ring-indigo-500 text-indigo-950 font-bold shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                    class="p-2.5 rounded-xl border text-left transition relative flex flex-col justify-between cursor-pointer">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge text-[10px]" :class="payStep === 'step_2' ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-700'">Tahap 2</span>
                                        <i class="fa-solid fa-circle-check text-indigo-600" x-show="payStep === 'step_2'"></i>
                                    </div>
                                    <div class="font-bold text-xs">2. Pembayaran ke-2</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">Termin / Angsuran</div>
                                </div>
                            </button>

                            <!-- Option 3: Pelunasan -->
                            <button type="button" 
                                    @click="setPayStep('pelunasan')"
                                    :class="payStep === 'pelunasan' ? 'border-emerald-600 bg-emerald-50/90 ring-2 ring-emerald-500 text-emerald-950 font-bold shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                    class="p-2.5 rounded-xl border text-left transition relative flex flex-col justify-between cursor-pointer">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge text-[10px]" :class="payStep === 'pelunasan' ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700'">Tahap 3</span>
                                        <i class="fa-solid fa-circle-check text-emerald-600" x-show="payStep === 'pelunasan'"></i>
                                    </div>
                                    <div class="font-bold text-xs">3. Pelunasan</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">Lunas Penuh (100%)</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Nominal Input with Quick Percentage Buttons -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label font-bold text-slate-800 text-xs uppercase mb-0">
                                Nominal Pembayaran Sekarang <span class="text-rose-500">*</span>
                            </label>
                            <div class="d-flex gap-1">
                                <button type="button" @click="setPercent(20)" class="btn btn-xs btn-white border py-0 px-2 text-[10px] rounded hover:bg-slate-100 font-semibold">20% DP</button>
                                <button type="button" @click="setPercent(50)" class="btn btn-xs btn-white border py-0 px-2 text-[10px] rounded hover:bg-slate-100 font-semibold">50% DP</button>
                                <button type="button" @click="setFullRemaining()" class="btn btn-xs py-0 px-2 text-[10px] rounded font-semibold text-emerald-700 border border-emerald-300 bg-emerald-100 hover:bg-emerald-200">Sisa Penuh</button>
                            </div>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text font-bold bg-white text-slate-700 border-slate-300">Rp</span>
                            <input type="number" 
                                   name="amount" 
                                   x-model.number="payAmount" 
                                   min="1" 
                                   :max="payRemainingAmount" 
                                   class="form-control font-mono font-bold text-base text-slate-900 border-slate-300" 
                                   placeholder="0" 
                                   required>
                        </div>
                        <div class="d-flex justify-content-between text-[11px] pt-1 text-slate-600">
                            <span>Sisa tagihan setelah ini: <strong :class="(payRemainingAmount - payAmount) <= 0 ? 'text-emerald-700' : 'text-amber-700'" x-text="'Rp ' + Math.max(0, payRemainingAmount - (payAmount || 0)).toLocaleString('id-ID')"></strong></span>
                            <span x-show="(payRemainingAmount - payAmount) <= 0" class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 font-semibold">Akan Lunas (100%)</span>
                        </div>
                    </div>

                    <!-- Previous Payments History (If Any) -->
                    <div x-show="payPurchase && payPurchase.payments && payPurchase.payments.length > 0" class="p-2.5 bg-white rounded-lg border border-slate-200 text-xs space-y-1.5">
                        <div class="font-bold text-slate-700 text-[11px]"><i class="fa-solid fa-clock-rotate-left me-1 text-teal-600"></i> Riwayat Pembayaran Sebelumnya:</div>
                        <div class="space-y-1 max-h-24 overflow-y-auto">
                            <template x-for="p in (payPurchase ? payPurchase.payments : [])" :key="p.id">
                                <div class="d-flex justify-content-between align-items-center bg-slate-50 p-1.5 rounded border text-[11px]">
                                    <div>
                                        <span class="badge bg-teal-50 text-teal-700 border border-teal-200 me-1 font-semibold" x-text="p.payment_step_label || p.payment_step"></span>
                                        <span class="text-slate-500" x-text="(p.paid_at || '').substring(0, 10)"></span>
                                    </div>
                                    <div class="font-mono font-bold text-emerald-700" x-text="'Rp ' + Number(p.amount).toLocaleString('id-ID')"></div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Payment Source (COA Account) -->
                    <div>
                        <label class="form-label font-semibold text-slate-700 text-xs uppercase">
                            Sumber Dana Pembayaran (Akun Kas / Bank) <span class="text-rose-500">*</span>
                        </label>
                        <select name="account_id" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Akun Kas / Bank --</option>
                            @foreach($paymentAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->kode_akun }} - {{ $acc->nama_akun }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Payment Method -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Metode Pembayaran</label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="Transfer Bank BCA">Transfer Bank BCA</option>
                                <option value="Transfer Bank Mandiri">Transfer Bank Mandiri</option>
                                <option value="Transfer Bank BRI">Transfer Bank BRI</option>
                                <option value="Transfer Bank BNI">Transfer Bank BNI</option>
                                <option value="Kas Tunai">Kas Tunai</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">No. Referensi / Slip Transfer</label>
                            <input type="text" name="payment_reference" class="form-control form-control-sm font-mono" placeholder="e.g. TRF-20260914-01">
                        </div>
                    </div>

                    <!-- Payment Notes -->
                    <div>
                        <label class="form-label font-semibold text-slate-700 text-xs uppercase">Catatan Pembayaran</label>
                        <input type="text" name="payment_notes" class="form-control form-control-sm" placeholder="e.g. DP 20% via transfer BCA">
                    </div>
                </div>

                <div class="bg-slate-50 border-top px-4 py-2.5 d-flex justify-content-end gap-2 flex-shrink-0">
                    <button type="button" class="btn-odoo-secondary" @click="payOpen = false">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success font-bold px-3">
                        <i class="fa-solid fa-check me-1"></i>
                        <span x-text="payStep === 'step_1' ? 'Konfirmasi Pembayaran 1 (DP)' : (payStep === 'step_2' ? 'Konfirmasi Pembayaran ke-2' : 'Konfirmasi Pelunasan')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<script>
    function printPO(poNum, supplierName, itemName, qty, totalCost, poDate, staffName, staffSig, managerName, managerSig, approvedAt, warehouseName, warehouseSig, verifiedAt, payments, paidAmount, remainingAmount, paymentStatus) {
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

        let paymentsHtml = '';
        if (payments && payments.length > 0) {
            paymentsHtml = `
                <div style="margin-top: 20px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; background: #f8fafc;">
                    <div style="font-weight: bold; font-size: 11px; margin-bottom: 6px; color: #1e3a8a;">RIWAYAT PEMBAYARAN TAGIHAN / DP VENDOR:</div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                        <thead>
                            <tr style="background: #e2e8f0;">
                                <th style="border: 1px solid #cbd5e1; padding: 5px; text-align: left;">Tahap</th>
                                <th style="border: 1px solid #cbd5e1; padding: 5px; text-align: center;">Tanggal</th>
                                <th style="border: 1px solid #cbd5e1; padding: 5px; text-align: left;">Metode / Rekening</th>
                                <th style="border: 1px solid #cbd5e1; padding: 5px; text-align: left;">No. Ref</th>
                                <th style="border: 1px solid #cbd5e1; padding: 5px; text-align: right;">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${payments.map(p => `
                                <tr>
                                    <td style="border: 1px solid #cbd5e1; padding: 5px; font-weight: bold;">${p.payment_step_label || p.payment_step}</td>
                                    <td style="border: 1px solid #cbd5e1; padding: 5px; text-align: center;">${(p.paid_at || '').substring(0, 10)}</td>
                                    <td style="border: 1px solid #cbd5e1; padding: 5px;">${p.account ? (p.account.kode_akun + ' - ' + p.account.nama_akun) : (p.payment_method || '-')}</td>
                                    <td style="border: 1px solid #cbd5e1; padding: 5px; font-family: monospace;">${p.payment_reference || '-'}</td>
                                    <td style="border: 1px solid #cbd5e1; padding: 5px; text-align: right; font-family: monospace; font-weight: bold;">Rp ${Number(p.amount || 0).toLocaleString('id-ID')}</td>
                                </tr>
                            `).join('')}
                            <tr style="background: #f1f5f9; font-weight: bold;">
                                <td colspan="4" style="border: 1px solid #cbd5e1; padding: 5px; text-align: right;">Total Terbayar:</td>
                                <td style="border: 1px solid #cbd5e1; padding: 5px; text-align: right; font-family: monospace; color: #059669;">Rp ${paidAmount}</td>
                            </tr>
                            ${remainingAmount && remainingAmount !== '0' ? `
                            <tr style="background: #fffbeb; font-weight: bold;">
                                <td colspan="4" style="border: 1px solid #cbd5e1; padding: 5px; text-align: right; color: #b45309;">Sisa Tagihan:</td>
                                <td style="border: 1px solid #cbd5e1; padding: 5px; text-align: right; font-family: monospace; color: #b45309;">Rp ${remainingAmount}</td>
                            </tr>` : ''}
                        </tbody>
                    </table>
                </div>
            `;
        }

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

                ${paymentsHtml}

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
