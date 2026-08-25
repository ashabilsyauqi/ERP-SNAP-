@extends('layouts.app')

@section('title', 'Buat Purchase Plan & RFQ Bundle Baru')
@section('page-title', 'Penyusunan Rencana Pengadaan (Purchase Plan)')

@section('action-buttons')
<a href="{{ route('purchasing.plans.index') }}" class="btn-odoo-secondary text-decoration-none">
    <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar Plan
</a>
@endsection

@section('content')
<div class="o_form_sheet p-0 overflow-hidden bg-white max-w-6xl mx-auto shadow-sm border"
     x-data="{
        existingMaterials: @json($materials),
        items: [
            { material_name: '', supplier_name: '', fixed_size: '', qty: 1, estimated_unit_price: 0, retail_price: 0 }
        ],
        addItem() {
            this.items.push({ material_name: '', supplier_name: '', fixed_size: '', qty: 1, estimated_unit_price: 0, retail_price: 0 });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            } else {
                alert('Purchase Plan minimal harus memiliki 1 item produk.');
            }
        },
        onMaterialSelect(index, event) {
            const val = event.target.value;
            const found = this.existingMaterials.find(m => m.material_name === val);
            if (found) {
                this.items[index].material_name = found.material_name;
                this.items[index].supplier_name = found.supplier ? found.supplier.name : '';
                this.items[index].fixed_size = found.fixed_size || '';
                this.items[index].estimated_unit_price = found.purchase_price || 0;
                this.items[index].retail_price = found.retail_price || 0;
            } else {
                this.items[index].material_name = val;
            }
        },
        getItemSubtotal(item) {
            return (Number(item.qty) || 0) * (Number(item.estimated_unit_price) || 0);
        },
        get totalEstimatedCost() {
            return this.items.reduce((sum, item) => sum + this.getItemSubtotal(item), 0);
        }
     }">

    <form action="{{ route('purchasing.plans.store') }}" method="POST">
        @csrf

        <!-- Sheet Header Bar -->
        <div class="bg-slate-900 text-white px-5 py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('images/logosnaprint.jpeg') }}" alt="SnapPrint" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                <div>
                    <h5 class="fw-bold mb-0 text-white font-mono">FORMULIR PURCHASE PLAN (BUNDLE RFQ)</h5>
                    <span class="text-xs text-slate-300">Rencana Pengadaan Multi-Produk dengan Rincian Anggaran</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-600 text-white px-3 py-1 text-xs font-bold font-mono" x-text="'Total: Rp ' + totalEstimatedCost.toLocaleString('id-ID')"></span>
            </div>
        </div>

        <div class="p-4 space-y-4">
            <!-- Plan General Information -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                <h6 class="fw-bold text-slate-800 text-xs uppercase mb-2 border-bottom pb-2">
                    <i class="fa-solid fa-clipboard-list text-blue-600 me-1"></i> Informasi Rencana Pengadaan
                </h6>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                            Judul / Tujuan Rencana Pengadaan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="title" required placeholder="Contoh: Restok Bahan Cetak Outdoor & Banner Bulanan" 
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs focus:border-blue-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                            Target Tanggal Realisasi
                        </label>
                        <input type="date" name="target_date" 
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs focus:border-blue-600 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @if(auth()->user()->isOwner())
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                Cabang Tujuan Pengadaan
                            </label>
                            <select name="branch_id" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs focus:border-blue-600 focus:outline-none">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->nama_cabang }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="{{ auth()->user()->isOwner() ? 'md:col-span-2' : 'md:col-span-3' }}">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                            Catatan & Justifikasi Kebutuhan Pengadaan
                        </label>
                        <input type="text" name="notes" placeholder="Contoh: Kebutuhan cetak untuk event pameran akhir bulan & stok gudang menipis" 
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs focus:border-blue-600 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Bundle Products Dynamic Table -->
            <div class="space-y-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-slate-800 text-xs uppercase mb-0">
                        <i class="fa-solid fa-boxes-stacked text-blue-600 me-1"></i> Bundle Daftar Produk & Rincian Biaya
                    </h6>
                    <button type="button" @click="addItem()" class="btn btn-sm btn-outline-primary py-1 px-3 text-xs font-semibold">
                        <i class="fa-solid fa-plus me-1"></i> Tambah Baris Produk
                    </button>
                </div>

                <div class="table-responsive border rounded-xl overflow-hidden shadow-sm">
                    <table class="table table-bordered table-sm mb-0 text-xs align-middle">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th style="width: 30px;" class="text-center">No</th>
                                <th style="width: 25%;">Nama Bahan / Produk <span class="text-rose-500">*</span></th>
                                <th style="width: 20%;">Supplier / Vendor</th>
                                <th style="width: 10%;" class="text-center">Ukuran (m)</th>
                                <th style="width: 10%;" class="text-center">Qty <span class="text-rose-500">*</span></th>
                                <th style="width: 15%;" class="text-end">Est. Harga Beli (Rp) <span class="text-rose-500">*</span></th>
                                <th style="width: 15%;" class="text-end">Subtotal Biaya</th>
                                <th style="width: 40px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in items" :key="idx">
                                <tr class="bg-white hover:bg-slate-50">
                                    <td class="text-center text-slate-500 font-bold" x-text="idx + 1"></td>
                                    
                                    <!-- Material Input with Datalist -->
                                    <td>
                                        <input type="text" :name="'items[' + idx + '][material_name]'" x-model="item.material_name" 
                                            @change="onMaterialSelect(idx, $event)"
                                            list="material-suggestions" required placeholder="Pilih / ketik nama bahan" 
                                            class="w-full px-2 py-1 bg-white border border-slate-200 rounded text-xs focus:border-blue-600 focus:outline-none">
                                    </td>

                                    <!-- Supplier Input with Datalist -->
                                    <td>
                                        <input type="text" :name="'items[' + idx + '][supplier_name]'" x-model="item.supplier_name" 
                                            list="supplier-suggestions" placeholder="Nama vendor / supplier" 
                                            class="w-full px-2 py-1 bg-white border border-slate-200 rounded text-xs focus:border-blue-600 focus:outline-none">
                                    </td>

                                    <!-- Fixed Size (Specs) -->
                                    <td>
                                        <input type="number" step="0.1" :name="'items[' + idx + '][fixed_size]'" x-model="item.fixed_size" placeholder="Ukuran" 
                                            class="w-full px-2 py-1 text-center bg-white border border-slate-200 rounded text-xs focus:border-blue-600 focus:outline-none">
                                    </td>

                                    <!-- Qty -->
                                    <td>
                                        <input type="number" min="1" :name="'items[' + idx + '][qty]'" x-model="item.qty" required 
                                            class="w-full px-2 py-1 text-center font-bold bg-white border border-slate-200 rounded text-xs focus:border-blue-600 focus:outline-none">
                                    </td>

                                    <!-- Estimated Unit Price -->
                                    <td>
                                        <input type="number" min="0" :name="'items[' + idx + '][estimated_unit_price]'" x-model="item.estimated_unit_price" required 
                                            class="w-full px-2 py-1 text-end font-mono bg-white border border-slate-200 rounded text-xs focus:border-blue-600 focus:outline-none">
                                    </td>

                                    <!-- Subtotal (Calculated) -->
                                    <td class="text-end font-mono font-bold text-blue-900" x-text="'Rp ' + getItemSubtotal(item).toLocaleString('id-ID')"></td>

                                    <!-- Delete Row -->
                                    <td class="text-center">
                                        <button type="button" @click="removeItem(idx)" class="text-rose-500 hover:text-rose-700 border-0 bg-transparent cursor-pointer p-1" title="Hapus Baris">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-slate-100">
                            <tr>
                                <td colspan="6" class="text-end fw-bold text-slate-700 fs-6">TOTAL ESTIMASI BIAYA RENCANA PENGADAAN:</td>
                                <td class="text-end fw-bold font-mono text-blue-900 fs-6" x-text="'Rp ' + totalEstimatedCost.toLocaleString('id-ID')"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Datalist Autocompletes -->
                <datalist id="material-suggestions">
                    @foreach($materials as $m)
                        <option value="{{ $m->material_name }}">{{ $m->material_name }} ({{ $m->branch->nama_cabang ?? 'Pusat' }})</option>
                    @endforeach
                </datalist>

                <datalist id="supplier-suggestions">
                    @foreach($suppliers as $s)
                        <option value="{{ $s->name }}">{{ $s->name }}</option>
                    @endforeach
                </datalist>
            </div>
        </div>

        <!-- Action Footer -->
        <div class="bg-slate-50 px-5 py-3.5 border-top d-flex justify-content-between align-items-center">
            <a href="{{ route('purchasing.plans.index') }}" class="btn-odoo-secondary text-decoration-none">
                Batal
            </a>
            
            <div class="d-flex gap-2">
                <button type="submit" name="action_type" value="draft" class="btn btn-sm btn-outline-secondary font-semibold px-3">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan sebagai Draft
                </button>
                <button type="submit" name="action_type" value="submit_rfq" class="btn btn-sm btn-primary font-bold px-4">
                    <i class="fa-solid fa-paper-plane me-1"></i> Ajukan RFQ ke Owner
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
