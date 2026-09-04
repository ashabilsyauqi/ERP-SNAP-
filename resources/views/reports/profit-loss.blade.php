@extends('layouts.app')

@section('title', 'Profit and Loss Statement')
@section('page-title', 'Laporan Laba & Rugi (Profit & Loss Statement)')

@section('action-buttons')
<button type="button" onclick="window.print()" class="btn-odoo-primary">
    <i class="fa-solid fa-print"></i>
    <span>Cetak Laporan</span>
</button>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Filter Toolbar -->
    <div class="o_form_sheet mb-3 p-3 bg-white print:hidden">
        <form method="GET" action="{{ route('reports.profit-loss') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Tipe Periode</label>
                <select name="period_type" id="period_type" class="form-select form-select-sm">
                    <option value="monthly" {{ $periodType == 'monthly' ? 'selected' : '' }}>Bulanan (Monthly)</option>
                    <option value="yearly" {{ $periodType == 'yearly' ? 'selected' : '' }}>Tahunan (Yearly)</option>
                </select>
            </div>
            
            <div class="col-12 col-md-3" id="month_selector" style="display: {{ $periodType == 'monthly' ? 'block' : 'none' }}">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Bulan</label>
                <select name="month" class="form-select form-select-sm">
                    @for($i=1; $i<=12; $i++)
                        <option value="{{ $i }}" {{ request('month', date('n')) == $i ? 'selected' : '' }}>
                            {{ date('F', mktime(0,0,0,$i,1)) }}
                        </option>
                    @endfor
                </select>
            </div>
            
            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Tahun</label>
                <select name="year" class="form-select form-select-sm">
                    @php $startYear = date('Y') - 5; @endphp
                    @for($i = date('Y'); $i >= $startYear; $i--)
                        <option value="{{ $i }}" {{ request('year', date('Y')) == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>

            @if(Auth::user()->isOwner() || Auth::user()->isSuperAdmin())
            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Cabang</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="all" {{ ($branchId ?? 'all') === 'all' ? 'selected' : '' }}>Semua Cabang (Konsolidasi)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ ($branchId ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-12 col-md-2">
                <button type="submit" class="btn-odoo-primary w-100 py-1 text-xs">
                    <i class="fa-solid fa-filter me-1"></i> Terapkan
                </button>
            </div>
        </form>
    </div>

    <!-- Financial Statement Sheet -->
    <div class="max-w-4xl mx-auto o_form_sheet p-5 bg-white print:p-0 print:border-0 print:shadow-none">
        
        <!-- Header -->
        <div class="text-center mb-4 pb-3 border-bottom">
            <h5 class="fw-bold text-slate-900 uppercase tracking-wide mb-1">PT Duta Raya Berjaya (Snaprint)</h5>
            <h4 class="fw-extrabold text-blue-900 mb-1">LAPORAN LABA DAN RUGI KOMPREHENSIF</h4>
            <p class="text-xs text-slate-500 mb-0">Periode Laporan: <strong>{{ $periodLabel }}</strong></p>
            @if(request('branch_id'))
                @php $b = $branches->firstWhere('id', request('branch_id')); @endphp
                <span class="badge bg-slate-100 text-slate-700 border mt-1">Cabang: {{ $b ? $b->nama_cabang : '' }}</span>
            @endif
        </div>

        <div class="space-y-4 text-sm">
            <!-- I. Operating Revenue -->
            <div>
                <div class="p-2 bg-slate-100 font-bold text-slate-900 text-xs uppercase d-flex justify-content-between align-items-center" style="border-left: 4px solid #1E3A8A;">
                    <span>I. Pendapatan Usaha (Operating Revenue)</span>
                </div>
                <table class="table table-sm table-hover mb-0 mt-1">
                    <tbody>
                        @foreach($pendapatan as $p)
                        <tr>
                            <td class="ps-4 text-slate-700">{{ $p->nama_akun }}</td>
                            <td class="text-end font-mono pe-3">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr class="fw-bold bg-blue-50/50">
                            <td class="ps-4 text-blue-900">Total Pendapatan Usaha</td>
                            <td class="text-end font-mono text-blue-900 pe-3">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- II. Cost of Goods Sold -->
            <div>
                <div class="p-2 bg-slate-100 font-bold text-slate-900 text-xs uppercase d-flex justify-content-between align-items-center" style="border-left: 4px solid #f59e0b;">
                    <span>II. Harga Pokok Penjualan (HPP Bahan / COGS)</span>
                </div>
                <table class="table table-sm table-hover mb-0 mt-1">
                    <tbody>
                        @forelse($hpp as $h)
                        <tr>
                            <td class="ps-4 text-slate-700">{{ $h->nama_akun }}</td>
                            <td class="text-end font-mono pe-3">Rp {{ number_format($h->jumlah, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="ps-4 text-slate-400 italic">Belum ada pos HPP</td>
                            <td class="text-end font-mono text-slate-400 pe-3">Rp 0</td>
                        </tr>
                        @endforelse
                        <tr class="fw-bold bg-amber-50/50">
                            <td class="ps-4 text-amber-900">Total Biaya Pokok Penjualan (HPP)</td>
                            <td class="text-end font-mono text-amber-800 pe-3">(Rp {{ number_format($totalHpp, 0, ',', '.') }})</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Gross Profit Bar -->
            <div class="d-flex justify-content-between align-items-center p-3 rounded bg-slate-100 border font-bold">
                <span class="text-slate-900 uppercase">Laba Kotor (Gross Profit)</span>
                <span class="fs-6 font-mono {{ $labaKotor >= 0 ? 'text-blue-900' : 'text-rose-700' }}">
                    Rp {{ number_format($labaKotor, 0, ',', '.') }}
                </span>
            </div>

            <!-- III. Operating Expenses -->
            <div>
                <div class="p-2 bg-slate-100 font-bold text-slate-900 text-xs uppercase d-flex justify-content-between align-items-center" style="border-left: 4px solid #e11d48;">
                    <span>III. Beban & Biaya Operasional (Operating Expenses)</span>
                </div>
                <table class="table table-sm table-hover mb-0 mt-1">
                    <tbody>
                        @forelse($bebanOperasional as $b)
                        <tr>
                            <td class="ps-4 text-slate-700">{{ $b->nama_akun }}</td>
                            <td class="text-end font-mono pe-3">Rp {{ number_format($b->jumlah, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="ps-4 text-slate-400 italic">Belum ada beban operasional</td>
                            <td class="text-end font-mono text-slate-400 pe-3">Rp 0</td>
                        </tr>
                        @endforelse
                        <tr class="fw-bold bg-rose-50/50">
                            <td class="ps-4 text-rose-900">Total Beban Operasional</td>
                            <td class="text-end font-mono text-rose-700 pe-3">(Rp {{ number_format($totalBebanOperasional, 0, ',', '.') }})</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Net Profit Final Box -->
            <div class="d-flex justify-content-between align-items-center p-3 rounded {{ $labaBersih >= 0 ? 'bg-blue-50 border border-blue-200 text-blue-950' : 'bg-rose-50 border border-rose-200 text-rose-900' }} font-bold fs-6">
                <span class="uppercase tracking-wide">Laba / (Rugi) Bersih Periode Berjalan</span>
                <span class="font-mono fs-5">Rp {{ number_format($labaBersih, 0, ',', '.') }}</span>
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
</div>

<script>
    document.getElementById('period_type').addEventListener('change', function() {
        if(this.value === 'monthly') {
            document.getElementById('month_selector').style.display = 'block';
        } else {
            document.getElementById('month_selector').style.display = 'none';
        }
    });
</script>
@endsection
