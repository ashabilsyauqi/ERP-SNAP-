@extends('layouts.app')

@section('title', 'Laporan Tutup Hari #' . $report->report_number)
@section('page-title', 'Berita Acara Tutup Hari (Dual Signature)')

@section('action-buttons')
<a href="{{ route('daily-closing.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 rounded-xl text-xs font-bold px-3 py-1.5">
    <i class="fa-solid fa-arrow-left"></i> Kembali
</a>
<button type="button" onclick="window.print()" class="btn btn-sm btn-outline-dark d-inline-flex align-items-center gap-1.5 rounded-xl text-xs font-bold px-3 py-1.5">
    <i class="fa-solid fa-print"></i> Cetak Laporan
</button>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-xl shadow-sm text-xs font-semibold" role="alert">
            <i class="fa-solid fa-circle-check me-1.5"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" id="printable-report">
        
        <!-- Header Berita Acara -->
        <div class="p-5 border-b bg-slate-50 d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-slate-900 text-white d-flex align-items-center justify-content-center font-bold text-xl flex-shrink-0">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
                <div>
                    <h5 class="mb-0 font-extrabold text-slate-900 font-mono tracking-tight">{{ $report->report_number }}</h5>
                    <div class="text-xs text-slate-500 font-semibold mt-0.5">
                        BERITA ACARA TUTUP HARI & REKONSILIASI KAS CABANG
                    </div>
                </div>
            </div>
            <div class="text-end">
                @if($report->isVerified())
                    <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs px-3 py-1.5 font-bold rounded-xl">
                        <i class="fa-solid fa-check-double me-1"></i> STATUS: TERVERIFIKASI OWNER
                    </span>
                @else
                    <span class="badge bg-amber-100 text-amber-800 border border-amber-300 text-xs px-3 py-1.5 font-bold rounded-xl animate-pulse">
                        <i class="fa-solid fa-clock me-1"></i> STATUS: MENUNGGU ACC OWNER
                    </span>
                @endif
                <div class="text-[11px] text-slate-400 mt-1 font-mono">
                    {{ $report->created_at->format('d M Y, H:i') }} WIB
                </div>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="p-5 space-y-5">
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-slate-50 border">
                    <span class="text-slate-400 font-bold uppercase text-[10px] block">Cabang Toko</span>
                    <span class="font-extrabold text-slate-800 text-sm mt-0.5 block">{{ $report->branch->nama_cabang ?? 'Pusat' }}</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border">
                    <span class="text-slate-400 font-bold uppercase text-[10px] block">Tanggal Tutup</span>
                    <span class="font-extrabold text-slate-800 text-sm mt-0.5 block">{{ $report->closing_date->format('d F Y') }}</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border">
                    <span class="text-slate-400 font-bold uppercase text-[10px] block">Shift Operasional</span>
                    <span class="font-extrabold text-slate-800 text-sm mt-0.5 block">{{ $report->shift_type }}</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border">
                    <span class="text-slate-400 font-bold uppercase text-[10px] block">Manager Penginput</span>
                    <span class="font-extrabold text-slate-800 text-sm mt-0.5 block">{{ $report->manager->full_name ?? $report->manager->username }}</span>
                </div>
            </div>

            <!-- Financial Table -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Sisi Penjualan -->
                <div class="border rounded-xl overflow-hidden">
                    <div class="bg-slate-100 p-2.5 font-bold text-xs text-slate-700 border-b">
                        1. Ringkasan Omset Penjualan POS ({{ $report->total_orders_count }} Trx)
                    </div>
                    <table class="table table-sm mb-0 text-xs">
                        <tbody>
                            <tr>
                                <td class="ps-3 py-2 text-slate-600">💵 Penjualan Tunai (Cash)</td>
                                <td class="pe-3 py-2 text-end font-mono font-bold text-emerald-700">Rp {{ number_format($report->total_cash_sales, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3 py-2 text-slate-600">🏦 Transfer Bank</td>
                                <td class="pe-3 py-2 text-end font-mono font-bold text-blue-700">Rp {{ number_format($report->total_transfer_sales, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3 py-2 text-slate-600">📱 QRIS Statis/Dinamis</td>
                                <td class="pe-3 py-2 text-end font-mono font-bold text-indigo-700">Rp {{ number_format($report->total_qris_sales, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="bg-blue-50 border-t font-bold">
                                <td class="ps-3 py-2 text-blue-900">Total Omset Hari Ini</td>
                                <td class="pe-3 py-2 text-end font-mono text-blue-950 fs-6">Rp {{ number_format($report->total_sales, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Sisi Kas Fisik -->
                <div class="border rounded-xl overflow-hidden">
                    <div class="bg-slate-100 p-2.5 font-bold text-xs text-slate-700 border-b">
                        2. Rekonsiliasi Kas Laci & Selisih
                    </div>
                    <table class="table table-sm mb-0 text-xs">
                        <tbody>
                            <tr>
                                <td class="ps-3 py-2 text-slate-600">Kas Awal Modal Laci</td>
                                <td class="pe-3 py-2 text-end font-mono font-semibold">Rp {{ number_format($report->opening_cash, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3 py-2 text-slate-600">+ Kas Masuk Non-POS</td>
                                <td class="pe-3 py-2 text-end font-mono text-emerald-700 font-semibold">+ Rp {{ number_format($report->total_cash_in, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3 py-2 text-slate-600">- Kas Keluar Operasional</td>
                                <td class="pe-3 py-2 text-end font-mono text-rose-700 font-semibold">- Rp {{ number_format($report->total_cash_out, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="border-t">
                                <td class="ps-3 py-2 text-slate-800 font-bold">Estimasi Kas Sistem (Expected)</td>
                                <td class="pe-3 py-2 text-end font-mono font-bold text-slate-900">Rp {{ number_format($report->expected_cash, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="bg-emerald-50 font-bold">
                                <td class="ps-3 py-2 text-emerald-900">Kas Fisik Aktual di Laci</td>
                                <td class="pe-3 py-2 text-end font-mono text-emerald-900 fs-6">Rp {{ number_format($report->actual_cash, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="border-t">
                                <td class="ps-3 py-2 font-bold">Status Selisih Kas</td>
                                <td class="pe-3 py-2 text-end font-mono font-bold">
                                    @if($report->cash_difference == 0)
                                        <span class="badge bg-emerald-100 text-emerald-800 text-[10px]">Rp 0 (PAS)</span>
                                    @elseif($report->cash_difference > 0)
                                        <span class="badge bg-blue-100 text-blue-800 text-[10px]">+ Rp {{ number_format($report->cash_difference, 0, ',', '.') }} (LEBIH)</span>
                                    @else
                                        <span class="badge bg-rose-100 text-rose-800 text-[10px]">- Rp {{ number_format(abs($report->cash_difference), 0, ',', '.') }} (KURANG)</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Meteran Mesin & Catatan Produksi -->
            @if($report->click_count_total !== null || $report->production_notes)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    @if($report->click_count_total !== null)
                        <div class="p-3 bg-slate-50 border rounded-xl">
                            <span class="font-bold text-slate-700 uppercase text-[10px] block mb-1">Meteran Mesin (Click Charge)</span>
                            <div class="d-flex justify-content-between text-slate-600">
                                <span>Awal: <strong class="font-mono">{{ number_format($report->click_counter_start) }}</strong></span>
                                <span>Akhir: <strong class="font-mono">{{ number_format($report->click_counter_end) }}</strong></span>
                                <span class="text-blue-900 font-bold">Total: <strong class="font-mono">{{ number_format($report->click_count_total) }} Klik</strong></span>
                            </div>
                        </div>
                    @endif

                    @if($report->production_notes)
                        <div class="p-3 bg-slate-50 border rounded-xl {{ $report->click_count_total === null ? 'col-span-2' : '' }}">
                            <span class="font-bold text-slate-700 uppercase text-[10px] block mb-1">Catatan Manager & Kendala</span>
                            <p class="mb-0 text-slate-600 italic">"{{ $report->production_notes }}"</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- DUAL DIGITAL SIGNATURE BOX -->
            <div class="border rounded-2xl p-4 bg-slate-50 mt-4">
                <div class="text-center font-bold text-xs text-slate-700 uppercase tracking-wider mb-4">
                    LEMBAR TANDA TANGAN DIGITAL BERITA ACARA TUTUP HARI
                </div>

                <div class="grid grid-cols-2 gap-6 text-center">
                    
                    <!-- 1. Manager Toko Signature -->
                    <div class="p-4 bg-white rounded-xl border d-flex flex-col items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Dibuat & Ditandatangani Oleh:</span>
                        
                        <div class="my-3 h-20 d-flex align-items-center justify-content-center">
                            @if($report->manager && $report->manager->signature_path)
                                <img src="{{ asset('storage/' . $report->manager->signature_path) }}" alt="Tanda Tangan Manager" style="max-height: 70px; max-width: 140px; object-fit: contain;">
                            @else
                                <div class="px-3 py-1.5 border border-emerald-500 bg-emerald-50 text-emerald-800 rounded-lg text-xs font-mono font-bold">
                                    <i class="fa-solid fa-signature me-1"></i> DIGITALLY SIGNED
                                </div>
                            @endif
                        </div>

                        <div>
                            <div class="font-bold text-xs text-slate-900">{{ $report->manager->full_name ?? $report->manager->username }}</div>
                            <div class="text-[10px] text-slate-500">Manager Toko {{ $report->branch->nama_cabang ?? '' }}</div>
                            @if($report->manager_signed_at)
                                <div class="text-[9px] text-emerald-700 font-mono mt-0.5">{{ $report->manager_signed_at->format('d/m/Y H:i') }} WIB</div>
                            @endif
                        </div>
                    </div>

                    <!-- 2. Owner Signature -->
                    <div class="p-4 bg-white rounded-xl border d-flex flex-col items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Diterima & Diverifikasi Oleh:</span>
                        
                        <div class="my-3 h-20 d-flex align-items-center justify-content-center">
                            @if($report->isVerified())
                                @if($report->owner && $report->owner->signature_path)
                                    <img src="{{ asset('storage/' . $report->owner->signature_path) }}" alt="Tanda Tangan Owner" style="max-height: 70px; max-width: 140px; object-fit: contain;">
                                @else
                                    <div class="px-3 py-1.5 border border-blue-500 bg-blue-50 text-blue-800 rounded-lg text-xs font-mono font-bold">
                                        <i class="fa-solid fa-stamp me-1"></i> VERIFIED BY OWNER
                                    </div>
                                @endif
                            @else
                                <div class="text-slate-400 text-xs italic">
                                    (Menunggu Persetujuan Owner)
                                </div>
                            @endif
                        </div>

                        <div>
                            @if($report->isVerified())
                                <div class="font-bold text-xs text-slate-900">{{ $report->owner->full_name ?? $report->owner->username }}</div>
                                <div class="text-[10px] text-slate-500">Owner Snaprint</div>
                                @if($report->owner_signed_at)
                                    <div class="text-[9px] text-blue-700 font-mono mt-0.5">{{ $report->owner_signed_at->format('d/m/Y H:i') }} WIB</div>
                                @endif
                            @else
                                <div class="text-xs font-bold text-amber-700">Belum Ditandatangani</div>
                                <div class="text-[10px] text-slate-400">Owner Snaprint</div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            <!-- Owner Verification Action Form -->
            @if(!$report->isVerified() && (auth()->user()->isOwner() || auth()->user()->isSuperAdmin()))
                <div class="bg-amber-50 border border-amber-300 rounded-2xl p-4 space-y-3">
                    <div class="d-flex align-items-center gap-2 text-amber-900 font-bold text-xs">
                        <i class="fa-solid fa-crown text-amber-600"></i>
                        <span>Persetujuan & Verifikasi Dokumen oleh Owner:</span>
                    </div>

                    <form action="{{ route('daily-closing.verify', $report->id) }}" method="POST">
                        @csrf
                        <div class="space-y-2">
                            <textarea name="owner_notes" class="form-control text-xs" rows="2" placeholder="Catatan verifikasi Owner (opsional)..."></textarea>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-sm btn-success rounded-xl text-xs font-bold px-4 py-2 d-inline-flex align-items-center gap-1.5 shadow-sm" onclick="return confirm('Verifikasi dan tandatangani penerimaan Laporan Tutup Hari ini?');">
                                    <i class="fa-solid fa-check-double"></i> Verifikasi & Bubuhkan Tanda Tangan Owner (ACC)
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif

        </div>

    </div>

</div>
@endsection
