@extends('layouts.app')

@section('title', 'Banner Production & Roll Allocator')
@section('page-title', 'Workstation Cetak Banner & Pengelolaan Roll')

@section('action-buttons')
<div class="d-flex align-items-center gap-2 flex-wrap">
    @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin())
        <form method="GET" action="{{ route('production.banner.index') }}" class="d-inline-flex align-items-center gap-1.5 me-2">
            @if(request('material_id'))
                <input type="hidden" name="material_id" value="{{ request('material_id') }}">
            @endif
            <label class="text-xs font-bold text-slate-600 mb-0"><i class="fa-solid fa-shop me-1 text-slate-500"></i> Cabang:</label>
            <select name="branch_id" class="form-select form-select-sm text-xs rounded-xl shadow-xs border-slate-300 font-bold text-slate-800" onchange="this.form.submit()">
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->nama_cabang }}</option>
                @endforeach
            </select>
        </form>
    @endif

    <!-- TOMBOL STOCK OPNAME ROLL DI NAVBAR -->
    <button type="button" class="btn btn-sm text-white font-bold px-3 py-1.5 rounded-xl shadow-xs d-inline-flex align-items-center gap-1.5 transition hover:brightness-110" style="background-color: #047857; border: none;" data-bs-toggle="modal" data-bs-target="#modalOpnameRoll">
        <i class="fa-solid fa-clipboard-check text-amber-300"></i>
        <span>Stock Opname Roll</span>
    </button>

    <a href="{{ route('pos.index') }}" class="btn-odoo-secondary text-decoration-none d-inline-flex align-items-center gap-1.5 text-xs font-bold">
        <i class="fa-solid fa-cash-register text-slate-600"></i>
        <span>Buka Kasir POS</span>
    </a>
    <a href="{{ route('stock.index') }}" class="btn-odoo-secondary text-decoration-none d-inline-flex align-items-center gap-1.5 text-xs font-bold">
        <i class="fa-solid fa-boxes-stacked text-slate-600"></i>
        <span>Menu Stok & Gudang</span>
    </a>
</div>
@endsection

@section('content')
<div class="space-y-5 pb-12">

    <!-- FLASH MESSAGES -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-2xl shadow-xs text-xs font-bold d-flex align-items-center gap-2 border-emerald-300 bg-emerald-50 text-emerald-900" role="alert">
            <i class="fa-solid fa-circle-check text-base text-emerald-600"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-2xl shadow-xs text-xs font-bold d-flex align-items-center gap-2 border-rose-300 bg-rose-50 text-rose-900" role="alert">
            <i class="fa-solid fa-triangle-exclamation text-base text-rose-600"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- TOP KPI SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Antrean Cetak Menunggu -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs d-flex align-items-center justify-content-between">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-amber-800 block mb-0.5">Antrean Menunggu Cetak</span>
                <h4 class="text-2xl font-black text-slate-900 mb-0">{{ $pendingCountTotal }} <span class="text-xs font-normal text-slate-500">Order</span></h4>
                <span class="text-[10px] text-slate-500 font-medium">Order kasir berstatus PAID / DP</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-amber-100 text-amber-700 d-flex align-items-center justify-content-center text-lg shadow-inner">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>

        <!-- Siap Diambil di Kasir -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs d-flex align-items-center justify-content-between">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-blue-800 block mb-0.5">Siap Diantar / Ambil</span>
                <h4 class="text-2xl font-black text-slate-900 mb-0">{{ $readyForPickupCount }} <span class="text-xs font-normal text-slate-500">Order</span></h4>
                <span class="text-[10px] text-slate-500 font-medium">Selesai cetak, siap closing kasir</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-blue-100 text-blue-700 d-flex align-items-center justify-content-center text-lg shadow-inner">
                <i class="fa-solid fa-box-open"></i>
            </div>
        </div>

        <!-- Total Selesai Dicetak Hari Ini -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs d-flex align-items-center justify-content-between">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-800 block mb-0.5">Selesai Hari Ini</span>
                <h4 class="text-2xl font-black text-slate-900 mb-0">{{ $totalPrintedTodayCount }} <span class="text-xs font-normal text-slate-500">Item</span></h4>
                <span class="text-[10px] text-slate-500 font-medium">Tercetak & stok terpotong</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-emerald-100 text-emerald-700 d-flex align-items-center justify-content-center text-lg shadow-inner">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <!-- Indikator Sinkronisasi Stok ERP -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs d-flex align-items-center justify-content-between">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-800 block mb-0.5">Luas Fisik Roll Aktif</span>
                <h4 class="text-2xl font-black text-slate-900 mb-0 font-mono">{{ number_format($totalPhysicalRollArea, 1) }} <span class="text-xs font-normal text-slate-500">m²</span></h4>
                <span class="text-[10px] text-slate-600 font-medium">
                    Master ERP: <strong class="font-mono text-indigo-950 font-bold">{{ number_format($erpStockArea, 1) }} m²</strong>
                </span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-indigo-100 text-indigo-700 d-flex align-items-center justify-content-center text-lg shadow-inner">
                <i class="fa-solid fa-scroll"></i>
            </div>
        </div>
    </div>

    <!-- SECTION 1: HIERARCHICAL ROLL STOCK SELECTOR & ACTUAL ROLLS OVERVIEW -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-slate-50/80 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h6 class="font-black text-slate-900 text-sm mb-0.5 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-tape text-blue-600"></i>
                    <span>Real Stock Gulungan Roll (Jenis Bahan &rarr; Subjenis &rarr; Gulungan Fisik)</span>
                </h6>
                <p class="text-xs text-slate-600 mb-0 font-medium">
                    Pilih jenis dan subjenis bahan untuk memantau sisa meter fisik per gulungan roll & perbandingan dengan Master Stok ERP.
                </p>
            </div>

            <!-- HIERARCHICAL DROPDOWN SELECTOR -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <label for="selectMaterialHierarchy" class="text-xs font-bold text-slate-700 mb-0 d-flex align-items-center gap-1">
                    <i class="fa-solid fa-filter text-slate-500"></i>
                    <span>Pilih Bahan:</span>
                </label>
                <select id="selectMaterialHierarchy" 
                        class="form-select form-select-sm rounded-xl font-bold border-slate-300 shadow-xs text-xs text-slate-900 bg-white" 
                        style="min-width: 340px; max-width: 480px;"
                        onchange="window.location.href = this.value">
                    @foreach($groupedMaterials as $family => $materialsInFamily)
                        <optgroup label="📁 {{ strtoupper($family) }}">
                            @foreach($materialsInFamily as $item)
                                @php
                                    $m = $item['material'];
                                    $isSelected = ($selectedMaterialId == $m->id);
                                @endphp
                                <option value="{{ route('production.banner.index', ['material_id' => $m->id, 'branch_id' => $branchId]) }}" {{ $isSelected ? 'selected' : '' }}>
                                    {{ $m->material_name }} &bull; (Actual Stock: {{ $item['total_meters'] }}m / {{ number_format($item['total_area'], 1) }} m²)
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>

                <!-- Tombol Buka Modal Opname Cepat (SOLID HIGH CONTRAST) -->
                <button type="button" class="btn btn-sm rounded-xl font-bold px-3 py-1.5 text-xs text-white shadow-xs d-inline-flex align-items-center gap-1.5 transition hover:brightness-110" style="background-color: #047857; border: none;" onclick="openOpnameModalForMaterial({{ $activeMaterial ? $activeMaterial->id : 'null' }})">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Opname Bahan Ini</span>
                </button>
            </div>
        </div>

        <div class="p-4 bg-white">
            @if(!$activeMaterial)
                <div class="text-center py-8 text-slate-400">
                    <i class="fa-solid fa-box-open text-4xl mb-2 opacity-40"></i>
                    <p class="text-xs mb-0 font-medium">Belum ada bahan roll banner yang terdaftar di cabang ini.</p>
                </div>
            @else
                <!-- Active Material Header Stats Banner (HIGH CONTRAST THEME - NO FADING GRADIENT) -->
                <div class="p-4 mb-4 rounded-2xl border border-slate-200 bg-slate-50/90 d-flex flex-wrap justify-content-between align-items-center gap-3 shadow-xs">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white d-flex align-items-center justify-content-center text-xl shadow-xs">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-0.5">
                                <span class="badge bg-blue-100 text-blue-900 border border-blue-200 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md">
                                    Subjenis Terpilih
                                </span>
                                <span class="text-xs text-slate-600 font-medium">Cabang: <strong class="text-slate-900 font-bold">{{ $branch->nama_cabang }}</strong></span>
                            </div>
                            <h5 class="text-lg font-black text-slate-900 mb-0 tracking-tight">{{ $activeMaterial->material_name }}</h5>
                            <span class="text-xs text-slate-600 font-medium">
                                Kategori: <strong class="text-slate-800">{{ $activeMaterial->category ?: 'Cetak Outdoor/Indoor' }}</strong> &bull; 
                                Total Gulungan: <strong class="text-slate-800">{{ $activeRolls->count() }} Roll Varian</strong>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <!-- Metric 1: Total Panjang Fisik -->
                        <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-end shadow-xs">
                            <span class="text-[10px] text-slate-500 font-extrabold uppercase block tracking-wider">Total Panjang Fisik:</span>
                            <strong class="text-sm font-mono text-blue-700 font-black">
                                {{ $activeRolls->sum('current_length_m') }} meter
                            </strong>
                        </div>

                        <!-- Metric 2: Total Luas Fisik -->
                        <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-end shadow-xs">
                            <span class="text-[10px] text-slate-500 font-extrabold uppercase block tracking-wider">Total Luas Fisik:</span>
                            <strong class="text-sm font-mono text-emerald-700 font-black">
                                {{ number_format($totalPhysicalRollArea, 2) }} m²
                            </strong>
                        </div>

                        <!-- Metric 3: Stok Master ERP -->
                        <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-end shadow-xs">
                            <span class="text-[10px] text-slate-500 font-extrabold uppercase block tracking-wider">Stok Master ERP:</span>
                            <strong class="text-sm font-mono text-indigo-800 font-black">
                                {{ number_format($erpStockArea, 1) }} m²
                            </strong>
                        </div>

                        <!-- Metric 4: Sinkron Status Badge -->
                        <div class="text-center">
                            @if(abs($syncDifference) <= 1)
                                <div class="bg-emerald-100 border border-emerald-300 rounded-xl px-3 py-1.5 text-emerald-900 text-center shadow-xs">
                                    <span class="text-[9px] font-bold uppercase block tracking-wider text-emerald-700">Status Stok:</span>
                                    <strong class="text-xs font-black d-flex align-items-center justify-content-center gap-1 mt-0.5">
                                        <i class="fa-solid fa-check-double text-emerald-700"></i> 100% SINKRON
                                    </strong>
                                </div>
                            @else
                                <div class="bg-amber-100 border border-amber-300 rounded-xl px-3 py-1.5 text-amber-950 text-center shadow-xs" title="Selisih luas fisik roll vs stok ERP: {{ $syncDifference }} m²">
                                    <span class="text-[9px] font-bold uppercase block tracking-wider text-amber-800">Status Stok:</span>
                                    <strong class="text-xs font-black d-flex align-items-center justify-content-center gap-1 mt-0.5">
                                        <i class="fa-solid fa-triangle-exclamation text-amber-700"></i> Selisih {{ $syncDifference }} m²
                                    </strong>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Roll Cards Grid for this Subjenis (HIGH CONTRAST & CRISP) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @forelse($activeRolls as $roll)
                        @php
                            $initLen = max(1, (float)$roll->initial_length_m);
                            $currLen = (float)$roll->current_length_m;
                            $pct = min(100, round(($currLen / $initLen) * 100));
                            $rollArea = round($currLen * (float)$roll->roll_width_m, 2);
                            $isLow = $currLen <= 15;
                            $isCritical = $currLen <= 5;
                        @endphp
                        <div class="p-3.5 rounded-2xl border transition-all duration-200 bg-white {{ $isCritical ? 'border-rose-400 bg-rose-50/30' : ($isLow ? 'border-amber-400 bg-amber-50/30' : 'border-slate-200 hover:border-blue-500 shadow-xs') }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="w-9 h-9 rounded-xl {{ $isCritical ? 'bg-rose-100 text-rose-700' : ($isLow ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-700') }} d-flex align-items-center justify-content-center text-sm font-bold shadow-xs">
                                        <i class="fa-solid fa-scroll"></i>
                                    </div>
                                    <div>
                                        <h6 class="font-black text-xs text-slate-900 mb-0">{{ $roll->roll_name }}</h6>
                                        <span class="text-[11px] font-mono text-slate-600 font-bold">Lebar: <strong class="text-slate-900">{{ $roll->roll_width_m }}m</strong></span>
                                    </div>
                                </div>
                                @if($isCritical)
                                    <span class="badge bg-rose-600 text-white text-[9px] font-bold px-2 py-0.5">KRITIS</span>
                                @elseif($isLow)
                                    <span class="badge bg-amber-500 text-white text-[9px] font-bold px-2 py-0.5">MENIPIS</span>
                                @else
                                    <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 text-[9px] font-bold px-2 py-0.5">AKTIF</span>
                                @endif
                            </div>

                            <!-- Visual Roll Length Progress Bar -->
                            <div class="space-y-1 my-2.5">
                                <div class="d-flex justify-content-between text-[11px] font-mono font-bold">
                                    <span class="text-slate-700">Sisa: <strong class="{{ $isCritical ? 'text-rose-700 font-black' : 'text-blue-700 font-black' }}">{{ $currLen }} m</strong></span>
                                    <span class="text-slate-500 font-bold">/ {{ $initLen }} m ({{ $pct }}%)</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                                    <div class="h-2.5 rounded-full transition-all duration-500 {{ $isCritical ? 'bg-rose-600' : ($isLow ? 'bg-amber-500' : 'bg-blue-600') }}" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-200 d-flex justify-content-between align-items-center text-xs">
                                <span class="text-slate-600 font-bold">Actual Stock Luas:</span>
                                <strong class="font-mono text-slate-900 font-black text-xs">{{ $rollArea }} m²</strong>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-12 text-center py-6 text-slate-400 text-xs font-medium">
                            Belum ada gulungan roll terdaftar untuk subjenis bahan ini.
                        </div>
                    @endforelse
                </div>

                <!-- COLLAPSIBLE TREE OVERVIEW OF ALL BANNER MATERIALS & ROLLS -->
                <div class="mt-4 pt-3 border-t border-slate-200">
                    <button class="btn btn-link text-decoration-none text-xs font-bold text-slate-700 p-0 d-inline-flex align-items-center gap-1.5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMaterialRollTree" aria-expanded="false" aria-controls="collapseMaterialRollTree">
                        <i class="fa-solid fa-network-wired text-blue-600"></i>
                        <span>Lihat Struktur Pohon Semua Bahan & Roll (Jenis &rarr; Subjenis &rarr; Gulungan)</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-500 ms-1"></i>
                    </button>

                    <div class="collapse mt-3" id="collapseMaterialRollTree">
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-3">
                            @foreach($groupedMaterials as $family => $materialsInFamily)
                                <div class="border-b border-slate-200/60 pb-2 last:border-b-0 last:pb-0">
                                    <div class="font-bold text-slate-900 text-xs mb-1.5 d-flex align-items-center gap-1.5">
                                        <i class="fa-solid fa-folder-open text-amber-500"></i>
                                        <span class="text-slate-900 uppercase font-black tracking-wide">{{ $family }}</span>
                                    </div>
                                    <div class="ms-4 space-y-2">
                                        @foreach($materialsInFamily as $item)
                                            @php $m = $item['material']; @endphp
                                            <div class="p-2.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1.5">
                                                    <a href="{{ route('production.banner.index', ['material_id' => $m->id, 'branch_id' => $branchId]) }}" class="font-black text-blue-700 hover:underline d-inline-flex align-items-center gap-1">
                                                        <i class="fa-solid fa-circle-dot text-[8px] text-blue-600"></i>
                                                        <span>{{ $m->material_name }}</span>
                                                    </a>
                                                    <span class="badge bg-slate-100 text-slate-800 font-mono text-[10px] font-bold border border-slate-200">
                                                        Actual Stock: <strong class="text-blue-800">{{ $item['total_meters'] }} m</strong> ({{ number_format($item['total_area'], 1) }} m²)
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap ms-3">
                                                    @foreach($m->rolls as $r)
                                                        <span class="badge bg-slate-50 text-slate-800 border border-slate-200 text-[10px] font-medium py-1 px-2">
                                                            <i class="fa-solid fa-scroll text-slate-500 me-0.5"></i>
                                                            {{ $r->roll_name }} ({{ $r->roll_width_m }}m): <strong class="text-blue-800 font-mono font-bold">{{ $r->current_length_m }}m</strong>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- SECTION 2: ANTREAN ORDER CETAK BANNER (SPK PRODUKSI) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-slate-50/80 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h6 class="font-black text-slate-900 text-sm mb-0.5 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-list-check text-amber-600"></i>
                    <span>Antrean Order Cetak Banner Masuk (Status Bayar: PAID / DP)</span>
                </h6>
                <p class="text-xs text-slate-600 mb-0 font-medium">
                    Orderan pelanggan dari kasir yang siap diproduksi. Centang beberapa pesanan untuk digabung (<strong>Join / Nesting</strong>) ke dalam satu tarikan roll.
                </p>
            </div>

            <!-- Queue Actions & Filter -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Filter Bahan di Tabel Antrean -->
                <div class="d-inline-flex align-items-center gap-1.5">
                    <label for="filterQueueMaterial" class="text-xs font-bold text-slate-700 mb-0">Filter:</label>
                    <select id="filterQueueMaterial" class="form-select form-select-sm rounded-xl text-xs font-bold border-slate-300 text-slate-800 bg-white" onchange="filterQueueTable(this.value)">
                        <option value="ALL">Semua Bahan ({{ $pendingQueue->count() }} Order)</option>
                        @php
                            $uniqueMaterials = $pendingQueue->pluck('material.material_name')->filter()->unique();
                        @endphp
                        @foreach($uniqueMaterials as $matName)
                            <option value="{{ $matName }}">{{ $matName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Action: Batch Print Selected Orders -->
                <button type="button" class="btn text-white text-xs px-3.5 py-2 rounded-xl font-bold d-inline-flex align-items-center gap-2 shadow-xs transition hover:brightness-110" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border: none;" onclick="openJoinPrintModal()">
                    <i class="fa-solid fa-print"></i>
                    <span>Proses Cetak Order Terpilih (<span id="selectedCountBadge">0</span>)</span>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-xs" id="tablePendingQueue">
                <thead>
                    <tr class="bg-slate-100/90 text-slate-700 uppercase text-[10px] font-black border-b border-slate-200">
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
                <tbody class="divide-y divide-slate-100" id="queueTbody">
                    @forelse($pendingQueue as $item)
                        @php
                            $tx = $item->transaction;
                            $dimText = $item->dimension_text ?: ($item->fixed_length_m . 'm x ' . round($item->custom_width_cm/100, 2) . 'm');
                            $itemArea = (float)($item->area_m2 ?: 1.0);
                            $totalArea = round($itemArea * (int)$item->qty_ordered, 2);
                            $materialName = $item->material->material_name ?? ($item->product_name_snapshot ?? '-');
                            $matId = $item->material_id;
                            $lengthM = (float)($item->fixed_length_m ?: 1.0);
                            $widthM = (float)($item->custom_width_cm ? round($item->custom_width_cm / 100, 2) : 1.0);
                        @endphp
                        <tr class="queue-row hover:bg-blue-50/40 transition" data-material-name="{{ $materialName }}">
                            <td class="ps-4 text-center">
                                <input type="checkbox" class="form-check-input order-checkbox cursor-pointer" 
                                       value="{{ $item->id }}" 
                                       data-material-id="{{ $matId }}"
                                       data-material-name="{{ $materialName }}"
                                       data-invoice="{{ $tx->invoice_number }}"
                                       data-customer="{{ $tx->customer->name ?? 'Pelanggan Umum' }}"
                                       data-dim="{{ $dimText }}"
                                       data-length="{{ $lengthM }}"
                                       data-width="{{ $widthM }}"
                                       data-qty="{{ $item->qty_ordered }}"
                                       data-area="{{ $totalArea }}"
                                       onchange="onOrderCheckboxChange()">
                            </td>
                            <td>
                                <div class="font-mono font-bold text-slate-900">{{ $tx->invoice_number }}</div>
                                <span class="text-[10px] text-slate-500 font-medium">{{ $tx->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td>
                                <div class="font-bold text-slate-900">{{ $tx->customer->name ?? 'Pelanggan Umum' }}</div>
                                @if(isset($tx->customer->phone) && $tx->customer->phone)
                                    <span class="text-[10px] text-slate-500 font-mono">{{ $tx->customer->phone }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-blue-50 text-blue-800 border border-blue-200 text-[11px] font-bold px-2 py-1">
                                    {{ $materialName }}
                                </span>
                            </td>
                            <td class="text-center font-mono font-bold text-blue-700">
                                {{ $dimText }}
                            </td>
                            <td class="text-center font-bold text-slate-800">
                                {{ $item->qty_ordered }}x
                            </td>
                            <td class="text-center font-mono font-bold text-emerald-700">
                                {{ $totalArea }} m²
                            </td>
                            <td>
                                <div class="text-[11px] text-slate-700 font-semibold">{{ $item->finishing ?: '-' }}</div>
                                @if($item->notes || $tx->notes)
                                    <div class="text-[10px] text-amber-800 font-bold"><i class="fa-solid fa-note-sticky me-1 text-amber-600"></i>{{ $item->notes ?: $tx->notes }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($tx->payment_status === 'PAID')
                                    <span class="badge bg-emerald-50 text-emerald-800 border border-emerald-300 font-bold px-2 py-1 text-[10px]">
                                        <i class="fa-solid fa-check me-0.5 text-emerald-600"></i> LUNAS
                                    </span>
                                @elseif($tx->payment_status === 'PARTIAL')
                                    <span class="badge bg-amber-50 text-amber-900 border border-amber-300 font-bold px-2 py-1 text-[10px]" title="DP: Rp {{ number_format($tx->paid_amount) }} | Sisa: Rp {{ number_format($tx->remaining_amount) }}">
                                        DP (Sisa Rp {{ number_format($tx->remaining_amount, 0, ',', '.') }})
                                    </span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-700 text-[10px] font-bold">{{ $tx->payment_status }}</span>
                                @endif
                            </td>
                            <td class="pe-4 text-center">
                                <button type="button" class="btn btn-xs rounded-xl font-bold px-3 py-1.5 text-white shadow-xs transition hover:brightness-110" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none;" onclick="openSinglePrintModal({{ $item->id }})">
                                    <i class="fa-solid fa-print me-1"></i> Cetak
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyQueueRow">
                            <td colspan="10" class="text-center py-10 text-slate-400">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 d-flex align-items-center justify-content-center mx-auto mb-2 text-xl">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                </div>
                                <h6 class="font-bold text-slate-700 text-xs mb-0.5">Tidak Ada Antrean Cetak</h6>
                                <p class="text-[11px] mb-0 font-medium">Semua order banner yang telah dibayar kasir telah selesai diproduksi.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECTION 3: SIAP DIANTAR KE KASIR (READY FOR PICKUP / HANDOVER) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-slate-50/80 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="font-black text-slate-900 text-sm mb-0.5 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-box-open text-blue-600"></i>
                    <span>Pesanan Selesai Cetak — Siap Diantar ke Meja Kasir / Serah Terima Pelanggan</span>
                </h6>
                <p class="text-xs text-slate-600 mb-0 font-medium">
                    Fisik banner sudah selesai dicetak. Bawa banner ke meja kasir untuk diserahkan ke pelanggan dan lakukan closing transaksi.
                </p>
            </div>
            <span class="badge bg-blue-100 text-blue-800 border border-blue-200 font-bold px-2.5 py-1 text-xs rounded-xl">
                {{ $readyForPickupCount }} Siap Serah Terima
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-xs">
                <thead>
                    <tr class="bg-slate-100/90 text-slate-700 uppercase text-[10px] font-black border-b border-slate-200">
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
                                <div class="text-[10px] text-slate-500 font-medium">{{ $rTx->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <strong class="text-slate-900 font-bold">{{ $rTx->customer->name ?? 'Pelanggan Umum' }}</strong>
                                @if(isset($rTx->customer->phone) && $rTx->customer->phone)
                                    <div class="text-[10px] text-slate-500 font-mono">{{ $rTx->customer->phone }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="space-y-1">
                                    @foreach($rTx->transactionDetails as $d)
                                        <div class="d-flex align-items-center gap-1.5 text-[11px]">
                                            <span class="badge bg-slate-100 text-slate-800 border text-[10px] font-bold">{{ $d->material->material_name ?? '-' }}</span>
                                            <span class="font-mono text-blue-700 font-bold">{{ $d->dimension_text ?: ($d->fixed_length_m . 'x' . round($d->custom_width_cm/100, 2) . 'm') }}</span>
                                            <span class="text-slate-500 font-bold">({{ $d->qty_ordered }}x)</span>
                                            @if($d->materialRoll)
                                                <span class="badge bg-emerald-50 text-emerald-800 border border-emerald-200 text-[9px] font-bold">
                                                    <i class="fa-solid fa-scroll me-0.5 text-emerald-600"></i> {{ $d->materialRoll->roll_name }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-center">
                                @if($rTx->payment_status === 'PAID')
                                    <span class="badge bg-emerald-100 text-emerald-900 font-bold px-2 py-1 text-[10px]">
                                        <i class="fa-solid fa-check-circle me-1 text-emerald-600"></i> LUNAS
                                    </span>
                                @elseif($rTx->payment_status === 'PARTIAL')
                                    <span class="badge bg-amber-100 text-amber-950 font-bold px-2 py-1 text-[10px]">
                                        <i class="fa-solid fa-hand-holding-dollar me-1 text-amber-600"></i> BELUM LUNAS (DP)
                                    </span>
                                @endif
                            </td>
                            <td class="text-end font-mono font-bold {{ (float)$rTx->remaining_amount > 0 ? 'text-rose-700 font-black' : 'text-slate-400' }}">
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
                            <td colspan="6" class="text-center py-6 text-slate-400 text-xs font-medium">
                                Belum ada pesanan yang selesai cetak menunggu serah terima kasir.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL 1: ALOKASI GULUNGAN ROLL & JOIN CETAK (NESTING) -->
<div class="modal fade" id="modalExecuteBannerPrint" tabindex="-1" aria-labelledby="modalExecuteBannerPrintLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="modal-header text-white p-4" style="background-color: #0f172a !important;">
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
                        <span class="text-[10px] font-bold uppercase text-slate-600 block mb-1">Rincian Orderan yang Akan Dicetak:</span>
                        <div id="modalSelectedOrdersList" class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                            <!-- Populated via JS -->
                        </div>
                        <div class="mt-2 pt-2 border-t border-slate-200 d-flex justify-content-between align-items-center text-xs">
                            <span class="text-slate-700 font-bold">Total Luas Bersih Pesanan Pelanggan:</span>
                            <strong id="modalTotalOrderArea" class="font-mono text-blue-900 font-black text-sm">0.00 m²</strong>
                        </div>
                    </div>

                    <!-- Info Margin Lipatan Banner & Toleransi Supplier (+10cm) -->
                    <div class="p-2.5 rounded-xl bg-blue-50 border border-blue-200 d-flex align-items-center justify-content-between text-xs">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-circle-check text-blue-600 text-sm"></i>
                            <div>
                                <strong class="text-slate-900">Toleransi Pabrik (+10 cm):</strong>
                                <span class="text-slate-700">Roll supplier sudah memiliki lebihan fisik $\pm 10\text{ cm}$, sehingga pesanan pas di roll (misal order 1M di roll 1M) muat dan aman untuk lipatan keliling.</span>
                            </div>
                        </div>
                        <span class="badge bg-blue-100 text-blue-900 font-mono text-[10px] font-bold">Toleransi Supplier Aman</span>
                    </div>

                    <!-- Pilih Gulungan Roll Fisik (Populated dynamically according to selected material) -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                            <label class="form-label font-bold text-slate-900 text-xs uppercase mb-0">
                                1. Pilih Gulungan Roll Fisik yang Digunakan <span class="text-rose-600">*</span>
                            </label>
                            <span class="text-[11px] text-slate-700 font-bold" id="modalRollMaterialBadge">Bahan: Flexy</span>
                        </div>
                        <div id="modalRollOptionsList" class="space-y-2">
                            <!-- Populated dynamically via JS -->
                        </div>
                        <div id="modalRollWarning" class="mt-2" style="display: none;"></div>
                    </div>

                    <!-- Tarikan Panjang Roll (Meter) & Catatan Operator (Bootstrap Row untuk Mencegah Glitch CSS) -->
                    <div class="row g-3 pt-1">
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-slate-900 text-xs uppercase mb-1 d-block">
                                2. Panjang Tarikan Roll yang Dipotong (Meter) <span class="text-rose-600">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0.1" name="cut_length_m" id="modalCutLengthInput" value="1.00" class="form-control font-mono font-bold text-blue-900 text-sm" required oninput="calculateModalPrintArea()">
                                <span class="input-group-text bg-slate-100 font-bold text-xs text-slate-700">Meter</span>
                            </div>
                            <span class="text-[10px] text-slate-500 mt-1 d-block font-medium">Sudah mencakup toleransi lipatan banner (+10 cm).</span>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-slate-900 text-xs uppercase mb-1 d-block">
                                Catatan Operator (Opsional)
                            </label>
                            <input type="text" name="operator_notes" class="form-control form-control-sm text-xs rounded-xl" placeholder="Misal: Join cetak 1m + 1.8m di roll 3m">
                            <span class="text-[10px] text-slate-500 mt-1 d-block font-medium">Catatan proses mesin cetak.</span>
                        </div>
                    </div>

                    <!-- Kalkulasi Luas Terpotong & Sinkronisasi Stok ERP (SOLID HIGH-CONTRAST DARK BOX) -->
                    <div class="p-3.5 rounded-2xl border border-slate-700 space-y-2 shadow-xs" style="background-color: #0f172a !important; color: #ffffff !important;">
                        <div class="d-flex justify-content-between align-items-center text-xs">
                            <span class="text-slate-300 font-medium">Luas Bahan Fisik Terpotong dari Roll:</span>
                            <strong id="modalPhysicalAreaDeducted" class="font-mono text-emerald-400 text-sm font-black">0.00 m²</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-xs">
                            <span class="text-slate-300 font-medium">Luas Bersih Pesanan Pelanggan:</span>
                            <span id="modalOrderAreaEcho" class="font-mono text-cyan-300 font-bold text-sm">0.00 m²</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-xs pt-2 border-t border-slate-700">
                            <div>
                                <span class="text-amber-300 text-xs font-black d-flex align-items-center gap-1.5">
                                    <i class="fa-solid fa-scissors text-amber-400"></i>
                                    <span>Total Waste Bahan (Offcut & Lipatan):</span>
                                </span>
                                <span class="text-[10px] text-slate-400 font-medium">Otomatis dicatat ke Buku Jurnal Waste Produksi</span>
                            </div>
                            <span id="modalOffcutArea" class="font-mono text-amber-300 text-base font-black">0.00 m²</span>
                        </div>
                    </div>

                </div>

                <div class="modal-footer bg-slate-50 p-3 justify-content-between border-t border-slate-200">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl text-xs font-bold" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-sm text-white rounded-xl text-xs font-bold px-4 py-2 d-inline-flex align-items-center gap-1.5 shadow-sm transition hover:brightness-110" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                        <i class="fa-solid fa-check"></i>
                        <span>Selesaikan Cetak & Potong Stok Sekarang</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: STOCK OPNAME FISIK GULUNGAN ROLL -->
<div class="modal fade" id="modalOpnameRoll" tabindex="-1" aria-labelledby="modalOpnameRollLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="modal-header text-white p-4" style="background-color: #064e3b !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-200 d-flex align-items-center justify-content-center text-lg">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-extrabold text-sm mb-0 tracking-wide text-white" id="modalOpnameRollLabel">
                            STOCK OPNAME FISIK GULUNGAN ROLL BANNER
                        </h5>
                        <p class="text-[11px] text-emerald-100 mb-0 mt-0.5">
                            Perbarui sisa meter fisik aktual per roll. Master Stok ERP ($m^2$) akan otomatis disinkronkan.
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('production.banner.opname') }}" method="POST" id="formOpnameRoll">
                @csrf
                <div class="modal-body p-4 space-y-4">
                    
                    <!-- Pilih Subjenis Bahan yang Akan Di-Opname -->
                    <div>
                        <label for="opnameMaterialDropdown" class="form-label font-bold text-slate-900 text-xs uppercase mb-1 d-flex justify-content-between align-items-center">
                            <span>1. Pilih Subjenis Bahan <span class="text-rose-600">*</span></span>
                            <span class="text-[10px] text-slate-500 font-medium">Pilih bahan yang sedang diukur fisiknya</span>
                        </label>
                        <select id="opnameMaterialDropdown" name="material_id" class="form-select form-select-sm rounded-xl font-bold text-xs border-slate-300 text-slate-900 bg-white" onchange="renderOpnameMaterial(this.value)">
                            @foreach($groupedMaterials as $family => $materialsInFamily)
                                <optgroup label="📁 {{ strtoupper($family) }}">
                                    @foreach($materialsInFamily as $item)
                                        @php $m = $item['material']; @endphp
                                        <option value="{{ $m->id }}" {{ ($activeMaterial && $activeMaterial->id == $m->id) ? 'selected' : '' }}>
                                            {{ $m->material_name }} (Actual: {{ $item['total_meters'] }}m / {{ number_format($item['total_area'], 1) }} m²)
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <!-- Input Roll Table / Cards -->
                    <div>
                        <label class="form-label font-bold text-slate-900 text-xs uppercase mb-1.5 d-flex justify-content-between align-items-center">
                            <span>2. Masukkan Sisa Meter Fisik per Gulungan Roll <span class="text-rose-600">*</span></span>
                            <span class="text-[10px] text-slate-500 font-medium">Isi sesuai meteran fisik di workshop</span>
                        </label>
                        <div id="opnameRollsContainer" class="space-y-2">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>

                    <!-- Live Summary Sinkronisasi Master Stok ERP (SOLID HIGH-CONTRAST DARK BOX) -->
                    <div class="p-3.5 rounded-2xl border border-slate-700 space-y-2 shadow-xs" style="background-color: #0f172a !important; color: #ffffff !important;">
                        <div class="d-flex justify-content-between align-items-center text-xs">
                            <span class="text-slate-300 font-medium">Total Luas Fisik Baru (Hasil Opname):</span>
                            <strong id="opnameSummaryNewArea" class="font-mono text-emerald-400 text-sm font-black">0.00 m²</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-xs">
                            <span class="text-slate-300 font-medium">Stok Master ERP Saat Ini:</span>
                            <span id="opnameSummaryErpArea" class="font-mono text-cyan-300 font-bold text-sm">0.00 m²</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-xs pt-2 border-t border-slate-700">
                            <span class="text-slate-300 font-medium">Selisih Penyesuaian ke ERP:</span>
                            <span id="opnameSummaryDiffArea" class="font-mono text-amber-300 text-xs font-black">±0.00 m²</span>
                        </div>
                    </div>

                    <!-- Catatan Opname -->
                    <div>
                        <label class="form-label font-bold text-slate-900 text-xs uppercase mb-1">
                            Catatan Opname (Opsional)
                        </label>
                        <input type="text" name="notes" class="form-control form-control-sm text-xs rounded-xl" placeholder="Misal: Opname fisik shift pagi / penyesuaian sisa roll">
                    </div>

                </div>

                <div class="modal-footer bg-slate-50 p-3 justify-content-between border-t border-slate-200">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl text-xs font-bold" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-sm text-white rounded-xl text-xs font-bold px-4 py-2 d-inline-flex align-items-center gap-1.5 shadow-sm transition hover:brightness-110" style="background-color: #047857; border: none;">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Simpan & Sinkronkan Stok ERP Sekarang</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// JSON DATA DARI SERVER
const bannerMaterialsData = @json($bannerMaterialsJson);
let selectedOrders = [];

// FILTER TABEL ANTREAN BERDASARKAN BAHAN
function filterQueueTable(selectedMatName) {
    const rows = document.querySelectorAll('#queueTbody tr.queue-row');
    let visibleCount = 0;
    rows.forEach(r => {
        const mat = r.getAttribute('data-material-name');
        if (selectedMatName === 'ALL' || mat === selectedMatName) {
            r.style.display = '';
            visibleCount++;
        } else {
            r.style.display = 'none';
        }
    });

    const emptyRow = document.getElementById('emptyQueueRow');
    if (emptyRow) {
        emptyRow.style.display = (visibleCount === 0) ? '' : 'none';
    }
}

// CHECKBOX SELECTION LOGIC
function onOrderCheckboxChange() {
    selectedOrders = [];
    document.querySelectorAll('.order-checkbox:checked').forEach(cb => {
        selectedOrders.push({
            id: cb.value,
            materialId: parseInt(cb.getAttribute('data-material-id')),
            materialName: cb.getAttribute('data-material-name'),
            invoice: cb.getAttribute('data-invoice'),
            customer: cb.getAttribute('data-customer'),
            dim: cb.getAttribute('data-dim'),
            length: parseFloat(cb.getAttribute('data-length')) || 1.0,
            width: parseFloat(cb.getAttribute('data-width')) || 1.0,
            qty: parseInt(cb.getAttribute('data-qty')) || 1,
            area: parseFloat(cb.getAttribute('data-area')) || 0
        });
    });

    const badge = document.getElementById('selectedCountBadge');
    if (badge) badge.innerText = selectedOrders.length;
}

function toggleSelectAll(masterCb) {
    document.querySelectorAll('.order-checkbox').forEach(cb => {
        const row = cb.closest('tr');
        if (row && row.style.display !== 'none') {
            cb.checked = masterCb.checked;
        }
    });
    onOrderCheckboxChange();
}

function openSinglePrintModal(detailId) {
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

    const container = document.getElementById('hiddenDetailIdsContainer');
    container.innerHTML = '';
    let totalArea = 0;
    let listHtml = '';
    let firstMatId = selectedOrders[0].materialId;
    let firstMatName = selectedOrders[0].materialName;

    // Check if multiple materials are selected
    const differentMat = selectedOrders.some(o => o.materialId !== firstMatId);
    if (differentMat) {
        alert('Perhatian: Anda memilih order dengan jenis bahan yang berbeda. Harap pilih order dengan jenis bahan yang sama untuk diproses cetak bersamaan.');
        return;
    }

    selectedOrders.forEach(o => {
        container.innerHTML += `<input type="hidden" name="detail_ids[]" value="${o.id}">`;
        totalArea += o.area;
        listHtml += `
            <div class="d-flex justify-content-between align-items-center p-2.5 bg-white rounded-xl border border-slate-200 text-xs shadow-2xs">
                <div>
                    <strong class="font-mono text-slate-900">${o.invoice}</strong> &bull; <span class="text-slate-800 font-bold">${o.customer}</span>
                    <div class="text-[11px] text-blue-700 font-bold font-mono">${o.dim} (${o.qty}x)</div>
                </div>
                <span class="badge bg-emerald-100 text-emerald-900 font-mono font-black text-xs border border-emerald-300">${o.area.toFixed(2)} m²</span>
            </div>
        `;
    });

    document.getElementById('modalSelectedOrdersList').innerHTML = listHtml;
    document.getElementById('modalTotalOrderArea').innerText = totalArea.toFixed(2) + ' m²';
    document.getElementById('modalOrderAreaEcho').innerText = totalArea.toFixed(2) + ' m²';
    document.getElementById('modalRollMaterialBadge').innerText = 'Bahan: ' + firstMatName;

    // Standard banner margin allowance for lipatan & finishing = 10 cm (0.10 m)
    const LIPATAN_MARGIN_M = 0.10;

    // Find required width and length across all selected orders
    let maxSmallerDim = 0;
    let maxLargerDim = 0;

    selectedOrders.forEach(o => {
        const smaller = Math.min(o.width, o.length);
        const larger = Math.max(o.width, o.length);
        if (smaller > maxSmallerDim) maxSmallerDim = smaller;
        if (larger > maxLargerDim) maxLargerDim = larger;
    });

    // Standar lebar roll & toleransi supplier:
    // Roll supplier sudah memiliki lebihan fisik (+10cm toleransi dari pabrik),
    // sehingga order nominal s/d lebar roll (misal order 1M di roll 1M) muat & aman.
    const maxOrderWidth = maxSmallerDim;
    const suggestedCutLength = Math.round((maxLargerDim + 0.10) * 100) / 100;

    // Find the material rolls from bannerMaterialsData
    const targetMat = bannerMaterialsData.find(m => m.id === firstMatId);
    const rollsListEl = document.getElementById('modalRollOptionsList');
    rollsListEl.innerHTML = '';

    if (!targetMat || !targetMat.rolls || targetMat.rolls.length === 0) {
        rollsListEl.innerHTML = `
            <div class="p-3 bg-rose-50 text-rose-800 border border-rose-300 rounded-xl text-xs font-bold">
                Tidak ditemukan data gulungan roll untuk bahan "${firstMatName}". Harap periksa master bahan.
            </div>
        `;
    } else {
        // Find best fitting roll (the smallest roll where width >= maxOrderWidth)
        const fittingRolls = targetMat.rolls.filter(r => r.width >= maxOrderWidth);
        let autoSelectedRollId = (fittingRolls.length > 0) ? fittingRolls[0].id : null;

        targetMat.rolls.forEach((r) => {
            const canFit = (r.width >= maxOrderWidth);
            const isAutoChecked = (r.id === autoSelectedRollId);

            let badgeHtml = '';
            let cardStyle = '';
            let labelClass = '';

            if (canFit) {
                if (r.id === autoSelectedRollId) {
                    badgeHtml = `<span class="badge bg-emerald-100 text-emerald-900 border border-emerald-400 text-[10px] font-black"><i class="fa-solid fa-check-circle me-1 text-emerald-700"></i> Rekomendasi Cocok (${r.width}m &ge; ${maxOrderWidth}m)</span>`;
                    labelClass = 'bg-blue-50/50 border-blue-500 shadow-xs cursor-pointer';
                } else {
                    badgeHtml = `<span class="badge bg-slate-100 text-slate-800 border border-slate-300 text-[10px] font-bold">Muat (${r.width}m)</span>`;
                    labelClass = 'bg-white border-slate-300 hover:border-blue-400 cursor-pointer';
                }
            } else {
                badgeHtml = `<span class="badge bg-rose-100 text-rose-800 border border-rose-300 text-[10px] font-black"><i class="fa-solid fa-ban me-1 text-rose-600"></i> Tidak Muat (Lebar order ${maxOrderWidth}m &gt; ${r.width}m)</span>`;
                labelClass = 'bg-slate-100 border-slate-200 opacity-60';
                cardStyle = 'pointer-events: none;';
            }

            rollsListEl.innerHTML += `
                <label class="d-flex align-items-center justify-content-between p-3 rounded-2xl border transition ${labelClass}" style="${cardStyle}">
                    <div class="d-flex align-items-center gap-2.5">
                        <input type="radio" name="material_roll_id" value="${r.id}" 
                               data-width="${r.width}" 
                               data-length="${r.length}" 
                               ${!canFit ? 'disabled' : ''} 
                               ${isAutoChecked ? 'checked' : ''} 
                               onchange="calculateModalPrintArea()" 
                               class="form-check-input cursor-pointer">
                        <div>
                            <div class="font-black text-xs text-slate-900 d-flex align-items-center gap-1.5 flex-wrap">
                                <span>${r.name}</span>
                                ${badgeHtml}
                            </div>
                            <span class="text-[11px] text-slate-600 font-medium">
                                Lebar Fisik: <strong class="text-slate-900 font-bold">${r.width}m</strong> &bull; Sisa Gulungan: <strong class="text-blue-700 font-mono font-black">${r.length}m</strong>
                            </span>
                        </div>
                    </div>
                    <span class="badge bg-slate-100 text-slate-800 font-mono text-xs font-black px-2 py-1 border border-slate-200">
                        ${r.area.toFixed(1)} m²
                    </span>
                </label>
            `;
        });

        // Set suggested cut length (includes +10cm lipatan)
        const cutInput = document.getElementById('modalCutLengthInput');
        if (cutInput) {
            cutInput.value = suggestedCutLength.toFixed(2);
        }
    }

    calculateModalPrintArea();

    const modalEl = document.getElementById('modalExecuteBannerPrint');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function calculateModalPrintArea() {
    const cutInput = document.getElementById('modalCutLengthInput');
    const cutLen = parseFloat(cutInput?.value) || 0;

    const checkedRoll = document.querySelector('input[name="material_roll_id"]:checked');
    const rollWidth = parseFloat(checkedRoll?.getAttribute('data-width')) || 0;

    const physicalArea = cutLen * rollWidth;
    document.getElementById('modalPhysicalAreaDeducted').innerText = physicalArea.toFixed(2) + ' m²';

    let totalOrderArea = 0;
    selectedOrders.forEach(o => totalOrderArea += o.area);

    const offcut = Math.max(0, physicalArea - totalOrderArea);
    document.getElementById('modalOffcutArea').innerText = offcut.toFixed(2) + ' m²';

    const warningEl = document.getElementById('modalRollWarning');
    if (warningEl) {
        if (!checkedRoll || rollWidth === 0) {
            warningEl.innerHTML = `<div class="p-2.5 rounded-xl bg-rose-50 text-rose-800 text-xs font-bold border border-rose-300 d-flex align-items-center gap-2"><i class="fa-solid fa-triangle-exclamation text-base text-rose-600"></i><span>Perhatian: Roll yang dipilih tidak valid atau semua roll di bawah kapasitas ukuran pesanan!</span></div>`;
            warningEl.style.display = 'block';
        } else {
            warningEl.style.display = 'none';
        }
    }
}

// STOCK OPNAME MODAL FUNCTIONS
function openOpnameModalForMaterial(materialId) {
    const dropdown = document.getElementById('opnameMaterialDropdown');
    if (dropdown && materialId) {
        dropdown.value = materialId;
    }
    renderOpnameMaterial(dropdown ? dropdown.value : materialId);
    const modalEl = document.getElementById('modalOpnameRoll');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function renderOpnameMaterial(matId) {
    const id = parseInt(matId);
    const mat = bannerMaterialsData.find(m => m.id === id);
    const container = document.getElementById('opnameRollsContainer');
    container.innerHTML = '';

    if (!mat || !mat.rolls || mat.rolls.length === 0) {
        container.innerHTML = '<div class="p-3 text-slate-500 text-xs font-bold">Tidak ada data roll untuk bahan ini.</div>';
        updateOpnameSummary(0, 0);
        return;
    }

    mat.rolls.forEach(r => {
        container.innerHTML += `
            <div class="p-3 rounded-2xl bg-white border border-slate-200 hover:border-emerald-500 transition shadow-2xs">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 d-flex align-items-center justify-content-center text-xs font-bold">
                            <i class="fa-solid fa-scroll"></i>
                        </div>
                        <div>
                            <strong class="text-xs text-slate-900 block font-black">${r.name}</strong>
                            <span class="text-[10px] text-slate-600 font-mono font-medium">Lebar: <strong class="text-slate-900">${r.width}m</strong> &bull; Sisa Sistem: <strong class="text-blue-800">${r.length}m</strong> (${r.area.toFixed(1)} m²)</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="text-[10px] text-slate-500 block font-bold uppercase tracking-wider">Selisih Fisik:</span>
                        <span id="diff_badge_${r.id}" class="badge bg-slate-100 text-slate-700 font-mono text-[10px] font-bold border border-slate-200">±0.00 m</span>
                    </div>
                </div>

                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-slate-50 text-slate-800 font-black text-xs border-slate-300">Stok Fisik Aktual:</span>
                    <input type="number" step="0.01" min="0" 
                           name="rolls[${r.id}]" 
                           id="roll_input_${r.id}" 
                           value="${r.length}" 
                           data-roll-id="${r.id}"
                           data-width="${r.width}"
                           data-old-length="${r.length}"
                           class="form-control form-control-sm text-end font-mono font-black text-emerald-800 text-sm border-slate-300 opname-length-input" 
                           required 
                           oninput="calculateOpnameDiff()">
                    <span class="input-group-text bg-slate-100 font-bold text-xs text-slate-700 border-slate-300">Meter</span>
                </div>
            </div>
        `;
    });

    calculateOpnameDiff();
}

function calculateOpnameDiff() {
    const matId = parseInt(document.getElementById('opnameMaterialDropdown')?.value);
    const mat = bannerMaterialsData.find(m => m.id === matId);
    if (!mat) return;

    let newTotalArea = 0;
    const inputs = document.querySelectorAll('.opname-length-input');
    inputs.forEach(inp => {
        const rollId = inp.getAttribute('data-roll-id');
        const width = parseFloat(inp.getAttribute('data-width')) || 1.0;
        const oldLen = parseFloat(inp.getAttribute('data-old-length')) || 0;
        const newLen = parseFloat(inp.value) || 0;

        const diffLen = newLen - oldLen;
        const diffBadge = document.getElementById('diff_badge_' + rollId);
        if (diffBadge) {
            if (diffLen === 0) {
                diffBadge.className = 'badge bg-slate-100 text-slate-700 font-mono text-[10px] font-bold border border-slate-200';
                diffBadge.innerText = 'Sesuai Sistem';
            } else if (diffLen > 0) {
                diffBadge.className = 'badge bg-blue-100 text-blue-900 border border-blue-400 font-mono text-[10px] font-black';
                diffBadge.innerText = `+${diffLen.toFixed(2)} m (+${(diffLen * width).toFixed(2)} m²)`;
            } else {
                diffBadge.className = 'badge bg-rose-100 text-rose-900 border border-rose-400 font-mono text-[10px] font-black';
                diffBadge.innerText = `${diffLen.toFixed(2)} m (${(diffLen * width).toFixed(2)} m²)`;
            }
        }

        newTotalArea += (newLen * width);
    });

    updateOpnameSummary(newTotalArea, mat.stock_qty);
}

function updateOpnameSummary(newArea, erpArea) {
    const newAreaEl = document.getElementById('opnameSummaryNewArea');
    const erpAreaEl = document.getElementById('opnameSummaryErpArea');
    const diffAreaEl = document.getElementById('opnameSummaryDiffArea');

    if (newAreaEl) newAreaEl.innerText = newArea.toFixed(2) + ' m²';
    if (erpAreaEl) erpAreaEl.innerText = erpArea.toFixed(2) + ' m²';

    const diff = newArea - erpArea;
    if (diffAreaEl) {
        if (Math.abs(diff) < 0.01) {
            diffAreaEl.innerText = '0.00 m² (100% Sesuai)';
            diffAreaEl.className = 'font-mono text-emerald-400 text-xs font-black';
        } else if (diff > 0) {
            diffAreaEl.innerText = `+${diff.toFixed(2)} m² (Stok ERP Bertambah)`;
            diffAreaEl.className = 'font-mono text-cyan-300 text-xs font-black';
        } else {
            diffAreaEl.innerText = `${diff.toFixed(2)} m² (Stok ERP Berkurang)`;
            diffAreaEl.className = 'font-mono text-amber-300 text-xs font-black';
        }
    }
}

// Auto init on document ready
document.addEventListener('DOMContentLoaded', () => {
    const dropdown = document.getElementById('opnameMaterialDropdown');
    if (dropdown) {
        renderOpnameMaterial(dropdown.value);
    }

    // Auto open opname modal if URL parameter open_opname=1
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open_opname') === '1') {
        const modalEl = document.getElementById('modalOpnameRoll');
        if (modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }
});
</script>
@endsection
