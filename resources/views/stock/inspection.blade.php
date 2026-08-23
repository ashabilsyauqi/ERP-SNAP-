@extends('layouts.app')

@section('title', 'Incoming Receipts Inspection (GRN)')
@section('page-title', 'Incoming Receipts (GRN Inspection)')

@section('action-buttons')
<a href="{{ route('stock.rejected') }}" class="btn-odoo-secondary text-decoration-none">
    <i class="fa-solid fa-rotate-left text-rose-600"></i>
    <span>Scrap / Return History</span>
</a>
@endsection

@section('content')
<div x-data="{ 
    verifyOpen: false,
    verifyPurchase: { id: '', po_number: '', material_name: '', qty_bought: 0, supplier_name: '', total_cost: 0 },
    rejectOpen: false,
    rejectPurchase: { id: '', po_number: '', material_name: '' }
}" id="main-view-wrapper" data-view-wrapper>

    <!-- Info Banner (Odoo Flow Explanation) -->
    <div class="mb-3 p-3 bg-indigo-50 border border-indigo-200 rounded d-flex align-items-start gap-2">
        <i class="fa-solid fa-circle-info text-indigo-600 fs-5 mt-0.5"></i>
        <div class="text-xs text-indigo-950">
            <strong>Alur Goods Receipt (GRN / Cek Fisik Gudang):</strong> Barang pada daftar ini adalah Purchase Order yang <strong>telah disetujui (ACC) oleh Manajer Toko</strong> dan sedang menunggu kedatangan fisik barang di gudang. Klik tombol <span class="badge bg-emerald-600 text-white"><i class="fa-solid fa-check"></i> Validate & Receive</span> setelah fisik barang diperiksa untuk resmi memasukkan kuantitas ke stok gudang.
        </div>
    </div>

    <!-- Main Odoo Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden">
        <div class="p-3 bg-slate-50 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-slate-800 mb-0 fs-6 d-flex align-items-center gap-2">
                <i class="fa-solid fa-truck-ramp-box text-teal-600"></i> Menunggu Cek Fisik Gudang (Incoming Orders)
            </h6>
            <span class="badge bg-amber-50 text-amber-800 border border-amber-300 font-bold text-xs">
                {{ number_format($pendingCount) }} Transaksi Menunggu
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover o_list_table mb-0" id="main-table">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="ps-3 text-center no-sort">
                            <input type="checkbox" class="form-check-input">
                        </th>
                        <th class="sortable">No. PO & Status ACC</th>
                        <th class="sortable">Tgl Order</th>
                        <th class="sortable">Vendor / Supplier</th>
                        <th class="sortable">Barang / Material</th>
                        <th class="sortable text-center">Qty Dipesan</th>
                        <th class="sortable text-end">Total Biaya PO</th>
                        <th class="text-center no-sort" style="width: 200px;">Aksi Verifikasi Fisik</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingPurchases as $purchase)
                        <tr class="search-row">
                            <td class="ps-3 text-center">
                                <input type="checkbox" class="form-check-input">
                            </td>
                            <td>
                                <span class="font-mono fw-bold text-indigo-700">{{ $purchase->po_number ?? 'PO-'.$purchase->id }}</span>
                                <div class="text-[10px] text-emerald-700">
                                    <i class="fa-solid fa-circle-check text-[9px] me-0.5"></i> Di-ACC: {{ $purchase->approvedBy->username ?? 'Manajer' }}
                                </div>
                            </td>
                            <td class="text-slate-600 text-xs">
                                {{ $purchase->created_at->format('d M Y') }}
                            </td>
                            <td>
                                <span class="badge bg-slate-100 text-slate-700 border text-[11px] font-normal">
                                    {{ $purchase->supplier->name ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-slate-800">{{ $purchase->material->material_name ?? 'N/A' }}</div>
                                <span class="text-[10px] text-slate-400">Cabang: {{ $purchase->branch->nama_cabang ?? 'Pusat' }}</span>
                            </td>
                            <td class="text-center font-bold text-slate-800">
                                {{ number_format($purchase->qty_bought) }} Units
                            </td>
                            <td class="text-end font-mono fw-bold text-slate-700">
                                Rp {{ number_format($purchase->total_cost, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1.5">
                                    <button @click="
                                        verifyPurchase = {
                                            id: '{{ $purchase->id }}',
                                            po_number: '{{ $purchase->po_number ?? 'PO-'.$purchase->id }}',
                                            material_name: '{{ addslashes($purchase->material->material_name ?? 'N/A') }}',
                                            qty_bought: '{{ $purchase->qty_bought }}',
                                            supplier_name: '{{ addslashes($purchase->supplier->name ?? 'N/A') }}',
                                            total_cost: '{{ number_format($purchase->total_cost, 0, ',', '.') }}'
                                        };
                                        verifyOpen = true;
                                    " class="btn-odoo-primary py-0.5 px-2 text-xs">
                                        <i class="fa-solid fa-check me-1"></i> Terima Fisik
                                    </button>
                                    <button @click="
                                        rejectPurchase = {
                                            id: '{{ $purchase->id }}',
                                            po_number: '{{ $purchase->po_number ?? 'PO-'.$purchase->id }}',
                                            material_name: '{{ addslashes($purchase->material->material_name ?? 'N/A') }}'
                                        };
                                        rejectOpen = true;
                                    " class="btn-odoo-secondary text-rose-700 py-0.5 px-2 text-xs">
                                        <i class="fa-solid fa-xmark me-1"></i> Tolak
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="p-4">
                                    <i class="fa-solid fa-circle-check fs-1 text-emerald-400 mb-2"></i>
                                    <p class="mb-0 fw-semibold text-slate-600">Tidak ada barang masuk yang menunggu pemeriksaan fisik gudang.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Verify Modal (Odoo Form Style) -->
    <div x-show="verifyOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" style="display: none;" x-cloak>
        <div class="bg-white rounded shadow-2xl border w-full max-w-lg overflow-hidden" @click.away="verifyOpen = false">
            <form :action="'/stock/purchases/' + verifyPurchase.id + '/verify'" method="POST">
                @csrf
                <div class="bg-slate-50 border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fs-6 fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-truck-ramp-box text-teal-600"></i> Verifikasi Penerimaan Fisik Barang (GRN)
                    </h5>
                    <button type="button" class="btn-close text-xs" @click="verifyOpen = false"></button>
                </div>
                <div class="p-4 space-y-3">
                    <div class="p-3 bg-slate-50 rounded border border-slate-100 space-y-1">
                        <div class="d-flex justify-content-between text-xs">
                            <span class="text-slate-500">No. PO:</span>
                            <span class="fw-bold font-mono text-slate-900" x-text="verifyPurchase.po_number"></span>
                        </div>
                        <div class="d-flex justify-content-between text-xs">
                            <span class="text-slate-500">Bahan Baku:</span>
                            <span class="fw-bold text-slate-900" x-text="verifyPurchase.material_name"></span>
                        </div>
                        <div class="d-flex justify-content-between text-xs">
                            <span class="text-slate-500">Supplier / Vendor:</span>
                            <span class="text-slate-700" x-text="verifyPurchase.supplier_name"></span>
                        </div>
                    </div>

                    <div>
                        <label class="form-label font-semibold text-slate-700 text-xs uppercase">Jumlah Fisik Diterima (Kuantitas Masuk)</label>
                        <input type="number" name="qty_received" :value="verifyPurchase.qty_bought" required min="1" class="form-control form-control-sm font-bold">
                        <small class="text-slate-400 text-[11px]">Jumlah unit fisik yang dihitung di gudang dan akan ditambahkan ke stok aktif.</small>
                    </div>

                    <div>
                        <label class="form-label font-semibold text-slate-700 text-xs uppercase">Catatan Pemeriksaan Fisik Gudang</label>
                        <textarea name="verification_notes" rows="2" class="form-control form-control-sm" placeholder="e.g. Kemasan mulus, kuantitas lengkap, segel utuh..."></textarea>
                    </div>
                </div>
                <div class="bg-slate-50 border-top px-4 py-2.5 d-flex justify-content-end gap-2">
                    <button type="button" class="btn-odoo-secondary" @click="verifyOpen = false">Batal</button>
                    <button type="submit" class="btn-odoo-primary">Validasi & Tambahkan ke Stok</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div x-show="rejectOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" style="display: none;" x-cloak>
        <div class="bg-white rounded shadow-2xl border w-full max-w-lg overflow-hidden" @click.away="rejectOpen = false">
            <form :action="'/stock/purchases/' + rejectPurchase.id + '/reject'" method="POST">
                @csrf
                <div class="bg-rose-50 border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fs-6 fw-bold text-rose-800 mb-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-xmark text-rose-600"></i> Tolak / Retur Barang ke Supplier
                    </h5>
                    <button type="button" class="btn-close text-xs" @click="rejectOpen = false"></button>
                </div>
                <div class="p-4 space-y-3">
                    <p class="text-xs text-slate-600">
                        Anda akan menolak pengadaan <strong x-text="rejectPurchase.po_number"></strong> (<span x-text="rejectPurchase.material_name"></span>). Stok inventaris tidak akan bertambah.
                    </p>
                    <div>
                        <label class="form-label font-semibold text-slate-700 text-xs uppercase">Alasan Penolakan / Retur</label>
                        <textarea name="rejection_reason" rows="3" required class="form-control form-control-sm" placeholder="e.g. Bahan basah, sobek, gramatur kertas tidak sesuai..."></textarea>
                    </div>
                </div>
                <div class="bg-slate-50 border-top px-4 py-2.5 d-flex justify-content-end gap-2">
                    <button type="button" class="btn-odoo-secondary" @click="rejectOpen = false">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger font-semibold">Konfirmasi Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
