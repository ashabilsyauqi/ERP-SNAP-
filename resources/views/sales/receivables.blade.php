@extends('layouts.app')

@section('title', 'Piutang & Pesanan DP')
@section('page-title', 'Daftar Piutang & Monitoring Pesanan DP')

@section('action-buttons')
<a href="{{ route('pos.index') }}" class="btn-odoo-primary text-decoration-none">
    <i class="fa-solid fa-cash-register me-1"></i> Buka Kasir (POS)
</a>
<a href="{{ route('sales.index') }}" class="btn-odoo-secondary text-decoration-none">
    <i class="fa-solid fa-receipt me-1"></i> Semua Riwayat Penjualan
</a>
@endsection

@section('content')
<div class="space-y-4 animate-fade-in" x-data="{ 
    settleModalOpen: false, 
    selectedTrx: { id: null, invoice_number: '', customer_name: '', total_price: 0, paid_amount: 0, remaining_amount: 0 },
    openSettle(trx) {
        this.selectedTrx = trx;
        this.settleModalOpen = true;
    }
}">

    <!-- KPI Metric Cards Header -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Total Piutang -->
        <div class="bg-white p-3.5 rounded-2xl border border-amber-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-amber-700 uppercase tracking-wider mb-0.5">Total Sisa Piutang</p>
                <h3 class="text-xl font-extrabold text-amber-900 font-mono mb-0">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-slate-500 font-medium">Tagihan yang belum dilunasi client</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shadow-sm border border-amber-200">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
        </div>

        <!-- Total DP Diterima -->
        <div class="bg-white p-3.5 rounded-2xl border border-emerald-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider mb-0.5">Total DP Diterima</p>
                <h3 class="text-xl font-extrabold text-emerald-900 font-mono mb-0">Rp {{ number_format($totalDpDiterima, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-slate-500 font-medium">Uang muka masuk kas</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shadow-sm border border-emerald-200">
                <i class="fa-solid fa-money-bill-transfer"></i>
            </div>
        </div>

        <!-- Sedang Produksi -->
        <div class="bg-white p-3.5 rounded-2xl border border-blue-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-blue-700 uppercase tracking-wider mb-0.5">Sedang Diproduksi</p>
                <h3 class="text-xl font-extrabold text-blue-900 font-mono mb-0">{{ $countInProduction }} <span class="text-xs font-normal text-slate-500">Order</span></h3>
                <span class="text-[10px] text-slate-500 font-medium">Proses cetak & finishing</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shadow-sm border border-blue-200">
                <i class="fa-solid fa-gears"></i>
            </div>
        </div>

        <!-- Siap Diambil -->
        <div class="bg-white p-3.5 rounded-2xl border border-indigo-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider mb-0.5">Siap Diambil (Ready)</p>
                <h3 class="text-xl font-extrabold text-indigo-900 font-mono mb-0">{{ $countReady }} <span class="text-xs font-normal text-slate-500">Order</span></h3>
                <span class="text-[10px] text-slate-500 font-medium">Menunggu client & pelunasan</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shadow-sm border border-indigo-200">
                <i class="fa-solid fa-box-open"></i>
            </div>
        </div>
    </div>

    <!-- Filter Toolbar & Navigation Tabs -->
    <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-3">
        <!-- Tabs -->
        <div class="flex flex-wrap items-center gap-1.5 w-full md:w-auto">
            <a href="{{ route('sales.receivables', ['tab' => 'unpaid']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold text-decoration-none transition {{ $tab === 'unpaid' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <i class="fa-solid fa-hand-holding-dollar me-1"></i> Piutang Belum Lunas
            </a>
            <a href="{{ route('sales.receivables', ['tab' => 'production']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold text-decoration-none transition {{ $tab === 'production' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <i class="fa-solid fa-gears me-1"></i> Sedang Produksi ({{ $countInProduction }})
            </a>
            <a href="{{ route('sales.receivables', ['tab' => 'ready']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold text-decoration-none transition {{ $tab === 'ready' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <i class="fa-solid fa-box-open me-1"></i> Siap Diambil ({{ $countReady }})
            </a>
            <a href="{{ route('sales.receivables', ['tab' => 'all']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold text-decoration-none transition {{ $tab === 'all' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Semua Pesanan
            </a>
            <a href="{{ route('sales.receivables', ['tab' => 'paid']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold text-decoration-none transition {{ $tab === 'paid' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Sudah Lunas (Histori)
            </a>
        </div>

        <!-- Search Table Filter -->
        <div class="w-full md:w-64 relative">
            <input type="text" class="table-search-input w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="Filter data client / invoice...">
            <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
        </div>
    </div>

    <!-- Main Receivables & Orders Table Sheet -->
    <div class="o_form_sheet overflow-hidden" data-view-wrapper>
        <div class="table-responsive">
            <table class="o_list_table table-sm mb-0" id="main-table">
                <thead>
                    <tr>
                        <th class="sortable text-center" style="width: 40px;">No</th>
                        <th class="sortable">No. Invoice & Tanggal</th>
                        <th class="sortable">Client / Pemesan</th>
                        <th>Item & Spesifikasi Pengerjaan</th>
                        <th class="sortable text-center">Status Produksi</th>
                        <th class="sortable text-end">Total Biaya</th>
                        <th class="sortable text-end">DP Masuk</th>
                        <th class="sortable text-end" style="color: #b45309;">Sisa Piutang</th>
                        <th class="sortable text-center">Status Bayar</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $index => $t)
                    @php
                        $invData = [
                            'invoice_number' => $t->invoice_number,
                            'created_at' => $t->created_at->format('d M Y H:i'),
                            'cashier_name' => $t->user->full_name ?: ($t->user->username ?? 'Kasir'),
                            'branch_name' => $t->branch->nama_cabang ?? 'Pusat',
                            'payment_method' => $t->payment_method,
                            'payment_status' => $t->payment_status,
                            'total_price' => $t->total_price,
                            'paid_amount' => $t->paid_amount,
                            'remaining_amount' => $t->remaining_amount,
                            'customer_name' => $t->customer_name,
                            'customer_phone' => $t->customer_phone,
                            'due_date' => $t->due_date ? $t->due_date->format('d M Y') : null,
                            'production_notes' => $t->production_notes,
                            'items' => $t->transactionDetails->map(function($d) {
                                return [
                                    'material_name' => $d->material->material_name ?? 'Item',
                                    'qty_ordered' => $d->qty_ordered,
                                    'selling_price' => $d->selling_price,
                                    'subtotal' => $d->qty_ordered * $d->selling_price
                                ];
                            })
                        ];
                    @endphp
                    <tr class="search-row hover:bg-slate-50/80 transition">
                        <td class="text-center text-slate-400 text-xs">{{ $index + 1 }}</td>
                        
                        <!-- Invoice & Date -->
                        <td>
                            <button type="button" 
                                onclick='openSnaprintInvoice(@json($invData))'
                                class="font-mono font-bold text-xs text-blue-600 hover:text-blue-800 hover:underline bg-transparent border-0 p-0 text-start cursor-pointer d-flex align-items-center gap-1.5"
                                title="Klik untuk membuka faktur resmi">
                                <i class="fa-solid fa-file-invoice text-blue-500"></i>
                                <span>{{ $t->invoice_number }}</span>
                            </button>
                            <div class="text-[10px] text-slate-400 mt-0.5">
                                <i class="fa-regular fa-clock me-0.5"></i> {{ $t->created_at->format('d/m/Y H:i') }}
                                @if($t->due_date)
                                    <span class="ms-1 text-indigo-600 font-semibold">&bull; DL: {{ $t->due_date->format('d/m/Y') }}</span>
                                @endif
                            </div>
                        </td>

                        <!-- Customer Info -->
                        <td>
                            <div class="font-bold text-xs text-slate-900">{{ $t->customer_name ?: 'Pelanggan Umum' }}</div>
                            @if($t->customer_phone)
                                @php
                                    $cleanPhone = preg_replace('/[^0-9]/', '', $t->customer_phone);
                                    if (Str::startsWith($cleanPhone, '0')) {
                                        $cleanPhone = '62' . substr($cleanPhone, 1);
                                    }
                                @endphp
                                <a href="https://wa.me/{{ $cleanPhone }}?text=Halo%20{{ urlencode($t->customer_name ?? 'Kak') }},%20mengenai%20pesanan%20cetak%20Snaprint%20dengan%20No%20Invoice%20{{ $t->invoice_number }}..." 
                                   target="_blank" 
                                   class="text-[11px] text-emerald-600 hover:text-emerald-700 font-semibold text-decoration-none d-inline-flex align-items-center gap-1 mt-0.5">
                                    <i class="fa-brands fa-whatsapp text-xs"></i> {{ $t->customer_phone }}
                                </a>
                            @else
                                <span class="text-[10px] text-slate-400">-</span>
                            @endif
                        </td>

                        <!-- Items & Production Notes -->
                        <td>
                            <div class="text-xs text-slate-700 line-clamp-1">
                                @foreach($t->transactionDetails as $d)
                                    <span class="font-medium">{{ $d->material->material_name ?? 'Bahan' }}</span> ({{ $d->qty_ordered }}x){{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            </div>
                            @if($t->production_notes)
                                <div class="text-[10px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200 mt-1 d-inline-block line-clamp-1">
                                    <i class="fa-solid fa-pen-ruler me-1"></i> {{ $t->production_notes }}
                                </div>
                            @endif
                        </td>

                        <!-- Order Production Status Dropdown -->
                        <td class="text-center">
                            <form action="{{ route('sales.status', $t->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <select name="order_status" onchange="this.form.submit()" class="form-select form-select-sm text-[11px] py-1 px-2 font-bold rounded-lg border cursor-pointer
                                    {{ $t->order_status === 'in_production' ? 'bg-blue-50 text-blue-700 border-blue-300' : '' }}
                                    {{ $t->order_status === 'ready' ? 'bg-indigo-50 text-indigo-700 border-indigo-300' : '' }}
                                    {{ $t->order_status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : '' }}
                                    {{ $t->order_status === 'cancelled' ? 'bg-rose-50 text-rose-700 border-rose-300' : '' }}">
                                    <option value="in_production" {{ $t->order_status === 'in_production' ? 'selected' : '' }}>🔨 Produksi</option>
                                    <option value="ready" {{ $t->order_status === 'ready' ? 'selected' : '' }}>📦 Siap Diambil</option>
                                    <option value="completed" {{ $t->order_status === 'completed' ? 'selected' : '' }}>✓ Selesai</option>
                                    <option value="cancelled" {{ $t->order_status === 'cancelled' ? 'selected' : '' }}>✕ Batal</option>
                                </select>
                            </form>
                        </td>

                        <!-- Total Price -->
                        <td class="text-end font-mono text-xs font-semibold text-slate-800">
                            Rp {{ number_format($t->total_price, 0, ',', '.') }}
                        </td>

                        <!-- Paid / DP Amount -->
                        <td class="text-end font-mono text-xs font-semibold text-emerald-700">
                            Rp {{ number_format($t->paid_amount, 0, ',', '.') }}
                        </td>

                        <!-- Remaining Piutang -->
                        <td class="text-end font-mono text-xs font-extrabold {{ $t->remaining_amount > 0 ? 'text-amber-600 bg-amber-50/50' : 'text-slate-400' }}">
                            Rp {{ number_format($t->remaining_amount, 0, ',', '.') }}
                        </td>

                        <!-- Payment Status Badge -->
                        <td class="text-center">
                            @if($t->isPaid())
                                <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] py-1 px-2 font-extrabold rounded-md">
                                    <i class="fa-solid fa-circle-check me-0.5"></i> PAID (LUNAS)
                                </span>
                            @elseif($t->isPartial())
                                <span class="badge bg-amber-100 text-amber-800 border border-amber-300 text-[10px] py-1 px-2 font-extrabold rounded-md">
                                    <i class="fa-solid fa-clock-rotate-left me-0.5"></i> DP (PARSIAL)
                                </span>
                            @else
                                <span class="badge bg-rose-100 text-rose-800 border border-rose-300 text-[10px] py-1 px-2 font-extrabold rounded-md">
                                    <i class="fa-solid fa-circle-xmark me-0.5"></i> UNPAID
                                </span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="text-center">
                            <div class="d-inline-flex items-center gap-1">
                                @if($t->remaining_amount > 0)
                                    <!-- Pelunasan Modal Trigger -->
                                    <button type="button" 
                                            @click="openSettle({
                                                id: {{ $t->id }},
                                                invoice_number: '{{ $t->invoice_number }}',
                                                customer_name: '{{ $t->customer_name ?: 'Pelanggan' }}',
                                                total_price: {{ $t->total_price }},
                                                paid_amount: {{ $t->paid_amount }},
                                                remaining_amount: {{ $t->remaining_amount }}
                                            })"
                                            class="btn btn-sm btn-primary py-0.5 px-2 text-[11px] font-bold d-inline-flex align-items-center gap-1 shadow-sm"
                                            title="Proses Pelunasan Piutang">
                                        <i class="fa-solid fa-credit-card"></i> Pelunasan
                                    </button>
                                @endif

                                <!-- Thermal Receipt 58mm Print Button -->
                                <a href="{{ route('sales.receipt', $t->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0.5 px-2 text-[11px]" title="Cetak Struk 58mm">
                                    <i class="fa-solid fa-print text-slate-600"></i>
                                </a>

                                <!-- Invoice Modal Button -->
                                <button type="button" onclick='openSnaprintInvoice(@json($invData))' class="btn btn-sm btn-outline-primary py-0.5 px-2 text-[11px]" title="Lihat Faktur Resmi">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-8 text-slate-400">
                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                <i class="fa-solid fa-hand-holding-dollar text-xl"></i>
                            </div>
                            <p class="font-bold text-slate-700 text-sm mb-0.5">Tidak Ada Data Piutang</p>
                            <p class="text-xs text-slate-400 mb-0">Semua pesanan client pada tab ini telah lunas atau belum ada transaksi DP.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Interactive Settlement Modal (Pelunasan Piutang) -->
    <div x-show="settleModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4"
         style="position: fixed; inset: 0; z-index: 999999 !important; display: none;"
         @keydown.window.escape="settleModalOpen = false">
        
        <div class="bg-white rounded-3 shadow-2xl border w-full max-w-md overflow-hidden my-auto" @click.away="settleModalOpen = false">
            <div class="bg-slate-900 text-white px-4 py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-hand-holding-dollar text-emerald-400 fs-5"></i>
                    <div>
                        <h6 class="fw-bold mb-0 text-white font-mono" x-text="'Pelunasan: #' + selectedTrx.invoice_number"></h6>
                        <span class="text-[11px] text-slate-300">Penerimaan Sisa Piutang Client</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" @click="settleModalOpen = false"></button>
            </div>

            <!-- Form Body -->
            <form :action="'/sales/' + selectedTrx.id + '/settle'" method="POST" class="p-4 space-y-3">
                @csrf
                
                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200 space-y-1.5 text-xs">
                    <div class="d-flex justify-content-between">
                        <span class="text-slate-500">Nama Client:</span>
                        <strong class="text-slate-900" x-text="selectedTrx.customer_name"></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-slate-500">Total Tagihan:</span>
                        <span class="font-mono font-bold text-slate-900" x-text="'Rp ' + Number(selectedTrx.total_price).toLocaleString('id-ID')"></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-slate-500">DP Telah Masuk:</span>
                        <span class="font-mono font-bold text-emerald-700" x-text="'Rp ' + Number(selectedTrx.paid_amount).toLocaleString('id-ID')"></span>
                    </div>
                    <div class="d-flex justify-content-between border-top border-slate-200 pt-1.5">
                        <span class="font-bold text-amber-800">Sisa Piutang:</span>
                        <span class="font-mono font-extrabold text-amber-700 text-sm" x-text="'Rp ' + Number(selectedTrx.remaining_amount).toLocaleString('id-ID')"></span>
                    </div>
                </div>

                <div>
                    <label class="form-label text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nominal Pelunasan (Rp)</label>
                    <input type="number" name="amount" :max="selectedTrx.remaining_amount" :value="selectedTrx.remaining_amount" required 
                        class="form-control font-mono font-bold text-base text-blue-900 bg-slate-50 border-slate-300 py-2">
                    <small class="text-slate-400 text-[11px]">Default terisi lunas penuh (bisa diubah jika pelunasan bertahap).</small>
                </div>

                <div>
                    <label class="form-label text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Metode Pembayaran</label>
                    <select name="payment_method" required class="form-select text-xs font-semibold py-2">
                        <option value="Cash">Tunai (Cash)</option>
                        <option value="Transfer">Transfer Bank</option>
                        <option value="QRIS">QRIS</option>
                    </select>
                </div>

                <div>
                    <label class="form-label text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Pelunasan (Opsional)</label>
                    <textarea name="keterangan" rows="2" placeholder="Misal: Pelunasan saat pengambilan banner oleh Pak Budi..." class="form-control text-xs"></textarea>
                </div>

                <div class="pt-2 d-flex gap-2">
                    <button type="button" @click="settleModalOpen = false" class="btn btn-secondary text-xs font-semibold flex-1 py-2">Batal</button>
                    <button type="submit" class="btn btn-success text-xs font-bold flex-1 py-2 shadow-sm d-inline-flex align-items-center justify-content-center gap-1.5">
                        <i class="fa-solid fa-check"></i> Konfirmasi Pelunasan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
