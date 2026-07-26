@extends('layouts.app')

@section('title', 'Manajemen Cabang')
@section('page-title', 'Manajemen Cabang')

@section('content')
<div x-data="{ 
    addOpen: false, 
    editOpen: false, 
    editBranch: { id: '', nama_cabang: '', alamat: '', telepon: '' }
}" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Manajemen Cabang</h2>
            <p class="text-sm text-slate-500">Kelola unit cabang printshop Anda beserta kontak dan alamatnya.</p>
        </div>
        <button @click="addOpen = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition shadow-sm flex items-center">
            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Tambah Cabang
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-55/50 border-b border-slate-100 text-slate-500 text-xs uppercase tracking-wider font-semibold">
                        <th class="px-6 py-4">No</th>
                        <th class="px-6 py-4">Nama Cabang</th>
                        <th class="px-6 py-4">Alamat</th>
                        <th class="px-6 py-4">Telepon</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-150 text-slate-700">
                    @forelse($branches as $branch)
                        <tr class="hover:bg-slate-50/50 transition-colors {{ $branch->trashed() ? 'bg-slate-50/20' : '' }}">
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-900 flex items-center gap-2">
                                <div class="h-8 w-8 rounded-full bg-slate-105 flex items-center justify-center text-indigo-650 font-bold text-xs uppercase {{ $branch->trashed() ? 'opacity-55' : '' }}">
                                    {{ substr($branch->nama_cabang, 0, 2) }}
                                </div>
                                <span class="{{ $branch->trashed() ? 'text-slate-400 font-medium' : '' }}">{{ $branch->nama_cabang }}</span>
                                @if($branch->trashed())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Archived
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 max-w-sm truncate {{ $branch->trashed() ? 'text-slate-400' : '' }}">
                                {{ $branch->alamat ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 {{ $branch->trashed() ? 'text-slate-400' : '' }}">
                                {{ $branch->telepon ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center space-x-2">
                                    @if($branch->trashed())
                                        <!-- Lihat Laporan Button for Archived Branch -->
                                        <a href="{{ route('dashboard', ['branch_id' => $branch->id]) }}" class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition" title="Lihat Laporan">
                                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z" />
                                            </svg>
                                            Lihat Laporan
                                        </a>
                                    @else
                                        <!-- Edit Button -->
                                        <button @click="
                                            editBranch = { 
                                                id: '{{ $branch->id }}', 
                                                nama_cabang: '{{ $branch->nama_cabang }}', 
                                                alamat: '{{ $branch->alamat ?? '' }}', 
                                                telepon: '{{ $branch->telepon ?? '' }}' 
                                            }; 
                                            editOpen = true;
                                        " class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit Cabang">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <!-- Delete Button -->
                                        <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" class="inline" onsubmit="return confirm('PERINGATAN: Menghapus cabang ini akan menghapus semua data produk (material) dan data user (akun karyawan) di cabang ini secara permanen. Data transaksi cabang ini akan tetap disimpan sebagai arsip laporan. Apakah Anda yakin ingin menghapusnya?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus/Arsipkan Cabang">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">
                                Belum ada data cabang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah Cabang -->
    <div x-show="addOpen" class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="addOpen = false"></div>
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg z-10">
            <form action="{{ route('branches.store') }}" method="POST">
                @csrf
                <div class="bg-white px-6 pb-6 pt-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-900">Tambah Cabang Baru</h3>
                        <button type="button" @click="addOpen = false" class="text-slate-400 hover:text-slate-650">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Cabang <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama_cabang" required placeholder="Contoh: Cabang Bekasi Timur" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">No. Telepon</label>
                            <input type="text" name="telepon" placeholder="Contoh: 021-82736152" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Alamat</label>
                            <textarea name="alamat" rows="3" placeholder="Masukkan alamat lengkap cabang..." class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 rounded-b-2xl">
                    <button type="submit" class="inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition duration-150">
                        Simpan
                    </button>
                    <button type="button" @click="addOpen = false" class="inline-flex justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition duration-150">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Cabang -->
    <div x-show="editOpen" class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="editOpen = false"></div>
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg z-10">
            <form :action="'/branches/' + editBranch.id" method="POST">
                @csrf
                @method('PUT')
                <div class="bg-white px-6 pb-6 pt-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-900">Edit Cabang</h3>
                        <button type="button" @click="editOpen = false" class="text-slate-400 hover:text-slate-650">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Cabang <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama_cabang" required x-model="editBranch.nama_cabang" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">No. Telepon</label>
                            <input type="text" name="telepon" x-model="editBranch.telepon" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Alamat</label>
                            <textarea name="alamat" rows="3" x-model="editBranch.alamat" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 rounded-b-2xl">
                    <button type="submit" class="inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition duration-150">
                        Perbarui
                    </button>
                    <button type="button" @click="editOpen = false" class="inline-flex justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition duration-150">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
