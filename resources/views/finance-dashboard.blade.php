@extends('layouts.app')

@section('title', 'Dashboard Keuangan')
@section('page-title', 'Overview Keuangan ERP')

@section('content')

<!-- Branch Filter (Owner Only) -->
@if(auth()->user()->isOwner())
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('dashboard') }}" class="row align-items-center g-2">
            <div class="col-auto">
                <label class="col-form-label fw-bold text-dark text-sm">Cabang Filter:</label>
            </div>
            <div class="col-auto">
                <select name="branch_id" onchange="this.form.submit()" class="form-select form-select-sm fw-semibold">
                    <option value="all" {{ request('branch_id') == 'all' ? 'selected' : '' }}>Semua Cabang (Konsolidasi)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->nama_cabang }} {{ $branch->trashed() ? '(Archived)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Stats Row - Bladewind Statistic Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Penjualan -->
    <x-bladewind::statistic 
        number="Rp {{ number_format($totalPenjualan, 0, ',', '.') }}" 
        label="Total Penjualan (Bulan Ini)" 
        has_shadow="true">
        <x-slot name="icon">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl">
                <i class="bi bi-cart-check-fill text-2xl"></i>
            </div>
        </x-slot>
    </x-bladewind::statistic>

    <!-- Kas Masuk -->
    <x-bladewind::statistic 
        number="Rp {{ number_format($totalKasMasuk, 0, ',', '.') }}" 
        label="Total Kas Masuk (Bulan Ini)" 
        has_shadow="true">
        <x-slot name="icon">
            <div class="p-3 bg-sky-50 text-sky-600 rounded-2xl">
                <i class="bi bi-arrow-down-left-circle-fill text-2xl"></i>
            </div>
        </x-slot>
    </x-bladewind::statistic>

    <!-- Pengeluaran Operasional -->
    <x-bladewind::statistic 
        number="Rp {{ number_format($totalPengeluaranOpex, 0, ',', '.') }}" 
        label="Pengeluaran Operasional (OPEX)" 
        has_shadow="true">
        <x-slot name="icon">
            <div class="p-3 bg-rose-50 text-rose-600 rounded-2xl">
                <i class="bi bi-receipt-cutoff text-2xl"></i>
            </div>
        </x-slot>
    </x-bladewind::statistic>

    <!-- Pembelian Bahan Baku -->
    <x-bladewind::statistic 
        number="Rp {{ number_format($totalPembelianBahan, 0, ',', '.') }}" 
        label="Pembelian Bahan (HPP Modal)" 
        has_shadow="true">
        <x-slot name="icon">
            <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                <i class="bi bi-box-seam-fill text-2xl"></i>
            </div>
        </x-slot>
    </x-bladewind::statistic>
</div>

<!-- Content Area Row -->
<div class="row">
    
    <!-- Recent Transactions Table Card -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold mb-0 text-dark">
                    <i class="bi bi-journal-bookmark-fill text-primary me-2"></i> Transaksi Kas Terbaru
                </h5>
                <a href="{{ route('reports.cash-mutation') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                    Lihat Semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7 text-muted">
                            <tr>
                                <th class="ps-4">Tanggal / Ref</th>
                                <th>Tipe & Akun</th>
                                <th class="text-end pe-4">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $trx)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($trx->tanggal)->translatedFormat('d M Y') }}</div>
                                        <div class="text-muted fs-7 font-mono">{{ $trx->nomor_referensi }}</div>
                                    </td>
                                    <td>
                                        <div class="mb-1">
                                            @if($trx->tipe === 'masuk')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <i class="bi bi-plus-circle me-1"></i> Masuk
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                    <i class="bi bi-dash-circle me-1"></i> Keluar
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-dark fw-semibold text-truncate" style="max-width: 240px;">{{ $trx->account->nama_akun }}</div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="fw-bold {{ $trx->tipe === 'masuk' ? 'text-success' : 'text-danger' }}">
                                            {{ $trx->tipe === 'masuk' ? '+' : '-' }} Rp {{ number_format($trx->jumlah, 0, ',', '.') }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        Belum ada transaksi kas tercatat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side Info Cards -->
    <div class="col-lg-4 mb-4">
        <div class="space-y-4">
            
            <!-- POS Activity Summary Card -->
            <div class="card shadow-sm border-0 text-bg-dark rounded-4 p-4">
                <div class="card-body p-0">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-white text-dark p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-receipt fs-5"></i>
                        </div>
                        <h5 class="card-title fw-bold text-white mb-0">Aktivitas POS (Bulan Ini)</h5>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-white-50 text-uppercase font-mono fw-semibold mb-1" style="font-size: 11px;">Jumlah Transaksi Checkout</p>
                        <h2 class="display-6 fw-bold text-white mb-0">{{ number_format($jumlahTransaksi) }} <small class="fs-6 font-normal">Faktur</small></h2>
                    </div>
                    
                    <div>
                        <a href="{{ route('reports.sales') }}" class="btn btn-light w-100 rounded-pill fw-bold text-dark shadow-sm">
                            Lihat Laporan Penjualan
                        </a>
                    </div>
                </div>
            </div>

            <!-- Active Branch Info Card -->
            <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                <div class="card-body p-0 d-flex align-items-start">
                    <div class="rounded-3 bg-primary-subtle text-primary p-3 me-3 d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-building fs-4"></i>
                    </div>
                    <div>
                        <small class="text-uppercase text-muted fw-bold font-mono" style="font-size: 11px;">Cabang Operasional</small>
                        <h5 class="fw-bold text-dark mb-1">{{ Auth::user()->branch->nama_cabang ?? 'Pusat' }}</h5>
                        <p class="text-muted text-xs mb-0">{{ Auth::user()->branch->alamat ?? 'Semua Lokasi Konsolidasi' }}</p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

</div>

@endsection
