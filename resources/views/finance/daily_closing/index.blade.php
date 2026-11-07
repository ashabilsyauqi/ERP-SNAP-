@extends('layouts.app')

@section('title', 'Laporan Tutup Hari (Daily Closing)')
@section('page-title', 'Laporan Tutup Hari & Peti Cash Cabang')

@section('action-buttons')
<a href="{{ route('pos.index') }}" class="btn-odoo-secondary text-decoration-none">
    <i class="fa-solid fa-cash-register me-1 text-slate-600"></i> Ke Terminal Kasir (POS)
</a>
<!-- Tombol Ambil Semua Cash (Di Luar Lembar Kerja / List Tanggal) -->
<button type="button" class="btn text-white text-decoration-none font-black text-xs px-3 py-1.5 rounded-xl shadow-sm d-inline-flex align-items-center gap-1.5" style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); border: none;" onclick="requestBossPinForSweepAll()" title="Tarik seluruh sisa uang kas fisik di brankas/laci toko">
    <i class="fa-solid fa-sack-dollar text-white"></i>
    <span>⚡ Ambil Semua Cash</span>
</button>
<button type="button" class="btn text-white text-decoration-none font-bold text-xs px-3 py-1.5 rounded-xl shadow-sm d-inline-flex align-items-center gap-1.5" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%); border: none;" onclick="requestBossPinForGeneral()">
    <i class="fa-solid fa-hand-holding-dollar"></i>
    <span>- Diambil Boss</span>
</button>
<button type="button" class="btn btn-outline-secondary text-xs px-3 py-1.5 rounded-xl font-bold d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalHistoryBossWithdrawal">
    <i class="fa-solid fa-clock-rotate-left text-slate-600"></i>
    <span>Riwayat Tarik Kas</span>
</button>
<a href="{{ route('daily-closing.create') }}" class="btn-odoo-primary text-decoration-none">
    <i class="fa-solid fa-plus me-1"></i>
    <span>Tutup Kasir Baru</span>
</a>
@endsection

@section('content')
<div class="space-y-4">

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show text-xs font-semibold rounded-2xl shadow-sm d-flex align-items-center gap-2 mb-0" role="alert">
            <i class="fa-solid fa-circle-check fs-5 text-emerald-600"></i>
            <div class="flex-fill">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show text-xs font-semibold rounded-2xl shadow-sm d-flex align-items-center gap-2 mb-0" role="alert">
            <i class="fa-solid fa-circle-exclamation fs-5 text-rose-600"></i>
            <div class="flex-fill">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- 4 KPI Summary Cards with Peti Cash Accumulation -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <!-- 1. Saldo Peti Cash (Live) -->
        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm" style="border-left: 5px solid #059669 !important;">
            <div class="d-flex justify-content-between align-items-start">
                <span class="text-[10px] font-black text-emerald-700 uppercase tracking-wider block">SISA PETI CASH (LACI KASIR)</span>
                <span class="badge bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-1.5 py-0.5 rounded-lg">LIVE</span>
            </div>
            <div class="fs-4 font-black text-emerald-900 font-mono mt-1">
                Rp {{ number_format($totalLivePettyCash ?? 0, 0, ',', '.') }}
            </div>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <div class="text-[11px] text-slate-500 d-flex align-items-center gap-1">
                    <i class="fa-solid fa-vault text-emerald-600"></i>
                    <span>Fisik kas tersisa di brankas toko</span>
                </div>
                <button type="button" onclick="requestBossPinForSweepAll()" class="btn btn-xs btn-outline-danger py-0.5 px-2 rounded-lg font-bold text-[10px] d-inline-flex align-items-center gap-1 shadow-2xs" title="Tarik seluruh uang kas fisik yang ada di brankas">
                    <i class="fa-solid fa-bolt"></i>
                    <span>Ambil Semua</span>
                </button>
            </div>
        </div>

        <!-- 2. Masuk Peti Bulan Ini -->
        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm" style="border-left: 5px solid #2563eb !important;">
            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">MASUK PETI (OKTOBER 2026)</span>
            <div class="fs-5 font-black text-blue-900 font-mono mt-1">
                + Rp {{ number_format($monthCashIn ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1 d-flex align-items-center gap-1">
                <i class="fa-solid fa-cash-register text-blue-600"></i>
                <span>Akumulasi fisik dari closing harian</span>
            </div>
        </div>

        <!-- 3. Diambil Boss Bulan Ini -->
        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm" style="border-left: 5px solid #d97706 !important;">
            <div class="d-flex justify-content-between align-items-start">
                <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">DIAMBIL BOSS (OKTOBER 2026)</span>
                <button type="button" class="badge bg-amber-100 text-amber-800 border-0 text-[10px] font-bold px-1.5 py-0.5 rounded cursor-pointer" data-bs-toggle="modal" data-bs-target="#modalHistoryBossWithdrawal">
                    Lihat Log
                </button>
            </div>
            <div class="fs-5 font-black text-amber-900 font-mono mt-1">
                - Rp {{ number_format($monthWithdrawn ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1 d-flex align-items-center gap-1">
                <i class="fa-solid fa-hand-holding-dollar text-amber-600"></i>
                <span>Setoran bank / penarikan kas owner</span>
            </div>
        </div>

        <!-- 4. Total Laporan & Status ACC -->
        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm" style="border-left: 5px solid #6366f1 !important;">
            <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider block">STATUS VERIFIKASI</span>
            <div class="fs-5 font-black text-indigo-900 font-mono mt-1">
                {{ $reports->total() }} Laporan
            </div>
            <div class="text-[11px] mt-1 d-flex align-items-center gap-1 font-semibold {{ $pendingCount > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                <i class="fa-solid {{ $pendingCount > 0 ? 'fa-hourglass-half' : 'fa-check-circle' }}"></i>
                <span>{{ $pendingCount }} Menunggu ACC Tanda Tangan</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('daily-closing.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-2.5 items-end">
            @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin() || (isset($canChange) && $canChange))
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Cabang</label>
                    <select name="branch_id" class="form-select form-select-sm text-xs">
                        <option value="all" {{ ($branchId ?? 'all') === 'all' ? 'selected' : '' }}>Semua Cabang</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ ($branchId ?? '') == $b->id ? 'selected' : '' }}>
                                {{ $b->nama_cabang }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm text-xs" value="{{ request('date_from') }}">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm text-xs" value="{{ request('date_to') }}">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Status Verifikasi</label>
                <select name="status" class="form-select form-select-sm text-xs">
                    <option value="all">Semua Status</option>
                    <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>⏳ Menunggu ACC Owner</option>
                    <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>✅ Terverifikasi (ACC)</option>
                </select>
            </div>

            <div class="d-flex gap-1.5">
                <button type="submit" class="btn btn-sm btn-primary flex-fill rounded-xl text-xs font-bold py-1.5" style="background-color: #1e40af; border-color: #1e40af;">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                <a href="{{ route('daily-closing.index') }}" class="btn btn-sm btn-outline-secondary rounded-xl text-xs py-1.5">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table Card with Running Petty Cash Accumulation -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-3 bg-slate-50 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 font-extrabold text-slate-800 text-xs text-uppercase tracking-wider">
                    <i class="fa-solid fa-list-check me-1.5 text-blue-800"></i> Riwayat Laporan Closing & Rekonsiliasi Peti Cash
                </h6>
                <p class="mb-0 text-[11px] text-slate-500 mt-0.5">Riwayat kas fisik closing harian dan penarikan kas Boss. Akumulasi saldo peti kas terpantau pada kartu ringkasan di atas.</p>
            </div>
            <span class="badge bg-white text-slate-700 border border-slate-200 text-xs px-2.5 py-1 font-mono font-bold">
                Total: {{ $reports->total() }} Data
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-xs">
                <thead class="bg-slate-100 text-slate-700 border-bottom font-bold">
                    <tr>
                        <th class="ps-3 py-2.5">No Laporan</th>
                        <th class="py-2.5">Tanggal Tutup</th>
                        <th class="py-2.5">Cabang</th>
                        <th class="py-2.5">Dibuat Oleh</th>
                        <th class="py-2.5 text-end">Total Omset POS</th>
                        <th class="py-2.5 text-end text-emerald-800">Kas Fisik Masuk</th>
                        <th class="py-2.5 text-center text-amber-800">Diambil Boss</th>
                        <th class="py-2.5 text-end text-emerald-900 bg-emerald-50/50">Aktual Cash Harian</th>
                        <th class="py-2.5 text-center">Status</th>
                        <th class="pe-3 py-2.5 text-center" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $r)
                        @php
                            $rowActualCash = (float)$r->actual_cash;
                            $rowWithdrawnToday = (float)($r->total_withdrawn_today ?? 0);
                            $rowDailyRemaining = max(0, $rowActualCash - $rowWithdrawnToday);
                            $rowPettyCash = (float)($r->running_petty_cash ?? $r->actual_cash);
                            $rowBranchName = addslashes($r->branch->nama_cabang ?? 'Cabang');
                        @endphp
                        <tr>
                            <td class="ps-3 font-mono font-bold text-blue-900">
                                <a href="{{ route('daily-closing.show', $r->id) }}" class="text-decoration-none">
                                    {{ $r->report_number }}
                                </a>
                            </td>
                            <td class="font-semibold text-slate-700">
                                <div class="font-bold text-slate-800">{{ $r->closing_date->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $r->closing_date->format('l') }}</div>
                            </td>
                            <td>
                                <span class="badge bg-blue-50 text-blue-800 border border-blue-200 text-[10px] font-bold">
                                    {{ $r->branch->nama_cabang ?? 'Pusat' }}
                                </span>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-800">{{ $r->manager->full_name ?? $r->manager->username ?? 'Kasir Toko' }}</div>
                                <div class="text-[10px] text-slate-400">Kasir / Manager</div>
                            </td>
                            <td class="text-end font-mono font-bold text-slate-900">
                                Rp {{ number_format($r->total_sales, 0, ',', '.') }}
                                @if(!empty($r->total_waste_hpp) && $r->total_waste_hpp > 0)
                                    <div class="text-[9px] text-amber-700 font-bold mt-0.5" title="Kerugian bahan terbuang (waste trial error)">
                                        <i class="fa-solid fa-recycle text-[8px] me-0.5"></i> Waste: Rp {{ number_format($r->total_waste_hpp, 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-end font-mono font-bold text-emerald-700">
                                + Rp {{ number_format($r->actual_cash, 0, ',', '.') }}
                            </td>
                            <td class="text-center font-mono">
                                @if(isset($r->total_withdrawn_today) && $r->total_withdrawn_today > 0)
                                    <span class="badge bg-amber-100 text-amber-900 border border-amber-300 font-mono font-bold text-[11px] px-2 py-0.5 cursor-pointer shadow-xs" 
                                          onclick="requestBossPinWithData({
                                              branchId: '{{ $r->branch_id }}',
                                              branchName: '{{ $rowBranchName }}',
                                              dateStr: '{{ $r->closing_date->format('Y-m-d') }}',
                                              dateFormatted: '{{ $r->closing_date->format('d M Y') }}',
                                              reportId: '{{ $r->id }}',
                                              reportNumber: '{{ $r->report_number }}',
                                              actualCash: {{ $rowActualCash }},
                                              pettyCash: {{ $rowPettyCash }}
                                          })" 
                                          title="{{ $r->day_withdrawals[0]->notes ?? 'Klik untuk lihat/tambah penarikan' }}">
                                        <i class="fa-solid fa-arrow-down me-0.5 text-rose-600"></i> - Rp {{ number_format($r->total_withdrawn_today, 0, ',', '.') }}
                                    </span>
                                    <div class="text-[9px] text-amber-800 mt-0.5 font-sans font-semibold">
                                        {{ $r->day_withdrawals[0]->taken_by ?? 'Boss' }}
                                    </div>
                                @else
                                    <button type="button" class="btn btn-xs btn-outline-dashed text-slate-400 hover:text-amber-700 py-0 px-1.5 rounded" 
                                            title="Catat kas diambil boss tanggal ini (Otomatis bawa data hari ini)" 
                                            onclick="requestBossPinWithData({
                                                branchId: '{{ $r->branch_id }}',
                                                branchName: '{{ $rowBranchName }}',
                                                dateStr: '{{ $r->closing_date->format('Y-m-d') }}',
                                                dateFormatted: '{{ $r->closing_date->format('d M Y') }}',
                                                reportId: '{{ $r->id }}',
                                                reportNumber: '{{ $r->report_number }}',
                                                actualCash: {{ $rowActualCash }},
                                                pettyCash: {{ $rowPettyCash }}
                                            })">
                                        <i class="fa-solid fa-plus text-[10px]"></i>
                                    </button>
                                @endif
                            </td>
                            <td class="text-end font-mono font-black {{ $rowDailyRemaining > 0 ? 'text-emerald-900 bg-emerald-50/40' : 'text-slate-400 bg-slate-50/20' }}">
                                <div class="fs-6 {{ $rowDailyRemaining > 0 ? 'text-emerald-900' : 'text-slate-500' }}">
                                    Rp {{ number_format($rowDailyRemaining, 0, ',', '.') }}
                                </div>
                                <div class="text-[9px] font-sans font-normal {{ $rowDailyRemaining > 0 ? 'text-emerald-700' : 'text-slate-400' }}">
                                    @if($rowWithdrawnToday > 0)
                                        @if($rowDailyRemaining == 0)
                                            <i class="fa-solid fa-check text-slate-400 me-0.5"></i> Diambil Boss (Nihil)
                                        @else
                                            <i class="fa-solid fa-coins text-emerald-600 me-0.5"></i> Sisa aktual harian
                                        @endif
                                    @else
                                        <i class="fa-solid fa-cash-register text-emerald-600 me-0.5"></i> Aktual cash harian
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @if($r->isVerified())
                                    <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] px-2 py-0.5 font-bold">
                                        <i class="fa-solid fa-check-double me-1"></i> ACC Owner
                                    </span>
                                @else
                                    <span class="badge bg-amber-100 text-amber-800 border border-amber-300 text-[10px] px-2 py-0.5 font-bold animate-pulse">
                                        <i class="fa-solid fa-clock me-1"></i> Menunggu ACC
                                    </span>
                                @endif
                            </td>
                            <td class="pe-3 text-center">
                                <div class="btn-group btn-group-sm">
                                    <!-- Aksi Langsung di Baris Tabel: Catat Diambil Boss Otomatis Membawa Data Hari Itu -->
                                    <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2 text-amber-700" 
                                            title="Ambil Kas Hari Ini: Otomatis Membawa Rp {{ number_format($r->actual_cash) }}" 
                                            onclick="requestBossPinWithData({
                                                branchId: '{{ $r->branch_id }}',
                                                branchName: '{{ $rowBranchName }}',
                                                dateStr: '{{ $r->closing_date->format('Y-m-d') }}',
                                                dateFormatted: '{{ $r->closing_date->format('d M Y') }}',
                                                reportId: '{{ $r->id }}',
                                                reportNumber: '{{ $r->report_number }}',
                                                actualCash: {{ $rowActualCash }},
                                                pettyCash: {{ $rowPettyCash }}
                                            })">
                                        <i class="fa-solid fa-hand-holding-dollar"></i>
                                    </button>
                                    <a href="{{ route('daily-closing.show', $r->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="Buka Detail Rekonsiliasi & Denominasi">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </a>
                                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isOwner())
                                        <form action="{{ route('daily-closing.destroy', $r->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus laporan tutup hari ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus Laporan">
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
                                <i class="fa-solid fa-clipboard-check fs-1 text-slate-300 mb-2"></i>
                                <p class="mb-0 font-semibold">Belum ada data laporan tutup hari.</p>
                                <a href="{{ route('daily-closing.create') }}" class="btn btn-sm btn-odoo-primary mt-2 text-decoration-none">
                                    <i class="fa-solid fa-plus me-1"></i> Buat Laporan Hari Ini
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reports->hasPages())
            <div class="p-3 bg-slate-50 border-top">
                {{ $reports->withQueryString()->links() }}
            </div>
        @endif
    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL: POP-UP VERIFIKASI PIN OTORISASI BOSS (MUNCUL SEBELUM CATAT KAS) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalBossPinPrompt" tabindex="-1" aria-labelledby="modalBossPinPromptLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 360px;">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="modal-header border-0 pb-0 justify-content-between p-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/20 border border-amber-400/40 d-flex align-items-center justify-content-center text-amber-400 text-sm">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h6 class="modal-title font-extrabold text-sm text-white mb-0" id="modalBossPinPromptLabel">
                        Otorisasi PIN Boss
                    </h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formVerifyBossPin" onsubmit="return handleVerifyBossPin(event)">
                <div class="modal-body p-4 text-center">
                    <p class="text-xs text-slate-600 mb-3 font-semibold">
                        Masukkan PIN otorisasi rahasia untuk membuka pencatatan penarikan kas diambil Boss.
                    </p>

                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-slate-100 border-2 border-end-0 border-slate-300 rounded-start-2xl text-slate-500 px-3">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input type="password" id="inputPromptBossPin" maxlength="6" inputmode="numeric" 
                                   class="form-control text-center font-mono font-black text-2xl tracking-widest border-2 border-slate-300 rounded-end-2xl py-2.5 focus:border-amber-500" 
                                   placeholder="••••" autocomplete="off" required>
                        </div>
                        <div id="promptBossPinError" class="text-[11.5px] text-rose-600 font-bold mt-2 d-none align-items-center justify-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>PIN Otorisasi Salah! Akses ditolak.</span>
                        </div>
                    </div>
                    
                    <div class="text-[11px] text-slate-400">
                        <i class="fa-solid fa-shield-check me-1 text-slate-400"></i> Proteksi penarikan kas brankas & laci toko
                    </div>
                </div>

                <div class="modal-footer bg-slate-50 p-3 d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl text-xs px-3 font-semibold flex-1" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-sm text-white rounded-xl text-xs px-4 py-2.5 font-bold shadow-sm flex-1" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%); border: none;">
                        <i class="fa-solid fa-key me-1"></i> Lanjutkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: CATAT PENGAMBILAN KAS OLEH BOSS (DIAMBIL BOSS) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalBossWithdrawal" tabindex="-1" aria-labelledby="modalBossWithdrawalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="modal-header text-white p-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-400/40 d-flex align-items-center justify-content-center text-amber-400 fs-5">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-extrabold text-sm mb-0 tracking-wide text-white" id="modalBossWithdrawalLabel">
                            Catat Kas Diambil Boss
                        </h5>
                        <p class="text-[11px] text-slate-300 mb-0">Tarik uang kas fisik dari peti cash / laci kasir</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('daily-closing.boss-withdrawal.store') }}" method="POST" id="formBossWithdrawal">
                @csrf
                <input type="hidden" name="daily_closing_report_id" id="modalWithdrawalReportId" value="">

                <div class="modal-body p-4 space-y-3">
                    
                    <!-- Banner Informasi Laporan Hari Tersebut (Otomatis Terisi) -->
                    <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="font-bold text-amber-950 d-flex align-items-center gap-1.5">
                                <i class="fa-solid fa-file-invoice-dollar text-amber-600"></i>
                                <span id="modalReportBadge">Laporan Harian</span>
                            </span>
                            <span class="badge bg-amber-200 text-amber-900 font-mono font-bold text-[10px]" id="modalDateBadge">{{ date('d M Y') }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-amber-200/70 font-mono text-[11px]">
                            <div>Kas Fisik Hari Ini: <strong class="text-emerald-800" id="modalActualCashText">Rp 0</strong></div>
                            <div>Saldo Peti Toko: <strong class="text-blue-800" id="modalPettyCashText">Rp 0</strong></div>
                        </div>
                    </div>

                    <!-- Opsi Penarikan Kas (Ambil Semua Cash / Hari Ini / Bebas) -->
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                        <label class="block text-[10.5px] font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                            <i class="fa-solid fa-sliders text-amber-600 me-1"></i> Opsi Penarikan Kas:
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <!-- Opsi 1: Ambil Semua Cash -->
                            <button type="button" class="btn btn-sm btn-outline-danger text-start p-2 rounded-xl border-2 transition d-flex flex-column justify-between active" id="btnOptSweepAll" onclick="selectWithdrawalOption('sweep_all')">
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wide">Ambil Semua</span>
                                    <i class="fa-solid fa-circle-check text-rose-600 text-xs" id="iconOptSweepAll"></i>
                                </div>
                                <div class="font-mono font-black text-xs text-rose-950 mt-1" id="labelOptSweepAll">
                                    Rp {{ number_format($totalLivePettyCash ?? 0, 0, ',', '.') }}
                                </div>
                                <span class="text-[9px] text-rose-700/80">Kosongkan peti toko</span>
                            </button>

                            <!-- Opsi 2: Kas Closing Hari Ini -->
                            <button type="button" class="btn btn-sm btn-outline-success text-start p-2 rounded-xl border-2 transition d-flex flex-column justify-between" id="btnOptToday" onclick="selectWithdrawalOption('today')">
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wide">Kas Hari Ini</span>
                                    <i class="fa-regular fa-circle text-slate-400 text-xs" id="iconOptToday"></i>
                                </div>
                                <div class="font-mono font-black text-xs text-emerald-950 mt-1" id="labelOptToday">
                                    Rp 0
                                </div>
                                <span class="text-[9px] text-emerald-700/80">Sesuai fisik closing</span>
                            </button>

                            <!-- Opsi 3: Nominal Kustom -->
                            <button type="button" class="btn btn-sm btn-outline-primary text-start p-2 rounded-xl border-2 transition d-flex flex-column justify-between" id="btnOptCustom" onclick="selectWithdrawalOption('custom')">
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wide">Nominal Bebas</span>
                                    <i class="fa-regular fa-circle text-slate-400 text-xs" id="iconOptCustom"></i>
                                </div>
                                <div class="font-mono font-black text-xs text-blue-950 mt-1">
                                    Ketik Manual
                                </div>
                                <span class="text-[9px] text-blue-700/80">Sebagian kas</span>
                            </button>
                        </div>
                    </div>

                    <!-- Nominal Uang Diambil (Sudah Otomatis Terisi) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Nominal Uang yang Diambil (Rp)
                            <span class="text-[10px] text-emerald-700 font-normal ms-1 font-sans">*(Sudah otomatis terisi nominal hari ini)*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-slate-100 font-mono font-bold text-xs">Rp</span>
                            <input type="number" id="inputWithdrawalAmount" name="amount" min="1" step="any" class="form-control text-sm font-mono font-bold rounded-end-xl text-emerald-900" placeholder="0" required oninput="previewAmountRupiah(this.value)">
                        </div>
                        <div id="textAmountPreview" class="text-[11px] text-emerald-700 font-mono font-bold mt-1"></div>
                    </div>

                    <!-- Cabang & Tanggal (Terkunci Otomatis Sesuai Baris) -->
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Cabang</label>
                            @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin() || (isset($canChange) && $canChange))
                                <select name="branch_id" id="modalBranchSelect" class="form-select form-select-sm text-xs font-semibold rounded-xl" required>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}" {{ ($branchId ?? 1) == $b->id ? 'selected' : '' }}>
                                            {{ $b->nama_cabang }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id ?: 1 }}">
                                <input type="text" class="form-control form-control-sm text-xs font-semibold rounded-xl bg-slate-100" value="{{ auth()->user()->branch->nama_cabang ?? 'Cabang Kasir' }}" readonly>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal</label>
                            <input type="date" name="withdrawal_date" id="modalWithdrawalDate" class="form-control form-control-sm text-xs font-bold rounded-xl" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <!-- Diambil Oleh (Otomatis Sesuai User yang Login) -->
                    @php
                        $curUser = strtoupper(auth()->user()->username ?? '');
                        $defaultBoss = str_contains($curUser, 'KINGASHABIL') ? 'Boss KINGAshabil' : 'Boss SWANTO';
                    @endphp
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Diambil Oleh (Nama Boss)</label>
                        <select name="taken_by" id="modalTakenBy" class="form-select form-select-sm text-xs font-semibold rounded-xl" required>
                            <option value="Boss SWANTO" {{ $defaultBoss === 'Boss SWANTO' ? 'selected' : '' }}>Boss SWANTO</option>
                            <option value="Boss KINGAshabil" {{ $defaultBoss === 'Boss KINGAshabil' ? 'selected' : '' }}>Boss KINGAshabil</option>
                            <option value="Owner Swanto & King Ashabil">Owner Swanto & King Ashabil</option>
                            <option value="Pimpinan / Manajemen Snaprint">Pimpinan / Manajemen Snaprint</option>
                            <option value="Kasir Toko (Setor Bank)">Kasir Toko (Setor Bank Mandiri/BCA)</option>
                        </select>
                    </div>

                    <!-- Keperluan / Tujuan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Keperluan / Tujuan</label>
                        <select name="purpose" class="form-select form-select-sm text-xs font-semibold rounded-xl" required>
                            <option value="Setor Tunai ke Bank BCA">Setor Tunai ke Bank BCA</option>
                            <option value="Setor Tunai ke Bank Mandiri">Setor Tunai ke Bank Mandiri</option>
                            <option value="Kas Operasional Kantor Pusat">Kas Operasional Kantor Pusat</option>
                            <option value="Penarikan Tunai Owner (Prive)">Penarikan Tunai Owner (Prive)</option>
                            <option value="Belanja Modal / Stok Bahan Baku Mendesak">Belanja Modal / Stok Bahan Baku Mendesak</option>
                            <option value="Lain-lain">Lain-lain</option>
                        </select>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="notes" id="modalWithdrawalNotes" class="form-control form-control-sm text-xs rounded-xl" placeholder="Catatan penarikan kas...">
                    </div>

                    <!-- Status PIN Otorisasi (Sudah Lolos Verifikasi Pop-Up PIN) -->
                    <input type="hidden" name="boss_pin" id="inputBossPin" value="" required>
                    <div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-300 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white d-flex align-items-center justify-content-center shadow-xs">
                                <i class="fa-solid fa-shield-check fs-6"></i>
                            </div>
                            <div>
                                <div class="text-xs font-black text-emerald-950">Otorisasi PIN Terverifikasi</div>
                                <div class="text-[11px] text-emerald-700">Akses penarikan kas disetujui melalui verifikasi PIN.</div>
                            </div>
                        </div>
                        <span class="badge bg-emerald-200 text-emerald-900 font-extrabold text-[10px] px-2 py-1 rounded-lg">
                            <i class="fa-solid fa-check me-1"></i> VERIFIED
                        </span>
                    </div>

                </div>

                <div class="modal-footer bg-slate-50 p-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl text-xs px-3 font-semibold" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-sm text-white rounded-xl text-xs px-4 py-2 font-bold shadow-sm" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%); border: none;">
                        <i class="fa-solid fa-check me-1"></i> Konfirmasi Ambil Kas (Simpan)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: RIWAYAT PENARIKAN KAS OLEH BOSS -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalHistoryBossWithdrawal" tabindex="-1" aria-labelledby="modalHistoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="modal-header bg-slate-900 text-white p-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-amber-400 fs-5"></i>
                    <div>
                        <h5 class="modal-title font-extrabold text-sm mb-0 text-white" id="modalHistoryLabel">
                            Riwayat Penarikan Kas ("Diambil Boss")
                        </h5>
                        <p class="text-[11px] text-slate-400 mb-0">Daftar pencatatan uang fisik yang diambil dari Peti Cash</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-xs">
                        <thead class="bg-slate-100 text-slate-700 font-bold">
                            <tr>
                                <th class="ps-3 py-2.5">Tanggal</th>
                                <th class="py-2.5">Cabang</th>
                                <th class="py-2.5">Diambil Oleh</th>
                                <th class="py-2.5 text-end text-rose-800">Nominal Diambil</th>
                                <th class="py-2.5">Keperluan & Catatan</th>
                                <th class="pe-3 py-2.5 text-center" style="width: 80px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentWithdrawals ?? [] as $w)
                                <tr>
                                    <td class="ps-3 font-semibold text-slate-800 font-mono">
                                        {{ $w->withdrawal_date ? $w->withdrawal_date->format('d M Y') : '-' }}
                                    </td>
                                    <td>
                                        <span class="badge bg-slate-100 text-slate-700 border text-[10px]">
                                            {{ $w->branch->nama_cabang ?? 'Cabang' }}
                                        </span>
                                    </td>
                                    <td class="font-bold text-amber-900">
                                        <i class="fa-solid fa-user-tie text-amber-700 me-1"></i> {{ $w->taken_by }}
                                    </td>
                                    <td class="text-end font-mono font-black text-rose-700">
                                        - Rp {{ number_format($w->amount, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <div class="font-bold text-slate-800">{{ $w->purpose ?? 'Pengambilan Kas' }}</div>
                                        @if($w->notes)
                                            <div class="text-[10px] text-slate-500">{{ $w->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="pe-3 text-center">
                                        @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin() || auth()->user()->isManager())
                                            <form action="{{ route('daily-closing.boss-withdrawal.destroy', $w->id) }}" method="POST" onsubmit="return confirm('Hapus/batalkan data penarikan kas ini? Saldo peti cash akan bertambah kembali.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2 text-[10px] rounded-lg" title="Hapus Data">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-slate-400">
                                        Belum ada data penarikan kas oleh Boss.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer bg-slate-50 p-3">
                <button type="button" class="btn btn-sm btn-secondary rounded-xl text-xs px-3 font-semibold" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function formatRupiahString(val) {
    if (!val || isNaN(val)) return 'Rp 0';
    return 'Rp ' + Math.round(val).toLocaleString('id-ID');
}

function previewAmountRupiah(val) {
    const el = document.getElementById('textAmountPreview');
    if (!el) return;
    if (!val || isNaN(val) || val <= 0) {
        el.innerText = '';
        return;
    }
    el.innerText = 'Terbaca: ' + formatRupiahString(val);
}

function setWithdrawalAmount(val) {
    const input = document.getElementById('inputWithdrawalAmount');
    if (!input) return;
    input.value = Math.round(val || 0);
    previewAmountRupiah(input.value);

    // Update active state of buttons
    const btnAct = document.getElementById('btnQuickActualCash');
    const btnPet = document.getElementById('btnQuickPettyCash');
    if (btnAct && btnPet) {
        if (Math.round(val) === Math.round(btnAct.dataset.amount)) {
            btnAct.classList.add('active');
            btnPet.classList.remove('active');
        } else if (Math.round(val) === Math.round(btnPet.dataset.amount)) {
            btnPet.classList.add('active');
            btnAct.classList.remove('active');
        } else {
            btnAct.classList.remove('active');
            btnPet.classList.remove('active');
        }
    }
}

const branchLivePettyCashMap = @json($branchLivePettyCash ?? []);
let globalLivePettyCash = {{ (float)($totalLivePettyCash ?? 0) }};
let currentWithdrawalOption = 'sweep_all'; // 'sweep_all' | 'today' | 'custom'

// State untuk alur verifikasi PIN sebelum buka modal
let pendingWithdrawalMode = null; // 'sweep_all' | 'general' | 'specific'
let pendingWithdrawalData = null;

function requestBossPinForSweepAll() {
    pendingWithdrawalMode = 'sweep_all';
    pendingWithdrawalData = null;
    showBossPinModal();
}

function requestBossPinForGeneral() {
    pendingWithdrawalMode = 'general';
    pendingWithdrawalData = null;
    showBossPinModal();
}

function requestBossPinWithData(data) {
    pendingWithdrawalMode = 'specific';
    pendingWithdrawalData = data;
    showBossPinModal();
}

function showBossPinModal() {
    const pinInput = document.getElementById('inputPromptBossPin');
    const pinError = document.getElementById('promptBossPinError');
    if (pinInput) {
        pinInput.value = '';
        pinInput.classList.remove('is-invalid');
    }
    if (pinError) {
        pinError.classList.add('d-none');
        pinError.classList.remove('d-flex');
    }
    const pinModalEl = document.getElementById('modalBossPinPrompt');
    if (pinModalEl) {
        const pinModal = bootstrap.Modal.getOrCreateInstance(pinModalEl);
        pinModal.show();
        setTimeout(() => {
            if (pinInput) pinInput.focus();
        }, 350);
    }
}

function handleVerifyBossPin(e) {
    if (e && e.preventDefault) e.preventDefault();
    const pinInput = document.getElementById('inputPromptBossPin');
    const pinError = document.getElementById('promptBossPinError');
    const enteredPin = (pinInput ? pinInput.value : '').trim();

    if (enteredPin !== '2209') {
        if (pinInput) {
            pinInput.classList.add('is-invalid');
            pinInput.value = '';
            pinInput.focus();
        }
        if (pinError) {
            pinError.classList.remove('d-none');
            pinError.classList.add('d-flex');
        }
        return false;
    }

    // PIN Valid! Sembunyikan Pop-up PIN
    const pinModalEl = document.getElementById('modalBossPinPrompt');
    if (pinModalEl) {
        const pinModal = bootstrap.Modal.getInstance(pinModalEl);
        if (pinModal) pinModal.hide();
    }

    // Isi hidden input PIN di form penarikan
    const formPinInput = document.getElementById('inputBossPin');
    if (formPinInput) {
        formPinInput.value = enteredPin;
    }

    // Buka Modal Catat Kas Diambil Boss setelah jeda transisi modal
    setTimeout(() => {
        if (pendingWithdrawalMode === 'specific' && pendingWithdrawalData) {
            openBossWithdrawalModal(pendingWithdrawalData);
        } else if (pendingWithdrawalMode === 'sweep_all') {
            openBossWithdrawalSweepAll();
        } else {
            openBossWithdrawalGeneral();
            const modalEl = document.getElementById('modalBossWithdrawal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        }
    }, 280);

    return false;
}

function getLivePettyCashForBranch(bId) {
    if (bId && branchLivePettyCashMap && branchLivePettyCashMap[bId] !== undefined) {
        return branchLivePettyCashMap[bId];
    }
    return globalLivePettyCash;
}

// Buka Modal dengan Mode Ambil Semua Cash (Sweep All)
function openBossWithdrawalSweepAll() {
    const modalEl = document.getElementById('modalBossWithdrawal');
    if (!modalEl) return;

    const reportInput = document.getElementById('modalWithdrawalReportId');
    if (reportInput) reportInput.value = '';

    const reportBadge = document.getElementById('modalReportBadge');
    if (reportBadge) reportBadge.innerText = '⚡ Tarik Semua Kas Peti Toko';

    const branchSelect = document.getElementById('modalBranchSelect');
    let targetBranchId = branchSelect ? branchSelect.value : null;
    let liveCash = getLivePettyCashForBranch(targetBranchId);

    const actualCashText = document.getElementById('modalActualCashText');
    if (actualCashText) actualCashText.innerText = 'Rp 0';
    const pettyCashText = document.getElementById('modalPettyCashText');
    if (pettyCashText) pettyCashText.innerText = formatRupiahString(liveCash);

    const labelToday = document.getElementById('labelOptToday');
    if (labelToday) labelToday.innerText = 'Rp 0';

    window.lastRowActualCash = 0;
    window.lastRowNotes = '';

    selectWithdrawalOption('sweep_all', liveCash);

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function selectWithdrawalOption(opt, customVal) {
    currentWithdrawalOption = opt;
    const btnSweep = document.getElementById('btnOptSweepAll');
    const iconSweep = document.getElementById('iconOptSweepAll');
    const btnToday = document.getElementById('btnOptToday');
    const iconToday = document.getElementById('iconOptToday');
    const btnCustom = document.getElementById('btnOptCustom');
    const iconCustom = document.getElementById('iconOptCustom');

    const amountInput = document.getElementById('inputWithdrawalAmount');
    const notesInput = document.getElementById('modalWithdrawalNotes');
    const branchSelect = document.getElementById('modalBranchSelect');
    const targetBranchId = branchSelect ? branchSelect.value : null;
    const liveCash = getLivePettyCashForBranch(targetBranchId);

    // Reset styles
    [btnSweep, btnToday, btnCustom].forEach(b => {
        if (b) {
            b.classList.remove('active', 'border-rose-600', 'border-emerald-600', 'border-blue-600');
        }
    });
    if (iconSweep) { iconSweep.className = 'fa-regular fa-circle text-slate-400 text-xs'; }
    if (iconToday) { iconToday.className = 'fa-regular fa-circle text-slate-400 text-xs'; }
    if (iconCustom) { iconCustom.className = 'fa-regular fa-circle text-slate-400 text-xs'; }

    // Update label sweep all
    const labelSweep = document.getElementById('labelOptSweepAll');
    if (labelSweep) labelSweep.innerText = formatRupiahString(liveCash);

    if (opt === 'sweep_all') {
        if (btnSweep) {
            btnSweep.classList.add('active', 'border-rose-600');
        }
        if (iconSweep) {
            iconSweep.className = 'fa-solid fa-circle-check text-rose-600 text-xs';
        }
        const val = customVal !== undefined ? customVal : liveCash;
        if (amountInput) {
            amountInput.value = Math.round(val);
            previewAmountRupiah(amountInput.value);
        }
        if (notesInput && (!notesInput.value || notesInput.value.includes('Tarik'))) {
            notesInput.value = 'Tarik seluruh sisa uang kas fisik peti cash / brankas toko';
        }
    } else if (opt === 'today') {
        if (btnToday) {
            btnToday.classList.add('active', 'border-emerald-600');
        }
        if (iconToday) {
            iconToday.className = 'fa-solid fa-circle-check text-emerald-600 text-xs';
        }
        let todayVal = window.lastRowActualCash || 0;
        if (amountInput) {
            amountInput.value = Math.round(todayVal);
            previewAmountRupiah(amountInput.value);
        }
        if (notesInput) {
            notesInput.value = window.lastRowNotes || 'Tarik kas fisik closing hari ini';
        }
    } else if (opt === 'custom') {
        if (btnCustom) {
            btnCustom.classList.add('active', 'border-blue-600');
        }
        if (iconCustom) {
            iconCustom.className = 'fa-solid fa-circle-check text-blue-600 text-xs';
        }
        if (amountInput) {
            amountInput.focus();
            amountInput.select();
        }
    }
}

// Ketika modal dibuka dari baris data tabel
function openBossWithdrawalModal(data) {
    const modalEl = document.getElementById('modalBossWithdrawal');
    if (!modalEl) return;

    // 1. Set Cabang
    const branchSelect = document.getElementById('modalBranchSelect');
    if (branchSelect && data.branchId) branchSelect.value = data.branchId;
    const branchHidden = modalEl.querySelector('input[name="branch_id"]');
    if (branchHidden && data.branchId) branchHidden.value = data.branchId;

    // 2. Set Tanggal
    const dateInput = document.getElementById('modalWithdrawalDate');
    if (dateInput && data.dateStr) dateInput.value = data.dateStr;

    // 3. Set Report ID & Badge
    const reportInput = document.getElementById('modalWithdrawalReportId');
    if (reportInput) reportInput.value = data.reportId || '';
    const reportBadge = document.getElementById('modalReportBadge');
    if (reportBadge) reportBadge.innerText = data.reportNumber ? ('Laporan #' + data.reportNumber) : 'Penarikan Kas Toko';
    const dateBadge = document.getElementById('modalDateBadge');
    if (dateBadge) dateBadge.innerText = data.dateFormatted || data.dateStr;

    // 4. Update ringkasan kas hari ini & saldo peti
    const actualCashText = document.getElementById('modalActualCashText');
    if (actualCashText) actualCashText.innerText = formatRupiahString(data.actualCash);
    const pettyCashText = document.getElementById('modalPettyCashText');
    if (pettyCashText) pettyCashText.innerText = formatRupiahString(data.pettyCash);

    // 5. Update Labels di Opsi
    const labelToday = document.getElementById('labelOptToday');
    if (labelToday) labelToday.innerText = formatRupiahString(data.actualCash);

    const liveCash = getLivePettyCashForBranch(data.branchId);
    const labelSweep = document.getElementById('labelOptSweepAll');
    if (labelSweep) labelSweep.innerText = formatRupiahString(liveCash);

    window.lastRowActualCash = data.actualCash || 0;
    window.lastRowNotes = 'Tarik kas fisik closing ' + (data.reportNumber || '') + ' (' + (data.dateFormatted || '') + ')';

    // Default: Bawa kas hari ini
    selectWithdrawalOption('today');

    // Buka Modal
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function openBossWithdrawalGeneral() {
    const reportBadge = document.getElementById('modalReportBadge');
    if (reportBadge) reportBadge.innerText = 'Penarikan Kas Peti Cabang';
    const reportInput = document.getElementById('modalWithdrawalReportId');
    if (reportInput) reportInput.value = '';
    const notesInput = document.getElementById('modalWithdrawalNotes');
    if (notesInput) notesInput.value = '';

    const branchSelect = document.getElementById('modalBranchSelect');
    let targetBranchId = branchSelect ? branchSelect.value : null;
    let liveCash = getLivePettyCashForBranch(targetBranchId);

    const actualCashText = document.getElementById('modalActualCashText');
    if (actualCashText) actualCashText.innerText = 'Rp 0';
    const pettyCashText = document.getElementById('modalPettyCashText');
    if (pettyCashText) pettyCashText.innerText = formatRupiahString(liveCash);

    const labelToday = document.getElementById('labelOptToday');
    if (labelToday) labelToday.innerText = 'Rp 0';

    window.lastRowActualCash = 0;
    window.lastRowNotes = '';

    selectWithdrawalOption('custom');
}

document.addEventListener('DOMContentLoaded', function() {
    const formBoss = document.getElementById('formBossWithdrawal');
    if (formBoss) {
        formBoss.addEventListener('submit', function(e) {
            const pinInput = document.getElementById('inputBossPin');
            if (!pinInput || pinInput.value.trim() !== '2209') {
                e.preventDefault();
                e.stopPropagation();
                const modalEl = document.getElementById('modalBossWithdrawal');
                if (modalEl) {
                    const m = bootstrap.Modal.getInstance(modalEl);
                    if (m) m.hide();
                }
                showBossPinModal();
                return false;
            }
        });
    }

    // Reset PIN saat modal ditutup agar sesi berikutnya selalu verifikasi ulang
    const modalWithdrawalEl = document.getElementById('modalBossWithdrawal');
    if (modalWithdrawalEl) {
        modalWithdrawalEl.addEventListener('hidden.bs.modal', function () {
            const formPinInput = document.getElementById('inputBossPin');
            if (formPinInput) formPinInput.value = '';
            pendingWithdrawalMode = null;
            pendingWithdrawalData = null;
        });
    }

    // Auto verifikasi saat mengetik 4 digit di pop-up PIN
    const promptPin = document.getElementById('inputPromptBossPin');
    if (promptPin) {
        promptPin.addEventListener('input', function() {
            const pinError = document.getElementById('promptBossPinError');
            if (pinError) {
                pinError.classList.add('d-none');
                pinError.classList.remove('d-flex');
            }
            this.classList.remove('is-invalid');
            if (this.value.length === 4) {
                handleVerifyBossPin();
            }
        });
    }

    // Ketika ganti Cabang di dalam modal: Otomatis sinkronkan saldo live cabang tersebut
    const branchSelect = document.getElementById('modalBranchSelect');
    if (branchSelect) {
        branchSelect.addEventListener('change', function() {
            const liveBal = getLivePettyCashForBranch(this.value);
            const labelSweep = document.getElementById('labelOptSweepAll');
            if (labelSweep) labelSweep.innerText = formatRupiahString(liveBal);
            const pettyCashText = document.getElementById('modalPettyCashText');
            if (pettyCashText) pettyCashText.innerText = formatRupiahString(liveBal);

            if (currentWithdrawalOption === 'sweep_all') {
                const amountInput = document.getElementById('inputWithdrawalAmount');
                if (amountInput) {
                    amountInput.value = Math.round(liveBal);
                    previewAmountRupiah(amountInput.value);
                }
            }
        });
    }

    // URL trigger: jika dibuka dengan ?action=sweep_all, otomatis buka alur tarik semua kas
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'sweep_all') {
        setTimeout(() => {
            requestBossPinForSweepAll();
        }, 350);
    }
});
</script>
@endsection