@extends('layouts.app')

@section('title', 'Purchase Plans & Bundle RFQ')
@section('page-title', 'Rencana Pengadaan & RFQ Bundle (Purchase Plans)')

@section('action-buttons')
<a href="{{ route('purchasing.plans.create') }}" class="btn-odoo-primary text-decoration-none">
    <i class="fa-solid fa-plus me-1"></i>
    <span>Buat Purchase Plan Baru</span>
</a>
<a href="{{ route('purchasing.index') }}" class="btn-odoo-secondary text-decoration-none">
    <i class="fa-solid fa-list me-1"></i>
    <span>Daftar RFQ Satuan</span>
</a>
@endsection

@section('content')
<div x-data="{ 
    detailOpen: false,
    rejectOpen: false,
    selectedPlan: null,
    rejectPlanId: null,
    openPlanDetail(plan) {
        this.selectedPlan = plan;
        this.detailOpen = true;
    },
    openRejectModal(planId) {
        this.rejectPlanId = planId;
        this.rejectOpen = true;
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
                <div class="o_stat_text">Menunggu ACC Owner (RFQ)</div>
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
                            <th class="sortable">No. Rencana / RFQ</th>
                            <th class="sortable">Judul Pengadaan</th>
                            <th class="sortable">Cabang & Pembuat</th>
                            <th class="sortable text-center">Bundle Item</th>
                            <th class="sortable text-end">Total Estimasi Biaya</th>
                            <th class="sortable text-center">Status Persetujuan</th>
                            <th class="text-center no-sort" style="width: 140px;">Aksi</th>
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
                                        <span class="badge bg-emerald-50 text-emerald-800 border border-emerald-300 text-[11px] font-semibold">
                                            <i class="fa-solid fa-circle-check me-1"></i> Disetujui (PO Terbit)
                                        </span>
                                        @if($plan->approvedBy)
                                            <div class="text-[10px] text-emerald-700 font-semibold mt-0.5">
                                                ✓ Oleh: {{ $plan->approvedBy->username }}
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
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-sm btn-light border py-0 px-2 text-indigo-700" title="Buka Rincian Bundle"
                                                onclick="showPlanById({{ $plan->id }})">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </button>

                                        @if(auth()->user()->isOwner() && $plan->status === 'waiting_owner_approval')
                                            <form action="{{ route('purchasing.plans.approve', $plan->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Setujui Purchase Plan #{{ $plan->plan_number }}? Seluruh item bundle akan diterbitkan menjadi PO dan dikirim ke bagian pemeriksaan gudang.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2" title="Setujui (ACC) Rencana Pengadaan">
                                                    <i class="fa-solid fa-check text-xs"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" title="Tolak Rencana Pengadaan"
                                                    @click="openRejectModal({{ $plan->id }})">
                                                <i class="fa-solid fa-xmark text-xs"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
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

    <!-- Modal Rincian Bundle Purchase Plan (Odoo Detail Sheet) -->
    <div x-show="detailOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" 
         style="display: none; position: fixed; inset: 0; z-index: 999999 !important;" 
         x-cloak>
        <div class="bg-white rounded-xl shadow-2xl border w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-fade-in"
             @click.away="detailOpen = false">
            
            <!-- Modal Header -->
            <div class="bg-slate-900 text-white px-4 py-3 d-flex justify-content-between align-items-center flex-shrink-0">
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ asset('images/logosnaprint.jpeg') }}" alt="SnapPrint" class="rounded-circle" style="width: 28px; height: 28px; object-fit: cover;">
                    <div>
                        <h6 class="fw-bold mb-0 text-white font-mono" x-text="'PURCHASE PLAN: ' + (selectedPlan ? selectedPlan.plan_number : '')"></h6>
                        <span class="text-[11px] text-slate-300">Rincian Bundle Pengadaan & Permohonan RFQ</span>
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

            <!-- Modal Footer with Owner Actions -->
            <div class="bg-slate-50 px-4 py-3 border-top d-flex justify-content-between align-items-center flex-shrink-0">
                <button type="button" @click="detailOpen = false" class="btn-odoo-secondary">Tutup</button>
                
                <div class="d-flex gap-2" x-if="selectedPlan">
                    @if(auth()->user()->isOwner())
                        <template x-if="selectedPlan.status === 'waiting_owner_approval'">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-danger font-semibold px-3"
                                        @click="openRejectModal(selectedPlan.id); detailOpen = false;">
                                    <i class="fa-solid fa-ban me-1"></i> Tolak RFQ
                                </button>
                                <form :action="'/purchasing/plans/' + selectedPlan.id + '/approve'" method="POST" onsubmit="return confirm('Setujui Purchase Plan ini? PO akan otomatis diterbitkan dan masuk ke alur pemeriksaan gudang.');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success font-semibold px-3">
                                        <i class="fa-solid fa-check me-1"></i> Setujui & Terbitkan PO (ACC)
                                    </button>
                                </form>
                            </div>
                        </template>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tolak RFQ Owner -->
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

</div>

<script>
    window.allPlansData = @json($plans);

    function showPlanById(id) {
        const found = window.allPlansData.find(function(p) { return p.id == id; });
        if (found) {
            const root = document.getElementById('main-view-wrapper');
            if (root && window.Alpine) {
                const data = Alpine.$data(root);
                if (data) {
                    data.openPlanDetail(found);
                }
            }
        }
    }
</script>
@endsection
