@extends('layouts.app')

@section('title', 'Buat Laporan Tutup Hari (Daily Closing)')
@section('page-title', 'Form Tutup Kas Harian & Rekonsiliasi Cabang')

@section('action-buttons')
<a href="{{ route('daily-closing.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 rounded-xl text-xs font-bold px-3 py-1.5">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar
</a>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-xl shadow-sm text-xs font-semibold" role="alert">
            <i class="fa-solid fa-circle-exclamation me-1.5"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 text-xs text-blue-900 d-flex items-start gap-3 shadow-xs">
        <div class="w-8 h-8 rounded-xl bg-blue-600 text-white d-flex align-items-center justify-content-center flex-shrink-0 font-bold">
            <i class="fa-solid fa-info"></i>
        </div>
        <div>
            <div class="font-bold text-sm">Mode Percobaan Laporan Tutup Hari (Dual Signature)</div>
            <p class="mb-0 text-slate-600 mt-0.5">
                Data penjualan POS, kas masuk, dan kas keluar di bawah ini dikalkulasi otomatis secara real-time dari database. 
                Saat Anda menyimpan form ini, tanda tangan digital akun Anda akan <strong>otomatis tertempel</strong> dan laporan akan diteruskan ke Owner untuk di-ACC.
            </p>
        </div>
    </div>

    <form action="{{ route('daily-closing.store') }}" method="POST" id="closing-form">
        @csrf

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Header Card -->
            <div class="p-4 bg-slate-900 text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white d-flex align-items-center justify-content-center flex-shrink-0 font-bold">
                        <i class="fa-solid fa-cash-register"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 font-extrabold tracking-tight">Rekonsiliasi Tutup Kas Cabang</h6>
                        <p class="text-xs text-slate-400 mb-0">Petugas: {{ auth()->user()->full_name ?? auth()->user()->username }} (Manager Toko)</p>
                    </div>
                </div>
                <div>
                    <span class="badge bg-slate-800 text-slate-300 border border-slate-700 text-xs px-2.5 py-1">
                        {{ \Carbon\Carbon::parse($targetDate)->format('d F Y') }}
                    </span>
                </div>
            </div>

            <div class="p-5 space-y-5">
                
                <!-- 1. Metadata Form -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Cabang Toko</label>
                        @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin())
                            <select name="branch_id" class="form-select form-select-sm text-xs font-bold" onchange="window.location.href='{{ route('daily-closing.create') }}?branch_id=' + this.value + '&closing_date=' + document.getElementById('closing_date_input').value">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ $branch && $branch->id == $b->id ? 'selected' : '' }}>
                                        {{ $b->nama_cabang }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="branch_id" value="{{ $branch->id ?? 1 }}">
                            <input type="text" class="form-control form-control-sm text-xs font-bold bg-slate-100" value="{{ $branch->nama_cabang ?? 'Pusat' }}" readonly>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Tutup Buku</label>
                        <input type="date" name="closing_date" id="closing_date_input" class="form-control form-control-sm text-xs font-bold" value="{{ $targetDate }}" onchange="window.location.href='{{ route('daily-closing.create') }}?branch_id={{ $branch->id ?? 1 }}&closing_date=' + this.value">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Jenis Shift</label>
                        <select name="shift_type" class="form-select form-select-sm text-xs font-bold">
                            <option value="Full Day">Full Day (Satu Hari Penuh)</option>
                            <option value="Shift Pagi">Shift Pagi</option>
                            <option value="Shift Malam">Shift Malam</option>
                        </select>
                    </div>
                </div>

                <hr class="border-slate-200">

                <!-- 2. Rincian Penerimaan Penjualan POS (Read-Only Autocalculated) -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-0">
                            A. Rincian Omset Penjualan POS ({{ $totalOrdersCount }} Transaksi)
                        </label>
                        <span class="text-[11px] text-slate-500 italic">Otomatis dari kasir</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="text-[10px] font-bold text-slate-500 uppercase block">💵 Penjualan Tunai (Cash)</span>
                            <div class="font-mono font-bold text-emerald-700 text-sm mt-1">
                                Rp {{ number_format($totalCashSales, 0, ',', '.') }}
                            </div>
                            <input type="hidden" name="total_cash_sales" id="total_cash_sales" value="{{ $totalCashSales }}">
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="text-[10px] font-bold text-slate-500 uppercase block">🏦 Transfer Bank</span>
                            <div class="font-mono font-bold text-blue-700 text-sm mt-1">
                                Rp {{ number_format($totalTransferSales, 0, ',', '.') }}
                            </div>
                            <input type="hidden" name="total_transfer_sales" value="{{ $totalTransferSales }}">
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="text-[10px] font-bold text-slate-500 uppercase block">📱 QRIS Statis/Dinamis</span>
                            <div class="font-mono font-bold text-indigo-700 text-sm mt-1">
                                Rp {{ number_format($totalQrisSales, 0, ',', '.') }}
                            </div>
                            <input type="hidden" name="total_qris_sales" value="{{ $totalQrisSales }}">
                        </div>

                        <div class="p-3 rounded-xl bg-blue-50 border border-blue-200">
                            <span class="text-[10px] font-bold text-blue-800 uppercase block">Total Seluruh Omset</span>
                            <div class="font-mono font-extrabold text-blue-950 text-sm mt-1">
                                Rp {{ number_format($totalSales, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Kas Masuk, Kas Keluar & Rekonsiliasi Kas Fisik -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            B. Mutasi Kas Operasional
                        </label>
                        
                        <div class="p-3 rounded-xl bg-slate-50 border space-y-2 text-xs">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-slate-600 font-semibold">Kas Awal Modal Laci:</span>
                                <div class="w-36">
                                    <input type="number" name="opening_cash" id="opening_cash" class="form-control form-control-sm text-end font-mono font-bold text-xs" value="{{ (int)$openingCash }}" min="0" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-emerald-700 font-semibold">+ Kas Masuk (Non-POS):</span>
                                <div class="w-36">
                                    <input type="number" name="total_cash_in" id="total_cash_in" class="form-control form-control-sm text-end font-mono font-bold text-xs text-emerald-700" value="{{ (int)$cashIn }}" min="0" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-rose-700 font-semibold">- Kas Keluar (Pengeluaran):</span>
                                <div class="w-36">
                                    <input type="number" name="total_cash_out" id="total_cash_out" class="form-control form-control-sm text-end font-mono font-bold text-xs text-rose-700" value="{{ (int)$cashOut }}" min="0" required>
                                </div>
                            </div>
                        </div>

                        <!-- Click Charge Meteran Mesin -->
                        <div class="p-3 rounded-xl bg-slate-50 border space-y-2 text-xs">
                            <span class="font-bold text-slate-700 d-block uppercase text-[10px]">C. Meteran Mesin (Click Charge)</span>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] text-slate-500 font-semibold">Meteran Awal</label>
                                    <input type="number" name="click_counter_start" id="click_start" class="form-control form-control-sm text-xs font-mono" placeholder="0">
                                </div>
                                <div>
                                    <label class="text-[10px] text-slate-500 font-semibold">Meteran Akhir</label>
                                    <input type="number" name="click_counter_end" id="click_end" class="form-control form-control-sm text-xs font-mono" placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rekonsiliasi Kas Laci -->
                    <div class="bg-slate-900 text-white rounded-2xl p-4 flex flex-col justify-between space-y-3">
                        <div>
                            <div class="text-xs font-bold text-amber-400 uppercase tracking-wider">
                                Rekonsiliasi Kas Fisik Laci
                            </div>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                Hitung seluruh uang tunai fisik yang ada di laci kasir malam ini.
                            </div>
                        </div>

                        <div class="space-y-2 bg-slate-800/80 p-3 rounded-xl border border-slate-700 text-xs">
                            <div class="d-flex justify-content-between text-slate-300">
                                <span>Estimasi Kas Sistem (Expected):</span>
                                <span class="font-mono font-bold text-white" id="display_expected">
                                    Rp {{ number_format($expectedCash, 0, ',', '.') }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-2 border-t border-slate-700">
                                <span class="font-bold text-emerald-400">Kas Fisik Aktual (Di Laci):</span>
                                <div class="w-40">
                                    <input type="number" name="actual_cash" id="actual_cash" class="form-control form-control-sm text-end font-mono font-bold text-sm bg-slate-950 text-emerald-300 border-emerald-500" value="{{ (int)$expectedCash }}" min="0" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between text-xs pt-1">
                                <span class="text-slate-400">Status Selisih (Difference):</span>
                                <span class="font-mono font-bold text-amber-400" id="display_diff">
                                    Rp 0 (Pas)
                                </span>
                            </div>
                        </div>

                        <!-- Catatan Produksi -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 mb-1">Catatan Manager & Kendala Produksi</label>
                            <textarea name="production_notes" class="form-control form-control-sm text-xs bg-slate-800 text-white border-slate-700" rows="2" placeholder="Catatan closing kasir, kendala mesin cetak, atau status pesanan tertunda..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- 4. Digital Signature Preview -->
                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl d-flex align-items-center justify-content-between text-xs">
                    <div class="d-flex align-items-center gap-2.5">
                        <i class="fa-solid fa-signature text-emerald-700 text-xl"></i>
                        <div>
                            <div class="font-bold text-emerald-950">Tanda Tangan Digital Manager Otomatis</div>
                            <div class="text-[11px] text-emerald-700">Tertempel atas nama {{ auth()->user()->full_name ?? auth()->user()->username }}</div>
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-emerald-600 text-white text-[10px] px-2 py-1 font-bold rounded-lg">
                            <i class="fa-solid fa-check me-1"></i> Siap Disubmit
                        </span>
                    </div>
                </div>

            </div>

            <!-- Card Footer -->
            <div class="p-4 bg-slate-100 border-t d-flex justify-content-between align-items-center">
                <a href="{{ route('daily-closing.index') }}" class="btn btn-sm btn-outline-secondary rounded-xl text-xs font-semibold px-4 py-2">
                    Batal
                </a>
                <button type="submit" class="btn btn-sm btn-primary rounded-xl text-xs font-bold px-4 py-2 shadow-sm d-inline-flex align-items-center gap-1.5" style="background-color: #1e40af; border-color: #1e40af;">
                    <i class="fa-solid fa-paper-plane"></i> Submit & Tandatangani Laporan Tutup Hari
                </button>
            </div>

        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const openingInput = document.getElementById('opening_cash');
    const cashSales = parseFloat(document.getElementById('total_cash_sales').value) || 0;
    const cashInInput = document.getElementById('total_cash_in');
    const cashOutInput = document.getElementById('total_cash_out');
    const actualInput = document.getElementById('actual_cash');
    const displayExpected = document.getElementById('display_expected');
    const displayDiff = document.getElementById('display_diff');

    function calc() {
        const opening = parseFloat(openingInput.value) || 0;
        const cashIn = parseFloat(cashInInput.value) || 0;
        const cashOut = parseFloat(cashOutInput.value) || 0;
        const actual = parseFloat(actualInput.value) || 0;

        const expected = opening + cashSales + cashIn - cashOut;
        const diff = actual - expected;

        displayExpected.innerText = 'Rp ' + expected.toLocaleString('id-ID');
        if (diff === 0) {
            displayDiff.innerText = 'Rp 0 (Pas)';
            displayDiff.className = 'font-mono font-bold text-emerald-400';
        } else if (diff > 0) {
            displayDiff.innerText = '+ Rp ' + diff.toLocaleString('id-ID') + ' (Lebih)';
            displayDiff.className = 'font-mono font-bold text-blue-400';
        } else {
            displayDiff.innerText = '- Rp ' + Math.abs(diff).toLocaleString('id-ID') + ' (Kurang)';
            displayDiff.className = 'font-mono font-bold text-rose-400';
        }
    }

    openingInput.addEventListener('input', calc);
    cashInInput.addEventListener('input', calc);
    cashOutInput.addEventListener('input', calc);
    actualInput.addEventListener('input', calc);
});
</script>
@endsection
