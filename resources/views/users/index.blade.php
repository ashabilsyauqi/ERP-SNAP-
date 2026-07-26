@extends('layouts.app')

@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')

@section('content')
<div x-data="{ 
    addOpen: false, 
    editOpen: false, 
    addRole: 'cashier',
    editUser: { id: '', username: '', role: '', branch_id: '' }
}" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Manajemen User</h2>
            <p class="text-sm text-slate-500">Kelola akun pengguna, peran (role), dan penempatan cabang.</p>
        </div>
        <button @click="addOpen = true; addRole = 'cashier'" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition shadow-sm flex items-center">
            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            Tambah User
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-55/50 border-b border-slate-100 text-slate-500 text-xs uppercase tracking-wider font-semibold">
                        <th class="px-6 py-4">No</th>
                        <th class="px-6 py-4">Username</th>
                        <th class="px-6 py-4">Role</th>
                        <th class="px-6 py-4">Cabang</th>
                        <th class="px-6 py-4">Dibuat Pada</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-150 text-slate-700">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-900 flex items-center gap-2">
                                <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-indigo-600 font-bold text-xs uppercase">
                                    {{ substr($user->username, 0, 2) }}
                                </div>
                                <span>{{ $user->username }}</span>
                                @if(auth()->id() === $user->id)
                                    <span class="inline-flex items-center rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-650 ring-1 ring-inset ring-slate-500/10">Anda</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($user->role === 'owner')
                                    <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-700/10 capitalize">
                                        {{ $user->role }}
                                    </span>
                                @elseif($user->role === 'purchasing')
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-700/10 capitalize">
                                        {{ $user->role }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-700/10 capitalize">
                                        {{ $user->role }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($user->branch)
                                    <span class="text-slate-900 font-medium">{{ $user->branch->nama_cabang }}</span>
                                @else
                                    <span class="text-slate-400 italic">Semua Cabang / Pusat</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">
                                {{ $user->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center space-x-2">
                                    <!-- Edit Button -->
                                    <button @click="
                                        editUser = { 
                                            id: '{{ $user->id }}', 
                                            username: '{{ $user->username }}', 
                                            role: '{{ $user->role }}', 
                                            branch_id: '{{ $user->branch_id ?? '' }}' 
                                        }; 
                                        editOpen = true;
                                    " class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit User">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Delete Button (Disabled for Self) -->
                                    @if(auth()->id() !== $user->id)
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus User">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <button disabled class="p-1.5 text-slate-300 cursor-not-allowed" title="Tidak dapat menghapus diri sendiri">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400 text-sm">
                                Belum ada data user.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah User -->
    <div x-show="addOpen" class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="addOpen = false"></div>
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg z-10">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="bg-white px-6 pb-6 pt-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-900">Tambah User Baru</h3>
                        <button type="button" @click="addOpen = false" class="text-slate-400 hover:text-slate-650">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Username <span class="text-rose-500">*</span></label>
                            <input type="text" name="username" required placeholder="Contoh: cashier_grandwis" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Password <span class="text-rose-500">*</span></label>
                            <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Role <span class="text-rose-500">*</span></label>
                            <select name="role" x-model="addRole" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white">
                                <option value="owner">Owner</option>
                                <option value="purchasing">Purchasing</option>
                                <option value="cashier">Cashier</option>
                            </select>
                        </div>
                        <div x-show="addRole !== 'owner'" x-transition>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Cabang Penempatan <span class="text-rose-500">*</span></label>
                            <select name="branch_id" :required="addRole !== 'owner'" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white">
                                <option value="">Pilih Cabang</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->nama_cabang }}</option>
                                @endforeach
                            </select>
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

    <!-- Modal Edit User -->
    <div x-show="editOpen" class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="editOpen = false"></div>
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg z-10">
            <form :action="'/users/' + editUser.id" method="POST">
                @csrf
                @method('PUT')
                <div class="bg-white px-6 pb-6 pt-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-900">Edit User</h3>
                        <button type="button" @click="editOpen = false" class="text-slate-400 hover:text-slate-650">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Username <span class="text-rose-500">*</span></label>
                            <input type="text" name="username" required x-model="editUser.username" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Password</label>
                            <input type="password" name="password" minlength="6" placeholder="Kosongkan jika tidak ingin diubah" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <p class="mt-1 text-[11px] text-slate-400">Isi password minimal 6 karakter hanya jika ingin menggantinya.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Role <span class="text-rose-500">*</span></label>
                            <select name="role" x-model="editUser.role" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white">
                                <option value="owner">Owner</option>
                                <option value="purchasing">Purchasing</option>
                                <option value="cashier">Cashier</option>
                            </select>
                        </div>
                        <div x-show="editUser.role !== 'owner'" x-transition>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Cabang Penempatan <span class="text-rose-500">*</span></label>
                            <select name="branch_id" :required="editUser.role !== 'owner'" x-model="editUser.branch_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white">
                                <option value="">Pilih Cabang</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->nama_cabang }}</option>
                                @endforeach
                            </select>
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
