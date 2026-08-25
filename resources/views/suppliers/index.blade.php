@extends('layouts.app')

@section('title', 'Vendors & Suppliers')
@section('page-title', 'Vendors (Data Supplier & Rekening Pemasok)')

@section('action-buttons')
<button type="button" onclick="document.getElementById('modal-add').classList.remove('hidden')" class="btn-odoo-primary">
    <i class="fa-solid fa-plus me-1"></i>
    <span>Tambah Vendor Baru</span>
</button>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Main Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden bg-white">
        <!-- View Mode 1: Table List View -->
        <div class="table-view-container">
            <div class="table-responsive">
                <table class="table table-hover o_list_table mb-0" id="main-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="ps-3 text-center no-sort">
                                <input type="checkbox" class="form-check-input">
                            </th>
                            <th class="sortable">Nama Kontak / PIC</th>
                            <th class="sortable">Nama Perusahaan</th>
                            <th class="sortable">Nomor Kontak / WA</th>
                            <th class="sortable">Rekening Bank Pembayaran</th>
                            <th class="sortable">Alamat Gudang / Kantor</th>
                            <th class="text-center no-sort" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                            <tr class="search-row">
                                <td class="ps-3 text-center">
                                    <input type="checkbox" class="form-check-input">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded bg-blue-50 border p-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                            <i class="fa-solid fa-building text-blue-700 text-xs"></i>
                                        </div>
                                        <div>
                                            <a href="#" onclick="openEditModal({{ $supplier->toJson() }}); return false;" class="fw-bold text-slate-800 text-decoration-none hover:text-blue-700">
                                                {{ $supplier->name }}
                                            </a>
                                            <div class="text-[10px] text-slate-400 font-mono">#SUP-{{ $supplier->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-slate-700 fw-semibold">{{ $supplier->perusahaan ?? '-' }}</span>
                                </td>
                                <td>
                                    @if($supplier->kontak)
                                        <span class="badge bg-light text-slate-700 border text-[11px] font-mono">
                                            <i class="fa-solid fa-phone me-1 opacity-70"></i> {{ $supplier->kontak }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($supplier->bank_name || $supplier->bank_account_number)
                                        <div class="d-flex align-items-center gap-1.5">
                                            <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold px-2 py-0.5 text-[11px]">
                                                {{ $supplier->bank_name ?? 'Bank' }}
                                            </span>
                                            <span class="font-mono fw-bold text-slate-800 text-xs">{{ $supplier->bank_account_number }}</span>
                                        </div>
                                        @if($supplier->bank_account_name)
                                            <div class="text-[10px] text-slate-500 mt-0.5">a/n: <strong>{{ $supplier->bank_account_name }}</strong></div>
                                        @endif
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">Belum diatur</span>
                                    @endif
                                </td>
                                <td class="text-slate-600 text-xs line-clamp-1">
                                    {{ $supplier->alamat ?? '-' }}
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button onclick="openEditModal({{ $supplier->toJson() }})" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Edit Data & Rekening Vendor">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>
                                        <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus supplier ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="p-4">
                                        <i class="fa-solid fa-building fs-1 text-slate-300 mb-2"></i>
                                        <p class="mb-0">Belum ada data supplier / vendor.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- View Mode 2: Kanban Cards -->
        <div class="grid-view-container d-none p-4 bg-slate-50 border-top">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @forelse($suppliers as $supplier)
                    <div class="o_kanban_record bg-white border rounded p-3 shadow-sm hover:shadow transition search-card" style="border-left: 4px solid #1E3A8A !important;">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="rounded bg-blue-50 text-blue-700 p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="fa-solid fa-building fs-5"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h6 class="fw-bold text-slate-900 mb-0 line-clamp-1 fs-6">{{ $supplier->name }}</h6>
                                <span class="text-slate-400 text-[11px]">{{ $supplier->perusahaan ?? 'Individu' }}</span>
                            </div>
                        </div>
                        <div class="text-[11px] text-slate-600 mb-1">
                            <i class="fa-solid fa-phone me-1 opacity-70"></i> {{ $supplier->kontak ?? '-' }}
                        </div>
                        @if($supplier->bank_account_number)
                            <div class="text-[11px] text-indigo-700 bg-indigo-50 rounded p-1.5 mb-2 font-mono">
                                <i class="fa-solid fa-credit-card me-1"></i> {{ $supplier->bank_name }}: <strong>{{ $supplier->bank_account_number }}</strong>
                                <div class="text-[10px] text-slate-500 font-sans">a/n {{ $supplier->bank_account_name }}</div>
                            </div>
                        @endif
                        <div class="text-[11px] text-slate-500 line-clamp-1 mb-3">
                            <i class="fa-solid fa-location-dot me-1 opacity-70"></i> {{ $supplier->alamat ?? '-' }}
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-slate-100">
                            <span class="text-[10px] font-mono text-slate-400">#SUP-{{ $supplier->id }}</span>
                            <div class="btn-group btn-group-sm">
                                <button onclick="openEditModal({{ $supplier->toJson() }})" class="btn btn-sm btn-outline-secondary py-0 px-2">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                                <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus supplier ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">Belum ada data supplier.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Supplier (Centered Flex Modal) -->
<div id="modal-add" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 hidden">
    <div class="bg-white rounded-3 shadow-2xl border w-full max-w-lg overflow-hidden my-auto" onclick="event.stopPropagation()">
        <form action="{{ route('suppliers.store') }}" method="POST">
            @csrf
            <div class="bg-slate-50 px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fs-6 font-bold text-slate-800 d-flex align-items-center gap-2 mb-0">
                    <i class="fa-solid fa-building text-blue-700"></i> Tambah Vendor / Supplier Baru
                </h5>
                <button type="button" class="btn-close text-xs" onclick="document.getElementById('modal-add').classList.add('hidden')"></button>
            </div>
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label font-semibold text-slate-700 text-xs uppercase">Nama Kontak / PIC <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required class="form-control form-control-sm" placeholder="e.g. Budi Santoso">
                    </div>
                    <div>
                        <label class="form-label font-semibold text-slate-700 text-xs uppercase">Nama Perusahaan / Toko</label>
                        <input type="text" name="perusahaan" class="form-control form-control-sm" placeholder="e.g. PT. Bintang Terang Media">
                    </div>
                </div>

                <div>
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">Nomor Kontak / WhatsApp</label>
                    <input type="text" name="kontak" class="form-control form-control-sm" placeholder="e.g. 08123456789">
                </div>

                <!-- Rekening Bank Fields -->
                <div class="p-3 bg-slate-50 rounded-2 border space-y-2.5">
                    <div class="text-xs font-bold text-slate-800 uppercase d-flex align-items-center gap-1.5">
                        <i class="fa-solid fa-credit-card text-indigo-600"></i> Informasi Rekening Bank (Untuk Pembayaran Tagihan)
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        <div>
                            <label class="form-label text-slate-600 text-[11px] mb-1">Nama Bank</label>
                            <input type="text" name="bank_name" class="form-control form-control-sm" placeholder="BCA / Mandiri / BRI">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label text-slate-600 text-[11px] mb-1">Nomor Rekening</label>
                            <input type="text" name="bank_account_number" class="form-control form-control-sm font-mono" placeholder="e.g. 8820192831">
                        </div>
                    </div>
                    <div>
                        <label class="form-label text-slate-600 text-[11px] mb-1">Atas Nama (Nama Pemilik Rekening)</label>
                        <input type="text" name="bank_account_name" class="form-control form-control-sm" placeholder="e.g. PT Bintang Terang Media / Budi Santoso">
                    </div>
                </div>

                <div>
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">Alamat Kantor / Gudang</label>
                    <textarea name="alamat" rows="2" class="form-control form-control-sm" placeholder="e.g. Jl. Industri No. 12, Bekasi"></textarea>
                </div>
            </div>
            <div class="bg-slate-50 border-top px-4 py-2.5 d-flex justify-content-end gap-2">
                <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')" class="btn-odoo-secondary">Batal</button>
                <button type="submit" class="btn-odoo-primary">Simpan Vendor</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Supplier (Centered Flex Modal) -->
<div id="modal-edit" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 hidden">
    <div class="bg-white rounded-3 shadow-2xl border w-full max-w-lg overflow-hidden my-auto" onclick="event.stopPropagation()">
        <form id="form-edit" method="POST">
            @csrf
            @method('PUT')
            <div class="bg-slate-50 px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fs-6 font-bold text-slate-800 d-flex align-items-center gap-2 mb-0">
                    <i class="fa-solid fa-pen-to-square text-blue-700"></i> Edit Data Vendor & Rekening
                </h5>
                <button type="button" class="btn-close text-xs" onclick="document.getElementById('modal-edit').classList.add('hidden')"></button>
            </div>
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label font-semibold text-slate-700 text-xs uppercase">Nama Kontak / PIC <span class="text-rose-500">*</span></label>
                        <input type="text" id="edit-name" name="name" required class="form-control form-control-sm">
                    </div>
                    <div>
                        <label class="form-label font-semibold text-slate-700 text-xs uppercase">Nama Perusahaan / Toko</label>
                        <input type="text" id="edit-perusahaan" name="perusahaan" class="form-control form-control-sm">
                    </div>
                </div>

                <div>
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">Nomor Kontak</label>
                    <input type="text" id="edit-kontak" name="kontak" class="form-control form-control-sm">
                </div>

                <!-- Rekening Bank Fields -->
                <div class="p-3 bg-slate-50 rounded-2 border space-y-2.5">
                    <div class="text-xs font-bold text-slate-800 uppercase d-flex align-items-center gap-1.5">
                        <i class="fa-solid fa-credit-card text-indigo-600"></i> Informasi Rekening Bank (Untuk Pembayaran Tagihan)
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        <div>
                            <label class="form-label text-slate-600 text-[11px] mb-1">Nama Bank</label>
                            <input type="text" id="edit-bank-name" name="bank_name" class="form-control form-control-sm" placeholder="BCA / Mandiri / BRI">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label text-slate-600 text-[11px] mb-1">Nomor Rekening</label>
                            <input type="text" id="edit-bank-account-number" name="bank_account_number" class="form-control form-control-sm font-mono" placeholder="e.g. 8820192831">
                        </div>
                    </div>
                    <div>
                        <label class="form-label text-slate-600 text-[11px] mb-1">Atas Nama (Nama Pemilik Rekening)</label>
                        <input type="text" id="edit-bank-account-name" name="bank_account_name" class="form-control form-control-sm" placeholder="e.g. PT Bintang Terang Media / Budi Santoso">
                    </div>
                </div>

                <div>
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">Alamat</label>
                    <textarea id="edit-alamat" name="alamat" rows="2" class="form-control form-control-sm"></textarea>
                </div>
            </div>
            <div class="bg-slate-50 border-top px-4 py-2.5 d-flex justify-content-end gap-2">
                <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')" class="btn-odoo-secondary">Batal</button>
                <button type="submit" class="btn-odoo-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(supplier) {
        document.getElementById('form-edit').action = `/suppliers/${supplier.id}`;
        document.getElementById('edit-name').value = supplier.name || '';
        document.getElementById('edit-perusahaan').value = supplier.perusahaan || '';
        document.getElementById('edit-kontak').value = supplier.kontak || '';
        document.getElementById('edit-alamat').value = supplier.alamat || '';
        document.getElementById('edit-bank-name').value = supplier.bank_name || '';
        document.getElementById('edit-bank-account-number').value = supplier.bank_account_number || '';
        document.getElementById('edit-bank-account-name').value = supplier.bank_account_name || '';
        document.getElementById('modal-edit').classList.remove('hidden');
    }
</script>
@endsection
