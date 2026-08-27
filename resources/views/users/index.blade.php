@extends('layouts.app')

@section('title', 'Users & Access Rights')
@section('page-title', 'Settings / Users & Companies / Users (Hak Akses Pengguna & Pengaturan Modul)')

@section('action-buttons')
<button type="button" class="btn-odoo-primary" data-bs-toggle="modal" data-bs-target="#modalAddUser">
    <i class="fa-solid fa-plus"></i>
    <span>New User</span>
</button>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper x-data="{ currentTab: 'users' }">

    <!-- Top Tabs Switcher -->
    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
        <ul class="nav nav-pills gap-2">
            <li class="nav-item">
                <button class="nav-link px-3 py-1.5 rounded-2 text-xs fw-bold cursor-pointer transition" 
                        :class="currentTab === 'users' ? 'active bg-blue-700 text-white shadow-sm' : 'bg-white text-slate-700 border hover:bg-slate-50'"
                        @click="currentTab = 'users'">
                    <i class="fa-solid fa-users me-1.5"></i>
                    <span>Daftar Pengguna ({{ $users->count() }})</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link px-3 py-1.5 rounded-2 text-xs fw-bold cursor-pointer transition" 
                        :class="currentTab === 'matrix' ? 'active bg-blue-700 text-white shadow-sm' : 'bg-white text-slate-700 border hover:bg-slate-50'"
                        @click="currentTab = 'matrix'">
                    <i class="fa-solid fa-shield-halved me-1.5"></i>
                    <span>Pengaturan Hak Akses & Matriks Modul</span>
                </button>
            </li>
        </ul>
        <div class="text-[11px] text-slate-500 d-none d-md-block">
            <i class="fa-solid fa-circle-info text-blue-600 me-1"></i>
            Role menentukan otorisasi modul, cabang, dan tindakan bisnis pada sistem ERP.
        </div>
    </div>

    <!-- TAB 1: USERS LIST TABLE -->
    <div x-show="currentTab === 'users'" class="o_form_sheet p-0 overflow-hidden bg-white shadow-sm rounded-3 border">
        <div class="table-responsive">
            <table class="table table-hover o_list_table mb-0" id="main-table">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="ps-3 text-center no-sort">
                            <input type="checkbox" class="form-check-input">
                        </th>
                        <th class="sortable">User / Login Name</th>
                        <th class="sortable">Role / Access Group</th>
                        <th class="sortable">Assigned Branch (Cabang)</th>
                        <th class="sortable text-center">Digital Signature</th>
                        <th class="text-center no-sort" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="search-row">
                            <td class="ps-3 text-center">
                                <input type="checkbox" class="form-check-input">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded bg-slate-100 border p-1 d-flex align-items-center justify-content-center fw-bold text-slate-700 text-xs" style="width: 32px; height: 32px;">
                                        {{ strtoupper(substr($user->username, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-slate-900">
                                            {{ $user->username }}
                                            @if(auth()->id() === $user->id)
                                                <span class="badge bg-teal-50 text-teal-700 border border-teal-200 text-[10px] ms-1">You</span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-mono">User ID: #USR-{{ $user->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $roleBadge = match($user->role) {
                                        'owner' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'manager' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'cashier' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'purchasing' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200'
                                    };
                                @endphp
                                <span class="badge {{ $roleBadge }} border text-[11px] font-semibold text-capitalize">
                                    <i class="fa-solid fa-shield-halved text-[9px] me-1 opacity-70"></i>
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td>
                                @if($user->branch)
                                    <span class="badge bg-slate-100 text-slate-700 border text-[11px] font-normal">
                                        <i class="fa-solid fa-building me-1 opacity-60"></i> {{ $user->branch->nama_cabang }}
                                    </span>
                                @else
                                    <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 text-[11px] font-semibold">
                                        <i class="fa-solid fa-globe me-1 opacity-60"></i> All Branches (Pusat)
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($user->signature_path)
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-semibold">
                                        <i class="fa-solid fa-signature me-1"></i> Registered
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[11px] italic">None</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Edit User" 
                                            onclick="openEditUserModal('{{ $user->id }}', '{{ addslashes($user->username) }}', '{{ $user->role }}', '{{ $user->branch_id ?? '' }}')">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    @if(auth()->id() !== $user->id)
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus/nonaktifkan pengguna {{ addslashes($user->username) }}? Riwayat transaksi historis tetap terjaga aman.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus User">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada pengguna terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: ACCESS RIGHTS & MODULE SETTINGS MATRIX -->
    <div x-show="currentTab === 'matrix'" class="o_form_sheet p-4 bg-white shadow-sm rounded-3 border" style="display: none;">
        <div class="mb-4">
            <h5 class="fw-bold text-slate-800 fs-6 mb-1">
                <i class="fa-solid fa-sliders text-blue-600 me-1.5"></i> Matriks Hak Akses & Pembagian Otoritas Modul
            </h5>
            <p class="text-xs text-slate-500 mb-0">
                Berikut adalah matriks resmi pembagian hak akses modul untuk setiap level pengguna (*Role-Based Access Control / RBAC*):
            </p>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle text-xs mb-0">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="py-2.5 px-3">Modul / Fitur Aplikasi</th>
                        <th class="text-center py-2.5 px-2 bg-purple-50/70 text-purple-900" style="width: 20%;">
                            <i class="fa-solid fa-crown me-1 text-purple-600"></i> Owner / Direksi
                        </th>
                        <th class="text-center py-2.5 px-2 bg-sky-50/70 text-sky-900" style="width: 20%;">
                            <i class="fa-solid fa-user-tie me-1 text-sky-600"></i> Manajer Cabang
                        </th>
                        <th class="text-center py-2.5 px-2 bg-amber-50/70 text-amber-900" style="width: 20%;">
                            <i class="fa-solid fa-cart-shopping me-1 text-amber-600"></i> Purchasing
                        </th>
                        <th class="text-center py-2.5 px-2 bg-emerald-50/70 text-emerald-900" style="width: 20%;">
                            <i class="fa-solid fa-cash-register me-1 text-emerald-600"></i> Kasir (POS)
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Executive Dashboard -->
                    <tr>
                        <td class="px-3 fw-bold text-slate-800">
                            <i class="fa-solid fa-chart-pie text-indigo-600 me-2"></i> Dashboard Eksekutif Konsolidasi
                            <div class="text-[10px] text-slate-400 font-normal">Omzet, HPP, OPEX & Laba Bersih Multi-Cabang</div>
                        </td>
                        <td class="text-center bg-purple-50/30 font-semibold text-emerald-700">
                            <i class="fa-solid fa-circle-check fs-6 text-emerald-600"></i><br>
                            <span class="text-[10px]">Semua Cabang</span>
                        </td>
                        <td class="text-center bg-sky-50/30 font-semibold text-blue-700">
                            <i class="fa-solid fa-circle-check fs-6 text-blue-600"></i><br>
                            <span class="text-[10px]">Cabang Sendiri</span>
                        </td>
                        <td class="text-center text-slate-300"><i class="fa-solid fa-circle-xmark fs-6"></i></td>
                        <td class="text-center text-slate-300"><i class="fa-solid fa-circle-xmark fs-6"></i></td>
                    </tr>

                    <!-- Accounting & Finance -->
                    <tr>
                        <td class="px-3 fw-bold text-slate-800">
                            <i class="fa-solid fa-wallet text-emerald-600 me-2"></i> Akuntansi & Buku Kas
                            <div class="text-[10px] text-slate-400 font-normal">Kas Masuk, Kas Keluar, Mutasi & Laba Rugi</div>
                        </td>
                        <td class="text-center bg-purple-50/30 font-semibold text-emerald-700">
                            <i class="fa-solid fa-circle-check fs-6 text-emerald-600"></i><br>
                            <span class="text-[10px]">Full Global</span>
                        </td>
                        <td class="text-center bg-sky-50/30 font-semibold text-blue-700">
                            <i class="fa-solid fa-circle-check fs-6 text-blue-600"></i><br>
                            <span class="text-[10px]">Input Kas Cabang</span>
                        </td>
                        <td class="text-center text-slate-300"><i class="fa-solid fa-circle-xmark fs-6"></i></td>
                        <td class="text-center text-slate-300"><i class="fa-solid fa-circle-xmark fs-6"></i></td>
                    </tr>

                    <!-- Terminal Kasir POS -->
                    <tr class="table-warning/20">
                        <td class="px-3 fw-bold text-slate-800">
                            <i class="fa-solid fa-cash-register text-rose-600 me-2"></i> Terminal Kasir POS (Point of Sale)
                            <div class="text-[10px] text-slate-400 font-normal">Checkout Pesanan, Nota Struk & Buka/Tutup Shift Kasir</div>
                        </td>
                        <td class="text-center bg-purple-50/30 font-semibold text-rose-600">
                            <i class="fa-solid fa-ban fs-6 text-rose-500"></i><br>
                            <span class="text-[10px] text-rose-600">Dinonaktifkan</span>
                        </td>
                        <td class="text-center bg-sky-50/30 font-semibold text-rose-600">
                            <i class="fa-solid fa-ban fs-6 text-rose-500"></i><br>
                            <span class="text-[10px] text-rose-600">Dinonaktifkan</span>
                        </td>
                        <td class="text-center text-slate-300"><i class="fa-solid fa-circle-xmark fs-6"></i></td>
                        <td class="text-center bg-emerald-50/30 font-semibold text-emerald-700">
                            <i class="fa-solid fa-circle-check fs-6 text-emerald-600"></i><br>
                            <span class="text-[10px]">Khusus Kasir</span>
                        </td>
                    </tr>

                    <!-- Penjualan & Piutang -->
                    <tr>
                        <td class="px-3 fw-bold text-slate-800">
                            <i class="fa-solid fa-receipt text-blue-600 me-2"></i> Orders & Piutang Pelanggan (DP)
                            <div class="text-[10px] text-slate-400 font-normal">Monitoring status SPK, pelunasan sisa DP & cetak invoice</div>
                        </td>
                        <td class="text-center bg-purple-50/30 font-semibold text-emerald-700">
                            <i class="fa-solid fa-circle-check fs-6 text-emerald-600"></i><br>
                            <span class="text-[10px]">Edit & Refund</span>
                        </td>
                        <td class="text-center bg-sky-50/30 font-semibold text-blue-700">
                            <i class="fa-solid fa-circle-check fs-6 text-blue-600"></i><br>
                            <span class="text-[10px]">Settle & Update Status</span>
                        </td>
                        <td class="text-center text-slate-300"><i class="fa-solid fa-circle-xmark fs-6"></i></td>
                        <td class="text-center bg-emerald-50/30 font-semibold text-emerald-700">
                            <i class="fa-solid fa-circle-check fs-6 text-emerald-600"></i><br>
                            <span class="text-[10px]">Settle & Cetak</span>
                        </td>
                    </tr>

                    <!-- Pengadaan & Purchasing -->
                    <tr>
                        <td class="px-3 fw-bold text-slate-800">
                            <i class="fa-solid fa-cart-shopping text-amber-600 me-2"></i> Pengadaan (Purchasing) & Supplier
                            <div class="text-[10px] text-slate-400 font-normal">Rencana Pembelian, RFQ Supplier & Pembayaran Vendor</div>
                        </td>
                        <td class="text-center bg-purple-50/30 font-semibold text-emerald-700">
                            <i class="fa-solid fa-circle-check fs-6 text-emerald-600"></i><br>
                            <span class="text-[10px]">Approval & Pay</span>
                        </td>
                        <td class="text-center bg-sky-50/30 font-semibold text-blue-700">
                            <i class="fa-solid fa-circle-check fs-6 text-blue-600"></i><br>
                            <span class="text-[10px]">Approval Cabang</span>
                        </td>
                        <td class="text-center bg-amber-50/30 font-semibold text-amber-700">
                            <i class="fa-solid fa-circle-check fs-6 text-amber-600"></i><br>
                            <span class="text-[10px]">Create RFQ & Plans</span>
                        </td>
                        <td class="text-center text-slate-300"><i class="fa-solid fa-circle-xmark fs-6"></i></td>
                    </tr>

                    <!-- QC & Stock Opname -->
                    <tr>
                        <td class="px-3 fw-bold text-slate-800">
                            <i class="fa-solid fa-boxes-stacked text-orange-600 me-2"></i> Stock Opname & QC Inspection
                            <div class="text-[10px] text-slate-400 font-normal">Pemeriksaan barang datang supplier & opname stok fisik</div>
                        </td>
                        <td class="text-center bg-purple-50/30 font-semibold text-emerald-700">
                            <i class="fa-solid fa-circle-check fs-6 text-emerald-600"></i><br>
                            <span class="text-[10px]">Monitoring Global</span>
                        </td>
                        <td class="text-center bg-sky-50/30 font-semibold text-blue-700">
                            <i class="fa-solid fa-circle-check fs-6 text-blue-600"></i><br>
                            <span class="text-[10px]">Verifikasi QC & Opname</span>
                        </td>
                        <td class="text-center text-slate-300"><i class="fa-solid fa-circle-xmark fs-6"></i></td>
                        <td class="text-center text-slate-300"><i class="fa-solid fa-circle-xmark fs-6"></i></td>
                    </tr>

                    <!-- Pengaturan Cabang & Pengguna -->
                    <tr>
                        <td class="px-3 fw-bold text-slate-800">
                            <i class="fa-solid fa-gear text-slate-700 me-2"></i> Pengaturan Cabang & Pengguna
                            <div class="text-[10px] text-slate-400 font-normal">Manajemen data cabang toko, user login & digital signature</div>
                        </td>
                        <td class="text-center bg-purple-50/30 font-semibold text-emerald-700">
                            <i class="fa-solid fa-circle-check fs-6 text-emerald-600"></i><br>
                            <span class="text-[10px]">Full Semua User & Cabang</span>
                        </td>
                        <td class="text-center bg-sky-50/30 font-semibold text-blue-700">
                            <i class="fa-solid fa-circle-check fs-6 text-blue-600"></i><br>
                            <span class="text-[10px]">User Kasir Cabangnya</span>
                        </td>
                        <td class="text-center text-slate-300"><i class="fa-solid fa-circle-xmark fs-6"></i></td>
                        <td class="text-center text-slate-300"><i class="fa-solid fa-circle-xmark fs-6"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap 5 Modal Tambah User -->
    <div class="modal fade" id="modalAddUser" tabindex="-1" aria-labelledby="modalAddUserLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 border shadow-lg overflow-hidden">
                <form action="{{ route('users.store') }}" method="POST">
                    @csrf
                    <div class="bg-slate-50 px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fs-6 fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2" id="modalAddUserLabel">
                            <i class="fa-solid fa-user-plus text-blue-600"></i> Tambah Pengguna Baru
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Username / Login ID <span class="text-danger">*</span></label>
                            <input type="text" name="username" required class="form-control form-control-sm" placeholder="e.g. kasir_zamrud">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" required class="form-control form-control-sm" placeholder="Min. 6 karakter">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Role Akses <span class="text-danger">*</span></label>
                            <select name="role" id="add_user_role" required class="form-select form-select-sm" onchange="toggleBranchInput('add')">
                                <option value="cashier">Kasir (Point of Sale)</option>
                                <option value="purchasing">Purchasing (Pengadaan)</option>
                                <option value="manager">Manager Toko (Approval & QC)</option>
                                @if(auth()->user()->isOwner())
                                    <option value="owner">Owner / Administrator (Full Access)</option>
                                @endif
                            </select>
                        </div>
                        <div class="mb-3" id="add_branch_container">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Penempatan Cabang <span class="text-danger">*</span></label>
                            <select name="branch_id" id="add_user_branch_id" class="form-select form-select-sm">
                                <option value="">-- Pilih Cabang --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->nama_cabang }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="bg-slate-50 border-top px-4 py-2.5 d-flex justify-content-end gap-2">
                        <button type="button" class="btn-odoo-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-odoo-primary">Simpan User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Modal Edit User -->
    <div class="modal fade" id="modalEditUser" tabindex="-1" aria-labelledby="modalEditUserLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 border shadow-lg overflow-hidden">
                <form id="formEditUser" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-slate-50 px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fs-6 fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2" id="modalEditUserLabel">
                            <i class="fa-solid fa-pen-to-square text-blue-600"></i> Edit Pengguna
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="edit_username" required class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Password (Kosongkan jika tidak diubah)</label>
                            <input type="password" name="password" class="form-control form-control-sm" placeholder="••••••••">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Role Akses <span class="text-danger">*</span></label>
                            <select name="role" id="edit_user_role" required class="form-select form-select-sm" onchange="toggleBranchInput('edit')">
                                <option value="cashier">Kasir (Point of Sale)</option>
                                <option value="purchasing">Purchasing (Pengadaan)</option>
                                <option value="manager">Manager Toko (Approval & QC)</option>
                                @if(auth()->user()->isOwner())
                                    <option value="owner">Owner / Administrator (Full Access)</option>
                                @endif
                            </select>
                        </div>
                        <div class="mb-3" id="edit_branch_container">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Penempatan Cabang</label>
                            <select name="branch_id" id="edit_user_branch_id" class="form-select form-select-sm">
                                <option value="">-- Pilih Cabang --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->nama_cabang }}</option>
                                @endforeach
                            </select>
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
function toggleBranchInput(mode) {
    const roleSelect = document.getElementById(mode + '_user_role');
    const branchContainer = document.getElementById(mode + '_branch_container');
    const branchSelect = document.getElementById(mode + '_user_branch_id');
    
    if (roleSelect.value === 'owner') {
        branchContainer.style.display = 'none';
        if (branchSelect) branchSelect.removeAttribute('required');
    } else {
        branchContainer.style.display = 'block';
        if (branchSelect) branchSelect.setAttribute('required', 'required');
    }
}

function openEditUserModal(id, username, role, branchId) {
    const form = document.getElementById('formEditUser');
    form.action = '/users/' + id;
    
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_user_role').value = role;
    document.getElementById('edit_user_branch_id').value = branchId;
    
    toggleBranchInput('edit');
    
    const modalEl = document.getElementById('modalEditUser');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    toggleBranchInput('add');
});
</script>
@endsection
