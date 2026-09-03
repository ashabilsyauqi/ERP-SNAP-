@extends('layouts.app')

@section('title', 'Laporan Tutup Hari (Daily Closing)')
@section('page-title', 'Laporan Tutup Hari & Rekonsiliasi Kas Cabang')

@section('action-buttons')
<a href="{{ route('daily-closing.create') }}" class="btn-odoo-primary text-decoration-none">
    <i class="fa-solid fa-file-signature me-1"></i>
    <span>Buat Laporan Tutup Hari</span>
</a>
@endsection

@section('content')
<div class="space-y-4">

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm" style="border-left: 4px solid #1e40af !important;">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Total Laporan</span>
            <div class="fs-5 font-extrabold text-slate-800 font-mono mt-0.5">
                {{ $reports->total() }} Laporan
            </div>
            <div class="text-[11px] text-slate-500 mt-1">Tercatat di sistem</div>
        </div>

        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm" style="border-left: 4px solid #f59e0b !important;">
            <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Menunggu ACC Owner</span>
            <div class="fs-5 font-extrabold text-amber-900 font-mono mt-0.5">
                {{ $pendingCount }} Laporan
            </div>
            <div class="text-[11px] text-amber-700 mt-1 font-semibold">Perlu tanda tangan Owner</div>
        </div>

        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm" style="border-left: 4px solid #10b981 !important;">
            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Status Fitur</span>
            <div class="fs-6 font-extrabold text-emerald-900 mt-0.5 d-flex align-items-center gap-1.5">
                <span class="badge bg-emerald-100 text-emerald-800 text-[10px] px-2 py-0.5">🟢 TAHAP PERCOBAAN</span>
            </div>
            <div class="text-[11px] text-slate-500 mt-1">Dual Digital Signature Aktif</div>
        </div>

        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm" style="border-left: 4px solid #6366f1 !important;">
            <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider block">Bagan Hierarki</span>
            <div class="fs-6 font-bold text-indigo-900 mt-0.5">
                Owner &bull; Manager &bull; Kasir
            </div>
            <div class="text-[11px] text-slate-500 mt-1">Alur verifikasi terstruktur</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('daily-closing.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-2.5 items-end">
            @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin())
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Cabang</label>
                    <select name="branch_id" class="form-select form-select-sm text-xs">
                        <option value="all">Semua Cabang</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
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

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-xs">
                <thead class="bg-slate-100 text-slate-700 border-bottom font-bold">
                    <tr>
                        <th class="ps-3 py-2.5">No Laporan</th>
                        <th class="py-2.5">Tanggal Tutup</th>
                        <th class="py-2.5">Cabang</th>
                        <th class="py-2.5">Dibuat Oleh</th>
                        <th class="py-2.5 text-end">Total Penjualan</th>
                        <th class="py-2.5 text-end">Kas Fisik</th>
                        <th class="py-2.5 text-center">Selisih</th>
                        <th class="py-2.5 text-center">Status</th>
                        <th class="pe-3 py-2.5 text-center" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $r)
                        <tr>
                            <td class="ps-3 font-mono font-bold text-blue-900">
                                <a href="{{ route('daily-closing.show', $r->id) }}" class="text-decoration-none">
                                    {{ $r->report_number }}
                                </a>
                            </td>
                            <td class="font-semibold text-slate-700">
                                {{ $r->closing_date->format('d M Y') }}
                                <span class="badge bg-slate-100 text-slate-600 text-[10px] ms-1">{{ $r->shift_type }}</span>
                            </td>
                            <td>
                                <span class="badge bg-blue-50 text-blue-800 border border-blue-200 text-[10px] font-bold">
                                    {{ $r->branch->nama_cabang ?? 'Pusat' }}
                                </span>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-800">{{ $r->manager->full_name ?? $r->manager->username }}</div>
                                <div class="text-[10px] text-slate-400">Manager Toko</div>
                            </td>
                            <td class="text-end font-mono font-bold text-slate-900">
                                Rp {{ number_format($r->total_sales, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-mono font-bold text-emerald-700">
                                Rp {{ number_format($r->actual_cash, 0, ',', '.') }}
                            </td>
                            <td class="text-center font-mono font-bold">
                                @if($r->cash_difference == 0)
                                    <span class="badge bg-emerald-100 text-emerald-800 text-[10px]">Pas (Rp 0)</span>
                                @elseif($r->cash_difference > 0)
                                    <span class="badge bg-blue-100 text-blue-800 text-[10px]">+ Rp {{ number_format($r->cash_difference, 0, ',', '.') }}</span>
                                @else
                                    <span class="badge bg-rose-100 text-rose-800 text-[10px]">- Rp {{ number_format(abs($r->cash_difference), 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($r->isVerified())
                                    <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] px-2 py-0.5 font-bold">
                                        <i class="fa-solid fa-check-double me-1"></i> Terverifikasi Owner
                                    </span>
                                @else
                                    <span class="badge bg-amber-100 text-amber-800 border border-amber-300 text-[10px] px-2 py-0.5 font-bold animate-pulse">
                                        <i class="fa-solid fa-clock me-1"></i> Menunggu ACC Owner
                                    </span>
                                @endif
                            </td>
                            <td class="pe-3 text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('daily-closing.show', $r->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="Buka Detail & Tanda Tangan">
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
                            <td colspan="9" class="text-center py-5 text-muted">
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
@endsection
