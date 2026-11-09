@extends('layouts.app')

@section('title', 'Banner Production & Roll Allocator')
@section('page-title', 'Workstation Cetak Banner & Pengelolaan Roll')

@section('action-buttons')
<div class="d-flex align-items-center gap-2">
    @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin())
        <form method="GET" action="{{ route('production.banner.index') }}" class="d-inline-flex align-items-center gap-1.5 me-2">
            @if(request('material_id'))
                <input type="hidden" name="material_id" value="{{ request('material_id') }}">
            @endif
            <label class="text-xs font-semibold text-slate-500 mb-0"><i class="fa-solid fa-shop me-1"></i> Cabang:</label>
            <select name="branch_id" class="form-select form-select-sm text-xs rounded-xl shadow-xs border-slate-300 font-semibold" onchange="this.form.submit()">
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->nama_cabang }}</option>
                @endforeach
            </select>
        </form>
    @endif

    <a href="{{ route('pos.index') }}" class="btn-odoo-secondary text-decoration-none d-inline-flex align-items-center gap-1.5">
        <i class="fa-solid fa-cash-register"></i>
        <span>Buka Kasir POS</span>
    </a>
    <a href="{{ route('stock.index') }}" class="btn-odoo-secondary text-decoration-none d-inline-flex align-items-center gap-1.5">
        <i class="fa-solid fa-boxes-stacked"></i>
        <span>Menu Stok & Gudang</span>
    </a>
</div>
@endsection

@section('content')
<div class="space-y-5 pb-12">

    <!-- TOP KPI SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Antrean Cetak Menunggu -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs d-flex align-items-center justify-content-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700 block mb-0.5">Antrean Menunggu Cetak</span>
                <h4 class="text-2xl font-black text-slate-800 mb-0">{{ $pendingCountTotal }} <span class="text-xs font-normal text-slate-500">Order</span></h4>
                <span class="text-[10px] text-slate-400">Order kasir berstatus PAID / DP</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 d-flex align-items-center justify-content-center text-lg shadow-inner">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>

        <!-- Siap Diambil di Kasir -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs d-flex align-items-center justify-content-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700 block mb-0.5">Siap Diantar / Ambil</span>
                <h4 class="text-2xl font-black text-slate-800 mb-0">{{ $readyForPickupCount }} <span class="text-xs font-normal text-slate-500">Order</span></h4>
                <span class="text-[10px] text-slate-400">Selesai cetak, siap closing kasir</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 d-flex align-items-center justify-content-center text-lg shadow-inner">
                <i class="fa-solid fa-box-open"></i>
            </div>
        </div>

        <!-- Total Selesai Dicetak Hari Ini -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs d-flex align-items-center justify-content-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 block mb-0.5">Selesai Hari Ini</span>
                <h4 class="text-2xl font-black text-slate-800 mb-0">{{ $totalPrintedTodayCount }} <span class="text-xs font-normal text-slate-500">Item</span></h4>
                <span class="text-[10px] text-slate-400">Tercetak & stok terpotong</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 d-flex align-items-center justify-content-center text-lg shadow-inner">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <!-- Indikator Sinkronisasi Stok ERP -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs d-flex align-items-center justify-content-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700 block mb-0.5">Luas Fisik Roll Aktif</span>
                <h4 class="text-2xl font-black text-slate-800 mb-0 font-mono">{{ number_format($totalPhysicalRollArea, 1) }} <span class="text-xs font-normal text-slate-500">m²</span></h4>
                <span class="text-[10px] text-slate-500">
                    ERP Stock: <strong class="font-mono text-indigo-900">{{ number_format($erpStockArea, 0) }} m²</strong>
                </span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 d-flex align-items-center justify-content-center text-lg shadow-inner">
                <i class="fa-solid fa-scroll"></i>
            </div>
        </div>
    </div>

    <!-- SECTION 1: REAL STOCK GULUNGAN ROLL MEDIA -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-slate-50/60 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h6 class="font-black text-slate-900 text-sm mb-0.5 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-tape text-blue-600"></i>
                    <span>Real Stock Gulungan Roll Media Fisik</span>
                </h6>
                <p class="text-xs text-slate-500 mb-0">
                    Sisa panjang gulungan roll di gudang produksi & perbandingan sinkronisasi luas ($m^2$) dengan Master Stok ERP.
                </p>
            </div>

            <!-- Material Tabs / Pills -->
            <div class="d-flex align-items-center gap-1.5 flex-wrap">
                @foreach($bannerMaterials as $bm)
                    <a href="{{ route('production.banner.index', ['material_id' => $bm->id, 'branch_id' => $branchId]) }}" 
                       class="btn btn-xs rounded-xl font-bold px-3 py-1.5 text-xs transition {{ $selectedMaterialId == $bm->id ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
                        {{ $bm->material_name }}
                        <span class="badge {{ $selectedMaterialId == $bm->id ? 'bg-slate-700 text-slate-200' : 'bg-slate-100 text-slate-600' }} ms-1">
                            {{ $bm->rolls->count() }} Roll
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="p-4 bg-white">
            @if(!$activeMaterial)
                <div class="text-center py-6 text-slate-400">
                    <i class="fa-solid fa-box-open text-3xl mb-2 opacity-50"></i>
                    <p class="text-xs mb-0">Belum ada bahan banner yang terdaftar di cabang ini.</p>
                </div>
            @else
                <!-- Active Material Header Stats -->
                <div class="p-3 mb-4 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 text-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-300 d-flex align-items-center justify-content-center text-lg">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div>
                            <span class="text-[10px] text-blue-300 uppercase tracking-widest font-bold">Bahan Terpilih:</span>
                            <h5 class="text-base font-black mb-0 text-white">{{ $activeMaterial->material_name }}</h5>
                            <span class="text-[11px] text-slate-300">Kategori: {{ $activeMaterial->category ?: 'Cetak Outdoor/Indoor' }} &bull; Cabang: {{ $branch->nama_cabang }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-4">
                        <div class="text-end">
                            <span class="text-[10px] text-slate-300 uppercase block">Total Luas Fisik Roll:</span>
                            <strong class="text-sm font-mono text-emerald-400">{{ number_format($totalPhysicalRollArea, 2) }} m²</strong>
                        </div>
                        <div class="border-s border-slate-700 ps-4 text-end">
                            <span class="text-[10px] text-slate-300 uppercase block">Stok Luas ERP:</span>
                            <strong class="text-sm font-mono text-cyan-300">{{ number_format($erpStockArea, 0) }} m²</strong>
                        </div>
                        <div class="border-s border-slate-700 ps-4 text-center">
                            @if(abs($syncDifference) <= 5)
                                <span class="badge bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 text-[10px] font-bold px-2 py-1 rounded-lg">
                                    <i class="fa-solid fa-check-double me-1"></i> SINKRON
                                </span>
                            @else
                                <span class="badge bg-amber-500/20 text-amber-300 border border-amber-500/40 text-[10px] font-bold px-2 py-1 rounded-lg" title="Selisih luas fisik roll vs stok ERP: {{ $syncDifference }} m²">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Selisih {{ $syncDifference }} m²
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Roll Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @forelse($activeRolls as $roll)
                        @php
                            $initLen = max(1, (float)$roll->initial_length_m);
                            $currLen = (float)$roll->current_length_m;
                            $pct = round(($currLen / $initLen) * 100);
                            $rollArea = round($currLen * (float)$roll->roll_width_m, 2);
                            $isLow = $currLen <= 15;
                            $isCritical = $currLen <= 5;
                        @endphp
                        <div class="p-3.5 rounded-2xl border transition-all duration-200 {{ $isCritical ? 'bg-rose-50/50 border-rose-300' : ($isLow ? 'bg-amber-50/50 border-amber-300' : 'bg-slate-50/60 border-slate-200 hover:border-blue-400') }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="w-8 h-8 rounded-xl {{ $isCritical ? 'bg-rose-100 text-rose-700' : ($isLow ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') }} d-flex align-items-center justify-content-center text-xs font-bold">
                                        <i class="fa-solid fa-scroll"></i>
                                    </div>
                                    <div>
                                        <h6 class="font-extrabold text-xs text-slate-800 mb-0">{{ $roll->roll_name }}</h6>
                                        <span class="text-[10px] font-mono text-slate-500">Lebar: <strong>{{ $roll->roll_width_m }}m</strong></span>
                                    </div>
                                </div>
                                @if($isCritical)
                                    <span class="badge bg-rose-600 text-white text-[9px] font-bold">KRITIS</span>
                                @elseif($isLow)
                                    <span class="badge bg-amber-500 text-white text-[9px] font-bold">MENIPIS</span>
                                @else
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-bold">AKTIF</span>
                                @endif
                            </div>

                            <!-- Visual Roll Length Progress Bar -->
                            <div class="space-y-1 mb-2">
                                <div class="d-flex justify-content-between text-[11px] font-mono font-bold">
                                    <span class="text-slate-700">Sisa: <strong class="{{ $isCritical ? 'text-rose-700' : 'text-blue-700' }}">{{ $currLen }} m</strong></span>
                                    <span class="text-slate-400">/ {{ $initLen }} m</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                    <div class="h-2 rounded-full transition-all duration-500 {{ $isCritical ? 'bg-rose-600' : ($isLow ? 'bg-amber-500' : 'bg-blue-600') }}" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-200/60 d-flex justify-content-between align-items-center text-[11px]">
                                <span class="text-slate-500">Luas Tersedia:</span>
                                <strong class="font-mono text-slate-800 font-extrabold">{{ $rollArea }} m²</strong>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-12 text-center py-6 text-slate-400 text-xs">
                            Belum ada gulungan roll terdaftar untuk bahan ini.
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>

    <!-- SECTION 2: ANTREAN ORDER CETAK BANNER (SPK PRODUKSI) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-slate-50/60 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h6 class="font-black text-slate-900 text-sm mb-0.5 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-list-check text-amber-600"></i>
                    <span>Antrean Order Cetak Banner Masuk (Status Bayar: PAID / DP)</span>
                </h6>
                <p class="text-xs text-slate-500 mb-0">
                    Orderan pelanggan dari kasir yang siap diproduksi. Centang beberapa pesanan untuk digabung (*Join / Nesting*) ke dalam satu tarikan roll.
                </p>
            </div>

            <!-- Action: Batch Print Selected Orders -->
            <div>
                <button type="button" class="btn text-white text-xs px-3.5 py-2 rounded-xl font-bold d-inline-flex align-items-center gap-2 shadow-sm" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border: none;" onclick="openJoinPrintModal()">
                    <i class="fa-solid fa-print"></i>
                    <span>Proses Cetak Order Terpilih (<span id="selectedCountBadge">0</span>)</span>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-xs">
                <thead>
                    <tr class="bg-slate-100/70 text-slate-600 uppercase text-[10px] font-bold border-b border-slate-200">
                        <th class="ps-4 py-2.5 text-center" style="width: 40px;">
                            <input type="checkbox" id="selectAllCheckbox" class="form-check-input cursor-pointer" onchange="toggleSelectAll(this)">
                        </th>
                        <th class="py-2.5">No. Invoice & Tanggal</th>
                        <th class="py-2.5">Pelanggan</th>
                        <th class="py-2.5">Bahan Cetak</th>
                        <th class="py-2.5 text-center">Ukuran Cetak</th>
                        <th class="py-2.5 text-center">Qty</th>
                        <th class="py-2.5 text-center">Luas Total</th>
                        <th class="py-2.5">Finishing & Catatan</th>
                        <th class="py-2.5 text-center">Status Bayar</th>
                        <th class="pe-4 py-2.5 text-center" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pendingQueue as $item)
                        @php
                            $tx = $item->transaction;
                            $dimText = $item->dimension_text ?: ($item->fixed_length_m . 'm x ' . round($item->custom_width_cm/100, 2) . 'm');
                            $itemArea = (float)($item->area_m2 ?: 1.0);
                            $totalArea = round($itemArea * (int)$item->qty_ordered, 2);
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="ps-4 text-center">
                                <input type="checkbox" class="form-check-input order-checkbox cursor-pointer" 
                                       value="{{ $item->id }}" 
                                       data-material-id="{{ $item->material_id }}"
                                       data-material-name="{{ $item->material->material_name ?? '-' }}"
                                       data-invoice="{{ $tx->invoice_number }}"
                                       data-customer="{{ $tx->customer_name }}"
                                       data-dim="{{ $dimText }}"
                                       data-qty="{{ $item->qty_ordered }}"
                                       data-area="{{ $totalArea }}"
                                       onchange="onOrderCheckboxChange()">
                            </td>
                            <td>
                                <div class="font-mono font-bold text-slate-900">{{ $tx->invoice_number }}</div>
                                <span class="text-[10px] text-slate-400">{{ $tx->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td>
                                <div class="font-bold text-slate-800">{{ $tx->customer_name ?: 'Pelanggan Umum' }}</div>
                                @if($tx->customer_phone)
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $tx->customer_phone }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-slate-100 text-slate-800 border text-[11px] font-semibold">
                                    {{ $item->material->material_name ?? '-' }}
                                </span>
                            </td>
                            <td class="text-center font-mono font-bold text-blue-700">
                                {{ $dimText }}
                            </td>
                            <td class="text-center font-bold text-slate-700">
                                {{ $item->qty_ordered }}x
                            </td>
                            <td class="text-center font-mono font-bold text-emerald-700">
                                {{ $totalArea }} m²
                            </td>
                            <td>
                                <div class="text-[11px] text-slate-600">{{ $item->finishing ?: '-' }}</div>
                                @if($tx->production_notes)
                                    <div class="text-[10px] text-amber-700 font-semibold"><i class="fa-solid fa-note-sticky me-1"></i>{{ $tx->production_notes }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($tx->payment_status === 'PAID')
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-300 font-bold px-2 py-1 text-[10px]">
                                        <i class="fa-solid fa-check me-0.5"></i> LUNAS
                                    </span>
                                @elseif($tx->payment_status === 'PARTIAL')
                                    <span class="badge bg-amber-50 text-amber-800 border border-amber-300 font-bold px-2 py-1 text-[10px]" title="DP: Rp {{ number_format($tx->paid_amount) }} | Sisa: Rp {{ number_format($tx->remaining_amount) }}">
                                        DP (Sisa Rp {{ number_format($tx->remaining_amount, 0, ',', '.') }})
                                    </span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-600 text-[10px]">{{ $tx->payment_status }}</span>
                                @endif
                            </td>
                            <td class="pe-4 text-center">
                                <button type="button" class="btn btn-xs rounded-xl font-bold px-3 py-1.5 text-white shadow-xs" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none;" onclick="openSinglePrintModal({{ $item->id }})">
                                    <i class="fa-solid fa-print me-1"></i> Cetak
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-10 text-slate-400">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 d-flex align-items-center justify-content-center mx-auto mb-2 text-xl">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                </div>
                                <h6 class="font-bold text-slate-700 text-xs mb-0.5">Tidak Ada Antrean Cetak</h6>
                                <p class="text-[11px] mb-0">Semua order banner yang telah dibayar kasir telah selesai diproduksi.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECTION 3: SIAP DIANTAR KE KASIR (READY FOR PICKUP / HANDOVER) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-slate-50/60 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="font-black text-slate-900 text-sm mb-0.5 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-box-open text-blue-600"></i>
                    <span>Pesanan Selesai Cetak — Siap Diantar ke Meja Kasir / Serah Terima Pelanggan</span>
                </h6>
                <p class="text-xs text-slate-500 mb-0">
                    Fisik banner sudah selesai dicetak. Bawa banner ke meja kasir untuk diserahkan ke pelanggan dan lakukan closing transaksi.
                </p>
            </div>
            <span class="badge bg-blue-50 text-blue-700 border border-blue-200 font-bold px-2.5 py-1 text-xs rounded-xl">
                {{ $readyForPickupCount }} Siap Serah Terima
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-xs">
                <thead>
                    <tr class="bg-slate-100/70 text-slate-600 uppercase text-[10px] font-bold border-b border-slate-200">
                        <th class="ps-4 py-2.5">No. Invoice</th>
                        <th class="py-2.5">Pelanggan</th>
                        <th class="py-2.5">Rincian Item Banner</th>
                        <th class="py-2.5 text-center">Status Pembayaran</th>
                        <th class="py-2.5 text-end">Sisa Tagihan (Rp)</th>
                        <th class="pe-4 py-2.5 text-center" style="width: 220px;">Aksi Closing Kasir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($readyForPickup as $rTx)
                        <tr>
                            <td class="ps-4">
                                <span class="font-mono font-black text-slate-900">{{ $rTx->invoice_number }}</span>
                                <div class="text-[10px] text-slate-400">{{ $rTx->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <strong class="text-slate-800">{{ $rTx->customer_name }}</strong>
                                @if($rTx->customer_phone)
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $rTx->customer_phone }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="space-y-1">
                                    @foreach($rTx->transactionDetails as $d)
                                        <div class="d-flex align-items-center gap-1.5 text-[11px]">
                                            <span class="badge bg-slate-100 text-slate-700 border text-[10px]">{{ $d->material->material_name ?? '-' }}</span>
                                            <span class="font-mono text-blue-700 font-bold">{{ $d->dimension_text ?: ($d->fixed_length_m . 'x' . round($d->custom_width_cm/100, 2) . 'm') }}</span>
                                            <span class="text-slate-400">({{ $d->qty_ordered }}x)</span>
                                            @if($d->materialRoll)
                                                <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-bold">
                                                    <i class="fa-solid fa-scroll me-0.5"></i> {{ $d->materialRoll->roll_name }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-center">
                                @if($rTx->payment_status === 'PAID')
                                    <span class="badge bg-emerald-100 text-emerald-800 font-bold px-2 py-1 text-[10px]">
                                        <i class="fa-solid fa-check-circle me-1"></i> LUNAS
                                    </span>
                                @elseif($rTx->payment_status === 'PARTIAL')
                                    <span class="badge bg-amber-100 text-amber-900 font-bold px-2 py-1 text-[10px]">
                                        <i class="fa-solid fa-hand-holding-dollar me-1"></i> BELUM LUNAS (DP)
                                    </span>
                                @endif
                            </td>
                            <td class="text-end font-mono font-bold {{ (float)$rTx->remaining_amount > 0 ? 'text-rose-700' : 'text-slate-400' }}">
                                Rp {{ number_format($rTx->remaining_amount, 0, ',', '.') }}
                            </td>
                            <td class="pe-4 text-center">
                                @if($rTx->payment_status === 'PARTIAL' && (float)$rTx->remaining_amount > 0)
                                    <a href="{{ route('sales.index', ['search' => $rTx->invoice_number]) }}" class="btn btn-xs btn-outline-amber rounded-xl font-bold px-2.5 py-1 text-xs">
                                        <i class="fa-solid fa-cash-register me-1"></i> Lunasi di Kasir
                                    </a>
                                @else
                                    <form action="{{ route('production.banner.handover', $rTx->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Konfirmasi: Fisik banner sudah diserahkan ke pelanggan dan transaksi ditutup (CLOSED)?');">
                                        @csrf
                                        <button type="submit" class="btn btn-xs rounded-xl font-bold px-3 py-1.5 text-white shadow-xs" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                                            <i class="fa-solid fa-handshake me-1"></i> Serahkan & Closing
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-6 text-slate-400 text-xs">
                                Belum ada pesanan yang selesai cetak menunggu serah terima kasir.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL INTERAKTIF: ALOKASI GULUNGAN ROLL & JOIN CETAK (NESTING) -->
<div class="modal fade" id="modalExecuteBannerPrint" tabindex="-1" aria-labelledby="modalExecuteBannerPrintLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="modal-header text-white p-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-blue-500/20 text-blue-300 d-flex align-items-center justify-content-center text-lg">
                        <i class="fa-solid fa-print"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-extrabold text-sm mb-0 tracking-wide text-white" id="modalExecuteBannerPrintLabel">
                            ALOKASI GULUNGAN ROLL & EKSEKUSI CETAK MESIN
                        </h5>
                        <p class="text-[11px] text-slate-300 mb-0 mt-0.5">
                            Pilih gulungan roll fisik yang digunakan untuk mencetak orderan ini (mendukung join layout).
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('production.banner.execute') }}" method="POST" id="formExecuteBannerPrint">
                @csrf
                <div id="hiddenDetailIdsContainer"></div>

                <div class="modal-body p-4 space-y-4">
                    
                    <!-- Rincian Order Terpilih -->
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                        <span class="text-[10px] font-bold uppercase text-slate-500 block mb-1">Rincian Orderan yang Akan Dicetak:</span>
                        <div id="modalSelectedOrdersList" class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                            <!-- Populated via JS -->
                        </div>
                        <div class="mt-2 pt-2 border-t border-slate-200 d-flex justify-content-between align-items-center text-xs">
                            <span class="text-slate-600 font-semibold">Total Luas Pesanan Pelanggan:</span>
                            <strong id="modalTotalOrderArea" class="font-mono text-blue-900 font-black text-sm">0.00 m²</strong>
                        </div>
                    </div>

                    <!-- Pilih Gulungan Roll Fisik -->
                    <div>
                        <label class="form-label font-bold text-slate-800 text-xs uppercase mb-1.5 d-flex justify-content-between align-items-center">
                            <span>1. Pilih Gulungan Roll Fisik yang Digunakan <span class="text-rose-600">*</span></span>
                            <span class="text-[10px] text-slate-500 font-normal">Pilih roll yang terpasang di mesin</span>
                        </label>
                        <div id="modalRollOptionsList" class="space-y-2">
                            @foreach($activeRolls as $idx => $r)
                                <label class="d-flex align-items-center justify-content-between p-3 rounded-2xl border cursor-pointer hover:border-blue-400 transition {{ $idx === 0 ? 'bg-blue-50/50 border-blue-400' : 'bg-white border-slate-200' }}">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <input type="radio" name="material_roll_id" value="{{ $r->id }}" data-width="{{ $r->roll_width_m }}" data-length="{{ $r->current_length_m }}" {{ $idx === 0 ? 'checked' : '' }} onchange="calculateModalPrintArea()" class="form-check-input cursor-pointer">
                                        <div>
                                            <div class="font-extrabold text-xs text-slate-900">{{ $r->roll_name }}</div>
                                            <span class="text-[11px] text-slate-500">Lebar: <strong>{{ $r->roll_width_m }}m</strong> &bull; Sisa Gulungan: <strong class="text-blue-700 font-mono">{{ $r->current_length_m }}m</strong></span>
                                        </div>
                                    </div>
                                    <span class="badge bg-slate-100 text-slate-700 font-mono text-xs font-bold px-2 py-1">
                                        {{ round((float)$r->current_length_m * (float)$r->roll_width_m, 1) }} m²
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Tarikan Panjang Roll (Meter) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="form-label font-bold text-slate-800 text-xs uppercase mb-1">
                                2. Panjang Tarikan Roll yang Dipotong (Meter) <span class="text-rose-600">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0.1" name="cut_length_m" id="modalCutLengthInput" value="1.00" class="form-control font-mono font-bold text-blue-900 text-sm" required oninput="calculateModalPrintArea()">
                                <span class="input-group-text bg-slate-100 font-bold">Meter</span>
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1 block">Panjang fisik bahan roll yang ditarik/dicetak mesin.</span>
                        </div>
                        <div>
                            <label class="form-label font-bold text-slate-800 text-xs uppercase mb-1">
                                Catatan Operator (Opsional)
                            </label>
                            <input type="text" name="operator_notes" class="form-control form-control-sm text-xs" placeholder="Misal: Gabung file banner 1m & 1.8m">
                        </div>
                    </div>

                    <!-- Kalkulasi Luas Terpotong & Sinkronisasi Stok ERP -->
                    <div class="p-3.5 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 text-white space-y-2">
                        <div class="d-flex justify-content-between align-items-center text-xs">
                            <span class="text-slate-300">Luas Bahan Terpotong dari Roll:</span>
                            <strong id="modalPhysicalAreaDeducted" class="font-mono text-emerald-400 text-sm font-bold">0.00 m²</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-xs">
                            <span class="text-slate-300">Luas Bersih Pesanan Pelanggan:</span>
                            <span id="modalOrderAreaEcho" class="font-mono text-cyan-300 font-bold">0.00 m²</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-xs pt-1.5 border-t border-slate-700">
                            <span class="text-slate-400 text-[11px]">Sisa Pinggiran Roll (Offcut Waste):</span>
                            <span id="modalOffcutArea" class="font-mono text-amber-300 text-[11px] font-bold">0.00 m²</span>
                        </div>
                    </div>

                </div>

                <div class="modal-footer bg-slate-50 p-3 justify-content-between">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl text-xs font-bold" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-sm text-white rounded-xl text-xs font-bold px-4 py-2 d-inline-flex align-items-center gap-1.5 shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                        <i class="fa-solid fa-check"></i>
                        <span>Selesaikan Cetak & Potong Stok Sekarang</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let selectedOrders = [];

function onOrderCheckboxChange() {
    selectedOrders = [];
    document.querySelectorAll('.order-checkbox:checked').forEach(cb => {
        selectedOrders.push({
            id: cb.value,
            materialId: cb.getAttribute('data-material-id'),
            materialName: cb.getAttribute('data-material-name'),
            invoice: cb.getAttribute('data-invoice'),
            customer: cb.getAttribute('data-customer'),
            dim: cb.getAttribute('data-dim'),
            qty: cb.getAttribute('data-qty'),
            area: parseFloat(cb.getAttribute('data-area')) || 0
        });
    });

    const badge = document.getElementById('selectedCountBadge');
    if (badge) badge.innerText = selectedOrders.length;
}

function toggleSelectAll(masterCb) {
    document.querySelectorAll('.order-checkbox').forEach(cb => {
        cb.checked = masterCb.checked;
    });
    onOrderCheckboxChange();
}

function openSinglePrintModal(detailId) {
    // Uncheck others and check only this one
    document.querySelectorAll('.order-checkbox').forEach(cb => {
        cb.checked = (cb.value == detailId);
    });
    onOrderCheckboxChange();
    openJoinPrintModal();
}

function openJoinPrintModal() {
    if (selectedOrders.length === 0) {
        alert('Silakan pilih minimal 1 orderan dari tabel antrean terlebih dahulu.');
        return;
    }

    // Populate hidden inputs
    const container = document.getElementById('hiddenDetailIdsContainer');
    container.innerHTML = '';
    let totalArea = 0;
    let listHtml = '';

    selectedOrders.forEach(o => {
        container.innerHTML += `<input type="hidden" name="detail_ids[]" value="${o.id}">`;
        totalArea += o.area;
        listHtml += `
            <div class="d-flex justify-content-between align-items-center p-1.5 bg-white rounded-xl border border-slate-200 text-xs">
                <div>
                    <strong class="font-mono text-slate-800">${o.invoice}</strong> &bull; <span class="text-slate-600 font-semibold">${o.customer}</span>
                    <div class="text-[10px] text-blue-700 font-bold font-mono">${o.dim} (${o.qty}x)</div>
                </div>
                <span class="badge bg-emerald-50 text-emerald-700 font-mono font-bold">${o.area.toFixed(2)} m²</span>
            </div>
        `;
    });

    document.getElementById('modalSelectedOrdersList').innerHTML = listHtml;
    document.getElementById('modalTotalOrderArea').innerText = totalArea.toFixed(2) + ' m²';
    document.getElementById('modalOrderAreaEcho').innerText = totalArea.toFixed(2) + ' m²';

    // Auto calculate suggested cut length based on first order dimension
    calculateModalPrintArea();

    const modalEl = document.getElementById('modalExecuteBannerPrint');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function calculateModalPrintArea() {
    const cutInput = document.getElementById('modalCutLengthInput');
    const cutLen = parseFloat(cutInput?.value) || 0;

    const checkedRoll = document.querySelector('input[name="material_roll_id"]:checked');
    const rollWidth = parseFloat(checkedRoll?.getAttribute('data-width')) || 1.0;

    const physicalArea = cutLen * rollWidth;
    document.getElementById('modalPhysicalAreaDeducted').innerText = physicalArea.toFixed(2) + ' m²';

    let totalOrderArea = 0;
    selectedOrders.forEach(o => totalOrderArea += o.area);

    const offcut = Math.max(0, physicalArea - totalOrderArea);
    document.getElementById('modalOffcutArea').innerText = offcut.toFixed(2) + ' m²';
}
</script>
@endsection
