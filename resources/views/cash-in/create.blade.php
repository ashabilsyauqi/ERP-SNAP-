@extends('layouts.app')

@section('title', 'Tambah Kas Masuk')
@section('page-title', 'Tambah Kas Masuk')

@section('content')

<div class="max-w-3xl">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="{{ route('kas-masuk.store') }}" method="POST" class="p-6 md:p-8">
            @csrf
            
            <div class="space-y-6">
                <!-- Tanggal -->
                <div>
                    <label for="tanggal" class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Transaksi <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal" id="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors @error('tanggal') border-red-500 @enderror">
                    @error('tanggal')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Akun Pendapatan/Aset -->
                <div>
                    <label for="account_id" class="block text-sm font-semibold text-gray-700 mb-1">Akun Tujuan <span class="text-red-500">*</span></label>
                    <select name="account_id" id="account_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors @error('account_id') border-red-500 @enderror">
                        <option value="">-- Pilih Akun --</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->kode_akun }} - {{ $account->nama_akun }} ({{ ucfirst($account->tipe) }})
                            </option>
                        @endforeach
                    </select>
                    @error('account_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jumlah -->
                <div>
                    <label for="jumlah" class="block text-sm font-semibold text-gray-700 mb-1">Jumlah (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="jumlah" id="jumlah" value="{{ old('jumlah') }}" required min="1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors @error('jumlah') border-red-500 @enderror"
                        placeholder="Contoh: 1500000">
                    @error('jumlah')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Keterangan -->
                <div>
                    <label for="keterangan" class="block text-sm font-semibold text-gray-700 mb-1">Keterangan <span class="text-red-500">*</span></label>
                    <textarea name="keterangan" id="keterangan" rows="3" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors @error('keterangan') border-red-500 @enderror"
                        placeholder="Keterangan transaksi (Misal: Tambahan Modal)">{{ old('keterangan') }}</textarea>
                    @error('keterangan')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-8 flex items-center gap-4">
                <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                    Simpan Kas Masuk
                </button>
                <a href="{{ route('kas-masuk.index') }}" class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
