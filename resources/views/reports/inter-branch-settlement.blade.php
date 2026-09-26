@extends('layouts.app')

@section('title', 'Bagi Hasil 25/75 & Settlement Kliring')
@section('page-title', 'Rekonsiliasi & Settlement Antar-Cabang (25/75)')

@section('action-buttons')
<a href="{{ route('reports.inter-branch-settlement.export-excel', request()->query()) }}" class="btn btn-outline-success btn-sm font-bold shadow-sm">
    <i class="fa-solid fa-file-excel me-1"></i> Ekspor CSV / Excel
</a>
<button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm font-bold shadow-sm">
    <i class="fa-solid fa-print me-1"></i> Cetak Laporan
</button>
<a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm font-bold shadow-sm">
    <i class="fa-solid fa-cash-register me-1"></i> Terminal Kasir POS
</a>
@endsection

@section('content')
<div class="space-y-6 pb-12">
    
    <!-- Filter Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <form method="GET" action="{{ route('reports.inter-branch-settlement') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            @if($isOwnerOrSuper)
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Pihak Cabang 1 (Asal)</label>
                    <select name="branch_1" class="form-select form-select-sm text-xs font-semibold rounded-xl">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $b->id == $branch1Id ? 'selected' : '' }}>{{ $b->nama_cabang }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Pihak Cabang 2 (Mitra)</label>
                    <select name="branch_2" class="form-select form-select-sm text-xs font-semibold rounded-xl">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $b->id == $branch2Id ? 'selected' : '' }}>{{ $b->nama_cabang }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Cabang Anda (Otomatis)</label>
                    <div class="form-control form-control-sm text-xs font-bold bg-slate-100 text-slate-800 rounded-xl flex items-center gap-1.5 py-1.5 border-slate-200">
                        <i class="fa-solid fa-store text-emerald-600"></i>
                        <span>{{ $branch1->nama_cabang ?? 'Cabang Anda' }}</span>
                    </div>
                    <input type="hidden" name="branch_1" value="{{ $branch1Id }}">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Pilih Mitra Kliring (Cabang Lawan)</label>
                    <select name="branch_2" class="form-select form-select-sm text-xs font-semibold rounded-xl border-indigo-300 focus:border-indigo-500">
                        @foreach($branches as $b)
                            @if($b->id != $branch1Id)
                                <option value="{{ $b->id }}" {{ $b->id == $branch2Id ? 'selected' : '' }}>{{ $b->nama_cabang }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Bulan & Tahun</label>
                <div class="grid grid-cols-2 gap-1.5">
                    <select name="month" class="form-select form-select-sm text-xs font-semibold rounded-xl">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                                {{ Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                    <select name="year" class="form-select form-select-sm text-xs font-semibold rounded-xl">
                        @for($y = 2025; $y <= 2028; $y++)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Rentang Tanggal (Opsional)</label>
                <div class="grid grid-cols-2 gap-1">
                    <input type="date" name="start_date" value="{{ $startDate }}" class="form-control form-control-sm text-xs rounded-xl">
                    <input type="date" name="end_date" value="{{ $endDate }}" class="form-control form-control-sm text-xs rounded-xl">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm rounded-xl font-bold flex-1">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                <a href="{{ route('reports.inter-branch-settlement') }}" class="btn btn-light btn-sm rounded-xl font-bold border">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- BANNER LOGIKA 25/75 & KEPEMILIKAN UANG -->
    <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex-1">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs font-bold mb-2.5">
                    <i class="fa-solid fa-shield-halved text-indigo-600"></i>
                    <span>Aturan Split Resmi: 25% Komisi Order &amp; 75% Pengerjaan Produksi</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900">Ringkasan Posisi Kas &amp; Pembagian Hak</h2>
                <p class="text-xs sm:text-sm text-slate-600 mt-1 max-w-2xl leading-relaxed">
                    Uang customer 100% dipegang oleh cabang penerima order. Dari total nominal pesanan, <strong class="text-amber-700 font-bold">25%</strong> adalah hak cabang pengirim (komisi) dan <strong class="text-emerald-700 font-bold">75%</strong> adalah hak cabang pelaksana produksi.
                </p>

                <!-- Visual 25% / 75% Split Indicator Bar -->
                <div class="mt-4 p-3.5 bg-slate-50 border border-slate-200 rounded-xl max-w-2xl">
                    <div class="flex justify-between text-[11px] font-bold mb-1.5">
                        <span class="text-amber-800 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
                            <span>25% Komisi Cabang Asal (Input Order)</span>
                        </span>
                        <span class="text-emerald-800 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <span>75% Biaya Pengerjaan (Cabang Pelaksana)</span>
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3.5 flex overflow-hidden p-0.5 border border-slate-300">
                        <div class="bg-amber-400 h-full rounded-l-full flex items-center justify-center text-[9px] font-bold text-amber-950" style="width: 25%" title="25% Komisi Order">25%</div>
                        <div class="bg-emerald-600 h-full rounded-r-full flex items-center justify-center text-[9px] font-bold text-white" style="width: 75%" title="75% Pelaksana Produksi">75%</div>
                    </div>
                </div>
            </div>

            <!-- Net Settlement Display Box -->
            <div class="w-full lg:w-auto min-w-[340px] bg-slate-900 text-white rounded-2xl p-5 flex flex-col gap-2.5 shadow-lg border border-slate-800">
                <span class="text-[11px] font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-scale-balanced text-amber-400"></i>
                    <span>Status Kliring Bersih (Netting):</span>
                </span>
                
                @if($settlement['is_balanced'])
                    <div class="flex items-center gap-3 bg-emerald-950/60 p-3 rounded-xl border border-emerald-500/30">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-400 text-emerald-400 flex items-center justify-center font-bold text-lg">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div>
                            <span class="text-emerald-300 font-bold text-sm block">SALDO IMPAS / SEIMBANG</span>
                            <span class="text-xs text-slate-300">Tidak ada kewajiban transfer antar cabang (Rp 0).</span>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3 bg-slate-800 p-3.5 rounded-xl border border-slate-700">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-400 text-amber-400 flex items-center justify-center font-bold text-lg">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        </div>
                        <div>
                            <span class="text-amber-300 font-bold text-xs sm:text-sm block">
                                {{ $settlement['payer']->nama_cabang }} &rarr; {{ $settlement['receiver']->nama_cabang }}
                            </span>
                            <span class="text-xs text-slate-300 block mt-0.5">
                                Wajib transfer netto: <strong class="text-amber-400 font-mono text-sm font-black">Rp {{ number_format($settlement['amount'], 0, ',', '.') }}</strong>
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- 2 Directions Visual Card Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 pt-5 border-t border-slate-200 text-xs">
            <!-- Flow 1: Branch 1 -> Branch 2 -->
            <div class="bg-emerald-50/70 rounded-xl p-4 border border-emerald-200 flex flex-col justify-between gap-2.5">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <span class="font-bold text-emerald-900 flex items-center gap-1.5 text-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            <span>{{ $branch1->nama_cabang ?? 'Cabang 1' }} &rarr; {{ $branch2->nama_cabang ?? 'Cabang 2' }}</span>
                        </span>
                        <span class="px-2.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10.5px] font-bold border border-emerald-300">
                            Order di {{ $branch1->nama_cabang ?? 'Cabang 1' }}
                        </span>
                    </div>
                    <p class="text-slate-600 text-xs mb-0">
                        Uang 100% diterima kasir <strong>{{ $branch1->nama_cabang ?? 'Cabang 1' }}</strong>. Hak {{ $branch1->nama_cabang ?? 'Cabang 1' }} <strong>25%</strong> (komisi), Hak {{ $branch2->nama_cabang ?? 'Cabang 2' }} <strong>75%</strong> (produksi).
                    </p>
                </div>
                <div class="text-xs text-emerald-950 font-mono bg-white p-2.5 rounded-lg border border-emerald-200 font-medium">
                    Total Order: <strong class="text-slate-900">Rp {{ number_format($b1CashIn, 0, ',', '.') }}</strong> &rarr; {{ $branch1->nama_cabang ?? 'Cabang 1' }} wajib setor <strong class="text-rose-700">Rp {{ number_format($b1OwesB2, 0, ',', '.') }}</strong> ke {{ $branch2->nama_cabang ?? 'Cabang 2' }}.
                </div>
            </div>

            <!-- Flow 2: Branch 2 -> Branch 1 -->
            <div class="bg-indigo-50/70 rounded-xl p-4 border border-indigo-200 flex flex-col justify-between gap-2.5">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <span class="font-bold text-indigo-900 flex items-center gap-1.5 text-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            <span>{{ $branch2->nama_cabang ?? 'Cabang 2' }} &rarr; {{ $branch1->nama_cabang ?? 'Cabang 1' }}</span>
                        </span>
                        <span class="px-2.5 py-0.5 rounded-md bg-indigo-100 text-indigo-800 text-[10.5px] font-bold border border-indigo-300">
                            Order di {{ $branch2->nama_cabang ?? 'Cabang 2' }}
                        </span>
                    </div>
                    <p class="text-slate-600 text-xs mb-0">
                        Uang 100% diterima kasir <strong>{{ $branch2->nama_cabang ?? 'Cabang 2' }}</strong>. Hak {{ $branch2->nama_cabang ?? 'Cabang 2' }} <strong>25%</strong> (komisi), Hak {{ $branch1->nama_cabang ?? 'Cabang 1' }} <strong>75%</strong> (produksi).
                    </p>
                </div>
                <div class="text-xs text-indigo-950 font-mono bg-white p-2.5 rounded-lg border border-indigo-200 font-medium">
                    Total Order: <strong class="text-slate-900">Rp {{ number_format($b2CashIn, 0, ',', '.') }}</strong> &rarr; {{ $branch2->nama_cabang ?? 'Cabang 2' }} wajib setor <strong class="text-rose-700">Rp {{ number_format($b2OwesB1, 0, ',', '.') }}</strong> ke {{ $branch1->nama_cabang ?? 'Cabang 1' }}.
                </div>
            </div>
        </div>
    </div>

    <!-- STAT CARDS: PHYSICAL CASH VS REVENUE -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- CARD 1: Physical Cash at Branch 1 -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-emerald-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-md">Kas Fisik {{ $branch1->nama_cabang ?? 'Cabang 1' }}</span>
                <i class="fa-solid fa-wallet text-emerald-600 text-lg"></i>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900 font-mono">Rp {{ number_format($b1CashIn, 0, ',', '.') }}</div>
                <p class="text-xs text-slate-500 mt-1">Uang customer diterima kasir {{ $branch1->nama_cabang ?? 'Cabang 1' }}</p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500">Kewajiban Transfer (75%):</span>
                <span class="font-bold text-rose-600 font-mono">Rp {{ number_format($b1OwesB2, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- CARD 2: Revenue Share of Branch 1 -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-emerald-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-900 bg-emerald-100 px-2.5 py-1 rounded-md">Hak Bersih {{ $branch1->nama_cabang ?? 'Cabang 1' }}</span>
                <i class="fa-solid fa-chart-line text-emerald-700 text-lg"></i>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-emerald-700 font-mono">Rp {{ number_format($b1TotalRevenue, 0, ',', '.') }}</div>
                <p class="text-xs text-slate-500 mt-1">25% komisi order + 75% pengerjaan</p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500">Piutang dari {{ $branch2->nama_cabang ?? 'Cabang 2' }}:</span>
                <span class="font-bold text-emerald-600 font-mono">Rp {{ number_format($b1WorkShare, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- CARD 3: Physical Cash at Branch 2 -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-800 bg-indigo-50 px-2.5 py-1 rounded-md">Kas Fisik {{ $branch2->nama_cabang ?? 'Cabang 2' }}</span>
                <i class="fa-solid fa-wallet text-indigo-600 text-lg"></i>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900 font-mono">Rp {{ number_format($b2CashIn, 0, ',', '.') }}</div>
                <p class="text-xs text-slate-500 mt-1">Uang customer diterima kasir {{ $branch2->nama_cabang ?? 'Cabang 2' }}</p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500">Kewajiban Transfer (75%):</span>
                <span class="font-bold text-rose-600 font-mono">Rp {{ number_format($b2OwesB1, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- CARD 4: Revenue Share of Branch 2 -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-900 bg-indigo-100 px-2.5 py-1 rounded-md">Hak Bersih {{ $branch2->nama_cabang ?? 'Cabang 2' }}</span>
                <i class="fa-solid fa-chart-line text-indigo-700 text-lg"></i>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-indigo-700 font-mono">Rp {{ number_format($b2TotalRevenue, 0, ',', '.') }}</div>
                <p class="text-xs text-slate-500 mt-1">25% komisi order + 75% pengerjaan</p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500">Piutang dari {{ $branch1->nama_cabang ?? 'Cabang 1' }}:</span>
                <span class="font-bold text-indigo-600 font-mono">Rp {{ number_format($b2WorkShare, 0, ',', '.') }}</span>
            </div>
        </div>

    </div>

    <!-- DETAILED RECONCILIATION SUMMARY BOX (3 COLUMNS) -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-5 border-b border-slate-100">
            <div>
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2 mb-0">
                    <i class="fa-solid fa-scale-balanced text-slate-700"></i>
                    <span>Matriks Rekonsiliasi & Hutang Piutang Kliring</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1 mb-0">Penghitungan komprehensif arus kas masuk vs hak bagi hasil kedua cabang (Periode: <strong>{{ $periodLabel }}</strong>)</p>
            </div>
            <div>
                <button type="button" onclick="openBeritaAcaraModal()" class="btn btn-dark btn-sm rounded-xl font-bold px-3 py-2 flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-file-signature"></i>
                    <span>Cetak Berita Acara Kliring</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            
            <!-- Column 1: Posisi Branch 1 -->
            <div class="bg-emerald-50/50 rounded-xl p-4 border border-emerald-200/80">
                <div class="flex items-center justify-between pb-3 border-b border-emerald-200">
                    <span class="font-bold text-emerald-900 text-sm flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> {{ $branch1->nama_cabang ?? 'Cabang 1' }}
                    </span>
                    <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">Rekap Kas</span>
                </div>
                <div class="space-y-2.5 mt-3 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Total Kas Fisik Masuk:</span>
                        <strong class="text-slate-900 font-mono">Rp {{ number_format($b1CashIn, 0, ',', '.') }}</strong>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Hak 25% (Order Sendiri):</span>
                        <strong class="text-emerald-700 font-mono">Rp {{ number_format($b1OwnShare, 0, ',', '.') }}</strong>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Hak 75% (Kerjakan {{ $branch2->nama_cabang ?? 'Cabang 2' }}):</span>
                        <strong class="text-emerald-700 font-mono">Rp {{ number_format($b1WorkShare, 0, ',', '.') }}</strong>
                    </div>
                    <div class="pt-2 border-t border-emerald-200 flex justify-between font-bold text-emerald-950 text-sm">
                        <span>Total Hak Pendapatan:</span>
                        <span class="font-mono">Rp {{ number_format($b1TotalRevenue, 0, ',', '.') }}</span>
                    </div>
                    <div class="pt-1 flex justify-between text-rose-600 font-semibold">
                        <span>Wajib Kirim ke {{ $branch2->nama_cabang ?? 'Cabang 2' }}:</span>
                        <span class="font-mono font-bold">Rp {{ number_format($b1OwesB2, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Column 2: Net Settlement Result -->
            <div class="bg-slate-900 text-white rounded-xl p-5 flex flex-col justify-between shadow-lg relative overflow-hidden">
                <div>
                    <div class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Eksekusi Kliring Akhir (Netting)</div>
                    @if($settlement['is_balanced'])
                        <div class="text-base font-bold text-emerald-400 mt-2">Saldo Saling Potong Impas</div>
                        <div class="text-2xl font-black text-emerald-400 mt-2 font-mono">Rp 0</div>
                        <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                            Kedua cabang memiliki saldo hutang-piutang yang persis seimbang. Tidak ada uang yang perlu ditransfer.
                        </p>
                    @else
                        <div class="text-base font-bold text-amber-300 mt-2">
                            {{ $settlement['payer']->nama_cabang }} &rarr; {{ $settlement['receiver']->nama_cabang }}
                        </div>
                        <div class="text-2xl font-black text-amber-400 mt-2 font-mono">
                            Rp {{ number_format($settlement['amount'], 0, ',', '.') }}
                        </div>
                        <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                            Setelah saling potong hutang-piutang 75%, <strong>{{ $settlement['payer']->nama_cabang }}</strong> mentransfer nominal bersih ke rekening <strong>{{ $settlement['receiver']->nama_cabang }}</strong>.
                        </p>
                    @endif
                </div>
                
                <div class="mt-4 pt-4 border-t border-white/10 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Metode:</span>
                    <span class="font-bold text-emerald-400 flex items-center gap-1">
                        <i class="fa-solid fa-circle-check"></i> Real-time Netting Synced
                    </span>
                </div>
            </div>

            <!-- Column 3: Posisi Branch 2 -->
            <div class="bg-indigo-50/50 rounded-xl p-4 border border-indigo-200/80">
                <div class="flex items-center justify-between pb-3 border-b border-indigo-200">
                    <span class="font-bold text-indigo-900 text-sm flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span> {{ $branch2->nama_cabang ?? 'Cabang 2' }}
                    </span>
                    <span class="text-[11px] font-semibold text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded">Rekap Kas</span>
                </div>
                <div class="space-y-2.5 mt-3 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Total Kas Fisik Masuk:</span>
                        <strong class="text-slate-900 font-mono">Rp {{ number_format($b2CashIn, 0, ',', '.') }}</strong>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Hak 25% (Order Sendiri):</span>
                        <strong class="text-indigo-700 font-mono">Rp {{ number_format($b2OwnShare, 0, ',', '.') }}</strong>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Hak 75% (Kerjakan {{ $branch1->nama_cabang ?? 'Cabang 1' }}):</span>
                        <strong class="text-indigo-700 font-mono">Rp {{ number_format($b2WorkShare, 0, ',', '.') }}</strong>
                    </div>
                    <div class="pt-2 border-t border-indigo-200 flex justify-between font-bold text-indigo-950 text-sm">
                        <span>Total Hak Pendapatan:</span>
                        <span class="font-mono">Rp {{ number_format($b2TotalRevenue, 0, ',', '.') }}</span>
                    </div>
                    <div class="pt-1 flex justify-between text-rose-600 font-semibold">
                        <span>Wajib Kirim ke {{ $branch1->nama_cabang ?? 'Cabang 1' }}:</span>
                        <span class="font-mono font-bold">Rp {{ number_format($b2OwesB1, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- CHARTS COMPARISON -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 pt-6 border-t border-slate-100">
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie text-slate-500"></i>
                    <span>Perbandingan Kas Fisik di Kasir (Cash in Hand)</span>
                </h4>
                <div class="h-56 relative flex items-center justify-center">
                    <canvas id="chartCashIn"></canvas>
                </div>
            </div>
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-chart-column text-slate-500"></i>
                    <span>Perbandingan Hak Pendapatan Bersih (25% + 75%)</span>
                </h4>
                <div class="h-56 relative flex items-center justify-center">
                    <canvas id="chartRevenue"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- TRANSACTIONS MASTER TABLE -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h3 class="font-bold text-slate-900 text-sm mb-0">Daftar Transaksi Lintas Cabang (Bilateral Pair)</h3>
                <p class="text-xs text-slate-500 mb-0">Total <strong>{{ $pairTransactions->count() }}</strong> transaksi ditemukan pada periode terpilih</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table table-hover align-middle mb-0 text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider text-[10px] border-b">
                    <tr>
                        <th class="py-3 px-4">No. Invoice & Tanggal</th>
                        <th class="py-3 px-4">Arah Order</th>
                        <th class="py-3 px-4">Pelanggan / Keterangan</th>
                        <th class="py-3 px-4 text-center">Metode Bayar</th>
                        <th class="py-3 px-4 text-end">Total Nilai (100%)</th>
                        <th class="py-3 px-4 text-end bg-emerald-50/60 text-emerald-900 font-bold">Hak {{ $branch1->nama_cabang ?? 'Cabang 1' }}</th>
                        <th class="py-3 px-4 text-end bg-indigo-50/60 text-indigo-900 font-bold">Hak {{ $branch2->nama_cabang ?? 'Cabang 2' }}</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($pairTransactions as $trx)
                        @php
                            $isB1Origin = ($trx->branch_id == $branch1Id);
                            $b1Share = $isB1Origin ? ($trx->total_price * 0.25) : ($trx->total_price * 0.75);
                            $b2Share = $isB1Origin ? ($trx->total_price * 0.75) : ($trx->total_price * 0.25);
                        @endphp
                        <tr>
                            <td class="py-3 px-4">
                                <a href="{{ route('invoices.public', $trx->invoice_number) }}" target="_blank" class="font-bold font-mono text-blue-600 hover:text-blue-800 text-decoration-none">
                                    {{ $trx->invoice_number }}
                                </a>
                                <span class="text-[11px] text-slate-400 block">{{ $trx->created_at->format('d M Y H:i') }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($isB1Origin)
                                    <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold text-[10.5px]">
                                        🟢 {{ $branch1->nama_cabang }} &rarr; {{ $branch2->nama_cabang }}
                                    </span>
                                @else
                                    <span class="badge bg-indigo-100 text-indigo-800 border border-indigo-300 font-bold text-[10.5px]">
                                        🟣 {{ $branch2->nama_cabang }} &rarr; {{ $branch1->nama_cabang }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <strong class="text-slate-900 block">{{ $trx->customer_name ?: ($trx->customer ? $trx->customer->name : 'Pelanggan Umum') }}</strong>
                                <div class="text-[11px] text-slate-500 truncate max-w-xs">
                                    @foreach($trx->transactionDetails as $d)
                                        <span>{{ $d->material ? $d->material->material_name : ($d->is_vendor_job ? "[Offset] {$d->vendor_name}" : 'Item') }} ({{ $d->qty_ordered }}x)</span>@if(!$loop->last), @endif
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="badge bg-slate-100 text-slate-700 border text-[10px] font-bold">{{ $trx->payment_method }}</span>
                            </td>
                            <td class="py-3 px-4 text-end font-bold font-mono text-slate-900">
                                Rp {{ number_format($trx->total_price, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-end font-bold font-mono text-emerald-700 bg-emerald-50/40">
                                Rp {{ number_format($b1Share, 0, ',', '.') }}
                                <span class="text-[9.5px] text-slate-400 block font-normal">{{ $isB1Origin ? '25% Komisi' : '75% Pengerjaan' }}</span>
                            </td>
                            <td class="py-3 px-4 text-end font-bold font-mono text-indigo-700 bg-indigo-50/40">
                                Rp {{ number_format($b2Share, 0, ',', '.') }}
                                <span class="text-[9.5px] text-slate-400 block font-normal">{{ $isB1Origin ? '75% Pengerjaan' : '25% Komisi' }}</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('sales.receipt', $trx->id) }}" target="_blank" class="btn btn-xs btn-outline-secondary py-0.5 px-2 rounded-lg" title="Cetak Struk">
                                    <i class="fa-solid fa-receipt"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 block"></i>
                                <span>Belum ada transaksi lintas cabang antara <strong>{{ $branch1->nama_cabang ?? 'Cabang 1' }}</strong> dan <strong>{{ $branch2->nama_cabang ?? 'Cabang 2' }}</strong> pada periode ini.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($pairTransactions->count() > 0)
                    <tfoot class="bg-slate-50 font-bold border-t-2 text-xs">
                        <tr>
                            <td colspan="4" class="py-3 px-4 text-end uppercase">TOTAL AKUMULASI:</td>
                            <td class="py-3 px-4 text-end font-black font-mono text-slate-900">
                                Rp {{ number_format($b1CashIn + $b2CashIn, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-end font-black font-mono text-emerald-700 bg-emerald-100/50">
                                Rp {{ number_format($b1TotalRevenue, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-end font-black font-mono text-indigo-700 bg-indigo-100/50">
                                Rp {{ number_format($b2TotalRevenue, 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<!-- MODAL BERITA ACARA KLIRING / PRINTABLE SETTLEMENT SHEET -->
<div class="modal fade" id="modalBeritaAcara" tabindex="-1" aria-labelledby="modalBeritaAcaraLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden" style="border-radius: 1.25rem;">
            <div class="px-4 py-3 bg-slate-900 text-white d-flex justify-content-between align-items-center">
                <h6 class="text-sm font-bold mb-0 text-white flex items-center gap-2">
                    <i class="fa-solid fa-file-signature text-amber-400"></i>
                    <span>Berita Acara Rekonsiliasi & Settlement Kliring</span>
                </h6>
                <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="p-6 bg-white space-y-4 text-xs text-slate-800" id="printableBeritaAcara">
                <div class="text-center pb-4 border-b">
                    <h4 class="font-black text-slate-900 text-base uppercase tracking-wider mb-1">BERITA ACARA SETTLEMENT BAGI HASIL 25/75</h4>
                    <p class="text-xs text-slate-500 mb-0">Periode: <strong>{{ $periodLabel }}</strong> | Tanggal Cetak: {{ date('d F Y H:i') }}</p>
                </div>

                <p class="leading-relaxed">
                    Pada hari ini telah dilakukan rekonsiliasi pembukuan dan penyelesaian hak bagi hasil lintas cabang antara:
                </p>

                <div class="grid grid-cols-2 gap-4 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">PIHAK 1 (CABANG ASAL / MITRA)</span>
                        <strong class="text-slate-900 text-sm block">{{ $branch1->nama_cabang ?? 'Cabang 1' }}</strong>
                        <span class="text-[11px] text-slate-500">{{ $branch1->alamat ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">PIHAK 2 (CABANG PELAKSANA / PRODUKSI)</span>
                        <strong class="text-slate-900 text-sm block">{{ $branch2->nama_cabang ?? 'Cabang 2' }}</strong>
                        <span class="text-[11px] text-slate-500">{{ $branch2->alamat ?? '-' }}</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <h6 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Rincian Posisi Keuangan:</h6>
                    <table class="table table-bordered table-sm text-xs">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th>Keterangan Parameter</th>
                                <th class="text-end">{{ $branch1->nama_cabang ?? 'Cabang 1' }}</th>
                                <th class="text-end">{{ $branch2->nama_cabang ?? 'Cabang 2' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Total Uang Fisik Customer Diterima (100%)</td>
                                <td class="text-end font-mono font-bold">Rp {{ number_format($b1CashIn, 0, ',', '.') }}</td>
                                <td class="text-end font-mono font-bold">Rp {{ number_format($b2CashIn, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Hak Komisi 25% (Order Sendiri)</td>
                                <td class="text-end font-mono text-emerald-700">Rp {{ number_format($b1OwnShare, 0, ',', '.') }}</td>
                                <td class="text-end font-mono text-indigo-700">Rp {{ number_format($b2OwnShare, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Hak Produksi 75% (Mengerjakan Order Mitra)</td>
                                <td class="text-end font-mono text-emerald-700">Rp {{ number_format($b1WorkShare, 0, ',', '.') }}</td>
                                <td class="text-end font-mono text-indigo-700">Rp {{ number_format($b2WorkShare, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-light font-bold">
                                <td>Total Hak Pendapatan Riil</td>
                                <td class="text-end font-mono font-black text-emerald-800">Rp {{ number_format($b1TotalRevenue, 0, ',', '.') }}</td>
                                <td class="text-end font-mono font-black text-indigo-800">Rp {{ number_format($b2TotalRevenue, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Kewajiban Setoran Kas ke Mitra (75%)</td>
                                <td class="text-end font-mono text-rose-600">Rp {{ number_format($b1OwesB2, 0, ',', '.') }}</td>
                                <td class="text-end font-mono text-rose-600">Rp {{ number_format($b2OwesB1, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Keputusan Settlement -->
                <div class="bg-slate-900 text-white p-4 rounded-xl text-center space-y-1">
                    <span class="text-[11px] text-slate-300 uppercase tracking-wider block font-bold">KEPUTUSAN NET SETTLEMENT (SALING POTONG):</span>
                    @if($settlement['is_balanced'])
                        <h5 class="text-emerald-400 font-black mb-0">SALDO SEIMBANG / TIDAK ADA TRANSFER</h5>
                    @else
                        <h5 class="text-amber-300 font-black mb-0">
                            {{ $settlement['payer']->nama_cabang }} &rarr; {{ $settlement['receiver']->nama_cabang }}
                        </h5>
                        <div class="text-xl font-black text-amber-400 font-mono">
                            Rp {{ number_format($settlement['amount'], 0, ',', '.') }}
                        </div>
                    @endif
                </div>

                <!-- Tanda Tangan -->
                <div class="grid grid-cols-3 gap-4 pt-6 text-center text-xs">
                    <div>
                        <span class="text-slate-500 block">Penanggung Jawab 1</span>
                        <div class="h-16"></div>
                        <strong class="border-t pt-1 block text-slate-900">{{ $branch1->nama_cabang ?? 'Cabang 1' }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Finance / Super Admin</span>
                        <div class="h-16"></div>
                        <strong class="border-t pt-1 block text-slate-900">{{ auth()->user()->full_name ?: auth()->user()->username }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Penanggung Jawab 2</span>
                        <div class="h-16"></div>
                        <strong class="border-t pt-1 block text-slate-900">{{ $branch2->nama_cabang ?? 'Cabang 2' }}</strong>
                    </div>
                </div>
            </div>

            <div class="px-6 py-3 bg-slate-100 border-t d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl font-bold" data-bs-dismiss="modal">Tutup</button>
                <button type="button" onclick="printBeritaAcara()" class="btn btn-sm btn-primary rounded-xl font-bold flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-print"></i>
                    <span>Cetak Lembar Berita Acara</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const b1Name = "{{ $branch1->nama_cabang ?? 'Cabang 1' }}";
    const b2Name = "{{ $branch2->nama_cabang ?? 'Cabang 2' }}";

    // Chart 1: Cash In Flow
    const ctxCash = document.getElementById('chartCashIn');
    if (ctxCash) {
        new Chart(ctxCash, {
            type: 'doughnut',
            data: {
                labels: [`Kas Fisik ${b1Name}`, `Kas Fisik ${b2Name}`],
                datasets: [{
                    data: [{{ $b1CashIn }}, {{ $b2CashIn }}],
                    backgroundColor: ['#10b981', '#6366f1'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11, family: 'Plus Jakarta Sans' } } }
                }
            }
        });
    }

    // Chart 2: Revenue Share Comparison
    const ctxRev = document.getElementById('chartRevenue');
    if (ctxRev) {
        new Chart(ctxRev, {
            type: 'bar',
            data: {
                labels: [b1Name, b2Name],
                datasets: [
                    {
                        label: 'Komisi 25% (Order Sendiri)',
                        data: [{{ $b1OwnShare }}, {{ $b2OwnShare }}],
                        backgroundColor: '#10b981'
                    },
                    {
                        label: 'Pengerjaan 75% (Order Mitra)',
                        data: [{{ $b1WorkShare }}, {{ $b2WorkShare }}],
                        backgroundColor: '#6366f1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, ticks: { font: { size: 10 } } },
                    y: { stacked: true, ticks: { font: { size: 10 } } }
                },
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11, family: 'Plus Jakarta Sans' } } }
                }
            }
        });
    }
});

function openBeritaAcaraModal() {
    const modalEl = document.getElementById('modalBeritaAcara');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function printBeritaAcara() {
    const printContent = document.getElementById('printableBeritaAcara').innerHTML;
    const originalContent = document.body.innerHTML;
    document.body.innerHTML = `<div style="padding: 20px; font-family: 'Plus Jakarta Sans', sans-serif;">${printContent}</div>`;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}
</script>
@endsection
