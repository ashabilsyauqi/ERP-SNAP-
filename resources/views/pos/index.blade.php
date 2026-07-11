@extends('layouts.app')

@section('content')
<div class="flex flex-col md:flex-row gap-6 h-[calc(100vh-140px)] animate-fade-in relative pb-16 md:pb-0">
    
    <!-- Left Column: Products Grid & Search (60%) -->
    <div class="w-full md:w-3/5 lg:w-2/3 flex flex-col gap-4 min-h-0">
        
        <!-- Products Header & Search -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200/60 flex flex-col sm:flex-row justify-between items-center gap-4 flex-shrink-0">
            <div>
                <h2 class="text-lg font-bold text-slate-950">POS Terminal</h2>
                <p class="text-xs text-slate-500">Select materials to build the customer's order</p>
            </div>
            
            <!-- Live Search Products -->
            <div class="relative w-full sm:w-64">
                <input type="text" id="product-search" onkeyup="filterProducts()" placeholder="Search products..." 
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/15 focus:border-indigo-500 text-xs transition duration-150">
                <div class="absolute left-3.5 top-3 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Products Cards Grid -->
        <div id="products-grid" class="grid grid-cols-2 lg:grid-cols-3 gap-4 overflow-y-auto pr-1 pb-4 flex-grow min-h-0">
            @foreach($materials as $material)
                <div class="product-card bg-white p-4 rounded-2xl border border-slate-200/60 shadow-sm hover:border-indigo-500 hover:shadow-md transition duration-200 cursor-pointer flex flex-col justify-between group relative" 
                     data-name="{{ strtolower($material->material_name) }}"
                     onclick="addToCart('{{ $material->material_name }}', {{ $material->fixed_size ?? 'null' }}, {{ $material->retail_price }}, {{ json_encode($material->wholesalePrices) }})">
                    
                    <div class="space-y-1">
                        <h3 class="font-bold text-slate-900 group-hover:text-indigo-600 transition text-sm sm:text-base leading-snug">{{ $material->material_name }}</h3>
                        
                        @if($material->fixed_size)
                            <div class="inline-flex items-center gap-1 bg-slate-100 text-slate-600 text-[10px] font-bold px-2 py-0.5 rounded-md">
                                Size: {{ $material->fixed_size }}m
                            </div>
                        @endif
                        
                        <!-- Stock Status Badge -->
                        <div class="mt-1">
                            @if($material->stock_qty > 0)
                                <span class="text-[10px] font-medium text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">
                                    {{ $material->stock_qty }} available
                                </span>
                            @else
                                <span class="text-[10px] font-medium text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded">
                                    Out of stock
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="flex justify-between items-baseline flex-wrap gap-1">
                            <span class="text-xs text-slate-400 font-medium">Retail Price</span>
                            <span class="font-bold text-slate-900 text-sm sm:text-base">Rp {{ number_format($material->retail_price, 0, ',', '.') }}</span>
                        </div>
                        
                        @if($material->wholesalePrices->count() > 0)
                            <div class="mt-2 border-t border-slate-100 pt-2 space-y-1">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Wholesale Discounts</span>
                                @foreach($material->wholesalePrices as $wp)
                                    <div class="flex justify-between text-[10px] text-emerald-700 bg-emerald-50/50 px-1.5 py-0.5 rounded font-medium">
                                        <span>Min {{ $wp->min_qty }} units:</span>
                                        <span class="font-bold">Rp {{ number_format($wp->wholesale_price, 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Stock Reference Widget -->
        <div class="bg-slate-100 p-4 rounded-2xl border border-slate-200/80 flex-shrink-0 overflow-x-auto whitespace-nowrap hidden sm:block">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">In-Stock Reference:</h4>
            <div class="flex gap-3 text-xs">
                @foreach($materials as $material)
                    <div class="bg-white px-3 py-1.5 rounded-xl shadow-sm border border-slate-200 flex items-center gap-1.5">
                        <span class="font-semibold text-slate-700 text-xs">{{ $material->material_name }}</span>
                        @if($material->fixed_size) 
                            <span class="text-[10px] text-indigo-500 font-medium">({{ $material->fixed_size }}m)</span> 
                        @endif
                        <span class="text-slate-300">|</span>
                        <span class="font-bold {{ $material->stock_qty > 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $material->stock_qty }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Right Column: Cart & Checkout (Desktop only, 40%) -->
    <div class="hidden md:flex md:w-2/5 lg:w-1/3 bg-white rounded-2xl border border-slate-200 shadow-lg flex-col overflow-hidden h-full">
        <!-- Cart Header -->
        <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <h2 class="text-base font-bold">Current Order</h2>
            </div>
            <span id="cart-item-count-badge" class="bg-indigo-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">0 items</span>
        </div>
        
        <!-- Cart Items List (Desktop) -->
        <div class="p-4 flex-grow overflow-y-auto bg-slate-50/50" id="cart-container-desktop">
            <!-- Injected by JS -->
        </div>

        <!-- Checkout Pricing & Action Area -->
        <div class="p-4 border-t border-slate-200 bg-white space-y-4">
            
            <!-- Payment Method Tiles (Desktop) -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Payment Method</label>
                <div class="grid grid-cols-3 gap-2">
                    <!-- Cash Button -->
                    <button type="button" onclick="setPaymentMethod('Cash')" id="pm-desktop-Cash" 
                        class="pm-tile flex flex-col items-center justify-center py-2.5 px-2 border border-slate-200 rounded-xl transition duration-150 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer">
                        <svg class="h-5 w-5 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="text-xs font-bold">Cash</span>
                    </button>
                    <!-- Transfer Button -->
                    <button type="button" onclick="setPaymentMethod('Transfer')" id="pm-desktop-Transfer" 
                        class="pm-tile flex flex-col items-center justify-center py-2.5 px-2 border border-slate-200 rounded-xl transition duration-150 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer">
                        <svg class="h-5 w-5 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span class="text-xs font-bold">Transfer</span>
                    </button>
                    <!-- QRIS Button -->
                    <button type="button" onclick="setPaymentMethod('QRIS')" id="pm-desktop-QRIS" 
                        class="pm-tile flex flex-col items-center justify-center py-2.5 px-2 border border-slate-200 rounded-xl transition duration-150 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer">
                        <svg class="h-5 w-5 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-16v.01M4 12h16M4 8h16m-4 8h2m-14 4h.01M4 4h4v4H4V4zm0 12h4v4H4v-4zm12 0h4v4h-4v-4zM12 12h.01" />
                        </svg>
                        <span class="text-xs font-bold">QRIS</span>
                    </button>
                </div>
            </div>

            <!-- Receipt Breakdown -->
            <div class="bg-slate-50 p-3 rounded-xl space-y-1.5 text-xs text-slate-500 font-medium">
                <div class="flex justify-between">
                    <span>Subtotal</span>
                    <span id="receipt-subtotal-desktop">Rp 0</span>
                </div>
                <div class="flex justify-between text-slate-800 font-bold border-t border-slate-200/60 pt-1.5 text-sm">
                    <span>Total Billing</span>
                    <span id="receipt-total-desktop">Rp 0</span>
                </div>
            </div>
            
            <div id="checkout-error-desktop" class="hidden bg-rose-50 border border-rose-150 text-rose-700 p-3 rounded-xl text-xs font-semibold"></div>

            <button onclick="processCheckout()" id="checkout-btn-desktop" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-emerald-600/10 transition duration-150 flex justify-center items-center gap-2 cursor-pointer disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Process Checkout</span>
            </button>
        </div>
    </div>

    <!-- ==================== MOBILE LAYOUT ELEMENTS ==================== -->
    
    <!-- Mobile Persistent Bottom Bar (shows when items are in cart) -->
    <div id="mobile-cart-bar" class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 px-6 py-4 flex items-center justify-between z-30 shadow-2xl transition duration-300">
        <div>
            <p id="mobile-cart-qty-text" class="text-xs font-medium text-slate-500">0 items selected</p>
            <p id="mobile-cart-total-text" class="text-lg font-extrabold text-slate-900">Rp 0</p>
        </div>
        
        <button onclick="toggleMobileCartDrawer(true)" id="mobile-cart-review-btn" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 px-6 rounded-xl flex items-center gap-2 shadow-lg shadow-indigo-600/10 transition cursor-pointer">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            <span>Review & Pay</span>
        </button>
    </div>

    <!-- Mobile Cart Slide-Up Drawer -->
    <div id="mobile-cart-drawer" class="fixed inset-0 z-50 md:hidden hidden flex flex-col justify-end bg-slate-950/60 backdrop-blur-sm transition-opacity duration-300 opacity-0">
        <div id="mobile-drawer-panel" class="bg-white w-full max-h-[85vh] rounded-t-3xl shadow-2xl flex flex-col transform translate-y-full transition-transform duration-300 ease-out overflow-hidden">
            
            <!-- Drawer Header -->
            <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center flex-shrink-0">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span class="font-bold text-base">Your Cart Details</span>
                </div>
                
                <button type="button" onclick="toggleMobileCartDrawer(false)" class="text-slate-400 hover:text-white p-1 rounded-full hover:bg-slate-800 transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Cart Items Mobile Scroll Region -->
            <div class="p-6 overflow-y-auto bg-slate-50 flex-grow" id="cart-container-mobile">
                <!-- Injected by JS -->
            </div>

            <!-- Checkout Section Mobile -->
            <div class="p-6 border-t border-slate-200 bg-white space-y-5 flex-shrink-0">
                <!-- Payment Method Tiles (Mobile) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Payment Method</label>
                    <div class="grid grid-cols-3 gap-2">
                        <!-- Cash Button Mobile -->
                        <button type="button" onclick="setPaymentMethod('Cash')" id="pm-mobile-Cash" 
                            class="pm-tile flex flex-col items-center justify-center py-2.5 px-2 border border-slate-200 rounded-xl transition duration-150 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer">
                            <svg class="h-5 w-5 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="text-xs font-bold">Cash</span>
                        </button>
                        <!-- Transfer Button Mobile -->
                        <button type="button" onclick="setPaymentMethod('Transfer')" id="pm-mobile-Transfer" 
                            class="pm-tile flex flex-col items-center justify-center py-2.5 px-2 border border-slate-200 rounded-xl transition duration-150 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer">
                            <svg class="h-5 w-5 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <span class="text-xs font-bold">Transfer</span>
                        </button>
                        <!-- QRIS Button Mobile -->
                        <button type="button" onclick="setPaymentMethod('QRIS')" id="pm-mobile-QRIS" 
                            class="pm-tile flex flex-col items-center justify-center py-2.5 px-2 border border-slate-200 rounded-xl transition duration-150 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer">
                            <svg class="h-5 w-5 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-16v.01M4 12h16M4 8h16m-4 8h2m-14 4h.01M4 4h4v4H4V4zm0 12h4v4H4v-4zm12 0h4v4h-4v-4zM12 12h.01" />
                            </svg>
                            <span class="text-xs font-bold">QRIS</span>
                        </button>
                    </div>
                </div>

                <!-- Price summary -->
                <div class="bg-slate-50 p-4 rounded-xl space-y-1.5 text-xs text-slate-500 font-medium">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span id="receipt-subtotal-mobile">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-slate-800 font-bold border-t border-slate-200/60 pt-2 text-base">
                        <span>Total Billing</span>
                        <span id="receipt-total-mobile">Rp 0</span>
                    </div>
                </div>

                <div id="checkout-error-mobile" class="hidden bg-rose-50 border border-rose-150 text-rose-700 p-3 rounded-xl text-xs font-semibold"></div>

                <button onclick="processCheckout()" id="checkout-btn-mobile" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-emerald-600/10 transition flex justify-center items-center gap-2 cursor-pointer disabled:opacity-50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Confirm & Pay Billing</span>
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

    // --- Update Quantities ---
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
        
        const receiptSubtotalDesktop = document.getElementById('receipt-subtotal-desktop');
        const receiptTotalDesktop = document.getElementById('receipt-total-desktop');
        
        const receiptSubtotalMobile = document.getElementById('receipt-subtotal-mobile');
        const receiptTotalMobile = document.getElementById('receipt-total-mobile');
        
        const mobileCartBar = document.getElementById('mobile-cart-bar');
        const mobileCartQtyText = document.getElementById('mobile-cart-qty-text');
        const mobileCartTotalText = document.getElementById('mobile-cart-total-text');

        const checkoutBtnDesktop = document.getElementById('checkout-btn-desktop');
        const checkoutBtnMobile = document.getElementById('checkout-btn-mobile');

        // Total count of units in cart
        let totalItemsCount = cart.reduce((sum, item) => sum + item.qty, 0);

        if (cart.length === 0) {
            const emptyHtml = `
                <div class="text-center text-slate-400 py-12 flex flex-col items-center justify-center h-full">
                    <svg class="mx-auto h-12 w-12 text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="text-sm font-semibold">Cart is currently empty</p>
                    <p class="text-xs text-slate-400 mt-1">Click on any products to add them here.</p>
                </div>`;
            
            desktopContainer.innerHTML = emptyHtml;
            mobileContainer.innerHTML = emptyHtml;
            
            badgeCount.innerText = '0 items';
            receiptSubtotalDesktop.innerText = 'Rp 0';
            receiptTotalDesktop.innerText = 'Rp 0';
            receiptSubtotalMobile.innerText = 'Rp 0';
            receiptTotalMobile.innerText = 'Rp 0';
            
            // Hide bottom mobile bar if empty
            mobileCartBar.classList.add('translate-y-full');
            
            checkoutBtnDesktop.disabled = true;
            checkoutBtnMobile.disabled = true;
            return;
        }

        // Show mobile bottom bar if items present
        mobileCartBar.classList.remove('translate-y-full');
        badgeCount.innerText = `${totalItemsCount} ${totalItemsCount === 1 ? 'item' : 'items'}`;

        let total = 0;
        let itemsHtml = '';

        cart.forEach(item => {
            let priceDetails = getUnitPrice(item.retail_price, item.wholesale_prices, item.qty);
            let itemPrice = priceDetails.price;
            total += (item.qty * itemPrice);

            let sizeBadge = item.requested_size ? `<span class="text-[10px] bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-md font-bold">${item.requested_size}m size</span>` : '';
            let wholesaleBadge = priceDetails.isWholesale ? `<span class="text-[9px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-extrabold uppercase tracking-wider">Grosir</span>` : '';

            itemsHtml += `
                <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 mb-3 flex justify-between items-center shadow-sm hover:shadow transition duration-150 animate-fade-in">
                    <div class="flex-grow space-y-1">
                        <div class="font-bold text-slate-800 text-sm flex items-center flex-wrap gap-1.5">
                            <span>${item.material_name_or_type}</span>
                            ${sizeBadge}
                            ${wholesaleBadge}
                        </div>
                        <div class="text-xs font-semibold text-slate-500">
                            Rp ${itemPrice.toLocaleString()} &times; ${item.qty}
                        </div>
                    </div>
                    <div class="flex items-center gap-2 bg-slate-100 p-1 rounded-xl">
                        <button onclick="updateQty(${item.id}, -1)" class="w-7 h-7 flex items-center justify-center bg-white hover:bg-slate-50 rounded-lg text-slate-700 font-bold border border-slate-200/60 shadow-sm cursor-pointer">-</button>
                        <span class="w-6 text-center text-xs font-extrabold text-slate-800">${item.qty}</span>
                        <button onclick="updateQty(${item.id}, 1)" class="w-7 h-7 flex items-center justify-center bg-white hover:bg-slate-50 rounded-lg text-slate-700 font-bold border border-slate-200/60 shadow-sm cursor-pointer">+</button>
                    </div>
                </div>
            `;
        });

        // Set content
        desktopContainer.innerHTML = itemsHtml;
        mobileContainer.innerHTML = itemsHtml;

        const formattedTotal = `Rp ${total.toLocaleString()}`;
        receiptSubtotalDesktop.innerText = formattedTotal;
        receiptTotalDesktop.innerText = formattedTotal;
        receiptSubtotalMobile.innerText = formattedTotal;
        receiptTotalMobile.innerText = formattedTotal;

        mobileCartQtyText.innerText = `${totalItemsCount} items selected`;
        mobileCartTotalText.innerText = formattedTotal;

        checkoutBtnDesktop.disabled = false;
        checkoutBtnMobile.disabled = false;
    }

    // --- State Toggling for Payment Methods ---
    function setPaymentMethod(method) {
        // Set input hidden state
        document.getElementById('global_payment_method').value = method;

        // Reset all tiles
        document.querySelectorAll('.pm-tile').forEach(tile => {
            tile.classList.remove('border-indigo-600', 'bg-indigo-50/50', 'text-indigo-700', 'ring-2', 'ring-indigo-500/10');
            tile.classList.add('border-slate-200', 'bg-white', 'text-slate-600');
        });

        // Activate matching desktop and mobile tiles
        const desktopTile = document.getElementById(`pm-desktop-${method}`);
        const mobileTile = document.getElementById(`pm-mobile-${method}`);

        if (desktopTile) {
            desktopTile.classList.remove('border-slate-200', 'bg-white', 'text-slate-600');
            desktopTile.classList.add('border-indigo-600', 'bg-indigo-50/50', 'text-indigo-700', 'ring-2', 'ring-indigo-500/10');
        }

        if (mobileTile) {
            mobileTile.classList.remove('border-slate-200', 'bg-white', 'text-slate-600');
            mobileTile.classList.add('border-indigo-600', 'bg-indigo-50/50', 'text-indigo-700', 'ring-2', 'ring-indigo-500/10');
        }
    }

    // --- Toggle Mobile Slide-up Drawer ---
    function toggleMobileCartDrawer(open) {
        const container = document.getElementById('mobile-cart-drawer');
        const panel = document.getElementById('mobile-drawer-panel');

        if (open) {
            container.classList.remove('hidden');
            setTimeout(() => {
                container.classList.remove('opacity-0');
                container.classList.add('opacity-100');
                panel.classList.remove('translate-y-full');
                panel.classList.add('translate-y-0');
            }, 10);
        } else {
            container.classList.remove('opacity-100');
            container.classList.add('opacity-0');
            panel.classList.remove('translate-y-0');
            panel.classList.add('translate-y-full');
            setTimeout(() => {
                container.classList.add('hidden');
            }, 300);
        }
    }

    // --- Filter Products Grid (Live Search) ---
    function filterProducts() {
        const searchInput = document.getElementById('product-search').value.toLowerCase();
        const cards = document.querySelectorAll('.product-card');

        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            if (name.includes(searchInput)) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }

    // --- Process Checkout Call ---
    async function processCheckout() {
        if (cart.length === 0) return;

        const btnDesktop = document.getElementById('checkout-btn-desktop');
        const btnMobile = document.getElementById('checkout-btn-mobile');
        
        const errDesktop = document.getElementById('checkout-error-desktop');
        const errMobile = document.getElementById('checkout-error-mobile');
        
        const paymentMethod = document.getElementById('global_payment_method').value;

        // Start Loading State
        btnDesktop.disabled = true;
        btnDesktop.innerText = 'Processing...';
        btnMobile.disabled = true;
        btnMobile.innerText = 'Processing...';
        
        errDesktop.classList.add('hidden');
        errMobile.classList.add('hidden');

        try {
            const response = await fetch('{{ route('pos.checkout') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ 
                    items: cart,
                    payment_method: paymentMethod
                })
            });

            const data = await response.json();

            if (data.success) {
                alert(data.message);
                window.location.href = data.redirect;
            } else {
                errDesktop.innerText = data.message || 'Checkout failed.';
                errDesktop.classList.remove('hidden');
                
                errMobile.innerText = data.message || 'Checkout failed.';
                errMobile.classList.remove('hidden');
                
                btnDesktop.disabled = false;
                btnDesktop.innerText = 'Process Checkout';
                btnMobile.disabled = false;
                btnMobile.innerText = 'Confirm & Pay Billing';
            }
        } catch (error) {
            const netError = 'Network error during checkout.';
            errDesktop.innerText = netError;
            errDesktop.classList.remove('hidden');
            
            errMobile.innerText = netError;
            errMobile.classList.remove('hidden');
            
            btnDesktop.disabled = false;
            btnDesktop.innerText = 'Process Checkout';
            btnMobile.disabled = false;
            btnMobile.innerText = 'Confirm & Pay Billing';
        }
    }

    // Initialize View with default Cash payment method
    setPaymentMethod('Cash');
    renderCart();
</script>
@endsection
