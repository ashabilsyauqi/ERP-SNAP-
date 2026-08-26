@extends('layouts.app')

@section('title', 'Point of Sale (POS)')
@section('page-title', 'Terminal Kasir Penjualan (POS)')

@section('action-buttons')
<a href="{{ route('sales.receivables') }}" class="btn-odoo-primary text-decoration-none">
    <i class="fa-solid fa-hand-holding-dollar me-1"></i> Piutang & Pesanan DP
</a>
<a href="{{ route('sales.index') }}" class="btn-odoo-secondary text-decoration-none">
    <i class="fa-solid fa-receipt me-1 text-blue-600"></i> Riwayat Penjualan
</a>
@endsection

@section('content')
<div class="flex flex-col lg:flex-row gap-3 h-[calc(100vh-95px)] animate-fade-in relative pb-16 lg:pb-0 overflow-hidden" 
     id="pos-main-container"
     x-data="{ 
        activeTab: 'catalog', 
        isDp: false, 
        cartTotal: 0,
        cartItemCount: 0,
        minDpThreshold: 500000,
        customerName: '', 
        customerPhone: '', 
        dueDate: '', 
        dpAmount: 0, 
        productionNotes: '',
        showToast: false,
        toastItemName: '',
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
    <div class="lg:hidden flex items-center bg-slate-200/90 p-1 rounded-2xl gap-1 flex-shrink-0 shadow-inner">
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

    <!-- Left Column: Products Grid & Search -->
    <div class="flex-1 flex flex-col gap-2.5 min-h-0 h-full"
         :class="activeTab === 'catalog' ? 'flex' : 'hidden lg:flex'">
        
        <!-- Products Header & Search (Compact Clean Bar) -->
        <div class="bg-white px-3.5 py-2.5 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-2.5 flex-shrink-0">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-cash-register text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 mb-0">Katalog Produk & Layanan Kasir</h2>
                    <p class="text-[11px] text-slate-500 mb-0">Klik kartu untuk langsung memasukkan ke keranjang kasir</p>
                </div>
            </div>
            
            <!-- Live Search Products -->
            <div class="relative w-full sm:w-72">
                <input type="text" id="product-search" onkeyup="filterProducts()" placeholder="Cari 112 produk cetak & merchandise..." 
                    class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 text-xs transition duration-150">
                <div class="absolute left-3 top-2.5 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
            </div>
        </div>

        <!-- Categories Pill Filter Tabs (Touch Friendly) -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 flex-shrink-0 no-scrollbar" id="category-filter-container">
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
        
        <!-- Products Cards Grid (Max 3 Columns, Smooth Vertical Scroll) -->
        <div id="products-grid" class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-3 gap-2.5 overflow-y-auto pr-1 pb-2 flex-grow min-h-0">
            @foreach($materials as $material)
                <div class="product-card bg-white p-3 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-500 transition duration-150 cursor-pointer flex flex-col justify-between group"
                     data-name="{{ strtolower($material->material_name) }}"
                     data-category="{{ $material->category ?? 'Lainnya' }}"
                     onclick="handleProductClick('{{ addslashes($material->material_name) }}', '{{ $material->fixed_size }}', {{ $material->retail_price }}, {{ json_encode($material->wholesalePrices) }}, '{{ addslashes($material->category ?? '') }}')">
                    
                    <div>
                        <div class="flex justify-between items-start mb-1.5 gap-1">
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
                            <span class="badge {{ $badgeStyle }} border text-[10px] font-semibold px-2 py-0.5 rounded-md truncate max-w-[130px]" title="{{ $material->category ?? 'Bahan' }}">
                                {{ $material->category ?? 'Bahan' }}
                            </span>
                            <span class="text-[10px] {{ $material->stock_qty > 10 ? 'text-emerald-600' : ($material->stock_qty > 0 ? 'text-amber-600' : 'text-rose-600') }} font-bold flex-shrink-0">
                                {{ $material->stock_qty }} pcs
                            </span>
                        </div>
                        
                        <h3 class="font-bold text-slate-900 text-xs line-clamp-2 mb-1 group-hover:text-blue-600 transition leading-snug">
                            {{ $material->material_name }}
                        </h3>
                        
                        @if($material->fixed_size)
                            <div class="text-[10px] text-slate-500 mb-1">
                                Lebar Roll: <strong class="text-slate-700">{{ $material->fixed_size }} m</strong>
                            </div>
                        @endif

                        @if($material->wholesalePrices->count() > 0)
                            <div class="text-[10px] text-blue-600 font-medium mb-1">
                                <i class="fa-solid fa-tags me-1"></i>{{ $material->wholesalePrices->count() }} Tier Grosir
                            </div>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex justify-between items-center mt-2">
                        <div>
                            <span class="text-[9.5px] text-slate-400 block leading-tight">Harga Satuan</span>
                            <span class="font-bold text-blue-900 font-mono text-xs">
                                Rp {{ number_format($material->retail_price, 0, ',', '.') }}
                            </span>
                        </div>
                        
                        <button type="button" class="w-7 h-7 bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white rounded-lg flex items-center justify-center transition border-0 cursor-pointer text-xs">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
            @endforeach

            <!-- Empty State for Filter/Search -->
            <div id="products-empty-state" class="col-span-2 sm:col-span-3 py-12 text-center hidden">
                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto text-slate-400 mb-2 text-lg">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <h4 class="text-xs font-bold text-slate-700 mb-1">Produk Tidak Ditemukan</h4>
                <p class="text-[11px] text-slate-400 mb-0">Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
            </div>
        </div>
    </div>

    <!-- Right Column: Cart & Checkout (Always visible on Desktop lg+, activeTab === 'cart' on < lg) -->
    <div class="w-full lg:w-[360px] xl:w-[390px] bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden h-full flex-shrink-0"
         :class="activeTab === 'cart' ? 'flex' : 'hidden lg:flex'">
        
        <!-- Cart Header -->
        <div class="px-3.5 py-2.5 bg-slate-900 text-white flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2">
                <!-- Back to Catalog button on screens < 1024px -->
                <button type="button" @click="activeTab = 'catalog'" class="lg:hidden w-7 h-7 bg-white/10 hover:bg-white/20 text-white rounded-lg flex items-center justify-center border-0 cursor-pointer" title="Kembali ke Katalog">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                </button>
                <i class="fa-solid fa-cart-shopping text-blue-400 text-sm"></i>
                <h2 class="text-xs font-bold mb-0 text-white">Keranjang Order (POS)</h2>
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
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Nama Client</label>
                            <input type="text" x-model="customerName" placeholder="Contoh: PT Surya / Bpk Dani" 
                                class="w-full px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">No. WhatsApp</label>
                            <input type="text" x-model="customerPhone" placeholder="08123456789" 
                                class="w-full px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs font-mono">
                        </div>
                    </div>

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

            <!-- Receipt Breakdown -->
            <div class="bg-slate-50 p-2.5 rounded-xl space-y-1 text-xs text-slate-500 font-medium border border-slate-100">
                <div class="flex justify-between">
                    <span>Total Tagihan Pesanan</span>
                    <span id="receipt-total-desktop" class="font-mono font-bold text-slate-900">Rp 0</span>
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
            </div>

            <!-- Error Notification Card (Rose Red) -->
            <div id="checkout-error-desktop" class="hidden bg-rose-50 border border-rose-200 text-rose-700 p-2.5 rounded-xl text-xs font-semibold"></div>

            <button onclick="processCheckout()" id="checkout-btn-desktop" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl shadow transition duration-150 flex justify-center items-center gap-2 cursor-pointer disabled:opacity-50 border-0">
                <i class="fa-solid fa-circle-check"></i>
                <span x-text="(isDp && isEligibleForDp) ? 'Proses Simpan Pesanan DP' : 'Proses Bayar (Checkout)'">Proses Bayar (Checkout)</span>
            </button>
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

<!-- Modal Custom Dimension Banner (Panjang Fixed & Lebar Custom dalam CM) -->
<div class="modal fade" id="modalBannerDimension" tabindex="-1" aria-labelledby="modalBannerDimensionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border shadow-2xl overflow-hidden">
            <div class="bg-slate-900 text-white px-4 py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-ruler-combined text-blue-400"></i>
                    <h5 class="fs-6 fw-bold mb-0 text-white" id="modalBannerDimensionLabel">Kustomisasi Ukuran Banner</h5>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="p-4 space-y-4 bg-slate-50">
                <!-- Product Info Card -->
                <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase">Produk Bahan Cetak</div>
                    <div class="font-bold text-slate-900 text-sm" id="dim_product_name">-</div>
                    <div class="text-xs text-blue-700 font-mono font-bold mt-0.5">
                        Harga Dasar: <span id="dim_product_price">Rp 0</span> / m²
                    </div>
                </div>

                <!-- Input Dimension Form -->
                <div class="space-y-3">
                    <!-- 1. Panjang (Fixed Size / Roll) -->
                    <div>
                        <label class="form-label font-bold text-slate-700 text-xs uppercase d-flex justify-content-between align-items-center">
                            <span>1. Panjang Bahan (Fixed Roll) <span class="text-rose-600">*</span></span>
                            <span class="badge bg-blue-100 text-blue-800 text-[10px]">Ukuran Standar Meter</span>
                        </label>
                        <div class="input-group input-group-sm mb-1">
                            <select id="dim_fixed_length_select" onchange="onFixedLengthSelectChange(this.value)" class="form-select form-select-sm font-semibold">
                                <option value="1.0">1.0 Meter (100 cm)</option>
                                <option value="1.2">1.2 Meter (120 cm)</option>
                                <option value="1.5">1.5 Meter (150 cm)</option>
                                <option value="2.0">2.0 Meter (200 cm)</option>
                                <option value="2.5">2.5 Meter (250 cm)</option>
                                <option value="3.0">3.0 Meter (300 cm)</option>
                                <option value="3.2">3.2 Meter (320 cm)</option>
                                <option value="custom">Custom Panjang Roll (Meter)...</option>
                            </select>
                            <span class="input-group-text font-bold text-xs bg-slate-100">Meter</span>
                        </div>
                        <div id="dim_custom_length_container" class="hidden mt-1">
                            <input type="number" step="0.1" min="0.1" id="dim_fixed_length_custom" oninput="calculateDimensionPreview()" class="form-control form-control-sm font-bold" placeholder="Contoh: 2.2">
                        </div>
                    </div>

                    <!-- 2. Lebar (Custom dalam CM) -->
                    <div>
                        <label class="form-label font-bold text-slate-700 text-xs uppercase d-flex justify-content-between align-items-center">
                            <span>2. Lebar Cetak (Customize dalam CM) <span class="text-rose-600">*</span></span>
                            <span class="badge bg-emerald-100 text-emerald-800 text-[10px]">Satuan Centimeter (CM)</span>
                        </label>
                        <div class="input-group input-group-sm mb-2">
                            <input type="number" id="dim_custom_width_cm" min="10" step="1" value="150" oninput="calculateDimensionPreview()" class="form-control form-control-sm font-bold font-mono text-base text-blue-900" placeholder="contoh: 150">
                            <span class="input-group-text font-bold text-xs bg-slate-100">CM</span>
                        </div>
                        <!-- Quick Width Chips -->
                        <div class="d-flex flex-wrap gap-1.5 align-items-center">
                            <span class="text-[10px] text-slate-500 font-semibold me-1">Pilihan Cepat:</span>
                            <button type="button" onclick="setDimWidth(50)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill">50 cm</button>
                            <button type="button" onclick="setDimWidth(100)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill">100 cm</button>
                            <button type="button" onclick="setDimWidth(150)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill">150 cm</button>
                            <button type="button" onclick="setDimWidth(200)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill">200 cm</button>
                            <button type="button" onclick="setDimWidth(250)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill">250 cm</button>
                            <button type="button" onclick="setDimWidth(300)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill">300 cm</button>
                            <button type="button" onclick="setDimWidth(400)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill">400 cm</button>
                            <button type="button" onclick="setDimWidth(500)" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-[10px] rounded-pill">500 cm</button>
                        </div>
                    </div>

                    <!-- 3. Jumlah Cetak (Qty Lembar) -->
                    <div>
                        <label class="form-label font-bold text-slate-700 text-xs uppercase">3. Jumlah Lembar (Qty Pcs)</label>
                        <div class="input-group input-group-sm">
                            <button type="button" onclick="changeDimQty(-1)" class="btn btn-outline-secondary font-bold">-</button>
                            <input type="number" id="dim_qty" min="1" value="1" oninput="calculateDimensionPreview()" class="form-control form-control-sm text-center font-bold font-mono">
                            <button type="button" onclick="changeDimQty(1)" class="btn btn-outline-secondary font-bold">+</button>
                        </div>
                    </div>
                </div>

                <!-- Live Calculation Result Card -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 space-y-1.5">
                    <div class="d-flex justify-content-between text-xs text-blue-900">
                        <span>Ukuran Cetak:</span>
                        <strong id="dim_preview_size" class="font-mono">2.0m x 150cm</strong>
                    </div>
                    <div class="d-flex justify-content-between text-xs text-blue-900">
                        <span>Luas per Lembar:</span>
                        <strong id="dim_preview_area" class="font-mono text-emerald-700">3.00 m²</strong>
                    </div>
                    <div class="d-flex justify-content-between text-xs text-blue-900 border-t border-blue-200 pt-1">
                        <span>Harga Satuan per Lembar:</span>
                        <strong id="dim_preview_unit_price" class="font-mono">Rp 75.000</strong>
                    </div>
                    <div class="d-flex justify-content-between text-sm text-blue-950 font-bold border-t border-blue-200 pt-1">
                        <span>Total Subtotal:</span>
                        <span id="dim_preview_subtotal" class="font-mono fs-6 text-blue-800">Rp 75.000</span>
                    </div>
                </div>
            </div>
            <div class="bg-slate-100 border-top px-4 py-2.5 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" onclick="confirmBannerDimensionAddToCart()" class="btn btn-primary btn-sm font-bold">
                    <i class="fa-solid fa-cart-plus me-1"></i> Masukkan ke Keranjang
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let cart = [];
    let cartCounter = 0;
    window.currentGrandTotal = 0;

    // Active state for dimension modal
    let activeDimProduct = {
        name: '',
        fixedSize: null,
        retailPrice: 0,
        wholesalePrices: [],
        editCartId: null
    };

    // --- Check if product is banner or custom media ---
    function isBannerProduct(name, fixedSize, category) {
        if (!fixedSize || parseFloat(fixedSize) <= 0) return false;
        
        const nameLower = (name || '').toLowerCase();
        // Exclude standard documents, copies, prints, bindings, merchandise, etc.
        const nonBannerKeywords = ['fotokopi', 'foto copy', 'scan', 'print', 'jilid', 'laminating', 'laminasi', 'spiral', 'nota', 'brosur', 'mug', 'stempel', 'stampel', 'tumbler', 'kalender', 'kipas', 'lanyard', 'pin', 'id card', 'cutting', 'sablon dtf', 'paket'];
        for (let kw of nonBannerKeywords) {
            if (nameLower.includes(kw)) return false;
        }

        const isOutdoorCat = category && /outdoor|indoor/i.test(category);
        const isRollName = /^(flexi|flexy|albatros|ritrama|oneway|kain banner)/i.test(nameLower.trim());
        return isOutdoorCat || isRollName;
    }

    // --- Handle product card click ---
    function handleProductClick(materialName, fixedSize, retailPrice, wholesalePrices, category = '') {
        if (isBannerProduct(materialName, fixedSize, category)) {
            openBannerDimensionModal(materialName, fixedSize, retailPrice, wholesalePrices);
        } else {
            addToCart(materialName, fixedSize, retailPrice, wholesalePrices);
        }
    }

    // --- Open Banner Dimension Modal ---
    function openBannerDimensionModal(materialName, fixedSize, retailPrice, wholesalePrices, editCartItem = null) {
        activeDimProduct = {
            name: materialName,
            fixedSize: fixedSize,
            retailPrice: retailPrice,
            wholesalePrices: wholesalePrices || [],
            editCartId: editCartItem ? editCartItem.id : null
        };

        document.getElementById('dim_product_name').innerText = materialName;
        document.getElementById('dim_product_price').innerText = 'Rp ' + Number(retailPrice).toLocaleString('id-ID');

        // Set Fixed Length
        const selectFixed = document.getElementById('dim_fixed_length_select');
        const customContainer = document.getElementById('dim_custom_length_container');
        const customInput = document.getElementById('dim_fixed_length_custom');

        let targetFixed = editCartItem ? String(editCartItem.fixed_length_m) : (fixedSize ? String(fixedSize) : '2.0');
        
        let optionFound = false;
        for (let i = 0; i < selectFixed.options.length; i++) {
            if (selectFixed.options[i].value === targetFixed) {
                selectFixed.selectedIndex = i;
                optionFound = true;
                break;
            }
        }

        if (!optionFound) {
            selectFixed.value = 'custom';
            customContainer.classList.remove('hidden');
            customInput.value = targetFixed;
        } else {
            customContainer.classList.add('hidden');
        }

        // Set Custom Width CM
        const widthVal = editCartItem ? editCartItem.custom_width_cm : 150;
        document.getElementById('dim_custom_width_cm').value = widthVal;

        // Set Qty
        const qtyVal = editCartItem ? editCartItem.qty : 1;
        document.getElementById('dim_qty').value = qtyVal;

        calculateDimensionPreview();

        const modalEl = document.getElementById('modalBannerDimension');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function onFixedLengthSelectChange(val) {
        const customContainer = document.getElementById('dim_custom_length_container');
        if (val === 'custom') {
            customContainer.classList.remove('hidden');
        } else {
            customContainer.classList.add('hidden');
        }
        calculateDimensionPreview();
    }

    function setDimWidth(val) {
        document.getElementById('dim_custom_width_cm').value = val;
        calculateDimensionPreview();
    }

    function changeDimQty(delta) {
        const input = document.getElementById('dim_qty');
        let val = (parseInt(input.value, 10) || 1) + delta;
        if (val < 1) val = 1;
        input.value = val;
        calculateDimensionPreview();
    }

    function getActiveFixedLength() {
        const select = document.getElementById('dim_fixed_length_select').value;
        if (select === 'custom') {
            return parseFloat(document.getElementById('dim_fixed_length_custom').value) || 2.0;
        }
        return parseFloat(select) || 2.0;
    }

    function calculateDimensionPreview() {
        const fixedLength = getActiveFixedLength();
        const customWidthCm = parseFloat(document.getElementById('dim_custom_width_cm').value) || 100;
        const qty = parseInt(document.getElementById('dim_qty').value, 10) || 1;

        const areaM2 = (fixedLength * (customWidthCm / 100));
        const roundedArea = Math.round(areaM2 * 1000) / 1000;

        const { price: baseUnitPrice } = getUnitPrice(activeDimProduct.retailPrice, activeDimProduct.wholesalePrices, qty);
        const unitPricePerLembar = Math.round(roundedArea * baseUnitPrice);
        const subtotal = unitPricePerLembar * qty;

        document.getElementById('dim_preview_size').innerText = `${fixedLength}m x ${customWidthCm}cm`;
        document.getElementById('dim_preview_area').innerText = `${roundedArea.toFixed(2)} m²`;
        document.getElementById('dim_preview_unit_price').innerText = 'Rp ' + Number(unitPricePerLembar).toLocaleString('id-ID');
        document.getElementById('dim_preview_subtotal').innerText = 'Rp ' + Number(subtotal).toLocaleString('id-ID');
    }

    function confirmBannerDimensionAddToCart() {
        const fixedLength = getActiveFixedLength();
        const customWidthCm = parseFloat(document.getElementById('dim_custom_width_cm').value) || 100;
        const qty = parseInt(document.getElementById('dim_qty').value, 10) || 1;
        const areaM2 = Math.round((fixedLength * (customWidthCm / 100)) * 1000) / 1000;

        if (activeDimProduct.editCartId !== null) {
            // Edit existing cart item
            const item = cart.find(i => i.id === activeDimProduct.editCartId);
            if (item) {
                item.fixed_length_m = fixedLength;
                item.custom_width_cm = customWidthCm;
                item.area_m2 = areaM2;
                item.qty = qty;
                item.dimension_text = `${fixedLength}m x ${customWidthCm}cm (${areaM2.toFixed(2)} m²)`;
            }
        } else {
            // Add new custom banner item to cart
            const dimensionText = `${fixedLength}m x ${customWidthCm}cm (${areaM2.toFixed(2)} m²)`;
            
            // Check if exact dimension already in cart
            const existing = cart.find(i => i.material_name_or_type === activeDimProduct.name 
                                          && i.fixed_length_m === fixedLength 
                                          && i.custom_width_cm === customWidthCm);
            if (existing) {
                existing.qty += qty;
            } else {
                cart.push({
                    id: cartCounter++,
                    material_name_or_type: activeDimProduct.name,
                    fixed_length_m: fixedLength,
                    custom_width_cm: customWidthCm,
                    area_m2: areaM2,
                    dimension_text: dimensionText,
                    requested_size: fixedLength,
                    is_custom_banner: true,
                    qty: qty,
                    retail_price: activeDimProduct.retailPrice,
                    wholesale_prices: activeDimProduct.wholesalePrices
                });
            }
        }

        renderCart();

        window.dispatchEvent(new CustomEvent('item-added-to-cart', { detail: { name: activeDimProduct.name } }));

        const modalEl = document.getElementById('modalBannerDimension');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }

    // --- Add regular non-banner items to Cart ---
    function addToCart(materialName, fixedSize, retailPrice, wholesalePrices) {
        let size = fixedSize;

        let existingItem = cart.find(i => i.material_name_or_type === materialName && !i.is_custom_banner);

        if (existingItem) {
            updateQty(existingItem.id, 1);
        } else {
            cart.push({
                id: cartCounter++,
                material_name_or_type: materialName,
                requested_size: size,
                is_custom_banner: false,
                qty: 1,
                retail_price: retailPrice,
                wholesale_prices: wholesalePrices
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
            
            let finalUnitPrice = basePrice;
            if (item.is_custom_banner && item.area_m2) {
                finalUnitPrice = Math.round(item.area_m2 * basePrice);
            }

            const itemTotal = finalUnitPrice * item.qty;
            
            totalQty += item.qty;
            grandTotal += itemTotal;

            cartHtml += `
                <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex flex-col gap-1.5">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="font-bold text-slate-900 text-xs">${item.material_name_or_type}</span>
                            
                            ${item.is_custom_banner ? `
                                <div class="mt-0.5">
                                    <span class="badge bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-mono font-semibold py-0.5 px-1.5 rounded">
                                        <i class="fa-solid fa-ruler-combined me-1"></i>${item.dimension_text || (item.fixed_length_m + 'm x ' + item.custom_width_cm + 'cm')}
                                    </span>
                                </div>
                            ` : (item.requested_size ? `<span class="block text-[10px] text-blue-600 font-medium">Ukuran: ${item.requested_size}m</span>` : '')}
                            
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-[11px] font-mono text-slate-600">
                                    @ Rp ${Number(finalUnitPrice).toLocaleString('id-ID')}
                                    ${item.is_custom_banner ? `<small class="text-slate-400">(${Number(basePrice).toLocaleString('id-ID')}/m²)</small>` : ''}
                                </span>
                                ${isWholesale ? `<span class="text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 px-1 rounded">Grosir</span>` : ''}
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <span class="font-bold font-mono text-xs text-slate-900">Rp ${Number(itemTotal).toLocaleString('id-ID')}</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center border-t border-slate-100 pt-1.5 mt-0.5">
                        <div class="d-flex align-items-center gap-2">
                            <button onclick="updateQty(${item.id}, -${item.qty})" class="text-[10px] text-rose-500 hover:text-rose-700 font-semibold bg-transparent border-0 cursor-pointer p-0">
                                <i class="fa-solid fa-trash-can me-0.5"></i> Hapus
                            </button>
                            ${item.is_custom_banner ? `
                                <button onclick="openBannerDimensionModal('${item.material_name_or_type}', '${item.fixed_length_m}', ${item.retail_price}, ${JSON.stringify(item.wholesale_prices)}, cart.find(i=>i.id===${item.id}))" class="text-[10px] text-blue-600 hover:text-blue-800 font-semibold bg-transparent border-0 cursor-pointer p-0">
                                    <i class="fa-solid fa-pen-ruler me-0.5"></i> Ubah Ukuran
                                </button>
                            ` : ''}
                        </div>
                        
                        <div class="flex items-center border border-slate-300 rounded-lg overflow-hidden bg-white shadow-sm">
                            <button type="button" onclick="updateQty(${item.id}, -1)" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition font-bold text-xs bg-transparent border-0 cursor-pointer" title="Kurangi 1">-</button>
                            <input type="number" min="1" value="${item.qty}" onchange="setQty(${item.id}, this.value)" onkeydown="if(event.key === 'Enter'){ this.blur(); }" class="w-12 h-7 text-center font-bold font-mono text-xs text-slate-900 border-x border-slate-200 bg-slate-50 focus:bg-white focus:outline-none p-0" title="Ketik jumlah unit">
                            <button type="button" onclick="updateQty(${item.id}, 1)" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition font-bold text-xs bg-transparent border-0 cursor-pointer" title="Tambah 1">+</button>
                        </div>
                    </div>
                </div>
            `;
        });

        cartHtml += '</div>';

        if (desktopContainer) desktopContainer.innerHTML = cartHtml;
        if (badgeCount) badgeCount.innerText = `${totalQty} item`;
        window.currentGrandTotal = grandTotal;
        
        window.dispatchEvent(new CustomEvent('cart-total-changed', { detail: { total: grandTotal, count: totalQty } }));

        const formattedTotal = `Rp ${Number(grandTotal).toLocaleString('id-ID')}`;
        if (receiptTotalDesktop) receiptTotalDesktop.innerText = formattedTotal;
    }

    // --- Category & Search Combined Filtering ---
    let currentCategory = 'all';

    function filterCategory(category, btnElement) {
        currentCategory = category;

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

        if (btnElement) {
            btnElement.classList.remove('bg-white', 'text-slate-700', 'border-slate-200', 'font-medium');
            btnElement.classList.add('bg-blue-600', 'text-white', 'border-blue-600', 'active', 'font-semibold');
            const countBadge = btnElement.querySelector('span:last-child');
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
        const query = (document.getElementById('product-search')?.value || '').toLowerCase().trim();
        const cards = document.querySelectorAll('.product-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const name = (card.getAttribute('data-name') || '').toLowerCase();
            const cat = card.getAttribute('data-category') || '';

            const matchQuery = !query || name.includes(query);
            const matchCat = (currentCategory === 'all') || (cat === currentCategory);

            if (matchQuery && matchCat) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        const emptyState = document.getElementById('products-empty-state');
        if (emptyState) {
            if (visibleCount === 0) {
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
            }
        }
    }

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

    // --- Checkout Logic via Fetch AJAX ---
    function processCheckout() {
        if (cart.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Keranjang Kosong', text: 'Pilih bahan baku terlebih dahulu.' });
            return;
        }

        const posContainer = document.getElementById('pos-main-container');
        const alpineData = posContainer && window.Alpine ? Alpine.$data(posContainer) : null;

        const isDp = alpineData ? (alpineData.isDp && alpineData.isEligibleForDp) : false;
        const dpAmount = isDp ? (parseFloat(alpineData.dpAmount) || 0) : 0;
        const customerName = isDp ? alpineData.customerName : null;
        const customerPhone = isDp ? alpineData.customerPhone : null;
        const dueDate = isDp ? alpineData.dueDate : null;
        const productionNotes = isDp ? alpineData.productionNotes : null;

        if (isDp) {
            const minAllowedDp = alpineData ? alpineData.minDpAmount : Math.round((window.currentGrandTotal || 0) * 0.5);
            if (dpAmount < minAllowedDp) {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Nominal DP Kurang dari 50%', 
                    text: 'Nominal uang muka (DP) minimal 50% dari total pesanan (Minimal: Rp ' + Number(minAllowedDp).toLocaleString('id-ID') + ').' 
                });
                return;
            }
        }

        const paymentMethod = document.getElementById('global_payment_method').value;
        const errContainerDesktop = document.getElementById('checkout-error-desktop');
        const errContainerMobile = document.getElementById('checkout-error-mobile');
        const successContainerDesktop = document.getElementById('checkout-success-desktop');
        const successContainerMobile = document.getElementById('checkout-success-mobile');
        
        const btnDesktop = document.getElementById('checkout-btn-desktop');
        const btnMobile = document.getElementById('checkout-btn-mobile');

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

        // Format items payload for PosController
        const payloadItems = cart.map(item => ({
            material_name_or_type: item.material_name_or_type,
            requested_size: item.requested_size,
            fixed_length_m: item.fixed_length_m || null,
            custom_width_cm: item.custom_width_cm || null,
            area_m2: item.area_m2 || null,
            dimension_text: item.dimension_text || null,
            qty: item.qty
        }));

        fetch('{{ route("pos.checkout") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                payment_method: paymentMethod,
                items: payloadItems,
                is_dp: isDp,
                dp_amount: dpAmount,
                customer_name: customerName,
                customer_phone: customerPhone,
                due_date: dueDate,
                production_notes: productionNotes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (btnDesktop) {
                btnDesktop.disabled = false;
                btnDesktop.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> ${isDp ? 'Proses Simpan Pesanan DP' : 'Proses Bayar (Checkout)'}`;
            }
            if (btnMobile) {
                btnMobile.disabled = false;
                btnMobile.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> Konfirmasi & Bayar Tagihan`;
            }

            if (data.status === 'success' || data.success === true) {
                // Clear cart state
                cart = [];
                renderCart();

                const isPartial = data.payment_status === 'PARTIAL';

                // Show Green / Amber Success Card in Desktop Cart Panel
                if (successContainerDesktop) {
                    document.getElementById('success-inv-text').innerText = '#' + data.invoice_number;
                    
                    const badgeEl = document.getElementById('success-badge-tag');
                    if (isPartial) {
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
                        openSnaprintInvoice(data);
                    };
                }

                // Show Success SweetAlert with Instant 58mm Thermal Print Trigger
                const titleHtml = isPartial 
                    ? '<span style="color: #d97706; font-weight: 800;">Pesanan DP Tercatat!</span>'
                    : '<span style="color: #059669; font-weight: 800;">Transaksi LUNAS (PAID)</span>';

                const statusBadge = isPartial
                    ? `<span class="inline-block px-3 py-1 bg-amber-100 text-amber-800 border border-amber-400 rounded-md text-xs font-extrabold uppercase mb-2">
                        <i class="fa-solid fa-clock-rotate-left text-amber-600"></i> STATUS: DP (PARSIAL) &bull; SISA PIUTANG: Rp ${Number(data.remaining_amount || 0).toLocaleString('id-ID')}
                       </span>`
                    : `<span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 border border-emerald-400 rounded-md text-xs font-extrabold uppercase mb-2">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i> STATUS: PAID (LUNAS)
                       </span>`;

                Swal.fire({
                    icon: 'success',
                    title: titleHtml,
                    html: `
                        <div class="py-2 text-center">
                            ${statusBadge}
                            <div class="font-mono text-base font-bold text-slate-800 mt-1">#${data.invoice_number}</div>
                            ${data.customer_name ? `<div class="text-xs text-slate-700 font-semibold mt-1">Client: <strong>${data.customer_name}</strong></div>` : ''}
                            <div class="text-sm font-extrabold text-blue-900 mt-1 font-mono">
                                Total: Rp ${Number(data.total_price || 0).toLocaleString('id-ID')}
                                ${isPartial ? `<br><span class="text-emerald-700 text-xs">DP Masuk: Rp ${Number(data.paid_amount || 0).toLocaleString('id-ID')}</span>` : ''}
                            </div>
                            <div class="text-xs text-slate-500 mt-1">Metode: <strong>${data.payment_method}</strong> &bull; Kasir: ${data.cashier_name}</div>
                        </div>
                    `,
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonColor: '#2563eb',
                    denyButtonColor: '#0f172a',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fa-solid fa-print me-1"></i> Cetak Struk 58mm',
                    denyButtonText: '<i class="fa-solid fa-file-invoice me-1"></i> Buka Faktur / SPK',
                    cancelButtonText: 'Selesai (+ Transaksi Baru)'
                }).then((result) => {
                    if (result.isConfirmed && data.receipt_url) {
                        window.open(data.receipt_url, '_blank', 'width=450,height=600');
                    } else if (result.isDenied) {
                        openSnaprintInvoice(data);
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
            
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Koneksi bermasalah atau terjadi error pada server.' });
        });
    }
</script>
@endsection
