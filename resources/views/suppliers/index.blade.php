@extends('layouts.app')

@section('title', 'Data Supplier')
@section('page-title', 'Master Data Supplier')

@section('content')

<div class="mb-6 flex flex-wrap justify-between items-center gap-3" id="supplier-wrapper" data-view-wrapper>
    <div class="flex items-center gap-3 flex-wrap">
        <p class="text-gray-500 text-sm mb-0">Kelola daftar supplier / vendor untuk pengadaan.</p>
        <div class="relative w-64">
            <input type="text" class="table-search-input form-control w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs" placeholder="🔍 Cari supplier, perusahaan...">
        </div>
    </div>
    
    <div class="flex items-center gap-3">
        <!-- Dual View Switcher Toggle Buttons -->
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-primary btn-view-list active font-semibold" onclick="toggleViewMode('list', 'supplier-wrapper')">
                <i class="bi bi-list-task me-1"></i> List Table
            </button>
            <button type="button" class="btn btn-outline-secondary btn-view-grid font-semibold" onclick="toggleViewMode('grid', 'supplier-wrapper')">
                <i class="bi bi-grid-3x3-gap-fill me-1"></i> Card Grid
            </button>
        </div>

        <button onclick="document.getElementById('modal-add').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition shadow-sm flex items-center">
            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Supplier
        </button>
    </div>
</div>

<!-- Table & Grid Cards Container -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- Mode 1: Table List View -->
    <div class="table-view-container overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider font-semibold">
                    <th class="px-6 py-4 sortable">No</th>
                    <th class="px-6 py-4 sortable">Nama Supplier</th>
                    <th class="px-6 py-4 sortable">Perusahaan</th>
                    <th class="px-6 py-4 sortable">Kontak</th>
                    <th class="px-6 py-4 sortable">Alamat</th>
                    <th class="px-6 py-4 text-center no-sort">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($suppliers as $supplier)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $loop->iteration }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $supplier->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $supplier->perusahaan ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $supplier->kontak ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $supplier->alamat ?? '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center space-x-2">
                                <button onclick="openEditModal({{ $supplier->toJson() }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus supplier ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">
                            Belum ada data supplier.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mode 2: Grid / Card View (Dynamic Kotak-Kotak Gen-Z Style) -->
    <div class="grid-view-container d-none p-4">
        <div class="row g-4">
            @forelse($suppliers as $supplier)
                <div class="col-12 col-sm-6 col-md-4 grid-card">
                    <div class="card h-100 border rounded-4 shadow-sm hover-shadow transition">
                        <div class="card-header bg-light border-bottom p-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark fs-6">{{ $supplier->name }}</span>
                            <span class="badge bg-indigo-subtle text-indigo border border-indigo-subtle">Vendor</span>
                        </div>
                        <div class="card-body p-3 space-y-2">
                            <div>
                                <small class="text-muted text-xs d-block">Perusahaan:</small>
                                <span class="fw-bold text-dark">{{ $supplier->perusahaan ?? '-' }}</span>
                            </div>
                            <div>
                                <small class="text-muted text-xs d-block">Kontak / HP:</small>
                                <span class="fw-semibold text-primary"><i class="bi bi-telephone me-1"></i> {{ $supplier->kontak ?? '-' }}</span>
                            </div>
                            <div>
                                <small class="text-muted text-xs d-block">Alamat:</small>
                                <span class="text-muted text-xs">{{ $supplier->alamat ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top p-3 text-end">
                            <button onclick="openEditModal({{ $supplier->toJson() }})" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </button>
                            <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus supplier?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2">
                                    <i class="bi bi-trash"></i>
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

<!-- Modal Tambah -->
<div id="modal-add" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('modal-add').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <form action="{{ route('suppliers.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg font-bold leading-6 text-gray-900 mb-6">Tambah Supplier Baru</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Supplier <span class="text-red-500">*</span></label>
                                        <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Perusahaan</label>
                                        <input type="text" name="perusahaan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Kontak / No. Telepon</label>
                                        <input type="text" name="kontak" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                                        <textarea name="alamat" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Simpan</button>
                        <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div id="modal-edit" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('modal-edit').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <form id="edit-form" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg font-bold leading-6 text-gray-900 mb-6">Edit Supplier</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Supplier <span class="text-red-500">*</span></label>
                                        <input type="text" name="name" id="edit-name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Perusahaan</label>
                                        <input type="text" name="perusahaan" id="edit-perusahaan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Kontak / No. Telepon</label>
                                        <input type="text" name="kontak" id="edit-kontak" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                                        <textarea name="alamat" id="edit-alamat" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Simpan Perubahan</button>
                        <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openEditModal(supplier) {
        document.getElementById('edit-name').value = supplier.name;
        document.getElementById('edit-perusahaan').value = supplier.perusahaan || '';
        document.getElementById('edit-kontak').value = supplier.kontak || '';
        document.getElementById('edit-alamat').value = supplier.alamat || '';
        
        document.getElementById('edit-form').action = `/suppliers/${supplier.id}`;
        document.getElementById('modal-edit').classList.remove('hidden');
    }
</script>

@endsection
