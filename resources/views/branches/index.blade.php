@extends('layouts.app')

@section('title', 'Branches & Companies')
@section('page-title', 'Settings / Companies & Branches (Manajemen Cabang)')

@section('action-buttons')
<button type="button" class="btn-odoo-primary" data-bs-toggle="modal" data-bs-target="#modalAddBranch">
    <i class="fa-solid fa-plus"></i>
    <span>New Branch</span>
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
                        <th class="sortable">Branch Name (Nama Cabang)</th>
                        <th class="sortable">Address (Alamat)</th>
                        <th class="sortable">Phone (Telepon)</th>
                        <th class="sortable text-center">Status</th>
                        <th class="text-center no-sort" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                        <tr class="search-row {{ $branch->trashed() ? 'opacity-50' : '' }}">
                            <td class="ps-3 text-center">
                                <input type="checkbox" class="form-check-input">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded bg-slate-100 border p-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="fa-solid fa-building-circle-check text-teal-600 text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-slate-900">{{ $branch->nama_cabang }}</div>
                                        <span class="text-[10px] text-slate-400 font-mono">Branch ID: #BR-{{ $branch->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-slate-600 text-xs">
                                {{ $branch->alamat ?? '-' }}
                            </td>
                            <td>
                                @if($branch->telepon)
                                    <span class="badge bg-light text-slate-700 border text-[11px] font-mono">
                                        <i class="fa-solid fa-phone me-1 opacity-70"></i> {{ $branch->telepon }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($branch->trashed())
                                    <span class="badge bg-amber-50 text-amber-700 border border-amber-200 text-[10px]">Archived</span>
                                @else
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px]">Active</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" 
                                            onclick="openEditBranchModal('{{ $branch->id }}', '{{ addslashes($branch->nama_cabang) }}', '{{ addslashes($branch->alamat ?? '') }}', '{{ addslashes($branch->telepon ?? '') }}')" 
                                            class="btn btn-sm btn-outline-secondary py-0 px-2" 
                                            title="Edit Cabang">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    @if(!$branch->trashed())
                                        <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Arsipkan cabang ini? Data transaksi masa lalu tetap tersimpan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Arsipkan">
                                                <i class="fa-solid fa-box-archive text-xs"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('branches.restore', $branch->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2" title="Aktifkan Kembali">
                                                <i class="fa-solid fa-rotate-left text-xs"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada unit cabang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap 5 Modal Tambah Cabang -->
    <div class="modal fade" id="modalAddBranch" tabindex="-1" aria-labelledby="modalAddBranchLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 border shadow-lg overflow-hidden">
                <form action="{{ route('branches.store') }}" method="POST">
                    @csrf
                    <div class="bg-slate-50 px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fs-6 fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2" id="modalAddBranchLabel">
                            <i class="fa-solid fa-building-circle-check text-teal-600"></i> New Company / Branch
                        </h5>
                        <button type="button" class="btn-close text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Branch Name / Nama Cabang <span class="text-danger">*</span></label>
                            <input type="text" name="nama_cabang" required class="form-control form-control-sm" placeholder="e.g. Snaprint Margonda / Cabang Depok">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Phone / Telepon</label>
                            <input type="text" name="telepon" class="form-control form-control-sm" placeholder="e.g. 021-77889900">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Full Address / Alamat Lengkap</label>
                            <textarea name="alamat" rows="3" class="form-control form-control-sm" placeholder="e.g. Jl. Margonda Raya No. 45, Depok"></textarea>
                        </div>
                    </div>
                    <div class="bg-slate-50 border-top px-4 py-2.5 d-flex justify-content-end gap-2">
                        <button type="button" class="btn-odoo-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-odoo-primary">Save Branch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Modal Edit Cabang -->
    <div class="modal fade" id="modalEditBranch" tabindex="-1" aria-labelledby="modalEditBranchLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 border shadow-lg overflow-hidden">
                <form id="formEditBranch" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-slate-50 px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fs-6 fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2" id="modalEditBranchLabel">
                            <i class="fa-solid fa-pen-to-square text-teal-600"></i> Edit Branch
                        </h5>
                        <button type="button" class="btn-close text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Branch Name / Nama Cabang <span class="text-danger">*</span></label>
                            <input type="text" name="nama_cabang" id="edit_nama_cabang" required class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Phone / Telepon</label>
                            <input type="text" name="telepon" id="edit_telepon" class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold text-slate-700 text-xs uppercase">Full Address / Alamat Lengkap</label>
                            <textarea name="alamat" id="edit_alamat" rows="3" class="form-control form-control-sm"></textarea>
                        </div>
                    </div>
                    <div class="bg-slate-50 border-top px-4 py-2.5 d-flex justify-content-end gap-2">
                        <button type="button" class="btn-odoo-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-odoo-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openEditBranchModal(id, namaCabang, alamat, telepon) {
    const form = document.getElementById('formEditBranch');
    form.action = '/branches/' + id;
    
    document.getElementById('edit_nama_cabang').value = namaCabang;
    document.getElementById('edit_alamat').value = alamat;
    document.getElementById('edit_telepon').value = telepon;
    
    const modalEl = document.getElementById('modalEditBranch');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}
</script>
@endsection
