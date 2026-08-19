<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') | SnapPrint ERP Bladewind UI</title>

    <!-- Google Font: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons & FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- ApexCharts CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css">
    <!-- Driver.js for Guided Tour -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css"/>

    <!-- Bladewind UI CSS -->
    <link href="{{ asset('vendor/bladewind/css/animate.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('vendor/bladewind/css/bladewind-ui.min.css') }}" rel="stylesheet" />

    <!-- Tailwind CSS CDN Engine -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            corePlugins: {
                preflight: false,
            },
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        },
                        indigo: {
                            50: '#eep2ff',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- AutoNumeric.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
    <!-- SheetJS (xlsx) CDN for 1-Click Excel Export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <!-- SignaturePad.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important; 
            background-color: #f8fafc;
        }
        svg {
            max-width: 100%;
            height: auto;
            display: inline-block;
            vertical-align: middle;
        }
        .btn-outline-indigo {
            color: #4f46e5;
            border-color: #4f46e5;
        }
        .btn-outline-indigo:hover {
            background-color: #4f46e5;
            color: #ffffff;
        }
        .text-indigo { color: #4f46e5 !important; }
        .bg-indigo-50 { background-color: #eef2ff !important; }
        .text-indigo-600 { color: #4f46e5 !important; }
        /* Custom Sort Indicator */
        th.sortable { cursor: pointer; user-select: none; position: relative; }
        th.sortable:hover { background-color: rgba(241, 245, 249, 0.8); }
        .sort-icon { display: inline-block; margin-left: 6px; font-size: 0.75rem; transition: transform 0.2s; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen" x-data="{ sidebarOpen: true }">

    <div class="flex h-screen overflow-hidden bg-slate-100">

        <!-- Bladewind-inspired Sleek Sidebar Nav -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="bg-slate-900 text-slate-300 flex flex-col transition-all duration-300 ease-in-out shadow-2xl relative z-30 flex-shrink-0">
            <!-- Brand Logo -->
            <div class="h-16 px-5 flex items-center justify-between border-b border-slate-800/80 bg-slate-950/60">
                <a href="{{ route('owner.dashboard') }}" class="flex items-center gap-3 overflow-hidden text-decoration-none">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30 flex-shrink-0">
                        <i class="bi bi-printer-fill fs-5"></i>
                    </div>
                    <div x-show="sidebarOpen" class="transition-opacity duration-200">
                        <h1 class="text-base font-extrabold text-white tracking-wide leading-tight mb-0">SnapPrint</h1>
                        <span class="text-[10px] font-semibold tracking-wider text-indigo-400 uppercase">ERP Enterprise</span>
                    </div>
                </a>
                <button @click="sidebarOpen = !sidebarOpen" class="text-slate-400 hover:text-white p-1 rounded-lg transition focus:outline-none hidden md:block">
                    <i :class="sidebarOpen ? 'bi bi-chevron-left' : 'bi bi-chevron-right'"></i>
                </button>
            </div>

            <!-- Active Branch Info Pill -->
            <div x-show="sidebarOpen" class="px-4 py-3 border-b border-slate-800/50 bg-slate-900/40">
                <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-800/80 border border-slate-700/60">
                    <i class="bi bi-building text-indigo-400"></i>
                    <div class="overflow-hidden">
                        <p class="text-[10px] uppercase font-bold text-slate-400 mb-0">Cabang Aktif</p>
                        <p class="text-xs font-semibold text-slate-200 truncate mb-0">
                            {{ auth()->user()->branch->nama_cabang ?? 'Pusat (Global)' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6 hide-scrollbar">
                
                <!-- Main Analytics -->
                <div>
                    <p x-show="sidebarOpen" class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Utama & Analitik</p>
                    <div class="space-y-1">
                        @if(auth()->user()->isOwner() || auth()->user()->isManager())
                            <a href="{{ route('owner.dashboard') }}" id="tour-step-dashboard" 
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-xs text-decoration-none transition duration-150 {{ request()->routeIs('owner.dashboard') ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                                <i class="bi bi-grid-1x2-fill text-sm"></i>
                                <span x-show="sidebarOpen">Dashboard Owner</span>
                            </a>
                        @endif

                        <a href="{{ route('dashboard') }}" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-xs text-decoration-none transition duration-150 {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                            <i class="bi bi-wallet2 text-sm"></i>
                            <span x-show="sidebarOpen">Dashboard Keuangan</span>
                        </a>
                    </div>
                </div>

                <!-- Stock & Inventory (Odoo Standard) -->
                <div>
                    <p x-show="sidebarOpen" class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Stock & Inventory (Odoo)</p>
                    <div class="space-y-1" x-data="{ openStock: true }">
                        <button @click="openStock = !openStock" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium text-slate-400 hover:bg-slate-800/70 hover:text-slate-200 transition text-left focus:outline-none">
                            <div class="flex items-center gap-3">
                                <i class="bi bi-box-seam-fill text-amber-400"></i>
                                <span x-show="sidebarOpen">Stock & Master Data</span>
                            </div>
                            <i x-show="sidebarOpen" :class="openStock ? 'bi bi-chevron-down' : 'bi bi-chevron-right'" class="text-[10px]"></i>
                        </button>
                        
                        <div x-show="openStock && sidebarOpen" class="pl-7 space-y-1 mt-1">
                            <a href="{{ route('materials.index') }}" 
                                class="block px-3 py-2 rounded-lg text-xs text-decoration-none transition {{ request()->routeIs('materials.*') ? 'text-indigo-400 font-bold bg-indigo-950/40' : 'text-slate-400 hover:text-slate-200' }}">
                                • Master Bahan & Produk
                            </a>
                            <a href="{{ route('stock.index') }}" 
                                class="block px-3 py-2 rounded-lg text-xs text-decoration-none transition {{ request()->routeIs('stock.index') ? 'text-indigo-400 font-bold bg-indigo-950/40' : 'text-slate-400 hover:text-slate-200' }}">
                                • Data Stok & Opname
                            </a>
                            <a href="{{ route('stock.inspection') }}" 
                                class="block px-3 py-2 rounded-lg text-xs text-decoration-none transition {{ request()->routeIs('stock.inspection') ? 'text-indigo-400 font-bold bg-indigo-950/40' : 'text-slate-400 hover:text-slate-200' }}">
                                • Pemeriksaan Barang (GRN)
                            </a>
                            <a href="{{ route('stock.rejected') }}" 
                                class="block px-3 py-2 rounded-lg text-xs text-decoration-none transition {{ request()->routeIs('stock.rejected') ? 'text-indigo-400 font-bold bg-indigo-950/40' : 'text-slate-400 hover:text-slate-200' }}">
                                • Riwayat Retur & Reject
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Purchasing (Odoo Standard) -->
                <div>
                    <p x-show="sidebarOpen" class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Purchasing (Odoo)</p>
                    <div class="space-y-1" x-data="{ openPurchasing: true }">
                        <button @click="openPurchasing = !openPurchasing" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium text-slate-400 hover:bg-slate-800/70 hover:text-slate-200 transition text-left focus:outline-none">
                            <div class="flex items-center gap-3">
                                <i class="bi bi-cart-check-fill text-emerald-400"></i>
                                <span x-show="sidebarOpen">Pengadaan (Purchasing)</span>
                            </div>
                            <i x-show="sidebarOpen" :class="openPurchasing ? 'bi bi-chevron-down' : 'bi bi-chevron-right'" class="text-[10px]"></i>
                        </button>

                        <div x-show="openPurchasing && sidebarOpen" class="pl-7 space-y-1 mt-1">
                            <a href="{{ route('purchasing.index') }}" 
                                class="block px-3 py-2 rounded-lg text-xs text-decoration-none transition {{ request()->routeIs('purchasing.index') ? 'text-indigo-400 font-bold bg-indigo-950/40' : 'text-slate-400 hover:text-slate-200' }}">
                                • Pengajuan PO (RFQ)
                            </a>
                            <a href="{{ route('purchasing.history') }}" 
                                class="block px-3 py-2 rounded-lg text-xs text-decoration-none transition {{ request()->routeIs('purchasing.history') ? 'text-indigo-400 font-bold bg-indigo-950/40' : 'text-slate-400 hover:text-slate-200' }}">
                                • Riwayat & Log PO
                            </a>
                            <a href="{{ route('suppliers.index') }}" 
                                class="block px-3 py-2 rounded-lg text-xs text-decoration-none transition {{ request()->routeIs('suppliers.*') ? 'text-indigo-400 font-bold bg-indigo-950/40' : 'text-slate-400 hover:text-slate-200' }}">
                                • Data Supplier (Vendors)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- POS & Cashier -->
                <div>
                    <p x-show="sidebarOpen" class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Kasir & Penjualan</p>
                    <div class="space-y-1">
                        <a href="{{ route('sales.index') }}" id="tour-step-sales" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-xs text-decoration-none transition {{ request()->routeIs('sales.*') ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                            <i class="bi bi-receipt-cutoff text-sm text-cyan-400"></i>
                            <span x-show="sidebarOpen">Riwayat Transaksi Penjualan</span>
                        </a>
                        <a href="{{ route('pos.index') }}" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-xs text-decoration-none transition {{ request()->routeIs('pos.*') ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                            <i class="bi bi-cash-stack text-sm text-rose-400"></i>
                            <span x-show="sidebarOpen">POS Kasir Checkout</span>
                        </a>
                    </div>
                </div>

                <!-- Account & Settings -->
                <div>
                    <p x-show="sidebarOpen" class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Sistem & Pengguna</p>
                    <div class="space-y-1">
                        @if(auth()->user()->isOwner() || auth()->user()->isManager())
                            <a href="{{ route('users.index') }}" 
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-xs text-decoration-none transition {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                                <i class="bi bi-people-fill text-sm"></i>
                                <span x-show="sidebarOpen">Manajemen User</span>
                            </a>
                        @endif

                        <a href="{{ route('profile.index') }}" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-xs text-decoration-none transition {{ request()->routeIs('profile.*') ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                            <i class="bi bi-person-bounding-box text-sm"></i>
                            <span x-show="sidebarOpen">Profil & Tanda Tangan</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- Footer User Section -->
            <div class="p-3 border-t border-slate-800/80 bg-slate-950/40">
                <div class="flex items-center justify-between gap-2 p-2 rounded-xl bg-slate-800/50">
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <x-bladewind::avatar name="{{ auth()->user()->username }}" size="small" />
                        <div x-show="sidebarOpen" class="overflow-hidden">
                            <p class="text-xs font-bold text-white truncate mb-0">{{ auth()->user()->username }}</p>
                            <p class="text-[10px] text-indigo-400 uppercase font-semibold mb-0">{{ auth()->user()->role }}</p>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline" x-show="sidebarOpen">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-rose-400 p-1.5 rounded-lg transition" title="Logout Account">
                            <i class="bi bi-box-arrow-right text-base"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- Top Header Navbar (Bladewind Style) -->
            <header class="h-16 bg-white border-b border-slate-200/80 px-6 flex items-center justify-between shadow-sm z-20">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-slate-500 hover:text-slate-800 p-2 rounded-xl hover:bg-slate-100 transition focus:outline-none md:hidden">
                        <i class="bi bi-list text-xl"></i>
                    </button>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 tracking-tight mb-0">@yield('page-title', 'Dashboard System')</h2>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Guided Tour Driver.js Button -->
                    <button type="button" onclick="startGuidedTour()" id="tour-button" class="btn btn-sm btn-outline-indigo rounded-pill px-3 font-semibold d-inline-flex align-items-center gap-1">
                        <i class="bi bi-compass-fill text-indigo-600"></i>
                        <span class="d-none d-sm-inline">Petunjuk Navigasi</span>
                    </button>

                    <!-- Bladewind Notification Bell -->
                    <x-bladewind::bell has_unread="true" />

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-link p-0 text-decoration-none d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <x-bladewind::avatar name="{{ auth()->user()->username }}" size="small" />
                            <span class="text-xs font-semibold text-slate-700 d-none d-md-inline">{{ auth()->user()->username }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-2" style="min-width: 200px;">
                            <li>
                                <a class="dropdown-menu-item dropdown-item rounded-3 text-xs fw-semibold py-2" href="{{ route('profile.index') }}">
                                    <i class="bi bi-person me-2 text-indigo"></i> Profil Account
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item rounded-3 text-xs fw-semibold py-2 text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Keluar (Logout)
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Main Page Content Body -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-100/70">
                <!-- Flash Notification Alerts -->
                @if(session('success'))
                    <div class="mb-4">
                        <x-bladewind::alert type="success" show_close_icon="true">
                            {{ session('success') }}
                        </x-bladewind::alert>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4">
                        <x-bladewind::alert type="error" show_close_icon="true">
                            {{ session('error') }}
                        </x-bladewind::alert>
                    </div>
                @endif

                @yield('content')
            </main>

        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- ApexCharts JS -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"></script>
    <!-- Driver.js for Guided Tour -->
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
    <!-- Bladewind UI Helper JS -->
    <script src="{{ asset('vendor/bladewind/js/helpers.js') }}"></script>

    <script>
        // Guided Tour Engine (Tokopedia/Admin LTE 4 style)
        function startGuidedTour() {
            if (typeof driver === 'undefined') return;
            const driverObj = driver.js.driver({
                showProgress: true,
                steps: [
                    { element: '#tour-button', popover: { title: 'Petunjuk Navigasi Tokopedia-Style', description: 'Klik tombol ini kapan saja untuk mempelajari alur penggunaan fitur di SnapPrint ERP.', side: "bottom" } },
                    { element: '#tour-step-dashboard', popover: { title: 'Dashboard Analytics', description: 'Pantau indikator kinerja utama (KPI) omset, laba bersih, dan grafik metode pembayaran.', side: "right" } },
                    { element: '#tour-step-sales', popover: { title: 'POS Kasir Cetak Nota', description: 'Area kasir toko untuk melakukan transaksi cetak banner, stiker, atau A3+ dengan struk nota otomatis.', side: "right" } }
                ]
            });
            driverObj.drive();
        }

        // Global Table Column Sorting Engine
        document.addEventListener('click', function(e) {
            const th = e.target.closest('th.sortable');
            if (!th) return;

            const table = th.closest('table');
            if (!table) return;

            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const thIndex = Array.from(th.parentNode.children).indexOf(th);
            const isAsc = th.getAttribute('data-sort') !== 'asc';

            Array.from(th.parentNode.children).forEach(sibling => {
                sibling.removeAttribute('data-sort');
                const icon = sibling.querySelector('.sort-icon');
                if (icon) icon.innerHTML = '⇅';
            });

            th.setAttribute('data-sort', isAsc ? 'asc' : 'desc');
            
            let icon = th.querySelector('.sort-icon');
            if (!icon) {
                icon = document.createElement('span');
                icon.className = 'sort-icon';
                th.appendChild(icon);
            }
            icon.innerHTML = isAsc ? '▲' : '▼';

            const rows = Array.from(tbody.querySelectorAll('tr:not(.no-sort)'));
            if (rows.length <= 1) return;

            rows.sort((a, b) => {
                const aCol = (a.children[thIndex]?.innerText || '').trim().toLowerCase();
                const bCol = (b.children[thIndex]?.innerText || '').trim().toLowerCase();

                const aNum = parseFloat(aCol.replace(/[^0-9.-]+/g, ''));
                const bNum = parseFloat(bCol.replace(/[^0-9.-]+/g, ''));

                if (!isNaN(aNum) && !isNaN(bNum) && !aCol.includes('-') && !bCol.includes('-')) {
                    return isAsc ? aNum - bNum : bNum - aNum;
                }

                return isAsc ? aCol.localeCompare(bCol) : bCol.localeCompare(aCol);
            });

            rows.forEach(row => tbody.appendChild(row));
        });

        // Global Dual Engine Live Search (Filters Table Rows & Grid Cards simultaneously)
        document.addEventListener('input', function(e) {
            if (!e.target.matches('.table-search-input, [data-table-search]')) return;

            const query = e.target.value.toLowerCase().trim();
            const container = e.target.closest('.card, .tab-view, .container-fluid, body');

            if (!container) return;

            // Filter Table Rows
            const rows = container.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });

            // Filter Grid Cards
            const cards = container.querySelectorAll('.grid-card, .search-card');
            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        });

        // Global View Mode Toggle Utility (List Table vs Grid Cards)
        function toggleViewMode(viewType, containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            const tableView = container.querySelector('.table-view-container');
            const gridView = container.querySelector('.grid-view-container');
            const btnList = container.querySelector('.btn-view-list');
            const btnGrid = container.querySelector('.btn-view-grid');

            if (viewType === 'grid') {
                if (tableView) tableView.classList.add('d-none');
                if (gridView) gridView.classList.remove('d-none');
                if (btnList) {
                    btnList.classList.remove('active', 'btn-primary');
                    btnList.classList.add('btn-outline-secondary');
                }
                if (btnGrid) {
                    btnGrid.classList.add('active', 'btn-primary');
                    btnGrid.classList.remove('btn-outline-secondary');
                }
            } else {
                if (gridView) gridView.classList.add('d-none');
                if (tableView) tableView.classList.remove('d-none');
                if (btnGrid) {
                    btnGrid.classList.remove('active', 'btn-primary');
                    btnGrid.classList.add('btn-outline-secondary');
                }
                if (btnList) {
                    btnList.classList.add('active', 'btn-primary');
                    btnList.classList.remove('btn-outline-secondary');
                }
            }

            localStorage.setItem('preferred_view_' + containerId, viewType);
        }

        // SweetAlert2 Toast Helper
        const Toast = typeof Swal !== 'undefined' ? Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        }) : null;

        // Global SweetAlert2 Action Confirmation
        function confirmAction(title, text, confirmButtonText, onConfirm) {
            if (typeof Swal === 'undefined') {
                if (confirm(title + '\n' + text)) onConfirm();
                return;
            }
            Swal.fire({
                title: title || 'Apakah Anda Yakin?',
                text: text || 'Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#64748b',
                confirmButtonText: confirmButtonText || 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal',
                customClass: { popup: 'rounded-4 shadow' }
            }).then((result) => {
                if (result.isConfirmed) {
                    onConfirm();
                }
            });
        }

        // 1-Click Excel Export Utility (SheetJS)
        function exportTableToExcel(tableId, filename) {
            if (typeof XLSX === 'undefined') {
                alert('Library SheetJS belum siap.');
                return;
            }
            const table = (tableId ? document.getElementById(tableId) : null) || document.querySelector('table');
            if (!table) {
                if (Toast) Toast.fire({ icon: 'error', title: 'Tabel data tidak ditemukan.' });
                return;
            }
            const wb = XLSX.utils.table_to_book(table, { sheet: "Data Export" });
            XLSX.writeFile(wb, (filename || 'SnapPrint_ERP_Export') + '.xlsx');
            if (Toast) Toast.fire({ icon: 'success', title: '📊 File Excel Berhasil Diunduh!' });
        }

        // Auto restore preferred view mode & initialize AutoNumeric on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-view-wrapper]').forEach(wrapper => {
                const containerId = wrapper.id;
                if (!containerId) return;
                const savedView = localStorage.getItem('preferred_view_' + containerId);
                if (savedView) {
                    toggleViewMode(savedView, containerId);
                }
            });

            // Initialize AutoNumeric on currency input fields if library exists
            if (typeof AutoNumeric !== 'undefined') {
                document.querySelectorAll('.autonumeric-rupiah').forEach(input => {
                    new AutoNumeric(input, {
                        currencySymbol: 'Rp ',
                        digitGroupSeparator: '.',
                        decimalCharacter: ',',
                        decimalPlaces: 0,
                        unformatOnSubmit: true
                    });
                });
            }
        });
    </script>
</body>
</html>
