@extends('layouts.app')

@section('title', 'Laba Rugi')
@section('page-title', 'Laporan Laba Rugi')

@section('content')

<!-- Filter -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8 print:hidden">
    <form method="GET" action="{{ route('reports.profit-loss') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Laporan</label>
            <select name="period_type" id="period_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm">
                <option value="monthly" {{ $periodType == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                <option value="yearly" {{ $periodType == 'yearly' ? 'selected' : '' }}>Tahunan</option>
            </select>
        </div>
        
        <div id="month_selector" style="display: {{ $periodType == 'monthly' ? 'block' : 'none' }}">
            <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
            <select name="month" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm">
                @for($i=1; $i<=12; $i++)
                    <option value="{{ $i }}" {{ request('month', date('n')) == $i ? 'selected' : '' }}>
                        {{ date('F', mktime(0,0,0,$i,1)) }}
                    </option>
                @endfor
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
            <select name="year" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm">
                @php $startYear = date('Y') - 5; @endphp
                @for($i = date('Y'); $i >= $startYear; $i--)
                    <option value="{{ $i }}" {{ request('year', date('Y')) == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select>
        </div>

        @if(Auth::user()->role === 'owner')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cabang</label>
            <select name="branch_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm">
                <option value="">Konsolidasi Semua Cabang</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                @endforeach
            </select>
        </div>
        @else
        <div class="hidden"></div>
        @endif

        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                Buat Laporan
            </button>
            <button type="button" onclick="window.print()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none" title="Print Laporan">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            </button>
        </div>
    </form>
</div>

<!-- Laporan Paper -->
<div class="max-w-4xl mx-auto bg-white rounded-none md:rounded-2xl shadow-sm border border-gray-100 p-8 md:p-12 print:shadow-none print:border-none print:p-0">
    
    <!-- Header Laporan -->
    <div class="text-center mb-10 border-b-2 border-gray-800 pb-6">
        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight uppercase">PT Duta Raya Berjaya (Snaprint)</h1>
        <h2 class="text-xl font-bold text-gray-700 mt-1">LAPORAN LABA RUGI</h2>
        <p class="text-gray-500 font-medium mt-2">Periode: {{ $periodLabel }}</p>
        @if(request('branch_id'))
            @php $b = $branches->firstWhere('id', request('branch_id')); @endphp
            <p class="text-primary-600 font-semibold mt-1">Cabang: {{ $b ? $b->nama_cabang : '' }}</p>
        @endif
    </div>

    <div class="space-y-6 text-sm">
        
        <!-- PENDAPATAN -->
        <div>
            <h3 class="font-bold text-gray-900 uppercase bg-gray-50 py-2 px-3 border-l-4 border-gray-800 mb-2">I. Pendapatan</h3>
            <table class="w-full mb-2">
                <tbody>
                    @foreach($pendapatan as $p)
                    <tr class="group hover:bg-gray-50 transition-colors">
                        <td class="py-2 px-4 text-gray-700 pl-8">{{ $p->nama_akun }}</td>
                        <td class="py-2 px-4 text-right text-gray-900 w-1/3">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="flex justify-between items-center bg-green-50/50 py-2 px-4 font-bold border-y border-gray-200">
                <span class="text-gray-900">Total Pendapatan</span>
                <span class="text-green-700">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- HPP -->
        <div>
            <h3 class="font-bold text-gray-900 uppercase bg-gray-50 py-2 px-3 border-l-4 border-gray-800 mb-2 mt-6">II. Harga Pokok Penjualan (HPP)</h3>
            <table class="w-full mb-2">
                <tbody>
                    @forelse($hpp as $h)
                    <tr class="group hover:bg-gray-50 transition-colors">
                        <td class="py-2 px-4 text-gray-700 pl-8">{{ $h->nama_akun }}</td>
                        <td class="py-2 px-4 text-right text-gray-900 w-1/3">Rp {{ number_format($h->jumlah, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td class="py-2 px-4 text-gray-400 pl-8 italic">Tidak ada HPP tercatat</td>
                        <td class="py-2 px-4 text-right text-gray-400 w-1/3">Rp 0</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="flex justify-between items-center bg-rose-50/50 py-2 px-4 font-bold border-y border-gray-200">
                <span class="text-gray-900">Total Harga Pokok Penjualan</span>
                <span class="text-rose-700">(Rp {{ number_format($totalHpp, 0, ',', '.') }})</span>
            </div>
        </div>

        <!-- LABA KOTOR -->
        <div class="flex justify-between items-center bg-gray-100 py-3 px-4 font-extrabold border-y-2 border-gray-800 my-6 text-base">
            <span class="text-gray-900 uppercase tracking-wide">Laba Kotor</span>
            <span class="{{ $labaKotor >= 0 ? 'text-green-700' : 'text-rose-700' }}">Rp {{ number_format($labaKotor, 0, ',', '.') }}</span>
        </div>

        <!-- BEBAN OPERASIONAL -->
        <div>
            <h3 class="font-bold text-gray-900 uppercase bg-gray-50 py-2 px-3 border-l-4 border-gray-800 mb-2">III. Beban Operasional</h3>
            <table class="w-full mb-2">
                <tbody>
                    @forelse($bebanOperasional as $b)
                    <tr class="group hover:bg-gray-50 transition-colors">
                        <td class="py-2 px-4 text-gray-700 pl-8">{{ $b->nama_akun }}</td>
                        <td class="py-2 px-4 text-right text-gray-900 w-1/3">Rp {{ number_format($b->jumlah, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td class="py-2 px-4 text-gray-400 pl-8 italic">Tidak ada beban operasional tercatat</td>
                        <td class="py-2 px-4 text-right text-gray-400 w-1/3">Rp 0</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="flex justify-between items-center bg-rose-50/50 py-2 px-4 font-bold border-y border-gray-200">
                <span class="text-gray-900">Total Beban Operasional</span>
                <span class="text-rose-700">(Rp {{ number_format($totalBebanOperasional, 0, ',', '.') }})</span>
            </div>
        </div>

        <!-- LABA BERSIH -->
        <div class="flex justify-between items-center py-4 px-4 font-extrabold border-y-4 border-double border-gray-900 mt-8 text-lg {{ $labaBersih >= 0 ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
            <span class="uppercase tracking-wide">Laba Bersih {{ $labaBersih >= 0 ? '' : '(Rugi)' }} Operasional</span>
            <span>Rp {{ number_format($labaBersih, 0, ',', '.') }}</span>
        </div>
        
    </div>
    
    <!-- Signatures (Print Only) -->
    <div class="hidden print:flex mt-24 justify-between px-12">
        <div class="text-center">
            <p class="mb-20">Disetujui Oleh,</p>
            <p class="font-bold border-b border-gray-900 px-8 pb-1 inline-block">Direktur / Owner</p>
        </div>
        <div class="text-center">
            <p class="mb-20">Dibuat Oleh,</p>
            <p class="font-bold border-b border-gray-900 px-8 pb-1 inline-block">Bagian Keuangan</p>
        </div>
    </div>

</div>

<style>
    @media print {
        @page { margin: 1.5cm; }
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background-color: white !important; }
        aside, header { display: none !important; }
        main { padding: 0 !important; overflow: visible !important; }
    }
</style>

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
