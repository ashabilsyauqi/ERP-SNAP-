@extends('layouts.app')

@section('title', 'Point of Sale (POS)')
@section('page-title', 'Terminal Kasir Penjualan (POS)')

@section('action-buttons')
@if(!auth()->user()->isOperator())
<button type="button" onclick="openDraftOrdersModal()" class="btn-odoo-secondary text-decoration-none position-relative">
    <i class="fa-solid fa-inbox me-1 text-amber-600"></i> Pesanan Draft
    <span id="draft-counter-badge" class="badge bg-amber-600 text-white rounded-pill text-[10px] ms-1">0</span>
</button>
@endif
<a href="{{ route('sales.receivables') }}" class="btn-odoo-primary text-decoration-none">
    <i class="fa-solid fa-hand-holding-dollar me-1"></i> Piutang & Pesanan DP
</a>
<a href="{{ route('sales.index') }}" class="btn-odoo-secondary text-decoration-none">
    <i class="fa-solid fa-receipt me-1 text-blue-600"></i> Riwayat Penjualan
</a>
@endsection

@section('content')
<div class="flex flex-col lg:flex-row gap-3 h-[calc(100vh-95px)] w-full max-w-full animate-fade-in relative pb-16 lg:pb-0 overflow-hidden" 
     id="pos-main-container"
     x-data="{ 
        activeTab: 'catalog', 
        isDp: false, 
        cartTotal: 0,
        cartItemCount: 0,
        minDpThreshold: 500000,
        customerId: null,
        customerName: '', 
        customerPhone: '', 
        customerEmail: '',
        customersList: {{ Js::from($customers ?? []) }},
        showCustomerDropdown: false,
        dueDate: '', 
        dpAmount: 0, 
        productionNotes: '',
        showToast: false,
        toastItemName: '',
        selectExistingCustomer(cust) {
            this.customerId = cust.id;
            this.customerName = cust.name;
            this.customerPhone = cust.phone || '';
            this.customerEmail = cust.email || '';
            this.showCustomerDropdown = false;
        },
        clearCustomer() {
            this.customerId = null;
            this.customerName = '';
            this.customerPhone = '';
            this.customerEmail = '';
            this.showCustomerDropdown = false;
        },
        get filteredCustomers() {
            if (!this.customerName || this.customerName.trim() === '') return this.customersList.slice(0, 10);
            const q = this.customerName.toLowerCase();
            return this.customersList.filter(c => 
                (c.name && c.name.toLowerCase().includes(q)) || 
                (c.phone && c.phone.includes(q)) ||
                (c.email && c.email.toLowerCase().includes(q))
            ).slice(0, 8);
        },
        notifyItemAdded(name) {
            this.toastItemName = name;
            this.showToast = true;
            setTimeout(() => { this.showToast = false; }, 1800);
        },
        get minDpAmount() {
            return Math.round((Number(this.cartTotal) || 0) * 0.5);
        },
        get isEligibleForDp() {
            return Number(this.cartTotal) >= this.minDpThreshold;
        },
        toggleDp(checked) {
            this.isDp = checked;
            if (checked && (!this.dpAmount || this.dpAmount < this.minDpAmount)) {
                this.dpAmount = this.minDpAmount;
            }
        },
        setDpPercent(pct) {
            if (pct < 50) pct = 50;
            const total = Number(this.cartTotal) || 0;
            this.dpAmount = Math.round(total * (pct / 100));
        },
        handleCartTotalUpdate(total, count) {
            this.cartTotal = Number(total) || 0;
            this.cartItemCount = Number(count) || 0;
            if (this.cartTotal < this.minDpThreshold) {
                this.isDp = false;
                this.dpAmount = 0;
            } else if (this.isDp && (!this.dpAmount || this.dpAmount < this.minDpAmount)) {
                this.dpAmount = this.minDpAmount;
            }
        }
     }"
     @cart-total-changed.window="handleCartTotalUpdate($event.detail.total, $event.detail.count)"
     @item-added-to-cart.window="notifyItemAdded($event.detail.name)">
    
    <!-- Mobile & Tablet Segmented Tab Switcher (Visible on screens < 1024px) -->
    <div class="lg:hidden flex items-center bg-slate-200/90 p-1 rounded-2xl gap-1 flex-shrink-0 shadow-inner w-full">
        <button type="button" 
                @click="activeTab = 'catalog'" 
                :class="activeTab === 'catalog' ? 'bg-white text-blue-600 shadow font-bold' : 'text-slate-600 font-semibold hover:text-slate-900'"
                class="flex-1 py-2 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition duration-150 border-0 cursor-pointer">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span>1. Katalog Produk</span>
            <span class="bg-blue-100 text-blue-800 text-[10px] px-1.5 py-0.2 rounded-full font-mono">{{ $materials->count() }}</span>
        </button>
        <button type="button" 
                @click="activeTab = 'cart'" 
                :class="activeTab === 'cart' ? 'bg-blue-600 text-white shadow font-bold' : 'text-slate-600 font-semibold hover:text-slate-900'"
                class="flex-1 py-2 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition duration-150 border-0 cursor-pointer relative">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>2. Keranjang</span>
            <span :class="activeTab === 'cart' ? 'bg-white text-blue-700' : 'bg-blue-600 text-white'" class="text-[10px] font-bold px-2 py-0.2 rounded-full font-mono" x-text="cartItemCount + ' item'">0 item</span>
            <span class="font-mono font-bold text-[11px] ms-1" x-text="'(Rp ' + Number(cartTotal).toLocaleString('id-ID') + ')'"></span>
        </button>
    </div>

    <!-- Left Column: Products Grid & Search (min-w-0 ensures no flex blowout) -->
    <div class="flex-1 min-w-0 flex flex-col gap-2.5 min-h-0 h-full overflow-hidden"
         :class="activeTab === 'catalog' ? 'flex' : 'hidden lg:flex'">
        
        <!-- Products Header & Search (Compact Clean Bar) -->
        <div class="bg-white px-3.5 py-2.5 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-2.5 flex-shrink-0 w-full">
            <div class="flex items-center gap-2 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-cash-register text-sm"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold text-slate-900 mb-0 truncate">Katalog Produk & Layanan Kasir</h2>
                    <p class="text-[11px] text-slate-500 mb-0 truncate">Klik kartu untuk memasukkan ke keranjang kasir</p>
                </div>
            </div>
            
            <!-- Live Search Products -->
            <div class="relative w-full sm:w-64 md:w-72 flex-shrink-0">
                <input type="text" id="product-search" onkeyup="filterProducts()" placeholder="Cari produk / layanan..." 
                    class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 text-xs transition duration-150">
                <div class="absolute left-3 top-2.5 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
            </div>
        </div>

        <!-- Categories Pill Filter Tabs (Touch Friendly, Horizontal Scroll) -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 flex-shrink-0 no-scrollbar w-full" id="category-filter-container">
            <button type="button" 
                    onclick="filterCategory('all', this)" 
                    class="category-filter-btn px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-150 border bg-blue-600 text-white border-blue-600 shadow-sm cursor-pointer flex items-center gap-1.5 active"
                    data-cat="all">
                <i class="fa-solid fa-layer-group text-[11px]"></i>
                <span>Semua</span>
                <span class="bg-white/20 text-white text-[10px] px-1.5 py-0.2 rounded-full font-mono">{{ $materials->count() }}</span>
            </button>

            @php
                $catIcons = [
                    'Print Dokumen dan Sticker' => ['icon' => 'fa-file-lines', 'color' => 'text-sky-500'],
                    'Cetak Outdoor dan Indoor' => ['icon' => 'fa-panorama', 'color' => 'text-amber-500'],
                    'Finishing' => ['icon' => 'fa-scissors', 'color' => 'text-indigo-500'],
                    'Merchandise Custom' => ['icon' => 'fa-gift', 'color' => 'text-rose-500'],
                    'Stampel' => ['icon' => 'fa-stamp', 'color' => 'text-purple-500'],
                    'Nota' => ['icon' => 'fa-receipt', 'color' => 'text-blue-500'],
                    'Brosur' => ['icon' => 'fa-newspaper', 'color' => 'text-emerald-500'],
                    'Tumbler' => ['icon' => 'fa-mug-hot', 'color' => 'text-teal-500'],
                ];
            @endphp

            @foreach($categories as $cat)
                @php
                    $catCount = $materials->where('category', $cat)->count();
                    $info = $catIcons[$cat] ?? ['icon' => 'fa-tag', 'color' => 'text-slate-500'];
                @endphp
                <button type="button" 
                        onclick="filterCategory('{{ addslashes($cat) }}', this)" 
                        class="category-filter-btn px-3 py-1.5 rounded-xl text-xs font-medium whitespace-nowrap transition-all duration-150 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 shadow-sm cursor-pointer flex items-center gap-1.5"
                        data-cat="{{ $cat }}">
                    <i class="fa-solid {{ $info['icon'] }} {{ $info['color'] }} text-[11px]"></i>
                    <span>{{ $cat }}</span>
                    <span class="bg-slate-100 text-slate-600 text-[10px] px-1.5 py-0.2 rounded-full font-mono">{{ $catCount }}</span>
                </button>
            @endforeach
        </div>
        
        <!-- Products Cards Grid (Fluid Responsive Auto-Fill Flex/Grid, Smooth Vertical Scroll) -->
        <div id="products-grid" class="grid grid-cols-[repeat(auto-fill,minmax(250px,1fr))] gap-3 overflow-y-auto pr-1 pb-2 flex-grow min-h-0 w-full content-start">
            @foreach($materials as $material)
                <div class="product-card bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-500 transition duration-150 cursor-pointer flex flex-col justify-between group min-w-[230px]"
                     data-id="{{ $material->id }}"
                     data-name="{{ strtolower($material->material_name) }}"
                     data-category="{{ $material->category ?? 'Lainnya' }}"
                     onclick="onSelectProduct({{ $material->id }})">
                    
                    <div class="min-w-0">
                        <div class="flex justify-between items-start mb-2 gap-1.5">
                            @php
                                $badgeStyle = match($material->category) {
                                    'Print Dokumen dan Sticker' => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'Cetak Outdoor dan Indoor' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'Finishing' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'Merchandise Custom' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    'Stampel' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'Nota' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'Brosur' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'Tumbler' => 'bg-teal-50 text-teal-700 border-teal-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200'
                                };
                            @endphp
                            <span class="badge {{ $badgeStyle }} border text-[11px] font-semibold px-2.5 py-0.5 rounded-lg truncate max-w-[140px]" title="{{ $material->category ?? 'Bahan' }}">
                                {{ $material->category ?? 'Bahan' }}
                            </span>
                            <span class="text-xs {{ $material->stock_qty > 10 ? 'text-emerald-600' : ($material->stock_qty > 0 ? 'text-amber-600' : 'text-rose-600') }} font-bold flex-shrink-0">
                                {{ $material->stock_qty }} pcs
                            </span>
                        </div>
                        
                        <h3 class="font-bold text-slate-900 text-sm line-clamp-2 mb-1.5 group-hover:text-blue-600 transition leading-snug">
                            {{ $material->material_name }}
                        </h3>
                        
                        @if($material->fixed_size)
                            <div class="text-xs text-slate-500 mb-1 truncate">
                                Lebar Roll: <strong class="text-slate-700 font-semibold">{{ $material->fixed_size }} m</strong>
                            </div>
                        @endif

                        @if($material->wholesalePrices->count() > 0)
                            <div class="text-xs text-blue-600 font-semibold mb-1 truncate">
                                <i class="fa-solid fa-tags me-1"></i>{{ $material->wholesalePrices->count() }} Tier Grosir
                            </div>
                        @endif
                    </div>

                    <div class="pt-2.5 border-t border-slate-100 flex justify-between items-center mt-3">
                        <div class="min-w-0">
                            <span class="text-[10px] text-slate-400 block leading-tight font-semibold uppercase tracking-wider">Harga Satuan</span>
                            <span class="font-black text-blue-900 font-mono text-sm sm:text-base truncate block">
                                Rp {{ number_format($material->retail_price, 0, ',', '.') }}
                            </span>
                        </div>
                        
                        <button type="button" class="w-8 h-8 bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white rounded-xl flex items-center justify-center transition border-0 cursor-pointer text-sm flex-shrink-0 font-bold shadow-sm">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
            @endforeach

            <!-- Empty State for Filter/Search -->
            <div id="products-empty-state" class="col-span-full py-12 text-center hidden">
                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto text-slate-400 mb-2 text-lg">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <h4 class="text-xs font-bold text-slate-700 mb-1">Produk Tidak Ditemukan</h4>
                <p class="text-[11px] text-slate-400 mb-0">Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
            </div>
        </div>
    </div>

    <!-- Right Column: Cart & Checkout (Strict Fixed Responsive Width, Always In View) -->
    <div class="w-full lg:w-[360px] xl:w-[400px] 2xl:w-[430px] bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden h-full flex-shrink-0"
         :class="activeTab === 'cart' ? 'flex' : 'hidden lg:flex'">
        
        <!-- Cart Header -->
        <div class="px-4 py-3 bg-slate-900 text-white flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <!-- Back to Catalog button on screens < 1024px -->
                <button type="button" @click="activeTab = 'catalog'" class="lg:hidden w-8 h-8 bg-white/10 hover:bg-white/20 text-white rounded-lg flex items-center justify-center border-0 cursor-pointer" title="Kembali ke Katalog">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </button>
                <i class="fa-solid fa-cart-shopping text-blue-400 text-base"></i>
                <h2 class="text-sm font-bold mb-0 text-white">Keranjang Order (POS)</h2>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="clearCart()" class="text-[11px] text-slate-400 hover:text-rose-400 font-semibold bg-transparent border-0 cursor-pointer p-0">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset
                </button>
                <span id="cart-item-count-badge" class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="cartItemCount + ' item'">0 item</span>
            </div>
        </div>
        
        <!-- Cart Items List (Independent Scroll) -->
        <div class="p-3 flex-grow overflow-y-auto bg-slate-50/50 min-h-0 space-y-2" id="cart-container-desktop">
            <!-- Injected by JS -->
        </div>

        <!-- Checkout Pricing & Action Area (Docked at Bottom) -->
        <div class="p-3 border-t border-slate-200 bg-white space-y-2 flex-shrink-0 overflow-y-auto max-h-[50vh]">
            
            <!-- Customer Selection Area (Searchable Autocomplete + Auto-Create) -->
            <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/90 space-y-2 relative" @click.outside="showCustomerDropdown = false">
                <div class="flex items-center justify-between">
                    <label class="block text-[10px] font-bold text-slate-700 uppercase tracking-wider mb-0 flex items-center gap-1">
                        <i class="fa-solid fa-user-tag text-blue-600"></i> Pelanggan / Customer
                    </label>
                    <template x-if="customerName">
                        <button type="button" @click="clearCustomer()" class="text-[10px] text-rose-500 hover:text-rose-700 font-semibold bg-transparent border-0 cursor-pointer p-0">
                            <i class="fa-solid fa-xmark me-0.5"></i> Reset
                        </button>
                    </template>
                </div>

                <!-- Searchable Name Input with Live Dropdown -->
                <div class="relative">
                    <div class="flex items-center bg-white border border-slate-300 rounded-lg px-2 py-1 shadow-sm focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500">
                        <i class="fa-solid fa-magnifying-glass text-slate-400 text-xs me-1.5 flex-shrink-0"></i>
                        <input type="text" 
                               x-model="customerName" 
                               @focus="showCustomerDropdown = true" 
                               @input="customerId = null; showCustomerDropdown = true"
                               placeholder="Ketik / cari nama pelanggan..." 
                               class="w-full bg-transparent border-0 text-xs font-semibold text-slate-900 focus:outline-none p-0">
                        <template x-if="customerId">
                            <span class="badge bg-blue-100 text-blue-800 text-[9px] font-bold px-1.5 py-0.5 rounded flex-shrink-0">Tersimpan</span>
                        </template>
                    </div>

                    <!-- Autocomplete Dropdown Menu -->
                    <div x-show="showCustomerDropdown && filteredCustomers.length > 0" 
                         x-cloak 
                         class="absolute z-50 left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-48 overflow-y-auto divide-y divide-slate-100">
                        <template x-for="cust in filteredCustomers" :key="cust.id">
                            <div @click="selectExistingCustomer(cust)" 
                                 class="p-2 hover:bg-blue-50 cursor-pointer transition flex items-center justify-between text-xs">
                                <div class="min-w-0 pr-2">
                                    <div class="font-bold text-slate-900 truncate" x-text="cust.name"></div>
                                    <div class="text-[10px] text-slate-500 font-mono" x-text="cust.phone || cust.email || 'Tanpa Kontak'"></div>
                                </div>
                                <span class="text-[10px] text-blue-600 font-semibold flex-shrink-0">Pilih <i class="fa-solid fa-chevron-right text-[8px] ms-0.5"></i></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Optional Phone & Email Inputs -->
                <div class="grid grid-cols-2 gap-1.5 pt-1 border-t border-slate-200/60">
                    <div>
                        <label class="block text-[9.5px] font-bold text-slate-500 uppercase mb-0.5">No. WhatsApp (Opsional)</label>
                        <input type="text" x-model="customerPhone" placeholder="0812xxxxxxxx" 
                            class="w-full px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-[9.5px] font-bold text-slate-500 uppercase mb-0.5">Email (Opsional)</label>
                        <input type="email" x-model="customerEmail" placeholder="nama@email.com" 
                            class="w-full px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs">
                    </div>
                </div>
            </div>

            <!-- DP (Down Payment) & Custom Order Toggle - Hanya Muncul jika Total Tagihan >= Rp 500.000 -->
            <div x-show="isEligibleForDp" x-cloak class="p-2.5 bg-blue-50/60 rounded-xl border border-blue-200/80 space-y-2 transition-all">
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer mb-0">
                        <input type="checkbox" :checked="isDp" @change="toggleDp($event.target.checked)" id="is_dp_toggle" class="form-check-input text-blue-600 rounded">
                        <span class="text-xs font-bold text-blue-950">Pesanan Khusus / DP (Uang Muka)</span>
                    </label>
                    <span class="text-[10px] text-blue-700 font-semibold uppercase tracking-wider" x-show="isDp">Mode DP Aktif</span>
                </div>

                <!-- DP Extra Fields (Animated Expand) -->
                <div x-show="isDp" x-cloak class="space-y-2 pt-1 border-t border-blue-200/60 text-xs">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Estimasi Selesai (DL)</label>
                            <input type="date" x-model="dueDate" 
                                class="w-full px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs">
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-0.5">
                                <label class="text-[10px] font-bold text-slate-600 uppercase">Nominal DP (Rp)</label>
                                <span class="text-[9px] text-amber-700 font-bold" x-text="'Min. 50%: Rp ' + minDpAmount.toLocaleString('id-ID')"></span>
                            </div>
                            <input type="number" x-model="dpAmount" :min="minDpAmount" placeholder="0" 
                                class="w-full px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs font-mono font-bold text-emerald-800">
                        </div>
                    </div>

                    <!-- Shortcut DP Buttons (Minimal 50%) -->
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="text-slate-500 font-semibold">Pilihan DP (Min 50%):</span>
                        <div class="flex gap-1">
                            <button type="button" @click="setDpPercent(50)" class="px-2 py-0.5 bg-white border border-blue-300 text-blue-700 rounded font-bold hover:bg-blue-100">50% (Min)</button>
                            <button type="button" @click="setDpPercent(70)" class="px-2 py-0.5 bg-white border border-blue-300 text-blue-700 rounded font-bold hover:bg-blue-100">70%</button>
                            <button type="button" @click="setDpPercent(80)" class="px-2 py-0.5 bg-white border border-blue-300 text-blue-700 rounded font-bold hover:bg-blue-100">80%</button>
                        </div>
                    </div>

                    <!-- Notes & Specs -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Catatan Produksi / Finishing</label>
                        <textarea x-model="productionNotes" rows="1" placeholder="Misal: Finishing mata ayam 4 pojok, bahan matte..." 
                            class="w-full px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs"></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Method Tiles (Desktop & Mobile) -->
            @if(auth()->user()->isOperator())
                <div class="p-2.5 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-[11px] flex items-center gap-2 font-medium">
                    <i class="fa-solid fa-circle-info text-amber-600 text-sm flex-shrink-0"></i>
                    <span>Mode Cek Harga Aktif: Pesanan akan disimpan sebagai <strong>Draft Antrean Kasir</strong> tanpa pembayaran langsung.</span>
                </div>
            @else
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Metode Pembayaran (DP / Full)</label>
                    <div class="grid grid-cols-3 gap-1.5">
                        <!-- Cash Button -->
                        <button type="button" onclick="setPaymentMethod('Cash')" id="pm-desktop-Cash" 
                            class="pm-tile flex flex-col items-center justify-center py-1.5 px-2 border border-slate-200 rounded-xl transition duration-150 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer">
                            <i class="fa-solid fa-money-bill-wave text-sm mb-0.5 text-emerald-600"></i>
                            <span class="text-[11px] font-bold">Tunai</span>
                        </button>
                        <!-- Transfer Button -->
                        <button type="button" onclick="setPaymentMethod('Transfer')" id="pm-desktop-Transfer" 
                            class="pm-tile flex flex-col items-center justify-center py-1.5 px-2 border border-slate-200 rounded-xl transition duration-150 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer">
                            <i class="fa-solid fa-building-columns text-sm mb-0.5 text-blue-600"></i>
                            <span class="text-[11px] font-bold">Transfer</span>
                        </button>
                        <!-- QRIS Button -->
                        <button type="button" onclick="setPaymentMethod('QRIS')" id="pm-desktop-QRIS" 
                            class="pm-tile flex flex-col items-center justify-center py-1.5 px-2 border border-slate-200 rounded-xl transition duration-150 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer">
                            <i class="fa-solid fa-qrcode text-sm mb-0.5 text-indigo-600"></i>
                            <span class="text-[11px] font-bold">QRIS</span>
                        </button>
                    </div>
                </div>
            @endif

            <!-- Receipt Breakdown -->
            <div class="bg-slate-50 p-2.5 rounded-xl space-y-1 text-xs text-slate-500 font-medium border border-slate-100">
                <div class="flex justify-between">
                    <span>Total Tagihan Pesanan</span>
                    <span id="receipt-total-desktop" class="font-mono font-bold text-slate-900">Rp 0</span>
                </div>

                <!-- Negotiation Discount Breakdown Row -->
                <div id="nego-discount-row" class="hidden flex justify-between text-emerald-700 font-bold">
                    <span class="flex items-center gap-1">
                        <i class="fa-solid fa-handshake text-emerald-600"></i> Potongan Nego
                    </span>
                    <span id="nego-discount-text" class="font-mono">- Rp 0</span>
                </div>
                <div id="nego-final-row" class="hidden flex justify-between text-blue-900 font-black pt-1 border-t border-slate-200">
                    <span>Total Akhir Nego</span>
                    <span id="nego-final-text" class="font-mono text-sm">Rp 0</span>
                </div>
                
                <template x-if="isDp">
                    <div class="space-y-1 pt-1 border-t border-slate-200">
                        <div class="flex justify-between text-emerald-700 font-semibold">
                            <span>Uang Muka (DP) Dibayar</span>
                            <span class="font-mono" x-text="'Rp ' + Number(dpAmount || 0).toLocaleString('id-ID')"></span>
                        </div>
                        <div class="flex justify-between text-amber-800 font-extrabold">
                            <span>Sisa Piutang (Pelunasan Nanti)</span>
                            <span class="font-mono text-amber-700" x-text="'Rp ' + Math.max(0, (window.currentGrandTotal || 0) - Number(dpAmount || 0)).toLocaleString('id-ID')"></span>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Success Notification Card (Emerald Green) -->
            <div id="checkout-success-desktop" class="hidden bg-emerald-50 border border-emerald-300 text-emerald-900 p-3 rounded-xl text-xs space-y-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-emerald-600 text-white font-bold text-[10px] px-2 py-0.5 rounded" id="success-badge-tag">
                        <i class="fa-solid fa-circle-check me-1"></i> LUNAS (PAID)
                    </span>
                    <span class="font-mono font-bold text-blue-900 text-xs" id="success-inv-text">INV-XXXX</span>
                </div>
                <div class="text-[11px] text-emerald-800 fw-semibold" id="success-msg-text">
                    Transaksi berhasil diproses & tercatat pada kasir.
                </div>
                <div class="d-flex gap-2 pt-1">
                    <button type="button" id="btn-print-last-receipt" class="btn btn-sm btn-primary text-xs flex-1 py-1 font-semibold d-inline-flex align-items-center justify-content-center gap-1">
                        <i class="fa-solid fa-print"></i> Struk 58mm
                    </button>
                    <button type="button" id="btn-open-last-inv" class="btn btn-sm btn-outline-secondary text-xs flex-1 py-1 font-semibold d-inline-flex align-items-center justify-content-center gap-1">
                        <i class="fa-solid fa-file-invoice text-blue-600"></i> Buka Faktur
                    </button>
                </div>
                <button type="button" id="btn-wa-last-receipt" class="hidden btn btn-sm w-full py-1 text-xs font-bold text-white d-flex align-items-center justify-content-center gap-1.5 rounded-lg shadow-sm" style="background-color: #25D366; border-color: #25D366;">
                    <i class="fa-brands fa-whatsapp text-sm"></i> Kirim Faktur ke WhatsApp
                </button>
            </div>

            <!-- Error Notification Card (Rose Red) -->
            <div id="checkout-error-desktop" class="hidden bg-rose-50 border border-rose-200 text-rose-700 p-2.5 rounded-xl text-xs font-semibold"></div>

            <!-- Checkout Action Buttons Area (With Titik 3 Dropdown & Quick Draft Button) -->
            <div class="flex items-center gap-2 pt-1.5">
                @if(!auth()->user()->isOperator())
                    <!-- Titik 3 Dropdown Menu (Negosiasi & Opsi Draft) -->
                    <div class="dropdown">
                        <button class="h-11 w-11 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-700 border border-slate-300 rounded-xl transition flex items-center justify-center cursor-pointer flex-shrink-0 shadow-sm" 
                                type="button" 
                                id="btnDropdownActionPos" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false" 
                                title="Menu Opsi (Negosiasi & Draft)">
                            <i class="fa-solid fa-ellipsis-vertical text-lg text-slate-700"></i>
                        </button>
                        <ul class="dropdown-menu shadow-xl border border-slate-200 rounded-2xl p-1.5 text-xs z-50" aria-labelledby="btnDropdownActionPos">
                            <li>
                                <button type="button" class="dropdown-item py-2 px-3 rounded-xl flex items-center gap-2.5 font-bold text-slate-700 hover:bg-blue-50 hover:text-blue-700" onclick="openNegotiationModal()">
                                    <i class="fa-solid fa-handshake text-amber-500 text-sm"></i>
                                    <span>Negosiasi Harga Item</span>
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1 border-slate-100"></li>
                            <li>
                                <button type="button" class="dropdown-item py-2 px-3 rounded-xl flex items-center gap-2.5 font-bold text-amber-800 hover:bg-amber-50 hover:text-amber-900" onclick="processCheckout(true)">
                                    <i class="fa-solid fa-file-pen text-amber-600 text-sm"></i>
                                    <span>Simpan Sebagai Draft Pesanan</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Tombol Cepat: Simpan Sebagai Draft (Tanpa Bayar) -->
                    <button onclick="processCheckout(true)" type="button" class="h-11 px-3 bg-amber-50 hover:bg-amber-100 active:bg-amber-200 text-amber-900 border border-amber-300 font-bold rounded-xl flex items-center justify-center gap-1.5 text-xs shadow-sm transition cursor-pointer flex-shrink-0" title="Simpan sebagai draft pesanan tanpa bayar">
                        <i class="fa-solid fa-file-pen text-amber-600 text-sm"></i>
                        <span>Draft</span>
                    </button>
                @endif

                @if(auth()->user()->isOperator())
                    <button onclick="processCheckout(true)" id="checkout-btn-desktop" class="flex-1 h-11 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-bold py-2.5 px-4 rounded-xl shadow-md transition duration-150 flex justify-center items-center gap-2 cursor-pointer disabled:opacity-50 border-0 text-sm">
                        <i class="fa-solid fa-file-signature text-base"></i>
                        <span>Simpan Draft Pesanan (Ke Kasir)</span>
                    </button>
                @else
                    <button onclick="processCheckout(false)" id="checkout-btn-desktop" class="flex-1 h-11 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold py-2.5 px-4 rounded-xl shadow-md transition duration-150 flex justify-center items-center gap-2 cursor-pointer disabled:opacity-50 border-0 text-sm">
                        <i class="fa-solid fa-circle-check text-base"></i>
                        <span x-text="(isDp && isEligibleForDp) ? 'Proses Bayar DP' : 'Proses Bayar (Checkout)'">Proses Bayar (Checkout)</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Mobile & Tablet Persistent Bottom Bar (Visible on < lg when on catalog view) -->
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur border-t border-slate-200 px-4 py-2.5 flex items-center justify-between z-30 shadow-2xl transition duration-300"
         x-show="activeTab === 'catalog'" x-cloak>
        <div>
            <p class="text-[11px] font-medium text-slate-500 mb-0" x-text="cartItemCount + ' item di keranjang'">0 item di keranjang</p>
            <p class="text-sm font-extrabold text-blue-900 mb-0 font-mono" x-text="'Rp ' + Number(cartTotal).toLocaleString('id-ID')">Rp 0</p>
        </div>
        
        <button type="button" @click="activeTab = 'cart'" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl flex items-center gap-2 transition cursor-pointer border-0 text-xs shadow-md shadow-blue-500/30 active:scale-95">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Buka Keranjang</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </button>
    </div>

    <!-- Toast Notification for Product Click -->
    <div x-show="showToast" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="fixed bottom-16 lg:bottom-6 left-1/2 transform -translate-x-1/2 z-50 bg-slate-900 text-white px-4 py-2 rounded-full shadow-2xl flex items-center gap-2 text-xs border border-white/20 pointer-events-none"
         style="display: none;">
        <i class="fa-solid fa-circle-check text-emerald-400"></i>
        <span>Ditambahkan: <strong x-text="toastItemName" class="text-blue-300"></strong></span>
    </div>
</div>
</div>

<!-- Input holds global payment selection state -->
<input type="hidden" id="global_payment_method" value="Cash">

<!-- Modal Negosiasi Harga Satuan Item (Per-Item Negotiation) -->
<div class="modal fade" id="modalItemNegotiation" tabindex="-1" aria-labelledby="modalItemNegotiationLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden" style="border-radius: 1.25rem;">
            <!-- Modal Header -->
            <div class="px-4 py-3 d-flex justify-content-between align-items-center bg-slate-900 text-white">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-500/20 border border-amber-400/30 text-amber-400">
                        <i class="fa-solid fa-handshake text-sm"></i>
                    </div>
                    <div>
                        <h6 class="text-sm font-bold mb-0 text-white" id="modalItemNegotiationLabel">Negosiasi Harga Item</h6>
                        <span class="text-[11px] text-slate-400" id="item_nego_item_name">Katalog Item</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 space-y-3" style="background-color: #f8fafc;">
                <!-- Ringkasan Item & Harga Standar -->
                <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm">
                    <div class="d-flex justify-content-between items-start mb-1.5">
                        <div class="min-w-0 pe-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Item Terpilih</span>
                            <strong id="item_nego_name_display" class="text-slate-900 text-xs truncate block">-</strong>
                            <span id="item_nego_specs_display" class="text-[10px] text-blue-700 font-semibold block mt-0.5"></span>
                        </div>
                        <span id="item_nego_qty_display" class="badge bg-slate-100 text-slate-800 border text-xs font-mono font-bold px-2 py-1 flex-shrink-0">1 pcs</span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center pt-2 border-t border-slate-100 text-[11px]">
                        <span class="text-slate-500">Harga Standar Sistem:</span>
                        <div class="text-end font-mono">
                            <span id="item_nego_orig_unit" class="text-slate-700 font-bold">Rp 0 / pcs</span>
                            <span class="text-slate-400 text-[10px] block">Subtotal: <strong id="item_nego_orig_subtotal" class="text-slate-700">Rp 0</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Input Nominal Nego -->
                <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm space-y-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="font-bold text-slate-900 text-xs uppercase mb-0">Harga Satuan Kesepakatan (Nego)</label>
                        <span class="text-[10px] text-slate-400">per lembar / pcs</span>
                    </div>
                    
                    <div class="input-group input-group-sm">
                        <span class="input-group-text font-bold text-sm bg-slate-100 text-slate-700">Rp</span>
                        <input type="number" id="item_nego_unit_input" min="0" step="500" 
                               oninput="calcItemNegoPreview()" 
                               class="form-control font-mono font-bold text-slate-900 text-base" 
                               placeholder="Contoh: 45000">
                    </div>

                    <!-- Quick Action Chips -->
                    <div class="d-flex flex-wrap gap-1 align-items-center pt-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase me-1">Cepat:</span>
                        <button type="button" onclick="adjustItemNegoDelta(-5000)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-bold">- 5rb</button>
                        <button type="button" onclick="adjustItemNegoDelta(-10000)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-bold">- 10rb</button>
                        <button type="button" onclick="adjustItemNegoDelta(-20000)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-bold">- 20rb</button>
                        <button type="button" onclick="applyItemNegoPercent(5)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-bold">Disc 5%</button>
                        <button type="button" onclick="applyItemNegoPercent(10)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-bold">Disc 10%</button>
                        <button type="button" onclick="resetItemNegoModalToOrig()" class="btn btn-xs btn-outline-primary py-0.5 px-2 text-[10px] rounded-pill font-bold">Harga Asli</button>
                    </div>
                </div>

                <!-- Preview Box -->
                <div class="p-3 rounded-xl border border-amber-200 bg-amber-50/70 text-amber-950 text-xs">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-slate-600 font-medium">Subtotal Baru Setelah Nego:</span>
                        <strong id="item_nego_preview_subtotal" class="font-mono text-sm text-blue-900 font-extrabold">Rp 0</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center text-[11px] text-emerald-700">
                        <span>Penyesuaian per item:</span>
                        <strong id="item_nego_preview_diff" class="font-mono">- Rp 0 / pcs</strong>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-white px-4 py-2.5 border-top border-slate-200 d-flex justify-content-between align-items-center">
                <button type="button" onclick="resetItemNegotiationActive()" id="btn-reset-item-nego" class="btn btn-sm btn-outline-danger font-semibold px-2.5 text-xs">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset Asli
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm font-semibold px-3 text-xs" data-bs-dismiss="modal">Batal</button>
                    <button type="button" onclick="applyItemNegotiation()" class="btn btn-primary btn-sm font-bold shadow-sm px-3 text-xs">
                        <i class="fa-solid fa-check me-1"></i> Terapkan Nego Item
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Negosiasi Tagihan POS (Per-Item Negotiation Hub) -->
<div class="modal fade" id="modalNegotiation" tabindex="-1" aria-labelledby="modalNegotiationLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden" style="border-radius: 1.25rem;">
            <!-- Modal Header -->
            <div class="px-5 py-3.5 d-flex justify-content-between align-items-center bg-slate-900 text-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-amber-500/20 border border-amber-400/30 text-amber-400 text-lg">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <div>
                        <h5 class="text-base font-bold mb-0 text-white" id="modalNegotiationLabel">Negosiasi Harga per Item</h5>
                        <span class="text-xs text-slate-400">Atur harga kesepakatan langsung pada item tanpa potongan diskon</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white text-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 space-y-3" style="background-color: #f8fafc; max-height: calc(80vh - 120px); overflow-y: auto;">
                <div class="text-xs text-slate-500">
                    Klik tombol <strong>Atur Nego</strong> pada item di bawah untuk menyesuaikan harga satuan:
                </div>

                <!-- Container list item nego -->
                <div id="nego-items-list-container" class="space-y-2.5">
                    <!-- Populated via JS renderNegoItemsList() -->
                </div>

                <!-- Catatan SPK / Nego Opsional -->
                <div class="bg-white p-3 rounded-xl border border-slate-200 mt-2">
                    <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Catatan Kesepakatan (Opsional)</label>
                    <input type="text" id="input-nego-notes" placeholder="Misal: Order partai besar, kesepakatan khusus..." 
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-white px-5 py-3 border-t border-slate-200 d-flex justify-content-between align-items-center">
                <button type="button" onclick="resetAllItemNegotiations()" class="btn btn-outline-danger btn-sm text-xs font-semibold px-3">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset Semua Nego
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm text-xs font-semibold px-3" data-bs-dismiss="modal">
                        Tutup
                    </button>
                    <button type="button" onclick="applyNegotiationAndDraft()" class="btn btn-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 btn-sm text-xs font-bold shadow-sm px-3" style="background-color: #fef3c7; border-color: #fcd34d;">
                        <i class="fa-solid fa-file-pen text-amber-700 me-1"></i> Simpan sbg Draft
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Antrean Pesanan Draft Kasir -->
<div class="modal fade" id="modalDraftOrders" tabindex="-1" aria-labelledby="modalDraftOrdersLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden" style="border-radius: 1.25rem;">
            <div class="px-4 py-3 d-flex justify-content-between align-items-center bg-slate-900 text-white">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-500/20 border border-amber-400/30 text-amber-400">
                        <i class="fa-solid fa-inbox text-sm"></i>
                    </div>
                    <div>
                        <h5 class="fs-6 fw-bold mb-0 text-white" id="modalDraftOrdersLabel">Antrean Pesanan Draft Cabang</h5>
                        <span class="text-[11px] text-slate-400">Daftar pesanan yang dibuat oleh Operator / Cek Harga</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" onclick="loadDraftOrders()" class="btn btn-sm btn-light py-1 px-2.5 text-xs text-slate-700 font-semibold" title="Muat Ulang Draft">
                        <i class="fa-solid fa-rotate me-1"></i> Refresh
                    </button>
                    <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="p-4 max-h-[70vh] overflow-y-auto" style="background-color: #f8fafc;">
                <div id="draft-orders-loading" class="text-center py-8 text-slate-400">
                    <i class="fa-solid fa-spinner fa-spin text-2xl text-amber-600 mb-2"></i>
                    <p class="text-xs mb-0">Mengambil antrean pesanan draft...</p>
                </div>

                <div id="draft-orders-list" class="space-y-2.5 hidden">
                    <!-- Populated dynamically via JS -->
                </div>

                <div id="draft-orders-empty" class="text-center py-10 text-slate-400 hidden">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-300 text-2xl mb-2">
                        <i class="fa-solid fa-folder-open"></i>
                    </div>
                    <h6 class="text-xs font-bold text-slate-700 mb-1">Belum Ada Draft Menunggu</h6>
                    <p class="text-[11px] text-slate-400 mb-0">Semua pesanan draft dari operator telah diproses atau belum ada yang dibuat.</p>
                </div>
            </div>
            <div class="bg-white px-4 py-2.5 border-t border-slate-200 d-flex justify-content-between align-items-center text-xs text-slate-500">
                <span><i class="fa-solid fa-circle-info text-amber-500 me-1"></i> Pembayaran draft akan mengurangi stok dan mencatat penerimaan kas.</span>
                <button type="button" class="btn btn-sm btn-secondary text-xs" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Custom Dimension Banner (Interactive Roll Canvas & Adjustable Dimensions) -->
<div class="modal fade" id="modalBannerDimension" tabindex="-1" aria-labelledby="modalBannerDimensionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 1050px;">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden" style="border-radius: 1.25rem;">
            <style>
                #modalBannerDimension label {
                    position: static !important;
                    background-color: transparent !important;
                    padding: 0 !important;
                    letter-spacing: normal !important;
                    display: inline-block !important;
                    width: auto !important;
                }
            </style>
            <!-- Modal Header with Solid Background -->
            <div class="px-4 py-3 d-flex justify-content-between align-items-center" style="background-color: #0f172a !important; color: #ffffff !important;">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: rgba(59, 130, 246, 0.2); border: 1px solid rgba(96, 165, 250, 0.5); color: #60a5fa;">
                        <i class="fa-solid fa-scroll text-sm"></i>
                    </div>
                    <div>
                        <h5 class="fs-6 fw-bold mb-0 text-white" id="modalBannerDimensionLabel">Kustomisasi Ukuran Gulungan Banner</h5>
                        <span class="text-[11px]" style="color: #94a3b8;">Cetak Outdoor & Indoor (Lebar 1.0m - 3.0m | Panjang 1.0m - 30.0m)</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="p-4" style="background-color: #f8fafc; max-height: calc(88vh - 120px); overflow-y: auto;">
                <div class="row g-3">
                    <!-- LEFT COLUMN: Dynamic Banner Roll Visual Preview -->
                    <div class="col-12 col-lg-5">
                        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm h-100 d-flex flex-col justify-between" style="border-radius: 1rem;">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="overflow-hidden">
                                        <span class="text-[10px] font-bold tracking-wider text-slate-400 uppercase">Material Cetak</span>
                                        <h6 class="font-bold text-slate-900 text-sm mb-0 text-truncate" id="dim_product_name">-</h6>
                                    </div>
                                    <span class="badge bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-mono font-bold px-2 py-1" id="dim_product_price">Rp 0/m²</span>
                                </div>

                                <!-- Visual Roll Simulation Graphic Box -->
                                <div class="rounded-xl p-3 border border-slate-200 relative overflow-hidden flex flex-col items-center justify-center min-h-[220px]" style="background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);">
                                    <!-- Spindle Roll Top Header Graphic -->
                                    <div class="w-full flex items-center justify-center mb-2 z-10">
                                        <div class="h-4 rounded-full shadow-md w-11/12 border flex items-center justify-between px-2 relative" style="background: linear-gradient(90deg, #1e293b 0%, #64748b 50%, #1e293b 100%); border-color: #334155;">
                                            <div class="w-2 h-2 rounded-full" style="background-color: #0f172a;"></div>
                                            <span class="text-[8px] font-mono text-slate-200 uppercase tracking-widest font-bold">GULUNGAN ROLL BANNER</span>
                                            <div class="w-2 h-2 rounded-full" style="background-color: #0f172a;"></div>
                                        </div>
                                    </div>

                                    <!-- Dynamic Scaled Canvas Sheet -->
                                    <div id="banner_preview_canvas" 
                                         class="bg-white border-2 border-dashed border-blue-500 rounded shadow-md relative transition-all duration-300 flex flex-col items-center justify-center p-2 text-center overflow-hidden"
                                         style="width: 150px; height: 110px; max-width: 95%; max-height: 180px; background-color: #ffffff !important;">
                                        
                                        <!-- Corner Grommets (Mata Ayam) -->
                                        <div class="absolute top-1 left-1 w-2 h-2 rounded-full border border-slate-500 bg-slate-300 shadow-inner"></div>
                                        <div class="absolute top-1 right-1 w-2 h-2 rounded-full border border-slate-500 bg-slate-300 shadow-inner"></div>
                                        <div class="absolute bottom-1 left-1 w-2 h-2 rounded-full border border-slate-500 bg-slate-300 shadow-inner"></div>
                                        <div class="absolute bottom-1 right-1 w-2 h-2 rounded-full border border-slate-500 bg-slate-300 shadow-inner"></div>

                                        <!-- Canvas Label Text -->
                                        <div class="z-10 p-1">
                                             <i class="fa-solid fa-image text-blue-500 text-lg mb-1 block"></i>
                                            <span id="canvas_dim_label" class="font-bold text-slate-900 font-mono text-xs block leading-tight">1.0m x 2.0m</span>
                                            <span id="canvas_area_label" class="text-[10px] text-emerald-700 font-bold font-mono block">2.00 m²</span>
                                        </div>
                                    </div>

                                    <!-- Dimension Indicator Markers -->
                                    <div class="w-full flex justify-between items-center text-[10px] font-mono font-bold px-2 mt-2" style="color: #334155;">
                                        <span>↔ Lebar: <strong id="marker_width_label" class="text-blue-700">1.00 m</strong></span>
                                        <span>↕ Panjang: <strong id="marker_length_label" class="text-blue-700">2.00 m</strong></span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded-xl text-blue-900 text-[11px] leading-tight flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-check text-blue-600"></i>
                                <span>Batas Dimensi: <strong>Min 1.0m</strong> | Lebar Max 3.0m | Panjang Max 30.0m</span>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Input Controls, Sliders & Quick Chips -->
                    <div class="col-12 col-lg-7">
                        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm space-y-3" style="border-radius: 1rem;">
                            
                            <!-- 1. LEBAR BANNER (Locked Min 1.0m, Max 3.0m) -->
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="font-bold text-slate-900 text-xs uppercase mb-0">
                                        <span>1. Lebar Cetak (1.0m s/d 3.0m) <span class="text-rose-600">*</span></span>
                                    </label>
                                    <span class="text-[11px] font-mono font-bold text-blue-800 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                        <span id="label_val_width">1.00</span> Meter
                                    </span>
                                </div>
                                <div class="input-group input-group-sm mb-1.5">
                                    <input type="number" id="banner_width_m" min="1.0" max="3.0" step="0.05" value="1.0" 
                                           oninput="syncBannerWidth(this.value, 'input')" 
                                           class="form-control form-control-sm font-bold font-mono text-slate-900 text-sm" placeholder="1.0">
                                    <span class="input-group-text font-bold text-xs bg-slate-100 text-slate-700">Meter</span>
                                </div>
                                <input type="range" id="banner_width_slider" min="1.0" max="3.0" step="0.05" value="1.0" 
                                       oninput="syncBannerWidth(this.value, 'slider')" 
                                       class="form-range w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                                
                                <!-- Quick Chips Width -->
                                <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                                    <span class="text-[10px] text-slate-500 font-semibold me-1">Pilihan Lebar:</span>
                                    <button type="button" onclick="setBannerWidth(1.0)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">1.0m</button>
                                    <button type="button" onclick="setBannerWidth(1.2)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">1.2m</button>
                                    <button type="button" onclick="setBannerWidth(1.5)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">1.5m</button>
                                    <button type="button" onclick="setBannerWidth(2.0)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">2.0m</button>
                                    <button type="button" onclick="setBannerWidth(2.5)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">2.5m</button>
                                    <button type="button" onclick="setBannerWidth(3.0)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">3.0m (Max)</button>
                                </div>
                            </div>

                            <!-- 2. PANJANG BANNER (Locked Min 1.0m, Max 30.0m) -->
                            <div class="border-t border-slate-100 pt-2.5">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="font-bold text-slate-900 text-xs uppercase mb-0">
                                        <span>2. Panjang Cetak (1.0m s/d 30.0m) <span class="text-rose-600">*</span></span>
                                    </label>
                                    <span class="text-[11px] font-mono font-bold text-blue-800 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                        <span id="label_val_length">2.00</span> Meter
                                    </span>
                                </div>
                                <div class="input-group input-group-sm mb-1.5">
                                    <input type="number" id="banner_length_m" min="1.0" max="30.0" step="0.1" value="2.0" 
                                           oninput="syncBannerLength(this.value, 'input')" 
                                           class="form-control form-control-sm font-bold font-mono text-slate-900 text-sm" placeholder="2.0">
                                    <span class="input-group-text font-bold text-xs bg-slate-100 text-slate-700">Meter</span>
                                </div>
                                <input type="range" id="banner_length_slider" min="1.0" max="30.0" step="0.1" value="2.0" 
                                       oninput="syncBannerLength(this.value, 'slider')" 
                                       class="form-range w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                                
                                <!-- Quick Chips Length -->
                                <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                                    <span class="text-[10px] text-slate-500 font-semibold me-1">Pilihan Panjang:</span>
                                    <button type="button" onclick="setBannerLength(1.0)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">1m</button>
                                    <button type="button" onclick="setBannerLength(2.0)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">2m</button>
                                    <button type="button" onclick="setBannerLength(3.0)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">3m</button>
                                    <button type="button" onclick="setBannerLength(4.0)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">4m</button>
                                    <button type="button" onclick="setBannerLength(5.0)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">5m</button>
                                    <button type="button" onclick="setBannerLength(10.0)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">10m</button>
                                    <button type="button" onclick="setBannerLength(20.0)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">20m</button>
                                    <button type="button" onclick="setBannerLength(30.0)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">30m (Max)</button>
                                </div>
                            </div>

                            <!-- 3. FINISHING & JUMLAH LEMBAR (QTY) -->
                            <div class="border-t border-slate-100 pt-2.5 row g-2">
                                <div class="col-7">
                                    <label class="font-bold text-slate-900 text-xs uppercase mb-1 block">Finishing Spanduk</label>
                                    <select id="banner_finishing" onchange="onFinishingChange(this.value)" class="form-select form-select-sm text-xs font-semibold text-slate-800">
                                        <option value="Mata Ayam 4 Sudut">Mata Ayam 4 Sudut (Standar)</option>
                                        <option value="Mata Ayam Keliling (Per Meter)">Mata Ayam Keliling (Per 1 Meter)</option>
                                        <option value="Mata Ayam Custom">Mata Ayam Custom (Pilih Jumlah)</option>
                                        <option value="Lipat Pas Gambar">Lipat Press Lem Pas Gambar</option>
                                        <option value="Selongsong Atas Bawah">Selongsong Bambu / Kayu (Atas-Bawah)</option>
                                        <option value="Lebihan Putih (Tanpa Mata Ayam)">Lebihan Putih 5cm (Tanpa Mata Ayam)</option>
                                        <option value="Potong Pas (Tanpa Finishing)">Potong Pas (Tanpa Finishing)</option>
                                    </select>
                                </div>
                                <div class="col-5">
                                    <label class="font-bold text-slate-900 text-xs uppercase mb-1 block">Jumlah (Qty)</label>
                                    <div class="input-group input-group-sm">
                                        <button type="button" onclick="changeDimQty(-1)" class="btn btn-outline-secondary font-bold">-</button>
                                        <input type="number" id="dim_qty" min="1" value="1" oninput="calculateDimensionPreview()" class="form-control form-control-sm text-center font-bold font-mono text-slate-900">
                                        <button type="button" onclick="changeDimQty(1)" class="btn btn-outline-secondary font-bold">+</button>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. PENGATURAN MATA AYAM / EYELETS (4 GRATIS, >4 DI-CHARGE 500/PCS) -->
                            <div id="wrapper_mata_ayam" class="border-t border-slate-100 pt-2.5">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="font-bold text-slate-900 text-xs uppercase mb-0 d-inline-flex align-items-center">
                                        <i class="fa-solid fa-circle-dot text-blue-600 me-1.5"></i>
                                        <span>Jumlah Mata Ayam / Ring Lubang</span>
                                    </label>
                                    <span id="badge_mata_ayam_rule" class="text-[10px] font-bold px-2 py-0.5 rounded border bg-emerald-50 text-emerald-700 border-emerald-200">
                                        4 Sudut Gratis (Rp 0)
                                    </span>
                                </div>

                                <div class="row g-2 align-items-center">
                                    <div class="col-6 col-sm-5">
                                        <div class="input-group input-group-sm">
                                            <button type="button" onclick="changeMataAyamQty(-1)" class="btn btn-outline-secondary font-bold">-</button>
                                            <input type="number" id="mata_ayam_count" min="0" max="100" value="4" 
                                                   oninput="onMataAyamInput(this.value)" 
                                                   class="form-control form-control-sm text-center font-bold font-mono text-slate-900" placeholder="4">
                                            <button type="button" onclick="changeMataAyamQty(1)" class="btn btn-outline-secondary font-bold">+</button>
                                            <span class="input-group-text font-bold text-xs bg-slate-100 text-slate-700">Pcs</span>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-7">
                                        <div id="text_mata_ayam_cost_desc" class="text-[11px] font-medium text-slate-500 leading-tight">
                                            Maks. 4 pcs gratis (tiap sudut). Tambahan dikenakan Rp 500/pcs.
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Chips Mata Ayam -->
                                <div class="d-flex flex-wrap gap-1 align-items-center mt-1.5">
                                    <span class="text-[10px] text-slate-500 font-semibold me-1">Pilihan Cepat:</span>
                                    <button type="button" onclick="setMataAyamCount(4)" class="btn btn-xs btn-outline-primary py-0.5 px-2 text-[10px] rounded-pill font-bold">4 Pcs (Gratis)</button>
                                    <button type="button" onclick="setMataAyamCount(6)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">6 Pcs (+Rp 1.000)</button>
                                    <button type="button" onclick="setMataAyamCount(8)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">8 Pcs (+Rp 2.000)</button>
                                    <button type="button" onclick="setMataAyamKelilingAuto()" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">Keliling (Tiap 1m)</button>
                                    <button type="button" onclick="setMataAyamCount(0)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill font-semibold">Tanpa Lubang (0)</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- LIVE CALCULATION SUMMARY CARD WITH SOLID HIGH-CONTRAST DARK BACKGROUND -->
                <div class="p-3.5 mt-3 shadow-lg rounded-2xl" style="background-color: #0f172a !important; color: #ffffff !important; border-radius: 1rem; border: 1px solid #1e293b;">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-7">
                            <div class="d-flex flex-wrap gap-x-4 gap-y-1 text-xs mb-1" style="color: #cbd5e1;">
                                <div>
                                    <span style="color: #94a3b8;">Ukuran Cetak:</span>
                                    <strong id="dim_preview_size" class="text-white font-mono font-bold ms-1">1.00m x 2.00m</strong>
                                </div>
                                <div>
                                    <span style="color: #94a3b8;">Luas Cetak:</span>
                                    <strong id="dim_preview_area" class="font-mono font-bold ms-1" style="color: #34d399 !important;">2.00 m²</strong>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-x-3 text-[11px] mb-1" style="color: #cbd5e1;">
                                <div>
                                    <span style="color: #94a3b8;">Cetak Bahan:</span>
                                    <strong id="dim_preview_material_price" class="font-mono text-white font-bold ms-1">Rp 0</strong>
                                </div>
                                <div id="dim_preview_eyelet_wrapper">
                                    <span style="color: #94a3b8;">Mata Ayam:</span>
                                    <strong id="dim_preview_eyelet_price" class="font-mono font-bold ms-1 text-emerald-400">Gratis (4 pcs)</strong>
                                </div>
                            </div>
                            <div class="text-[12px]" style="color: #94a3b8;">
                                <span>Harga Satuan:</span>
                                <strong id="dim_preview_unit_price" class="font-mono font-bold ms-1" style="color: #facc15 !important;">Rp 0</strong> / lembar
                            </div>
                        </div>
                        <div class="col-12 col-md-5 text-md-end mt-2 mt-md-0">
                            <span class="text-[10px] uppercase tracking-wider block font-bold" style="color: #94a3b8;">TOTAL SUBTOTAL PESANAN</span>
                            <span id="dim_preview_subtotal" class="font-mono text-2xl font-extrabold" style="color: #38bdf8 !important;">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-slate-100 border-top px-4 py-2.5 d-flex justify-content-between align-items-center">
                <span class="text-[11px] text-slate-500 font-medium">
                    <i class="fa-solid fa-calculator text-blue-600 me-1"></i> Perhitungan otomatis luas roll & tier harga grosir.
                </span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm font-semibold px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="button" onclick="confirmBannerDimensionAddToCart()" class="btn btn-primary btn-sm font-bold shadow-sm px-3">
                        <i class="fa-solid fa-cart-plus me-1"></i> Masukkan ke Keranjang
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Products master catalog lookup object indexed by material id
    const productsCatalog = @json($materials->keyBy('id'));

    function onSelectProduct(materialId) {
        const product = productsCatalog[materialId];
        if (!product) return;
        handleProductClick(
            product.id,
            product.material_name,
            product.fixed_size,
            parseFloat(product.retail_price) || 0,
            product.wholesale_prices || [],
            product.category || ''
        );
    }

    window.onSelectProduct = onSelectProduct;

    let cart = [];
    let cartCounter = 0;
    window.currentGrandTotal = 0;

    // Active state for dimension modal
    let activeDimProduct = {
        materialId: null,
        name: '',
        fixedSize: null,
        retailPrice: 0,
        wholesalePrices: [],
        editCartId: null
    };

    // --- Check if product is banner or custom media ---
    function isBannerProduct(name, fixedSize, category) {
        const nameLower = (name || '').toLowerCase();
        const catLower = (category || '').toLowerCase();

        // Check outdoor / indoor category
        if (catLower.includes('outdoor') || catLower.includes('indoor') || catLower.includes('banner') || catLower.includes('spanduk')) {
            return true;
        }

        // Keywords
        const bannerKeywords = ['flexi', 'flexy', 'albatros', 'albatross', 'banner', 'spanduk', 'baliho', 'backdrop', 'ritrama', 'oneway', 'one way', 'kain banner', 'luster', 'polybanner', 'backlite', 'frontlite', 'korchin', 'hicon', 'xbanner', 'x-banner', 'roll up'];
        for (let kw of bannerKeywords) {
            if (nameLower.includes(kw)) return true;
        }

        if (fixedSize && parseFloat(fixedSize) > 0) {
            return true;
        }

        return false;
    }

    // --- Handle product card click ---
    function handleProductClick(materialId, materialName, fixedSize, retailPrice, wholesalePrices, category = '') {
        if (isBannerProduct(materialName, fixedSize, category)) {
            openBannerDimensionModal(materialId, materialName, fixedSize, retailPrice, wholesalePrices);
        } else {
            addToCart(materialId, materialName, fixedSize, retailPrice, wholesalePrices);
        }
    }

    // --- Edit Banner item in Cart ---
    function editBannerCartItem(itemId) {
        const item = cart.find(i => i.id === itemId);
        if (!item) return;
        openBannerDimensionModal(
            item.material_id,
            item.material_name_or_type,
            item.fixed_length_m || 3.0,
            item.retail_price,
            item.wholesale_prices || [],
            item
        );
    }

    // --- Modal Safe Opener & Closer ---
    function showBannerModal() {
        const modalEl = document.getElementById('modalBannerDimension');
        if (!modalEl) return;
        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
                return;
            }
        } catch(e) {
            console.warn('Bootstrap modal fallback', e);
        }
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        modalEl.removeAttribute('aria-hidden');
        modalEl.setAttribute('aria-modal', 'true');
        if (!document.getElementById('custom-modal-backdrop')) {
            const bd = document.createElement('div');
            bd.id = 'custom-modal-backdrop';
            bd.className = 'modal-backdrop fade show';
            document.body.appendChild(bd);
        }
    }

    function hideBannerModal() {
        const modalEl = document.getElementById('modalBannerDimension');
        if (!modalEl) return;
        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
        } catch(e) {}
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.removeAttribute('aria-modal');
        const bd = document.getElementById('custom-modal-backdrop');
        if (bd) bd.remove();
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
    }

    // --- Open Banner Dimension Modal ---
    function openBannerDimensionModal(materialId, materialName, fixedSize, retailPrice, wholesalePrices, editCartItem = null) {
        activeDimProduct = {
            materialId: materialId || (editCartItem ? editCartItem.material_id : null),
            name: materialName,
            fixedSize: fixedSize,
            retailPrice: retailPrice,
            wholesalePrices: wholesalePrices || [],
            editCartId: editCartItem ? editCartItem.id : null
        };

        const nameEl = document.getElementById('dim_product_name');
        if (nameEl) nameEl.innerText = materialName;
        const priceEl = document.getElementById('dim_product_price');
        if (priceEl) priceEl.innerText = 'Rp ' + Number(retailPrice).toLocaleString('id-ID') + '/m²';

        // Set Width (Locked min 1.0m, max 3.0m)
        let targetWidth = 1.0;
        if (editCartItem && editCartItem.width_m) {
            targetWidth = parseFloat(editCartItem.width_m);
        } else if (fixedSize && parseFloat(fixedSize) > 0) {
            targetWidth = Math.max(1.0, Math.min(3.0, parseFloat(fixedSize)));
        }
        setBannerWidth(targetWidth);

        // Set Length (Locked min 1.0m, max 30.0m)
        let targetLength = 2.0;
        if (editCartItem && editCartItem.length_m) {
            targetLength = parseFloat(editCartItem.length_m);
        } else if (editCartItem && editCartItem.custom_width_cm) {
            targetLength = parseFloat(editCartItem.custom_width_cm) / 100;
        }
        setBannerLength(targetLength);

        // Set Finishing & Mata Ayam
        const finishEl = document.getElementById('banner_finishing');
        if (finishEl) {
            if (editCartItem && editCartItem.finishing) {
                finishEl.value = editCartItem.finishing;
            } else {
                finishEl.value = 'Mata Ayam 4 Sudut';
            }
        }

        let defaultEyelets = 4;
        if (editCartItem && editCartItem.eyelet_count !== undefined) {
            defaultEyelets = parseInt(editCartItem.eyelet_count, 10) || 0;
        } else if (finishEl && (finishEl.value.includes('Tanpa') || finishEl.value.includes('Lipat') || finishEl.value.includes('Selongsong') || finishEl.value.includes('Potong'))) {
            defaultEyelets = 0;
        }
        setMataAyamCount(defaultEyelets);

        // Set Qty
        const qtyVal = editCartItem ? editCartItem.qty : 1;
        const qtyEl = document.getElementById('dim_qty');
        if (qtyEl) qtyEl.value = qtyVal;

        calculateDimensionPreview();
        showBannerModal();
    }

    // --- Helper Functions for Mata Ayam (Eyelets) ---
    function onFinishingChange(finishingVal) {
        if (finishingVal === 'Mata Ayam 4 Sudut') {
            setMataAyamCount(4);
        } else if (finishingVal === 'Mata Ayam Keliling (Per Meter)') {
            setMataAyamKelilingAuto();
        } else if (finishingVal === 'Mata Ayam Custom') {
            const cur = parseInt(document.getElementById('mata_ayam_count')?.value, 10) || 4;
            setMataAyamCount(cur);
        } else {
            // Tanpa Mata Ayam (Lipat, Selongsong, Lebihan, Potong)
            setMataAyamCount(0);
        }
    }

    function setMataAyamCount(count) {
        count = Math.max(0, parseInt(count, 10) || 0);
        const input = document.getElementById('mata_ayam_count');
        if (input) input.value = count;
        calculateDimensionPreview();
    }

    function changeMataAyamQty(delta) {
        const input = document.getElementById('mata_ayam_count');
        let val = (parseInt(input?.value, 10) || 0) + delta;
        if (val < 0) val = 0;
        if (val > 100) val = 100;
        if (input) input.value = val;
        calculateDimensionPreview();
    }

    function onMataAyamInput(val) {
        let count = parseInt(val, 10);
        if (isNaN(count) || count < 0) count = 0;
        calculateDimensionPreview();
    }

    function setMataAyamKelilingAuto() {
        let rawWidth = parseFloat(document.getElementById('banner_width_m')?.value) || 1.0;
        let rawLength = parseFloat(document.getElementById('banner_length_m')?.value) || 1.0;
        // Keliling: perimeter (4 sudut + jarak tiap 1m)
        const perimeter = 2 * (rawWidth + rawLength);
        const autoCount = Math.max(4, Math.round(perimeter));
        setMataAyamCount(autoCount);
    }

    // --- Synchronization between sliders and inputs (Locked Min 1.0m) ---
    function syncBannerWidth(val, source) {
        let num = parseFloat(val) || 1.0;
        num = Math.max(1.0, Math.min(3.0, num));
        num = Math.round(num * 100) / 100;

        if (source === 'slider') {
            document.getElementById('banner_width_m').value = num;
        } else {
            document.getElementById('banner_width_slider').value = num;
        }
        document.getElementById('label_val_width').innerText = num.toFixed(2);
        calculateDimensionPreview();
    }

    function setBannerWidth(val) {
        let num = parseFloat(val) || 1.0;
        num = Math.max(1.0, Math.min(3.0, num));
        num = Math.round(num * 100) / 100;

        document.getElementById('banner_width_m').value = num;
        document.getElementById('banner_width_slider').value = num;
        document.getElementById('label_val_width').innerText = num.toFixed(2);
        calculateDimensionPreview();
    }

    function syncBannerLength(val, source) {
        let num = parseFloat(val) || 1.0;
        num = Math.max(1.0, Math.min(30.0, num));
        num = Math.round(num * 100) / 100;

        if (source === 'slider') {
            document.getElementById('banner_length_m').value = num;
        } else {
            document.getElementById('banner_length_slider').value = num;
        }
        document.getElementById('label_val_length').innerText = num.toFixed(2);
        calculateDimensionPreview();
    }

    function setBannerLength(val) {
        let num = parseFloat(val) || 1.0;
        num = Math.max(1.0, Math.min(30.0, num));
        num = Math.round(num * 100) / 100;

        document.getElementById('banner_length_m').value = num;
        document.getElementById('banner_length_slider').value = num;
        document.getElementById('label_val_length').innerText = num.toFixed(2);
        calculateDimensionPreview();
    }

    function changeDimQty(delta) {
        const input = document.getElementById('dim_qty');
        let val = (parseInt(input.value, 10) || 1) + delta;
        if (val < 1) val = 1;
        input.value = val;
        calculateDimensionPreview();
    }

    // --- Dynamic Banner Roll Canvas & Calculation ---
    function calculateDimensionPreview() {
        let rawWidth = parseFloat(document.getElementById('banner_width_m')?.value) || 1.0;
        let rawLength = parseFloat(document.getElementById('banner_length_m')?.value) || 1.0;
        const qty = parseInt(document.getElementById('dim_qty')?.value, 10) || 1;
        let eyeletCount = parseInt(document.getElementById('mata_ayam_count')?.value, 10);
        if (isNaN(eyeletCount) || eyeletCount < 0) eyeletCount = 0;

        // Strict Constraints: Lebar [1.0m - 3.0m], Panjang [1.0m - 30.0m]
        rawWidth = Math.max(1.0, Math.min(3.0, rawWidth));
        rawLength = Math.max(1.0, Math.min(30.0, rawLength));

        const areaM2 = Math.round((rawWidth * rawLength) * 1000) / 1000;

        // Price calculation using Wholesale Tier & Area
        const { price: baseUnitPrice } = getUnitPrice(activeDimProduct.retailPrice, activeDimProduct.wholesalePrices, qty);
        const materialCostPerLembar = Math.round(areaM2 * baseUnitPrice);

        // Aturan Mata Ayam: 4 pcs gratis, jika lebih dari 4 dikenakan biaya 500 per pcs
        let extraEyeletCost = 0;
        if (eyeletCount > 4) {
            extraEyeletCost = (eyeletCount - 4) * 500;
        }

        const unitPricePerLembar = materialCostPerLembar + extraEyeletCost;
        const subtotal = unitPricePerLembar * qty;

        // Update Labels
        document.getElementById('dim_preview_size').innerText = `${rawWidth.toFixed(2)}m x ${rawLength.toFixed(2)}m`;
        document.getElementById('dim_preview_area').innerText = `${areaM2.toFixed(2)} m²`;
        if (document.getElementById('dim_preview_material_price')) {
            document.getElementById('dim_preview_material_price').innerText = 'Rp ' + Number(materialCostPerLembar).toLocaleString('id-ID');
        }
        if (document.getElementById('dim_preview_eyelet_price')) {
            if (eyeletCount === 0) {
                document.getElementById('dim_preview_eyelet_price').innerText = 'Tanpa Lubang (Rp 0)';
                document.getElementById('dim_preview_eyelet_price').className = 'font-mono font-bold ms-1 text-slate-400';
            } else if (eyeletCount <= 4) {
                document.getElementById('dim_preview_eyelet_price').innerText = `Gratis (${eyeletCount} pcs)`;
                document.getElementById('dim_preview_eyelet_price').className = 'font-mono font-bold ms-1 text-emerald-400';
            } else {
                document.getElementById('dim_preview_eyelet_price').innerText = `+Rp ${Number(extraEyeletCost).toLocaleString('id-ID')} (${eyeletCount} pcs: 4 Free + ${eyeletCount - 4}x500)`;
                document.getElementById('dim_preview_eyelet_price').className = 'font-mono font-bold ms-1 text-amber-300';
            }
        }

        document.getElementById('dim_preview_unit_price').innerText = 'Rp ' + Number(unitPricePerLembar).toLocaleString('id-ID');
        document.getElementById('dim_preview_subtotal').innerText = 'Rp ' + Number(subtotal).toLocaleString('id-ID');

        document.getElementById('marker_width_label').innerText = `${rawWidth.toFixed(2)} m`;
        document.getElementById('marker_length_label').innerText = `${rawLength.toFixed(2)} m`;

        document.getElementById('canvas_dim_label').innerText = `${rawWidth.toFixed(2)}m x ${rawLength.toFixed(2)}m`;
        document.getElementById('canvas_area_label').innerText = `${areaM2.toFixed(2)} m²`;

        // Update Rule Badge & Desc in input section
        const badgeRule = document.getElementById('badge_mata_ayam_rule');
        const descCost = document.getElementById('text_mata_ayam_cost_desc');
        if (badgeRule && descCost) {
            if (eyeletCount === 0) {
                badgeRule.className = 'text-[10px] font-bold px-2 py-0.5 rounded border bg-slate-100 text-slate-600 border-slate-200';
                badgeRule.innerText = 'Tanpa Mata Ayam (Rp 0)';
                descCost.innerHTML = 'Spanduk diproduksi tanpa ring mata ayam.';
            } else if (eyeletCount <= 4) {
                badgeRule.className = 'text-[10px] font-bold px-2 py-0.5 rounded border bg-emerald-50 text-emerald-700 border-emerald-200';
                badgeRule.innerText = `${eyeletCount} Sudut Standar (GRATIS)`;
                descCost.innerHTML = 'Standar gratis hingga 4 mata ayam di tiap sudut spanduk.';
            } else {
                badgeRule.className = 'text-[10px] font-bold px-2 py-0.5 rounded border bg-amber-50 text-amber-800 border-amber-300';
                badgeRule.innerText = `+ Rp ${Number(extraEyeletCost).toLocaleString('id-ID')} (${eyeletCount - 4} Pcs Tambahan)`;
                descCost.innerHTML = `<strong>4 pcs pertama gratis</strong>, <strong>${eyeletCount - 4} pcs tambahan</strong> dikenakan Rp 500/pcs (+Rp ${Number(extraEyeletCost).toLocaleString('id-ID')}/lembar).`;
            }
        }

        // Update Visual Canvas Aspect Ratio
        const canvas = document.getElementById('banner_preview_canvas');
        if (canvas) {
            let ratio = rawLength / rawWidth;
            let targetW = 140;
            let targetH = 110;

            if (ratio >= 2.0) {
                targetW = Math.min(200, Math.round(90 + (ratio * 10)));
                targetH = 75;
            } else if (ratio <= 0.7) {
                targetW = 100;
                targetH = Math.min(140, Math.round(90 + (1 / ratio * 12)));
            } else {
                targetW = 130;
                targetH = Math.min(120, Math.max(70, Math.round(130 / ratio)));
            }

            canvas.style.width = `${targetW}px`;
            canvas.style.height = `${targetH}px`;
        }
    }

    // --- Confirm Custom Banner to Cart ---
    function confirmBannerDimensionAddToCart() {
        let rawWidth = parseFloat(document.getElementById('banner_width_m')?.value) || 1.0;
        let rawLength = parseFloat(document.getElementById('banner_length_m')?.value) || 1.0;
        const qty = parseInt(document.getElementById('dim_qty')?.value, 10) || 1;
        const finishing = document.getElementById('banner_finishing')?.value || 'Mata Ayam 4 Sudut';
        let eyeletCount = parseInt(document.getElementById('mata_ayam_count')?.value, 10);
        if (isNaN(eyeletCount) || eyeletCount < 0) eyeletCount = 0;

        // Strict Lock min 1.0m
        rawWidth = Math.max(1.0, Math.min(3.0, rawWidth));
        rawLength = Math.max(1.0, Math.min(30.0, rawLength));

        const areaM2 = Math.round((rawWidth * rawLength) * 1000) / 1000;

        let extraEyeletCost = 0;
        if (eyeletCount > 4) {
            extraEyeletCost = (eyeletCount - 4) * 500;
        }

        let eyeletDesc = '';
        if (eyeletCount === 0) {
            eyeletDesc = 'Tanpa Mata Ayam';
        } else if (eyeletCount <= 4) {
            eyeletDesc = `Mata Ayam ${eyeletCount} Sudut (Gratis)`;
        } else {
            eyeletDesc = `Mata Ayam ${eyeletCount} Pcs (+Rp ${Number(extraEyeletCost).toLocaleString('id-ID')})`;
        }

        const noteDim = `${rawWidth.toFixed(2)}m x ${rawLength.toFixed(2)}m (${areaM2.toFixed(2)}m²) - ${finishing} [${eyeletDesc}]`;

        if (activeDimProduct.editCartId !== null) {
            // Edit existing cart item
            const item = cart.find(i => i.id === activeDimProduct.editCartId);
            if (item) {
                item.material_id = activeDimProduct.materialId || item.material_id;
                item.material_name_or_type = activeDimProduct.name || item.material_name_or_type;
                item.width_m = rawWidth;
                item.length_m = rawLength;
                item.fixed_length_m = rawWidth;
                item.custom_width_cm = Math.round(rawLength * 100);
                item.area_m2 = areaM2;
                item.billable_area_m2 = areaM2;
                item.finishing = finishing;
                item.eyelet_count = eyeletCount;
                item.extra_eyelet_cost = extraEyeletCost;
                item.qty = qty;
                item.dimension_text = noteDim;
            }
        } else {
            // Add new custom banner item to cart
            cart.push({
                id: cartCounter++,
                material_id: activeDimProduct.materialId,
                material_name_or_type: activeDimProduct.name,
                width_m: rawWidth,
                length_m: rawLength,
                fixed_length_m: rawWidth,
                custom_width_cm: Math.round(rawLength * 100),
                area_m2: areaM2,
                billable_area_m2: areaM2,
                finishing: finishing,
                eyelet_count: eyeletCount,
                extra_eyelet_cost: extraEyeletCost,
                dimension_text: noteDim,
                requested_size: rawWidth,
                is_custom_banner: true,
                qty: qty,
                retail_price: activeDimProduct.retailPrice,
                wholesale_prices: activeDimProduct.wholesalePrices
            });
        }

        renderCart();

        window.dispatchEvent(new CustomEvent('item-added-to-cart', { detail: { name: activeDimProduct.name } }));

        hideBannerModal();
    }
    // --- Add regular non-banner items to Cart ---
    function addToCart(materialId, materialName, fixedSize, retailPrice, wholesalePrices) {
        let size = fixedSize;

        let existingItem = cart.find(i => (i.material_id === materialId || i.material_name_or_type === materialName) && !i.is_custom_banner);

        if (existingItem) {
            updateQty(existingItem.id, 1);
        } else {
            cart.push({
                id: cartCounter++,
                material_id: materialId,
                material_name_or_type: materialName,
                requested_size: size,
                is_custom_banner: false,
                qty: 1,
                retail_price: retailPrice,
                wholesale_prices: wholesalePrices || []
            });
            renderCart();
        }

        window.dispatchEvent(new CustomEvent('item-added-to-cart', { detail: { name: materialName } }));
    }

    // --- Clear All Items in Cart ---
    function clearCart() {
        if (cart.length > 0) {
            cart = [];
            renderCart();
        }
    }

    // --- Update Quantities by Increment/Decrement ---
    function updateQty(id, delta) {
        const item = cart.find(i => i.id === id);
        if (item) {
            item.qty += delta;
            if (item.qty <= 0) {
                cart = cart.filter(i => i.id !== id);
            }
            renderCart();
        }
    }

    // --- Set Quantity Directly via Typing ---
    function setQty(id, val) {
        let parsed = parseInt(val, 10);
        if (isNaN(parsed) || parsed <= 0) {
            parsed = 1;
        }
        const item = cart.find(i => i.id === id);
        if (item) {
            item.qty = parsed;
            renderCart();
        }
    }

    // --- Helper to calculate unit price based on wholesale tiers ---
    function getUnitPrice(retailPrice, wholesalePrices, qty) {
        let price = retailPrice;
        let isWholesale = false;
        
        if (wholesalePrices && wholesalePrices.length > 0) {
            let applicableTier = null;
            wholesalePrices.forEach(tier => {
                if (qty >= tier.min_qty) {
                    if (!applicableTier || tier.min_qty > applicableTier.min_qty) {
                        applicableTier = tier;
                    }
                }
            });
            if (applicableTier) {
                price = parseFloat(applicableTier.wholesale_price);
                isWholesale = true;
            }
        }
        return { price, isWholesale };
    }

    // --- Render Cart ---
    function renderCart() {
        const desktopContainer = document.getElementById('cart-container-desktop');
        const badgeCount = document.getElementById('cart-item-count-badge');
        const receiptTotalDesktop = document.getElementById('receipt-total-desktop');

        if (cart.length === 0) {
            const emptyState = `
                <div class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-cart-shopping text-xl text-slate-400"></i>
                    </div>
                    <p class="font-bold text-slate-700 text-sm mb-1">Keranjang Masih Kosong</p>
                    <p class="text-xs text-slate-400">Pilih produk atau layanan dari katalog untuk memulai transaksi.</p>
                </div>
            `;
            if (desktopContainer) desktopContainer.innerHTML = emptyState;
            if (badgeCount) badgeCount.innerText = '0 item';
            if (receiptTotalDesktop) receiptTotalDesktop.innerText = 'Rp 0';
            window.currentGrandTotal = 0;
            
            window.dispatchEvent(new CustomEvent('cart-total-changed', { detail: { total: 0, count: 0 } }));
            return;
        }

        let totalQty = 0;
        let grandTotal = 0;
        let cartHtml = '<div class="space-y-2">';

        cart.forEach(item => {
            const { price: basePrice, isWholesale } = getUnitPrice(item.retail_price, item.wholesale_prices, item.qty);
            
            let standardUnitPrice = basePrice;
            if (item.is_custom_banner && (item.billable_area_m2 || item.area_m2)) {
                const area = item.billable_area_m2 || item.area_m2;
                const extraEyelet = (item.extra_eyelet_cost !== undefined) 
                    ? item.extra_eyelet_cost 
                    : (item.eyelet_count > 4 ? (item.eyelet_count - 4) * 500 : 0);
                standardUnitPrice = Math.round(area * basePrice) + extraEyelet;
            }

            const isNegotiated = (item.custom_unit_price !== undefined && item.custom_unit_price !== null);
            const finalUnitPrice = isNegotiated ? item.custom_unit_price : standardUnitPrice;

            const itemTotal = finalUnitPrice * item.qty;
            
            totalQty += item.qty;
            grandTotal += itemTotal;

            cartHtml += `
                <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex flex-col gap-1.5">
                    <div class="flex justify-between items-start gap-2">
                        <div class="min-w-0 flex-grow">
                            <span class="font-bold text-slate-900 text-xs truncate block">${item.material_name_or_type}</span>
                            
                            ${item.is_custom_banner ? `
                                <div class="mt-0.5">
                                    <span class="badge bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-mono font-semibold py-0.5 px-1.5 rounded inline-block text-wrap">
                                        <i class="fa-solid fa-ruler-combined me-1"></i>${item.dimension_text || (item.fixed_length_m + 'm x ' + item.custom_width_cm + 'cm')}
                                    </span>
                                </div>
                            ` : (item.requested_size ? `<span class="block text-[10px] text-blue-600 font-medium">Ukuran: ${item.requested_size}m</span>` : '')}
                            
                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                <span class="text-[11px] font-mono text-slate-700 font-bold">
                                    @ Rp ${Number(finalUnitPrice).toLocaleString('id-ID')}
                                    ${(!isNegotiated && item.is_custom_banner) ? `<small class="text-slate-400 font-normal">(${Number(basePrice).toLocaleString('id-ID')}/m²)</small>` : ''}
                                </span>
                                ${isNegotiated ? `
                                    <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-800 border border-amber-300 text-[9px] font-bold px-1.5 py-0.5 rounded font-mono">
                                        <i class="fa-solid fa-handshake text-amber-600 text-[9px]"></i>
                                        <span>Nego (Asli: Rp ${Number(standardUnitPrice).toLocaleString('id-ID')})</span>
                                        <button type="button" onclick="event.stopPropagation(); resetItemNegotiation(${item.id})" class="text-amber-700 hover:text-rose-600 bg-transparent border-0 p-0 ms-0.5 font-bold cursor-pointer text-[10px]" title="Batalkan Nego Item">&times;</button>
                                    </span>
                                ` : ''}
                                ${(!isNegotiated && isWholesale) ? `<span class="text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 px-1 rounded">Grosir</span>` : ''}
                            </div>
                        </div>
                        
                        <div class="text-right flex-shrink-0">
                            <span class="font-bold font-mono text-xs text-slate-900">Rp ${Number(itemTotal).toLocaleString('id-ID')}</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center border-t border-slate-100 pt-1.5 mt-0.5">
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" onclick="updateQty(${item.id}, -${item.qty})" class="text-[10px] text-rose-500 hover:text-rose-700 font-semibold bg-transparent border-0 cursor-pointer p-0">
                                <i class="fa-solid fa-trash-can me-0.5"></i> Hapus
                            </button>
                            ${item.is_custom_banner ? `
                                <button type="button" onclick="editBannerCartItem(${item.id})" class="text-[10px] text-blue-600 hover:text-blue-800 font-semibold bg-transparent border-0 cursor-pointer p-0">
                                    <i class="fa-solid fa-pen-ruler me-0.5"></i> Ubah Ukuran
                                </button>
                            ` : ''}
                            <button type="button" onclick="openItemNegotiationModal(${item.id})" class="text-[10px] font-bold bg-transparent border-0 cursor-pointer p-0 ${isNegotiated ? 'text-amber-700 hover:text-amber-900' : 'text-blue-600 hover:text-blue-800'}" title="Negosiasi harga satuan item">
                                <i class="fa-solid fa-handshake me-0.5"></i> ${isNegotiated ? 'Ubah Nego' : 'Nego'}
                            </button>
                        </div>
                        
                        <div class="flex items-center border border-slate-300 rounded-lg overflow-hidden bg-white shadow-sm flex-shrink-0">
                            <button type="button" onclick="updateQty(${item.id}, -1)" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition font-bold text-xs bg-transparent border-0 cursor-pointer" title="Kurangi 1">-</button>
                            <input type="number" min="1" value="${item.qty}" onchange="setQty(${item.id}, this.value)" onkeydown="if(event.key === 'Enter'){ this.blur(); }" class="w-10 h-7 text-center font-bold font-mono text-xs text-slate-900 border-x border-slate-200 bg-slate-50 focus:bg-white focus:outline-none p-0" title="Ketik jumlah unit">
                            <button type="button" onclick="updateQty(${item.id}, 1)" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition font-bold text-xs bg-transparent border-0 cursor-pointer" title="Tambah 1">+</button>
                        </div>
                    </div>
                </div>
            `;
        });

        cartHtml += '</div>';

        if (desktopContainer) desktopContainer.innerHTML = cartHtml;
        if (badgeCount) badgeCount.innerText = `${totalQty} item`;
        window.currentAccumulatedTotal = grandTotal;
        const finalAfterDiscount = Math.max(0, grandTotal - (window.negotiationDiscount || 0));
        window.currentGrandTotal = finalAfterDiscount;

        const negoRow = document.getElementById('nego-discount-row');
        const negoFinalRow = document.getElementById('nego-final-row');
        const negoDiscountText = document.getElementById('nego-discount-text');
        const negoFinalText = document.getElementById('nego-final-text');

        if (window.negotiationDiscount > 0 && grandTotal > 0) {
            if (negoRow) {
                negoRow.classList.remove('hidden');
                if (negoDiscountText) negoDiscountText.innerText = `- Rp ${Number(window.negotiationDiscount).toLocaleString('id-ID')}`;
            }
            if (negoFinalRow) {
                negoFinalRow.classList.remove('hidden');
                if (negoFinalText) negoFinalText.innerText = `Rp ${Number(finalAfterDiscount).toLocaleString('id-ID')}`;
            }
            if (receiptTotalDesktop) {
                receiptTotalDesktop.innerHTML = `<span class="line-through text-slate-400 font-normal me-1">Rp ${Number(grandTotal).toLocaleString('id-ID')}</span> <span class="text-blue-900 font-bold">Rp ${Number(finalAfterDiscount).toLocaleString('id-ID')}</span>`;
            }
        } else {
            if (negoRow) negoRow.classList.add('hidden');
            if (negoFinalRow) negoFinalRow.classList.add('hidden');
            if (receiptTotalDesktop) receiptTotalDesktop.innerText = `Rp ${Number(grandTotal).toLocaleString('id-ID')}`;
        }

        window.dispatchEvent(new CustomEvent('cart-total-changed', { detail: { total: finalAfterDiscount, count: totalQty } }));
    }

    // --- Category & Search Combined Filtering ---
    let currentCategory = 'all';

    function filterCategory(category, btnElement) {
        currentCategory = (category || 'all').toString().trim();

        // Update Button Active Styles
        document.querySelectorAll('.category-filter-btn').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600', 'active', 'font-semibold');
            btn.classList.add('bg-white', 'text-slate-700', 'border-slate-200', 'font-medium');
            const countBadge = btn.querySelector('span:last-child');
            if (countBadge) {
                countBadge.classList.remove('bg-white/20', 'text-white');
                countBadge.classList.add('bg-slate-100', 'text-slate-600');
            }
        });

        const activeBtn = btnElement || document.querySelector(`.category-filter-btn[data-cat="${currentCategory}"]`);
        if (activeBtn) {
            activeBtn.classList.remove('bg-white', 'text-slate-700', 'border-slate-200', 'font-medium');
            activeBtn.classList.add('bg-blue-600', 'text-white', 'border-blue-600', 'active', 'font-semibold');
            const countBadge = activeBtn.querySelector('span:last-child');
            if (countBadge) {
                countBadge.classList.remove('bg-slate-100', 'text-slate-600');
                countBadge.classList.add('bg-white/20', 'text-white');
            }
        }

        applyCombinedFilter();
    }

    function filterProducts() {
        applyCombinedFilter();
    }

    function applyCombinedFilter() {
        const searchInput = document.getElementById('product-search');
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const cards = document.querySelectorAll('.product-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const name = (card.getAttribute('data-name') || '').toLowerCase();
            const cat = (card.getAttribute('data-category') || '').trim();

            const matchQuery = !query || name.includes(query);
            const matchCat = (currentCategory === 'all') || (cat.toLowerCase() === currentCategory.toLowerCase());

            if (matchQuery && matchCat) {
                card.style.display = 'flex';
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.style.display = 'none';
                card.classList.add('hidden');
            }
        });

        const emptyState = document.getElementById('products-empty-state');
        if (emptyState) {
            if (visibleCount === 0) {
                emptyState.classList.remove('hidden');
                emptyState.style.display = 'block';
            } else {
                emptyState.classList.add('hidden');
                emptyState.style.display = 'none';
            }
        }
    }

    window.filterCategory = filterCategory;
    window.filterProducts = filterProducts;
    window.applyCombinedFilter = applyCombinedFilter;

    // --- Switch Selected Payment Tile UI ---
    function setPaymentMethod(method) {
        document.getElementById('global_payment_method').value = method;

        ['Cash', 'Transfer', 'QRIS'].forEach(m => {
            const btnD = document.getElementById(`pm-desktop-${m}`);
            const btnM = document.getElementById(`pm-mobile-${m}`);

            if (m === method) {
                if (btnD) {
                    btnD.classList.add('border-blue-600', 'bg-blue-50', 'text-blue-700', 'ring-2', 'ring-blue-500/20');
                    btnD.classList.remove('border-slate-200', 'bg-white', 'text-slate-600');
                }
                if (btnM) {
                    btnM.classList.add('border-blue-600', 'bg-blue-50', 'text-blue-700', 'ring-2', 'ring-blue-500/20');
                    btnM.classList.remove('border-slate-200', 'bg-white', 'text-slate-600');
                }
            } else {
                if (btnD) {
                    btnD.classList.remove('border-blue-600', 'bg-blue-50', 'text-blue-700', 'ring-2', 'ring-blue-500/20');
                    btnD.classList.add('border-slate-200', 'bg-white', 'text-slate-600');
                }
                if (btnM) {
                    btnM.classList.remove('border-blue-600', 'bg-blue-50', 'text-blue-700', 'ring-2', 'ring-blue-500/20');
                    btnM.classList.add('border-slate-200', 'bg-white', 'text-slate-600');
                }
            }
        });
    }

    // Initialize Default Payment Method (Cash)
    setPaymentMethod('Cash');

    // --- Mobile Drawer Animation Controller ---
    function toggleMobileCartDrawer(open) {
        const drawer = document.getElementById('mobile-cart-drawer');
        const panel = document.getElementById('mobile-drawer-panel');

        if (open) {
            drawer.classList.remove('hidden');
            setTimeout(() => {
                drawer.classList.remove('opacity-0');
                panel.classList.remove('translate-y-full');
            }, 10);
        } else {
            drawer.classList.add('opacity-0');
            panel.classList.add('translate-y-full');
            setTimeout(() => {
                drawer.classList.add('hidden');
            }, 300);
        }
    }

    // --- Negotiation State and Functions (Per-Item Negotiation) ---
    window.negotiationDiscount = 0;
    window.negotiationNotes = '';
    let activeNegoCartItemId = null;
    let activeNegoStandardUnitPrice = 0;
    let activeNegoQty = 1;

    function openItemNegotiationModal(cartItemId) {
        const item = cart.find(i => i.id === cartItemId);
        if (!item) return;

        activeNegoCartItemId = cartItemId;
        activeNegoQty = item.qty || 1;

        // Calculate standard unit price
        const { price: basePrice } = getUnitPrice(item.retail_price, item.wholesale_prices, item.qty);
        let standardUnitPrice = basePrice;
        if (item.is_custom_banner && (item.billable_area_m2 || item.area_m2)) {
            const area = item.billable_area_m2 || item.area_m2;
            const extraEyelet = (item.extra_eyelet_cost !== undefined) 
                ? item.extra_eyelet_cost 
                : (item.eyelet_count > 4 ? (item.eyelet_count - 4) * 500 : 0);
            standardUnitPrice = Math.round(area * basePrice) + extraEyelet;
        }
        activeNegoStandardUnitPrice = standardUnitPrice;

        // Set modal displays
        const nameEl = document.getElementById('item_nego_item_name');
        const nameDispEl = document.getElementById('item_nego_name_display');
        const specsDispEl = document.getElementById('item_nego_specs_display');
        const qtyDispEl = document.getElementById('item_nego_qty_display');
        const origUnitEl = document.getElementById('item_nego_orig_unit');
        const origSubtotalEl = document.getElementById('item_nego_orig_subtotal');

        if (nameEl) nameEl.innerText = item.material_name_or_type;
        if (nameDispEl) nameDispEl.innerText = item.material_name_or_type;
        if (specsDispEl) specsDispEl.innerText = item.dimension_text || (item.requested_size ? `Ukuran: ${item.requested_size}m` : '');
        if (qtyDispEl) qtyDispEl.innerText = `${item.qty} pcs`;

        if (origUnitEl) origUnitEl.innerText = `Rp ${Number(standardUnitPrice).toLocaleString('id-ID')} / pcs`;
        if (origSubtotalEl) origSubtotalEl.innerText = `Rp ${Number(standardUnitPrice * item.qty).toLocaleString('id-ID')}`;

        // Current price (negotiated or standard)
        const currentPrice = (item.custom_unit_price !== undefined && item.custom_unit_price !== null) 
            ? item.custom_unit_price 
            : standardUnitPrice;
        
        const inputEl = document.getElementById('item_nego_unit_input');
        if (inputEl) inputEl.value = currentPrice;

        calcItemNegoPreview();

        // Show modal
        const modalEl = document.getElementById('modalItemNegotiation');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    function calcItemNegoPreview() {
        const inputEl = document.getElementById('item_nego_unit_input');
        let inputVal = parseFloat(inputEl?.value);
        if (isNaN(inputVal) || inputVal < 0) inputVal = activeNegoStandardUnitPrice;

        const subtotal = inputVal * activeNegoQty;
        const diff = activeNegoStandardUnitPrice - inputVal;

        const prevSubtotal = document.getElementById('item_nego_preview_subtotal');
        if (prevSubtotal) prevSubtotal.innerText = `Rp ${Number(subtotal).toLocaleString('id-ID')}`;
        
        const diffEl = document.getElementById('item_nego_preview_diff');
        if (diffEl) {
            if (diff > 0) {
                diffEl.innerText = `- Rp ${Number(diff).toLocaleString('id-ID')} / pcs (Hemat Rp ${Number(diff * activeNegoQty).toLocaleString('id-ID')})`;
                diffEl.className = 'font-mono font-bold text-emerald-700';
            } else if (diff < 0) {
                diffEl.innerText = `+ Rp ${Number(Math.abs(diff)).toLocaleString('id-ID')} / pcs`;
                diffEl.className = 'font-mono font-bold text-amber-700';
            } else {
                diffEl.innerText = 'Sama dengan harga standar (Rp 0)';
                diffEl.className = 'font-mono text-slate-500';
            }
        }
    }

    function adjustItemNegoDelta(delta) {
        const input = document.getElementById('item_nego_unit_input');
        let cur = parseFloat(input?.value) || activeNegoStandardUnitPrice;
        let next = Math.max(0, cur + delta);
        if (input) input.value = next;
        calcItemNegoPreview();
    }

    function applyItemNegoPercent(pct) {
        const input = document.getElementById('item_nego_unit_input');
        let next = Math.round(activeNegoStandardUnitPrice * (1 - pct / 100));
        if (input) input.value = Math.max(0, next);
        calcItemNegoPreview();
    }

    function resetItemNegoModalToOrig() {
        const input = document.getElementById('item_nego_unit_input');
        if (input) input.value = activeNegoStandardUnitPrice;
        calcItemNegoPreview();
    }

    function applyItemNegotiation() {
        if (activeNegoCartItemId === null) return;
        const item = cart.find(i => i.id === activeNegoCartItemId);
        if (!item) return;

        let inputVal = parseFloat(document.getElementById('item_nego_unit_input')?.value);
        if (isNaN(inputVal) || inputVal < 0) {
            inputVal = activeNegoStandardUnitPrice;
        }

        if (inputVal === activeNegoStandardUnitPrice) {
            delete item.custom_unit_price;
        } else {
            item.custom_unit_price = inputVal;
        }

        renderCart();

        const modalEl = document.getElementById('modalItemNegotiation');
        if (modalEl) {
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }

        // If hub modal is open, refresh its list
        renderNegoItemsList();
    }

    function resetItemNegotiationActive() {
        if (activeNegoCartItemId === null) return;
        const item = cart.find(i => i.id === activeNegoCartItemId);
        if (item) {
            delete item.custom_unit_price;
            renderCart();
        }
        const modalEl = document.getElementById('modalItemNegotiation');
        if (modalEl) {
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }
        renderNegoItemsList();
    }

    function resetItemNegotiation(cartItemId) {
        const item = cart.find(i => i.id === cartItemId);
        if (item) {
            delete item.custom_unit_price;
            renderCart();
        }
        renderNegoItemsList();
    }

    function resetAllItemNegotiations() {
        cart.forEach(item => {
            delete item.custom_unit_price;
        });
        window.negotiationDiscount = 0;
        window.negotiationNotes = '';
        renderCart();
        renderNegoItemsList();
        const modalEl = document.getElementById('modalNegotiation');
        if (modalEl) {
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }
    }

    function openNegotiationModal() {
        if (!cart || cart.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Keranjang Kosong', text: 'Pilih produk terlebih dahulu sebelum melakukan negosiasi harga.' });
            return;
        }

        renderNegoItemsList();

        const modalEl = document.getElementById('modalNegotiation');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    function renderNegoItemsList() {
        const container = document.getElementById('nego-items-list-container');
        if (!container) return;
        if (!cart || cart.length === 0) {
            container.innerHTML = '<div class="text-center text-slate-400 py-4 text-xs">Keranjang masih kosong</div>';
            return;
        }

        let html = '';
        cart.forEach(item => {
            const { price: basePrice } = getUnitPrice(item.retail_price, item.wholesale_prices, item.qty);
            let standardUnitPrice = basePrice;
            if (item.is_custom_banner && (item.billable_area_m2 || item.area_m2)) {
                const area = item.billable_area_m2 || item.area_m2;
                const extraEyelet = (item.extra_eyelet_cost !== undefined) 
                    ? item.extra_eyelet_cost 
                    : (item.eyelet_count > 4 ? (item.eyelet_count - 4) * 500 : 0);
                standardUnitPrice = Math.round(area * basePrice) + extraEyelet;
            }
            const isNegotiated = (item.custom_unit_price !== undefined && item.custom_unit_price !== null);
            const currentUnitPrice = isNegotiated ? item.custom_unit_price : standardUnitPrice;

            html += `
                <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-slate-900 text-xs truncate">${item.material_name_or_type}</div>
                        <div class="text-[10px] text-slate-500">${item.dimension_text || (item.requested_size ? 'Ukuran: ' + item.requested_size + 'm' : '')} &bull; <strong class="text-slate-700">${item.qty} pcs</strong></div>
                        <div class="mt-1 flex items-center gap-1.5 font-mono text-xs">
                            <span class="text-slate-500">Harga Satuan:</span>
                            <strong class="${isNegotiated ? 'text-amber-800' : 'text-slate-800'}">Rp ${Number(currentUnitPrice).toLocaleString('id-ID')}</strong>
                            ${isNegotiated ? `<span class="badge bg-amber-100 text-amber-800 text-[9px] px-1 py-0.5 font-sans">Nego (Asli: Rp ${Number(standardUnitPrice).toLocaleString('id-ID')})</span>` : ''}
                        </div>
                    </div>
                    <div class="flex-shrink-0 flex items-center gap-1.5">
                        <button type="button" onclick="openItemNegotiationModal(${item.id})" class="btn btn-sm ${isNegotiated ? 'btn-amber-50 text-amber-900 border border-amber-300' : 'btn-primary'} text-xs py-1 px-2.5 font-bold">
                            <i class="fa-solid fa-handshake me-1"></i> ${isNegotiated ? 'Ubah Nego' : 'Atur Nego'}
                        </button>
                        ${isNegotiated ? `
                            <button type="button" onclick="resetItemNegotiation(${item.id})" class="btn btn-sm btn-light border text-rose-600 text-xs py-1 px-2" title="Reset ke Normal">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    function applyNegotiationAndDraft() {
        const modalEl = document.getElementById('modalNegotiation');
        if (modalEl) {
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }
        setTimeout(() => {
            processCheckout(true);
        }, 300);
    }

    function resetNegotiation() {
        resetAllItemNegotiations();
    }

    // --- Draft Orders Management (Cashier) ---
    function openDraftOrdersModal() {
        const modalEl = document.getElementById('modalDraftOrders');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
            loadDraftOrders();
        }
    }

    function loadDraftOrders() {
        const loading = document.getElementById('draft-orders-loading');
        const list = document.getElementById('draft-orders-list');
        const empty = document.getElementById('draft-orders-empty');

        if (loading) loading.classList.remove('hidden');
        if (list) list.classList.add('hidden');
        if (empty) empty.classList.add('hidden');

        fetch('{{ route("pos.drafts") }}')
            .then(res => res.json())
            .then(data => {
                if (loading) loading.classList.add('hidden');
                const drafts = data.drafts || [];
                updateDraftBadge(drafts.length);

                if (drafts.length === 0) {
                    if (empty) empty.classList.remove('hidden');
                    return;
                }

                if (list) {
                    list.innerHTML = '';
                    drafts.forEach(d => {
                        const itemsSummary = (d.transaction_details || []).map(it => 
                            `<span class="badge bg-slate-100 text-slate-700 border text-[10px] me-1 mb-1">${it.qty_ordered}x ${it.material?.material_name || 'Item'} (@ Rp ${Number(it.selling_price).toLocaleString('id-ID')})</span>`
                        ).join('');

                        const card = document.createElement('div');
                        card.className = 'bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 hover:border-amber-400 transition';
                        card.innerHTML = `
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono font-bold text-amber-900 text-xs">${d.invoice_number}</span>
                                    <span class="badge bg-amber-50 text-amber-700 border border-amber-300 text-[9px] font-extrabold uppercase">DRAFT</span>
                                    <span class="text-[10px] text-slate-400">${new Date(d.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</span>
                                </div>
                                <div class="font-bold text-slate-800 text-xs">${d.customer_name || 'Pelanggan Umum'} ${d.customer_phone ? `<small class="text-slate-500 font-mono">(${d.customer_phone})</small>` : ''}</div>
                                <div class="mt-1 flex flex-wrap gap-1">${itemsSummary}</div>
                                ${d.negotiation_notes ? `<div class="text-[10px] text-emerald-700 mt-1"><i class="fa-solid fa-handshake me-1"></i>Catatan Nego: ${d.negotiation_notes}</div>` : ''}
                                <div class="text-[10px] text-slate-400 mt-1">Dibuat oleh: <strong class="text-slate-600">${d.user?.full_name || d.user?.username || 'Operator'}</strong></div>
                            </div>
                            <div class="text-right flex-shrink-0 w-full sm:w-auto flex sm:flex-col justify-between sm:justify-center items-center sm:items-end gap-2 border-t sm:border-t-0 pt-2 sm:pt-0">
                                <div>
                                    <span class="text-[10px] text-slate-400 block">Total Tagihan</span>
                                    <span class="font-mono font-extrabold text-blue-900 text-sm">Rp ${Number(d.total_price).toLocaleString('id-ID')}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <a href="/invoices/${d.invoice_number}" target="_blank" class="btn btn-sm btn-outline-secondary text-xs py-1.5 px-2.5 font-semibold" title="Buka Faktur Draft">
                                        <i class="fa-solid fa-file-invoice text-slate-600"></i> Faktur
                                    </a>
                                    <button type="button" onclick="promptSettleDraft(${d.id}, '${d.invoice_number}', ${d.total_price})" class="btn btn-sm btn-primary text-xs py-1.5 px-3 font-bold flex items-center gap-1.5 shadow-sm">
                                        <i class="fa-solid fa-cash-register"></i> Bayar
                                    </button>
                                </div>
                            </div>
                        `;
                        list.appendChild(card);
                    });
                    list.classList.remove('hidden');
                }
            })
            .catch(err => {
                if (loading) loading.classList.add('hidden');
                console.error(err);
            });
    }

    function updateDraftBadge(count) {
        const badge = document.getElementById('draft-counter-badge');
        if (badge) {
            badge.innerText = count;
            if (count > 0) {
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }

    function promptSettleDraft(draftId, invNumber, totalPrice) {
        Swal.fire({
            title: `<span style="font-size: 16px; font-weight: 800; color: #0f172a;">Pelunasan Draft #${invNumber}</span>`,
            html: `
                <div style="text-align: left; font-size: 12px;" class="space-y-3">
                    <div style="background: #eff6ff; padding: 8px 12px; border-radius: 8px; border: 1px solid #bfdbfe;">
                        <span style="color: #1e3a8a; font-weight: bold;">Total Tagihan:</span>
                        <span style="float: right; font-family: monospace; font-size: 14px; font-weight: 800; color: #1d4ed8;">Rp ${Number(totalPrice).toLocaleString('id-ID')}</span>
                    </div>

                    <div>
                        <label style="font-weight: bold; color: #334155; margin-bottom: 4px; display: block;">Metode Pembayaran:</label>
                        <select id="settle_pm" class="form-select form-select-sm" style="font-size: 12px;">
                            <option value="Cash">Tunai (Cash)</option>
                            <option value="Transfer">Transfer Bank</option>
                            <option value="QRIS">QRIS</option>
                        </select>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 cursor-pointer mb-1">
                            <input type="checkbox" id="settle_is_dp" onchange="document.getElementById('settle_dp_wrap').style.display = this.checked ? 'block' : 'none';" class="form-check-input">
                            <span style="font-weight: 600; color: #334155;">Bayar Sebagai DP (Uang Muka)</span>
                        </label>
                        <div id="settle_dp_wrap" style="display: none; margin-top: 6px;">
                            <label style="font-size: 11px; color: #64748b;">Nominal DP (Minimal 50% = Rp ${Number(Math.round(totalPrice * 0.5)).toLocaleString('id-ID')}):</label>
                            <input type="number" id="settle_dp_amount" min="${Math.round(totalPrice * 0.5)}" value="${Math.round(totalPrice * 0.5)}" class="form-control form-control-sm" style="font-family: monospace;">
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Proses Pelunasan',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                const pm = document.getElementById('settle_pm').value;
                const isDp = document.getElementById('settle_is_dp').checked;
                const dpAmount = isDp ? parseFloat(document.getElementById('settle_dp_amount').value) || 0 : 0;

                if (isDp && dpAmount < Math.round(totalPrice * 0.5)) {
                    Swal.showValidationMessage(`Nominal DP minimal Rp ${Number(Math.round(totalPrice * 0.5)).toLocaleString('id-ID')}`);
                    return false;
                }

                return { payment_method: pm, is_dp: isDp, dp_amount: dpAmount };
            }
        }).then((res) => {
            if (res.isConfirmed && res.value) {
                executeSettleDraft(draftId, res.value);
            }
        });
    }

    function executeSettleDraft(draftId, payload) {
        Swal.fire({
            title: 'Memproses Pembayaran...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        fetch(`/pos/drafts/${draftId}/settle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.success === true) {
                loadDraftOrders();
                Swal.fire({
                    icon: 'success',
                    title: 'Pembayaran Berhasil!',
                    html: `
                        <p style="font-size: 13px; color: #475569;">Draft <strong>#${data.invoice_number}</strong> telah resmi dilunasi dan stok dikurangi.</p>
                        <div class="d-flex gap-2 justify-content-center mt-3">
                            <a href="${data.receipt_url}" target="_blank" class="btn btn-sm btn-primary text-xs py-1.5 px-3">
                                <i class="fa-solid fa-print me-1"></i> Cetak Struk
                            </a>
                            <a href="${data.public_invoice_url}" target="_blank" class="btn btn-sm btn-outline-primary text-xs py-1.5 px-3">
                                <i class="fa-solid fa-file-pdf me-1"></i> Unduh Faktur PDF
                            </a>
                        </div>
                    `,
                    showConfirmButton: true,
                    confirmButtonText: 'Selesai'
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal Memproses', text: data.message || 'Terjadi kesalahan' });
            }
        })
        .catch(err => {
            Swal.fire({ icon: 'error', title: 'Kesalahan Sistem', text: err.message });
        });
    }

    // --- Checkout Logic via Fetch AJAX with Pre-Payment Confirmation Popup ---
    function processCheckout(isDraft = false) {
        if (cart.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Keranjang Kosong', text: 'Pilih produk terlebih dahulu.' });
            return;
        }

        const posContainer = document.getElementById('pos-main-container');
        const alpineData = (posContainer && window.Alpine) ? Alpine.$data(posContainer) : {};

        const isDp = !isDraft && (alpineData ? (alpineData.isDp && alpineData.isEligibleForDp) : false);
        const dpAmount = isDp ? (parseFloat(alpineData.dpAmount) || 0) : 0;
        const dueDate = isDp ? (alpineData.dueDate || null) : null;
        const productionNotes = alpineData ? (alpineData.productionNotes || null) : null;
        const customerId = alpineData ? (alpineData.customerId || null) : null;
        const customerName = alpineData ? (alpineData.customerName || '') : '';
        const customerPhone = alpineData ? (alpineData.customerPhone || '') : '';
        const customerEmail = alpineData ? (alpineData.customerEmail || '') : '';

        const currentGrand = window.currentGrandTotal || 0;

        if (isDp) {
            const minAllowedDp = alpineData ? alpineData.minDpAmount : Math.round(currentGrand * 0.5);
            if (dpAmount < minAllowedDp) {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Nominal DP Kurang dari 50%', 
                    text: 'Nominal uang muka (DP) minimal 50% dari total pesanan (Minimal: Rp ' + Number(minAllowedDp).toLocaleString('id-ID') + ').' 
                });
                return;
            }
        }

        const paymentMethod = isDraft ? 'Draft' : (document.getElementById('global_payment_method').value || 'Cash');
        const errContainerDesktop = document.getElementById('checkout-error-desktop');
        const errContainerMobile = document.getElementById('checkout-error-mobile');
        const successContainerDesktop = document.getElementById('checkout-success-desktop');
        const successContainerMobile = document.getElementById('checkout-success-mobile');
        
        const btnDesktop = document.getElementById('checkout-btn-desktop');
        const btnMobile = document.getElementById('checkout-btn-mobile');

        // Format items payload for PosController & Build itemized summary HTML
        let itemsTableRows = '';
        let totalItemCount = 0;
        let calculatedGrandTotal = 0;

        const payloadItems = cart.map((item, idx) => {
            const { price: basePrice, isWholesale } = getUnitPrice(item.retail_price, item.wholesale_prices, item.qty);
            let standardUnitPrice = basePrice;
            if (item.is_custom_banner && (item.billable_area_m2 || item.area_m2)) {
                const area = item.billable_area_m2 || item.area_m2;
                const extraEyelet = (item.extra_eyelet_cost !== undefined) 
                    ? item.extra_eyelet_cost 
                    : (item.eyelet_count > 4 ? (item.eyelet_count - 4) * 500 : 0);
                standardUnitPrice = Math.round(area * basePrice) + extraEyelet;
            }
            const isNegotiated = (item.custom_unit_price !== undefined && item.custom_unit_price !== null);
            const finalUnitPrice = isNegotiated ? item.custom_unit_price : standardUnitPrice;

            const itemSubtotal = finalUnitPrice * item.qty;
            totalItemCount += item.qty;
            calculatedGrandTotal += itemSubtotal;

            itemsTableRows += `
                <tr style="border-bottom: 1px solid #e2e8f0; font-size: 11px;">
                    <td style="padding: 6px 8px; text-align: left; vertical-align: middle;">
                        <strong style="color: #1e293b;">${idx + 1}. ${item.material_name_or_type}</strong>
                        ${item.is_custom_banner ? `<div style="color: #2563eb; font-size: 10px; font-weight: 600;">${item.dimension_text || (item.fixed_length_m + 'm x ' + item.custom_width_cm + 'cm')}</div>` : (item.requested_size ? `<div style="color: #2563eb; font-size: 10px;">Ukuran: ${item.requested_size}m</div>` : '')}
                        ${isNegotiated ? `<div style="color: #b45309; font-size: 9.5px; font-weight: bold;"><i class="fa-solid fa-handshake me-0.5"></i> Nego: Rp ${Number(finalUnitPrice).toLocaleString('id-ID')}</div>` : ''}
                    </td>
                    <td style="padding: 6px 8px; text-align: center; vertical-align: middle; color: #475569; font-weight: bold;">
                        ${item.qty}x
                    </td>
                    <td style="padding: 6px 8px; text-align: right; vertical-align: middle; color: #475569; font-family: monospace;">
                        Rp ${Number(finalUnitPrice).toLocaleString('id-ID')}
                    </td>
                    <td style="padding: 6px 8px; text-align: right; vertical-align: middle; font-weight: bold; color: #0f172a; font-family: monospace;">
                        Rp ${Number(itemSubtotal).toLocaleString('id-ID')}
                    </td>
                </tr>
            `;

            return {
                material_id: item.material_id || null,
                material_name_or_type: item.material_name_or_type,
                requested_size: item.requested_size,
                width_m: item.width_m || item.fixed_length_m || null,
                length_m: item.length_m || (item.custom_width_cm ? item.custom_width_cm / 100 : null),
                fixed_length_m: item.fixed_length_m || null,
                custom_width_cm: item.custom_width_cm || null,
                area_m2: item.area_m2 || null,
                billable_area_m2: item.billable_area_m2 || null,
                is_custom_banner: !!item.is_custom_banner,
                custom_unit_price: isNegotiated ? item.custom_unit_price : null,
                eyelet_count: item.eyelet_count || 0,
                extra_eyelet_cost: (item.extra_eyelet_cost !== undefined) ? item.extra_eyelet_cost : (item.eyelet_count > 4 ? (item.eyelet_count - 4) * 500 : 0),
                finishing: item.finishing || null,
                dimension_text: item.dimension_text || null,
                qty: item.qty
            };
        });

        const finalAfterNego = calculatedGrandTotal;

        // Customer & Breakdown Formatting
        const customerDisplay = customerName 
            ? `<strong style="color: #1e3a8a;">${customerName}</strong> ${customerPhone ? '<span style="color: #64748b; font-size: 10px;">(' + customerPhone + ')</span>' : ''}` 
            : '<span style="color: #94a3b8; font-style: italic;">Umum / Non-Member</span>';

        let financialSummaryHtml = '';
        if (isDraft) {
            financialSummaryHtml = `
                <div style="background: #fffbeb; border-radius: 8px; padding: 10px 12px; border: 1px solid #fde68a; margin-top: 10px;">
                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                        <span style="color: #92400e; font-weight: bold;">Status Draft:</span>
                        <strong style="color: #b45309;">BELUM DIBAYAR</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 800; color: #1e3a8a; border-top: 1px dashed #f59e0b; padding-top: 4px; margin-top: 4px;">
                        <span>Total Tagihan Pesanan:</span>
                        <span style="font-family: monospace;">Rp ${Number(finalAfterNego).toLocaleString('id-ID')}</span>
                    </div>
                </div>
            `;
        } else if (isDp) {
            const sisaPiutang = finalAfterNego - dpAmount;
            const dpPercent = finalAfterNego > 0 ? Math.round((dpAmount / finalAfterNego) * 100) : 0;
            financialSummaryHtml = `
                <div style="background: #f8fafc; border-radius: 8px; padding: 10px 12px; border: 1px solid #e2e8f0; margin-top: 10px;">
                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                        <span style="color: #64748b;">Total Nilai Pesanan:</span>
                        <strong style="font-family: monospace; color: #0f172a;">Rp ${Number(finalAfterNego).toLocaleString('id-ID')}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: #059669; font-weight: bold; margin-bottom: 4px;">
                        <span>Uang Muka (DP ${dpPercent}%):</span>
                        <span style="font-family: monospace;">Rp ${Number(dpAmount).toLocaleString('id-ID')}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: #d97706; font-weight: bold; padding-top: 4px; border-top: 1px dashed #cbd5e1;">
                        <span>Sisa Piutang (Pelunasan):</span>
                        <span style="font-family: monospace;">Rp ${Number(sisaPiutang).toLocaleString('id-ID')}</span>
                    </div>
                    ${dueDate ? `<div style="font-size: 10.5px; color: #475569; margin-top: 6px; padding-top: 4px; border-top: 1px solid #f1f5f9;"><i class="fa-solid fa-calendar-day text-amber-500 me-1"></i> Target Selesai: <strong>${dueDate}</strong></div>` : ''}
                </div>
            `;
        } else {
            financialSummaryHtml = `
                <div style="background: #eff6ff; border-radius: 8px; padding: 10px 12px; border: 1px solid #bfdbfe; margin-top: 10px;">
                    ${(window.negotiationDiscount > 0) ? `
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b; margin-bottom: 2px;">
                            <span>Total Asli:</span>
                            <span style="font-family: monospace; text-decoration: line-through;">Rp ${Number(calculatedGrandTotal).toLocaleString('id-ID')}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: #059669; font-weight: bold; margin-bottom: 4px;">
                            <span>Potongan Nego:</span>
                            <span style="font-family: monospace;">- Rp ${Number(window.negotiationDiscount).toLocaleString('id-ID')}</span>
                        </div>
                    ` : ''}
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #dbeafe; padding-top: 4px;">
                        <div>
                            <div style="font-size: 11px; font-weight: 700; color: #1e3a8a; text-transform: uppercase;">Total Tagihan Kasir:</div>
                            <div style="font-size: 10px; color: #3b82f6;">${totalItemCount} item dalam keranjang</div>
                        </div>
                        <span style="font-size: 16px; font-weight: 800; color: #1d4ed8; font-family: monospace;">Rp ${Number(finalAfterNego).toLocaleString('id-ID')}</span>
                    </div>
                </div>
            `;
        }

        const confirmationModalHtml = `
            <div style="text-align: left; font-family: system-ui, -apple-system, sans-serif;">
                <div style="background: #f1f5f9; border-radius: 8px; padding: 8px 12px; font-size: 11px; margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                        <span style="color: #64748b;">Pelanggan:</span>
                        <div>${customerDisplay}</div>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Metode / Tipe:</span>
                        <strong style="color: ${isDraft ? '#d97706' : '#2563eb'}; text-transform: uppercase;">${isDraft ? 'Draft Pesanan (Menunggu Kasir)' : paymentMethod}</strong>
                    </div>
                </div>

                <div style="max-height: 160px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 6px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc; color: #64748b; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;">
                                <th style="padding: 6px 8px; text-align: left;">Item</th>
                                <th style="padding: 6px 8px; text-align: center;">Qty</th>
                                <th style="padding: 6px 8px; text-align: right;">Harga</th>
                                <th style="padding: 6px 8px; text-align: right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>${itemsTableRows}</tbody>
                    </table>
                </div>

                ${financialSummaryHtml}

                ${productionNotes ? `<div style="font-size: 10.5px; color: #475569; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; padding: 6px 8px; margin-top: 8px;"><i class="fa-solid fa-note-sticky text-amber-500 me-1"></i> Catatan SPK: <em>${productionNotes}</em></div>` : ''}
            </div>
        `;

        const swalTitle = isDraft
            ? '<span style="font-size: 17px; font-weight: 800; color: #d97706;"><i class="fa-solid fa-file-signature text-amber-500 me-2"></i>Simpan Draft Pesanan</span>'
            : (isDp 
                ? '<span style="font-size: 17px; font-weight: 800; color: #0f172a;"><i class="fa-solid fa-file-invoice-dollar text-amber-500 me-2"></i>Konfirmasi Pesanan DP</span>' 
                : '<span style="font-size: 17px; font-weight: 800; color: #0f172a;"><i class="fa-solid fa-cash-register text-blue-600 me-2"></i>Konfirmasi Pembayaran</span>');

        const confirmBtnText = isDraft
            ? '<i class="fa-solid fa-file-signature me-1"></i> Ya, Simpan Draft (Ke Kasir)'
            : (isDp ? '<i class="fa-solid fa-check me-1"></i> Ya, Proses Bayar DP' : '<i class="fa-solid fa-check me-1"></i> Ya, Proses & Bayar');

        if (isDraft) {
            // Langsung simpan draft tanpa popup konfirmasi
            executeCheckoutFetch(payloadItems, paymentMethod, false, 0, customerId, customerName, customerPhone, customerEmail, dueDate, productionNotes, true, alpineData, btnDesktop, btnMobile, errContainerDesktop, errContainerMobile, successContainerDesktop, successContainerMobile);
            return;
        }

        // Pre-payment Confirmation Popup (Hanya untuk pembayaran riil)
        Swal.fire({
            title: swalTitle,
            html: confirmationModalHtml,
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: confirmBtnText,
            cancelButtonText: '<i class="fa-solid fa-xmark me-1"></i> Batal / Periksa Lagi',
            reverseButtons: true,
            focusConfirm: true,
            width: '460px'
        }).then((confirmResult) => {
            if (!confirmResult.isConfirmed) {
                return;
            }

            executeCheckoutFetch(payloadItems, paymentMethod, isDp, dpAmount, customerId, customerName, customerPhone, customerEmail, dueDate, productionNotes, isDraft, alpineData, btnDesktop, btnMobile, errContainerDesktop, errContainerMobile, successContainerDesktop, successContainerMobile);
        });
    }

    // --- Helper to execute checkout fetch request ---
    function executeCheckoutFetch(payloadItems, paymentMethod, isDp, dpAmount, customerId, customerName, customerPhone, customerEmail, dueDate, productionNotes, isDraft, alpineData, btnDesktop, btnMobile, errContainerDesktop, errContainerMobile, successContainerDesktop, successContainerMobile) {
        // Disable Buttons
        if (btnDesktop) {
            btnDesktop.disabled = true;
            btnDesktop.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...`;
        }
        if (btnMobile) {
            btnMobile.disabled = true;
            btnMobile.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...`;
        }

        if (errContainerDesktop) errContainerDesktop.classList.add('hidden');
        if (errContainerMobile) errContainerMobile.classList.add('hidden');
        if (successContainerDesktop) successContainerDesktop.classList.add('hidden');
        if (successContainerMobile) successContainerMobile.classList.add('hidden');

        fetch('{{ route("pos.checkout") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                payment_method: isDraft ? 'Draft' : paymentMethod,
                items: payloadItems,
                is_dp: isDp,
                dp_amount: dpAmount,
                discount_amount: window.negotiationDiscount || 0,
                negotiation_notes: window.negotiationNotes || null,
                is_draft: isDraft,
                customer_id: customerId,
                customer_name: customerName,
                customer_phone: customerPhone,
                customer_email: customerEmail,
                due_date: dueDate,
                production_notes: productionNotes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (btnDesktop) {
                btnDesktop.disabled = false;
                btnDesktop.innerHTML = isDraft 
                    ? `<i class="fa-solid fa-file-signature me-1"></i> Simpan Draft Pesanan (Ke Kasir)` 
                    : `<i class="fa-solid fa-circle-check me-1"></i> ${isDp ? 'Proses Bayar DP' : 'Proses Bayar (Checkout)'}`;
            }
            if (btnMobile) {
                btnMobile.disabled = false;
                btnMobile.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> Konfirmasi & Bayar Tagihan`;
            }

            if (data.status === 'success' || data.success === true) {
                // Clear cart & negotiation state
                cart = [];
                resetNegotiation();
                renderCart();
                if (alpineData.clearCustomer) alpineData.clearCustomer();

                const isPartial = data.payment_status === 'PARTIAL';
                const isDraftResult = data.is_draft || data.order_status === 'draft';

                // Update Draft counter on Cashier
                fetch('{{ route("pos.drafts") }}').then(r => r.json()).then(d => {
                    if (d.drafts) updateDraftBadge(d.drafts.length);
                }).catch(() => {});

                // Jika DRAFT: Jangan tampilkan note/kartu apa pun di panel keranjang, langsung kosongkan!
                if (isDraftResult) {
                    if (successContainerDesktop) successContainerDesktop.classList.add('hidden');
                    if (successContainerMobile) successContainerMobile.classList.add('hidden');
                    if (errContainerDesktop) errContainerDesktop.classList.add('hidden');
                    if (errContainerMobile) errContainerMobile.classList.add('hidden');

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `Draft #${data.invoice_number} berhasil disimpan`,
                        showConfirmButton: false,
                        timer: 1200
                    });
                    return;
                }

                // Show Green / Amber Success Card in Desktop Cart Panel (Hanya untuk Transaksi Pembayaran)
                if (successContainerDesktop) {
                    document.getElementById('success-inv-text').innerText = '#' + data.invoice_number;
                    
                    const badgeEl = document.getElementById('success-badge-tag');
                    if (isDraftResult) {
                        badgeEl.className = 'badge bg-amber-500 text-white font-bold text-[10px] px-2 py-0.5 rounded';
                        badgeEl.innerHTML = '<i class="fa-solid fa-inbox me-1"></i> DRAFT PESANAN';
                        document.getElementById('success-msg-text').innerText = `Draft #${data.invoice_number} berhasil disimpan. Berikan ke Kasir untuk pembayaran.`;
                    } else if (isPartial) {
                        badgeEl.className = 'badge bg-amber-500 text-white font-bold text-[10px] px-2 py-0.5 rounded';
                        badgeEl.innerHTML = '<i class="fa-solid fa-clock-rotate-left me-1"></i> DP (UANG MUKA)';
                        document.getElementById('success-msg-text').innerText = `DP Rp ${Number(data.paid_amount).toLocaleString('id-ID')} diterima. Sisa Piutang: Rp ${Number(data.remaining_amount).toLocaleString('id-ID')}`;
                    } else {
                        badgeEl.className = 'badge bg-emerald-600 text-white font-bold text-[10px] px-2 py-0.5 rounded';
                        badgeEl.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> LUNAS (PAID)';
                        document.getElementById('success-msg-text').innerText = 'Transaksi lunas berhasil diproses & tercatat pada kasir.';
                    }

                    successContainerDesktop.classList.remove('hidden');

                    document.getElementById('btn-print-last-receipt').onclick = function() {
                        window.open(data.receipt_url, '_blank', 'width=450,height=600');
                    };
                    document.getElementById('btn-open-last-inv').onclick = function() {
                        if (data.public_invoice_url) {
                            window.open(data.public_invoice_url, '_blank');
                        } else {
                            openSnaprintInvoice(data);
                        }
                    };

                    const btnWa = document.getElementById('btn-wa-last-receipt');
                    if (btnWa) {
                        if (data.customer_phone) {
                            btnWa.classList.remove('hidden');
                            btnWa.innerHTML = `<i class="fa-brands fa-whatsapp text-sm me-1"></i> Kirim Faktur ke WhatsApp (${data.customer_phone})`;
                            btnWa.onclick = function() {
                                openWhatsAppReceipt(data.customer_phone, data);
                            };
                        } else {
                            btnWa.classList.add('hidden');
                        }
                    }
                }

                // Show Success SweetAlert with Instant Thermal Print & WhatsApp PDF Delivery
                let titleHtml = '';
                let statusBadge = '';

                if (isDraftResult) {
                    titleHtml = '<span style="color: #d97706; font-weight: 800;">Draft Pesanan Berhasil Disimpan!</span>';
                    statusBadge = `<span class="inline-block px-3 py-1 bg-amber-100 text-amber-800 border border-amber-400 rounded-md text-xs font-extrabold uppercase mb-2">
                        <i class="fa-solid fa-inbox text-amber-600"></i> STATUS: DRAFT (MENUNGGU KASIR)
                    </span>`;
                } else if (isPartial) {
                    titleHtml = '<span style="color: #d97706; font-weight: 800;">Pesanan DP Tercatat!</span>';
                    statusBadge = `<span class="inline-block px-3 py-1 bg-amber-100 text-amber-800 border border-amber-400 rounded-md text-xs font-extrabold uppercase mb-2">
                        <i class="fa-solid fa-clock-rotate-left text-amber-600"></i> STATUS: DP (PARSIAL) &bull; SISA PIUTANG: Rp ${Number(data.remaining_amount || 0).toLocaleString('id-ID')}
                    </span>`;
                } else {
                    titleHtml = '<span style="color: #059669; font-weight: 800;">Transaksi LUNAS (PAID)</span>';
                    statusBadge = `<span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 border border-emerald-400 rounded-md text-xs font-extrabold uppercase mb-2">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i> STATUS: PAID (LUNAS)
                    </span>`;
                }

                const waBtnHtml = data.customer_phone ? `
                    <div class="mt-3 pt-2.5 border-top border-dashed">
                        <button type="button" id="btn-swal-wa" class="btn w-100 py-2 text-xs font-bold text-white d-flex align-items-center justify-content-center gap-2 rounded-xl shadow-md cursor-pointer border-0" style="background-color: #25D366;">
                            <i class="fa-brands fa-whatsapp text-base"></i>
                            <span>Kirim Faktur & PDF ke WhatsApp (${data.customer_phone})</span>
                        </button>
                    </div>
                ` : '';

                Swal.fire({
                    icon: 'success',
                    title: titleHtml,
                    html: `
                        <div class="py-2 text-center">
                            ${statusBadge}
                            <div class="font-mono text-base font-bold text-slate-800 mt-1">#${data.invoice_number}</div>
                            ${data.customer_name ? `<div class="text-xs text-slate-700 font-semibold mt-1">Client: <strong>${data.customer_name}</strong> ${data.customer_phone ? '<span class="text-slate-500">(' + data.customer_phone + ')</span>' : ''}</div>` : ''}
                            <div class="text-sm font-extrabold text-blue-900 mt-1 font-mono">
                                Total: Rp ${Number(data.total_price || 0).toLocaleString('id-ID')}
                                ${isPartial ? `<br><span class="text-emerald-700 text-xs">DP Masuk: Rp ${Number(data.paid_amount || 0).toLocaleString('id-ID')}</span>` : ''}
                            </div>
                            <div class="text-xs text-slate-500 mt-1">Metode: <strong>${data.payment_method}</strong> &bull; Petugas: ${data.cashier_name}</div>
                            ${waBtnHtml}
                        </div>
                    `,
                    didOpen: () => {
                        const swalWaBtn = document.getElementById('btn-swal-wa');
                        if (swalWaBtn && data.customer_phone) {
                            swalWaBtn.onclick = function() {
                                openWhatsAppReceipt(data.customer_phone, data);
                            };
                        }
                    },
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonColor: '#2563eb',
                    denyButtonColor: '#0f172a',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fa-solid fa-print me-1"></i> Cetak Struk 58mm',
                    denyButtonText: '<i class="fa-solid fa-file-pdf me-1"></i> Unduh / Buka Faktur PDF',
                    cancelButtonText: 'Selesai (+ Transaksi Baru)'
                }).then((result) => {
                    if (result.isConfirmed && data.receipt_url) {
                        window.open(data.receipt_url, '_blank', 'width=450,height=600');
                    } else if (result.isDenied) {
                        if (data.public_invoice_url) {
                            window.open(data.public_invoice_url, '_blank');
                        } else {
                            openSnaprintInvoice(data);
                        }
                    }
                });

            } else {
                const errorMsg = data.message || 'Terjadi kesalahan sistem saat memproses transaksi kasir.';
                if (errContainerDesktop) {
                    errContainerDesktop.innerText = errorMsg;
                    errContainerDesktop.classList.remove('hidden');
                }
                if (errContainerMobile) {
                    errContainerMobile.innerText = errorMsg;
                    errContainerMobile.classList.remove('hidden');
                }
            }
        })
        .catch(err => {
            if (btnDesktop) {
                btnDesktop.disabled = false;
                btnDesktop.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> Proses Bayar`;
            }
            if (btnMobile) {
                btnMobile.disabled = false;
                btnMobile.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> Konfirmasi & Bayar`;
            }
            
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Koneksi bermasalah atau terjadi error pada server: ' + err.message });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const catContainer = document.getElementById('category-filter-container');
        if (catContainer) {
            catContainer.addEventListener('click', function(e) {
                const btn = e.target.closest('.category-filter-btn');
                if (btn) {
                    const cat = btn.getAttribute('data-cat') || 'all';
                    filterCategory(cat, btn);
                }
            });
        }

        // Fetch draft count on load for cashiers
        fetch('{{ route("pos.drafts") }}')
            .then(res => res.json())
            .then(data => {
                if (data.drafts) updateDraftBadge(data.drafts.length);
            }).catch(() => {});
    });

    window.onSelectProduct = onSelectProduct;
    window.handleProductClick = handleProductClick;
    window.addToCart = addToCart;
    window.updateQty = updateQty;
    window.setQty = setQty;
    window.clearCart = clearCart;
    window.editBannerCartItem = editBannerCartItem;
    window.openBannerDimensionModal = openBannerDimensionModal;
    window.confirmBannerDimensionAddToCart = confirmBannerDimensionAddToCart;
    window.showBannerModal = showBannerModal;
    window.hideBannerModal = hideBannerModal;
    window.syncBannerWidth = syncBannerWidth;
    window.setBannerWidth = setBannerWidth;
    window.syncBannerLength = syncBannerLength;
    window.setBannerLength = setBannerLength;
    window.changeDimQty = changeDimQty;
    window.calculateDimensionPreview = calculateDimensionPreview;
    window.renderCart = renderCart;
    window.filterCategory = filterCategory;
    window.filterProducts = filterProducts;
    window.applyCombinedFilter = applyCombinedFilter;
    window.setPaymentMethod = setPaymentMethod;
    window.processCheckout = processCheckout;
    window.openNegotiationModal = openNegotiationModal;
    window.setNegoMode = setNegoMode;
    window.calcNegoResult = calcNegoResult;
    window.applyNegotiation = applyNegotiation;
    window.resetNegotiation = resetNegotiation;
    window.openDraftOrdersModal = openDraftOrdersModal;
    window.loadDraftOrders = loadDraftOrders;
    window.promptSettleDraft = promptSettleDraft;
    window.executeSettleDraft = executeSettleDraft;
</script>
@endsection
