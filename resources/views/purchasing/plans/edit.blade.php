@extends('layouts.app')

@section('title', 'Edit Purchase Plan - ' . $plan->plan_number)
@section('page-title', 'Edit Rencana Pengadaan (Purchase Plan)')

@section('action-buttons')
<button type="button" onclick="submitPlanForm('submit_rfq')" class="btn-odoo-primary">
    <i class="fa-solid fa-paper-plane me-1"></i>
    <span>Ajukan ke Owner (ACC)</span>
</button>
<button type="button" onclick="submitPlanForm('draft')" class="btn-odoo-secondary">
    <i class="fa-solid fa-floppy-disk me-1"></i>
    <span>Simpan Draft</span>
</button>
<a href="{{ route('purchasing.plans.index') }}" class="btn-odoo-secondary text-decoration-none">
    Batal
</a>
@endsection

@section('content')
<div class="max-w-5xl mx-auto" x-data="purchasePlanForm()" id="plan-form-container">

    <!-- Odoo Form Statusbar -->
    <div class="o_form_statusbar mb-3">
        <div class="d-flex align-items-center gap-2">
            <button type="button" @click="submit('submit_rfq')" class="btn-odoo-primary text-xs">
                <i class="fa-solid fa-paper-plane me-1"></i> Simpan & Ajukan ke Owner
            </button>
            <button type="button" @click="submit('draft')" class="btn-odoo-secondary text-xs">
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Draft
            </button>
            <a href="{{ route('purchasing.plans.index') }}" class="btn-odoo-secondary text-xs text-decoration-none">
                Batal
            </a>
        </div>
        <div class="o_statusbar_status">
            <div class="o_arrow_button {{ $plan->status === 'draft' ? 'active' : '' }}">1. Draft Plan</div>
            <div class="o_arrow_button {{ $plan->status === 'waiting_owner_approval' ? 'active' : '' }}">2. Persetujuan Owner</div>
            <div class="o_arrow_button">3. GRN Gudang</div>
        </div>
    </div>

    @if($plan->rejection_notes)
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-800 mb-3 flex items-start gap-2">
            <i class="fa-solid fa-circle-exclamation text-rose-600 mt-0.5"></i>
            <div>
                <strong>Catatan Penolakan Sebelumnya:</strong>
                <div>{{ $plan->rejection_notes }}</div>
            </div>
        </div>
    @endif

    <!-- Odoo Form Sheet -->
    <div class="o_form_sheet p-4 bg-white shadow-sm border rounded">
        <!-- Title & Stat Widget -->
        <div class="mb-4 pb-3 border-bottom d-flex justify-content-between align-items-start">
            <div>
                <h4 class="fw-bold text-slate-900 mb-1">Edit Purchase Plan: {{ $plan->plan_number }}</h4>
                <p class="text-xs text-slate-500 mb-0">Ubah rincian bundle produk atau perbarui harga sebelum diajukan ke Owner.</p>
            </div>
            <div class="o_stat_button bg-slate-50 border">
                <i class="fa-solid fa-wallet text-teal-600 fs-5"></i>
                <div>
                    <div class="o_stat_value text-teal-700 font-mono" x-text="'Rp ' + totalEstimatedCost.toLocaleString('id-ID')">Rp 0</div>
                    <div class="o_stat_text">Total Anggaran Plan</div>
                </div>
            </div>
        </div>

        <form action="{{ route('purchasing.plans.update', $plan->id) }}" method="POST" id="purchase-plan-form" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="action_type" id="action_type_input" value="submit_rfq">

            <!-- General Plan Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">
                        Judul / Tujuan Rencana Pengadaan <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title', $plan->title) }}" required placeholder="Contoh: Restok Bahan Cetak & Banner Bulanan" 
                        class="form-control form-control-sm">
                </div>

                <div>
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">
                        Target Tanggal Realisasi
                    </label>
                    <input type="date" name="target_date" value="{{ old('target_date', $plan->target_date ? $plan->target_date->format('Y-m-d') : '') }}" class="form-control form-control-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @if(auth()->user()->isOwner())
                    <div>
                        <label class="form-label font-semibold text-slate-700 text-xs uppercase">Cabang Tujuan Pengadaan</label>
                        <select name="branch_id" class="form-select form-select-sm">
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ $plan->branch_id == $b->id ? 'selected' : '' }}>{{ $b->nama_cabang }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="{{ auth()->user()->isOwner() ? 'md:col-span-2' : 'md:col-span-3' }}">
                    <label class="form-label font-semibold text-slate-700 text-xs uppercase">
                        Catatan & Justifikasi Kebutuhan Pengadaan
                    </label>
                    <input type="text" name="notes" value="{{ old('notes', $plan->notes) }}" placeholder="Contoh: Kebutuhan cetak promo akhir bulan & stok menipis" 
                        class="form-control form-control-sm">
                </div>
            </div>

            <!-- Bundle Products Table Section -->
            <div class="p-3 bg-slate-50 rounded border mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-slate-800 text-xs uppercase mb-0">
                        <i class="fa-solid fa-boxes-stacked text-teal-600 me-1"></i> Bundle Daftar Produk & Rincian Biaya
                    </h6>
                    <button type="button" @click="addItem()" class="btn btn-sm btn-outline-secondary py-0.5 px-2 text-xs">
                        <i class="fa-solid fa-plus me-1"></i> Tambah Baris Produk
                    </button>
                </div>

                <div class="table-responsive bg-white rounded border" style="overflow: visible !important;">
                    <table class="table table-sm table-bordered mb-0 text-xs align-middle">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th style="width: 35px;" class="text-center">No</th>
                                <th style="width: 28%;">Nama Bahan / Produk <span class="text-rose-500">*</span></th>
                                <th style="width: 22%;">Vendor / Supplier</th>
                                <th style="width: 10%;" class="text-center">Ukuran (m)</th>
                                <th style="width: 10%;" class="text-center">Qty <span class="text-rose-500">*</span></th>
                                <th style="width: 15%;" class="text-end">Est. Harga Beli (Rp) <span class="text-rose-500">*</span></th>
                                <th style="width: 15%;" class="text-end">Subtotal (Rp)</th>
                                <th style="width: 35px;" class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in items" :key="idx">
                                <tr>
                                    <td class="text-center font-bold text-slate-400" x-text="idx + 1"></td>
                                    
                                    <!-- Material Input with Modern Seamless Floating Dropdown -->
                                    <td class="position-relative" x-data="{ open: false }" @click.outside="open = false">
                                        <input type="text" 
                                               :name="'items[' + idx + '][material_name]'" 
                                               x-model="item.material_name" 
                                               @focus="open = true" 
                                               @input="open = true"
                                               required 
                                               autocomplete="off"
                                               placeholder="Ketik / cari nama bahan..." 
                                               class="form-control form-control-sm py-1 bg-white focus:border-blue-600">
                                        
                                        <!-- Seamless Floating Dropdown Box -->
                                        <div x-show="open" 
                                             x-cloak
                                             class="position-absolute bg-white rounded-2 shadow-lg border p-1 z-3"
                                             style="top: 100%; left: 0; min-width: 280px; max-height: 240px; overflow-y: auto;">
                                            
                                            <template x-for="mat in filteredMaterials(item.material_name)" :key="mat.id">
                                                <div @click="selectMaterial(idx, mat); open = false" 
                                                     class="p-2 border-bottom cursor-pointer hover:bg-light transition text-start">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <strong class="text-slate-900 text-xs" x-text="mat.material_name"></strong>
                                                        <span class="badge bg-light text-secondary text-[10px]" x-text="mat.branch ? mat.branch.nama_cabang : 'Pusat'"></span>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center mt-1 text-[10px] text-muted">
                                                        <span x-text="mat.supplier ? mat.supplier.name : '-'"></span>
                                                        <span class="font-mono fw-bold text-success" x-text="'Rp ' + Number(mat.purchase_price || 0).toLocaleString('id-ID')"></span>
                                                    </div>
                                                </div>
                                            </template>

                                            <div @click="open = false" 
                                                 class="p-2 bg-light text-primary fw-semibold cursor-pointer text-xs transition">
                                                <i class="fa-solid fa-plus-circle me-1"></i>
                                                <span x-text="'Gunakan bahan baru: \'' + (item.material_name || '') + '\''"></span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Supplier Input with Modern Seamless Floating Dropdown -->
                                    <td class="position-relative" x-data="{ open: false }" @click.outside="open = false">
                                        <input type="text" 
                                               :name="'items[' + idx + '][supplier_name]'" 
                                               x-model="item.supplier_name" 
                                               @focus="open = true" 
                                               @input="open = true"
                                               autocomplete="off"
                                               placeholder="Vendor / supplier..." 
                                               class="form-control form-control-sm py-1 bg-white focus:border-blue-600">
                                        
                                        <!-- Seamless Floating Dropdown Box -->
                                        <div x-show="open" 
                                             x-cloak
                                             class="position-absolute bg-white rounded-2 shadow-lg border p-1 z-3"
                                             style="top: 100%; left: 0; min-width: 240px; max-height: 200px; overflow-y: auto;">
                                            
                                            <template x-for="s in filteredSuppliers(item.supplier_name)" :key="s.id">
                                                <div @click="selectSupplier(idx, s); open = false" 
                                                     class="p-2 border-bottom cursor-pointer hover:bg-light transition text-start d-flex justify-content-between align-items-center">
                                                    <strong class="text-slate-800 text-xs" x-text="s.name"></strong>
                                                    <i class="fa-solid fa-building text-muted text-[10px]"></i>
                                                </div>
                                            </template>

                                            <div @click="open = false" 
                                                 class="p-2 bg-light text-primary fw-semibold cursor-pointer text-xs transition">
                                                <i class="fa-solid fa-plus-circle me-1"></i>
                                                <span x-text="'Gunakan vendor baru: \'' + (item.supplier_name || '') + '\''"></span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <input type="number" step="0.01" :name="'items[' + idx + '][fixed_size]'" x-model="item.fixed_size" placeholder="Pcs/m" 
                                            class="form-control form-control-sm py-1 text-center">
                                    </td>

                                    <td>
                                        <input type="number" min="1" :name="'items[' + idx + '][qty]'" x-model="item.qty" required 
                                            class="form-control form-control-sm py-1 text-center font-bold">
                                    </td>

                                    <td>
                                        <input type="number" min="0" :name="'items[' + idx + '][estimated_unit_price]'" x-model="item.estimated_unit_price" required 
                                            class="form-control form-control-sm py-1 text-end font-mono">
                                    </td>

                                    <td class="text-end font-mono fw-bold text-slate-800" x-text="'Rp ' + getItemSubtotal(item).toLocaleString('id-ID')"></td>

                                    <td class="text-center">
                                        <button type="button" @click="removeItem(idx)" class="text-danger border-0 bg-transparent p-0" title="Hapus Baris">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-slate-50 font-bold">
                            <tr>
                                <td colspan="6" class="text-end text-slate-700">TOTAL ESTIMASI BIAYA PENGADAAN:</td>
                                <td class="text-end font-mono text-teal-800 fs-6" x-text="'Rp ' + totalEstimatedCost.toLocaleString('id-ID')"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Action Buttons Footer -->
            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="{{ route('purchasing.plans.index') }}" class="btn-odoo-secondary">Batal</a>
                <button type="button" @click="submit('draft')" class="btn btn-sm btn-outline-secondary font-semibold">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Draft
                </button>
                <button type="button" @click="submit('submit_rfq')" class="btn-odoo-primary">
                    <i class="fa-solid fa-paper-plane me-1"></i> Ajukan ke Owner (ACC)
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.rawMaterials = @json($materials);
    window.rawSuppliers = @json($suppliers);
    window.initialPlanItems = @json($initialPlanItems ?? []);

    function purchasePlanForm() {
        return {
            items: window.initialPlanItems && window.initialPlanItems.length > 0 
                ? window.initialPlanItems 
                : [{ material_name: '', supplier_name: '', fixed_size: '', qty: 1, estimated_unit_price: 0, retail_price: 0 }],
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
            filteredMaterials(query) {
                const q = (query || '').toLowerCase().trim();
                if (!q) return window.rawMaterials.slice(0, 8);
                return window.rawMaterials.filter(function(m) {
                    return m.material_name.toLowerCase().includes(q);
                }).slice(0, 10);
            },
            filteredSuppliers(query) {
                const q = (query || '').toLowerCase().trim();
                if (!q) return window.rawSuppliers.slice(0, 8);
                return window.rawSuppliers.filter(function(s) {
                    return s.name.toLowerCase().includes(q);
                }).slice(0, 10);
            },
            selectMaterial(index, mat) {
                this.items[index].material_name = mat.material_name;
                this.items[index].supplier_name = mat.supplier ? mat.supplier.name : '';
                this.items[index].fixed_size = mat.fixed_size || '';
                this.items[index].estimated_unit_price = mat.purchase_price || 0;
                this.items[index].retail_price = mat.retail_price || 0;
            },
            selectSupplier(index, supp) {
                this.items[index].supplier_name = supp.name;
            },
            getItemSubtotal(item) {
                return (Number(item.qty) || 0) * (Number(item.estimated_unit_price) || 0);
            },
            get totalEstimatedCost() {
                return this.items.reduce(function(sum, item) {
                    return sum + ((Number(item.qty) || 0) * (Number(item.estimated_unit_price) || 0));
                }, 0);
            },
            submit(actionType) {
                document.getElementById('action_type_input').value = actionType;
                document.getElementById('purchase-plan-form').submit();
            }
        };
    }

    function submitPlanForm(actionType) {
        document.getElementById('action_type_input').value = actionType;
        document.getElementById('purchase-plan-form').submit();
    }
</script>
@endsection
