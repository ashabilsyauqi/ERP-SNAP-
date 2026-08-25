@extends('layouts.app')

@section('title', 'Users & Access Rights')
@section('page-title', 'Settings / Users & Companies / Users (Hak Akses Pengguna)')

@section('action-buttons')
<button type="button" class="btn-odoo-primary" data-bs-toggle="modal" data-bs-target="#modalAddUser">
    <i class="fa-solid fa-plus"></i>
    <span>New User</span>
</button>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>

    <!-- Main Odoo Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden bg-white">
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
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pengguna ini?');">
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
                            <input type="text" name="username" required class="form-control form-control-sm" placeholder="e.g. johan_kasir">
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
                                <option value="owner">Owner / Administrator (Full Access)</option>
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
                                <option value="owner">Owner / Administrator (Full Access)</option>
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
