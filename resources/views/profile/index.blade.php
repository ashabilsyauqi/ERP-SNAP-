@extends('layouts.app')

@section('title', 'Profil & Tanda Tangan Digital')
@section('page-title', 'Pengaturan Profil & Tanda Tangan Digital')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto animate-fade-in" x-data="{ mode: 'draw' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Pengaturan Profil & Tanda Tangan Digital</h2>
            <p class="text-sm text-slate-500">Kelola identitas akun dan tanda tangan digital resmi untuk otorisasi cetak dokumen PO & Nota.</p>
        </div>
    </div>

    <!-- User Info Card -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-sm flex items-center gap-5">
        <div class="h-16 w-16 rounded-2xl bg-indigo-600 text-white font-bold text-2xl flex items-center justify-center shadow-md shadow-indigo-600/20">
            {{ strtoupper(substr($user->username, 0, 1)) }}
        </div>
        <div>
            <h3 class="text-lg font-bold text-slate-900">{{ $user->username }}</h3>
            <div class="flex items-center gap-2 mt-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100 uppercase">
                    Role: {{ $user->role }}
                </span>
                <span class="text-xs text-slate-500">
                    Cabang: <span class="font-semibold text-slate-700">{{ $user->branch->nama_cabang ?? 'Global' }}</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Digital Signature Card -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-sm space-y-6" id="signature-section">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Tanda Tangan Digital Account</h3>
                <p class="text-xs text-slate-500">Tanda tangan ini akan terstempel otomatis pada dokumen cetak PO (Dibuat oleh / Disetujui oleh).</p>
            </div>
            <div class="flex gap-1.5 p-1 bg-slate-100 rounded-xl">
                <button type="button" @click="mode = 'draw'" :class="{ 'bg-white text-slate-800 shadow-sm': mode === 'draw', 'text-slate-500': mode !== 'draw' }" class="px-3 py-1.5 font-semibold text-xs rounded-lg transition">Layar Coretan (Draw)</button>
                <button type="button" @click="mode = 'upload'" :class="{ 'bg-white text-slate-800 shadow-sm': mode === 'upload', 'text-slate-500': mode !== 'upload' }" class="px-3 py-1.5 font-semibold text-xs rounded-lg transition">Upload File Gambar</button>
            </div>
        </div>

        <!-- Current Active Signature Preview -->
        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tanda Tangan Saat Ini</label>
            <div class="p-4 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50 flex items-center justify-center min-h-[120px]">
                @if($user->signature_path)
                    <img src="{{ asset('storage/' . $user->signature_path) }}" alt="Digital Signature" class="max-h-24 max-w-full object-contain">
                @else
                    <div class="text-center text-slate-400 text-xs italic">
                        Belum ada Tanda Tangan Digital terpasang. Tanda tangan akan di-generate otomatis sebagai Stempel Otorisasi Digital jika belum digambar.
                    </div>
                @endif
            </div>
        </div>

        <!-- Draw Mode -->
        <form action="{{ route('profile.signature') }}" method="POST" id="sig-form" x-show="mode === 'draw'">
            @csrf
            <input type="hidden" name="signature_base64" id="signature_base64">
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Gambar Tanda Tangan Anda di Bawah Ini</label>
            <div class="border border-slate-300 rounded-2xl overflow-hidden bg-white shadow-inner relative">
                <canvas id="signature-canvas" class="w-full h-44 cursor-crosshair bg-slate-50/30"></canvas>
                <button type="button" onclick="clearCanvas()" class="absolute right-3 top-3 text-xs bg-slate-200 hover:bg-slate-300 text-slate-700 px-3 py-1 rounded-lg font-semibold transition">Bersihkan Canvas</button>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="button" onclick="saveCanvas()" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 px-6 rounded-xl transition text-sm shadow-sm">
                    Simpan Tanda Tangan Digital
                </button>
            </div>
        </form>

        <!-- Upload Mode -->
        <form action="{{ route('profile.signature') }}" method="POST" enctype="multipart/form-data" x-show="mode === 'upload'">
            @csrf
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Upload Gambar Tanda Tangan (PNG Transparan Disarankan)</label>
            <input type="file" name="signature_file" accept="image/png,image/jpeg" required class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <div class="mt-4 flex justify-end">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 px-6 rounded-xl transition text-sm shadow-sm">
                    Upload Tanda Tangan
                </button>
            </div>
        </form>
    </div>

    <!-- Password Settings Card -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-sm space-y-4">
        <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Ubah Password Akun</h3>
        <form action="{{ route('profile.password') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Password Saat Ini</label>
                <input type="password" name="current_password" required class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Password Baru</label>
                    <input type="password" name="new_password" required class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="new_password_confirmation" required class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2.5 px-6 rounded-xl transition text-sm shadow-sm">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let signaturePad = null;

    document.addEventListener('DOMContentLoaded', () => {
        const canvas = document.getElementById('signature-canvas');
        if (!canvas) return;

        // Resize canvas to fill container resolution cleanly
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            if (signaturePad) signaturePad.clear();
        }

        window.addEventListener("resize", resizeCanvas);
        resizeCanvas();

        if (typeof SignaturePad !== 'undefined') {
            signaturePad = new SignaturePad(canvas, {
                penColor: 'rgb(30, 41, 59)',
                minWidth: 1.5,
                maxWidth: 3.5
            });
        }
    });

    function clearCanvas() {
        if (signaturePad) {
            signaturePad.clear();
        } else {
            const canvas = document.getElementById('signature-canvas');
            if (canvas) canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        }
    }

    function saveCanvas() {
        if (signaturePad && signaturePad.isEmpty()) {
            if (typeof Toast !== 'undefined') Toast.fire({ icon: 'warning', title: 'Silakan corat-coret tanda tangan terlebih dahulu.' });
            return;
        }

        const canvas = document.getElementById('signature-canvas');
        const dataURL = signaturePad ? signaturePad.toDataURL('image/png') : canvas.toDataURL('image/png');
        document.getElementById('signature_base64').value = dataURL;
        document.getElementById('sig-form').submit();
    }
</script>
@endsection
