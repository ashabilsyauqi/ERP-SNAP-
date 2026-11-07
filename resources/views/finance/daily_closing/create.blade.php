@extends('layouts.app')

@section('title', 'Tutup Kasir Harian & Rekonsiliasi Kas')
@section('page-title', 'Tutup Kasir Harian & Kontrol Uang Fisik')

@section('action-buttons')
<a href="{{ route('daily-closing.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 rounded-xl text-xs font-bold px-3 py-1.5">
    <i class="fa-solid fa-arrow-left"></i> Riwayat Closing
</a>
<a href="{{ route('pos.index') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 rounded-xl text-xs font-bold px-3 py-1.5">
    <i class="fa-solid fa-cash-register"></i> Ke Terminal Kasir (POS)
</a>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-5" x-data="dailyClosingForm()">
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-2xl shadow-sm text-xs font-semibold" role="alert">
            <i class="fa-solid fa-circle-exclamation me-1.5"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Form Submit Lembar Kerja Tutup Kasir -->
    <form action="{{ route('daily-closing.store') }}" method="POST" id="formDailyClosing">
        @csrf

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-6">
            
            <!-- Header Kartu: Gradient Solid Gelap dengan Kontras Tinggi -->
            <div class="rounded-2xl p-5 shadow-sm"
                 style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #ffffff !important; border: 1px solid #1e293b;">
                <div class="d-flex flex-column sm:flex-row justify-content-between align-items-start sm:align-items-center gap-3">
                    <div>
                        <span class="badge font-bold px-2.5 py-1 text-[11px] rounded-lg tracking-wider uppercase mb-2 d-inline-block"
                              style="background-color: #3b82f6; color: #ffffff;">
                            LEMBAR KERJA TUTUP KASIR
                        </span>
                        <h4 class="font-extrabold text-base sm:text-lg mb-1 tracking-tight" style="color: #ffffff !important;">
                            Rekonsiliasi Kas Harian & Kontrol Uang Fisik Toko
                        </h4>
                        <p class="text-xs mb-0" style="color: #cbd5e1 !important;">
                            Periksa penjualan tunai, catat pengeluaran toko, catat kas diambil boss, dan hitung lembaran/koin kasir.
                        </p>
                    </div>

                    <div class="text-start sm:text-end bg-black/30 p-3 rounded-xl border border-white/10">
                        <div class="text-[10px] uppercase font-bold tracking-wider" style="color: #94a3b8;">Tanggal Closing</div>
                        <div class="text-xs font-bold font-mono text-white mt-0.5">
                            {{ Carbon\Carbon::parse($targetDate)->format('d F Y') }}
                        </div>
                        <input type="hidden" name="closing_date" value="{{ $targetDate }}">
                    </div>
                </div>
            </div>

            <!-- 1. IDENTITAS CABANG & SHIFT (CABANG TERKUNCI OTOMATIS) -->
            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 space-y-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-700 d-flex align-items-center gap-1.5">
                        <i class="fa-solid fa-store text-blue-700"></i>
                        Identitas Cabang & Operasional
                    </span>
                    <span class="badge bg-slate-200 text-slate-700 text-[10px] font-bold px-2 py-0.5 rounded-md">
                        Shift: Full Day (Satu Hari Penuh)
                    </span>
                    <input type="hidden" name="shift_type" value="Full Day">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <!-- Branch Selector / Locked Badge -->
                    <div>
                        <label class="block text-slate-600 font-bold mb-1">
                            Cabang Toko:
                            @if(!$canChangeBranch)
                                <span class="text-[10px] text-slate-400 font-normal ms-1">(Terkunci otomatis sesuai akun kasir Anda)</span>
                            @endif
                        </label>
                        @if($canChangeBranch)
                            <select name="branch_id" class="form-select form-select-sm text-xs font-bold rounded-xl" onchange="window.location.href = '{{ route('daily-closing.create') }}?branch_id=' + this.value + '&closing_date={{ $targetDate }}';">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ $branch->id == $b->id ? 'selected' : '' }}>
                                        {{ $b->nama_cabang }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                            <div class="form-control form-control-sm text-xs font-bold rounded-xl bg-slate-100 text-slate-800 d-flex align-items-center justify-content-between">
                                <span><i class="fa-solid fa-lock text-slate-400 me-1.5"></i>{{ $branch->nama_cabang }}</span>
                                <span class="badge bg-blue-100 text-blue-800 text-[10px]">Cabang Kasir</span>
                            </div>
                        @endif
                    </div>

                    <!-- Kasir / User Info -->
                    <div>
                        <label class="block text-slate-600 font-bold mb-1">Kasir / Penanggung Jawab Closing:</label>
                        <div class="form-control form-control-sm text-xs font-bold rounded-xl bg-slate-100 text-slate-800 d-flex align-items-center justify-content-between">
                            <span><i class="fa-solid fa-user-check text-emerald-600 me-1.5"></i>{{ auth()->user()->full_name ?: auth()->user()->username }}</span>
                            <span class="badge bg-emerald-100 text-emerald-800 text-[10px] uppercase font-bold">{{ auth()->user()->role }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. DATA PENJUALAN OTOMATIS DARI SISTEM POS HARI INI -->
            <div class="rounded-2xl border border-blue-200 bg-blue-50/40 p-4 space-y-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-xs font-extrabold uppercase tracking-wider text-blue-950 mb-0 d-flex align-items-center gap-1.5">
                            <i class="fa-solid fa-cash-register text-blue-600"></i>
                            A. Ringkasan Omset Penjualan POS Hari Ini
                        </h6>
                        <p class="text-[11px] text-blue-800/80 mb-0 mt-0.5">
                            Dihitung otomatis dari seluruh order dan pelunasan piutang kasir yang masuk di sistem.
                        </p>
                    </div>
                    <span class="badge bg-blue-600 text-white font-mono font-bold text-xs px-2.5 py-1 rounded-lg">
                        {{ $totalOrdersCount }} Transaksi
                    </span>
                    <input type="hidden" name="total_orders_count" value="{{ $totalOrdersCount }}">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                    <!-- Cash Sales -->
                    <div class="bg-white p-3 rounded-xl border border-emerald-300 shadow-xs">
                        <span class="text-[10px] uppercase font-bold text-emerald-700 tracking-wider block">💵 Uang Tunai (Cash POS)</span>
                        <div class="font-mono font-extrabold text-base text-emerald-900 mt-1">
                            Rp {{ number_format($totalCashSales, 0, ',', '.') }}
                        </div>
                        <input type="hidden" name="total_cash_sales" value="{{ $totalCashSales }}">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Wajib ada di laci fisik</span>
                    </div>

                    <!-- Transfer Sales -->
                    <div class="bg-white p-3 rounded-xl border border-blue-200 shadow-xs">
                        <span class="text-[10px] uppercase font-bold text-blue-700 tracking-wider block">🏦 Transfer Bank</span>
                        <div class="font-mono font-extrabold text-base text-blue-900 mt-1">
                            Rp {{ number_format($totalTransferSales, 0, ',', '.') }}
                        </div>
                        <input type="hidden" name="total_transfer_sales" value="{{ $totalTransferSales }}">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Langsung masuk rekening</span>
                    </div>

                    <!-- QRIS Sales -->
                    <div class="bg-white p-3 rounded-xl border border-indigo-200 shadow-xs">
                        <span class="text-[10px] uppercase font-bold text-indigo-700 tracking-wider block">📱 QRIS Statis/Dinamis</span>
                        <div class="font-mono font-extrabold text-base text-indigo-900 mt-1">
                            Rp {{ number_format($totalQrisSales, 0, ',', '.') }}
                        </div>
                        <input type="hidden" name="total_qris_sales" value="{{ $totalQrisSales }}">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Settlement QRIS online</span>
                    </div>

                    <!-- Total Omset -->
                    <div class="bg-gradient-to-br from-slate-900 to-indigo-950 p-3 rounded-xl text-white shadow-xs">
                        <span class="text-[10px] uppercase font-bold text-slate-300 tracking-wider block">📊 Total Omset Hari Ini</span>
                        <div class="font-mono font-extrabold text-base text-white mt-1">
                            Rp {{ number_format($totalSales, 0, ',', '.') }}
                        </div>
                        <span class="text-[10px] text-slate-300 mt-0.5 block">Cash + Transfer + QRIS</span>
                    </div>
                </div>
            </div>

            <!-- 3. Dynamic Expenses Section (PENGELUARAN KAS HARIAN) -->
            <div class="rounded-2xl border border-rose-200 bg-rose-50/40 p-4 space-y-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-xs font-extrabold uppercase tracking-wider text-rose-950 mb-0 d-flex align-items-center gap-1.5">
                            <i class="fa-solid fa-arrow-up-from-bracket text-rose-600"></i>
                            B. Pengeluaran Kas Operasional Hari Ini (Cash Out)
                        </h6>
                        <p class="text-[11px] text-rose-800/80 mb-0 mt-0.5">
                            Catat pengeluaran tunai hari ini (misal: bensin, lakban, galon, konsumsi). Otomatis masuk ke Buku Kas Keluar sistem.
                        </p>
                    </div>
                    <button type="button" @click="addExpenseRow()" class="btn btn-sm btn-danger text-xs font-bold rounded-xl px-3 py-1.5 shadow-sm d-inline-flex align-items-center gap-1">
                        <i class="fa-solid fa-plus"></i> Tambah Pengeluaran
                    </button>
                </div>

                <!-- Pengeluaran yang sudah tercatat sebelumnya hari ini (jika ada) -->
                @if($existingExpenses->count() > 0)
                    <div class="bg-white rounded-xl border border-rose-200 overflow-hidden text-xs">
                        <div class="bg-rose-100/70 px-3 py-1.5 font-bold text-rose-900 text-[11px] d-flex justify-content-between">
                            <span>Pengeluaran yang Sudah Tercatat di Sistem Hari Ini:</span>
                            <span class="font-mono font-bold">Subtotal: Rp {{ number_format($cashOut, 0, ',', '.') }}</span>
                        </div>
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr class="text-[10px] text-slate-500 uppercase bg-slate-50">
                                    <th class="ps-3 py-1.5">No. Referensi</th>
                                    <th class="py-1.5">Kategori Akun</th>
                                    <th class="py-1.5">Keterangan</th>
                                    <th class="pe-3 py-1.5 text-end">Jumlah (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($existingExpenses as $ex)
                                    <tr>
                                        <td class="ps-3 py-1 font-mono font-semibold text-slate-700">{{ $ex->nomor_referensi }}</td>
                                        <td class="py-1 text-slate-600">{{ $ex->account->nama_akun ?? 'Beban Operasional' }}</td>
                                        <td class="py-1 text-slate-800 font-medium">{{ $ex->keterangan }}</td>
                                        <td class="pe-3 py-1 text-end font-mono font-bold text-rose-700">Rp {{ number_format($ex->jumlah, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- Dynamic Rows Form -->
                <div class="space-y-2">
                    <template x-if="newExpenses.length === 0">
                        <div class="text-center py-3 bg-white/70 border border-dashed border-rose-200 rounded-xl text-xs text-slate-500">
                            <i class="fa-solid fa-receipt text-rose-400 text-lg mb-1 d-block"></i>
                            Belum ada baris pengeluaran baru ditambahkan. Klik <strong class="text-rose-700 cursor-pointer" @click="addExpenseRow()">"+ Tambah Pengeluaran"</strong> jika ada pengeluaran kas hari ini.
                        </div>
                    </template>

                    <template x-for="(expense, index) in newExpenses" :key="index">
                        <div class="bg-white p-2.5 rounded-xl border border-rose-200 shadow-xs grid grid-cols-12 gap-2 items-center text-xs animate-fade-in">
                            <div class="col-span-12 sm:col-span-3">
                                <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Kategori Beban</label>
                                <select :name="'new_expenses[' + index + '][account_id]'" x-model="expense.account_id" class="form-select form-select-sm text-xs rounded-lg font-medium" required>
                                    @foreach($expenseAccounts as $acc)
                                        <option value="{{ $acc->id }}">[{{ $acc->kode_akun }}] {{ $acc->nama_akun }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-span-12 sm:col-span-5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Keterangan / Keperluan</label>
                                <input type="text" :name="'new_expenses[' + index + '][keterangan]'" x-model="expense.keterangan" class="form-control form-control-sm text-xs rounded-lg" placeholder="Contoh: Beli lakban toko, bensin motor kurir..." required>
                            </div>

                            <div class="col-span-9 sm:col-span-3">
                                <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Nominal (Rp)</label>
                                <input type="number" :name="'new_expenses[' + index + '][amount]'" x-model.number="expense.amount" class="form-control form-control-sm text-xs font-mono font-bold text-end rounded-lg text-rose-700" placeholder="0" min="0" required>
                            </div>

                            <div class="col-span-3 sm:col-span-1 text-end pt-3 sm:pt-4">
                                <button type="button" @click="removeExpenseRow(index)" class="btn btn-sm btn-outline-danger p-1.5 rounded-lg text-xs" title="Hapus Baris">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Total Kas Keluar Ringkasan -->
                <div class="bg-rose-100 rounded-xl p-2.5 d-flex justify-content-between align-items-center text-xs font-bold text-rose-950">
                    <div>
                        <span>Total Akumulasi Pengeluaran Hari Ini (Cash Out):</span>
                        <span class="text-[10px] text-rose-700 font-normal ms-1">(Sistem + Baris Baru)</span>
                    </div>
                    <div class="font-mono text-sm text-rose-800" x-text="formatRp(totalCashOut)"></div>
                    <input type="hidden" name="total_cash_out" :value="totalCashOut">
                </div>
            </div>

            <!-- 3B. PENCATATAN WASTE BAHAN (TRIAL & ERROR MESIN) -->
            <div class="rounded-2xl border border-amber-300 bg-amber-50/40 p-4 space-y-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-xs font-extrabold uppercase tracking-wider text-amber-950 mb-0 d-flex align-items-center gap-1.5">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                            C. Pencatatan Waste Bahan (Trial & Error Mesin)
                        </h6>
                        <p class="text-[11px] text-amber-800/80 mb-0 mt-0.5">
                            Catat bahan yang terbuang karena trial error kalibrasi mesin, warna tidak cocok, atau cacat potong. <strong>Otomatis mengurangi sisa stok Roll / Kertas</strong> tanpa mengurangi uang tunai fisik di laci kasir.
                        </p>
                    </div>
                    <button type="button" @click="addWasteRow()" class="btn btn-sm text-xs font-bold rounded-xl px-3 py-1.5 shadow-sm d-inline-flex align-items-center gap-1 text-slate-900" style="background-color: #f59e0b; border-color: #f59e0b;">
                        <i class="fa-solid fa-plus"></i> Tambah Waste Bahan
                    </button>
                </div>

                <!-- Dynamic Waste Rows -->
                <div class="space-y-2">
                    <template x-if="wastes.length === 0">
                        <div class="text-center py-3 bg-white/70 border border-dashed border-amber-300 rounded-xl text-xs text-slate-500">
                            <i class="fa-solid fa-recycle text-amber-500 text-lg mb-1 d-block"></i>
                            Tidak ada bahan terbuang / nihil waste hari ini. Klik <strong class="text-amber-800 cursor-pointer" @click="addWasteRow()">"+ Tambah Waste Bahan"</strong> jika ada bahan rusak atau trial error mesin.
                        </div>
                    </template>

                    <template x-for="(waste, index) in wastes" :key="index">
                        <div class="bg-white p-3 rounded-xl border border-amber-200 shadow-xs space-y-2 text-xs animate-fade-in">
                            <div class="grid grid-cols-12 gap-2 items-center">
                                <!-- Pilih Material -->
                                <div class="col-span-12 sm:col-span-4">
                                    <label class="text-[10px] font-bold text-slate-600 uppercase block mb-1">
                                        Pilih Bahan Baku <span class="text-red-500">*</span>
                                    </label>
                                    <select :name="'wastes[' + index + '][material_id]'" 
                                            x-model="waste.material_id" 
                                            @change="onMaterialChange(index)" 
                                            class="form-select form-select-sm text-xs rounded-lg font-medium" 
                                            required>
                                        <option value="">-- Pilih Bahan Baku / Produk --</option>
                                        @if(isset($branchMaterials))
                                            @foreach($branchMaterials->groupBy('category') as $cat => $mats)
                                                <optgroup label="{{ $cat ?: 'Lainnya' }}">
                                                    @foreach($mats as $m)
                                                        <option value="{{ $m->id }}">
                                                            {{ $m->material_name }} ({{ $m->rolls->count() > 0 ? $m->rolls->count() . ' Gulungan Roll' : 'Stok: ' . $m->stock_qty . ' ' . ($m->unit ?: 'pcs') }})
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <!-- Pilih Roll (Jika bahan berupa Roll) -->
                                <div class="col-span-12 sm:col-span-3">
                                    <label class="text-[10px] font-bold text-slate-600 uppercase block mb-1">
                                        Pilihan Gulungan (Roll)
                                    </label>
                                    <select :name="'wastes[' + index + '][material_roll_id]'" 
                                            x-model="waste.material_roll_id" 
                                            @change="onRollChange(index)" 
                                            class="form-select form-select-sm text-xs rounded-lg font-medium" 
                                            :disabled="!getMaterialRolls(waste.material_id).length">
                                        <template x-if="!getMaterialRolls(waste.material_id).length">
                                            <option value="">(Bukan Produk Roll)</option>
                                        </template>
                                        <template x-for="r in getMaterialRolls(waste.material_id)" :key="r.id">
                                            <option :value="r.id" x-text="`${r.roll_name || ('Roll ' + r.roll_width_m + 'm')} (Sisa: ${r.current_length_m}m)`"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Jumlah Terbuang (Qty) -->
                                <div class="col-span-6 sm:col-span-2">
                                    <label class="text-[10px] font-bold text-slate-600 uppercase block mb-1">
                                        Qty Terbuang <span class="text-red-500">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" 
                                               :name="'wastes[' + index + '][waste_qty]'" 
                                               x-model.number="waste.waste_qty" 
                                               @input="calculateWasteHpp(index)" 
                                               class="form-control form-control-sm text-xs font-mono font-bold text-end rounded-start-lg" 
                                               placeholder="0" 
                                               min="0.1" 
                                               step="0.01" 
                                               required>
                                        <span class="input-group-text text-[10px] bg-slate-100 font-bold" x-text="waste.unit"></span>
                                    </div>
                                </div>

                                <!-- Alasan Waste -->
                                <div class="col-span-6 sm:col-span-3">
                                    <label class="text-[10px] font-bold text-slate-600 uppercase block mb-1">
                                        Alasan Waste
                                    </label>
                                    <select :name="'wastes[' + index + '][reason]'" 
                                            x-model="waste.reason" 
                                            class="form-select form-select-sm text-xs rounded-lg font-medium">
                                        <option value="Trial Alignment Printhead">Trial Alignment Printhead</option>
                                        <option value="Error Profil / Kalibrasi Warna">Error Profil / Kalibrasi Warna</option>
                                        <option value="Bahan Macet / Mengkerut (Head Strike)">Bahan Macet / Mengkerut (Head Strike)</option>
                                        <option value="Salah Ukuran / Salah File Desain">Salah Ukuran / Salah File Desain</option>
                                        <option value="Rusak Finishing / Potongan Miring">Rusak Finishing / Potongan Miring</option>
                                        <option value="Bahan Cacat dari Supplier">Bahan Cacat dari Supplier</option>
                                        <option value="Lain-lain">Lain-lain</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Baris Kedua: Catatan & Subtotal HPP -->
                            <div class="grid grid-cols-12 gap-2 items-center pt-1 border-t border-slate-100">
                                <div class="col-span-12 sm:col-span-8">
                                    <input type="text" 
                                           :name="'wastes[' + index + '][notes]'" 
                                           x-model="waste.notes" 
                                           class="form-control form-control-sm text-xs rounded-lg" 
                                           placeholder="Catatan detail (contoh: Mesin outdoor tinta kuning mampet, tes potong 1.5m)">
                                </div>
                                <div class="col-span-9 sm:col-span-3 text-end">
                                    <span class="text-[10px] text-slate-400 block">Estimasi Nilai HPP Waste:</span>
                                    <span class="font-mono font-bold text-xs text-amber-700" x-text="formatRp(waste.subtotal_hpp)"></span>
                                </div>
                                <div class="col-span-3 sm:col-span-1 text-end">
                                    <button type="button" @click="removeWasteRow(index)" class="btn btn-sm btn-outline-danger p-1 rounded-lg text-xs" title="Hapus Baris">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Total Kerugian Waste Ringkasan -->
                <div class="bg-amber-100/80 rounded-xl p-2.5 d-flex justify-content-between align-items-center text-xs font-bold text-amber-950">
                    <div class="d-flex align-items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-amber-700"></i>
                        <span>Total Kerugian Bahan Hari Ini (Waste HPP):</span>
                        <span class="badge bg-white text-amber-800 border border-amber-300 text-[10px] font-normal ms-1">
                            Potong Stok • Kas Laci Aman
                        </span>
                    </div>
                    <div class="font-mono text-sm text-amber-900" x-text="formatRp(totalWasteHpp)"></div>
                </div>
            </div>

            <!-- 4. KAS DIAMBIL BOSS HARI INI (LANGSUNG DI LEMBAR KERJA) -->
            <div class="rounded-2xl border border-amber-300 bg-amber-50/40 p-4 space-y-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-xs font-extrabold uppercase tracking-wider text-amber-950 mb-0 d-flex align-items-center gap-1.5">
                            <i class="fa-solid fa-hand-holding-dollar text-amber-600"></i>
                            D. Penarikan Kas oleh Boss Hari Ini (Diambil Boss)
                        </h6>
                        <p class="text-[11px] text-amber-800/80 mb-0 mt-0.5">
                            Jika Boss / Owner mengambil kas dari laci hari ini (misal untuk disetor ke bank / kas pusat), catat di sini agar fisik laci tidak dihitung selisih tekor.
                        </p>
                    </div>
                    <span class="badge bg-amber-100 text-amber-900 border border-amber-300 text-xs font-mono font-bold px-2.5 py-1">
                        <span x-text="formatRp(totalBossWithdrawal)"></span>
                    </span>
                </div>

                <!-- Penarikan yang sudah tercatat sebelumnya untuk tanggal ini (jika ada) -->
                @if(isset($existingBossWithdrawals) && $existingBossWithdrawals->count() > 0)
                    <div class="bg-white rounded-xl border border-amber-200 overflow-hidden text-xs">
                        <div class="bg-amber-100/70 px-3 py-1.5 font-bold text-amber-900 text-[11px] d-flex justify-content-between">
                            <span>Penarikan Kas oleh Boss yang Sudah Tercatat Hari Ini:</span>
                            <span class="font-mono font-bold">Subtotal: Rp {{ number_format($existingBossWithdrawalTotal, 0, ',', '.') }}</span>
                        </div>
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach($existingBossWithdrawals as $ew)
                                    <tr>
                                        <td class="ps-3 py-1 font-bold text-amber-900">
                                            <i class="fa-solid fa-user-tie me-1 text-amber-700"></i> {{ $ew->taken_by }}
                                        </td>
                                        <td class="py-1 text-slate-700">{{ $ew->purpose ?? 'Penarikan Kas' }}</td>
                                        <td class="pe-3 py-1 text-end font-mono font-bold text-rose-700">- Rp {{ number_format($ew->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="bg-white p-3 rounded-xl border border-amber-200 shadow-xs grid grid-cols-12 gap-3 items-center text-xs">
                    <div class="col-span-12 sm:col-span-4">
                        <label class="text-[10px] font-bold text-slate-600 uppercase block mb-1">Nominal yang Diambil Boss (Rp)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-slate-100 font-mono font-bold text-xs">Rp</span>
                            <input type="number" name="boss_withdrawal_amount" x-model.number="bossWithdrawalAmount" min="0" step="any" class="form-control form-control-sm font-mono font-bold text-amber-900 text-sm" placeholder="0 jika tidak ada">
                        </div>
                    </div>

                    <div class="col-span-12 sm:col-span-4">
                        <label class="text-[10px] font-bold text-slate-600 uppercase block mb-1">Diambil Oleh (Nama Boss)</label>
                        <select name="boss_withdrawal_taken_by" class="form-select form-select-sm text-xs rounded-lg font-medium">
                            <option value="Boss SWANTO">Boss SWANTO</option>
                            <option value="Boss KINGAshabil">Boss KINGAshabil</option>
                            <option value="Owner Swanto & King Ashabil">Owner Swanto & King Ashabil</option>
                            <option value="Pimpinan / Manajemen Snaprint">Pimpinan / Manajemen Snaprint</option>
                        </select>
                    </div>

                    <div class="col-span-12 sm:col-span-4">
                        <label class="text-[10px] font-bold text-slate-600 uppercase block mb-1">Keperluan / Tujuan Penarikan</label>
                        <select name="boss_withdrawal_purpose" class="form-select form-select-sm text-xs rounded-lg font-medium">
                            <option value="Setor Tunai ke Bank BCA">Setor Tunai ke Bank BCA</option>
                            <option value="Setor Tunai ke Bank Mandiri">Setor Tunai ke Bank Mandiri</option>
                            <option value="Kas Operasional Kantor Pusat">Kas Operasional Kantor Pusat</option>
                            <option value="Penarikan Tunai Owner (Prive)">Penarikan Tunai Owner (Prive)</option>
                            <option value="Belanja Modal / Bahan Baku Mendesak">Belanja Modal / Bahan Baku Mendesak</option>
                            <option value="Lain-lain">Lain-lain</option>
                        </select>
                    </div>

                    <div class="col-span-12">
                        <label class="text-[10px] font-bold text-slate-600 uppercase block mb-1">Catatan Penarikan Boss (Opsional)</label>
                        <input type="text" name="boss_withdrawal_notes" class="form-control form-control-sm text-xs rounded-lg" placeholder="Contoh: Titip setor BCA cabang Grand Wisata siang hari...">
                    </div>

                    <!-- PIN Otorisasi Boss (Wajib jika ada kas ditarik) -->
                    <div class="col-span-12" x-show="bossWithdrawalAmount > 0" x-transition>
                        <div class="p-3 rounded-xl bg-amber-100 border-2 border-amber-300 d-flex flex-column sm:flex-row items-center justify-between gap-3">
                            <div>
                                <label class="text-xs font-black text-amber-950 d-flex align-items-center gap-1.5 mb-0 uppercase tracking-wide">
                                    <i class="fa-solid fa-shield-halved text-amber-700 fs-6"></i>
                                    <span>PIN Otorisasi Boss (Wajib untuk penarikan kas)</span>
                                </label>
                                <p class="text-[11px] text-amber-800 mb-0">
                                    Masukkan PIN otorisasi rahasia untuk mengesahkan penarikan kas fisik hari ini.
                                </p>
                            </div>
                            <div class="w-full sm:w-48">
                                <input type="password" name="boss_pin" maxlength="6" class="form-control form-control-sm text-center font-mono font-black text-base tracking-widest border-amber-400 bg-white" placeholder="••••" :required="bossWithdrawalAmount > 0" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. CASH DENOMINATIONS: HITUNG FISIK UANG LEMBARAN & KOIN DI LACI -->
            <div class="rounded-2xl border border-emerald-300 bg-emerald-50/30 p-4 space-y-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-xs font-extrabold uppercase tracking-wider text-emerald-950 mb-0 d-flex align-items-center gap-1.5">
                            <i class="fa-solid fa-money-bill-wave text-emerald-600"></i>
                            E. Kontrol Uang Fisik Kasir (Hitung Lembaran & Koin di Laci)
                        </h6>
                        <p class="text-[11px] text-emerald-800/80 mb-0 mt-0.5">
                            Masukkan jumlah lembar dan keping uang tunai fisik yang ada di laci saat closing kasir.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" @click="resetDenominations()" class="btn btn-sm btn-outline-secondary text-xs rounded-xl px-2.5 py-1">
                            <i class="fa-solid fa-rotate-left me-1"></i> Reset Nol
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Kolom 1: Uang Kertas (Lembaran) -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-3 shadow-xs space-y-2">
                        <div class="d-flex justify-content-between align-items-center border-b pb-2 mb-2">
                            <span class="font-bold text-xs text-slate-800 uppercase d-flex align-items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 d-inline-block"></span>
                                Uang Kertas (Lembaran)
                            </span>
                            <span class="font-mono font-bold text-xs text-emerald-700" x-text="formatRp(totalBills)"></span>
                        </div>

                        <!-- 100.000 -->
                        <div class="d-flex align-items-center justify-content-between gap-2 p-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                            <div class="w-24 d-flex align-items-center gap-1.5 font-bold text-rose-700">
                                <span class="badge bg-rose-100 text-rose-800 border border-rose-300 font-mono text-[10px] px-1.5 py-0.5">100k</span>
                                <span>Rp 100.000</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" @click="adjustBill(100000, -1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">-</button>
                                <input type="number" name="denominations[bills][100000]" x-model.number="bills[100000]" class="form-control form-control-sm text-center font-mono font-bold w-16 p-1 text-xs" min="0" placeholder="0">
                                <button type="button" @click="adjustBill(100000, 1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">+</button>
                                <span class="text-[10px] text-slate-400 w-8">lbr</span>
                            </div>
                            <div class="w-28 text-end font-mono font-bold text-slate-800" x-text="formatRp(bills[100000] * 100000)"></div>
                        </div>

                        <!-- 50.000 -->
                        <div class="d-flex align-items-center justify-content-between gap-2 p-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                            <div class="w-24 d-flex align-items-center gap-1.5 font-bold text-blue-700">
                                <span class="badge bg-blue-100 text-blue-800 border border-blue-300 font-mono text-[10px] px-1.5 py-0.5">50k</span>
                                <span>Rp 50.000</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" @click="adjustBill(50000, -1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">-</button>
                                <input type="number" name="denominations[bills][50000]" x-model.number="bills[50000]" class="form-control form-control-sm text-center font-mono font-bold w-16 p-1 text-xs" min="0" placeholder="0">
                                <button type="button" @click="adjustBill(50000, 1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">+</button>
                                <span class="text-[10px] text-slate-400 w-8">lbr</span>
                            </div>
                            <div class="w-28 text-end font-mono font-bold text-slate-800" x-text="formatRp(bills[50000] * 50000)"></div>
                        </div>

                        <!-- 20.000 -->
                        <div class="d-flex align-items-center justify-content-between gap-2 p-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                            <div class="w-24 d-flex align-items-center gap-1.5 font-bold text-emerald-700">
                                <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 font-mono text-[10px] px-1.5 py-0.5">20k</span>
                                <span>Rp 20.000</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" @click="adjustBill(20000, -1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">-</button>
                                <input type="number" name="denominations[bills][20000]" x-model.number="bills[20000]" class="form-control form-control-sm text-center font-mono font-bold w-16 p-1 text-xs" min="0" placeholder="0">
                                <button type="button" @click="adjustBill(20000, 1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">+</button>
                                <span class="text-[10px] text-slate-400 w-8">lbr</span>
                            </div>
                            <div class="w-28 text-end font-mono font-bold text-slate-800" x-text="formatRp(bills[20000] * 20000)"></div>
                        </div>

                        <!-- 10.000 -->
                        <div class="d-flex align-items-center justify-content-between gap-2 p-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                            <div class="w-24 d-flex align-items-center gap-1.5 font-bold text-purple-700">
                                <span class="badge bg-purple-100 text-purple-800 border border-purple-300 font-mono text-[10px] px-1.5 py-0.5">10k</span>
                                <span>Rp 10.000</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" @click="adjustBill(10000, -1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">-</button>
                                <input type="number" name="denominations[bills][10000]" x-model.number="bills[10000]" class="form-control form-control-sm text-center font-mono font-bold w-16 p-1 text-xs" min="0" placeholder="0">
                                <button type="button" @click="adjustBill(10000, 1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">+</button>
                                <span class="text-[10px] text-slate-400 w-8">lbr</span>
                            </div>
                            <div class="w-28 text-end font-mono font-bold text-slate-800" x-text="formatRp(bills[10000] * 10000)"></div>
                        </div>

                        <!-- 5.000 -->
                        <div class="d-flex align-items-center justify-content-between gap-2 p-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                            <div class="w-24 d-flex align-items-center gap-1.5 font-bold text-amber-700">
                                <span class="badge bg-amber-100 text-amber-800 border border-amber-300 font-mono text-[10px] px-1.5 py-0.5">5k</span>
                                <span>Rp 5.000</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" @click="adjustBill(5000, -1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">-</button>
                                <input type="number" name="denominations[bills][5000]" x-model.number="bills[5000]" class="form-control form-control-sm text-center font-mono font-bold w-16 p-1 text-xs" min="0" placeholder="0">
                                <button type="button" @click="adjustBill(5000, 1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">+</button>
                                <span class="text-[10px] text-slate-400 w-8">lbr</span>
                            </div>
                            <div class="w-28 text-end font-mono font-bold text-slate-800" x-text="formatRp(bills[5000] * 5000)"></div>
                        </div>

                        <!-- 2.000 -->
                        <div class="d-flex align-items-center justify-content-between gap-2 p-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                            <div class="w-24 d-flex align-items-center gap-1.5 font-bold text-slate-600">
                                <span class="badge bg-slate-200 text-slate-800 border border-slate-300 font-mono text-[10px] px-1.5 py-0.5">2k</span>
                                <span>Rp 2.000</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" @click="adjustBill(2000, -1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">-</button>
                                <input type="number" name="denominations[bills][2000]" x-model.number="bills[2000]" class="form-control form-control-sm text-center font-mono font-bold w-16 p-1 text-xs" min="0" placeholder="0">
                                <button type="button" @click="adjustBill(2000, 1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">+</button>
                                <span class="text-[10px] text-slate-400 w-8">lbr</span>
                            </div>
                            <div class="w-28 text-end font-mono font-bold text-slate-800" x-text="formatRp(bills[2000] * 2000)"></div>
                        </div>

                        <!-- 1.000 (Kertas) -->
                        <div class="d-flex align-items-center justify-content-between gap-2 p-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                            <div class="w-24 d-flex align-items-center gap-1.5 font-bold text-slate-600">
                                <span class="badge bg-slate-200 text-slate-800 border border-slate-300 font-mono text-[10px] px-1.5 py-0.5">1k</span>
                                <span>Rp 1.000</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" @click="adjustBill(1000, -1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">-</button>
                                <input type="number" name="denominations[bills][1000]" x-model.number="bills[1000]" class="form-control form-control-sm text-center font-mono font-bold w-16 p-1 text-xs" min="0" placeholder="0">
                                <button type="button" @click="adjustBill(1000, 1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">+</button>
                                <span class="text-[10px] text-slate-400 w-8">lbr</span>
                            </div>
                            <div class="w-28 text-end font-mono font-bold text-slate-800" x-text="formatRp(bills[1000] * 1000)"></div>
                        </div>
                    </div>

                    <!-- Kolom 2: Uang Koin (Logam) -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-3 shadow-xs space-y-2">
                        <div class="d-flex justify-content-between align-items-center border-b pb-2 mb-2">
                            <span class="font-bold text-xs text-slate-800 uppercase d-flex align-items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 d-inline-block"></span>
                                Uang Koin (Logam)
                            </span>
                            <span class="font-mono font-bold text-xs text-amber-700" x-text="formatRp(totalCoins)"></span>
                        </div>

                        <!-- Koin 1.000 -->
                        <div class="d-flex align-items-center justify-content-between gap-2 p-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                            <div class="w-24 d-flex align-items-center gap-1.5 font-bold text-slate-700">
                                <span class="badge bg-amber-100 text-amber-800 border border-amber-300 font-mono text-[10px] px-1.5 py-0.5">🪙 1k</span>
                                <span>Rp 1.000</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" @click="adjustCoin(1000, -1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">-</button>
                                <input type="number" name="denominations[coins][1000]" x-model.number="coins[1000]" class="form-control form-control-sm text-center font-mono font-bold w-16 p-1 text-xs" min="0" placeholder="0">
                                <button type="button" @click="adjustCoin(1000, 1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">+</button>
                                <span class="text-[10px] text-slate-400 w-8">kpg</span>
                            </div>
                            <div class="w-28 text-end font-mono font-bold text-slate-800" x-text="formatRp(coins[1000] * 1000)"></div>
                        </div>

                        <!-- Koin 500 -->
                        <div class="d-flex align-items-center justify-content-between gap-2 p-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                            <div class="w-24 d-flex align-items-center gap-1.5 font-bold text-slate-700">
                                <span class="badge bg-slate-200 text-slate-800 border border-slate-300 font-mono text-[10px] px-1.5 py-0.5">🪙 500</span>
                                <span>Rp 500</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" @click="adjustCoin(500, -1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">-</button>
                                <input type="number" name="denominations[coins][500]" x-model.number="coins[500]" class="form-control form-control-sm text-center font-mono font-bold w-16 p-1 text-xs" min="0" placeholder="0">
                                <button type="button" @click="adjustCoin(500, 1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">+</button>
                                <span class="text-[10px] text-slate-400 w-8">kpg</span>
                            </div>
                            <div class="w-28 text-end font-mono font-bold text-slate-800" x-text="formatRp(coins[500] * 500)"></div>
                        </div>

                        <!-- Koin 200 -->
                        <div class="d-flex align-items-center justify-content-between gap-2 p-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                            <div class="w-24 d-flex align-items-center gap-1.5 font-bold text-slate-700">
                                <span class="badge bg-slate-200 text-slate-800 border border-slate-300 font-mono text-[10px] px-1.5 py-0.5">🪙 200</span>
                                <span>Rp 200</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" @click="adjustCoin(200, -1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">-</button>
                                <input type="number" name="denominations[coins][200]" x-model.number="coins[200]" class="form-control form-control-sm text-center font-mono font-bold w-16 p-1 text-xs" min="0" placeholder="0">
                                <button type="button" @click="adjustCoin(200, 1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">+</button>
                                <span class="text-[10px] text-slate-400 w-8">kpg</span>
                            </div>
                            <div class="w-28 text-end font-mono font-bold text-slate-800" x-text="formatRp(coins[200] * 200)"></div>
                        </div>

                        <!-- Koin 100 -->
                        <div class="d-flex align-items-center justify-content-between gap-2 p-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                            <div class="w-24 d-flex align-items-center gap-1.5 font-bold text-slate-700">
                                <span class="badge bg-slate-200 text-slate-800 border border-slate-300 font-mono text-[10px] px-1.5 py-0.5">🪙 100</span>
                                <span>Rp 100</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" @click="adjustCoin(100, -1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">-</button>
                                <input type="number" name="denominations[coins][100]" x-model.number="coins[100]" class="form-control form-control-sm text-center font-mono font-bold w-16 p-1 text-xs" min="0" placeholder="0">
                                <button type="button" @click="adjustCoin(100, 1)" class="btn btn-xs btn-outline-secondary px-2 py-0.5 rounded font-bold">+</button>
                                <span class="text-[10px] text-slate-400 w-8">kpg</span>
                            </div>
                            <div class="w-28 text-end font-mono font-bold text-slate-800" x-text="formatRp(coins[100] * 100)"></div>
                        </div>
                    </div>

                    <!-- Grand Total Hitungan Kas Fisik -->
                    <div class="col-span-1 md:col-span-2 mt-2 p-3.5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white rounded-2xl shadow-xs">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-200 block">Total Uang Fisik Terhitung di Laci (Lembaran + Koin):</span>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <div class="font-mono font-extrabold text-2xl text-white" x-text="formatRp(totalDenominations)"></div>
                            <span class="badge bg-white text-emerald-900 font-bold text-xs px-3 py-1.5 rounded-xl shadow-xs">
                                <i class="fa-solid fa-check-double me-1"></i> Fisik Kas Laci Aktual
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. REKONSILIASI KAS MURNI: 5 KARTU REKONSILIASI -->
            <div class="rounded-2xl p-4 shadow-md space-y-3"
                 style="background-color: #0f172a; color: #ffffff; border: 1px solid #1e293b;">
                <div class="d-flex flex-column sm:flex-row justify-content-between sm:align-items-center gap-2">
                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-wider d-flex align-items-center gap-1.5" style="color: #f59e0b;">
                            <i class="fa-solid fa-scale-balanced"></i>
                            F. Status Rekonsiliasi Kas Laci & Selisih
                        </div>
                        <div class="text-[11px] mt-0.5" style="color: #94a3b8;">
                            Uang Cash Seharusnya = <strong>Kasir Tunai</strong> dikurangi <strong>Pengeluaran</strong> dikurangi <strong>Diambil Boss</strong>.
                        </div>
                    </div>
                    <div>
                        <template x-if="cashDifference === 0">
                            <span class="badge font-mono font-bold text-xs px-3 py-1.5 rounded-xl shadow-xs"
                                  style="background-color: #059669; color: #ffffff;">
                                <i class="fa-solid fa-circle-check me-1"></i> Rp 0 (PAS / SEIMBANG)
                            </span>
                        </template>
                        <template x-if="cashDifference > 0">
                            <span class="badge font-mono font-bold text-xs px-3 py-1.5 rounded-xl shadow-xs"
                                  style="background-color: #2563eb; color: #ffffff;">
                                <i class="fa-solid fa-arrow-up me-1"></i> + <span x-text="formatRp(cashDifference)"></span> (LEBIH)
                            </span>
                        </template>
                        <template x-if="cashDifference < 0">
                            <span class="badge font-mono font-bold text-xs px-3 py-1.5 rounded-xl shadow-xs"
                                  style="background-color: #dc2626; color: #ffffff;">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> - <span x-text="formatRp(Math.abs(cashDifference))"></span> (KURANG / TEKOR)
                            </span>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 p-3.5 rounded-xl text-xs" style="background-color: #1e293b; border: 1px solid #334155;">
                    <!-- 1. Penjualan Cash -->
                    <div class="p-2.5 rounded-lg" style="background-color: #0f172a; border: 1px solid #334155;">
                        <span class="text-[10px] uppercase font-bold tracking-wider block" style="color: #94a3b8;">💵 Penjualan Kasir POS:</span>
                        <div class="font-mono font-bold text-sm mt-1" style="color: #34d399;" x-text="formatRp(cashSales)"></div>
                    </div>

                    <!-- 2. Pengeluaran Hari Ini -->
                    <div class="p-2.5 rounded-lg" style="background-color: #0f172a; border: 1px solid #334155;">
                        <span class="text-[10px] uppercase font-bold tracking-wider block" style="color: #94a3b8;">📤 Pengeluaran (Cash Out):</span>
                        <div class="font-mono font-bold text-sm mt-1" style="color: #f87171;" x-text="'- ' + formatRp(totalCashOut)"></div>
                    </div>

                    <!-- 3. Diambil Boss -->
                    <div class="p-2.5 rounded-lg" style="background-color: #0f172a; border: 1px solid #334155;">
                        <span class="text-[10px] uppercase font-bold tracking-wider block" style="color: #fcd34d;">👑 Diambil Boss:</span>
                        <div class="font-mono font-bold text-sm mt-1" style="color: #fbbf24;" x-text="'- ' + formatRp(totalBossWithdrawal)"></div>
                    </div>

                    <!-- 4. Expected Cash -->
                    <div class="p-2.5 rounded-lg" style="background-color: #0f172a; border: 1px solid #334155;">
                        <span class="text-[10px] uppercase font-bold tracking-wider block" style="color: #38bdf8;">🎯 Target Fisik Sisa di Laci:</span>
                        <div class="font-mono font-extrabold text-sm mt-1" style="color: #38bdf8;" x-text="formatRp(totalExpectedCash)"></div>
                    </div>

                    <!-- 5. Actual Cash dari hitungan lembaran + koin -->
                    <div class="p-2.5 rounded-lg" style="background-color: #064e3b; border: 1px solid #059669;">
                        <span class="text-[10px] uppercase font-bold tracking-wider block" style="color: #6ee7b7;">💰 Kas Fisik Laci (Hitungan):</span>
                        <div class="font-mono font-extrabold text-sm mt-1" style="color: #4ade80;" x-text="formatRp(actualCash)"></div>
                        <input type="hidden" name="actual_cash" :value="actualCash">
                    </div>
                </div>
            </div>

            <!-- 7. DIGITAL SIGNATURE PREVIEW -->
            <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl d-flex align-items-center justify-content-between text-xs">
                <div class="d-flex align-items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white d-flex align-items-center justify-content-center flex-shrink-0 font-bold">
                        <i class="fa-solid fa-signature"></i>
                    </div>
                    <div>
                        <div class="font-extrabold text-emerald-950">Tanda Tangan Digital Kasir / Manager Otomatis</div>
                        <div class="text-[11px] text-emerald-700">
                            Laporan akan ditandatangani otomatis atas nama: <strong>{{ auth()->user()->full_name ?: auth()->user()->username }}</strong> ({{ strtoupper(auth()->user()->role) }})
                        </div>
                    </div>
                </div>
                <div>
                    <span class="badge bg-emerald-600 text-white text-[11px] px-3 py-1.5 font-bold rounded-xl shadow-xs">
                        <i class="fa-solid fa-check me-1"></i> Siap Disimpan
                    </span>
                </div>
            </div>

            <!-- 8. ACTION BUTTONS (SUBMIT & BATAL) -->
            <div class="pt-3 border-t d-flex justify-content-between align-items-center">
                <a href="{{ route('daily-closing.index') }}" class="btn btn-sm btn-outline-secondary rounded-xl text-xs font-bold px-4 py-2">
                    Batal
                </a>
                <button type="submit" class="btn btn-sm btn-primary rounded-xl text-xs font-extrabold px-5 py-2.5 shadow-md d-inline-flex align-items-center gap-2" style="background-color: #1e40af; border-color: #1e40af;">
                    <i class="fa-solid fa-paper-plane"></i>
                    Submit Laporan Tutup Kasir
                </button>
            </div>

        </div>
    </form>
</div>

<script>
function dailyClosingForm() {
    return {
        cashSales: {{ (float)$totalCashSales }},
        initialCashOut: {{ (float)$cashOut }},
        bossWithdrawalAmount: {{ (float)($existingBossWithdrawalTotal ?? 0) }},
        bills: {
            100000: 0,
            50000: 0,
            20000: 0,
            10000: 0,
            5000: 0,
            2000: 0,
            1000: 0
        },
        coins: {
            1000: 0,
            500: 0,
            200: 0,
            100: 0
        },
        materialsList: @json($branchMaterials ?? []),
        wastes: [],
        addWasteRow: function() {
            this.wastes.push({
                material_id: '',
                material_roll_id: '',
                waste_qty: '',
                unit: 'pcs',
                hpp_per_unit: 0,
                subtotal_hpp: 0,
                reason: 'Trial Alignment Printhead',
                notes: ''
            });
        },
        removeWasteRow: function(index) {
            this.wastes.splice(index, 1);
        },
        getMaterialRolls: function(materialId) {
            if (!materialId) return [];
            var mat = this.materialsList.find(function(m) { return m.id == materialId; });
            return (mat && mat.rolls) ? mat.rolls : [];
        },
        onMaterialChange: function(index) {
            var item = this.wastes[index];
            var mat = this.materialsList.find(function(m) { return m.id == item.material_id; });
            if (!mat) {
                item.material_roll_id = '';
                item.unit = 'pcs';
                item.hpp_per_unit = 0;
                this.calculateWasteHpp(index);
                return;
            }
            if (mat.rolls && mat.rolls.length > 0) {
                item.material_roll_id = mat.rolls[0].id;
                item.unit = 'm';
                var rollWidth = parseFloat(mat.rolls[0].roll_width_m) || 1.0;
                item.hpp_per_unit = (parseFloat(mat.purchase_price) || 0) * rollWidth;
            } else {
                item.material_roll_id = '';
                item.unit = mat.unit || 'pcs';
                item.hpp_per_unit = parseFloat(mat.purchase_price) || 0;
            }
            this.calculateWasteHpp(index);
        },
        onRollChange: function(index) {
            var item = this.wastes[index];
            var mat = this.materialsList.find(function(m) { return m.id == item.material_id; });
            if (!mat) return;
            var roll = (mat.rolls || []).find(function(r) { return r.id == item.material_roll_id; });
            if (roll) {
                var rollWidth = parseFloat(roll.roll_width_m) || 1.0;
                item.hpp_per_unit = (parseFloat(mat.purchase_price) || 0) * rollWidth;
            } else {
                item.hpp_per_unit = parseFloat(mat.purchase_price) || 0;
            }
            this.calculateWasteHpp(index);
        },
        calculateWasteHpp: function(index) {
            var item = this.wastes[index];
            var qty = parseFloat(item.waste_qty) || 0;
            item.subtotal_hpp = Math.round(qty * (parseFloat(item.hpp_per_unit) || 0));
        },
        get totalWasteHpp() {
            return this.wastes.reduce(function(sum, item) { return sum + (parseFloat(item.subtotal_hpp) || 0); }, 0);
        },
        newExpenses: [],
        addExpenseRow: function() {
            this.newExpenses.push({
                account_id: '{{ $expenseAccounts->first()->id ?? 11 }}',
                keterangan: '',
                amount: ''
            });
        },
        removeExpenseRow: function(index) {
            this.newExpenses.splice(index, 1);
        },
        get totalNewExpenses() {
            return this.newExpenses.reduce(function(sum, item) { return sum + (parseFloat(item.amount) || 0); }, 0);
        },
        get totalCashOut() {
            return Number(this.initialCashOut) + this.totalNewExpenses;
        },
        get totalBossWithdrawal() {
            return parseFloat(this.bossWithdrawalAmount) || 0;
        },
        get totalExpectedCash() {
            return Math.max(0, Number(this.cashSales) - this.totalCashOut - this.totalBossWithdrawal);
        },
        get totalBills() {
            return (this.bills[100000] * 100000) +
                   (this.bills[50000] * 50000) +
                   (this.bills[20000] * 20000) +
                   (this.bills[10000] * 10000) +
                   (this.bills[5000] * 5000) +
                   (this.bills[2000] * 2000) +
                   (this.bills[1000] * 1000);
        },
        get totalCoins() {
            return (this.coins[1000] * 1000) +
                   (this.coins[500] * 500) +
                   (this.coins[200] * 200) +
                   (this.coins[100] * 100);
        },
        get totalDenominations() {
            return this.totalBills + this.totalCoins;
        },
        get actualCash() {
            return this.totalDenominations;
        },
        get cashDifference() {
            return this.actualCash - this.totalExpectedCash;
        },
        formatRp: function(val) {
            return 'Rp ' + Math.round(val || 0).toLocaleString('id-ID');
        },
        adjustBill: function(nominal, delta) {
            var current = parseInt(this.bills[nominal]) || 0;
            this.bills[nominal] = Math.max(0, current + delta);
        },
        adjustCoin: function(nominal, delta) {
            var current = parseInt(this.coins[nominal]) || 0;
            this.coins[nominal] = Math.max(0, current + delta);
        },
        resetDenominations: function() {
            if (confirm('Kosongkan semua hitungan lembaran dan koin?')) {
                for (var k in this.bills) this.bills[k] = 0;
                for (var k in this.coins) this.coins[k] = 0;
            }
        }
    };
}
</script>
@endsection
