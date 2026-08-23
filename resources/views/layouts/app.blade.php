<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') | SnapPrint ERP Odoo Enterprise</title>

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
                            500: '#00A09D', // Odoo Primary Accent (Teal)
                            600: '#008b88',
                            700: '#007370',
                        },
                        indigo: {
                            50: '#fcf8fa',
                            600: '#714B67', // Odoo Enterprise Purple
                            700: '#5c3d54',
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
        :root {
            --o-primary-color: #714B67;
            --o-accent-color: #00A09D;
            --o-bg-color: #F1F2F4;
        }
        body { 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important; 
            background-color: var(--o-bg-color);
        }
        svg {
            max-width: 100%;
            height: auto;
            display: inline-block;
            vertical-align: middle;
        }
        
        /* Odoo Styling classes */
        .o_main_navbar {
            background-color: var(--o-primary-color);
            height: 46px;
            z-index: 1040;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }
        .o_control_panel {
            background-color: #ffffff;
            border-bottom: 1px solid #dee2e6;
            padding: 8px 16px;
            min-height: 52px;
            z-index: 1030;
        }
        .o_form_sheet_bg {
            background-color: var(--o-bg-color);
            min-height: calc(100vh - 98px);
            padding: 20px;
        }
        .o_form_sheet {
            background-color: #ffffff;
            border: 1px solid #c8c9cc;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.04);
            max-width: 1300px;
            margin: 0 auto;
            border-radius: 6px;
            padding: 24px;
        }
        
        .btn-outline-indigo {
            color: var(--o-primary-color);
            border-color: var(--o-primary-color);
        }
        .btn-outline-indigo:hover {
            background-color: var(--o-primary-color);
            color: #ffffff;
        }
        .text-indigo { color: var(--o-primary-color) !important; }
        .bg-indigo-50 { background-color: #f7f1f5 !important; }
        .text-indigo-600 { color: var(--o-primary-color) !important; }
        
        /* Custom Sort Indicator */
        th.sortable { cursor: pointer; user-select: none; position: relative; }
        th.sortable:hover { background-color: rgba(241, 245, 249, 0.8); }
        .sort-icon { display: inline-block; margin-left: 6px; font-size: 0.75rem; transition: transform 0.2s; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen" x-data="{ showSwitcher: false }">

    @php
        $currentRoute = request()->route() ? request()->route()->getName() : '';
        $activeApp = 'Dashboard';
        $submenus = [];

        if (Str::startsWith($currentRoute, 'owner.dashboard') || Str::startsWith($currentRoute, 'dashboard') || Str::startsWith($currentRoute, 'accounts') || Str::startsWith($currentRoute, 'kas-masuk') || Str::startsWith($currentRoute, 'kas-keluar') || Str::startsWith($currentRoute, 'reports')) {
            $activeApp = 'Accounting';
            $submenus = [
                ['route' => 'owner.dashboard', 'label' => 'Dashboard Owner', 'role' => 'owner,manager'],
                ['route' => 'dashboard', 'label' => 'Dashboard Keuangan', 'role' => 'owner,manager'],
                ['route' => 'accounts.index', 'label' => 'Bagan Akun (COA)', 'role' => 'owner,manager'],
                ['route' => 'kas-masuk.index', 'label' => 'Kas Masuk', 'role' => 'owner,manager'],
                ['route' => 'kas-keluar.index', 'label' => 'Kas Keluar', 'role' => 'owner,manager'],
                ['route' => 'reports.cash-balance', 'label' => 'Saldo Kas & Bank', 'role' => 'owner,manager'],
                ['route' => 'reports.cash-mutation', 'label' => 'Mutasi Buku Besar', 'role' => 'owner,manager'],
                ['route' => 'reports.profit-loss', 'label' => 'Laba & Rugi', 'role' => 'owner,manager'],
            ];
        } elseif (Str::startsWith($currentRoute, 'materials') || Str::startsWith($currentRoute, 'stock')) {
            $activeApp = 'Inventory';
            $submenus = [
                ['route' => 'materials.index', 'label' => 'Master Bahan & Produk', 'role' => 'owner,manager'],
                ['route' => 'stock.index', 'label' => 'Stok & Opname', 'role' => 'manager,owner'],
                ['route' => 'stock.inspection', 'label' => 'Pemeriksaan (GRN)', 'role' => 'manager,owner'],
                ['route' => 'stock.rejected', 'label' => 'Barang Reject & Retur', 'role' => 'manager,owner'],
            ];
        } elseif (Str::startsWith($currentRoute, 'purchasing') || Str::startsWith($currentRoute, 'suppliers')) {
            $activeApp = 'Purchase';
            $submenus = [
                ['route' => 'purchasing.index', 'label' => 'Pengajuan PO (RFQ)', 'role' => 'purchasing,owner,manager'],
                ['route' => 'purchasing.history', 'label' => 'Riwayat & Log PO', 'role' => 'purchasing,owner,manager'],
                ['route' => 'suppliers.index', 'label' => 'Data Supplier', 'role' => 'purchasing,owner,manager'],
            ];
        } elseif (Str::startsWith($currentRoute, 'pos') || Str::startsWith($currentRoute, 'sales')) {
            $activeApp = 'Point of Sale';
            $submenus = [
                ['route' => 'pos.index', 'label' => 'POS Kasir Checkout', 'role' => 'cashier,owner,manager'],
                ['route' => 'sales.index', 'label' => 'Log Transaksi Penjualan', 'role' => 'cashier,owner,manager'],
            ];
        } elseif (Str::startsWith($currentRoute, 'users') || Str::startsWith($currentRoute, 'profile')) {
            $activeApp = 'Settings';
            $submenus = [
                ['route' => 'users.index', 'label' => 'Manajemen User', 'role' => 'owner,manager'],
                ['route' => 'profile.index', 'label' => 'Profil & Tanda Tangan', 'role' => 'all'],
            ];
        }
    @endphp

    <div class="flex flex-col h-screen overflow-hidden">
        <!-- Odoo Top Main Navbar -->
        <nav class="o_main_navbar d-flex align-items-center justify-content-between px-3 text-white flex-shrink-0">
            <div class="d-flex align-items-center h-100 gap-3">
                <!-- App Switcher Toggle Button -->
                <button @click="showSwitcher = !showSwitcher" class="btn text-white p-2 d-flex align-items-center hover:bg-black/10 border-0 outline-none" title="Home Menu / App Switcher">
                    <i class="fa-solid fa-table-cells fs-5"></i>
                </button>

                <!-- Current App Brand Title -->
                <span class="fw-bold tracking-wide text-white border-end pe-3 border-white/20 h-50 d-flex align-items-center">
                    SnapPrint {{ $activeApp }}
                </span>

                <!-- Submenus (Horizontal list) -->
                <div class="d-none d-md-flex align-items-center h-100 gap-1 overflow-x-auto">
                    @foreach($submenus as $sub)
                        @php
                            $allowed = false;
                            if ($sub['role'] === 'all') {
                                $allowed = true;
                            } else {
                                $roles = explode(',', $sub['role']);
                                if (in_array(auth()->user()->role, $roles)) {
                                    $allowed = true;
                                }
                            }
                        @endphp
                        @if($allowed)
                            <a href="{{ route($sub['route']) }}" 
                               class="px-3 h-100 d-flex align-items-center text-white text-decoration-none hover:bg-white/10 transition-colors {{ request()->routeIs($sub['route']) ? 'bg-white/15 fw-bold border-bottom border-white border-2' : '' }}">
                                {{ $sub['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Right side details & actions -->
            <div class="d-flex align-items-center gap-3">
                <button type="button" onclick="startGuidedTour()" id="tour-button" class="btn btn-sm btn-link text-white text-decoration-none hover:bg-white/10 px-2 py-1 rounded d-inline-flex align-items-center gap-1.5 border-0">
                    <i class="fa-solid fa-compass"></i>
                    <span class="d-none d-sm-inline">Petunjuk Navigasi</span>
                </button>

                <span class="badge bg-white/20 border border-white/10 text-white font-mono text-[10px] d-none d-lg-inline-block px-2.5 py-1">
                    <i class="fa-solid fa-building me-1"></i>
                    {{ auth()->user()->branch->nama_cabang ?? 'Pusat (Global)' }}
                </span>

                <!-- Bladewind Bell -->
                <div class="text-white relative">
                    <x-bladewind::bell has_unread="true" />
                </div>

                <!-- User Profile Dropdown -->
                <div class="dropdown h-100">
                    <button class="btn btn-link p-0 text-decoration-none text-white d-flex align-items-center gap-2 dropdown-toggle border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <x-bladewind::avatar label="{{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 2)) }}" size="small" />
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-2" style="min-width: 200px;">
                        <li class="px-3 py-2 border-bottom mb-2">
                            <p class="text-xs font-bold text-slate-800 mb-0 truncate">{{ auth()->user()->username }}</p>
                            <p class="text-[10px] text-slate-500 uppercase font-semibold mb-0">{{ auth()->user()->role }}</p>
                        </li>
                        <li>
                            <a class="dropdown-menu-item dropdown-item rounded-3 text-xs fw-semibold py-2" href="{{ route('profile.index') }}">
                                <i class="fa-solid fa-user-gear me-2 text-indigo"></i> Profil & Signature
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item rounded-3 text-xs fw-semibold py-2 text-danger">
                                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Keluar (Logout)
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Odoo Control Panel Header -->
        <header class="o_control_panel d-flex flex-wrap justify-content-between align-items-center flex-shrink-0">
            <div class="d-flex align-items-center gap-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 align-items-center">
                        <li class="breadcrumb-item text-slate-400 text-xs font-medium uppercase tracking-wide">{{ $activeApp }}</li>
                        <li class="breadcrumb-item active text-slate-800 font-bold text-base" aria-current="page">@yield('page-title', 'Overview')</li>
                    </ol>
                </nav>
            </div>
            
            <!-- Mobile Menu Dropdown -->
            <div class="d-md-none dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Menu Modul
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    @foreach($submenus as $sub)
                        @php
                            $allowed = false;
                            if ($sub['role'] === 'all') {
                                $allowed = true;
                            } else {
                                $roles = explode(',', $sub['role']);
                                if (in_array(auth()->user()->role, $roles)) {
                                    $allowed = true;
                                }
                            }
                        @endphp
                        @if($allowed)
                            <li>
                                <a class="dropdown-item text-xs {{ request()->routeIs($sub['route']) ? 'active bg-indigo text-white' : '' }}" href="{{ route($sub['route']) }}">
                                    {{ $sub['label'] }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="o_form_sheet_bg flex-grow-1 overflow-y-auto">
            <!-- Flash Notification Alerts -->
            @if(session('success'))
                <div class="o_form_sheet mb-4 p-3 bg-emerald-50 border border-emerald-100 rounded-lg">
                    <x-bladewind::alert type="success" show_close_icon="true">
                        {{ session('success') }}
                    </x-bladewind::alert>
                </div>
            @endif

            @if(session('error'))
                <div class="o_form_sheet mb-4 p-3 bg-rose-50 border border-rose-100 rounded-lg">
                    <x-bladewind::alert type="error" show_close_icon="true">
                        {{ session('error') }}
                    </x-bladewind::alert>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Odoo Enterprise App Switcher Overlay -->
    <div x-show="showSwitcher" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 bg-gradient-to-tr from-slate-950 via-indigo-950 to-slate-900 p-6 flex flex-col items-center justify-start overflow-y-auto"
         style="display: none;"
         x-cloak
         @keydown.window.escape="showSwitcher = false">
         
         <!-- Close Button -->
         <button @click="showSwitcher = false" class="absolute top-6 right-6 text-white/70 hover:text-white bg-white/10 hover:bg-white/20 p-2.5 rounded-full border-0 outline-none transition">
             <i class="fa-solid fa-xmark text-lg"></i>
         </button>

         <!-- Odoo Style search box -->
         <div class="w-full max-w-md mt-16 mb-12 relative" x-data="{ searchQuery: '' }">
             <input type="text" 
                    placeholder="Cari Aplikasi..." 
                    x-model="searchQuery"
                    @input="$dispatch('filter-apps', searchQuery)"
                    class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-2xl text-white text-base placeholder-white/40 focus:bg-white/15 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 transition">
             <i class="fa-solid fa-magnifying-glass absolute right-4 top-4 text-white/40"></i>
         </div>

         <!-- App Switcher Grid -->
         <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6 max-w-4xl w-full text-center"
              x-data="{ 
                  search: '',
                  apps: [
                      @if(auth()->user()->isOwner() || auth()->user()->isManager())
                      { name: 'Dashboard Owner', route: '{{ route('owner.dashboard') }}', icon: 'fa-solid fa-chart-line', bg: 'bg-gradient-to-tr from-violet-500 to-indigo-600' },
                      @endif
                      @if(auth()->user()->isOwner() || auth()->user()->isManager())
                      { name: 'Dashboard Keuangan', route: '{{ route('dashboard') }}', icon: 'fa-solid fa-wallet', bg: 'bg-gradient-to-tr from-emerald-500 to-teal-600' },
                      @endif
                      { name: 'Master Material', route: '{{ route('materials.index') }}', icon: 'fa-solid fa-boxes-stacked', bg: 'bg-gradient-to-tr from-amber-500 to-orange-600' },
                      { name: 'Stok & Opname', route: '{{ route('stock.index') }}', icon: 'fa-solid fa-cubes', bg: 'bg-gradient-to-tr from-yellow-400 to-amber-500' },
                      { name: 'Pengajuan PO (RFQ)', route: '{{ route('purchasing.index') }}', icon: 'fa-solid fa-file-invoice', bg: 'bg-gradient-to-tr from-cyan-500 to-blue-600' },
                      { name: 'Data Supplier', route: '{{ route('suppliers.index') }}', icon: 'fa-solid fa-building', bg: 'bg-gradient-to-tr from-purple-500 to-fuchsia-600' },
                      { name: 'POS Kasir Checkout', route: '{{ route('pos.index') }}', icon: 'fa-solid fa-cash-register', bg: 'bg-gradient-to-tr from-rose-500 to-pink-600' },
                      { name: 'Riwayat Penjualan', route: '{{ route('sales.index') }}', icon: 'fa-solid fa-receipt', bg: 'bg-gradient-to-tr from-blue-500 to-sky-600' },
                      @if(auth()->user()->isOwner() || auth()->user()->isManager())
                      { name: 'Manajemen User', route: '{{ route('users.index') }}', icon: 'fa-solid fa-users', bg: 'bg-gradient-to-tr from-slate-500 to-slate-700' },
                      @endif
                      { name: 'Profil & Signature', route: '{{ route('profile.index') }}', icon: 'fa-solid fa-user-gear', bg: 'bg-gradient-to-tr from-gray-500 to-gray-700' }
                  ]
              }"
              @filter-apps.window="search = $event.detail.toLowerCase()">
              
              <template x-for="app in apps">
                  <div x-show="app.name.toLowerCase().includes(search)" 
                       class="flex flex-col items-center">
                      <a :href="app.route" 
                         class="w-20 h-20 rounded-2xl flex items-center justify-center shadow-lg transform hover:scale-105 transition-all duration-200 text-decoration-none mb-2"
                         :class="app.bg">
                          <i class="text-3xl text-white" :class="app.icon"></i>
                      </a>
                      <span class="text-white text-xs font-semibold tracking-wide" x-text="app.name"></span>
                  </div>
              </template>
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
        // Guided Tour Engine
        function startGuidedTour() {
            if (typeof driver === 'undefined') return;
            const driverObj = driver.js.driver({
                showProgress: true,
                steps: [
                    { element: '#tour-button', popover: { title: 'Navigasi Odoo Enterprise', description: 'Klik tombol navigasi ini kapan saja untuk melihat instruksi penggunaan sistem.', side: "bottom" } },
                    { element: '.fa-table-cells', popover: { title: 'App Switcher / Home Screen', description: 'Klik ikon ini untuk membuka halaman menu Switcher full-screen dan berpindah modul secara instan.', side: "right" } }
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

        // Global Live Search Engine
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
                confirmButtonColor: '#714B67',
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

        // Auto restore preferred view mode & initialize AutoNumeric
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-view-wrapper]').forEach(wrapper => {
                const containerId = wrapper.id;
                if (!containerId) return;
                const savedView = localStorage.getItem('preferred_view_' + containerId);
                if (savedView) {
                    toggleViewMode(savedView, containerId);
                }
            });

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
