@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Purchasing & Master Data</h2>
            <p class="text-sm text-slate-500">Manage stock inventory, purchase history, and suppliers</p>
        </div>
        <div class="flex items-center gap-4">
            @if(auth()->user()->isOwner())
            <form action="{{ route('purchasing.index') }}" method="GET" class="hidden sm:block">
                <select name="branch_id" onchange="this.form.submit()" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block w-full py-2.5 px-3">
                    <option value="all" {{ request('branch_id') == 'all' ? 'selected' : '' }}>Semua Cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->nama_cabang }}
                        </option>
                    @endforeach
                </select>
            </form>
            @endif

            <a href="{{ route('purchasing.create') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 px-5 rounded-xl transition duration-150 shadow-sm flex items-center gap-2 cursor-pointer w-full sm:w-auto justify-center text-sm">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Pembelian
            </a>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden flex flex-col">
        
        <!-- Tab Navigation & Filters -->
        <div class="p-5 border-b border-slate-200/80 bg-white flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            
            <!-- Tabs -->
            <div class="flex gap-1.5 p-1 bg-slate-100 rounded-xl overflow-x-auto w-full md:w-auto hide-scrollbar">
                <button onclick="switchMainTab('inventory')" id="tab-inventory" class="px-5 py-2 font-semibold text-xs rounded-lg transition duration-200 bg-white text-slate-800 shadow-sm whitespace-nowrap">Master Barang</button>
                <button onclick="switchMainTab('history')" id="tab-history" class="px-5 py-2 font-semibold text-xs rounded-lg transition duration-200 text-slate-500 hover:text-slate-800 whitespace-nowrap">Riwayat Pembelian</button>
                <button onclick="switchMainTab('supplier')" id="tab-supplier" class="px-5 py-2 font-semibold text-xs rounded-lg transition duration-200 text-slate-500 hover:text-slate-800 whitespace-nowrap">Data Supplier</button>
            </div>
            
            <!-- Global Search -->
            <div class="relative w-full md:w-64">
                <input type="text" id="global-search" onkeyup="filterActiveTable()" placeholder="Cari data..." 
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/15 focus:border-indigo-500 text-xs transition duration-150">
                <div class="absolute left-3.5 top-3 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- 1. Master Barang (Inventory) -->
        <div id="view-inventory" class="overflow-x-auto tab-view animate-fade-in">
            <table class="min-w-full divide-y divide-slate-200 search-table">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Material Name</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Cabang</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Size</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">HPP (Modal)</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Retail Price</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Stock Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($materials as $material)
                        <tr class="hover:bg-slate-50/30 transition duration-150 search-row">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400 font-medium">#{{ $material->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-800 search-target">{{ $material->material_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium search-target">{{ $material->branch->nama_cabang ?? 'Global' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium">{{ $material->fixed_size ? $material->fixed_size . 'm' : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">Rp {{ number_format($material->purchase_price, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 font-bold">Rp {{ number_format($material->retail_price, 0, ',', '.') }}</td>
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
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-400">Belum ada data barang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 2. Riwayat Pembelian -->
        <div id="view-history" class="overflow-x-auto tab-view hidden">
            <table class="min-w-full divide-y divide-slate-200 search-table">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Tanggal</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Barang</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Cabang</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Supplier</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Qty</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Total Harga</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Diinput Oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($purchases as $purchase)
                        <tr class="hover:bg-slate-50/30 transition duration-150 search-row">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium">{{ $purchase->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-800 search-target">{{ $purchase->material->material_name ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium search-target">{{ $purchase->branch->nama_cabang ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 search-target">
                                @if($purchase->supplier)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-600">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        {{ $purchase->supplier->name }}
                                    </span>
                                @else
                                    <span class="text-slate-300 italic">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium">{{ $purchase->qty_bought }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 font-bold">Rp {{ number_format($purchase->total_cost, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400 font-medium">{{ $purchase->user->name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-400">Belum ada riwayat pembelian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 3. Data Supplier -->
        <div id="view-supplier" class="overflow-x-auto tab-view hidden">
            <table class="min-w-full divide-y divide-slate-200 search-table">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">ID Supplier</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Nama Supplier</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Tanggal Bergabung</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-slate-50/30 transition duration-150 search-row">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400 font-medium">SUP-{{ str_pad($supplier->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-800 search-target">{{ $supplier->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium">{{ $supplier->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-sm text-slate-400">Belum ada data supplier.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* Hide scrollbar for tabs */
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<script>
    let activeTabId = 'inventory';

    function switchMainTab(tab) {
        activeTabId = tab;
        
        // Hide all views
        document.querySelectorAll('.tab-view').forEach(el => el.classList.add('hidden'));
        
        // Reset all tabs UI
        const activeClass = "px-5 py-2 font-semibold text-xs rounded-lg transition duration-200 bg-white text-slate-800 shadow-sm whitespace-nowrap".split(" ");
        const inactiveClass = "px-5 py-2 font-semibold text-xs rounded-lg transition duration-200 text-slate-500 hover:text-slate-800 whitespace-nowrap".split(" ");
        
        ['inventory', 'history', 'supplier'].forEach(t => {
            const btn = document.getElementById('tab-' + t);
            btn.className = '';
            if (t === tab) {
                btn.classList.add(...activeClass);
                document.getElementById('view-' + t).classList.remove('hidden');
                document.getElementById('view-' + t).classList.add('animate-fade-in');
            } else {
                btn.classList.add(...inactiveClass);
            }
        });

        // Re-apply filter based on current tab
        filterActiveTable();
    }

    function filterActiveTable() {
        const input = document.getElementById('global-search').value.toLowerCase();
        
        // Get only the rows inside the active tab
        const activeView = document.getElementById('view-' + activeTabId);
        if(!activeView) return;

        const rows = activeView.querySelectorAll('.search-row');

        rows.forEach(row => {
            // Find all elements with class 'search-target' inside this row to match against
            const targets = row.querySelectorAll('.search-target');
            let matched = false;
            
            targets.forEach(target => {
                if (target.innerText.toLowerCase().includes(input)) {
                    matched = true;
                }
            });

            // Fallback: If no .search-target defined, just search all text content in the row
            if(targets.length === 0) {
                if (row.innerText.toLowerCase().includes(input)) matched = true;
            }

            if (matched || input === '') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
@endsection
