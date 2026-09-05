@php
    $months = [
        1 => ['short' => 'Jan', 'full' => 'Januari'],
        2 => ['short' => 'Feb', 'full' => 'Februari'],
        3 => ['short' => 'Mar', 'full' => 'Maret'],
        4 => ['short' => 'Apr', 'full' => 'April'],
        5 => ['short' => 'Mei', 'full' => 'Mei'],
        6 => ['short' => 'Jun', 'full' => 'Juni'],
        7 => ['short' => 'Jul', 'full' => 'Juli'],
        8 => ['short' => 'Agu', 'full' => 'Agustus'],
        9 => ['short' => 'Sep', 'full' => 'September'],
        10 => ['short' => 'Okt', 'full' => 'Oktober'],
        11 => ['short' => 'Nov', 'full' => 'November'],
        12 => ['short' => 'Des', 'full' => 'Desember'],
    ];

    $activeMonth = (int) ($selectedMonth ?? request('month', date('n')));
    $activeYear = (int) ($selectedYear ?? request('year', date('Y')));
    $activeTf = $timeframe ?? request('timeframe', 'month');
    $routeName = $route ?? Route::currentRouteName();
    $preserveParams = $extraParams ?? request()->except(['month', 'year', 'page', 'start_date', 'end_date', 'date']);
    
    // Years to show in selector: current year + 1 down to 4 years back
    $years = range(date('Y') + 1, date('Y') - 4);
@endphp

<div class="monthly-lifecycle-container bg-slate-50 border border-slate-200 rounded-2xl p-2.5 mb-3 shadow-2xs">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <!-- Title & Info -->
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-blue-100 text-blue-800 border border-blue-200 font-bold text-xs px-2.5 py-1 rounded-xl d-inline-flex align-items-center gap-1.5">
                <i class="fa-solid fa-calendar-week text-blue-600"></i>
                <span>Siklus Bulanan (Jan - Des)</span>
            </span>
            <span class="text-[11px] text-slate-500 font-medium d-none d-md-inline">
                Periode Terpilih: <strong class="text-slate-800 font-semibold">{{ $months[$activeMonth]['full'] ?? 'Bulan' }} {{ $activeYear }}</strong> 
                (1 {{ $months[$activeMonth]['short'] ?? '' }} - {{ \Carbon\Carbon::createFromDate($activeYear, $activeMonth, 1)->endOfMonth()->format('d') }} {{ $months[$activeMonth]['short'] ?? '' }})
            </span>
        </div>

        <!-- Year Selector Dropdown -->
        <div class="d-flex align-items-center gap-1.5">
            <span class="text-[11px] font-bold text-slate-500 uppercase">Tahun:</span>
            <div class="dropdown">
                <button class="btn btn-sm btn-white border border-slate-300 rounded-xl px-2.5 py-1 font-bold text-xs dropdown-toggle shadow-2xs text-slate-800" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-regular fa-calendar me-1 text-blue-600"></i> {{ $activeYear }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-md rounded-xl text-xs py-1 border-slate-200">
                    @foreach($years as $y)
                        <li>
                            <a class="dropdown-item py-1.5 px-3 {{ $activeYear == $y ? 'active fw-bold bg-blue-600 text-white' : 'text-slate-700' }}" 
                               href="{{ route($routeName, array_merge($preserveParams, ['year' => $y, 'month' => $activeMonth, 'timeframe' => $activeTf, 'period' => ($period ?? 'monthly')])) }}">
                                Tahun {{ $y }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if(isset($showAllYear) && $showAllYear)
            <a href="{{ route($routeName, array_merge($preserveParams, ['timeframe' => 'year', 'year' => $activeYear, 'period' => 'yearly'])) }}" 
               class="btn btn-sm text-xs px-2.5 py-1 rounded-xl font-bold transition {{ ($activeTf ?? '') === 'year' || ($activeTf ?? '') === '1Y' ? 'bg-slate-900 text-white shadow-xs' : 'btn-white border border-slate-300 text-slate-700 hover:bg-slate-100' }}"
               title="Lihat Rekap Seluruh 12 Bulan Tahun {{ $activeYear }}">
                <i class="fa-solid fa-chart-line me-1"></i> Full Year
            </a>
            @endif
        </div>
    </div>

    <!-- 12 Month Pills (Januari - Desember) -->
    <div class="d-flex align-items-center gap-1 mt-2.5 overflow-x-auto pb-1 flex-nowrap" style="scrollbar-width: thin;">
        @foreach($months as $mNum => $mMeta)
            @php
                $isCurrentRealMonth = (date('n') == $mNum && date('Y') == $activeYear);
                $isSelected = ($activeMonth == $mNum && ($activeTf !== 'year' && $activeTf !== '1Y'));
                
                // Construct URL parameters
                $urlParams = array_merge($preserveParams, [
                    'month' => $mNum,
                    'year' => $activeYear,
                    'timeframe' => 'month',
                    'period' => ($period ?? 'monthly'),
                    'start_date' => \Carbon\Carbon::createFromDate($activeYear, $mNum, 1)->startOfMonth()->toDateString(),
                    'end_date' => \Carbon\Carbon::createFromDate($activeYear, $mNum, 1)->endOfMonth()->toDateString()
                ]);
            @endphp
            <a href="{{ route($routeName, $urlParams) }}" 
               class="btn btn-sm text-xs py-1 px-2.5 rounded-xl font-bold flex-shrink-0 transition text-decoration-none d-flex align-items-center gap-1 {{ $isSelected ? 'bg-blue-600 text-white shadow-xs border-0' : ($isCurrentRealMonth ? 'btn-white border-2 border-blue-400 text-blue-700 font-extrabold hover:bg-blue-50' : 'btn-white border border-slate-200 text-slate-700 hover:bg-slate-100 hover:text-slate-900') }}"
               title="{{ $mMeta['full'] }} {{ $activeYear }} (1 s/d {{ \Carbon\Carbon::createFromDate($activeYear, $mNum, 1)->endOfMonth()->format('d') }})">
                <span>{{ $mMeta['short'] }}</span>
                @if($isCurrentRealMonth && !$isSelected)
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 inline-block" title="Bulan Sekarang"></span>
                @endif
            </a>
        @endforeach
    </div>
</div>
