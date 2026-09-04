@extends('layouts.app')

@section('title', 'Tambah Kas Keluar')
@section('page-title', 'Tambah Kas Keluar (Pengeluaran)')

@section('content')

<div class="max-w-3xl">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="{{ route('kas-keluar.store') }}" method="POST" enctype="multipart/form-data" class="p-6 md:p-8">
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

                <!-- Akun Beban -->
                <div>
                    <label for="account_id" class="block text-sm font-semibold text-gray-700 mb-1">Akun Beban / Pengeluaran <span class="text-red-500">*</span></label>
                    <select name="account_id" id="account_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors @error('account_id') border-red-500 @enderror">
                        <option value="">-- Pilih Akun --</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->kode_akun }} - {{ $account->nama_akun }}
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
                        placeholder="Contoh: 500000">
                    @error('jumlah')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Keterangan -->
                <div>
                    <label for="keterangan" class="block text-sm font-semibold text-gray-700 mb-1">Keterangan / Rincian Belanja <span class="text-red-500">*</span></label>
                    <textarea name="keterangan" id="keterangan" rows="3" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors @error('keterangan') border-red-500 @enderror"
                        placeholder="Misal: Beli tinta dan kertas roll">{{ old('keterangan') }}</textarea>
                    @error('keterangan')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Bukti Dokumen Pengeluaran (Nota / Struk) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Lampiran Bukti Pengeluaran (Nota / Struk / Kuitansi)
                        <span class="text-xs font-normal text-gray-500">(Opsional, Foto/Scan Nota)</span>
                    </label>
                    <div class="mt-1" x-data="{ 
                        hasFile: false, 
                        fileName: '', 
                        isImage: false, 
                        previewUrl: '', 
                        fileSize: '',
                        handleFileSelect(e) {
                            const file = e.target.files[0];
                            if (file) {
                                this.hasFile = true;
                                this.fileName = file.name;
                                this.fileSize = (file.size / 1024 > 1024) ? (file.size / (1024 * 1024)).toFixed(2) + ' MB' : (file.size / 1024).toFixed(1) + ' KB';
                                if (file.type.startsWith('image/')) {
                                    this.isImage = true;
                                    this.previewUrl = URL.createObjectURL(file);
                                } else {
                                    this.isImage = false;
                                    this.previewUrl = '';
                                }
                            } else {
                                this.reset();
                            }
                        },
                        reset() {
                            this.hasFile = false;
                            this.fileName = '';
                            this.isImage = false;
                            this.previewUrl = '';
                            this.fileSize = '';
                            document.getElementById('bukti_transaksi').value = '';
                        }
                    }">
                        <!-- Drag & Drop / Click Zone -->
                        <div x-show="!hasFile" class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-rose-400 hover:bg-rose-50/20 transition-all cursor-pointer"
                             onclick="document.getElementById('bukti_transaksi').click()">
                            <input type="file" name="bukti_transaksi" id="bukti_transaksi" class="hidden" accept="image/*,.pdf" @change="handleFileSelect">
                            <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3 shadow-xs border border-rose-100">
                                <i class="fa-solid fa-receipt text-xl"></i>
                            </div>
                            <p class="text-sm font-bold text-gray-800 mb-1">
                                Klik untuk upload foto nota / struk belanja
                            </p>
                            <p class="text-xs text-gray-500 mb-0">
                                Format: <b>JPG, PNG, WEBP, atau PDF</b> (Maks. 10 MB)
                            </p>
                        </div>

                        <!-- Preview Area -->
                        <div x-show="hasFile" x-cloak class="border border-gray-200 rounded-xl p-4 bg-gray-50">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <!-- Thumbnail or PDF Icon -->
                                    <template x-if="isImage">
                                        <img :src="previewUrl" alt="Preview Nota" class="w-16 h-16 object-cover rounded-lg border border-gray-300 shadow-xs flex-shrink-0">
                                    </template>
                                    <template x-if="!isImage">
                                        <div class="w-16 h-16 rounded-lg bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0 border border-red-200">
                                            <i class="fa-solid fa-file-pdf text-2xl"></i>
                                        </div>
                                    </template>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-800 truncate mb-0.5" x-text="fileName"></p>
                                        <span class="text-xs text-gray-500 font-mono" x-text="fileSize"></span>
                                        <span class="badge bg-emerald-100 text-emerald-800 text-[10px] ms-2 px-1.5 py-0.5 font-bold">Siap diunggah</span>
                                    </div>
                                </div>
                                <button type="button" @click="reset()" class="btn btn-sm btn-outline-danger px-2.5 py-1 text-xs rounded-lg font-bold d-flex items-center gap-1.5 flex-shrink-0">
                                    <i class="fa-solid fa-trash-can"></i>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>
                        @error('bukti_transaksi')
                            <p class="mt-1.5 text-sm text-red-500 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mt-8 flex items-center gap-4">
                <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-rose-600 rounded-lg hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 transition-colors">
                    Simpan Pengeluaran
                </button>
                <a href="{{ route('kas-keluar.index') }}" class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
