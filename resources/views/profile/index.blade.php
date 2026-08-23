@extends('layouts.app')

@section('title', 'My Profile & Preferences')
@section('page-title', 'Preferences & Profil Pengguna')

@section('content')
<div class="max-w-4xl mx-auto space-y-4" x-data="{ mode: 'draw', avatarPreview: '{{ $user->avatar_path ? asset('storage/' . $user->avatar_path) : '' }}' }" id="main-view-wrapper">

    <!-- Card 1: Profil Biodata & Foto Akun -->
    <div class="o_form_sheet p-4 bg-white">
        <div class="d-flex justify-content-between align-items-start pb-3 mb-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <div class="position-relative">
                    <template x-if="avatarPreview">
                        <img :src="avatarPreview" alt="Avatar" class="rounded-circle object-cover border-2 border-blue-600 shadow-sm" style="width: 64px; height: 64px;">
                    </template>
                    <template x-if="!avatarPreview">
                        <div class="rounded-circle bg-blue-100 text-blue-800 fw-bold fs-3 d-flex align-items-center justify-content-center border-2 border-blue-300" style="width: 64px; height: 64px;">
                            {{ strtoupper(substr($user->username, 0, 1)) }}
                        </div>
                    </template>
                </div>
                <div>
                    <h4 class="fw-bold text-slate-900 mb-0">{{ $user->full_name ?: $user->username }}</h4>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="badge bg-blue-50 text-blue-700 border border-blue-200 text-[11px] text-uppercase">
                            {{ $user->role }}
                        </span>
                        <span class="text-slate-500 text-xs">
                            Cabang: <strong>{{ $user->branch->nama_cabang ?? 'Pusat (All Branches)' }}</strong>
                        </span>
                    </div>
                </div>
            </div>

            <div class="o_stat_button">
                <i class="fa-solid fa-id-card text-blue-600 fs-5"></i>
                <div>
                    <div class="o_stat_value">{{ $user->full_name ? 'Complete' : 'Basic' }}</div>
                    <div class="o_stat_text">Profile Status</div>
                </div>
            </div>
        </div>

        <form action="{{ route('profile.biodata') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">Nama Lengkap (Full Name)</label>
                    <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" class="form-control form-control-sm" placeholder="e.g. Budi Santoso">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">Username Login</label>
                    <input type="text" value="{{ $user->username }}" readonly class="form-control form-control-sm bg-slate-50 text-slate-500">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">Email Resmi</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control form-control-sm" placeholder="e.g. budi@snapprint.id">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control form-control-sm" placeholder="e.g. 081234567890">
                </div>
                <div class="col-12">
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">Alamat Domisili / Keterangan</label>
                    <textarea name="address" rows="2" class="form-control form-control-sm" placeholder="e.g. Jl. Grand Wisata Blok AA No. 5, Bekasi">{{ old('address', $user->address) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">Foto Profil (Avatar Upload)</label>
                    <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" class="form-control form-control-sm"
                           @change="
                               const file = $event.target.files[0];
                               if (file) {
                                   const reader = new FileReader();
                                   reader.onload = (e) => { avatarPreview = e.target.result; };
                                   reader.readAsDataURL(file);
                               }
                           ">
                    <small class="text-slate-400 text-[11px]">Format: JPG, PNG, atau WebP (Maksimal 2MB).</small>
                </div>
            </div>
            <div class="d-flex justify-content-end pt-2">
                <button type="submit" class="btn-odoo-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data Profil
                </button>
            </div>
        </form>
    </div>

    <!-- Card 2: Digital Signature Section -->
    <div class="o_form_sheet p-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
            <div>
                <h6 class="fw-bold text-slate-800 text-xs uppercase mb-0">
                    <i class="fa-solid fa-signature text-blue-600 me-1"></i> Digital Signature / Tanda Tangan Resmi
                </h6>
                <small class="text-slate-400 text-[11px]">Tanda tangan akan terstempel otomatis pada cetak Purchase Order & Dokumen Toko.</small>
            </div>
            <div class="btn-group btn-group-sm">
                <button type="button" @click="mode = 'draw'" :class="{ 'btn-odoo-primary': mode === 'draw', 'btn-odoo-secondary': mode !== 'draw' }">Draw Pad</button>
                <button type="button" @click="mode = 'upload'" :class="{ 'btn-odoo-primary': mode === 'upload', 'btn-odoo-secondary': mode !== 'upload' }">Upload Image</button>
            </div>
        </div>

        <!-- Active Signature Preview -->
        <div class="p-3 border rounded bg-slate-50 text-center mb-3">
            <span class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider block mb-2">Current Active Signature</span>
            @if($user->signature_path)
                <img src="{{ asset('storage/' . $user->signature_path) }}" alt="Digital Signature" class="max-h-20 max-w-full mx-auto object-contain">
            @else
                <div class="text-slate-400 text-xs italic py-3">
                    Belum ada tanda tangan. Buat coretan atau upload file di bawah ini.
                </div>
            @endif
        </div>

        <!-- Draw Mode Form -->
        <form action="{{ route('profile.signature') }}" method="POST" id="sig-form" x-show="mode === 'draw'">
            @csrf
            <input type="hidden" name="signature_base64" id="signature_base64">
            <div class="border rounded overflow-hidden bg-white relative mt-2">
                <canvas id="signature-canvas" class="w-full h-40 cursor-crosshair bg-slate-50/50"></canvas>
                <button type="button" onclick="clearCanvas()" class="btn btn-sm btn-light border position-absolute top-2 end-2 text-xs py-0.5 px-2">Clear</button>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <button type="button" onclick="saveCanvas()" class="btn-odoo-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Tanda Tangan Canvas
                </button>
            </div>
        </form>

        <!-- Upload Mode Form -->
        <form action="{{ route('profile.signature') }}" method="POST" enctype="multipart/form-data" x-show="mode === 'upload'">
            @csrf
            <div class="mt-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Pilih File PNG Transparan</label>
                <input type="file" name="signature_file" accept="image/png,image/jpeg" required class="form-control form-control-sm">
            </div>
            <div class="d-flex justify-content-end mt-2">
                <button type="submit" class="btn-odoo-primary">
                    <i class="fa-solid fa-upload me-1"></i> Upload Gambar Tanda Tangan
                </button>
            </div>
        </form>
    </div>

    <!-- Card 3: Change Password -->
    <div class="o_form_sheet p-4 bg-white">
        <h6 class="fw-bold text-slate-800 text-xs uppercase mb-3 pb-2 border-bottom">
            <i class="fa-solid fa-key text-blue-600 me-1"></i> Change Password
        </h6>
        <form action="{{ route('profile.password') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Current Password</label>
                <input type="password" name="current_password" required class="form-control form-control-sm">
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">New Password</label>
                    <input type="password" name="new_password" required class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" required class="form-control form-control-sm">
                </div>
            </div>
            <div class="d-flex justify-content-end pt-2">
                <button type="submit" class="btn-odoo-secondary">
                    Perbarui Password
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
        if (!signaturePad || signaturePad.isEmpty()) {
            alert('Silakan buat tanda tangan terlebih dahulu pada kotak!');
            return;
        }
        document.getElementById('signature_base64').value = signaturePad.toDataURL('image/png');
        document.getElementById('sig-form').submit();
    }
</script>
@endsection
