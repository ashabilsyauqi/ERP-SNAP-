@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in max-w-4xl mx-auto">
    <!-- Header Section -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Tambah Pembelian</h2>
            <p class="text-sm text-slate-500">Add stock and record a new purchase from a supplier</p>
        </div>
        <a href="{{ route('purchasing.index') }}" class="bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 font-semibold py-2 px-4 rounded-xl transition shadow-sm text-sm flex items-center gap-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </a>
    </div>

    <!-- Form Section -->
    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-200/60">
        <form action="{{ route('purchasing.store') }}" method="POST" id="purchase-form" class="space-y-6">
            @csrf
            
            @if(auth()->user()->isOwner())
            <div>
                <label for="branch_id" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Cabang Tujuan</label>
                <div class="relative">
                    <select name="branch_id" id="branch_id" required
                        class="block w-full rounded-xl border border-slate-200 bg-white py-3 px-4 text-sm text-slate-800 transition duration-200 outline-none focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (request('branch_id') == $branch->id || auth()->user()->branch_id == $branch->id) ? 'selected' : '' }}>
                                {{ $branch->nama_cabang }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Autocomplete Dropdown for Material Name -->
                <div class="relative">
                    <label for="material_name" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama Barang / Material</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <input type="text" id="material_name" name="material_name" required autocomplete="off"
                            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 pl-10 pr-4 text-sm text-slate-800 placeholder-slate-400 transition duration-200 outline-none focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"
                            placeholder="Type to search or create new...">
                    </div>
                    
                    <div id="material_dropdown" class="hidden absolute z-30 mt-1 w-full bg-white shadow-xl max-h-60 rounded-xl border border-slate-200/80 py-0 overflow-auto focus:outline-none text-sm divide-y divide-slate-100">
                        <!-- Options injected by JS -->
                    </div>
                </div>

                <!-- Autocomplete Dropdown for Supplier Name -->
                <div class="relative">
                    <label for="supplier_name" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Supplier</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <input type="text" id="supplier_name" name="supplier_name" autocomplete="off"
                            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 pl-10 pr-4 text-sm text-slate-800 placeholder-slate-400 transition duration-200 outline-none focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"
                            placeholder="Ketik supplier atau buat baru...">
                    </div>
                    
                    <div id="supplier_dropdown" class="hidden absolute z-30 mt-1 w-full bg-white shadow-xl max-h-60 rounded-xl border border-slate-200/80 py-0 overflow-auto focus:outline-none text-sm divide-y divide-slate-100">
                        <!-- Options injected by JS -->
                    </div>
                </div>
            </div>

            <!-- Vendor Invoice Ref (SAP Standard) -->
            <div>
                <label for="vendor_ref" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">No. Faktur / Referensi Nota Supplier (Optional)</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <input type="text" id="vendor_ref" name="vendor_ref"
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 pl-10 pr-4 text-sm text-slate-800 placeholder-slate-400 transition duration-200 outline-none focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10" 
                        placeholder="Contoh: INV-BT/2026/0892">
                </div>
            </div>

            <div>
                <label for="fixed_size" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Fixed Size (Meters) - Optional</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </div>
                    <input type="number" id="fixed_size" name="fixed_size" step="0.01" min="0" 
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 pl-10 pr-4 text-sm text-slate-800 placeholder-slate-400 transition duration-200 outline-none focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10" 
                        placeholder="e.g. 3.00 for 3m Banner">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="qty_bought" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Quantity</label>
                    <input type="number" id="qty_bought" name="qty_bought" min="1" required 
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 px-4 text-sm text-slate-800 transition duration-200 outline-none focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10">
                </div>
                <div>
                    <label for="purchase_price" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Harga Beli (HPP)</label>
                    <input type="number" id="purchase_price" name="purchase_price" step="0.01" min="0" required 
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 px-4 text-sm text-slate-800 transition duration-200 outline-none focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10">
                </div>
                <div>
                    <label for="retail_price" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Harga Jual (Retail)</label>
                    <input type="number" id="retail_price" name="retail_price" step="0.01" min="0" required 
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 px-4 text-sm text-slate-800 transition duration-200 outline-none focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10">
                </div>
            </div>

            <!-- Wholesale Tiers Section -->
            <div class="p-4 border border-indigo-100 rounded-2xl bg-indigo-50/40">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold text-indigo-900 uppercase tracking-wider">Wholesale Prices</span>
                        <span class="text-[10px] text-indigo-500 font-medium">(Harga Grosir)</span>
                    </div>
                    <button type="button" onclick="addWholesaleTier()" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold py-1.5 px-3 rounded-lg flex items-center gap-1 shadow-sm transition duration-150 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Tier
                    </button>
                </div>
                
                <div id="wholesale-container" class="space-y-3">
                    <!-- Default First Tier -->
                    <div class="flex items-center gap-3 wholesale-row animate-fade-in">
                        <div class="w-1/2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Min Qty</label>
                            <input type="number" name="wholesale[0][min_qty]" min="1" class="block w-full px-3 py-2 border border-slate-200 bg-white rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/15 focus:border-indigo-500" placeholder="e.g. 10">
                        </div>
                        <div class="w-1/2 flex items-end gap-2">
                            <div class="flex-grow">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Wholesale Price</label>
                                <input type="number" name="wholesale[0][price]" step="0.01" min="0" class="block w-full px-3 py-2 border border-slate-200 bg-white rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/15 focus:border-indigo-500" placeholder="e.g. 40000">
                            </div>
                            <button type="button" onclick="this.closest('.wholesale-row').remove()" class="mb-1 text-slate-400 hover:text-rose-500 p-2 rounded-lg hover:bg-rose-50 transition duration-150">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-indigo-600 text-white font-semibold py-3 px-8 rounded-xl hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-150 shadow-lg shadow-indigo-600/15 w-full sm:w-auto cursor-pointer">
                    Simpan Pembelian
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- Data Preload ---
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

        const uniqueNames = [...new Set(materials.map(m => m.material_name))];
        const matches = uniqueNames.filter(name => name.toLowerCase().includes(val));
        
        if (matches.length > 0) {
            dropdown.classList.remove('hidden');
            matches.forEach(match => {
                const div = document.createElement('div');
                div.className = 'cursor-pointer select-none relative py-2.5 pl-4 pr-8 hover:bg-indigo-600 hover:text-white text-slate-700 border-b border-slate-100 text-sm first:rounded-t-xl transition duration-150';
                div.innerText = match;
                div.onclick = () => {
                    inputName.value = match;
                    dropdown.classList.add('hidden');
                    autoFillPricing(match);
                };
                dropdown.appendChild(div);
            });
        } else {
            dropdown.classList.remove('hidden');
        }

        const createDiv = document.createElement('div');
        createDiv.className = 'cursor-pointer select-none relative py-2.5 pl-4 pr-8 bg-indigo-50 text-indigo-700 font-bold hover:bg-indigo-600 hover:text-white text-sm last:rounded-b-xl transition duration-150';
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
                div.className = 'cursor-pointer select-none relative py-2.5 pl-4 pr-8 hover:bg-indigo-600 hover:text-white text-slate-700 border-b border-slate-100 text-sm first:rounded-t-xl transition duration-150';
                div.innerText = match;
                div.onclick = () => {
                    supplierInput.value = match;
                    supplierDropdown.classList.add('hidden');
                };
                supplierDropdown.appendChild(div);
            });
        } else {
            supplierDropdown.classList.remove('hidden');
        }

        const createSupplierDiv = document.createElement('div');
        createSupplierDiv.className = 'cursor-pointer select-none relative py-2.5 pl-4 pr-8 bg-indigo-50 text-indigo-700 font-bold hover:bg-indigo-600 hover:text-white text-sm last:rounded-b-xl transition duration-150';
        createSupplierDiv.innerText = '+ Create New Supplier: "' + this.value + '"';
        createSupplierDiv.onclick = () => {
            supplierDropdown.classList.add('hidden');
        };
        supplierDropdown.appendChild(createSupplierDiv);
    });

    // Hide dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!inputName.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
        if (!supplierInput.contains(e.target) && !supplierDropdown.contains(e.target)) {
            supplierDropdown.classList.add('hidden');
        }
    });

    // Auto-fill existing prices & supplier
    function autoFillPricing(name) {
        const mat = materials.find(m => m.material_name === name);
        if (mat) {
            document.getElementById('fixed_size').value = mat.fixed_size || '';
            document.getElementById('purchase_price').value = mat.purchase_price;
            document.getElementById('retail_price').value = mat.retail_price;
            
            // Auto-fill Supplier Name if associated with this material
            if (mat.supplier && mat.supplier.name) {
                document.getElementById('supplier_name').value = mat.supplier.name;
            }

            const container = document.getElementById('wholesale-container');
            container.innerHTML = '';
            
            if (mat.wholesale_prices && mat.wholesale_prices.length > 0) {
                mat.wholesale_prices.forEach((tier) => {
                    addWholesaleTier(tier.min_qty, tier.wholesale_price);
                });
            } else if (mat.wholesale_prices_raw && mat.wholesale_prices_raw.length > 0) {
                mat.wholesale_prices_raw.forEach((tier) => {
                    addWholesaleTier(tier.min_qty, tier.wholesale_price);
                });
            } else {
                addWholesaleTier(); 
            }
        }
    }

    // Dynamic Wholesale Tiers
    let tierIndex = 1; 
    function addWholesaleTier(minQty = '', price = '') {
        const container = document.getElementById('wholesale-container');
        const row = document.createElement('div');
        row.className = 'flex items-center gap-3 wholesale-row animate-fade-in';
        row.innerHTML = `
            <div class="w-1/2">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Min Qty</label>
                <input type="number" name="wholesale[${tierIndex}][min_qty]" value="${minQty}" min="1" class="block w-full px-3 py-2 border border-slate-200 bg-white rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/15 focus:border-indigo-500" placeholder="e.g. 10">
            </div>
            <div class="w-1/2 flex items-end gap-2">
                <div class="flex-grow">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Wholesale Price</label>
                    <input type="number" name="wholesale[${tierIndex}][price]" value="${price}" step="0.01" min="0" class="block w-full px-3 py-2 border border-slate-200 bg-white rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/15 focus:border-indigo-500" placeholder="e.g. 40000">
                </div>
                <button type="button" onclick="this.closest('.wholesale-row').remove()" class="mb-1 text-slate-400 hover:text-rose-500 p-2 rounded-lg hover:bg-rose-50 transition duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        `;
        container.appendChild(row);
        tierIndex++;
    }

    materials.forEach(m => {
        if (m.wholesale_prices) {
            m.wholesale_prices_raw = m.wholesale_prices;
        } else if (m.wholesale_prices === undefined && m.wholesale_price_records === undefined) {
            m.wholesale_prices = m.wholesale_prices || m.wholesale_price_records || [];
        }
    });
</script>
@endsection
