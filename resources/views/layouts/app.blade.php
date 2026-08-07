<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrintShop ERP & POS</title>
    <!-- Google Fonts for premium typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Instrument Sans', 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased flex flex-col md:flex-row">

    @auth
        <!-- ==================== MOBILE NAVIGATION ==================== -->
        <div class="md:hidden sticky top-0 z-40 flex items-center justify-between h-16 px-4 bg-slate-900 border-b border-slate-800 text-white w-full">
            <div class="flex items-center gap-2">
                <svg class="h-7 w-7 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span class="font-bold text-base tracking-wider">Snaprint <span class="text-indigo-400">ERP</span></span>
            </div>
            
            <button type="button" onclick="toggleMobileSidebar(true)" class="p-2 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white focus:outline-none">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Mobile Sidebar Off-Canvas Container -->
        <div id="mobile-sidebar" class="fixed inset-0 z-50 flex hidden">
            <!-- Backdrop -->
            <div id="mobile-backdrop" onclick="toggleMobileSidebar(false)" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300 ease-out opacity-0"></div>
            
            <!-- Sidebar content panel -->
            <div id="mobile-panel" class="relative flex flex-col w-full max-w-xs flex-grow bg-slate-900 pt-5 pb-4 transition-transform duration-300 ease-out -translate-x-full">
                <!-- Close Button -->
                <div class="absolute top-0 right-0 -mr-12 pt-4">
                    <button type="button" onclick="toggleMobileSidebar(false)" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-shrink-0 flex items-center px-6 gap-2 mb-6">
                    <svg class="h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span class="text-white font-black text-lg tracking-wider">Snaprint <span class="text-indigo-400">ERP</span></span>
                </div>

                <!-- Navigation List Mobile -->
                <div class="mt-5 flex-1 h-0 overflow-y-auto px-4">
                    <nav class="space-y-1">
                        @if(auth()->user()->isOwner() || auth()->user()->isManager())
                            <a href="{{ route('owner.dashboard') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('owner.dashboard') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                Dashboard
                            </a>
                        @endif

                        <!-- Sales Dropdown -->
                        @if(auth()->user()->isCashier() || auth()->user()->isOwner() || auth()->user()->isManager())
                            <div x-data="{ open: {{ request()->routeIs('pos.*') || request()->routeIs('sales.*') ? 'true' : 'false' }} }" class="space-y-1">
                                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 text-slate-400 hover:bg-slate-800 hover:text-white">
                                    <div class="flex items-center">
                                        <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                        <span>Sales</span>
                                    </div>
                                    <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" x-collapse x-cloak class="pl-11 pr-4 space-y-1 mt-1">
                                    <a href="{{ route('pos.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('pos.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                        POS Checkout
                                    </a>
                                    <a href="{{ route('sales.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('sales.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                        Riwayat Penjualan
                                    </a>
                                </div>
                            </div>
                        @endif

                        <!-- Purchasing Dropdown -->
                        @if(auth()->user()->isPurchasing() || auth()->user()->isOwner() || auth()->user()->isManager())
                            <div x-data="{ open: {{ request()->routeIs('purchasing.*') || request()->routeIs('suppliers.*') ? 'true' : 'false' }} }" class="space-y-1">
                                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 text-slate-400 hover:bg-slate-800/60 hover:text-white">
                                    <div class="flex items-center">
                                        <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                        <span>Purchasing</span>
                                    </div>
                                    <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" x-collapse x-cloak class="pl-11 pr-4 space-y-1 mt-1">
                                    <a href="{{ route('purchasing.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('purchasing.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                        Purchasing
                                    </a>
                                    <a href="{{ route('suppliers.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('suppliers.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                        Data Supplier
                                    </a>
                                </div>
                            </div>
                        @endif

                        <!-- Stock Menu (Manager & Owner) -->
                        @if(auth()->user()->isManager() || auth()->user()->isOwner())
                            <a href="{{ route('stock.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('stock.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span>Stock</span>
                            </a>
                        @endif

                        <!-- Finance Dropdown -->
                        @if(auth()->user()->isOwner() || auth()->user()->isManager())
                            <div x-data="{ open: {{ request()->routeIs('dashboard') || request()->routeIs('accounts.*') || request()->routeIs('kas-masuk.*') || request()->routeIs('kas-keluar.*') || request()->routeIs('reports.*') ? 'true' : 'false' }} }" class="space-y-1">
                                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 text-slate-400 hover:bg-slate-800/60 hover:text-white">
                                    <div class="flex items-center">
                                        <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Finance</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>INFO EXAMPLE</span>
                                    </div>
                                    <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" x-collapse x-cloak class="pl-11 pr-4 space-y-1 mt-1">
                                    <a href="{{ route('dashboard') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                        Dashboard Keuangan
                                    </a>
                                    <a href="{{ route('accounts.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('accounts.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                        Master Akun
                                    </a>
                                    <a href="{{ route('kas-masuk.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('kas-masuk.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                        Kas Masuk
                                    </a>
                                    <a href="{{ route('kas-keluar.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('kas-keluar.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                        Kas Keluar
                                    </a>
                                    
                                    <!-- Nested Laporan Dropdown -->
                                    <div x-data="{ reportsOpen: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }" class="space-y-1 mt-2 border-t border-slate-800/50 pt-2">
                                        <button @click="reportsOpen = !reportsOpen" type="button" class="w-full flex items-center justify-between py-1.5 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500 hover:text-white">
                                            <span>Laporan</span>
                                            <svg class="h-3 w-3 transition-transform duration-200" :class="{ 'rotate-180': reportsOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="reportsOpen" x-collapse x-cloak class="pl-3 space-y-0.5">
                                            <a href="{{ route('reports.cash-balance') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.cash-balance') ? 'bg-indigo-600/60 text-white' : 'text-slate-400 hover:text-white' }}">Saldo Kas</a>
                                            <a href="{{ route('reports.cash-mutation') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.cash-mutation') ? 'bg-indigo-600/60 text-white' : 'text-slate-400 hover:text-white' }}">Buku Mutasi</a>
                                            <a href="{{ route('reports.cash-in') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.cash-in') ? 'bg-indigo-600/65 text-white' : 'text-slate-400 hover:text-white' }}">Kas Masuk</a>
                                            <a href="{{ route('reports.cash-out') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.cash-out') ? 'bg-indigo-600/65 text-white' : 'text-slate-400 hover:text-white' }}">Kas Keluar</a>
                                            <a href="{{ route('reports.sales') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.sales') ? 'bg-indigo-600/65 text-white' : 'text-slate-400 hover:text-white' }}">Analisa Penjualan</a>
                                            <a href="{{ route('reports.expenses') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.expenses') ? 'bg-indigo-600/65 text-white' : 'text-slate-400 hover:text-white' }}">Pengeluaran Operasional</a>
                                            <a href="{{ route('reports.profit-loss') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.profit-loss') ? 'bg-indigo-600/65 text-white' : 'text-slate-400 hover:text-white' }}">Laba Rugi</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- System Settings -->
                        @if(auth()->user()->isOwner() || auth()->user()->isManager())
                            <div class="pt-4 mt-2 border-t border-slate-800">
                                <p class="px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Sistem</p>
                                <a href="{{ route('users.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                    <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Manajemen User
                                </a>
                                @if(auth()->user()->isOwner())
                                    <a href="{{ route('branches.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('branches.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }} mt-1">
                                        <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        Manajemen Cabang
                                    </a>
                                @endif
                            </div>
                        @endif
                    </nav>
                </div>

                <!-- Active User Section Mobile -->
                <div class="flex-shrink-0 flex border-t border-slate-800 p-4">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center">
                            <div class="h-9 w-9 rounded-full bg-slate-800 flex items-center justify-center text-indigo-400 font-bold text-sm">
                                {{ strtoupper(substr(auth()->user()->username, 0, 2)) }}
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-white">{{ auth()->user()->username }}</p>
                                <p class="text-xs font-medium text-slate-500 capitalize mb-1">{{ auth()->user()->role }}</p>
                                @if(auth()->user()->branch)
                                    <span class="inline-flex items-center rounded-md bg-indigo-500/10 px-2 py-1 text-xs font-medium text-indigo-400 ring-1 ring-inset ring-indigo-500/20">Cabang: {{ auth()->user()->branch->nama_cabang }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-indigo-500/10 px-2 py-1 text-xs font-medium text-indigo-400 ring-1 ring-inset ring-indigo-500/20">Semua Cabang</span>
                                @endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-red-400 hover:bg-slate-800 transition">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== DESKTOP NAVIGATION ==================== -->
        <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 bg-slate-900 border-r border-slate-800">
            <!-- Brand Logotype -->
            <div class="flex items-center h-16 flex-shrink-0 px-6 gap-2.5 bg-slate-950/20">
                <svg class="h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span class="text-white font-black text-lg tracking-wider">Snaprint <span class="text-indigo-400">ERP</span></span>
            </div>

            <!-- Navigation Links -->
            <div class="flex-grow flex flex-col justify-between pt-6 overflow-y-auto px-4">
                <nav class="space-y-1.5">
                    @if(auth()->user()->isOwner() || auth()->user()->isManager())
                        <a href="{{ route('owner.dashboard') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('owner.dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                            <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            Dashboard
                        </a>
                    @endif

                    <!-- Sales Dropdown -->
                    @if(auth()->user()->isCashier() || auth()->user()->isOwner() || auth()->user()->isManager())
                        <div x-data="{ open: {{ request()->routeIs('pos.*') || request()->routeIs('sales.*') ? 'true' : 'false' }} }" class="space-y-1">
                            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 text-slate-400 hover:bg-slate-800/60 hover:text-white">
                                <div class="flex items-center">
                                    <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    <span>Sales</span>
                                </div>
                                <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-collapse x-cloak class="pl-11 pr-4 space-y-1 mt-1">
                                <a href="{{ route('pos.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('pos.*') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                    POS Checkout
                                </a>
                                <a href="{{ route('sales.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('sales.*') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                    Riwayat Penjualan
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- Purchasing Dropdown -->
                    @if(auth()->user()->isPurchasing() || auth()->user()->isOwner() || auth()->user()->isManager())
                        <div x-data="{ open: {{ request()->routeIs('purchasing.*') || request()->routeIs('suppliers.*') ? 'true' : 'false' }} }" class="space-y-1">
                            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 text-slate-400 hover:bg-slate-800/60 hover:text-white">
                                <div class="flex items-center">
                                    <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    <span>Purchasing</span>
                                </div>
                                <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-collapse x-cloak class="pl-11 pr-4 space-y-1 mt-1">
                                <a href="{{ route('purchasing.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('purchasing.index') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                    Purchasing
                                </a>
                                <a href="{{ route('suppliers.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('suppliers.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                    Data Supplier
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- Stock Menu (Manager & Owner) -->
                    @if(auth()->user()->isManager() || auth()->user()->isOwner())
                        <a href="{{ route('stock.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('stock.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                            <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span>Stock</span>
                        </a>
                    @endif

                    <!-- Finance Dropdown -->
                    @if(auth()->user()->isOwner() || auth()->user()->isManager())
                        <div x-data="{ open: {{ request()->routeIs('dashboard') || request()->routeIs('accounts.*') || request()->routeIs('kas-masuk.*') || request()->routeIs('kas-keluar.*') || request()->routeIs('reports.*') ? 'true' : 'false' }} }" class="space-y-1">
                            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 text-slate-400 hover:bg-slate-800/60 hover:text-white">
                                <div class="flex items-center">
                                    <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Finance</span>
                                </div>
                                <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-collapse x-cloak class="pl-11 pr-4 space-y-1 mt-1">
                                <a href="{{ route('dashboard') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                    Dashboard Keuangan
                                </a>
                                <a href="{{ route('accounts.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('accounts.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                    Master Akun
                                </a>
                                <a href="{{ route('kas-masuk.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('kas-masuk.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                    Kas Masuk
                                </a>
                                <a href="{{ route('kas-keluar.index') }}" class="block py-2 text-sm font-medium rounded-lg px-3 transition duration-150 {{ request()->routeIs('kas-keluar.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                    Kas Keluar
                                </a>
                                
                                <!-- Nested Laporan Dropdown -->
                                <div x-data="{ reportsOpen: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }" class="space-y-1 mt-2 border-t border-slate-800/50 pt-2">
                                    <button @click="reportsOpen = !reportsOpen" type="button" class="w-full flex items-center justify-between py-1.5 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500 hover:text-white">
                                        <span>Laporan</span>
                                        <svg class="h-3 w-3 transition-transform duration-200" :class="{ 'rotate-180': reportsOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <div x-show="reportsOpen" x-collapse x-cloak class="pl-3 space-y-0.5">
                                        <a href="{{ route('reports.cash-balance') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.cash-balance') ? 'bg-indigo-600/60 text-white' : 'text-slate-400 hover:text-white' }}">Saldo Kas</a>
                                        <a href="{{ route('reports.cash-mutation') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.cash-mutation') ? 'bg-indigo-600/60 text-white' : 'text-slate-400 hover:text-white' }}">Buku Mutasi</a>
                                        <a href="{{ route('reports.cash-in') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.cash-in') ? 'bg-indigo-600/65 text-white' : 'text-slate-400 hover:text-white' }}">Kas Masuk</a>
                                        <a href="{{ route('reports.cash-out') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.cash-out') ? 'bg-indigo-600/65 text-white' : 'text-slate-400 hover:text-white' }}">Kas Keluar</a>
                                        <a href="{{ route('reports.sales') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.sales') ? 'bg-indigo-600/65 text-white' : 'text-slate-400 hover:text-white' }}">Analisa Penjualan</a>
                                        <a href="{{ route('reports.expenses') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.expenses') ? 'bg-indigo-600/65 text-white' : 'text-slate-400 hover:text-white' }}">Pengeluaran Operasional</a>
                                        <a href="{{ route('reports.profit-loss') }}" class="block py-1.5 text-xs font-medium rounded-lg px-2.5 transition {{ request()->routeIs('reports.profit-loss') ? 'bg-indigo-600/65 text-white' : 'text-slate-400 hover:text-white' }}">Laba Rugi</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- System Settings -->
                    @if(auth()->user()->isOwner() || auth()->user()->isManager())
                        <div class="pt-4 mt-2 border-t border-slate-800">
                            <p class="px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Sistem</p>
                            <a href="{{ route('users.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Manajemen User
                            </a>
                            @if(auth()->user()->isOwner())
                                <a href="{{ route('branches.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('branches.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} mt-1">
                                    <svg class="mr-3 h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    Manajemen Cabang
                                </a>
                            @endif
                        </div>
                    @endif
                </nav>

                <!-- Profile and Logout Block -->
                <div class="border-t border-slate-800 py-5 my-2 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-9 w-9 rounded-full bg-slate-800 flex items-center justify-center text-indigo-400 font-bold text-sm border border-slate-700">
                            {{ strtoupper(substr(auth()->user()->username, 0, 2)) }}
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white">{{ auth()->user()->username }}</p>
                            <p class="text-xs text-slate-500 capitalize mb-1">{{ auth()->user()->role }}</p>
                            @if(auth()->user()->branch)
                                <span class="inline-flex items-center rounded-md bg-indigo-500/10 px-2 py-1 text-xs font-medium text-indigo-400 ring-1 ring-inset ring-indigo-500/20">Cabang: {{ auth()->user()->branch->nama_cabang }}</span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-indigo-500/10 px-2 py-1 text-xs font-medium text-indigo-400 ring-1 ring-inset ring-indigo-500/20">Semua Cabang</span>
                            @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-2 rounded-xl text-slate-400 hover:text-red-400 hover:bg-slate-800/60 transition" title="Logout">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endauth



    <!-- ==================== MAIN CONTENT CONTAINER ==================== -->
    <div class="@auth md:pl-64 @endauth flex flex-col flex-1 min-w-0 min-h-screen bg-slate-50">
   

        <!-- Header area for dynamic page title context -->
        @auth
            <header class="hidden md:flex justify-between items-center h-16 px-8 border-b border-slate-200 bg-white">
                <h1 class="text-sm font-medium text-slate-500">
                    ERP System / <span class="text-slate-800 font-semibold capitalize">{{ request()->segment(1) }}</span>
                </h1>
                <div class="text-xs text-slate-400 font-medium">
                    {{ date('D, d M Y') }}
                </div>
            </header>
        @endauth

        <main class="flex-grow p-4 md:p-8 max-w-7xl w-full mx-auto">
            
            <!-- Toast System Alerts -->
            @if(session('success'))
                <div class="mb-6 flex items-center p-4 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl shadow-sm animate-fade-in-down" role="alert">
                    <svg class="w-5 h-5 mr-3 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            @endif
            
            @if(session('error'))
                <div class="mb-6 flex items-center p-4 bg-rose-50 border border-rose-100 text-rose-800 rounded-2xl shadow-sm" role="alert">
                    <svg class="w-5 h-5 mr-3 text-rose-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-rose-50 border border-rose-100 text-rose-800 rounded-2xl shadow-sm">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 mr-3 text-rose-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="text-sm font-semibold">Please correct the following errors:</span>
                    </div>
                    <ul class="list-disc ml-8 text-sm space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Main Content Insertion -->
            @yield('content')
        </main>

        <footer class="border-t border-slate-200/60 bg-white py-5 text-center mt-auto w-full">
            <p class="text-xs text-slate-400 font-medium">&copy; {{ date('Y') }} PrintShop ERP. Made for heavy daily shop operations.</p>
        </footer>
    </div>

    <!-- Vanilla Javascript sidebar toggles -->
    <script>
        function toggleMobileSidebar(open) {
            const container = document.getElementById('mobile-sidebar');
            const backdrop = document.getElementById('mobile-backdrop');
            const panel = document.getElementById('mobile-panel');

            if (open) {
                container.classList.remove('hidden');
                setTimeout(() => {
                    backdrop.classList.remove('opacity-0');
                    backdrop.classList.add('opacity-100');
                    panel.classList.remove('-translate-x-full');
                    panel.classList.add('translate-x-0');
                }, 10);
            } else {
                backdrop.classList.remove('opacity-100');
                backdrop.classList.add('opacity-0');
                panel.classList.remove('translate-x-0');
                panel.classList.add('-translate-x-full');
                
                setTimeout(() => {
                    container.classList.add('hidden');
                }, 300); // match duration-300
            }
        }
    </script>
</body>
</html>
