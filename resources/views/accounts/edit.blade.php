@extends('layouts.app')

@section('title', 'Edit Akun')
@section('page-title', 'Edit Master Akun')

@section('content')

<div class="max-w-3xl">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="{{ route('accounts.update', $account->id) }}" method="POST" class="p-6 md:p-8">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                <!-- Kode Akun -->
                <div>
                    <label for="kode_akun" class="block text-sm font-semibold text-gray-700 mb-1">Kode Akun <span class="text-red-500">*</span></label>
                    <input type="text" name="kode_akun" id="kode_akun" value="{{ old('kode_akun', $account->kode_akun) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors @error('kode_akun') border-red-500 @enderror">
                    @error('kode_akun')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Akun -->
                <div>
                    <label for="nama_akun" class="block text-sm font-semibold text-gray-700 mb-1">Nama Akun <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_akun" id="nama_akun" value="{{ old('nama_akun', $account->nama_akun) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors @error('nama_akun') border-red-500 @enderror">
                    @error('nama_akun')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tipe Akun -->
                <div>
                    <label for="tipe" class="block text-sm font-semibold text-gray-700 mb-1">Tipe Akun <span class="text-red-500">*</span></label>
                    <select name="tipe" id="tipe" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors @error('tipe') border-red-500 @enderror">
                        <option value="">-- Pilih Tipe Akun --</option>
                        <option value="aset" {{ old('tipe', $account->tipe) == 'aset' ? 'selected' : '' }}>Aset</option>
                        <option value="kewajiban" {{ old('tipe', $account->tipe) == 'kewajiban' ? 'selected' : '' }}>Kewajiban</option>
                        <option value="modal" {{ old('tipe', $account->tipe) == 'modal' ? 'selected' : '' }}>Modal</option>
                        <option value="pendapatan" {{ old('tipe', $account->tipe) == 'pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                        <option value="beban" {{ old('tipe', $account->tipe) == 'beban' ? 'selected' : '' }}>Beban</option>
                    </select>
                    @error('tipe')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Induk Akun -->
                <div>
                    <label for="parent_id" class="block text-sm font-semibold text-gray-700 mb-1">Induk Akun (Opsional)</label>
                    <select name="parent_id" id="parent_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors @error('parent_id') border-red-500 @enderror">
                        <option value="">-- Tidak Ada Induk --</option>
                        @foreach($parentAccounts as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id', $account->parent_id) == $parent->id ? 'selected' : '' }}>
                                {{ $parent->kode_akun }} - {{ $parent->nama_akun }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Deskripsi -->
                <div>
                    <label for="deskripsi" class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi (Opsional)</label>
                    <textarea name="deskripsi" id="deskripsi" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors @error('deskripsi') border-red-500 @enderror">{{ old('deskripsi', $account->deskripsi) }}</textarea>
                    @error('deskripsi')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status Aktif -->
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }}
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm font-medium text-gray-700">
                        Akun Aktif
                    </label>
                </div>
            </div>

            <div class="mt-8 flex items-center gap-4">
                <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                    Simpan Perubahan
                </button>
                <a href="{{ route('accounts.index') }}" class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
