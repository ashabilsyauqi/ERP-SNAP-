<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') | SnapPrint ERP</title>

    <!-- Google Font: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 & Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- ApexCharts CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css">
    <!-- Driver.js for Guided Tour -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css"/>

    <!-- Bladewind UI CSS -->
    <link href="{{ asset('vendor/bladewind/css/animate.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('vendor/bladewind/css/bladewind-ui.min.css') }}" rel="stylesheet" />

    <!-- Tailwind CSS CDN Engine with SnapPrint Blue Palette -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            corePlugins: {
                preflight: false,
            },
            theme: {
                extend: {
                    colors: {
                        snapprint: {
                            navy: '#0F172A',
                            royal: '#1E3A8A',
                            blue: '#2563EB',
                            blueDark: '#1D4ED8',
                            sky: '#0284C7',
                            bg: '#F8FAFC',
                            surface: '#FFFFFF',
                            border: '#E2E8F0',
                            text: '#1E293B',
                            muted: '#64748B',
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
            --o-primary-color: #1E3A8A;
            --o-primary-dark: #0F172A;
            --o-accent-color: #2563EB;
            --o-accent-dark: #1D4ED8;
            --o-bg-color: #F8FAFC;
            --o-border-color: #E2E8F0;
            --o-sheet-border: #CBD5E1;
        }
        body { 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important; 
            background-color: var(--o-bg-color);
            color: #1e293b;
            font-size: 13px;
        }
        svg {
            max-width: 100%;
            height: auto;
            display: inline-block;
            vertical-align: middle;
        }
        
        /* SnapPrint Brand Top Main Navbar */
        .o_main_navbar {
            background: linear-gradient(90deg, #0f172a 0%, #1e3a8a 40%, #1e40af 100%) !important;
            height: 46px !important;
            min-height: 46px !important;
            max-height: 46px !important;
            z-index: 1040;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            user-select: none;
            padding: 0 12px;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .o_main_navbar * {
            box-sizing: border-box;
        }
        .o_main_navbar .o_nav_link, 
        .o_main_navbar .o_nav_dropdown_toggle {
            color: rgba(255, 255, 255, 0.92) !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            padding: 0 12px !important;
            height: 46px !important;
            line-height: 46px !important;
            display: inline-flex !important;
            align-items: center !important;
            text-decoration: none !important;
            background: transparent;
            border: none !important;
            outline: none !important;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
            white-space: nowrap;
        }
        .o_main_navbar .o_nav_link:hover,
        .o_main_navbar .o_nav_dropdown_toggle:hover,
        .o_main_navbar .o_nav_link.active {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.15) !important;
        }
        .o_main_navbar .dropdown {
            height: 46px;
            display: inline-flex;
            align-items: center;
            position: relative;
        }
        .o_main_navbar .dropdown-menu {
            font-size: 13px;
            border-radius: 6px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.18);
            border: 1px solid #d8dadd;
            margin-top: 0px !important;
            top: 100% !important;
            background-color: #ffffff;
            z-index: 1050;
        }
        .o_main_navbar .dropdown-item {
            padding: 7px 16px;
            font-weight: 500;
            color: #1e293b;
        }
        .o_main_navbar .dropdown-item:hover {
            background-color: #eff6ff;
            color: #1e3a8a;
        }
        .o_main_navbar .dropdown-item.active {
            background-color: #eff6ff;
            color: #1e3a8a;
            font-weight: 700;
        }
        
        /* SnapPrint Two-Tier Control Panel */
        .o_control_panel {
            background-color: #ffffff;
            border-bottom: 1px solid var(--o-border-color);
            padding: 8px 16px;
            z-index: 1030;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        .o_breadcrumb {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
        .o_breadcrumb .breadcrumb-item + .breadcrumb-item::before {
            content: "/";
            color: #94a3b8;
            padding: 0 6px;
        }
        
        /* Search View Bar */
        .o_searchview {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 3px 8px;
            display: flex;
            align-items: center;
            min-width: 280px;
            max-width: 480px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .o_searchview:focus-within {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }
        .o_searchview_input {
            border: none;
            outline: none;
            font-size: 12px;
            width: 100%;
            background: transparent;
        }
        
        /* Action Buttons */
        .btn-odoo-primary {
            background-color: #2563eb;
            color: #ffffff;
            border: 1px solid #1d4ed8;
            font-weight: 600;
            font-size: 12px;
            border-radius: 6px;
            padding: 5px 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-odoo-primary:hover {
            background-color: #1d4ed8;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        }
        .btn-odoo-secondary {
            background-color: #ffffff;
            color: #1e293b;
            border: 1px solid #cbd5e1;
            font-weight: 600;
            font-size: 12px;
            border-radius: 6px;
            padding: 5px 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-odoo-secondary:hover {
            background-color: #f8fafc;
            color: #0f172a;
            border-color: #94a3b8;
        }
        
        /* Stat Buttons */
        .o_stat_button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background-color: #ffffff;
            border: 1px solid var(--o-border-color);
            border-radius: 8px;
            padding: 6px 14px;
            min-width: 130px;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .o_stat_button:hover {
            border-color: #2563eb;
            background-color: #f8fafc;
        }
        .o_stat_value {
            font-size: 14px;
            font-weight: 800;
            line-height: 1.1;
            color: #0f172a;
            font-family: monospace;
        }
        .o_stat_text {
            font-size: 10px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Form Sheets & Tables */
        .o_form_sheet {
            background-color: #ffffff;
            border: 1px solid var(--o-sheet-border);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            margin-bottom: 16px;
        }
        .o_form_sheet_bg {
            background-color: var(--o-bg-color);
            padding: 16px;
        }
        .o_list_table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .o_list_table th {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            background-color: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            padding: 8px 12px;
            user-select: none;
        }
        .o_list_table td {
            padding: 9px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .o_list_table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        /* Custom Sort Indicator */
        th.sortable { cursor: pointer; }
        .sort-icon { display: inline-block; margin-left: 4px; font-size: 0.7rem; }

        /* Professional Printable Media Styling */
        @media print {
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
                font-size: 11pt !important;
            }
            .o_main_navbar,
            .o_control_panel,
            .print\:hidden,
            .d-print-none,
            #app-switcher-matrix,
            .btn,
            .btn-odoo-primary,
            .btn-odoo-secondary {
                display: none !important;
            }
            .d-print-block {
                display: block !important;
            }
            .d-print-inline {
                display: inline !important;
            }
            main.o_form_sheet_bg {
                padding: 0 !important;
                background-color: #ffffff !important;
                overflow: visible !important;
            }
            .o_form_sheet {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            .table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 10pt !important;
            }
            .table th, .table td {
                border: 1px solid #cbd5e1 !important;
                padding: 6px 8px !important;
                color: #000000 !important;
            }
            .table thead th {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .table tfoot tr {
                background-color: #f8fafc !important;
                font-weight: bold !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .badge {
                border: 1px solid #94a3b8 !important;
                color: #000000 !important;
                background: transparent !important;
            }
            @page {
                size: A4 auto;
                margin: 1.5cm 1cm 1.5cm 1cm;
            }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen" x-data="{ showSwitcher: false }">

    @php
        $currentRoute = request()->route() ? request()->route()->getName() : '';
        $activeApp = 'Executive';
        $appIcon = 'fa-chart-pie';
        $menuGroups = [];

        // Dynamic Menu Construction based on Current App Context
        if (Str::startsWith($currentRoute, 'owner.dashboard') || Str::startsWith($currentRoute, 'branches')) {
            $activeApp = 'Executive';
            $appIcon = 'fa-chart-pie';
            $menuGroups = [
                [
                    'title' => 'Dashboard',
                    'type' => 'link',
                    'route' => 'owner.dashboard',
                    'role' => 'owner,manager'
                ],
                [
                    'title' => 'Perusahaan & Cabang',
                    'type' => 'dropdown',
                    'role' => 'owner,manager',
                    'items' => [
                        ['title' => 'Daftar Cabang Toko', 'route' => 'branches.index', 'role' => 'owner'],
                        ['title' => 'Manajemen Pengguna (Users)', 'route' => 'users.index', 'role' => 'owner,manager'],
                    ]
                ],
                [
                    'title' => 'Laporan Eksekutif',
                    'type' => 'dropdown',
                    'role' => 'owner,manager',
                    'items' => [
                        ['title' => 'Laporan Laba & Rugi Konsolidasi', 'route' => 'reports.profit-loss', 'role' => 'owner,manager'],
                        ['title' => 'Laporan Penjualan Semua Cabang', 'route' => 'reports.sales', 'role' => 'owner,manager'],
                        ['title' => 'Laporan Beban Operasional', 'route' => 'reports.expenses', 'role' => 'owner,manager'],
                    ]
                ]
            ];
        } elseif (Str::startsWith($currentRoute, 'dashboard') || Str::startsWith($currentRoute, 'accounts') || Str::startsWith($currentRoute, 'kas-masuk') || Str::startsWith($currentRoute, 'kas-keluar') || Str::startsWith($currentRoute, 'reports')) {
            $activeApp = 'Accounting';
            $appIcon = 'fa-wallet';
            $menuGroups = [
                [
                    'title' => 'Overview',
                    'type' => 'link',
                    'route' => 'dashboard',
                    'role' => 'owner,manager'
                ],
                [
                    'title' => 'Customers / Kas Masuk',
                    'type' => 'dropdown',
                    'role' => 'owner,manager',
                    'items' => [
                        ['title' => 'Piutang & Pesanan DP', 'route' => 'sales.receivables', 'role' => 'owner,manager'],
                        ['title' => 'Penerimaan Kas (Cash Receipts)', 'route' => 'kas-masuk.index', 'role' => 'owner,manager'],
                        ['title' => 'Laporan Kas Masuk', 'route' => 'reports.cash-in', 'role' => 'owner,manager'],
                        ['title' => 'Riwayat Transaksi Penjualan POS', 'route' => 'reports.sales', 'role' => 'owner,manager'],
                    ]
                ],
                [
                    'title' => 'Vendors / Kas Keluar',
                    'type' => 'dropdown',
                    'role' => 'owner,manager',
                    'items' => [
                        ['title' => 'Pengeluaran Kas (Disbursements)', 'route' => 'kas-keluar.index', 'role' => 'owner,manager'],
                        ['title' => 'Laporan Kas Keluar', 'route' => 'reports.cash-out', 'role' => 'owner,manager'],
                        ['title' => 'Laporan Beban & Biaya Operasional', 'route' => 'reports.expenses', 'role' => 'owner,manager'],
                    ]
                ],
                [
                    'title' => 'Accounting',
                    'type' => 'dropdown',
                    'role' => 'owner,manager',
                    'items' => [
                        ['title' => 'Bagan Akun (Chart of Accounts - COA)', 'route' => 'accounts.index', 'role' => 'owner,manager'],
                        ['title' => 'Mutasi Buku Besar (General Ledger)', 'route' => 'reports.cash-mutation', 'role' => 'owner,manager'],
                    ]
                ],
                [
                    'title' => 'Reporting',
                    'type' => 'dropdown',
                    'role' => 'owner,manager',
                    'items' => [
                        ['title' => 'Saldo Kas & Bank (Balance Sheet)', 'route' => 'reports.cash-balance', 'role' => 'owner,manager'],
                        ['title' => 'Mutasi Buku Besar (General Ledger)', 'route' => 'reports.cash-mutation', 'role' => 'owner,manager'],
                        ['title' => 'Laporan Laba & Rugi (Profit & Loss)', 'route' => 'reports.profit-loss', 'role' => 'owner,manager'],
                        ['title' => 'Laporan Penerimaan Kas', 'route' => 'reports.cash-in', 'role' => 'owner,manager'],
                        ['title' => 'Laporan Pengeluaran Kas', 'route' => 'reports.cash-out', 'role' => 'owner,manager'],
                    ]
                ]
            ];
        } elseif (Str::startsWith($currentRoute, 'materials') || Str::startsWith($currentRoute, 'stock')) {
            $activeApp = 'Inventory';
            $appIcon = 'fa-boxes-stacked';
            $menuGroups = [
                [
                    'title' => 'Overview',
                    'type' => 'link',
                    'route' => 'materials.index',
                    'role' => 'owner,manager'
                ],
                [
                    'title' => 'Operations',
                    'type' => 'dropdown',
                    'role' => 'manager,owner',
                    'items' => [
                        ['title' => 'Data Stok & Opname (Physical Inventory)', 'route' => 'stock.index', 'role' => 'manager,owner'],
                        ['title' => 'Pemeriksaan Barang Masuk (GRN Inspection)', 'route' => 'stock.inspection', 'role' => 'manager,owner'],
                        ['title' => 'Barang Reject & Retur Supplier', 'route' => 'stock.rejected', 'role' => 'manager,owner'],
                    ]
                ],
                [
                    'title' => 'Products',
                    'type' => 'dropdown',
                    'role' => 'owner,manager',
                    'items' => [
                        ['title' => 'Master Bahan Baku & Produk', 'route' => 'materials.index', 'role' => 'owner,manager'],
                    ]
                ],
                [
                    'title' => 'Reporting',
                    'type' => 'dropdown',
                    'role' => 'manager,owner',
                    'items' => [
                        ['title' => 'Laporan Stok & Pergerakan Barang', 'route' => 'stock.index', 'role' => 'manager,owner'],
                    ]
                ]
            ];
        } elseif (Str::startsWith($currentRoute, 'purchasing') || Str::startsWith($currentRoute, 'suppliers')) {
            $activeApp = 'Purchase';
            $appIcon = 'fa-cart-shopping';
            $menuGroups = [
                [
                    'title' => 'Orders',
                    'type' => 'dropdown',
                    'role' => 'purchasing,owner,manager',
                    'items' => [
                        ['title' => 'Purchase Plans (Rencana Pengadaan / RFQ Bundle)', 'route' => 'purchasing.plans.index', 'role' => 'purchasing,owner,manager'],
                        ['title' => 'Purchase Orders (Daftar PO & RFQ Satuan)', 'route' => 'purchasing.index', 'role' => 'purchasing,owner,manager'],
                        ['title' => 'Data Vendor / Supplier', 'route' => 'suppliers.index', 'role' => 'purchasing,owner,manager'],
                    ]
                ],
                [
                    'title' => 'Products',
                    'type' => 'dropdown',
                    'role' => 'purchasing,owner,manager',
                    'items' => [
                        ['title' => 'Katalog Bahan Baku & Produk', 'route' => 'materials.index', 'role' => 'owner,manager'],
                    ]
                ],
                [
                    'title' => 'Reporting',
                    'type' => 'dropdown',
                    'role' => 'purchasing,owner,manager',
                    'items' => [
                        ['title' => 'Riwayat & Log Belanja Pengadaan', 'route' => 'purchasing.history', 'role' => 'purchasing,owner,manager'],
                    ]
                ]
            ];
        } elseif (Str::startsWith($currentRoute, 'pos') || Str::startsWith($currentRoute, 'sales')) {
            $activeApp = 'Point of Sale';
            $appIcon = 'fa-cash-register';
            $menuGroups = [
                [
                    'title' => 'Orders',
                    'type' => 'dropdown',
                    'role' => 'cashier,owner,manager',
                    'items' => [
                        ['title' => 'Terminal Kasir Checkout (POS)', 'route' => 'pos.index', 'role' => 'cashier,owner,manager'],
                        ['title' => 'Piutang & Monitoring Pesanan DP', 'route' => 'sales.receivables', 'role' => 'cashier,owner,manager'],
                        ['title' => 'Riwayat Transaksi Penjualan', 'route' => 'sales.index', 'role' => 'cashier,owner,manager'],
                    ]
                ],
                [
                    'title' => 'Products',
                    'type' => 'dropdown',
                    'role' => 'cashier,owner,manager',
                    'items' => [
                        ['title' => 'Katalog Bahan Cetak Kasir', 'route' => 'materials.index', 'role' => 'owner,manager'],
                    ]
                ],
                [
                    'title' => 'Reporting',
                    'type' => 'dropdown',
                    'role' => 'cashier,owner,manager',
                    'items' => [
                        ['title' => 'Laporan Penjualan POS', 'route' => 'reports.sales', 'role' => 'owner,manager'],
                    ]
                ]
            ];
        } else {
            $activeApp = 'Settings';
            $appIcon = 'fa-gear';
            $menuGroups = [
                [
                    'title' => 'Profile',
                    'type' => 'link',
                    'route' => 'profile.index',
                    'role' => 'all'
                ],
                [
                    'title' => 'Users & Companies',
                    'type' => 'dropdown',
                    'role' => 'owner,manager',
                    'items' => [
                        ['title' => 'Manajemen Pengguna (Users)', 'route' => 'users.index', 'role' => 'owner,manager'],
                        ['title' => 'Daftar Cabang Toko (Branches)', 'route' => 'branches.index', 'role' => 'owner'],
                    ]
                ]
            ];
        }
    @endphp

    <div class="flex flex-col h-screen overflow-hidden">
        
        <!-- SnapPrint Brand Top Main Navbar -->
        <nav class="o_main_navbar flex-shrink-0">
            <div class="d-flex align-items-center h-100 flex-nowrap" style="overflow: visible;">
                <!-- App Switcher Matrix Button -->
                <button @click="showSwitcher = !showSwitcher" class="btn text-white px-2.5 py-0 d-flex align-items-center hover:bg-white/10 border-0 h-100 cursor-pointer" title="App Switcher (Home)">
                    <i class="fa-solid fa-table-cells fs-5"></i>
                </button>

                <!-- SnapPrint Brand Logo Emblem in Navbar -->
                <div class="d-flex align-items-center gap-2 border-end pe-3 ps-1 me-1 border-white/20 h-75">
                    <img src="{{ asset('images/logosnaprint.jpeg') }}" alt="SnapPrint" class="rounded-circle" style="width: 24px; height: 24px; object-fit: cover;">
                    <span class="fw-bold tracking-wide text-white text-xs d-none d-sm-inline">SnapPrint</span>
                    <span class="text-white/40 d-none d-sm-inline">|</span>
                    <span class="fw-bold text-white text-xs d-flex align-items-center gap-1.5">
                        <i class="fa-solid {{ $appIcon }} text-amber-300 text-xs"></i>
                        <span>{{ $activeApp }}</span>
                    </span>
                </div>

                <!-- Multi-level Dropdown Menus -->
                <div class="d-none d-md-flex align-items-center h-100 flex-nowrap" style="overflow: visible;">
                    @foreach($menuGroups as $group)
                        @php
                            $groupAllowed = false;
                            if ($group['role'] === 'all') {
                                $groupAllowed = true;
                            } else {
                                $roles = explode(',', $group['role']);
                                if (in_array(auth()->user()->role, $roles)) {
                                    $groupAllowed = true;
                                }
                            }
                        @endphp

                        @if($groupAllowed)
                            @if($group['type'] === 'link')
                                <a href="{{ Route::has($group['route']) ? route($group['route']) : '#' }}" class="o_nav_link {{ (Route::has($group['route']) && request()->routeIs($group['route'])) ? 'active' : '' }}">
                                    {{ $group['title'] }}
                                </a>
                            @elseif($group['type'] === 'dropdown')
                                <div class="dropdown">
                                    <button class="o_nav_dropdown_toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ $group['title'] }}
                                    </button>
                                    <ul class="dropdown-menu shadow">
                                        @foreach($group['items'] as $item)
                                            @php
                                                $itemAllowed = false;
                                                if ($item['role'] === 'all') {
                                                    $itemAllowed = true;
                                                } else {
                                                    $itemRoles = explode(',', $item['role']);
                                                    if (in_array(auth()->user()->role, $itemRoles)) {
                                                        $itemAllowed = true;
                                                    }
                                                }
                                            @endphp
                                            @if($itemAllowed)
                                                <li>
                                                    <a class="dropdown-item {{ (Route::has($item['route']) && request()->routeIs($item['route'])) ? 'active' : '' }}" href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}">
                                                        {{ $item['title'] }}
                                                    </a>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Right side System Tray -->
            <div class="d-flex align-items-center gap-2 flex-nowrap h-100">
                <!-- Guided Tour Button -->
                <button type="button" onclick="startGuidedTour()" id="tour-button" class="btn btn-sm text-white text-decoration-none hover:bg-white/10 px-2 py-1 rounded d-inline-flex align-items-center gap-1.5 border-0 cursor-pointer">
                    <i class="fa-solid fa-compass"></i>
                    <span class="d-none d-lg-inline text-xs">Petunjuk</span>
                </button>

                <!-- Company / Branch Selector Menu -->
                @if(auth()->user()->isOwner())
                <div class="dropdown">
                    <button class="btn btn-link p-0 text-decoration-none text-white d-flex align-items-center gap-1.5 dropdown-toggle border-0 h-100 px-2 hover:bg-white/10 cursor-pointer" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-building text-amber-300 text-xs"></i>
                        <span class="text-xs font-semibold">
                            @php
                                $selectedBranchId = request('branch_id', 'all');
                                $selectedBranchName = 'Semua Cabang (Global)';
                                if ($selectedBranchId !== 'all') {
                                    $bModel = \App\Models\Branch::find($selectedBranchId);
                                    if ($bModel) $selectedBranchName = $bModel->nama_cabang;
                                }
                            @endphp
                            {{ $selectedBranchName }}
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-1 p-2" style="min-width: 220px;">
                        <li class="px-2 py-1 text-[10px] uppercase font-bold text-slate-400">Pilih Cabang (Company)</li>
                        <li>
                            <a class="dropdown-item rounded-2 text-xs py-1.5 {{ $selectedBranchId === 'all' ? 'active bg-blue-600 text-white font-bold' : '' }}" href="?branch_id=all">
                                <i class="fa-solid fa-globe me-2"></i> Semua Cabang (Konsolidasi)
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        @php
                            $allBranches = \App\Models\Branch::all();
                        @endphp
                        @foreach($allBranches as $br)
                            <li>
                                <a class="dropdown-item rounded-2 text-xs py-1.5 {{ $selectedBranchId == $br->id ? 'active bg-blue-600 text-white font-bold' : '' }}" href="?branch_id={{ $br->id }}">
                                    <i class="fa-solid fa-shop me-2"></i> {{ $br->nama_cabang }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @else
                <div class="d-flex align-items-center gap-1.5 text-xs text-white/90 px-2">
                    <i class="fa-solid fa-building text-amber-300 text-xs"></i>
                    <span class="text-xs font-semibold">{{ auth()->user()->branch->nama_cabang ?? 'Pusat' }}</span>
                </div>
                @endif

                <!-- Bladewind Bell Notifications -->
                <div class="text-white relative mx-1">
                    <x-bladewind::bell has_unread="true" />
                </div>

                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-link p-0 text-decoration-none text-white d-flex align-items-center gap-2 dropdown-toggle border-0 cursor-pointer" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        @if(auth()->user()->avatar_path)
                            <img src="{{ asset('storage/' . auth()->user()->avatar_path) }}" alt="Avatar" class="rounded-circle object-cover border border-white/40" style="width: 28px; height: 28px;">
                        @else
                            <x-bladewind::avatar label="{{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 2)) }}" size="small" />
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-2" style="min-width: 200px;">
                        <li class="px-3 py-2 border-bottom mb-2">
                            <p class="text-xs font-bold text-slate-800 mb-0 truncate">{{ auth()->user()->full_name ?: auth()->user()->username }}</p>
                            <p class="text-[10px] text-slate-500 uppercase font-semibold mb-0">{{ auth()->user()->role }} &bull; {{ auth()->user()->branch->nama_cabang ?? 'Pusat' }}</p>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-3 text-xs fw-semibold py-2" href="{{ route('profile.index') }}">
                                <i class="fa-solid fa-user-gear me-2 text-blue-600"></i> Profil & Signature
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item rounded-3 text-xs fw-semibold py-2 text-danger cursor-pointer">
                                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Keluar (Logout)
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- SnapPrint Two-Tier Control Panel -->
        <header class="o_control_panel flex-shrink-0">
            @php
                $isDashboardOrReport = request()->routeIs('owner.dashboard', 'dashboard', 'reports.*', 'profile.*', 'pos', 'pos.*');
            @endphp
            <!-- Top Row: Breadcrumbs & Search View -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <!-- Breadcrumbs -->
                <div class="d-flex align-items-center gap-2">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 align-items-center o_breadcrumb">
                            <li class="breadcrumb-item text-slate-500 font-medium">{{ $activeApp }}</li>
                            <li class="breadcrumb-item active text-slate-900 font-bold" aria-current="page">@yield('page-title', 'Overview')</li>
                        </ol>
                    </nav>
                </div>

                <!-- Live Search View Box (Only shown on data list views, hidden on dashboards and POS) -->
                @if(!$isDashboardOrReport)
                <div class="o_searchview">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 me-2 text-xs"></i>
                    <input type="text" class="o_searchview_input table-search-input" placeholder="Search... (Filter data tabel)" aria-label="Search records">
                </div>
                @endif
            </div>

            <!-- Bottom Row: Primary Actions & View Switchers -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-1 border-top border-slate-100">
                <!-- Action Buttons (Left) -->
                <div class="d-flex align-items-center gap-2 flex-wrap" id="control-panel-actions">
                    @yield('action-buttons')
                    <button type="button" onclick="exportTableToExcel('main-table', 'SnapPrint_Export')" class="btn-odoo-secondary" title="Export Table to Excel (SheetJS)">
                        <i class="fa-solid fa-file-excel text-emerald-600"></i>
                        <span>Export</span>
                    </button>
                </div>

                <!-- Pager & View Mode Switcher (Right) -->
                <div class="d-flex align-items-center gap-3">
                    <span class="text-xs text-slate-500 font-mono d-none d-sm-inline">
                        <i class="fa-solid fa-shield-halved text-[10px] me-1 text-blue-600"></i> SnapPrint ERP
                    </span>

                    @if(!$isDashboardOrReport)
                    <!-- View Switcher Buttons -->
                    <div class="btn-group btn-group-sm border rounded" role="group" aria-label="View Switchers">
                        <button type="button" class="btn btn-sm btn-light btn-view-list active px-2.5 py-1 text-slate-700 font-semibold" onclick="toggleViewMode('list', 'main-view-wrapper')" title="List View (Tabel)">
                            <i class="fa-solid fa-list text-xs"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light btn-view-grid px-2.5 py-1 text-slate-500 font-semibold" onclick="toggleViewMode('grid', 'main-view-wrapper')" title="Kanban View (Kartu)">
                            <i class="fa-solid fa-grip text-xs"></i>
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="o_form_sheet_bg flex-grow-1 overflow-y-auto">
            <!-- Flash Notification Alerts -->
            @if(session('success'))
                <div class="o_form_sheet mb-3 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                    <x-bladewind::alert type="success" show_close_icon="true">
                        {{ session('success') }}
                    </x-bladewind::alert>
                </div>
            @endif

            @if(session('error'))
                <div class="o_form_sheet mb-3 p-3 bg-rose-50 border border-rose-200 rounded-lg">
                    <x-bladewind::alert type="error" show_close_icon="true">
                        {{ session('error') }}
                    </x-bladewind::alert>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- SnapPrint Solid Blue Gradient App Switcher Modal (Fullscreen overlay above all navbars) -->
    <div x-show="showSwitcher" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 p-6 flex flex-col items-center justify-start overflow-y-auto"
         style="position: fixed; inset: 0; z-index: 999999 !important; width: 100vw; height: 100vh; display: none; background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #1d4ed8 100%) !important;"
         x-cloak
         @keydown.window.escape="showSwitcher = false">
         
         <!-- Close Button -->
         <button @click="showSwitcher = false" class="absolute top-6 right-6 text-white/80 hover:text-white bg-white/10 hover:bg-white/20 p-2.5 rounded-full border-0 outline-none transition cursor-pointer" title="Tutup Menu">
             <i class="fa-solid fa-xmark text-lg"></i>
         </button>

         <!-- SnapPrint Brand Logo & Title in Switcher -->
         <div class="text-center mt-6 mb-2">
             <img src="{{ asset('images/logosnaprint.jpeg') }}" alt="SnapPrint Logo" class="rounded-circle shadow-2xl mb-3 border-2 border-white/50" style="width: 72px; height: 72px; object-fit: cover;">
             <h2 class="text-3xl font-extrabold text-white tracking-tight drop-shadow">SnapPrint ERP</h2>
             <p class="text-blue-200 text-xs mt-0.5">Enterprise Management System & Modules</p>
         </div>

         <!-- Search Apps Input -->
         <div class="w-full max-w-md my-6 relative" x-data="{ searchQuery: '' }">
             <input type="text" 
                    placeholder="Search Apps... (Ketik nama modul)" 
                    x-model="searchQuery"
                    @input="$dispatch('filter-apps', searchQuery)"
                    class="w-full px-4 py-3 bg-white/15 border border-white/25 rounded-2xl text-white text-base placeholder-white/50 focus:bg-white/20 focus:outline-none focus:ring-2 focus:ring-blue-400 transition shadow-xl">
             <i class="fa-solid fa-magnifying-glass absolute right-4 top-4 text-white/50"></i>
         </div>

         <!-- App Switcher Matrix Grid -->
         <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-8 max-w-4xl w-full text-center pb-12"
              x-data="{ 
                  search: '',
                  apps: [
                      @if(auth()->user()->isOwner() || auth()->user()->isManager())
                      { name: 'Executive / Owner', route: '{{ route('owner.dashboard') }}', icon: 'fa-solid fa-chart-pie', bg: 'bg-gradient-to-tr from-blue-700 to-indigo-900' },
                      @endif
                      @if(auth()->user()->isOwner() || auth()->user()->isManager())
                      { name: 'Accounting & Finance', route: '{{ route('dashboard') }}', icon: 'fa-solid fa-wallet', bg: 'bg-gradient-to-tr from-emerald-600 to-teal-800' },
                      @endif
                      { name: 'Inventory & Stock', route: '{{ route('stock.index') }}', icon: 'fa-solid fa-boxes-stacked', bg: 'bg-gradient-to-tr from-amber-500 to-orange-600' },
                      { name: 'Master Material', route: '{{ route('materials.index') }}', icon: 'fa-solid fa-cubes', bg: 'bg-gradient-to-tr from-cyan-600 to-blue-700' },
                      { name: 'Purchase / RFQ', route: '{{ route('purchasing.index') }}', icon: 'fa-solid fa-cart-shopping', bg: 'bg-gradient-to-tr from-blue-600 to-sky-700' },
                      { name: 'Vendors / Supplier', route: '{{ route('suppliers.index') }}', icon: 'fa-solid fa-building', bg: 'bg-gradient-to-tr from-indigo-600 to-purple-800' },
                      { name: 'Point of Sale (POS)', route: '{{ route('pos.index') }}', icon: 'fa-solid fa-cash-register', bg: 'bg-gradient-to-tr from-rose-500 to-pink-700' },
                      { name: 'Orders / Penjualan', route: '{{ route('sales.index') }}', icon: 'fa-solid fa-receipt', bg: 'bg-gradient-to-tr from-sky-500 to-blue-700' },
                      @if(auth()->user()->isOwner() || auth()->user()->isManager())
                      { name: 'Users & Access', route: '{{ route('users.index') }}', icon: 'fa-solid fa-users', bg: 'bg-gradient-to-tr from-slate-700 to-slate-900' },
                      @endif
                      { name: 'Settings & Profile', route: '{{ route('profile.index') }}', icon: 'fa-solid fa-gear', bg: 'bg-gradient-to-tr from-slate-600 to-gray-800' }
                  ]
              }"
              @filter-apps.window="search = $event.detail.toLowerCase()">
              
              <template x-for="app in apps">
                  <div x-show="app.name.toLowerCase().includes(search)" 
                       class="flex flex-col items-center">
                      <a :href="app.route" 
                         class="w-20 h-20 rounded-2xl flex items-center justify-center shadow-xl transform hover:scale-110 hover:shadow-2xl transition-all duration-200 text-decoration-none mb-2.5 border border-white/30"
                         :class="app.bg">
                          <i class="text-3xl text-white" :class="app.icon"></i>
                      </a>
                      <span class="text-white text-xs font-semibold tracking-wide drop-shadow" x-text="app.name"></span>
                  </div>
              </template>
         </div>
    </div>

    <!-- SnapPrint Universal Interactive Invoice Modal -->
    <div x-data="{ 
        open: false, 
        inv: { invoice_number: '', created_at: '', cashier_name: '', branch_name: '', payment_method: '', payment_status: 'PAID', total_price: 0, items: [] } 
    }" 
    @open-invoice-modal.window="inv = $event.detail; open = true;"
    x-show="open" 
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4" 
    style="position: fixed; inset: 0; z-index: 999999 !important; display: none;" 
    x-cloak 
    @keydown.window.escape="open = false">
        <div class="bg-white rounded-3 shadow-2xl border w-full max-w-2xl overflow-hidden my-auto" @click.away="open = false">
            <!-- Modal Header -->
            <div class="bg-slate-900 text-white px-4 py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ asset('images/logosnaprint.jpeg') }}" alt="SnapPrint Logo" class="rounded-circle" style="width: 28px; height: 28px; object-fit: cover;">
                    <div>
                        <h6 class="fw-bold mb-0 text-white font-mono" x-text="'INVOICE: ' + (inv.invoice_number || 'TRX-000')"></h6>
                        <span class="text-[11px] text-slate-300">SnapPrint Digital Printing ERP Official Invoice</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" @click="printSnapPrintInvoice(inv)" class="btn btn-sm btn-primary py-1 px-2.5 text-xs font-semibold">
                        <i class="fa-solid fa-print me-1"></i> Cetak Invoice / SPK
                    </button>
                    <button type="button" class="btn-close btn-close-white text-xs" @click="open = false"></button>
                </div>
            </div>

            <!-- Modal Invoice Body -->
            <div class="p-4 bg-white">
                <!-- Invoice Header with Company Logo -->
                <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{ asset('images/logosnaprint.jpeg') }}" alt="SnapPrint Logo" class="rounded-circle border" style="width: 52px; height: 52px; object-fit: cover;">
                        <div>
                            <h4 class="fw-bold text-blue-900 mb-0 tracking-tight">SnapPrint</h4>
                            <p class="text-xs text-slate-500 mb-0">Digital Printing & Advertising Solutions</p>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                Cabang: <strong class="text-slate-800" x-text="inv.branch_name || 'Pusat'"></strong>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <!-- Payment Status Stamp Badge -->
                        <div class="mb-2">
                            <template x-if="inv.payment_status === 'PAID' || (!inv.payment_status && (!inv.remaining_amount || inv.remaining_amount <= 0))">
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 text-emerald-800 border border-emerald-500 rounded-md text-xs font-extrabold uppercase tracking-wider">
                                    <i class="fa-solid fa-circle-check text-emerald-600"></i> PAID (LUNAS)
                                </span>
                            </template>
                            <template x-if="inv.payment_status === 'PARTIAL' || (inv.remaining_amount && inv.remaining_amount > 0)">
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-100 text-amber-800 border border-amber-500 rounded-md text-xs font-extrabold uppercase tracking-wider">
                                    <i class="fa-solid fa-clock-rotate-left text-amber-600"></i> DP (PARSIAL)
                                </span>
                            </template>
                            <template x-if="inv.payment_status === 'UNPAID'">
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-rose-100 text-rose-800 border border-rose-500 rounded-md text-xs font-extrabold uppercase tracking-wider">
                                    <i class="fa-solid fa-circle-xmark text-rose-600"></i> UNPAID (BELUM LUNAS)
                                </span>
                            </template>
                        </div>
                        <div class="text-xs text-slate-500 font-mono" x-text="'Tgl: ' + (inv.created_at || '-')"></div>
                        <div class="text-xs text-slate-500" x-text="'Kasir: ' + (inv.cashier_name || 'Kasir')"></div>
                    </div>
                </div>

                <!-- Client & Production Info (If Available) -->
                <div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-200 mb-3 text-xs" x-show="inv.customer_name || inv.customer_phone || inv.due_date || inv.production_notes">
                    <div class="grid grid-cols-2 gap-2">
                        <div x-show="inv.customer_name">
                            <span class="text-slate-500 text-[11px]">Client / Pemesan:</span>
                            <div class="font-bold text-slate-900" x-text="inv.customer_name"></div>
                        </div>
                        <div x-show="inv.customer_phone">
                            <span class="text-slate-500 text-[11px]">Kontak WhatsApp:</span>
                            <div class="font-mono text-emerald-700 font-bold" x-text="inv.customer_phone"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-1 pt-1 border-t border-slate-200/80" x-show="inv.due_date || inv.production_notes">
                        <div x-show="inv.due_date">
                            <span class="text-slate-500 text-[11px]">Estimasi Selesai (DL):</span>
                            <div class="font-bold text-indigo-700" x-text="inv.due_date"></div>
                        </div>
                        <div x-show="inv.production_notes">
                            <span class="text-slate-500 text-[11px]">Catatan Produksi:</span>
                            <div class="text-slate-800" x-text="inv.production_notes"></div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Items Table -->
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm text-xs mb-0">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th style="width: 30px;" class="text-center">No</th>
                                <th>Deskripsi Bahan / Pesanan</th>
                                <th class="text-center" style="width: 80px;">Qty</th>
                                <th class="text-end" style="width: 120px;">Harga Satuan</th>
                                <th class="text-end" style="width: 130px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in (inv.items || [])" :key="idx">
                                <tr>
                                    <td class="text-center" x-text="idx + 1"></td>
                                    <td>
                                        <strong class="text-slate-800" x-text="item.material_name || item.name || '-'"></strong>
                                        <div class="text-[10px] text-slate-400" x-show="item.specs" x-text="item.specs"></div>
                                    </td>
                                    <td class="text-center fw-semibold" x-text="item.qty_ordered || item.qty"></td>
                                    <td class="text-end font-mono" x-text="'Rp ' + Number(item.selling_price || item.price || 0).toLocaleString('id-ID')"></td>
                                    <td class="text-end font-mono fw-bold text-slate-800" x-text="'Rp ' + Number(item.subtotal || ((item.qty_ordered || item.qty) * (item.selling_price || item.price || 0))).toLocaleString('id-ID')"></td>
                                </tr>
                            </template>
                            <template x-if="!inv.items || inv.items.length === 0">
                                <tr>
                                    <td class="text-center">1</td>
                                    <td>
                                        <strong class="text-slate-800" x-text="inv.keterangan || 'Transaksi Penjualan Kasir POS'"></strong>
                                    </td>
                                    <td class="text-center">1</td>
                                    <td class="text-end font-mono" x-text="'Rp ' + Number(inv.total_price || 0).toLocaleString('id-ID')"></td>
                                    <td class="text-end font-mono fw-bold text-slate-800" x-text="'Rp ' + Number(inv.total_price || 0).toLocaleString('id-ID')"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-slate-50">
                            <tr>
                                <td colspan="4" class="text-end fw-bold text-slate-700">Total Nilai Pesanan:</td>
                                <td class="text-end fw-bold font-mono text-blue-900 fs-6" x-text="'Rp ' + Number(inv.total_price || 0).toLocaleString('id-ID')"></td>
                            </tr>
                            <template x-if="inv.payment_status === 'PARTIAL' || (inv.remaining_amount && inv.remaining_amount > 0)">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold text-emerald-700 text-xs">Uang Muka (DP) Diterima:</td>
                                    <td class="text-end font-mono font-bold text-emerald-700 text-xs" x-text="'Rp ' + Number(inv.paid_amount || 0).toLocaleString('id-ID')"></td>
                                </tr>
                            </template>
                            <template x-if="inv.payment_status === 'PARTIAL' || (inv.remaining_amount && inv.remaining_amount > 0)">
                                <tr class="bg-amber-50">
                                    <td colspan="4" class="text-end fw-bold text-amber-800 text-xs">Sisa Piutang (Pelunasan):</td>
                                    <td class="text-end font-mono font-extrabold text-amber-700 text-xs" x-text="'Rp ' + Number(inv.remaining_amount || 0).toLocaleString('id-ID')"></td>
                                </tr>
                            </template>
                            <tr>
                                <td colspan="4" class="text-end text-slate-500 text-[11px]">Metode Pembayaran:</td>
                                <td class="text-end text-slate-700 text-[11px] font-semibold" x-text="inv.payment_method || 'Cash / Kas Masuk'"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Footer notes -->
                <div class="p-2.5 bg-slate-50 rounded border text-center text-[11px] text-slate-500">
                    Terima kasih telah mencetak di <strong>SnapPrint</strong>. Simpan invoice ini sebagai bukti transaksi resmi.
                </div>
            </div>

            <!-- Modal Action Footer -->
            <div class="bg-slate-50 px-4 py-2.5 border-top d-flex justify-content-end gap-2">
                <button type="button" @click="open = false" class="btn-odoo-secondary">Tutup</button>
                <button type="button" @click="printSnapPrintInvoice(inv)" class="btn-odoo-primary">
                    <i class="fa-solid fa-print me-1"></i> Cetak Invoice / SPK
                </button>
            </div>
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
                    { element: '#tour-button', popover: { title: 'Navigasi SnapPrint ERP', description: 'Gunakan panduan ini kapan saja untuk melihat fitur di SnapPrint ERP.', side: "bottom" } },
                    { element: '.fa-table-cells', popover: { title: 'App Switcher (Home)', description: 'Buka App Matrix full-screen untuk berpindah modul secara cepat.', side: "right" } },
                    { element: '.o_searchview', popover: { title: 'Live Search View', description: 'Filter data tabel secara langsung dengan mengetik kata kunci.', side: "bottom" } }
                ]
            });
            driverObj.drive();
        }

        // Global Table Column Sorting Engine
        document.addEventListener('click', function(e) {
            const th = e.target.closest('th.sortable');
            if (!th) return;

            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr.search-row'));
            if (rows.length === 0) return;

            const thIndex = Array.from(th.parentNode.children).indexOf(th);
            const isAsc = !th.classList.contains('asc');

            table.querySelectorAll('th.sortable').forEach(header => {
                header.classList.remove('asc', 'desc');
                const icon = header.querySelector('.sort-icon');
                if (icon) icon.remove();
            });

            th.classList.toggle('asc', isAsc);
            th.classList.toggle('desc', !isAsc);
            
            const sortIcon = document.createElement('i');
            sortIcon.className = `sort-icon fa-solid fa-arrow-${isAsc ? 'up' : 'down'} text-[10px] text-blue-600 ms-1`;
            th.appendChild(sortIcon);

            rows.sort((rowA, rowB) => {
                const cellA = rowA.children[thIndex]?.innerText.trim() || '';
                const cellB = rowB.children[thIndex]?.innerText.trim() || '';

                const cleanNumA = cellA.replace(/[^0-9.-]+/g, '');
                const cleanNumB = cellB.replace(/[^0-9.-]+/g, '');
                const isNum = cleanNumA !== '' && cleanNumB !== '' && !isNaN(cleanNumA) && !isNaN(cleanNumB);

                if (isNum) {
                    return isAsc ? (parseFloat(cleanNumA) - parseFloat(cleanNumB)) : (parseFloat(cleanNumB) - parseFloat(cleanNumA));
                }
                return isAsc ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });

            rows.forEach(row => tbody.appendChild(row));
        });

        // Global Table Live Search Filter Engine
        document.addEventListener('input', function(e) {
            if (!e.target.classList.contains('table-search-input')) return;
            const query = e.target.value.toLowerCase().trim();
            const wrapper = document.querySelector('[data-view-wrapper]') || document;

            wrapper.querySelectorAll('tr.search-row').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });

            wrapper.querySelectorAll('.search-card').forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        });

        // Global List & Kanban Dual View Mode Switcher
        function toggleViewMode(mode, wrapperId) {
            const wrapper = document.getElementById(wrapperId) || document.querySelector('[data-view-wrapper]');
            if (!wrapper) return;

            const tableView = wrapper.querySelector('.table-view-container');
            const gridView = wrapper.querySelector('.grid-view-container');
            const btnList = document.querySelector('.btn-view-list');
            const btnGrid = document.querySelector('.btn-view-grid');

            if (mode === 'list') {
                if (tableView) tableView.classList.remove('d-none');
                if (gridView) gridView.classList.add('d-none');
                if (btnList) { btnList.classList.add('active', 'text-slate-700'); btnList.classList.remove('text-slate-400'); }
                if (btnGrid) { btnGrid.classList.remove('active', 'text-slate-700'); btnGrid.classList.add('text-slate-400'); }
            } else {
                if (tableView) tableView.classList.add('d-none');
                if (gridView) gridView.classList.remove('d-none');
                if (btnGrid) { btnGrid.classList.add('active', 'text-slate-700'); btnGrid.classList.remove('text-slate-400'); }
                if (btnList) { btnList.classList.remove('active', 'text-slate-700'); btnList.classList.add('text-slate-400'); }
            }
        }

        // Global Excel Export Engine (SheetJS)
        function exportTableToExcel(tableId, filename = 'SnapPrint_Export') {
            const table = document.getElementById(tableId);
            if (!table) {
                alert('Tabel tidak ditemukan untuk diekspor.');
                return;
            }
            if (typeof XLSX === 'undefined') {
                alert('Library Excel Export sedang dimuat, silakan coba sesaat lagi.');
                return;
            }
            const wb = XLSX.utils.table_to_book(table, { sheet: "Data" });
            XLSX.writeFile(wb, `${filename}_${new Date().toISOString().slice(0,10)}.xlsx`);
        }

        // Global Invoice Viewer Helper
        window.openSnapPrintInvoice = function(invData) {
            window.dispatchEvent(new CustomEvent('open-invoice-modal', { detail: invData }));
        };

        // Global Printable Invoice Generator
        window.printSnapPrintInvoice = function(inv) {
            const printWindow = window.open('', '_blank');
            const logoUrl = "{{ asset('images/logosnaprint.jpeg') }}";
            const isPartial = inv.payment_status === 'PARTIAL' || (inv.remaining_amount && inv.remaining_amount > 0);
            
            const itemsHtml = (inv.items && inv.items.length > 0) ? inv.items.map((it, idx) => `
                <tr>
                    <td style="text-align: center; padding: 8px; border: 1px solid #cbd5e1;">${idx + 1}</td>
                    <td style="padding: 8px; border: 1px solid #cbd5e1;">
                        <strong>${it.material_name || it.name || '-'}</strong>
                        ${it.specs ? `<br><small style="color: #64748b;">${it.specs}</small>` : ''}
                    </td>
                    <td style="text-align: center; padding: 8px; border: 1px solid #cbd5e1;">${it.qty_ordered || it.qty || 1}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #cbd5e1; font-family: monospace;">Rp ${Number(it.selling_price || it.price || 0).toLocaleString('id-ID')}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #cbd5e1; font-family: monospace; font-weight: bold;">Rp ${Number(it.subtotal || ((it.qty_ordered || it.qty || 1) * (it.selling_price || it.price || 0))).toLocaleString('id-ID')}</td>
                </tr>
            `).join('') : `
                <tr>
                    <td style="text-align: center; padding: 8px; border: 1px solid #cbd5e1;">1</td>
                    <td style="padding: 8px; border: 1px solid #cbd5e1;"><strong>${inv.keterangan || 'Transaksi Penjualan Kasir POS'}</strong></td>
                    <td style="text-align: center; padding: 8px; border: 1px solid #cbd5e1;">1</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #cbd5e1; font-family: monospace;">Rp ${Number(inv.total_price || 0).toLocaleString('id-ID')}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #cbd5e1; font-family: monospace; font-weight: bold;">Rp ${Number(inv.total_price || 0).toLocaleString('id-ID')}</td>
                </tr>
            `;

            printWindow.document.write(`
                <html>
                <head>
                    <title>Invoice - ${inv.invoice_number || 'Document'}</title>
                    <style>
                        body { font-family: 'Helvetica Neue', Arial, sans-serif; padding: 40px; color: #1e293b; font-size: 13px; }
                        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #1e3a8a; padding-bottom: 15px; margin-bottom: 20px; align-items: center; }
                        .brand-container { display: flex; align-items: center; gap: 14px; }
                        .brand-logo { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; }
                        .brand { font-size: 22px; font-weight: bold; color: #1e3a8a; }
                        .title { font-size: 18px; font-weight: bold; text-align: right; color: #0f172a; }
                        .stamp { display: inline-block; padding: 4px 12px; border: 2px solid ${isPartial ? '#d97706' : '#059669'}; color: ${isPartial ? '#d97706' : '#059669'}; font-weight: 800; border-radius: 6px; text-transform: uppercase; margin-bottom: 8px; font-size: 12px; }
                        .client-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 20px; }
                        .info-table { width: 100%; margin-bottom: 20px; }
                        .info-table td { padding: 4px 0; font-size: 13px; }
                        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                        .items-table th { background: #f1f5f9; padding: 10px; border: 1px solid #cbd5e1; text-align: left; font-size: 12px; }
                        .totals-table { width: 100%; margin-top: 15px; border-collapse: collapse; }
                        .totals-table td { padding: 6px 10px; text-align: right; }
                        .footer { margin-top: 40px; text-align: center; border-top: 1px solid #cbd5e1; padding-top: 15px; font-size: 11px; color: #64748b; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="brand-container">
                            <img src="${logoUrl}" alt="SnapPrint" class="brand-logo">
                            <div>
                                <div class="brand">SnapPrint</div>
                                <div style="font-size: 12px; color: #64748b;">Digital Printing & Advertising Solutions</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Cabang: <strong>${inv.branch_name || 'Pusat'}</strong></div>
                            </div>
                        </div>
                        <div class="title">
                            <div class="stamp">${isPartial ? '⚠ DP / UANG MUKA' : '✓ PAID (LUNAS)'}</div>
                            <div>FAKTUR / INVOICE ${isPartial ? '& SPK' : ''}</div>
                            <div style="font-size: 12px; font-weight: normal; color: #64748b; font-family: monospace;">No: ${inv.invoice_number || '-'}</div>
                        </div>
                    </div>

                    ${(inv.customer_name || inv.customer_phone || inv.due_date || inv.production_notes) ? `
                    <div class="client-box">
                        <table style="width: 100%; font-size: 12px;">
                            <tr>
                                <td style="width: 50%;"><strong>Client:</strong> ${inv.customer_name || 'Pelanggan Umum'}</td>
                                <td><strong>WhatsApp:</strong> ${inv.customer_phone || '-'}</td>
                            </tr>
                            ${(inv.due_date || inv.production_notes) ? `
                            <tr>
                                <td style="padding-top: 6px;"><strong>Deadline:</strong> ${inv.due_date || '-'}</td>
                                <td style="padding-top: 6px;"><strong>Catatan Produksi:</strong> ${inv.production_notes || '-'}</td>
                            </tr>` : ''}
                        </table>
                    </div>` : ''}

                    <table class="info-table">
                        <tr>
                            <td><strong>Tanggal Transaksi:</strong> ${inv.created_at || '-'}</td>
                            <td style="text-align: right;"><strong>Metode Pembayaran:</strong> ${inv.payment_method || 'Cash'}</td>
                        </tr>
                        <tr>
                            <td><strong>Petugas Kasir:</strong> ${inv.cashier_name || 'Kasir'}</td>
                            <td style="text-align: right;"><strong>Status:</strong> Dokumen Resmi Terverifikasi</td>
                        </tr>
                    </table>

                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="width: 30px; text-align: center;">No</th>
                                <th>Deskripsi Item / Pesanan</th>
                                <th style="width: 80px; text-align: center;">Qty</th>
                                <th style="width: 130px; text-align: right;">Harga Satuan</th>
                                <th style="width: 140px; text-align: right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>

                    <table class="totals-table">
                        <tr>
                            <td colspan="4" style="font-weight: bold;">Total Nilai Pesanan:</td>
                            <td style="font-weight: bold; font-family: monospace; font-size: 15px; color: #1e3a8a; width: 140px;">Rp ${Number(inv.total_price || 0).toLocaleString('id-ID')}</td>
                        </tr>
                        ${isPartial ? `
                        <tr>
                            <td colspan="4" style="font-weight: bold; color: #059669;">Uang Muka (DP) Dibayar:</td>
                            <td style="font-weight: bold; font-family: monospace; color: #059669;">Rp ${Number(inv.paid_amount || 0).toLocaleString('id-ID')}</td>
                        </tr>
                        <tr style="background: #fffbeb;">
                            <td colspan="4" style="font-weight: bold; color: #b45309;">Sisa Piutang (Pelunasan):</td>
                            <td style="font-weight: bold; font-family: monospace; font-size: 15px; color: #b45309;">Rp ${Number(inv.remaining_amount || 0).toLocaleString('id-ID')}</td>
                        </tr>
                        ` : ''}
                    </table>

                    <div class="footer">
                        Terima kasih atas kepercayaan Anda mencetak di SnapPrint. Dokumen ini sah dan diterbitkan secara otomatis oleh sistem SnapPrint ERP.
                    </div>

                    <script>
                        window.onload = function() { window.print(); }
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        };
    </script>
</body>
</html>
