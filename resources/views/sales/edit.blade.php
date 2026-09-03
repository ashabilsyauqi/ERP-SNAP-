@extends('layouts.app')

@section('title', 'Edit Transaksi #' . $transaction->invoice_number)
@section('page-title', 'Edit Transaksi Penjualan (Super Admin)')

@section('action-buttons')
<a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 rounded-xl text-xs font-bold px-3 py-1.5">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Riwayat
</a>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-4">
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-xl shadow-sm text-xs font-semibold" role="alert">
            <i class="fa-solid fa-circle-exclamation me-1.5"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('sales.update', $transaction->id) }}" method="POST" id="edit-transaction-form">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Header Card -->
            <div class="p-4 bg-slate-900 text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white d-flex align-items-center justify-content-center flex-shrink-0 font-bold">
                        <i class="fa-solid fa-file-invoice text-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-extrabold tracking-tight font-mono">{{ $transaction->invoice_number }}</h5>
                        <p class="text-xs text-slate-400 mb-0">Dibuat: {{ $transaction->created_at->format('d M Y, H:i') }} &bull; Kasir: {{ $transaction->user->full_name ?? $transaction->user->username }}</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-amber-500 text-white text-xs px-2.5 py-1 font-bold rounded-lg uppercase">
                        <i class="fa-solid fa-crown me-1"></i> Mode Super Admin
                    </span>
                </div>
            </div>

            <!-- Form Content -->
            <div class="p-5 space-y-5">

                <!-- 1. Informasi Pelanggan & Cabang -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Pelanggan</label>
                        <div class="input-group">
                            <span class="input-group-text bg-slate-50 border-slate-300 text-slate-400 text-xs"><i class="fa-solid fa-user"></i></span>
                            <input type="text" name="customer_name" class="form-control form-control-sm text-xs font-semibold" value="{{ old('customer_name', $transaction->customer_name) }}" placeholder="Nama Pelanggan">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nomor WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text bg-slate-50 border-slate-300 text-slate-400 text-xs"><i class="fa-brands fa-whatsapp text-emerald-600"></i></span>
                            <input type="text" name="customer_phone" class="form-control form-control-sm text-xs font-semibold font-mono" value="{{ old('customer_phone', $transaction->customer_phone) }}" placeholder="Contoh: 081234567890">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Cabang Toko</label>
                        <select name="branch_id" class="form-select form-select-sm text-xs font-semibold">
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ $transaction->branch_id == $b->id ? 'selected' : '' }}>
                                    {{ $b->nama_cabang }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr class="border-slate-200">

                <!-- 2. Status & Metode Pembayaran -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Metode Pembayaran</label>
                        <select name="payment_method" class="form-select form-select-sm text-xs font-bold">
                            <option value="Cash" {{ $transaction->payment_method === 'Cash' ? 'selected' : '' }}>💵 Cash (Tunai)</option>
                            <option value="Transfer" {{ $transaction->payment_method === 'Transfer' ? 'selected' : '' }}>🏦 Transfer Bank</option>
                            <option value="QRIS" {{ $transaction->payment_method === 'QRIS' ? 'selected' : '' }}>📱 QRIS</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status Pembayaran</label>
                        <select name="payment_status" class="form-select form-select-sm text-xs font-bold" id="payment_status_select">
                            <option value="PAID" {{ $transaction->payment_status === 'PAID' ? 'selected' : '' }}>🟢 PAID (Lunas)</option>
                            <option value="PARTIAL" {{ $transaction->payment_status === 'PARTIAL' ? 'selected' : '' }}>🟡 PARTIAL (DP / Cicil)</option>
                            <option value="UNPAID" {{ $transaction->payment_status === 'UNPAID' ? 'selected' : '' }}>🔴 UNPAID (Belum Bayar)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status Pesanan</label>
                        <select name="order_status" class="form-select form-select-sm text-xs font-bold">
                            <option value="completed" {{ $transaction->order_status === 'completed' ? 'selected' : '' }}>✅ Selesai (Completed)</option>
                            <option value="in_production" {{ $transaction->order_status === 'in_production' ? 'selected' : '' }}>⚙️ Sedang Diproduksi</option>
                            <option value="pending" {{ $transaction->order_status === 'pending' ? 'selected' : '' }}>⏳ Menunggu (Pending)</option>
                            <option value="draft" {{ $transaction->order_status === 'draft' ? 'selected' : '' }}>📝 Draft SPK</option>
                            <option value="cancelled" {{ $transaction->order_status === 'cancelled' ? 'selected' : '' }}>❌ Dibatalkan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Target Selesai (Due Date)</label>
                        <input type="date" name="due_date" class="form-control form-control-sm text-xs font-semibold" value="{{ $transaction->due_date ? $transaction->due_date->format('Y-m-d') : '' }}">
                    </div>
                </div>

                <!-- 3. Rincian Item Transaksi -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Rincian Item & Qty Bahan
                    </label>
                    <div class="table-responsive border rounded-xl overflow-hidden shadow-xs">
                        <table class="table table-sm table-hover align-middle mb-0 text-xs" id="items-table">
                            <thead class="bg-slate-100 text-slate-700 border-bottom">
                                <tr>
                                    <th class="ps-3 py-2" style="width: 40px;">No</th>
                                    <th class="py-2">Deskripsi Produk / Bahan</th>
                                    <th class="py-2 text-center" style="width: 120px;">Qty Pesanan</th>
                                    <th class="py-2 text-end" style="width: 160px;">Harga Satuan (Rp)</th>
                                    <th class="pe-3 py-2 text-end" style="width: 170px;">Subtotal (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transaction->transactionDetails as $idx => $d)
                                <tr>
                                    <td class="ps-3 text-center text-slate-500 font-mono">{{ $idx + 1 }}</td>
                                    <td>
                                        <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $d->id }}">
                                        <span class="font-bold text-slate-800">{{ $d->material->material_name ?? 'Item #' . $d->id }}</span>
                                        @if($d->dimension_text)
                                            <div class="text-[10px] text-blue-700 font-semibold mt-0.5">[{{ $d->dimension_text }}]</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <input type="number" name="items[{{ $idx }}][qty]" class="form-control form-control-sm text-center font-bold text-xs item-qty" value="{{ $d->qty_ordered }}" min="1" required data-index="{{ $idx }}">
                                    </td>
                                    <td class="text-end">
                                        <input type="number" name="items[{{ $idx }}][selling_price]" class="form-control form-control-sm text-end font-mono font-bold text-xs item-price" value="{{ (int)$d->selling_price }}" min="0" required data-index="{{ $idx }}">
                                    </td>
                                    <td class="pe-3 text-end font-mono font-bold text-blue-900 item-subtotal" id="subtotal-{{ $idx }}">
                                        Rp {{ number_format($d->qty_ordered * $d->selling_price, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4. Kalkulasi Total & Nominal Pembayaran -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Produksi & Keterangan</label>
                        <textarea name="production_notes" class="form-control text-xs" rows="3" placeholder="Catatan finishing, laminasi, atau catatan transaksi...">{{ old('production_notes', $transaction->production_notes) }}</textarea>
                    </div>

                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-2.5">
                        <div class="d-flex justify-content-between text-xs font-semibold text-slate-600">
                            <span>Total Nilai Tagihan:</span>
                            <span class="font-mono font-bold text-base text-blue-900" id="display-total-price">
                                Rp {{ number_format($transaction->total_price, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center text-xs font-semibold text-slate-700">
                            <span>Nominal Terbayar (DP / Pelunasan):</span>
                            <div class="w-48">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-xs font-bold">Rp</span>
                                    <input type="number" name="paid_amount" id="paid_amount_input" class="form-control form-control-sm text-end font-mono font-bold text-xs text-emerald-700" value="{{ (int)$transaction->paid_amount }}" min="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between text-xs font-semibold text-rose-700 pt-2 border-top">
                            <span>Sisa Piutang (Remaining):</span>
                            <span class="font-mono font-bold text-sm" id="display-remaining-amount">
                                Rp {{ number_format($transaction->remaining_amount, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Card Footer -->
            <div class="p-4 bg-slate-100 border-t d-flex justify-content-between align-items-center">
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-secondary rounded-xl text-xs font-semibold px-4 py-2">
                    Batal
                </a>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary rounded-xl text-xs font-bold px-4 py-2 shadow-sm d-inline-flex align-items-center gap-1.5" style="background-color: #1e40af; border-color: #1e40af;">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Transaksi
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const qtyInputs = document.querySelectorAll('.item-qty');
    const priceInputs = document.querySelectorAll('.item-price');
    const paidInput = document.getElementById('paid_amount_input');
    const displayTotal = document.getElementById('display-total-price');
    const displayRemaining = document.getElementById('display-remaining-amount');
    const statusSelect = document.getElementById('payment_status_select');

    function recalculate() {
        let grandTotal = 0;
        qtyInputs.forEach((qtyInput) => {
            const idx = qtyInput.getAttribute('data-index');
            const priceInput = document.querySelector(`.item-price[data-index="${idx}"]`);
            const subtotalElem = document.getElementById(`subtotal-${idx}`);

            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const subtotal = qty * price;

            grandTotal += subtotal;
            if (subtotalElem) {
                subtotalElem.innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
            }
        });

        displayTotal.innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');

        const paid = parseFloat(paidInput.value) || 0;
        const remaining = Math.max(0, grandTotal - paid);
        displayRemaining.innerText = 'Rp ' + remaining.toLocaleString('id-ID');

        // Auto suggest payment status if not manually altered
        if (paid >= grandTotal && grandTotal > 0) {
            statusSelect.value = 'PAID';
        } else if (paid > 0 && paid < grandTotal) {
            statusSelect.value = 'PARTIAL';
        } else if (paid === 0) {
            statusSelect.value = 'UNPAID';
        }
    }

    qtyInputs.forEach(input => input.addEventListener('input', recalculate));
    priceInputs.forEach(input => input.addEventListener('input', recalculate));
    paidInput.addEventListener('input', recalculate);
});
</script>
@endsection
