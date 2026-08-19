<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') | SnapPrint ERP AdminLTE 4</title>

    <!-- Google Font: Plus Jakarta Sans & Source Sans 3 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Overlayscrollbars -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- AdminLTE 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
    <!-- ApexCharts CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css">
    <!-- Driver.js for Guided Tour -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css"/>

    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important; }
        .app-sidebar { background-color: #0f172a !important; }
        .sidebar-brand { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .brand-text { font-weight: 700; color: #818cf8; letter-spacing: 0.5px; }
        .nav-link.active { background-color: #4f46e5 !important; color: #ffffff !important; font-weight: 600; }
        .small-box { border-radius: 1rem; overflow: hidden; }
        .card { border-radius: 1rem; border: 1px solid rgba(0,0,0,0.05); }

        /* SVG & Tailwind Layout Compatibility Fixes */
        svg { display: inline-block; vertical-align: middle; max-width: 100%; }
        svg.w-4, svg.h-4 { width: 1rem !important; height: 1rem !important; min-width: 1rem; min-height: 1rem; }
        svg.w-5, svg.h-5 { width: 1.25rem !important; height: 1.25rem !important; min-width: 1.25rem; min-height: 1.25rem; }
        svg.w-6, svg.h-6 { width: 1.5rem !important; height: 1.5rem !important; min-width: 1.5rem; min-height: 1.5rem; }
        svg.w-8, svg.h-8 { width: 2rem !important; height: 2rem !important; min-width: 2rem; min-height: 2rem; }
        svg.w-12, svg.h-12 { width: 3rem !important; height: 3rem !important; min-width: 3rem; min-height: 3rem; }
        svg.w-16, svg.h-16 { width: 4rem !important; height: 4rem !important; }
        svg.w-24, svg.h-24 { width: 6rem !important; height: 6rem !important; }
        svg:not([width]):not([class*="w-"]) { max-width: 1.5rem; max-height: 1.5rem; }

        /* Tailwind grid & flex bridge helper classes */
        .grid { display: grid; }
        .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        @media (min-width: 768px) {
            .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .md\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .md\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        .gap-6 { gap: 1.5rem; }
        .gap-4 { gap: 1rem; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .space-y-4 > * + * { margin-top: 1rem; }
    </style>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        @auth
        <!-- Header Navbar (App Header) -->
        <nav class="app-header navbar navbar-expand bg-body shadow-sm">
            <div class="container-fluid">
                <!-- Start Navbar Links -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list fs-4"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <span class="nav-link text-muted fw-semibold">
                            Cabang: <span class="text-primary">{{ auth()->user()->branch->nama_cabang ?? 'Semua Cabang (Global)' }}</span>
                        </span>
                    </li>
                </ul>

                <!-- End Navbar Links -->
                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- Interactive Tour Button -->
                    <li class="nav-item me-2">
                        <button type="button" onclick="startAppTour()" id="tour-button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                            <i class="bi bi-question-circle-fill me-1"></i> Panduan Tutorial
                        </button>
                    </li>

                    <!-- User Menu Dropdown -->
                    <li class="nav-item dropdown user-menu" id="tour-profile">
                        <a href="#" class="nav-link dropdown-toggle flex items-center" data-bs-toggle="dropdown">
                            <div class="rounded-circle bg-indigo-600 text-white fw-bold d-inline-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 13px;">
                                {{ strtoupper(substr(auth()->user()->username, 0, 2)) }}
                            </div>
                            <span class="d-none d-md-inline fw-semibold text-dark">{{ auth()->user()->username }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow border-0 rounded-4 mt-2">
                            <!-- User Header -->
                            <li class="user-header bg-primary text-white rounded-top-4 p-3 text-center">
                                <div class="rounded-circle bg-white text-primary fw-bold mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px; font-size: 18px;">
                                    {{ strtoupper(substr(auth()->user()->username, 0, 2)) }}
                                </div>
                                <p class="mb-0 fw-bold">{{ auth()->user()->username }}</p>
                                <small class="text-white-50 text-uppercase fw-semibold">{{ auth()->user()->role }} - {{ auth()->user()->branch->nama_cabang ?? 'Global' }}</small>
                            </li>
                            <!-- Menu Body / Footer -->
                            <li class="user-footer p-3 bg-light rounded-bottom-4 d-flex justify-between">
                                <a href="{{ route('profile.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                    <i class="bi bi-person-gear me-1"></i> Profil & Tanda Tangan
                                </a>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3">
                                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="app-sidebar shadow" data-bs-theme="dark">
            <!-- Sidebar Brand -->
            <div class="sidebar-brand p-3">
                <a href="{{ route('owner.dashboard') }}" class="brand-link text-decoration-none">
                    <span class="brand-text fs-5">SnapPrint <small class="badge bg-indigo-500 text-white">ERP 4</small></span>
                </a>
            </div>

            <!-- Sidebar Menu -->
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                        
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a href="{{ route('owner.dashboard') }}" class="nav-link {{ request()->routeIs('owner.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-speedometer2"></i>
                                <p>Dashboard Enterprise</p>
                            </a>
                        </li>

                        <!-- Purchasing Dropdown -->
                        @if(auth()->user()->isPurchasing() || auth()->user()->isOwner() || auth()->user()->isManager())
                        <li class="nav-item {{ request()->routeIs('purchasing.*') || request()->routeIs('suppliers.*') ? 'menu-open' : '' }}" id="tour-purchasing">
                            <a href="#" class="nav-link {{ request()->routeIs('purchasing.*') || request()->routeIs('suppliers.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-cart-check"></i>
                                <p>
                                    Purchasing
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('purchasing.index') }}" class="nav-link {{ request()->routeIs('purchasing.index') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-circle text-info"></i>
                                        <p>Purchasing & Master Data</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('purchasing.history') }}" class="nav-link {{ request()->routeIs('purchasing.history') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-circle text-warning"></i>
                                        <p>Riwayat PO Logs</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-circle text-success"></i>
                                        <p>Data Supplier</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif

                        <!-- Stock Dropdown -->
                        @if(auth()->user()->isManager() || auth()->user()->isOwner())
                        <li class="nav-item {{ request()->routeIs('stock.*') ? 'menu-open' : '' }}" id="tour-stock">
                            <a href="#" class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-boxes"></i>
                                <p>
                                    Stock & Gudang
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('stock.index') }}" class="nav-link {{ request()->routeIs('stock.index') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-circle text-primary"></i>
                                        <p>Data Stok & Opname</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('stock.inspection') }}" class="nav-link {{ request()->routeIs('stock.inspection') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-circle text-success"></i>
                                        <p>Pemeriksaan Barang</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('stock.rejected') }}" class="nav-link {{ request()->routeIs('stock.rejected') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-circle text-danger"></i>
                                        <p>Riwayat Retur & Reject</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif

                        <!-- Sales Dropdown -->
                        @if(auth()->user()->isCashier() || auth()->user()->isOwner() || auth()->user()->isManager())
                        <li class="nav-item {{ request()->routeIs('pos.*') || request()->routeIs('sales.*') ? 'menu-open' : '' }}" id="tour-sales">
                            <a href="#" class="nav-link {{ request()->routeIs('pos.*') || request()->routeIs('sales.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-bag"></i>
                                <p>
                                    Sales & POS
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.index') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-circle text-primary"></i>
                                        <p>POS Checkout</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.index') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-circle text-info"></i>
                                        <p>Riwayat Penjualan</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif

                        <!-- Finance Dropdown -->
                        @if(auth()->user()->isOwner() || auth()->user()->isManager())
                        <li class="nav-item {{ request()->routeIs('dashboard') || request()->routeIs('accounts.*') || request()->routeIs('kas-masuk.*') || request()->routeIs('kas-keluar.*') || request()->routeIs('reports.*') ? 'menu-open' : '' }}" id="tour-finance">
                            <a href="#" class="nav-link {{ request()->routeIs('dashboard') || request()->routeIs('accounts.*') || request()->routeIs('kas-masuk.*') || request()->routeIs('kas-keluar.*') || request()->routeIs('reports.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-cash-stack"></i>
                                <p>
                                    Finance & Akuntansi
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-circle text-info"></i>
                                        <p>Dashboard Keuangan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('accounts.index') }}" class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-circle text-warning"></i>
                                        <p>Master Akun</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('kas-masuk.index') }}" class="nav-link {{ request()->routeIs('kas-masuk.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-circle text-success"></i>
                                        <p>Kas Masuk</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('kas-keluar.index') }}" class="nav-link {{ request()->routeIs('kas-keluar.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-circle text-danger"></i>
                                        <p>Kas Keluar</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif

                        <!-- User Management (Owner & Manager) -->
                        @if(auth()->user()->isOwner() || auth()->user()->isManager())
                        <li class="nav-item">
                            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-people"></i>
                                <p>Manajemen User</p>
                            </a>
                        </li>
                        @endif

                        <!-- Profil & Tanda Tangan -->
                        <li class="nav-item">
                            <a href="{{ route('profile.index') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-pencil-square"></i>
                                <p>Profil & Signature</p>
                            </a>
                        </li>

                    </ul>
                </nav>
            </div>
        </aside>
        @endauth

        <!-- Main Content (App Main) -->
        <main class="app-main">
            <!-- App Content Header (Page Title & Breadcrumb) -->
            <div class="app-content-header py-3 bg-white border-bottom">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h3 class="mb-0 fw-bold text-dark">@yield('page-title', 'Dashboard')</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}" class="text-decoration-none">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">@yield('title', 'ERP System')</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- App Content Body -->
            <div class="app-content py-4">
                <div class="container-fluid">
                    <!-- Session Alerts -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2 fs-5"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </main>

        <!-- App Footer -->
        <footer class="app-footer text-center py-3 bg-white border-top">
            <div class="float-end d-none d-sm-inline">AdminLTE 4 Enterprise</div>
            <strong>Copyright &copy; {{ date('Y') }} SnapPrint ERP.</strong> All rights reserved.
        </footer>
    </div>

    <!-- JS Dependencies -->
    <!-- Popper & Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Overlayscrollbars -->
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"></script>
    <!-- AdminLTE 4 JS -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/js/adminlte.min.js"></script>
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"></script>
    <!-- Driver.js for Guided Tour -->
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>

    <!-- Interactive Guided Tour Script -->
    <script>
        function startAppTour() {
            if (typeof window.driver === 'undefined') {
                alert('Modul Tutorial sedang dimuat...');
                return;
            }

            const driverObj = window.driver.js.driver({
                showProgress: true,
                animate: true,
                nextBtnText: 'Lanjut ➔',
                prevBtnText: '⬅️ Kembali',
                doneBtnText: 'Selesai 🎉',
                steps: [
                    {
                        element: '#tour-button',
                        popover: {
                            title: '👋 AdminLTE 4 ERP Tutorial',
                            description: 'Klik tombol ini kapan saja untuk memulai ulang panduan navigasi interaktif aplikasi!',
                            side: "bottom", align: 'start'
                        }
                    },
                    {
                        element: '#tour-purchasing',
                        popover: {
                            title: '🛒 Modul Purchasing',
                            description: 'Tempat Staf Purchasing membuat Pengajuan Purchase Order (PO) ke Supplier. Status awal: <b>⏳ Menunggu ACC Manager</b>.',
                            side: "right", align: 'start'
                        }
                    },
                    {
                        element: '#tour-stock',
                        popover: {
                            title: '📦 Modul Stock & Gudang',
                            description: 'Tempat Manajer Toko menyetujui PO (ACC) dan melakukan <b>Pemeriksaan Fisik Barang Masuk</b> sebelum stok bertambah.',
                            side: "right", align: 'start'
                        }
                    },
                    {
                        element: '#tour-sales',
                        popover: {
                            title: '💰 Modul Sales & POS',
                            description: 'Tempat Kasir menginput pemesanan eceran/grosir pelanggan dan mencetak nota transaksi.',
                            side: "right", align: 'start'
                        }
                    },
                    {
                        element: '#tour-finance',
                        popover: {
                            title: '📊 Modul Finance & Keuangan',
                            description: 'Tempat melihat Dashboard Keuangan, Jurnal Kas Masuk/Keluar Voucher, dan Laporan Laba Rugi.',
                            side: "right", align: 'start'
                        }
                    },
                    {
                        element: '#tour-profile',
                        popover: {
                            title: '✍️ Profil & Tanda Tangan Digital',
                            description: 'Klik di sini untuk mengunggah atau menggambar <b>Tanda Tangan Digital Resmi</b> yang akan terstempel otomatis pada nota cetak PO.',
                            side: "bottom", align: 'end'
                        }
                    }
                ]
            });

            driverObj.drive();
        }
    </script>
</body>
</html>
