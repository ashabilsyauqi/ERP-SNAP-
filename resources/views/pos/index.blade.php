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
<div class="flex flex-col md:flex-row gap-4 h-[calc(100vh-125px)] animate-fade-in relative pb-16 md:pb-0 overflow-hidden" 
     id="pos-main-container"
     x-data="{ 
        isDp: false, 
        cartTotal: 0,
        minDpThreshold: 500000,
        customerName: '', 
        customerPhone: '', 
        dueDate: '', 
        dpAmount: 0, 
        productionNotes: '',
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
        handleCartTotalUpdate(total) {
            this.cartTotal = Number(total) || 0;
            if (this.cartTotal < this.minDpThreshold) {
                this.isDp = false;
                this.dpAmount = 0;
            } else if (this.isDp && (!this.dpAmount || this.dpAmount < this.minDpAmount)) {
                this.dpAmount = this.minDpAmount;
            }
        }
     }"
     @cart-total-changed.window="handleCartTotalUpdate($event.detail.total)">
    
    <!-- Left Column: Products Grid & Search (60% Desktop) -->
    <div class="w-full md:w-3/5 lg:w-2/3 flex flex-col gap-3 min-h-0 h-full">
        
        <!-- Products Header & Search -->
        <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 flex-shrink-0">
            <div>
                <h2 class="text-base font-bold text-slate-900 mb-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-cash-register text-blue-600"></i>
                    <span>Katalog Bahan Cetak Kasir</span>
                </h2>
                <p class="text-[11px] text-slate-500 mb-0">Klik pada kartu bahan baku untuk memasukkan ke keranjang kasir</p>
            </div>
            
            <!-- Live Search Products -->
            <div class="relative w-full sm:w-64">
                <input type="text" id="product-search" onkeyup="filterProducts()" placeholder="Cari bahan cetak..." 
                    class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 text-xs transition duration-150">
                <div class="absolute left-3 top-2.5 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
            </div>
        </div>
        
        <!-- Products Cards Grid (Independent Scroll) -->
        <div id="products-grid" class="grid grid-cols-2 lg:grid-cols-3 gap-3 overflow-y-auto pr-1 pb-2 flex-grow min-h-0">
            @foreach($materials as $material)
                <div class="product-card bg-white p-3 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-500 transition duration-150 cursor-pointer flex flex-col justify-between"
                     data-name="{{ strtolower($material->material_name) }}"
                     onclick="addToCart('{{ $material->material_name }}', '{{ $material->fixed_size }}', {{ $material->retail_price }}, {{ json_encode($material->wholesalePrices) }})">
                    
                    <div>
                        <div class="flex justify-between items-start mb-1">
                            <span class="badge bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                {{ $material->category ?? 'Bahan' }}
                            </span>
                            <span class="text-[10px] {{ $material->stock_qty > 10 ? 'text-emerald-600' : ($material->stock_qty > 0 ? 'text-amber-600' : 'text-rose-600') }} font-bold">
                                Stok: {{ $material->stock_qty }} {{ $material->unit ?? 'pcs' }}
                            </span>
                        </div>
                        
                        <h3 class="font-bold text-slate-900 text-xs line-clamp-2 mb-1">
                            {{ $material->material_name }}
                        </h3>
                        
                        @if($material->fixed_size)
                            <div class="text-[11px] text-slate-500 mb-2">
                                Ukuran: <strong class="text-slate-700">{{ $material->fixed_size }} m</strong>
                            </div>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex justify-between items-center mt-2">
                        <div>
                            <span class="text-[10px] text-slate-400 block leading-tight">Harga Satuan</span>
                            <span class="font-bold text-blue-900 font-mono text-xs">
                                Rp {{ number_format($material->retail_price, 0, ',', '.') }}
                            </span>
                        </div>
                        
                        <button type="button" class="w-7 h-7 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg flex items-center justify-center transition border-0 cursor-pointer text-xs">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Stock Reference Widget (Docked at Bottom of Left Column) -->
        <div class="bg-slate-100 p-2.5 rounded-xl border border-slate-200 flex-shrink-0 overflow-x-auto whitespace-nowrap hidden sm:block">
            <div class="d-flex align-items-center gap-2">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Stok Real-time:</span>
                <div class="flex gap-2 text-xs">
                    @foreach($materials as $material)
                        <div class="bg-white px-2.5 py-1 rounded-lg shadow-sm border border-slate-200 flex items-center gap-1.5">
                            <span class="font-medium text-slate-700 text-[11px]">{{ $material->material_name }}</span>
                            @if($material->fixed_size) 
                                <span class="text-[10px] text-blue-600">({{ $material->fixed_size }}m)</span> 
                            @endif
                            <span class="text-slate-300">|</span>
                            <span class="font-bold font-mono text-xs {{ $material->stock_qty > 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $material->stock_qty }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Cart & Checkout (Desktop 40%, Fits Exact Height) -->
    <div class="hidden md:flex md:w-2/5 lg:w-1/3 bg-white rounded-2xl border border-slate-200 shadow-sm flex-col overflow-hidden h-full">
        <!-- Cart Header -->
        <div class="p-3 bg-slate-900 text-white flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-cart-shopping text-blue-400"></i>
                <h2 class="text-sm font-bold mb-0 text-white">Keranjang Order (POS)</h2>
            </div>
            <span id="cart-item-count-badge" class="bg-blue-600 text-white text-[11px] font-bold px-2 py-0.5 rounded-full">0 item</span>
        </div>
        
        <!-- Cart Items List (Desktop Independent Scroll) -->
        <div class="p-3 flex-grow overflow-y-auto bg-slate-50/50 min-h-0 space-y-2.5" id="cart-container-desktop">
            <!-- Injected by JS -->
        </div>

        <!-- Checkout Pricing & Action Area (Docked at Bottom) -->
        <div class="p-3 border-t border-slate-200 bg-white space-y-2.5 flex-shrink-0 overflow-y-auto max-h-[55vh]">
            
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

            <!-- Payment Method Tiles (Desktop) -->
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

    <!-- ==================== MOBILE LAYOUT ELEMENTS ==================== -->
    
    <!-- Mobile Persistent Bottom Bar -->
    <div id="mobile-cart-bar" class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 px-4 py-3 flex items-center justify-between z-30 shadow-2xl transition duration-300">
        <div>
            <p id="mobile-cart-qty-text" class="text-xs font-medium text-slate-500 mb-0">0 item terpilih</p>
            <p id="mobile-cart-total-text" class="text-base font-extrabold text-blue-900 mb-0 font-mono">Rp 0</p>
        </div>
        
        <button onclick="toggleMobileCartDrawer(true)" id="mobile-cart-review-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl flex items-center gap-2 transition cursor-pointer border-0 text-xs">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Lihat Keranjang</span>
        </button>
    </div>

    <!-- Mobile Cart Slide-Up Drawer -->
    <div id="mobile-cart-drawer" class="fixed inset-0 z-50 md:hidden hidden flex flex-col justify-end bg-slate-950/60 backdrop-blur-sm transition-opacity duration-300 opacity-0">
        <div id="mobile-drawer-panel" class="bg-white w-full max-h-[85vh] rounded-t-3xl shadow-2xl flex flex-col transform translate-y-full transition-transform duration-300 ease-out overflow-hidden">
            
            <!-- Drawer Header -->
            <div class="px-4 py-3 bg-slate-900 text-white flex justify-between items-center flex-shrink-0">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-cart-shopping text-blue-400"></i>
                    <span class="font-bold text-sm text-white">Keranjang Order</span>
                </div>
                
                <button type="button" onclick="toggleMobileCartDrawer(false)" class="text-slate-400 hover:text-white p-1 rounded-full border-0 bg-transparent">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <!-- Cart Items Mobile Scroll Region -->
            <div class="p-4 overflow-y-auto bg-slate-50 flex-grow" id="cart-container-mobile">
                <!-- Injected by JS -->
            </div>

            <!-- Checkout Section Mobile -->
            <div class="p-4 border-t border-slate-200 bg-white space-y-3 flex-shrink-0">
                <!-- Payment Method Tiles (Mobile) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Metode Pembayaran</label>
                    <div class="grid grid-cols-3 gap-2">
                        <!-- Cash Button Mobile -->
                        <button type="button" onclick="setPaymentMethod('Cash')" id="pm-mobile-Cash" 
                            class="pm-tile flex flex-col items-center justify-center py-2 px-2 border border-slate-200 rounded-xl transition duration-150 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer">
                            <i class="fa-solid fa-money-bill-wave text-base mb-1 text-emerald-600"></i>
                            <span class="text-xs font-bold">Cash</span>
                        </button>
                        <!-- Transfer Button Mobile -->
                        <button type="button" onclick="setPaymentMethod('Transfer')" id="pm-mobile-Transfer" 
                            class="pm-tile flex flex-col items-center justify-center py-2 px-2 border border-slate-200 rounded-xl transition duration-150 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer">
                            <i class="fa-solid fa-building-columns text-base mb-1 text-blue-600"></i>
                            <span class="text-xs font-bold">Transfer</span>
                        </button>
                        <!-- QRIS Button Mobile -->
                        <button type="button" onclick="setPaymentMethod('QRIS')" id="pm-mobile-QRIS" 
                            class="pm-tile flex flex-col items-center justify-center py-2 px-2 border border-slate-200 rounded-xl transition duration-150 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer">
                            <i class="fa-solid fa-qrcode text-base mb-1 text-indigo-600"></i>
                            <span class="text-xs font-bold">QRIS</span>
                        </button>
                    </div>
                </div>

                <!-- Price summary -->
                <div class="bg-slate-50 p-3 rounded-xl space-y-1 text-xs text-slate-500 font-medium">
                    <div class="flex justify-between text-slate-900 font-bold border-t border-slate-200 pt-1 text-sm">
                        <span>Total Tagihan</span>
                        <span id="receipt-total-mobile" class="font-mono text-blue-900">Rp 0</span>
                    </div>
                </div>

                <!-- Success Alert Mobile -->
                <div id="checkout-success-mobile" class="hidden bg-emerald-50 border border-emerald-300 text-emerald-900 p-3 rounded-xl text-xs space-y-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-emerald-600 text-white font-bold text-[10px] px-2 py-0.5 rounded">
                            <i class="fa-solid fa-circle-check me-1"></i> STATUS SUKSES
                        </span>
                        <span class="font-mono font-bold text-blue-900" id="success-inv-text-mobile"></span>
                    </div>
                    <div class="d-flex gap-2 pt-1">
                        <button type="button" id="btn-print-last-receipt-mobile" class="btn btn-sm btn-primary text-xs flex-1 py-1 font-semibold">
                            <i class="fa-solid fa-print me-1"></i> Cetak Struk 58mm
                        </button>
                    </div>
                </div>

                <div id="checkout-error-mobile" class="hidden bg-rose-50 border border-rose-200 text-rose-700 p-2.5 rounded-xl text-xs font-semibold"></div>

                <button onclick="processCheckout()" id="checkout-btn-mobile" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl shadow transition flex justify-center items-center gap-2 cursor-pointer disabled:opacity-50 border-0">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Konfirmasi & Bayar Tagihan</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Input holds global payment selection state -->
<input type="hidden" id="global_payment_method" value="Cash">

<script>
    let cart = [];
    let cartCounter = 0;
    window.currentGrandTotal = 0;

    // --- Add items to Cart ---
    function addToCart(materialName, fixedSize, retailPrice, wholesalePrices) {
        let size = fixedSize;

        // Check if item already exists in cart
        let existingItem = cart.find(i => i.material_name_or_type === materialName && i.requested_size === size);

        if (existingItem) {
            updateQty(existingItem.id, 1);
        } else {
            cart.push({
                id: cartCounter++,
                material_name_or_type: materialName,
                requested_size: size,
                qty: 1,
                retail_price: retailPrice,
                wholesale_prices: wholesalePrices
            });
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

    // --- Render Cart (Desktop + Mobile) ---
    function renderCart() {
        const desktopContainer = document.getElementById('cart-container-desktop');
        const mobileContainer = document.getElementById('cart-container-mobile');
        
        const badgeCount = document.getElementById('cart-item-count-badge');
        const receiptTotalDesktop = document.getElementById('receipt-total-desktop');
        const receiptTotalMobile = document.getElementById('receipt-total-mobile');
        
        const mobileCartBar = document.getElementById('mobile-cart-bar');
        const mobileCartQtyText = document.getElementById('mobile-cart-qty-text');
        const mobileCartTotalText = document.getElementById('mobile-cart-total-text');

        if (cart.length === 0) {
            const emptyState = `
                <div class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-cart-shopping text-xl text-slate-400"></i>
                    </div>
                    <p class="font-bold text-slate-700 text-sm mb-1">Keranjang Masih Kosong</p>
                    <p class="text-xs text-slate-400">Pilih bahan cetak dari katalog di sebelah kiri untuk memulai transaksi.</p>
                </div>
            `;
            desktopContainer.innerHTML = emptyState;
            mobileContainer.innerHTML = emptyState;
            badgeCount.innerText = '0 item';
            receiptTotalDesktop.innerText = 'Rp 0';
            receiptTotalMobile.innerText = 'Rp 0';
            window.currentGrandTotal = 0;
            
            window.dispatchEvent(new CustomEvent('cart-total-changed', { detail: { total: 0 } }));

            mobileCartBar.classList.add('hidden');
            return;
        }

        let totalQty = 0;
        let grandTotal = 0;
        let cartHtml = '<div class="space-y-2.5">';

        cart.forEach(item => {
            const { price, isWholesale } = getUnitPrice(item.retail_price, item.wholesale_prices, item.qty);
            const itemTotal = price * item.qty;
            
            totalQty += item.qty;
            grandTotal += itemTotal;

            cartHtml += `
                <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-sm flex flex-col gap-2">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="font-bold text-slate-900 text-xs">${item.material_name_or_type}</span>
                            ${item.requested_size ? `<span class="block text-[10px] text-blue-600 font-medium">Ukuran: ${item.requested_size}m</span>` : ''}
                            
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-[11px] font-mono text-slate-500">Rp ${Number(price).toLocaleString('id-ID')}</span>
                                ${isWholesale ? `<span class="text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 px-1 rounded">Grosir</span>` : ''}
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <span class="font-bold font-mono text-xs text-slate-900">Rp ${Number(itemTotal).toLocaleString('id-ID')}</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center border-t border-slate-100 pt-2 mt-1">
                        <button onclick="updateQty(${item.id}, -${item.qty})" class="text-[10px] text-rose-500 hover:text-rose-700 font-semibold bg-transparent border-0 cursor-pointer p-0">
                            <i class="fa-solid fa-trash-can me-0.5"></i> Hapus
                        </button>
                        
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

        desktopContainer.innerHTML = cartHtml;
        mobileContainer.innerHTML = cartHtml;

        badgeCount.innerText = `${totalQty} item`;
        window.currentGrandTotal = grandTotal;
        
        // Dispatch cart total changed event for Alpine reactivity
        window.dispatchEvent(new CustomEvent('cart-total-changed', { detail: { total: grandTotal } }));

        const formattedTotal = `Rp ${Number(grandTotal).toLocaleString('id-ID')}`;
        receiptTotalDesktop.innerText = formattedTotal;
        receiptTotalMobile.innerText = formattedTotal;

        // Mobile Bottom Bar Data
        mobileCartBar.classList.remove('hidden');
        mobileCartQtyText.innerText = `${totalQty} item terpilih`;
        mobileCartTotalText.innerText = formattedTotal;
    }

    // --- Search Products Filtering ---
    function filterProducts() {
        const query = document.getElementById('product-search').value.toLowerCase().trim();
        const cards = document.querySelectorAll('.product-card');

        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            if (name.includes(query)) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
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
        btnDesktop.disabled = true;
        btnMobile.disabled = true;
        btnDesktop.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...`;
        btnMobile.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...`;

        errContainerDesktop.classList.add('hidden');
        errContainerMobile.classList.add('hidden');
        if (successContainerDesktop) successContainerDesktop.classList.add('hidden');
        if (successContainerMobile) successContainerMobile.classList.add('hidden');

        // Format items payload for PosController
        const payloadItems = cart.map(item => ({
            material_name_or_type: item.material_name_or_type,
            requested_size: item.requested_size,
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
            btnDesktop.disabled = false;
            btnMobile.disabled = false;
            btnDesktop.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> ${isDp ? 'Proses Simpan Pesanan DP' : 'Proses Bayar (Checkout)'}`;
            btnMobile.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> Konfirmasi & Bayar Tagihan`;

            if (data.status === 'success' || data.success === true) {
                // Clear cart state
                cart = [];
                renderCart();
                toggleMobileCartDrawer(false);

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
                        openSnapPrintInvoice(data);
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
                        openSnapPrintInvoice(data);
                    }
                });

            } else {
                const errorMsg = data.message || 'Terjadi kesalahan sistem saat memproses transaksi kasir.';
                errContainerDesktop.innerText = errorMsg;
                errContainerDesktop.classList.remove('hidden');
                errContainerMobile.innerText = errorMsg;
                errContainerMobile.classList.remove('hidden');
            }
        })
        .catch(err => {
            btnDesktop.disabled = false;
            btnMobile.disabled = false;
            btnDesktop.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> Proses Bayar`;
            btnMobile.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> Konfirmasi & Bayar`;
            
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Koneksi bermasalah atau terjadi error pada server.' });
        });
    }
</script>
@endsection
