@extends('layouts.app')

@section('title', 'Master Akun')
@section('page-title', 'Master Akun')

@section('content')

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between gap-4">
        <form method="GET" action="{{ route('accounts.index') }}" class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari akun..." 
                    class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm">
            </div>
            
            <select name="tipe" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                <option value="aset" {{ request('tipe') == 'aset' ? 'selected' : '' }}>Aset</option>
                <option value="kewajiban" {{ request('tipe') == 'kewajiban' ? 'selected' : '' }}>Kewajiban</option>
                <option value="modal" {{ request('tipe') == 'modal' ? 'selected' : '' }}>Modal</option>
                <option value="pendapatan" {{ request('tipe') == 'pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                <option value="beban" {{ request('tipe') == 'beban' ? 'selected' : '' }}>Beban</option>
            </select>
        </form>

        <div>
            <a href="{{ route('accounts.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors w-full sm:w-auto">
                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Tambah Akun
            </a>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider font-semibold">
                    <th class="px-6 py-4">Kode</th>
                    <th class="px-6 py-4">Nama Akun</th>
                    <th class="px-6 py-4">Tipe</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($accounts as $account)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $account->kode_akun }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $account->nama_akun }}</div>
                            @if($account->parent)
                                <div class="text-xs text-gray-500 mt-1">Sub dari: {{ $account->parent->nama_akun }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 capitalize">
                                {{ $account->tipe }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('accounts.toggle-status', $account->id) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $account->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} hover:opacity-80 transition-opacity">
                                    {{ $account->is_active ? 'Aktif' : 'Non-aktif' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <a href="{{ route('accounts.edit', $account->id) }}" class="inline-flex items-center p-2 text-primary-600 bg-primary-50 rounded-lg hover:bg-primary-100 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>
                            <form method="POST" action="{{ route('accounts.destroy', $account->id) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center p-2 text-rose-600 bg-rose-50 rounded-lg hover:bg-rose-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            Tidak ada data akun yang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    @if($accounts->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $accounts->links() }}
        </div>
    @endif
</div>

@endsection
