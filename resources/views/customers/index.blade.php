@extends('layouts.app')

@section('title', 'Data Pelanggan (Customers)')
@section('page-title', 'Orders & Penjualan / Data Pelanggan (Customer Directory)')

@section('action-buttons')
<button type="button" class="btn-odoo-primary" data-bs-toggle="modal" data-bs-target="#modalAddCustomer">
    <i class="fa-solid fa-user-plus"></i>
    <span>Tambah Pelanggan Baru</span>
</button>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>

    <!-- Top KPI Cards -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="o_form_sheet p-3 bg-white h-100 shadow-sm border-start border-4 border-blue-600 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Total Pelanggan Terdaftar</span>
                    <h3 class="text-2xl font-extrabold text-blue-900 mb-0 font-mono">{{ number_format($totalCustomers) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="o_form_sheet p-3 bg-white h-100 shadow-sm border-start border-4 border-emerald-600 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Total Belanja Pelanggan (Omzet)</span>
                    <h3 class="text-2xl font-extrabold text-emerald-800 mb-0 font-mono">Rp {{ number_format($totalOmsetCustomers ?? 0, 0, ',', '.') }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="o_form_sheet p-3 bg-white mb-3 shadow-sm rounded-3 border">
        <form method="GET" action="{{ route('customers.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-slate-50 text-slate-400 border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 text-xs" placeholder="Cari nama pelanggan, nomor WhatsApp/telepon, atau email...">
                </div>
            </div>

            @if(auth()->user()->isOwner())
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-slate-50 text-slate-700 text-xs"><i class="fa-solid fa-building me-1"></i> Cabang:</span>
                    <select name="branch_id" onchange="this.form.submit()" class="form-select text-xs">
                        <option value="all">Semua Cabang</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->nama_cabang }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn-odoo-primary py-1 px-3 text-xs flex-grow-1">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'branch_id']))
                <a href="{{ route('customers.index') }}" class="btn-odoo-secondary py-1 px-2 text-xs" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Customers Data Table -->
    <div class="o_form_sheet p-0 overflow-hidden bg-white shadow-sm rounded-3 border">
        <div class="table-responsive">
            <table class="table table-hover o_list_table mb-0" id="main-table">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="ps-3 text-center no-sort">
                            <input type="checkbox" class="form-check-input">
                        </th>
                        <th class="sortable">Nama Pelanggan</th>
                        <th class="sortable">Kontak / WhatsApp</th>
                        <th class="sortable">Cabang Registrasi</th>
                        <th class="sortable text-center">Total Pembelian</th>
                        <th class="sortable text-end pe-3">Akumulasi Belanja (Omzet)</th>
                        <th class="text-center no-sort" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $cust)
                        <tr class="search-row">
                            <td class="ps-3 text-center">
                                <input type="checkbox" class="form-check-input">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="rounded-circle bg-blue-50 text-blue-700 font-bold border border-blue-200 d-flex items-center justify-content-center text-xs flex-shrink-0" style="width: 34px; height: 34px;">
                                        {{ strtoupper(substr($cust->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('customers.show', $cust->id) }}" class="fw-bold text-slate-900 text-decoration-none hover:text-blue-600 block text-xs">
                                            {{ $cust->name }}
                                        </a>
                                        @if($cust->email)
                                            <span class="text-[10.5px] text-slate-400 block"><i class="fa-solid fa-envelope me-1 text-[9px]"></i>{{ $cust->email }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($cust->phone)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $cust->phone) }}" target="_blank" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-mono text-decoration-none hover:bg-emerald-100 transition py-1 px-2">
                                        <i class="fa-brands fa-whatsapp me-1 text-emerald-600"></i> {{ $cust->phone }}
                                    </a>
                                @else
                                    <span class="text-slate-400 text-xs italic">-</span>
                                @endif
                            </td>
                            <td>
                                @if($cust->branch)
                                    <span class="badge bg-slate-100 text-slate-700 border text-[10.5px]">
                                        <i class="fa-solid fa-shop me-1 text-slate-500"></i> {{ $cust->branch->nama_cabang }}
                                    </span>
                                @else
                                    <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10.5px]">
                                        <i class="fa-solid fa-globe me-1 text-indigo-500"></i> Seluruh Cabang
                                    </span>
                                @endif
                            </td>
                            <td class="text-center font-mono font-semibold text-slate-800 text-xs">
                                <span class="badge bg-blue-50 text-blue-800 border border-blue-200 px-2 py-0.5">
                                    {{ number_format($cust->transactions_count ?? 0) }} Transaksi
                                </span>
                            </td>
                            <td class="text-end pe-3 font-mono fw-bold text-slate-900 text-xs">
                                Rp {{ number_format($cust->transactions_sum_total_price ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('customers.show', $cust->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="Lihat Riwayat Pembelian">
                                        <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                                    </a>
                                    <button type="button" 
                                            onclick="openEditCustomerModal('{{ $cust->id }}', '{{ addslashes($cust->name) }}', '{{ addslashes($cust->phone ?? '') }}', '{{ addslashes($cust->email ?? '') }}', '{{ addslashes($cust->address ?? '') }}', '{{ addslashes($cust->notes ?? '') }}', '{{ $cust->branch_id ?? '' }}')" 
                                            class="btn btn-sm btn-outline-secondary py-0 px-2" 
                                            title="Edit Data Pelanggan">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    <form action="{{ route('customers.destroy', $cust->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data pelanggan {{ addslashes($cust->name) }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus Pelanggan">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="w-12 h-12 rounded-circle bg-slate-100 text-slate-400 d-flex align-items-center justify-content-center mx-auto mb-2 text-xl">
                                    <i class="fa-solid fa-users-slash"></i>
                                </div>
                                <h6 class="text-xs font-bold text-slate-700 mb-1">Belum Ada Data Pelanggan</h6>
                                <p class="text-[11px] text-slate-400 mb-0">Data pelanggan akan otomatis bertambah saat kasir memasukkan nama customer pada saat checkout POS.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
        <div class="p-3 border-top bg-slate-50 d-flex justify-content-between align-items-center">
            <span class="text-xs text-slate-500">
                Menampilkan {{ $customers->firstItem() }} - {{ $customers->lastItem() }} dari {{ $customers->total() }} pelanggan
            </span>
            {{ $customers->links() }}
        </div>
        @endif
    </div>

    <!-- Modal Tambah Pelanggan Baru -->
    <div class="modal fade" id="modalAddCustomer" tabindex="-1" aria-labelledby="modalAddCustomerLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 border shadow-lg overflow-hidden">
                <form action="{{ route('customers.store') }}" method="POST">
                    @csrf
                    <div class="bg-slate-900 text-white px-4 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fs-6 fw-bold text-white mb-0 d-flex align-items-center gap-2" id="modalAddCustomerLabel">
                            <i class="fa-solid fa-user-plus text-blue-400"></i> Tambah Pelanggan Baru
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 space-y-3">
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Nama Pelanggan <span class="text-danger">*</span></label>
                            <input type="text" name="name" required class="form-control form-control-sm text-xs" placeholder="Contoh: PT Sumber Rejeki / Bpk. Budi Santoso">
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label font-semibold text-slate-700 text-xs uppercase">No. WhatsApp / HP</label>
                                <input type="text" name="phone" class="form-control form-control-sm text-xs font-mono" placeholder="0812xxxxxxxx">
                            </div>
                            <div class="col-6">
                                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm text-xs" placeholder="nama@email.com">
                            </div>
                        </div>
                        @if(auth()->user()->isOwner())
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Cabang Registrasi</label>
                            <select name="branch_id" class="form-select form-select-sm text-xs">
                                <option value="">Semua Cabang / Pusat</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->nama_cabang }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                            <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                        @endif
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Alamat</label>
                            <textarea name="address" rows="2" class="form-control form-control-sm text-xs" placeholder="Alamat pengiriman / domisili..."></textarea>
                        </div>
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Catatan Tambahan</label>
                            <input type="text" name="notes" class="form-control form-control-sm text-xs" placeholder="Contoh: Langganan spanduk caleg, diskon khusus tempo">
                        </div>
                    </div>
                    <div class="bg-slate-50 border-top px-4 py-2.5 d-flex justify-content-end gap-2">
                        <button type="button" class="btn-odoo-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-odoo-primary">Simpan Pelanggan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Pelanggan -->
    <div class="modal fade" id="modalEditCustomer" tabindex="-1" aria-labelledby="modalEditCustomerLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 border shadow-lg overflow-hidden">
                <form id="formEditCustomer" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-slate-900 text-white px-4 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fs-6 fw-bold text-white mb-0 d-flex align-items-center gap-2" id="modalEditCustomerLabel">
                            <i class="fa-solid fa-user-pen text-blue-400"></i> Edit Data Pelanggan
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 space-y-3">
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Nama Pelanggan <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_cust_name" required class="form-control form-control-sm text-xs">
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label font-semibold text-slate-700 text-xs uppercase">No. WhatsApp / HP</label>
                                <input type="text" name="phone" id="edit_cust_phone" class="form-control form-control-sm text-xs font-mono">
                            </div>
                            <div class="col-6">
                                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Email</label>
                                <input type="email" name="email" id="edit_cust_email" class="form-control form-control-sm text-xs">
                            </div>
                        </div>
                        @if(auth()->user()->isOwner())
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Cabang Registrasi</label>
                            <select name="branch_id" id="edit_cust_branch_id" class="form-select form-select-sm text-xs">
                                <option value="">Semua Cabang / Pusat</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->nama_cabang }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Alamat</label>
                            <textarea name="address" id="edit_cust_address" rows="2" class="form-control form-control-sm text-xs"></textarea>
                        </div>
                        <div>
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Catatan Tambahan</label>
                            <input type="text" name="notes" id="edit_cust_notes" class="form-control form-control-sm text-xs">
                        </div>
                    </div>
                    <div class="bg-slate-50 border-top px-4 py-2.5 d-flex justify-content-end gap-2">
                        <button type="button" class="btn-odoo-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-odoo-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
function openEditCustomerModal(id, name, phone, email, address, notes, branchId) {
    const form = document.getElementById('formEditCustomer');
    form.action = '/customers/' + id;

    document.getElementById('edit_cust_name').value = name;
    document.getElementById('edit_cust_phone').value = phone;
    document.getElementById('edit_cust_email').value = email;
    document.getElementById('edit_cust_address').value = address;
    document.getElementById('edit_cust_notes').value = notes;

    const branchSelect = document.getElementById('edit_cust_branch_id');
    if (branchSelect) {
        branchSelect.value = branchId;
    }

    const modal = new bootstrap.Modal(document.getElementById('modalEditCustomer'));
    modal.show();
}
</script>
@endsection
