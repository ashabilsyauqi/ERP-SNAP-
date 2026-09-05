@extends('layouts.app')

@section('title', 'Laporan Laba Rugi & Arsip')
@section('page-title', 'Laporan Laba & Rugi (Profit & Loss Statement)')

@section('action-buttons')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <!-- Unduh Ringkasan PDF -->
    <a href="{{ route('reports.profit-loss.export-pdf', request()->all()) }}" class="btn-odoo-primary text-decoration-none d-inline-flex align-items-center gap-1.5" target="_blank" title="Unduh langsung ringkasan PDF">
        <i class="fa-solid fa-file-pdf text-rose-300"></i>
        <span>Unduh Ringkasan PDF</span>
    </a>

    <!-- Simpan ke Arsip Form -->
    <form method="POST" action="{{ route('reports.profit-loss.archive.store') }}" class="d-inline mb-0" id="form-save-archive">
        @csrf
        <input type="hidden" name="period_type" value="{{ $periodType }}">
        <input type="hidden" name="date" value="{{ request('date', date('Y-m-d')) }}">
        <input type="hidden" name="month" value="{{ request('month', date('n')) }}">
        <input type="hidden" name="year" value="{{ request('year', date('Y')) }}">
        <input type="hidden" name="start_date" value="{{ request('start_date', $startDate) }}">
        <input type="hidden" name="end_date" value="{{ request('end_date', $endDate) }}">
        <input type="hidden" name="branch_id" value="{{ $branchId }}">
        <button type="submit" class="btn-odoo-secondary text-slate-800 d-inline-flex align-items-center gap-1.5" title="Simpan snapshot laporan ini ke daftar arsip server">
            <i class="fa-solid fa-box-archive text-blue-600"></i>
            <span>Simpan ke Arsip</span>
        </button>
    </form>

    <!-- Cetak Laporan -->
    <button type="button" onclick="window.print()" class="btn-odoo-secondary text-slate-700">
        <i class="fa-solid fa-print"></i>
        <span>Cetak</span>
    </button>
</div>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-xl mb-3 d-flex align-items-center gap-2 text-xs" role="alert">
        <i class="fa-solid fa-circle-check fs-6 text-emerald-600"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-xl mb-3 d-flex align-items-center gap-2 text-xs" role="alert">
        <i class="fa-solid fa-circle-exclamation fs-6 text-rose-600"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Filter Toolbar -->
    <div class="o_form_sheet mb-4 p-3 bg-white print:hidden">
        <!-- Siklus Bulanan Kalender (Januari - Desember) Navigation -->
        @include('partials.monthly-lifecycle-bar', [
            'selectedMonth' => $month ?? request('month', date('n')),
            'selectedYear' => $year ?? request('year', date('Y')),
            'showAllYear' => true,
            'route' => 'reports.profit-loss',
            'extraParams' => ['period_type' => 'monthly', 'branch_id' => $branchId ?? 'all']
        ])

        <form method="GET" action="{{ route('reports.profit-loss') }}" class="row g-2 align-items-end" id="filter-form">
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label font-bold text-slate-700 text-xs uppercase mb-1">
                    <i class="fa-solid fa-calendar-days text-blue-600 me-1"></i> Tipe Periode
                </label>
                <select name="period_type" id="period_type" class="form-select form-select-sm fw-bold border-slate-300">
                    <option value="daily" {{ $periodType == 'daily' ? 'selected' : '' }}>Harian (Daily)</option>
                    <option value="monthly" {{ $periodType == 'monthly' ? 'selected' : '' }}>Bulanan (Monthly)</option>
                    <option value="yearly" {{ $periodType == 'yearly' ? 'selected' : '' }}>Tahunan (Yearly)</option>
                    <option value="custom" {{ $periodType == 'custom' ? 'selected' : '' }}>Rentang Tanggal (Custom)</option>
                </select>
            </div>

            <!-- Daily Date Selector -->
            <div class="col-12 col-sm-6 col-md-3" id="daily_selector" style="display: {{ $periodType == 'daily' ? 'block' : 'none' }}">
                <label class="form-label font-bold text-slate-700 text-xs uppercase mb-1">Pilih Tanggal</label>
                <input type="date" name="date" class="form-select form-select-sm fw-bold border-slate-300" value="{{ request('date', date('Y-m-d')) }}">
            </div>
            
            <!-- Monthly Selector -->
            <div class="col-12 col-sm-6 col-md-2" id="month_selector" style="display: {{ $periodType == 'monthly' ? 'block' : 'none' }}">
                <label class="form-label font-bold text-slate-700 text-xs uppercase mb-1">Bulan</label>
                <select name="month" class="form-select form-select-sm fw-bold border-slate-300">
                    @for($i=1; $i<=12; $i++)
                        <option value="{{ $i }}" {{ request('month', date('n')) == $i ? 'selected' : '' }}>
                            {{ date('F', mktime(0,0,0,$i,1)) }}
                        </option>
                    @endfor
                </select>
            </div>
            
            <!-- Year Selector -->
            <div class="col-12 col-sm-6 col-md-2" id="year_selector" style="display: {{ in_array($periodType, ['monthly', 'yearly']) ? 'block' : 'none' }}">
                <label class="form-label font-bold text-slate-700 text-xs uppercase mb-1">Tahun</label>
                <select name="year" class="form-select form-select-sm fw-bold border-slate-300">
                    @php $startYear = date('Y') - 5; @endphp
                    @for($i = date('Y'); $i >= $startYear; $i--)
                        <option value="{{ $i }}" {{ request('year', date('Y')) == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>

            <!-- Custom Date Range -->
            <div class="col-12 col-sm-6 col-md-2" id="start_date_selector" style="display: {{ $periodType == 'custom' ? 'block' : 'none' }}">
                <label class="form-label font-bold text-slate-700 text-xs uppercase mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-select form-select-sm border-slate-300" value="{{ request('start_date', date('Y-m-01')) }}">
            </div>
            <div class="col-12 col-sm-6 col-md-2" id="end_date_selector" style="display: {{ $periodType == 'custom' ? 'block' : 'none' }}">
                <label class="form-label font-bold text-slate-700 text-xs uppercase mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-select form-select-sm border-slate-300" value="{{ request('end_date', date('Y-m-d')) }}">
            </div>

            <!-- Branch Scoping (Owner & SuperAdmin) -->
            @if(Auth::user()->isOwner() || Auth::user()->isSuperAdmin())
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label font-bold text-slate-700 text-xs uppercase mb-1">
                    <i class="fa-solid fa-building text-blue-600 me-1"></i> Cabang
                </label>
                <select name="branch_id" class="form-select form-select-sm fw-bold border-slate-300">
                    <option value="all" {{ ($branchId ?? 'all') === 'all' ? 'selected' : '' }}>Semua Cabang (Konsolidasi)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ ($branchId ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-12 col-sm-6 col-md-2">
                <button type="submit" class="btn-odoo-primary w-100 py-1 text-xs font-bold">
                    <i class="fa-solid fa-filter me-1"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>

    <!-- Quick Reference Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4 print:hidden">
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Omzet POS</span>
                <span class="font-mono font-extrabold text-blue-900 text-sm">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</span>
            </div>
            <a href="{{ route('sales.index') }}" class="p-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition" title="Lihat Riwayat Transaksi POS">
                <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
            </a>
        </div>
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">HPP (Bahan + Click)</span>
                <span class="font-mono font-extrabold text-amber-800 text-sm">Rp {{ number_format($totalHpp, 0, ',', '.') }}</span>
            </div>
            <a href="{{ route('materials.index') }}" class="p-2 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 transition" title="Lihat Master Bahan & Click Charge">
                <i class="fa-solid fa-boxes-stacked text-xs"></i>
            </a>
        </div>
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">OPEX (Kas Keluar)</span>
                <span class="font-mono font-extrabold text-rose-700 text-sm">Rp {{ number_format($totalBebanOperasional, 0, ',', '.') }}</span>
            </div>
            <a href="{{ route('kas-keluar.index') }}" class="p-2 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 transition" title="Lihat Daftar Kas Keluar">
                <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
            </a>
        </div>
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Laba Bersih Final</span>
                <span class="font-mono font-extrabold text-sm {{ $labaBersih >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    Rp {{ number_format($labaBersih, 0, ',', '.') }}
                </span>
            </div>
            <div class="p-2 rounded-lg {{ $labaBersih >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                <i class="fa-solid {{ $labaBersih >= 0 ? 'fa-chart-line' : 'fa-chart-line-down' }} text-xs"></i>
            </div>
        </div>
    </div>

    <!-- Financial Statement Sheet -->
    <div class="max-w-4xl mx-auto o_form_sheet p-5 bg-white mb-5 print:p-0 print:border-0 print:shadow-none shadow-sm">
        
        <!-- Header -->
        <div class="text-center mb-4 pb-3 border-bottom">
            <h5 class="fw-bold text-slate-900 uppercase tracking-wide mb-1">PT Duta Raya Berjaya (Snaprint Enterprise)</h5>
            <h4 class="fw-extrabold text-blue-900 mb-1">LAPORAN LABA DAN RUGI KOMPREHENSIF</h4>
            <p class="text-xs text-slate-500 mb-0">Periode Laporan: <strong>{{ $periodLabel }}</strong></p>
            <span class="badge bg-slate-100 text-slate-700 border mt-1 font-semibold text-[11px]">
                <i class="fa-solid fa-building text-blue-600 me-1"></i> {{ $branchName }}
            </span>
        </div>

        <div class="space-y-4 text-sm">
            <!-- I. Operating Revenue -->
            <div>
                <div class="p-2 bg-slate-100 font-bold text-slate-900 text-xs uppercase d-flex justify-content-between align-items-center" style="border-left: 4px solid #1E3A8A;">
                    <span>I. Pendapatan Usaha (Omzet Penjualan POS)</span>
                    <a href="{{ route('sales.index') }}" class="text-[10px] text-blue-700 text-decoration-none hover:underline font-normal print:hidden">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka Rincian POS
                    </a>
                </div>
                <table class="table table-sm table-hover mb-0 mt-1 text-xs">
                    <tbody>
                        @foreach($pendapatan as $p)
                        <tr>
                            <td class="ps-4 text-slate-700">{{ $p->nama_akun }}</td>
                            <td class="text-end font-mono pe-3">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr class="fw-bold bg-blue-50/60">
                            <td class="ps-4 text-blue-950 font-bold">Total Pendapatan Usaha (Omzet Bersih)</td>
                            <td class="text-end font-mono text-blue-950 pe-3">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- II. Cost of Goods Sold -->
            <div>
                <div class="p-2 bg-slate-100 font-bold text-slate-900 text-xs uppercase d-flex justify-content-between align-items-center" style="border-left: 4px solid #f59e0b;">
                    <span>II. Harga Pokok Penjualan (HPP Bahan & Click Charge Mesin)</span>
                    <a href="{{ route('materials.index') }}" class="text-[10px] text-amber-800 text-decoration-none hover:underline font-normal print:hidden">
                        <i class="fa-solid fa-boxes-stacked me-1"></i> Lihat Modal Bahan
                    </a>
                </div>
                <table class="table table-sm table-hover mb-0 mt-1 text-xs">
                    <tbody>
                        @forelse($hpp as $h)
                        <tr>
                            <td class="ps-4 text-slate-700">{{ $h->nama_akun }}</td>
                            <td class="text-end font-mono pe-3">Rp {{ number_format($h->jumlah, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="ps-4 text-slate-400 italic">Belum ada pos HPP tercatat</td>
                            <td class="text-end font-mono text-slate-400 pe-3">Rp 0</td>
                        </tr>
                        @endforelse
                        <tr class="fw-bold bg-amber-50/60">
                            <td class="ps-4 text-amber-950 font-bold">Total Biaya Pokok Penjualan (HPP)</td>
                            <td class="text-end font-mono text-amber-900 pe-3">(Rp {{ number_format($totalHpp, 0, ',', '.') }})</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Gross Profit Bar -->
            <div class="d-flex justify-content-between align-items-center p-3 rounded-xl bg-slate-100 border border-slate-200 font-bold">
                <span class="text-slate-900 uppercase text-xs tracking-wider">
                    Laba Kotor (Gross Profit = Omzet &minus; HPP)
                </span>
                <span class="fs-6 font-mono font-extrabold {{ $labaKotor >= 0 ? 'text-blue-900' : 'text-rose-700' }}">
                    Rp {{ number_format($labaKotor, 0, ',', '.') }}
                </span>
            </div>

            <!-- III. Operating Expenses -->
            <div>
                <div class="p-2 bg-slate-100 font-bold text-slate-900 text-xs uppercase d-flex justify-content-between align-items-center" style="border-left: 4px solid #e11d48;">
                    <span>III. Beban Operasional (OPEX / Pengeluaran Kas Keluar)</span>
                    <a href="{{ route('kas-keluar.index') }}" class="text-[10px] text-rose-700 text-decoration-none hover:underline font-normal print:hidden">
                        <i class="fa-solid fa-receipt me-1"></i> Buka Daftar Kas Keluar
                    </a>
                </div>
                <table class="table table-sm table-hover mb-0 mt-1 text-xs">
                    <tbody>
                        @forelse($bebanOperasional as $b)
                        <tr>
                            <td class="ps-4 text-slate-700">{{ $b->nama_akun }}</td>
                            <td class="text-end font-mono pe-3">Rp {{ number_format($b->jumlah, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="ps-4 text-slate-400 italic">Belum ada pengeluaran kas keluar tercatat</td>
                            <td class="text-end font-mono text-slate-400 pe-3">Rp 0</td>
                        </tr>
                        @endforelse
                        <tr class="fw-bold bg-rose-50/60">
                            <td class="ps-4 text-rose-950 font-bold">Total Beban Operasional (OPEX)</td>
                            <td class="text-end font-mono text-rose-700 pe-3">(Rp {{ number_format($totalBebanOperasional, 0, ',', '.') }})</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Net Profit Final Box -->
            <div class="d-flex justify-content-between align-items-center p-3 rounded-xl {{ $labaBersih >= 0 ? 'bg-blue-50 border-2 border-blue-300 text-blue-950' : 'bg-rose-50 border-2 border-rose-300 text-rose-900' }} font-bold">
                <div>
                    <span class="uppercase tracking-wider text-xs block">Laba / (Rugi) Bersih Periode Berjalan</span>
                    <small class="text-slate-500 font-normal text-[11px]">Gross Profit &minus; Beban Operasional (OPEX)</small>
                </div>
                <span class="font-mono fs-5 font-black">Rp {{ number_format($labaBersih, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Signatures (Print Only) -->
        <div class="hidden print:flex mt-20 justify-between px-8">
            <div class="text-center">
                <p class="mb-16 text-xs">Disetujui Oleh,</p>
                <p class="font-bold border-top border-slate-900 pt-1 text-xs">Direktur / Pemilik Toko</p>
            </div>
            <div class="text-center">
                <p class="mb-16 text-xs">Dibuat Oleh,</p>
                <p class="font-bold border-top border-slate-900 pt-1 text-xs">Staff Finance & Akunting</p>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- ARCHIVE SECTION: Riwayat Arsip Laba Rugi   -->
    <!-- ========================================== -->
    <div class="max-w-4xl mx-auto o_form_sheet p-4 bg-white shadow-sm print:hidden">
        <div class="d-flex justify-content-between align-items-center pb-3 border-bottom mb-3">
            <div class="d-flex align-items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-700 d-flex align-items-center justify-content-center">
                    <i class="fa-solid fa-box-archive text-sm"></i>
                </div>
                <div>
                    <h6 class="mb-0 font-extrabold text-slate-900 text-sm">Arsip Dokumen Laporan Laba Rugi (Archives)</h6>
                    <span class="text-[11px] text-slate-400 font-medium">Daftar laporan laba rugi yang tersimpan resmi dalam format PDF</span>
                </div>
            </div>
            <span class="badge bg-slate-100 text-slate-700 border text-xs px-2.5 py-1 font-bold">
                {{ $archives->total() }} Arsip Tersimpan
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-xs">
                <thead class="bg-slate-50 text-slate-600 border-bottom font-bold">
                    <tr>
                        <th class="ps-3 py-2.5">Waktu Arsip</th>
                        <th class="py-2.5">Periode Laporan</th>
                        <th class="py-2.5">Cabang</th>
                        <th class="py-2.5 text-end">Omzet POS</th>
                        <th class="py-2.5 text-end">HPP</th>
                        <th class="py-2.5 text-end">OPEX</th>
                        <th class="py-2.5 text-end">Laba Bersih</th>
                        <th class="py-2.5">Dibuat Oleh</th>
                        <th class="pe-3 py-2.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($archives as $arc)
                    <tr>
                        <td class="ps-3 text-slate-500 font-medium whitespace-nowrap">
                            {{ $arc->created_at->translatedFormat('d M Y, H:i') }}
                        </td>
                        <td class="font-bold text-slate-900 whitespace-nowrap">
                            <i class="fa-solid fa-calendar-check text-blue-600 me-1"></i>
                            {{ $arc->period_label }}
                        </td>
                        <td class="whitespace-nowrap">
                            <span class="badge bg-slate-100 text-slate-700 border text-[10px]">
                                {{ $arc->branch->nama_cabang ?? 'Semua Cabang' }}
                            </span>
                        </td>
                        <td class="text-end font-mono font-bold text-blue-900 whitespace-nowrap">
                            {{ $arc->formatted_omzet }}
                        </td>
                        <td class="text-end font-mono text-amber-800 whitespace-nowrap">
                            {{ $arc->formatted_hpp }}
                        </td>
                        <td class="text-end font-mono text-rose-700 whitespace-nowrap">
                            {{ $arc->formatted_opex }}
                        </td>
                        <td class="text-end font-mono font-extrabold whitespace-nowrap">
                            <span class="badge {{ $arc->isSurplus() ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }} text-[11px] px-2 py-0.5">
                                {{ $arc->formatted_net_profit }}
                            </span>
                        </td>
                        <td class="text-slate-600 whitespace-nowrap">
                            {{ $arc->user->full_name ?: ($arc->user->name ?? 'User') }}
                        </td>
                        <td class="pe-3 text-center whitespace-nowrap">
                            <div class="d-inline-flex align-items-center gap-1">
                                <a href="{{ route('reports.profit-loss.archive.download', $arc->id) }}" class="btn btn-sm btn-outline-primary py-0.5 px-2 text-[11px] font-bold d-inline-flex align-items-center gap-1" title="Unduh Berkas PDF">
                                    <i class="fa-solid fa-file-arrow-down text-rose-600"></i>
                                    <span>PDF</span>
                                </a>
                                @if(Auth::user()->isOwner() || Auth::user()->isSuperAdmin() || Auth::user()->isManager())
                                <form method="POST" action="{{ route('reports.profit-loss.archive.destroy', $arc->id) }}" class="d-inline mb-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus arsip laporan laba rugi ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border py-0.5 px-1.5 text-rose-600 hover:bg-rose-50" title="Hapus Arsip">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-slate-400">
                            <i class="fa-solid fa-folder-open fs-3 text-slate-300 d-block mb-2"></i>
                            <span>Belum ada arsip laporan laba rugi yang disimpan. Klik tombol <strong>"Simpan ke Arsip"</strong> di atas untuk mengarsipkan snapshot saat ini.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($archives->hasPages())
        <div class="pt-3 border-top">
            {{ $archives->links() }}
        </div>
        @endif
    </div>

</div>

<script>
    document.getElementById('period_type').addEventListener('change', function() {
        const val = this.value;
        const dailySel = document.getElementById('daily_selector');
        const monthSel = document.getElementById('month_selector');
        const yearSel = document.getElementById('year_selector');
        const startSel = document.getElementById('start_date_selector');
        const endSel = document.getElementById('end_date_selector');

        dailySel.style.display = (val === 'daily') ? 'block' : 'none';
        monthSel.style.display = (val === 'monthly') ? 'block' : 'none';
        yearSel.style.display = (val === 'monthly' || val === 'yearly') ? 'block' : 'none';
        startSel.style.display = (val === 'custom') ? 'block' : 'none';
        endSel.style.display = (val === 'custom') ? 'block' : 'none';
    });
</script>
@endsection
