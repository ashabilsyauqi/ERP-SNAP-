@extends('layouts.app')

@section('title', 'New Request for Quotation')
@section('page-title', 'Buat PO Baru')

@section('action-buttons')
<button type="button" onclick="document.getElementById('purchase-form').submit()" class="btn-odoo-primary">
    <i class="fa-solid fa-check"></i>
    <span>Simpan & Ajukan PO</span>
</button>
<a href="{{ route('purchasing.index') }}" class="btn-odoo-secondary text-decoration-none">
    Discard
</a>
@endsection

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Odoo Form Statusbar -->
    <div class="o_form_statusbar mb-3">
        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="document.getElementById('purchase-form').submit()" class="btn-odoo-primary text-xs">
                Confirm Order
            </button>
            <a href="{{ route('purchasing.index') }}" class="btn-odoo-secondary text-xs text-decoration-none">
                Cancel
            </a>
        </div>
        <div class="o_statusbar_status">
            <div class="o_arrow_button active">RFQ</div>
            <div class="o_arrow_button">Purchase Order</div>
            <div class="o_arrow_button">Received</div>
        </div>
    </div>

    <!-- Odoo Form Sheet -->
    <div class="o_form_sheet p-4 bg-white">
        <div class="mb-4 pb-3 border-bottom d-flex justify-content-between align-items-start">
            <div>
                <h4 class="fw-bold text-slate-900 mb-1">New Purchase Order (PO Baru)</h4>
                <p class="text-xs text-slate-500 mb-0">Terbitkan pesanan pembelian material/bahan baku ke vendor.</p>
            </div>
            <div class="o_stat_button">
                <i class="fa-solid fa-truck-ramp-box text-teal-600 fs-5"></i>
                <div>
                    <div class="o_stat_value">Draft</div>
                    <div class="o_stat_text">Procurement</div>
                </div>
            </div>
        </div>

        <form action="{{ route('purchasing.store') }}" method="POST" id="purchase-form" class="space-y-4">
            @csrf
            
            @if(auth()->user()->isOwner())
            <div class="mb-3">
                <label for="branch_id" class="form-label font-semibold text-slate-700 text-xs uppercase">Target Warehouse / Cabang Penerima</label>
                <select name="branch_id" id="branch_id" required class="form-select form-select-sm">
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (request('branch_id') == $branch->id || auth()->user()->branch_id == $branch->id) ? 'selected' : '' }}>
                            {{ $branch->nama_cabang }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Autocomplete Dropdown for Material Name -->
                <div class="relative">
                    <label for="material_name" class="form-label font-semibold text-slate-700 text-xs uppercase">Product / Nama Bahan Baku</label>
                    <input type="text" id="material_name" name="material_name" required autocomplete="off"
                        class="form-control form-control-sm" placeholder="Type to search or create new...">
                    <div id="material_dropdown" class="hidden absolute z-30 mt-1 w-full bg-white shadow-xl max-h-60 rounded border overflow-auto text-sm divide-y">
                        <!-- Options injected by JS -->
                    </div>
                </div>

                <!-- Autocomplete Dropdown for Supplier Name -->
                <div class="relative">
                    <label for="supplier_name" class="form-label font-semibold text-slate-700 text-xs uppercase">Vendor / Supplier</label>
                    <input type="text" id="supplier_name" name="supplier_name" autocomplete="off"
                        class="form-control form-control-sm" placeholder="Type to search or create new...">
                    <div id="supplier_dropdown" class="hidden absolute z-30 mt-1 w-full bg-white shadow-xl max-h-60 rounded border overflow-auto text-sm divide-y">
                        <!-- Options injected by JS -->
                    </div>
                </div>

                <!-- No Faktur / Vendor Reference -->
                <div>
                    <label for="vendor_ref" class="form-label font-semibold text-slate-700 text-xs uppercase">Vendor Reference / No. Faktur</label>
                    <input type="text" id="vendor_ref" name="vendor_ref" class="form-control form-control-sm" placeholder="e.g. INV-SUPP-98765">
                </div>

                <!-- Fixed Size -->
                <div>
                    <label for="fixed_size" class="form-label font-semibold text-slate-700 text-xs uppercase">Length / Size (Meter, Optional)</label>
                    <input type="number" step="0.01" id="fixed_size" name="fixed_size" class="form-control form-control-sm" placeholder="e.g. 50">
                </div>
            </div>

            <div class="p-3 bg-slate-50 rounded border mt-4">
                <h6 class="fw-bold text-slate-800 text-xs uppercase mb-3"><i class="fa-solid fa-receipt text-teal-600 me-1"></i> Pricing & Quantity</h6>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="qty_bought" class="form-label font-semibold text-slate-700 text-xs uppercase">Kuantitas Order (Qty)</label>
                        <input type="number" id="qty_bought" name="qty_bought" required min="1" class="form-control form-control-sm font-bold" placeholder="10">
                    </div>
                    <div>
                        <label for="purchase_price" class="form-label font-semibold text-slate-700 text-xs uppercase">Unit Cost / Modal HPP (Rp)</label>
                        <input type="number" id="purchase_price" name="purchase_price" required min="0" class="form-control form-control-sm font-mono" placeholder="150000">
                    </div>
                    <div>
                        <label for="retail_price" class="form-label font-semibold text-slate-700 text-xs uppercase">Sales Price / Harga Eceran (Rp)</label>
                        <input type="number" id="retail_price" name="retail_price" required min="0" class="form-control form-control-sm font-mono" placeholder="220000">
                    </div>
                </div>
            </div>

            <!-- Dynamic Wholesale Tiers Section -->
            <div class="p-3 bg-slate-50 rounded border mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-slate-800 text-xs uppercase mb-0"><i class="fa-solid fa-tags text-teal-600 me-1"></i> Tier Wholesale Price (Harga Grosir)</h6>
                    <button type="button" onclick="addWholesaleTier()" class="btn btn-sm btn-outline-secondary py-0 px-2 text-xs">
                        <i class="fa-solid fa-plus me-1"></i> Add Tier
                    </button>
                </div>
                <div id="wholesale-container" class="space-y-2">
                    <!-- Tiers injected by JS -->
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="{{ route('purchasing.index') }}" class="btn-odoo-secondary">Discard</a>
                <button type="submit" class="btn-odoo-primary">Simpan & Ajukan PO</button>
            </div>
        </form>
    </div>
</div>

<script>
    const materials = @json($materials);
    const suppliers = @json($suppliers);

    // --- Autocomplete Logic: Material ---
    const inputName = document.getElementById('material_name');
    const dropdown = document.getElementById('material_dropdown');

    inputName.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        dropdown.innerHTML = '';
        
        if (!val) {
            dropdown.classList.add('hidden');
            return;
        }

        const matches = materials.filter(m => m.material_name.toLowerCase().includes(val));
        
        if (matches.length > 0) {
            dropdown.classList.remove('hidden');
            matches.forEach(match => {
                const div = document.createElement('div');
                div.className = 'cursor-pointer p-2 hover:bg-slate-100 text-slate-800 text-xs';
                div.innerHTML = `<strong>${match.material_name}</strong> <span class="text-slate-400">(${match.branch ? match.branch.nama_cabang : 'Pusat'})</span>`;
                div.onclick = () => {
                    inputName.value = match.material_name;
                    dropdown.classList.add('hidden');
                    autoFillPricing(match.material_name);
                };
                dropdown.appendChild(div);
            });
        } else {
            dropdown.classList.remove('hidden');
        }

        const createDiv = document.createElement('div');
        createDiv.className = 'cursor-pointer p-2 bg-teal-50 text-teal-800 font-bold hover:bg-teal-600 hover:text-white text-xs transition';
        createDiv.innerText = '+ Create New Material: "' + this.value + '"';
        createDiv.onclick = () => {
            dropdown.classList.add('hidden');
            document.getElementById('fixed_size').value = '';
            document.getElementById('purchase_price').value = '';
            document.getElementById('retail_price').value = '';
            document.getElementById('supplier_name').value = '';
            document.getElementById('wholesale-container').innerHTML = '';
            addWholesaleTier();
        };
        dropdown.appendChild(createDiv);
    });

    // --- Autocomplete Logic: Supplier ---
    const supplierInput = document.getElementById('supplier_name');
    const supplierDropdown = document.getElementById('supplier_dropdown');

    supplierInput.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        supplierDropdown.innerHTML = '';
        
        if (!val) {
            supplierDropdown.classList.add('hidden');
            return;
        }

        const uniqueSuppliers = [...new Set(suppliers.map(s => s.name))];
        const matches = uniqueSuppliers.filter(name => name.toLowerCase().includes(val));
        
        if (matches.length > 0) {
            supplierDropdown.classList.remove('hidden');
            matches.forEach(match => {
                const div = document.createElement('div');
                div.className = 'cursor-pointer p-2 hover:bg-slate-100 text-slate-800 text-xs';
                div.innerText = match;
                div.onclick = () => {
                    supplierInput.value = match;
                    supplierDropdown.classList.add('hidden');
                };
                dropdown.appendChild(div);
            });
        } else {
            supplierDropdown.classList.remove('hidden');
        }

        const createSupplierDiv = document.createElement('div');
        createSupplierDiv.className = 'cursor-pointer p-2 bg-teal-50 text-teal-800 font-bold hover:bg-teal-600 hover:text-white text-xs transition';
        createSupplierDiv.innerText = '+ Create New Supplier: "' + this.value + '"';
        createSupplierDiv.onclick = () => {
            supplierDropdown.classList.add('hidden');
        };
        supplierDropdown.appendChild(createSupplierDiv);
    });

    document.addEventListener('click', function(e) {
        if (!inputName.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
        if (!supplierInput.contains(e.target) && !supplierDropdown.contains(e.target)) {
            supplierDropdown.classList.add('hidden');
        }
    });

    function autoFillPricing(name) {
        const mat = materials.find(m => m.material_name === name);
        if (mat) {
            document.getElementById('fixed_size').value = mat.fixed_size || '';
            document.getElementById('purchase_price').value = mat.purchase_price;
            document.getElementById('retail_price').value = mat.retail_price;
            
            if (mat.supplier && mat.supplier.name) {
                document.getElementById('supplier_name').value = mat.supplier.name;
            }

            const container = document.getElementById('wholesale-container');
            container.innerHTML = '';
            
            if (mat.wholesale_prices && mat.wholesale_prices.length > 0) {
                mat.wholesale_prices.forEach((tier) => {
                    addWholesaleTier(tier.min_qty, tier.wholesale_price);
                });
            } else {
                addWholesaleTier(); 
            }
        }
    }

    let tierIndex = 1; 
    function addWholesaleTier(minQty = '', price = '') {
        const container = document.getElementById('wholesale-container');
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2 wholesale-row';
        row.innerHTML = `
            <div class="flex-1">
                <input type="number" name="wholesale[${tierIndex}][min_qty]" value="${minQty}" min="1" class="form-control form-control-sm" placeholder="Min Qty (e.g. 10)">
            </div>
            <div class="flex-1">
                <input type="number" name="wholesale[${tierIndex}][price]" value="${price}" step="0.01" min="0" class="form-control form-control-sm" placeholder="Wholesale Price (Rp)">
            </div>
            <button type="button" onclick="this.closest('.wholesale-row').remove()" class="btn btn-sm btn-outline-danger py-0 px-2">
                <i class="fa-solid fa-trash text-xs"></i>
            </button>
        `;
        container.appendChild(row);
        tierIndex++;
    }
</script>
@endsection
