@extends('layouts.app')

@section('title', 'Rencana Pengadaan Bahan (Purchase Plans)')
@section('page-title', 'Rencana Pengadaan Bahan (Purchase Plans)')

@section('action-buttons')
<a href="{{ route('purchasing.plans.create') }}" class="btn-odoo-primary text-decoration-none">
    <i class="fa-solid fa-plus me-1"></i>
    <span>Buat Purchase Plan Baru</span>
</a>
<a href="{{ route('purchasing.index') }}" class="btn-odoo-secondary text-decoration-none">
    <i class="fa-solid fa-list me-1"></i>
    <span>Daftar PO Satuan</span>
</a>
@endsection

@section('content')
<div x-data="{ 
    detailOpen: false,
    rejectOpen: false,
    payOpen: false,
    selectedPlan: null,
    rejectPlanId: null,
    payPlan: null,
    payStep: 'step_1',
    payAmount: 0,
    payTotalCost: 0,
    payPaidAmount: 0,
    payRemainingAmount: 0,
    openPlanDetail(plan) {
        this.selectedPlan = plan;
        this.detailOpen = true;
    },
    openRejectModal(planId) {
        this.rejectPlanId = planId;
        this.rejectOpen = true;
    },
    openPayModal(plan) {
        this.payPlan = plan;
        this.payTotalCost = Number(plan.total_estimated_cost || 0);
        this.payPaidAmount = Number(plan.paid_amount || 0);
        this.payRemainingAmount = Math.max(0, this.payTotalCost - this.payPaidAmount);

        const paymentsCount = (plan.payments || []).length;
        if (this.payPaidAmount <= 0) {
            this.payStep = 'step_1';
            this.payAmount = Math.round(this.payRemainingAmount * 0.5);
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
            this.payAmount = Math.round(this.payRemainingAmount * 0.5);
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

    <!-- Top KPI Stat Cards -->
    <div class="d-flex align-items-center gap-2 mb-3 overflow-x-auto pb-1">
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-wallet text-teal-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-teal-700">Rp {{ number_format($totalPlannedCost, 0, ',', '.') }}</div>
                <div class="o_stat_text">Total Anggaran Pengadaan</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-clock-rotate-left text-amber-500 fs-5"></i>
            <div>
                <div class="o_stat_value text-amber-600">{{ number_format($waitingApprovalCount) }}</div>
                <div class="o_stat_text">Menunggu ACC Owner</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-circle-check text-emerald-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-emerald-600">{{ number_format($approvedCount) }}</div>
                <div class="o_stat_text">Disetujui / PO Terbit</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-file-invoice-dollar text-indigo-600 fs-5"></i>
            <div>
                <div class="o_stat_value text-indigo-700">
                    {{ number_format($plans->whereIn('status', ['approved_by_owner', 'completed'])->where('payment_status', '!=', 'paid')->count()) }}
                </div>
                <div class="o_stat_text">Tagihan Belum Dibayar</div>
            </div>
        </div>
        <div class="o_stat_button bg-white shadow-sm">
            <i class="fa-solid fa-ban text-rose-500 fs-5"></i>
            <div>
                <div class="o_stat_value text-rose-600">{{ number_format($rejectedCount) }}</div>
                <div class="o_stat_text">Ditolak Owner</div>
            </div>
        </div>
    </div>

    <!-- Main Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden bg-white">
        <!-- View Mode 1: Table List View -->
        <div class="table-view-container">
            <div class="table-responsive">
                <table class="table table-hover o_list_table mb-0" id="main-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="ps-3 text-center no-sort">
                                <input type="checkbox" class="form-check-input">
                            </th>
                            <th class="sortable">No. Rencana Pengadaan</th>
                            <th class="sortable">Judul Pengadaan</th>
                            <th class="sortable">Cabang & Pembuat</th>
                            <th class="sortable text-center">Bundle Item</th>
                            <th class="sortable text-end">Total Estimasi Biaya</th>
                            <th class="sortable text-center">Status Persetujuan</th>
                            <th class="sortable text-center">Status Tagihan</th>
                            <th class="text-center no-sort" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                            <tr class="search-row">
                                <td class="ps-3 text-center">
                                    <input type="checkbox" class="form-check-input">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-link p-0 fw-bold font-mono text-indigo-700 text-decoration-none hover:underline d-inline-flex align-items-center gap-1.5"
                                            onclick="showPlanById({{ $plan->id }})">
                                        <i class="fa-solid fa-box-archive text-indigo-600 text-xs"></i>
                                        <span>{{ $plan->plan_number }}</span>
                                    </button>
                                    <div class="text-[10px] text-slate-400">
                                        Target: {{ $plan->target_date ? $plan->target_date->format('d M Y') : 'Segera' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800 text-xs">{{ $plan->title }}</div>
                                    @if($plan->notes)
                                        <div class="text-[11px] text-slate-500 line-clamp-1 italic">{{ $plan->notes }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-slate-100 text-slate-700 border text-[11px] font-normal">
                                        {{ $plan->branch->nama_cabang ?? 'Pusat' }}
                                    </span>
                                    <div class="text-[10px] text-slate-500 mt-0.5">
                                        Oleh: <strong>{{ $plan->user->username ?? 'Purchasing' }}</strong>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-sky-50 text-sky-800 border border-sky-200 font-bold px-2 py-0.5 text-[11px]">
                                        {{ $plan->items->count() }} Produk
                                    </span>
                                </td>
                                <td class="text-end font-mono fw-bold text-slate-800">
                                    Rp {{ number_format($plan->total_estimated_cost, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if($plan->status === 'waiting_owner_approval')
                                        <span class="badge bg-amber-50 text-amber-800 border border-amber-300 text-[11px] font-semibold">
                                            <i class="fa-solid fa-clock me-1"></i> Menunggu ACC Owner
                                        </span>
                                    @elseif($plan->status === 'approved_by_owner' || $plan->status === 'completed')
                                        <span class="badge bg-blue-50 text-blue-800 border border-blue-300 text-[11px] font-semibold">
                                            <i class="fa-solid fa-file-circle-check me-1"></i> PO Disetujui (PO Terbit)
                                        </span>
                                        @if($plan->approvedBy)
                                            <div class="text-[10px] text-blue-700 font-semibold mt-0.5">
                                                ✓ ACC: {{ $plan->approvedBy->username }}
                                            </div>
                                        @endif
                                    @elseif($plan->status === 'rejected_by_owner')
                                        <span class="badge bg-rose-50 text-rose-800 border border-rose-300 text-[11px] font-semibold">
                                            <i class="fa-solid fa-ban me-1"></i> Ditolak Owner
                                        </span>
                                    @else
                                        <span class="badge bg-slate-100 text-slate-700 border text-[11px] font-semibold">
                                            Draft
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($plan->isApproved())
                                        @if($plan->payment_status === 'paid')
                                            <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 text-[11px] font-bold">
                                                <i class="fa-solid fa-check-double me-1"></i> LUNAS
                                            </span>
                                        @elseif($plan->payment_status === 'partial')
                                            <div class="d-inline-flex flex-col text-start p-1 bg-amber-50 rounded border border-amber-300">
                                                <span class="text-[10px] font-bold text-amber-900">
                                                    <i class="fa-solid fa-clock-rotate-left me-0.5 text-amber-600"></i> DP/Sebagian:
                                                </span>
                                                <span class="font-mono font-bold text-[10px] text-emerald-800">
                                                    Rp {{ number_format($plan->paid_amount, 0, ',', '.') }}
                                                </span>
                                                <span class="text-[9px] text-rose-700 font-semibold font-mono">
                                                    Sisa: Rp {{ number_format($plan->remaining_amount ?? ($plan->total_estimated_cost - $plan->paid_amount), 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="badge bg-amber-100 text-amber-900 border border-amber-300 text-[11px] font-bold">
                                                <i class="fa-solid fa-file-invoice-dollar me-1"></i> Tagihan Belum Dibayar
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                        <!-- Detail Button -->
                                        <button type="button" class="btn btn-sm btn-outline-secondary border-slate-300 text-slate-700 hover:bg-slate-100 hover:text-indigo-600 rounded-md p-0 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" title="Buka Rincian Bundle & Tagihan"
                                                @click="openPlanDetail({{ json_encode($plan) }})">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </button>

                                        @if($plan->status === 'draft' || $plan->status === 'rejected_by_owner' || auth()->user()->isSuperAdmin())
                                            <!-- Edit / Lanjutkan Draft Button -->
                                            <a href="{{ route('purchasing.plans.edit', $plan->id) }}" class="btn btn-sm btn-outline-secondary border-slate-300 text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300 rounded-md p-0 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" title="Edit Plan">
                                                <i class="fa-solid fa-pen text-xs"></i>
                                            </a>

                                            @if($plan->status === 'draft' || $plan->status === 'rejected_by_owner')
                                                <!-- Ajukan ke Owner (ACC) Button -->
                                                <form action="{{ route('purchasing.plans.submit-rfq', $plan->id) }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Ajukan Purchase Plan #{{ $plan->plan_number }} ke Owner untuk persetujuan Owner?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success border-emerald-300 text-emerald-600 hover:bg-emerald-50 rounded-md p-0 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" title="Ajukan ke Owner (ACC)">
                                                        <i class="fa-solid fa-paper-plane text-xs"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            <!-- Hapus Plan Button -->
                                            <form action="{{ route('purchasing.plans.destroy', $plan->id) }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Hapus Purchase Plan #{{ $plan->plan_number }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-rose-300 text-rose-500 hover:bg-rose-50 hover:text-rose-700 rounded-md p-0 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" title="Hapus Plan (Super Admin)">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if(auth()->user()->isOwner())
                                            @if($plan->status === 'waiting_owner_approval')
                                                <form action="{{ route('purchasing.plans.approve', $plan->id) }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Setujui Purchase Plan #{{ $plan->plan_number }}? Seluruh item bundle akan diterbitkan menjadi PO dan tagihan siap dibayar.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success rounded-md px-2 d-inline-flex align-items-center justify-content-center gap-1 text-xs fw-semibold shadow-xs" style="height: 28px;" title="Setujui (ACC) Rencana Pengadaan">
                                                        <i class="fa-solid fa-check text-xs"></i> ACC
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-danger border-rose-300 text-rose-600 hover:bg-rose-50 rounded-md p-0 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" title="Tolak Rencana Pengadaan"
                                                        @click="openRejectModal({{ $plan->id }})">
                                                    <i class="fa-solid fa-xmark text-xs"></i>
                                                </button>
                                            @elseif($plan->isApproved() && $plan->payment_status !== 'paid')
                                                <!-- Tombol Bayar Tagihan untuk Owner (Dukungan DP / Termin / Pelunasan) -->
                                                <button type="button" 
                                                        class="btn btn-sm {{ $plan->payment_status === 'partial' ? 'btn-warning text-slate-900 border border-amber-400' : 'btn-primary' }} rounded-md px-2 d-inline-flex align-items-center justify-content-center gap-1 text-xs fw-bold shadow-xs" 
                                                        style="height: 28px;"
                                                        title="{{ $plan->payment_status === 'partial' ? 'Bayar Termin Lanjutan (Sisa: Rp ' . number_format($plan->remaining_amount ?? ($plan->total_estimated_cost - $plan->paid_amount), 0, ',', '.') . ')' : 'Bayar Tagihan Supplier (Transfer)' }}"
                                                        @click="openPayModal({{ json_encode($plan) }})">
                                                    <i class="fa-solid fa-credit-card text-xs"></i> 
                                                    <span>{{ $plan->payment_status === 'partial' ? 'Termin' : 'Bayar' }}</span>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="p-4">
                                        <i class="fa-solid fa-box-archive fs-1 text-slate-300 mb-2"></i>
                                        <p class="mb-0">Belum ada data rencana pengadaan (Purchase Plan).</p>
                                        <a href="{{ route('purchasing.plans.create') }}" class="btn btn-sm btn-odoo-primary mt-2 text-decoration-none">
                                            <i class="fa-solid fa-plus me-1"></i> Buat Purchase Plan Baru
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Rincian Bundle & Tagihan Vendor (Odoo Detail Sheet) -->
    <div x-show="detailOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" 
         style="display: none; position: fixed; inset: 0; z-index: 999999 !important;" 
         x-cloak>
        <div class="bg-white rounded-xl shadow-2xl border w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-fade-in"
             @click.away="detailOpen = false">
            
            <!-- Modal Header -->
            <div class="bg-slate-900 text-white px-4 py-3 d-flex justify-content-between align-items-center flex-shrink-0">
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ asset('images/logosnaprint.jpeg') }}" alt="Snaprint" class="rounded-circle" style="width: 28px; height: 28px; object-fit: cover;">
                    <div>
                        <h6 class="fw-bold mb-0 text-white font-mono" x-text="'PURCHASE PLAN: ' + (selectedPlan ? selectedPlan.plan_number : '')"></h6>
                        <span class="text-[11px] text-slate-300">Rincian Bundle Pengadaan & Tagihan Pembayaran Vendor</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" @click="detailOpen = false"></button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="p-4 overflow-y-auto space-y-4 flex-grow bg-white text-xs" x-if="selectedPlan">
                <!-- Info Header -->
                <div class="d-flex justify-content-between align-items-start border-bottom pb-3">
                    <div>
                        <h5 class="fw-bold text-slate-900 mb-1" x-text="selectedPlan.title"></h5>
                        <div class="text-slate-500">
                            Cabang: <strong class="text-slate-800" x-text="selectedPlan.branch ? selectedPlan.branch.nama_cabang : 'Pusat'"></strong> &bull; 
                            Dibuat Oleh: <strong class="text-slate-800" x-text="selectedPlan.user ? selectedPlan.user.username : 'Purchasing'"></strong>
                        </div>
                        <div class="text-slate-500 mt-0.5" x-show="selectedPlan.target_date">
                            Target Realisasi: <strong class="text-indigo-700" x-text="selectedPlan.target_date"></strong>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge px-3 py-1.5 rounded-md font-bold uppercase tracking-wider text-xs"
                              :class="{
                                  'bg-amber-100 text-amber-800 border border-amber-400': selectedPlan.status === 'waiting_owner_approval',
                                  'bg-emerald-100 text-emerald-800 border border-emerald-400': selectedPlan.status === 'approved_by_owner' || selectedPlan.status === 'completed',
                                  'bg-rose-100 text-rose-800 border border-rose-400': selectedPlan.status === 'rejected_by_owner',
                                  'bg-slate-100 text-slate-700 border': selectedPlan.status === 'draft'
                              }"
                              x-text="selectedPlan.status === 'waiting_owner_approval' ? 'MENUNGGU ACC OWNER' : (selectedPlan.status === 'approved_by_owner' ? 'DISUTUJUI OWNER (PO TERBIT)' : (selectedPlan.status === 'rejected_by_owner' ? 'DITOLAK' : 'DRAFT'))">
                        </span>
                        <div class="text-slate-400 mt-1 font-mono text-[11px]" x-text="'Tgl Dibuat: ' + (selectedPlan.created_at || '').substring(0, 10)"></div>
                    </div>
                </div>

                <!-- TAGIHAN PEMBAYARAN SUPPLIER (VENDOR BILLS) - Muncul jika sudah di-ACC Owner -->
                <!-- TAGIHAN PEMBAYARAN SUPPLIER (VENDOR BILLS) - Muncul jika sudah di-ACC Owner -->
                <template x-if="selectedPlan.status === 'approved_by_owner' || selectedPlan.status === 'completed'">
                    <div class="p-3 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-3">
                        <div class="d-flex justify-content-between align-items-center border-bottom border-indigo-200 pb-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-credit-card text-indigo-700 fs-5"></i>
                                <div>
                                    <h6 class="fw-bold text-indigo-950 mb-0">Rincian Tagihan & Rekening Transfer Vendor</h6>
                                    <span class="text-[10px] text-indigo-700">Daftar tagihan yang harus ditransfer oleh Owner ke supplier</span>
                                </div>
                            </div>
                            <div>
                                <span x-show="selectedPlan.payment_status === 'paid'" class="badge bg-emerald-600 text-white px-2.5 py-1 font-bold">
                                    <i class="fa-solid fa-check-double me-1"></i> TAGIHAN LUNAS
                                </span>
                                <span x-show="selectedPlan.payment_status === 'partial'" class="badge bg-amber-500 text-white px-2.5 py-1 font-bold animate-pulse">
                                    <i class="fa-solid fa-hourglass-half me-1"></i> DIBAYAR SEBAGIAN (DP)
                                </span>
                                <span x-show="selectedPlan.payment_status !== 'paid' && selectedPlan.payment_status !== 'partial'" class="badge bg-rose-500 text-white px-2.5 py-1 font-bold animate-pulse">
                                    <i class="fa-solid fa-clock me-1"></i> BELUM DITRANSFER
                                </span>
                            </div>
                        </div>

                        <!-- Payment Summary Card (Total, Terbayar, Sisa) -->
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="p-2 bg-white rounded border border-slate-200">
                                <div class="text-[10px] text-slate-500 uppercase font-semibold">Total Tagihan</div>
                                <div class="font-mono font-bold text-slate-800 text-xs" x-text="'Rp ' + Number(selectedPlan.total_estimated_cost || 0).toLocaleString('id-ID')"></div>
                            </div>
                            <div class="p-2 bg-emerald-50 rounded border border-emerald-200">
                                <div class="text-[10px] text-emerald-700 uppercase font-semibold">Sudah Terbayar</div>
                                <div class="font-mono font-bold text-emerald-800 text-xs" x-text="'Rp ' + Number(selectedPlan.paid_amount || 0).toLocaleString('id-ID')"></div>
                            </div>
                            <div class="p-2 bg-amber-50 rounded border border-amber-200">
                                <div class="text-[10px] text-amber-700 uppercase font-semibold">Sisa Tagihan</div>
                                <div class="font-mono font-bold text-amber-900 text-xs" x-text="'Rp ' + Number(selectedPlan.remaining_amount !== null ? selectedPlan.remaining_amount : Math.max(0, (selectedPlan.total_estimated_cost || 0) - (selectedPlan.paid_amount || 0))).toLocaleString('id-ID')"></div>
                            </div>
                        </div>

                        <!-- Installment Payment History Table (If any payments exist) -->
                        <div x-show="selectedPlan.payments && selectedPlan.payments.length > 0" class="p-2.5 bg-white rounded-lg border border-slate-200 text-xs space-y-2">
                            <div class="font-bold text-slate-800 d-flex justify-content-between align-items-center">
                                <span><i class="fa-solid fa-receipt text-teal-600 me-1"></i> Riwayat Pembayaran Termin:</span>
                                <span class="badge bg-teal-100 text-teal-800 text-[10px]" x-text="selectedPlan.payments ? selectedPlan.payments.length + 'x Pembayaran' : ''"></span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered text-[11px] mb-0">
                                    <thead class="bg-slate-100 text-slate-600">
                                        <tr>
                                            <th>Tahap Pembayaran</th>
                                            <th>Tanggal</th>
                                            <th>Sumber Kas/Bank</th>
                                            <th>No. Ref</th>
                                            <th class="text-end">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(pay, pIdx) in (selectedPlan.payments || [])" :key="pIdx">
                                            <tr>
                                                <td>
                                                    <span class="badge bg-teal-50 text-teal-700 border border-teal-200 font-semibold" x-text="pay.payment_step_label || (pay.payment_step === 'step_1' ? 'Pembayaran 1 (DP)' : (pay.payment_step === 'step_2' ? 'Pembayaran ke-2' : 'Pelunasan'))"></span>
                                                </td>
                                                <td class="font-mono text-slate-600" x-text="(pay.paid_at || '').substring(0, 10)"></td>
                                                <td>
                                                    <span class="text-slate-800" x-text="pay.account ? (pay.account.kode_akun + ' - ' + pay.account.nama_akun) : (pay.payment_method || '-')"></span>
                                                </td>
                                                <td class="font-mono text-slate-500" x-text="pay.payment_reference || '-'"></td>
                                                <td class="text-end font-mono font-bold text-emerald-700" x-text="'Rp ' + Number(pay.amount || 0).toLocaleString('id-ID')"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Per Supplier Billing Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <template x-for="(bill, bIdx) in (selectedPlan.supplier_bills || [])" :key="bIdx">
                                <div class="bg-white border rounded-lg p-3 shadow-sm space-y-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong class="text-slate-900 text-xs" x-text="bill.supplier_name"></strong>
                                            <div class="text-[10px] text-slate-500" x-text="bill.perusahaan || ''"></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="text-[10px] text-slate-400 uppercase">Total Tagihan</div>
                                            <strong class="text-indigo-900 font-mono text-xs" x-text="'Rp ' + Number(bill.total_amount || 0).toLocaleString('id-ID')"></strong>
                                        </div>
                                    </div>

                                    <!-- Bank Account Card -->
                                    <div class="p-2 bg-slate-50 rounded border text-xs">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold text-[10px]" x-text="bill.bank_name || 'Bank Rekening'"></span>
                                            <button type="button" x-show="bill.bank_account_number" 
                                                    @click="navigator.clipboard.writeText(bill.bank_account_number); alert('No. Rekening ' + bill.bank_account_number + ' berhasil disalin!')" 
                                                    class="btn btn-xs btn-outline-secondary py-0 px-1 text-[10px]" title="Salin No Rekening">
                                                <i class="fa-solid fa-copy me-1"></i> Salin Rek
                                            </button>
                                        </div>
                                        <div class="font-mono fw-bold text-slate-900" x-text="bill.bank_account_number || 'No. Rekening belum diatur di menu Vendor'"></div>
                                        <div class="text-[10px] text-slate-500 mt-0.5" x-show="bill.bank_account_name" x-text="'a/n ' + bill.bank_account_name"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Justification / Notes -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200" x-show="selectedPlan.notes">
                    <div class="font-bold text-slate-800 mb-0.5">Catatan / Justifikasi Kebutuhan:</div>
                    <div class="text-slate-700" x-text="selectedPlan.notes"></div>
                </div>

                <!-- Rejection Notes (If Any) -->
                <div class="p-3 bg-rose-50 rounded-xl border border-rose-200" x-show="selectedPlan.rejection_notes">
                    <div class="font-bold text-rose-900 mb-0.5">Alasan Penolakan dari Owner:</div>
                    <div class="text-rose-800" x-text="selectedPlan.rejection_notes"></div>
                </div>

                <!-- Bundle Item Table -->
                <div>
                    <h6 class="fw-bold text-slate-800 mb-2">Daftar Produk & Rincian Biaya Pengadaan:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm text-xs mb-0">
                            <thead class="bg-slate-100 text-slate-700">
                                <tr>
                                    <th style="width: 30px;" class="text-center">No</th>
                                    <th>Nama Produk / Bahan Baku</th>
                                    <th>Supplier / Vendor</th>
                                    <th class="text-center" style="width: 80px;">Qty</th>
                                    <th class="text-end" style="width: 130px;">Estimasi Harga Satuan</th>
                                    <th class="text-end" style="width: 140px;">Subtotal Biaya</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, idx) in (selectedPlan.items || [])" :key="idx">
                                    <tr>
                                        <td class="text-center font-bold text-slate-400" x-text="idx + 1"></td>
                                        <td>
                                            <strong class="text-slate-900" x-text="item.material_name"></strong>
                                            <div class="text-[10px] text-slate-400" x-show="item.fixed_size" x-text="'Ukuran: ' + item.fixed_size + 'm'"></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-slate-100 text-slate-700 border" x-text="item.supplier_name || '-'"></span>
                                        </td>
                                        <td class="text-center fw-bold font-mono" x-text="item.qty + ' unit'"></td>
                                        <td class="text-end font-mono text-slate-700" x-text="'Rp ' + Number(item.estimated_unit_price || 0).toLocaleString('id-ID')"></td>
                                        <td class="text-end font-mono fw-bold text-slate-900" x-text="'Rp ' + Number(item.subtotal || 0).toLocaleString('id-ID')"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-slate-50 font-bold">
                                <tr>
                                    <td colspan="5" class="text-end text-slate-700 fs-6">Total Estimasi Biaya Pengadaan:</td>
                                    <td class="text-end font-mono text-teal-800 fs-6" x-text="'Rp ' + Number(selectedPlan.total_estimated_cost || 0).toLocaleString('id-ID')"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer with Owner Actions & Draft Actions -->
            <div class="bg-slate-50 px-4 py-3 border-top d-flex justify-content-between align-items-center flex-shrink-0">
                <div class="d-flex gap-2">
                    <button type="button" @click="detailOpen = false" class="btn-odoo-secondary">Tutup</button>
                    <button type="button" @click="printPurchasePlan(selectedPlan)" class="btn btn-sm btn-light border font-semibold px-3 text-slate-700">
                        <i class="fa-solid fa-print me-1.5 text-blue-600"></i> Cetak SPK / Nota PO
                    </button>
                </div>
                
                <div class="d-flex gap-2" x-if="selectedPlan">
                    <!-- Actions for Draft or Rejected Plans -->
                    <template x-if="selectedPlan.status === 'draft' || selectedPlan.status === 'rejected_by_owner'">
                        <div class="d-flex gap-2">
                            <a :href="'/purchasing/plans/' + selectedPlan.id + '/edit'" class="btn btn-sm btn-outline-primary font-semibold px-3 text-decoration-none d-inline-flex align-items-center">
                                <i class="fa-solid fa-pen-to-square me-1.5"></i> Edit / Lanjutkan Plan
                            </a>
                            <form :action="'/purchasing/plans/' + selectedPlan.id + '/submit-rfq'" method="POST" onsubmit="return confirm('Ajukan Purchase Plan ini ke Owner untuk persetujuan Owner?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary font-bold px-3">
                                    <i class="fa-solid fa-paper-plane me-1.5"></i> Ajukan Persetujuan ke Owner
                                </button>
                            </form>
                        </div>
                    </template>

                    @if(auth()->user()->isOwner())
                        <template x-if="selectedPlan.status === 'waiting_owner_approval'">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-danger font-semibold px-3"
                                        @click="openRejectModal(selectedPlan.id); detailOpen = false;">
                                    <i class="fa-solid fa-ban me-1"></i> Tolak Pengadaan
                                </button>
                                <form :action="'/purchasing/plans/' + selectedPlan.id + '/approve'" method="POST" onsubmit="return confirm('Setujui Purchase Plan ini? PO akan otomatis diterbitkan dan tagihan siap dibayar.');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success font-semibold px-3">
                                        <i class="fa-solid fa-check me-1"></i> Setujui & Terbitkan PO (ACC)
                                    </button>
                                </form>
                            </div>
                        </template>

                        <template x-if="(selectedPlan.status === 'approved_by_owner' || selectedPlan.status === 'completed') && selectedPlan.payment_status !== 'paid'">
                            <button type="button" class="btn btn-sm btn-primary font-bold px-3"
                                    @click="openPayModal(selectedPlan); detailOpen = false;">
                                <i class="fa-solid fa-wallet me-1.5"></i> Bayar Tagihan (Transfer Kas/Bank)
                            </button>
                        </template>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tolak Pengadaan Owner -->
    <div x-show="rejectOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" style="display: none; position: fixed; inset: 0; z-index: 999999 !important;" x-cloak>
        <div class="bg-white rounded-xl shadow-2xl border w-full max-w-md overflow-hidden" @click.away="rejectOpen = false">
            <form :action="'/purchasing/plans/' + rejectPlanId + '/reject'" method="POST">
                @csrf
                <div class="bg-rose-600 text-white px-4 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-white"><i class="fa-solid fa-ban me-1"></i> Tolak Purchase Plan</h6>
                    <button type="button" class="btn-close btn-close-white text-xs" @click="rejectOpen = false"></button>
                </div>
                <div class="p-4 space-y-3 text-xs">
                    <p class="text-slate-600 mb-0">Berikan catatan alasan mengapa rencana pengadaan ini ditolak:</p>
                    <textarea name="rejection_notes" rows="3" class="form-control text-xs" placeholder="Contoh: Anggaran belum mencukupi / stok lama masih tersedia..." required></textarea>
                </div>
                <div class="bg-slate-50 border-top px-4 py-2.5 d-flex justify-content-end gap-2">
                    <button type="button" class="btn-odoo-secondary" @click="rejectOpen = false">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger font-semibold">Konfirmasi Tolak</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Pembayaran Tagihan Supplier (Transfer Kas/Bank) -->
    <div x-show="payOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" style="display: none; position: fixed; inset: 0; z-index: 999999 !important;" x-cloak>
        <div class="bg-white rounded-xl shadow-2xl border w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden" @click.away="payOpen = false" x-if="payPlan">
            <form :action="'/purchasing/plans/' + (payPlan ? payPlan.id : '') + '/pay'" method="POST" class="flex flex-col h-full mb-0">
                @csrf
                <div class="bg-slate-900 text-white px-4 py-3 d-flex justify-content-between align-items-center flex-shrink-0">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-credit-card text-emerald-400 fs-5"></i>
                        <div>
                            <h6 class="fw-bold mb-0 text-white font-mono" x-text="'BAYAR TAGIHAN: ' + (payPlan ? payPlan.plan_number : '')"></h6>
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
                    <div x-show="payPlan && payPlan.payments && payPlan.payments.length > 0" class="p-2.5 bg-white rounded-lg border border-slate-200 text-xs space-y-1.5">
                        <div class="font-bold text-slate-700 text-[11px]"><i class="fa-solid fa-clock-rotate-left me-1 text-teal-600"></i> Riwayat Pembayaran Sebelumnya:</div>
                        <div class="space-y-1 max-h-24 overflow-y-auto">
                            <template x-for="p in (payPlan ? payPlan.payments : [])" :key="p.id">
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

                    <!-- Rekening Supplier Destination -->
                    <div class="p-2.5 bg-slate-50 rounded-lg border text-xs space-y-1.5" x-show="payPlan && payPlan.supplier_bills">
                        <div class="font-bold text-slate-800"><i class="fa-solid fa-building-columns text-indigo-600 me-1"></i> Rekening Tujuan Transfer Supplier:</div>
                        <template x-for="bill in (payPlan ? payPlan.supplier_bills : [])" :key="bill.supplier_name">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-1">
                                <div>
                                    <strong x-text="bill.supplier_name"></strong>: 
                                    <span class="font-mono text-indigo-700 font-semibold" x-text="(bill.bank_name || 'Bank') + ' - ' + (bill.bank_account_number || 'Belum diatur')"></span>
                                    <span class="text-slate-500 text-[11px]" x-show="bill.bank_account_name" x-text="'(a/n ' + bill.bank_account_name + ')'"></span>
                                </div>
                                <div class="font-mono fw-bold text-slate-800" x-text="'Rp ' + Number(bill.total_amount).toLocaleString('id-ID')"></div>
                            </div>
                        </template>
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
                            <input type="text" name="payment_reference" class="form-control form-control-sm font-mono" placeholder="e.g. TRF-20260910-01">
                        </div>
                    </div>

                    <!-- Payment Notes -->
                    <div>
                        <label class="form-label font-semibold text-slate-700 text-xs uppercase">Catatan Pembayaran</label>
                        <input type="text" name="payment_notes" class="form-control form-control-sm" placeholder="e.g. DP 50% via transfer m-Banking">
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

<script>
    function printPurchasePlan(plan) {
        if (!plan) return;
        const printWindow = window.open('', '_blank');
        const logoUrl = "{{ asset('images/logosnaprint.jpeg') }}";
        const isPaid = plan.payment_status === 'paid';
        const isApproved = plan.status === 'approved_by_owner' || plan.status === 'completed';

        let itemsHtml = '';
        (plan.items || []).forEach((it, idx) => {
            itemsHtml += `
                <tr>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 8px;">${idx + 1}</td>
                    <td style="border: 1px solid #cbd5e1; padding: 8px;">
                        <strong>${it.material_name || '-'}</strong>
                        ${it.fixed_size ? `<div style="font-size: 11px; color: #64748b;">Ukuran: ${it.fixed_size}m</div>` : ''}
                    </td>
                    <td style="border: 1px solid #cbd5e1; padding: 8px;">${it.supplier_name || '-'}</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 8px; font-weight: bold;">${it.qty || 0} unit</td>
                    <td style="text-align: right; border: 1px solid #cbd5e1; padding: 8px; font-family: monospace;">Rp ${Number(it.estimated_unit_price || 0).toLocaleString('id-ID')}</td>
                    <td style="text-align: right; border: 1px solid #cbd5e1; padding: 8px; font-family: monospace; font-weight: bold;">Rp ${Number(it.subtotal || 0).toLocaleString('id-ID')}</td>
                </tr>
            `;
        });

        // 1. Signature / Mark Pemohon (Manager / Purchasing Staff)
        const creatorName = plan.user ? (plan.user.full_name || plan.user.username) : 'Pembuat Pengajuan';
        const creatorRole = plan.user && plan.user.role === 'manager' ? 'Manajer Toko / Cabang' : 'Staf Purchasing';
        const creatorSig = plan.user && plan.user.signature_path ? `{{ asset('storage') }}/${plan.user.signature_path}` : null;
        
        const creatorSigHtml = creatorSig 
            ? `<img src="${creatorSig}" style="max-height: 50px; max-width: 120px; margin: 0 auto 4px auto; display: block;">` 
            : `<div style="border: 1.5px dashed #2563eb; padding: 4px; color: #1e40af; font-size: 10px; font-weight: bold; border-radius: 4px; margin-bottom: 4px; background: #eff6ff;">✓ DIAJUKAN RESMI<br><small style="font-weight:normal;">${(plan.created_at || '').substring(0, 10)}</small></div>`;

        // 2. Signature / Mark ACC Owner
        const approverName = plan.approved_by_user ? (plan.approved_by_user.full_name || plan.approved_by_user.username) : (plan.approved_by ? 'Owner Snaprint' : 'Owner');
        const approverSig = plan.approved_by_user && plan.approved_by_user.signature_path ? `{{ asset('storage') }}/${plan.approved_by_user.signature_path}` : null;
        
        const approverSigHtml = isApproved 
            ? (approverSig 
                ? `<img src="${approverSig}" style="max-height: 50px; max-width: 120px; margin: 0 auto 4px auto; display: block;">` 
                : `<div style="border: 1.5px dashed #059669; padding: 4px; color: #047857; font-size: 10px; font-weight: bold; border-radius: 4px; margin-bottom: 4px; background: #ecfdf5;">✓ ACC OWNER DISETUJUI<br><small style="font-weight:normal;">${(plan.approved_at || '').substring(0, 10)}</small></div>`)
            : `<div style="height: 45px; line-height: 45px; font-style: italic; color: #94a3b8; font-size: 11px;">[ Menunggu ACC Owner ]</div>`;

        // 3. Signature / Mark Penerima Fisik Gudang (GRN Verification)
        let warehouseVerifierName = '(...........................)';
        let warehouseVerifierSigHtml = `<div style="height: 45px; line-height: 45px; font-style: italic; color: #94a3b8; font-size: 11px;">[ Menunggu Fisik Gudang ]</div>`;
        let warehouseRole = 'Staff Gudang Cabang';

        const verifiedPurchase = (plan.purchases || []).find(p => p.status === 'received' && (p.verified_by || p.verified_by_user));
        if (verifiedPurchase) {
            const vUser = verifiedPurchase.verified_by_user || verifiedPurchase.verified_by;
            const vName = typeof vUser === 'object' && vUser ? (vUser.full_name || vUser.username) : 'Staff Gudang';
            warehouseVerifierName = vName;
            const vSig = typeof vUser === 'object' && vUser && vUser.signature_path ? `{{ asset('storage') }}/${vUser.signature_path}` : null;
            const vDate = verifiedPurchase.verified_at ? String(verifiedPurchase.verified_at).substring(0, 10) : '';

            warehouseVerifierSigHtml = vSig 
                ? `<img src="${vSig}" style="max-height: 50px; max-width: 120px; margin: 0 auto 4px auto; display: block;">` 
                : `<div style="border: 1.5px dashed #059669; padding: 4px; color: #047857; font-size: 10px; font-weight: bold; border-radius: 4px; margin-bottom: 4px; background: #ecfdf5;">✓ FISIK DITERIMA GUDANG<br><small style="font-weight:normal;">${vDate}</small></div>`;
        }

        let paymentsHtml = '';
        if (plan.payments && plan.payments.length > 0) {
            paymentsHtml = `
                <div style="margin-top: 14px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; background: #f8fafc;">
                    <div style="font-weight: bold; font-size: 11px; margin-bottom: 6px; color: #1e3a8a;">RIWAYAT PEMBAYARAN TERMIN / DP:</div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 10.5px;">
                        <thead>
                            <tr style="background: #e2e8f0;">
                                <th style="border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left;">Tahap</th>
                                <th style="border: 1px solid #cbd5e1; padding: 4px 6px; text-align: center;">Tanggal</th>
                                <th style="border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left;">Metode / Rekening</th>
                                <th style="border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left;">No. Ref</th>
                                <th style="border: 1px solid #cbd5e1; padding: 4px 6px; text-align: right;">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${plan.payments.map(p => `
                                <tr>
                                    <td style="border: 1px solid #cbd5e1; padding: 4px 6px; font-weight: bold;">${p.payment_step_label || p.payment_step}</td>
                                    <td style="border: 1px solid #cbd5e1; padding: 4px 6px; text-align: center;">${(p.paid_at || '').substring(0, 10)}</td>
                                    <td style="border: 1px solid #cbd5e1; padding: 4px 6px;">${p.account ? (p.account.kode_akun + ' - ' + p.account.nama_akun) : (p.payment_method || '-')}</td>
                                    <td style="border: 1px solid #cbd5e1; padding: 4px 6px; font-family: monospace;">${p.payment_reference || '-'}</td>
                                    <td style="border: 1px solid #cbd5e1; padding: 4px 6px; text-align: right; font-family: monospace; font-weight: bold;">Rp ${Number(p.amount || 0).toLocaleString('id-ID')}</td>
                                </tr>
                            `).join('')}
                            <tr style="background: #f1f5f9; font-weight: bold;">
                                <td colspan="4" style="border: 1px solid #cbd5e1; padding: 4px 6px; text-align: right;">Total Terbayar:</td>
                                <td style="border: 1px solid #cbd5e1; padding: 4px 6px; text-align: right; font-family: monospace; color: #059669;">Rp ${Number(plan.paid_amount || 0).toLocaleString('id-ID')}</td>
                            </tr>
                            ${Number(plan.remaining_amount || 0) > 0 ? `
                            <tr style="background: #fffbeb; font-weight: bold;">
                                <td colspan="4" style="border: 1px solid #cbd5e1; padding: 4px 6px; text-align: right; color: #b45309;">Sisa Tagihan:</td>
                                <td style="border: 1px solid #cbd5e1; padding: 4px 6px; text-align: right; font-family: monospace; color: #b45309;">Rp ${Number(plan.remaining_amount || 0).toLocaleString('id-ID')}</td>
                            </tr>` : ''}
                        </tbody>
                    </table>
                </div>
            `;
        }

        printWindow.document.write(`
            <html>
            <head>
                <title>Purchase Plan & SPK - ${plan.plan_number}</title>
                <style>
                    body { font-family: 'Helvetica Neue', Arial, sans-serif; padding: 35px; color: #1e293b; font-size: 12px; }
                    .header { display: flex; justify-content: space-between; border-bottom: 2px solid #1e3a8a; padding-bottom: 12px; margin-bottom: 18px; align-items: center; }
                    .brand-container { display: flex; align-items: center; gap: 12px; }
                    .brand-logo { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
                    .brand { font-size: 20px; font-weight: bold; color: #1e3a8a; }
                    .title { font-size: 16px; font-weight: bold; text-align: right; color: #0f172a; }
                    .stamp-approved { display: inline-block; padding: 3px 10px; border: 2px solid #059669; color: #059669; font-weight: 800; border-radius: 6px; font-size: 11px; text-transform: uppercase; margin-bottom: 6px; }
                    .stamp-waiting { display: inline-block; padding: 3px 10px; border: 2px solid #d97706; color: #d97706; font-weight: 800; border-radius: 6px; font-size: 11px; text-transform: uppercase; margin-bottom: 6px; }
                    .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; }
                    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 11.5px; }
                    .items-table th { background: #f1f5f9; padding: 8px; border: 1px solid #cbd5e1; text-align: left; font-size: 11px; }
                    .totals-table { width: 100%; margin-top: 10px; border-collapse: collapse; }
                    .totals-table td { padding: 5px 8px; text-align: right; }
                    .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 30px; text-align: center; }
                    .sig-card { border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; background: #fafafa; }
                    .sig-title { font-size: 11px; font-weight: bold; color: #475569; margin-bottom: 8px; }
                    .sig-name { font-size: 11.5px; font-weight: bold; color: #0f172a; border-top: 1px solid #cbd5e1; padding-top: 5px; margin-top: 5px; }
                    .footer { margin-top: 30px; text-align: center; border-top: 1px solid #cbd5e1; padding-top: 12px; font-size: 10.5px; color: #64748b; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="brand-container">
                        <img src="${logoUrl}" alt="Snaprint" class="brand-logo">
                        <div>
                            <div class="brand">Snaprint</div>
                            <div style="font-size: 11px; color: #64748b;">Digital Printing & Procurement Management</div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Cabang: <strong>${plan.branch ? plan.branch.nama_cabang : 'Pusat'}</strong></div>
                        </div>
                    </div>
                    <div class="title">
                        <div>${isApproved ? '<span class="stamp-approved">✓ PO DISETUJUI OWNER</span>' : '<span class="stamp-waiting">⏳ MENUNGGU ACC OWNER</span>'}</div>
                        <div>SURAT PERINTAH KERJA & PENGADAAN (Surat Pengadaan Bahan)</div>
                        <div style="font-size: 11.5px; font-weight: normal; color: #64748b; font-family: monospace;">No: ${plan.plan_number}</div>
                    </div>
                </div>

                <div class="info-box">
                    <table style="width: 100%; font-size: 11.5px;">
                        <tr>
                            <td style="width: 50%;"><strong>Judul Pengadaan:</strong> ${plan.title || '-'}</td>
                            <td><strong>Target Realisasi:</strong> ${plan.target_date || 'Segera'}</td>
                        </tr>
                        <tr>
                            <td style="padding-top: 4px;"><strong>Diajukan Oleh:</strong> ${creatorName} (${creatorRole})</td>
                            <td style="padding-top: 4px;"><strong>Status Tagihan:</strong> ${isPaid ? '<strong style="color: #059669;">LUNAS (PAID)</strong>' : '<strong style="color: #d97706;">BELUM DIBAYAR</strong>'}</td>
                        </tr>
                        ${plan.notes ? `
                        <tr>
                            <td colspan="2" style="padding-top: 6px; color: #475569;"><strong>Catatan Kebutuhan:</strong> ${plan.notes}</td>
                        </tr>` : ''}
                    </table>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 30px; text-align: center;">No</th>
                            <th>Nama Produk / Bahan Baku Cetak</th>
                            <th>Supplier / Vendor</th>
                            <th style="width: 70px; text-align: center;">Qty</th>
                            <th style="width: 130px; text-align: right;">Estimasi Harga Satuan</th>
                            <th style="width: 140px; text-align: right;">Subtotal Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                </table>

                <table class="totals-table">
                    <tr>
                        <td colspan="4" style="font-weight: bold; font-size: 13px;">Total Estimasi Biaya Pengadaan:</td>
                        <td style="font-weight: bold; font-family: monospace; font-size: 14px; color: #1e3a8a; width: 150px;">Rp ${Number(plan.total_estimated_cost || 0).toLocaleString('id-ID')}</td>
                    </tr>
                </table>

                ${paymentsHtml}

                <!-- 3 Signatures: Creator / Manager, ACC Owner, and Penerima Gudang -->
                <div class="signatures">
                    <div class="sig-card">
                        <div class="sig-title">Diajukan Oleh (Pembuat / Manager):</div>
                        <div style="min-height: 50px; display: flex; align-items: center; justify-content: center;">
                            ${creatorSigHtml}
                        </div>
                        <div class="sig-name">${creatorName}</div>
                        <div style="font-size: 10px; color: #64748b;">${creatorRole}</div>
                    </div>

                    <div class="sig-card">
                        <div class="sig-title">Disetujui Oleh (Owner):</div>
                        <div style="min-height: 50px; display: flex; align-items: center; justify-content: center;">
                            ${approverSigHtml}
                        </div>
                        <div class="sig-name">${isApproved ? (plan.approved_by_user ? (plan.approved_by_user.full_name || plan.approved_by_user.username) : 'SWANTO / KINGAshabil') : '(...........................)'}</div>
                        <div style="font-size: 10px; color: #64748b;">Owner Snaprint</div>
                    </div>

                    <div class="sig-card">
                        <div class="sig-title">Penerimaan Fisik (Gudang):</div>
                        <div style="min-height: 50px; display: flex; align-items: center; justify-content: center;">
                            ${warehouseVerifierSigHtml}
                        </div>
                        <div class="sig-name">${warehouseVerifierName}</div>
                        <div style="font-size: 10px; color: #64748b;">${warehouseRole}</div>
                    </div>
                </div>

                <div class="footer">
                    Terima kasih atas kerja sama Anda.<br>
                    <strong>Kunjungi halaman kami: mysnaprint.com</strong> &bull; Snaprint "great spot to print"
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
