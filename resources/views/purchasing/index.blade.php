@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header Section -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Purchasing & Materials</h2>
            <p class="text-sm text-slate-500">Manage stock inventory, add new materials, and configure wholesale tiers</p>
        </div>
    </div>

    <!-- 2-Column Desktop Workspace -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Column: Add Stock / New Material Form (lg:col-span-5) -->
        <div class="lg:col-span-5 bg-white p-6 rounded-2xl shadow-sm border border-slate-200/60">
            <div class="flex items-center gap-2 mb-6">
                <div class="p-2 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Add Stock / New Material</h3>
            </div>
            
            <form action="{{ route('purchasing.store') }}" method="POST" id="purchase-form" class="space-y-6">
                @csrf
                
                <!-- Autocomplete Dropdown for Material Name -->
                <div class="relative">
                    <label for="material_name" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Material Name</label>
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
                        <label for="purchase_price" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Purchase HPP</label>
                        <input type="number" id="purchase_price" name="purchase_price" step="0.01" min="0" required 
                            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 px-4 text-sm text-slate-800 transition duration-200 outline-none focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10">
                    </div>
                    <div>
                        <label for="retail_price" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Retail Price</label>
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

                <div class="flex justify-end pt-2">
                    <button type="submit" class="bg-indigo-600 text-white font-semibold py-3 px-6 rounded-xl hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-150 shadow-lg shadow-indigo-600/15 w-full sm:w-auto cursor-pointer">
                        Save Material & Stock
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Column: Current Inventory Table (lg:col-span-7) -->
        <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden flex flex-col">
            
            <div class="p-5 border-b border-slate-200/80 bg-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <!-- Tabs -->
                <div class="flex gap-1.5 p-1 bg-slate-100 rounded-xl">
                    <button onclick="switchTab('general')" id="tab-general" class="px-4 py-2 font-semibold text-xs rounded-lg transition duration-200 bg-white text-slate-800 shadow-sm cursor-pointer">General Inventory</button>
                    <button onclick="switchTab('opex')" id="tab-opex" class="px-4 py-2 font-semibold text-xs rounded-lg transition duration-200 text-slate-500 hover:text-slate-855 cursor-pointer">OPEX Log (Tinta)</button>
                </div>
                
                <!-- Search Bar with Search Lens Icon -->
                <div class="relative w-full sm:w-60">
                    <input type="text" id="table-search" onkeyup="filterTable()" placeholder="Search items..." 
                        class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/15 focus:border-indigo-500 text-xs transition duration-150">
                    <div class="absolute left-3.5 top-2.5 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200" id="inventory-table">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Material Name</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Size</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">HPP</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Retail</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Wholesale Tiers</th>
                            <th scope="col" class="px-6 py-4 class-left text-xs font-bold text-slate-500 uppercase tracking-wider">Stock Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white" id="inventory-body">
                        @foreach($materials as $material)
                            @php
                                $isOpex = stripos($material->material_name, 'tinta') !== false;
                                $rowClass = $isOpex ? 'opex-row hidden' : 'general-row';
                            @endphp
                            <tr class="{{ $rowClass }} hover:bg-slate-50/30 transition duration-150 material-row">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400 font-medium">#{{ $material->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-800 material-name-col">{{ $material->material_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium">{{ $material->fixed_size ? $material->fixed_size . 'm' : '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">Rp {{ number_format($material->purchase_price, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 font-bold">Rp {{ number_format($material->retail_price, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-sm text-slate-500 font-medium">
                                    @if($material->wholesalePrices->count() > 0)
                                        <ul class="list-none space-y-1 text-xs">
                                            @foreach($material->wholesalePrices as $wp)
                                                <li>
                                                    <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">&ge; {{ $wp->min_qty }}</span>
                                                    <span class="font-bold text-slate-800">Rp {{ number_format($wp->wholesale_price, 0, ',', '.') }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-slate-400 italic text-xs">None</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($material->stock_qty > 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            {{ $material->stock_qty }} left
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-100">
                                            Out of stock
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // --- Data Preload ---
    const materials = @json($materials);
    
    // --- Autocomplete Logic ---
    const inputName = document.getElementById('material_name');
    const dropdown = document.getElementById('material_dropdown');
    
    inputName.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        dropdown.innerHTML = '';
        
        if (!val) {
            dropdown.classList.add('hidden');
            return;
        }

        // Filter unique names
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

        // Add create new option
        const createDiv = document.createElement('div');
        createDiv.className = 'cursor-pointer select-none relative py-2.5 pl-4 pr-8 bg-indigo-50 text-indigo-700 font-bold hover:bg-indigo-600 hover:text-white text-sm last:rounded-b-xl transition duration-150';
        createDiv.innerText = '+ Create New Material: "' + this.value + '"';
        createDiv.onclick = () => {
            dropdown.classList.add('hidden');
            // Allow them to type fresh prices
            document.getElementById('fixed_size').value = '';
            document.getElementById('purchase_price').value = '';
            document.getElementById('retail_price').value = '';
            document.getElementById('wholesale-container').innerHTML = '';
            addWholesaleTier();
        };
        dropdown.appendChild(createDiv);
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!inputName.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // Auto-fill existing prices if user selects an existing material
    function autoFillPricing(name) {
        const mat = materials.find(m => m.material_name === name);
        if (mat) {
            document.getElementById('fixed_size').value = mat.fixed_size || '';
            document.getElementById('purchase_price').value = mat.purchase_price;
            document.getElementById('retail_price').value = mat.retail_price;
            
            // Reconstruct wholesale tiers
            const container = document.getElementById('wholesale-container');
            container.innerHTML = '';
            
            if (mat.wholesale_prices && mat.wholesale_prices.length > 0) {
                mat.wholesale_prices.forEach((tier, index) => {
                    addWholesaleTier(tier.min_qty, tier.wholesale_price);
                });
            } else if (mat.wholesale_prices_raw && mat.wholesale_prices_raw.length > 0) {
                // fallback check if standard wholesale_prices relation name is formatted raw
                mat.wholesale_prices_raw.forEach((tier, index) => {
                    addWholesaleTier(tier.min_qty, tier.wholesale_price);
                });
            } else {
                addWholesaleTier(); // add one empty row
            }
        }
    }

    // --- Dynamic Wholesale Tiers ---
    let tierIndex = 1; // start at 1 because 0 is hardcoded in HTML
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

    // --- Tab and Live Search Logic ---
    let currentTab = 'general';

    function switchTab(tab) {
        currentTab = tab;
        const btnGen = document.getElementById('tab-general');
        const btnOpex = document.getElementById('tab-opex');

        if (tab === 'general') {
            btnGen.className = "px-4 py-2 font-semibold text-xs rounded-lg transition duration-200 bg-white text-slate-800 shadow-sm cursor-pointer";
            btnOpex.className = "px-4 py-2 font-semibold text-xs rounded-lg transition duration-200 text-slate-500 hover:text-slate-800 cursor-pointer";
        } else {
            btnOpex.className = "px-4 py-2 font-semibold text-xs rounded-lg transition duration-200 bg-white text-slate-800 shadow-sm cursor-pointer";
            btnGen.className = "px-4 py-2 font-semibold text-xs rounded-lg transition duration-200 text-slate-500 hover:text-slate-800 cursor-pointer";
        }

        filterTable(); // Re-apply filter and visibility rules
    }

    function filterTable() {
        const input = document.getElementById('table-search').value.toLowerCase();
        const rows = document.querySelectorAll('#inventory-body tr');

        rows.forEach(row => {
            const isOpexRow = row.classList.contains('opex-row');
            const name = row.querySelector('.material-name-col').innerText.toLowerCase();
            const matchesSearch = name.includes(input);

            // Determine if row belongs to current tab
            const belongsToTab = (currentTab === 'general' && !isOpexRow) || (currentTab === 'opex' && isOpexRow);

            if (belongsToTab && matchesSearch) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    }

    // Intercept autocomplete filling when materials preloads
    // Add extra hook to material preload wholesale details
    materials.forEach(m => {
        // map relation wholesalePrices to simple wholesale_prices for autofill compatibility
        if (m.wholesale_prices) {
            m.wholesale_prices_raw = m.wholesale_prices;
        } else if (m.wholesale_prices === undefined && m.wholesale_price_records === undefined) {
            // map from relationship if available
            m.wholesale_prices = m.wholesale_prices || m.wholesale_price_records || [];
        }
    });
</script>
@endsection
