<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur Penjualan - {{ $transaction->invoice_number }} - Snaprint</title>
    <!-- Tailwind CSS CDN for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- html2pdf.js for 1-click true PDF download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .invoice-sheet { box-shadow: none !important; border: none !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-200/70 text-slate-800 font-sans antialiased min-h-screen py-6 sm:py-10 px-3 sm:px-6">

    <!-- Top Action Floating Bar (No Print) -->
    <div class="max-w-4xl mx-auto mb-4 flex items-center justify-between no-print">
        <a href="javascript:window.close()" class="text-xs font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-1.5 bg-white py-2 px-3.5 rounded-xl border border-slate-300 shadow-sm transition">
            <i class="fa-solid fa-arrow-left"></i> Kembali / Tutup
        </a>
        <div class="flex items-center gap-2">
            <button type="button" onclick="downloadPDF()" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded-xl shadow-sm flex items-center gap-1.5 transition cursor-pointer">
                <i class="fa-solid fa-file-pdf"></i> Unduh Berkas PDF (A4)
            </button>
            <button type="button" onclick="window.print()" class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold py-2 px-4 rounded-xl shadow-sm flex items-center gap-1.5 transition cursor-pointer">
                <i class="fa-solid fa-print"></i> Cetak Dokumen
            </button>
        </div>
    </div>

    <!-- Main Printable Invoice Container (Paper Sheet Layout) -->
    <div id="invoice-printable" class="invoice-sheet max-w-4xl mx-auto bg-white rounded-2xl shadow-xl border border-slate-300/80 p-8 sm:p-12 relative overflow-hidden">
        
        @php
            $isPaid = $transaction->isPaid();
            $isPartial = $transaction->isPartial() || ((float)$transaction->paid_amount > 0 && (float)$transaction->remaining_amount > 0);
            $isUnpaid = !$isPaid && !$isPartial;
        @endphp

        <!-- Watermark Stamp Background -->
        @if($isUnpaid || $isPartial)
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center overflow-hidden opacity-[0.06] select-none" style="z-index: 0;">
                <div class="text-[130px] font-black tracking-widest text-red-600 border-[10px] border-red-600 rounded-3xl px-14 py-4 -rotate-12 uppercase font-mono">
                    UNPAID
                </div>
            </div>
        @elseif($isPaid)
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center overflow-hidden opacity-[0.06] select-none" style="z-index: 0;">
                <div class="text-[130px] font-black tracking-widest text-emerald-600 border-[10px] border-emerald-600 rounded-3xl px-14 py-4 -rotate-12 uppercase font-mono">
                    PAID
                </div>
            </div>
        @endif

        <!-- Header & Logo -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-6 border-b-2 border-blue-900 gap-4 relative z-10">
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('images/logosnaprint.jpeg') }}" alt="Snaprint Logo" class="w-14 h-14 rounded-full object-cover border border-slate-200 shadow-sm" onerror="this.src='https://ui-avatars.com/api/?name=Snaprint&background=1e3a8a&color=fff'">
                <div>
                    <h1 class="text-2xl font-black text-blue-900 tracking-tight leading-none mb-1">SNAPRINT</h1>
                    <p class="text-xs text-slate-500 font-medium mb-0">Digital Printing & Advertising Solutions</p>
                    <p class="text-xs text-slate-600 font-semibold mt-0.5">Cabang: <span class="text-blue-950">{{ $transaction->branch->nama_cabang ?? 'Pusat' }}</span></p>
                </div>
            </div>
            
            <div class="text-left sm:text-right w-full sm:w-auto">
                @if($isUnpaid)
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 border-2 border-red-600 text-red-700 font-black text-xs uppercase tracking-widest rounded-lg mb-1 shadow-sm whitespace-nowrap">
                        <i class="fa-solid fa-circle-xmark text-red-600"></i> UNPAID (BELUM BAYAR)
                    </div>
                @elseif($isPartial)
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 border-2 border-red-600 text-red-700 font-black text-xs uppercase tracking-widest rounded-lg mb-1 shadow-sm whitespace-nowrap">
                        <i class="fa-solid fa-clock-rotate-left text-red-600"></i> UNPAID (SISA PIUTANG: Rp {{ number_format($transaction->remaining_amount, 0, ',', '.') }})
                    </div>
                @else
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 border-2 border-emerald-600 text-emerald-700 font-extrabold text-xs uppercase tracking-wider rounded-lg mb-1 shadow-sm whitespace-nowrap">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i> LUNAS / PAID
                    </div>
                @endif

                <div class="text-lg font-black text-slate-900 mt-1">FAKTUR / INVOICE {{ $isPartial ? '& SPK' : '' }}</div>
                <div class="text-xs font-mono font-bold text-slate-500">No: {{ $transaction->invoice_number }}</div>
            </div>
        </div>

        <!-- Customer & Order Information Box -->
        <div class="bg-slate-50 rounded-xl border border-slate-200/80 p-4 my-6 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <span class="text-slate-400 font-bold uppercase tracking-wider block text-[10px]">Pelanggan / Customer</span>
                    <strong class="text-slate-900 text-sm block">{{ $transaction->customer_name ?: 'Pelanggan Umum' }}</strong>
                    @if($transaction->customer_phone)
                        <span class="text-slate-600 font-mono"><i class="fa-brands fa-whatsapp text-emerald-600 me-1"></i>{{ $transaction->customer_phone }}</span>
                    @endif
                </div>
                <div class="sm:text-right">
                    <span class="text-slate-400 font-bold uppercase tracking-wider block text-[10px]">Detail Transaksi</span>
                    <span class="text-slate-700 block">Tanggal: <strong>{{ $transaction->created_at->format('d M Y H:i') }}</strong></span>
                    <span class="text-slate-700 block">Metode Bayar: <strong>{{ $transaction->payment_method }}</strong></span>
                    <span class="text-slate-700 block">Petugas: <strong>{{ $transaction->user->full_name ?? ($transaction->user->username ?? 'Kasir') }}</strong></span>
                </div>
            </div>

            @if($transaction->due_date || $transaction->production_notes)
                <div class="mt-3 pt-3 border-t border-slate-200/80 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @if($transaction->due_date)
                        <div>
                            <span class="text-slate-500 font-semibold">Estimasi Selesai (Deadline):</span>
                            <strong class="text-rose-700 block">{{ \Carbon\Carbon::parse($transaction->due_date)->format('d F Y') }}</strong>
                        </div>
                    @endif
                    @if($transaction->production_notes)
                        <div>
                            <span class="text-slate-500 font-semibold">Catatan SPK / Finishing:</span>
                            <p class="text-slate-800 italic mb-0">{{ $transaction->production_notes }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Items Table -->
        <div class="overflow-x-auto mb-6">
            <table class="w-full border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/80 text-slate-700 border-y border-slate-200 text-left font-bold">
                        <th class="py-2.5 px-3 text-center w-10">No</th>
                        <th class="py-2.5 px-3">Deskripsi Item / Pesanan</th>
                        <th class="py-2.5 px-3 text-center w-20">Qty</th>
                        <th class="py-2.5 px-3 text-right w-32 whitespace-nowrap">Harga Satuan</th>
                        <th class="py-2.5 px-3 text-right w-36 whitespace-nowrap">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($transaction->transactionDetails as $idx => $detail)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3 px-3 text-center text-slate-500 font-medium">{{ $idx + 1 }}</td>
                            <td class="py-3 px-3">
                                <div class="font-bold text-slate-900 text-[13px]">{{ $detail->material->material_name ?? 'Item Cetak' }}</div>
                                @if($detail->dimension_text)
                                    <span class="inline-block bg-blue-50 text-blue-800 border border-blue-200 text-[10px] font-bold px-1.5 py-0.5 rounded mt-0.5">
                                        {{ $detail->dimension_text }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-slate-800">{{ $detail->qty_ordered }}</td>
                            <td class="py-3 px-3 text-right font-mono text-slate-700 whitespace-nowrap">Rp {{ number_format($detail->selling_price, 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                Rp {{ number_format($detail->qty_ordered * $detail->selling_price, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-slate-400">Rincian item tidak tersedia</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Totals & Payment Breakdown (Clean Inline Layout) -->
        <div class="border-t-2 border-slate-200 pt-5 flex flex-col sm:flex-row justify-between items-start gap-6">
            <div class="text-xs text-slate-500 flex-1 min-w-0">
                @if($transaction->negotiation_notes)
                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800">
                        <span class="font-bold block text-[11px] uppercase tracking-wider mb-1"><i class="fa-solid fa-handshake me-1"></i> Catatan Negosiasi:</span>
                        {{ $transaction->negotiation_notes }}
                    </div>
                @endif
                <div class="mt-2 text-[11px] text-slate-400 space-y-0.5">
                    <div>Metode Pembayaran: <strong class="text-slate-700">{{ $transaction->payment_method ?: 'Cash' }}</strong></div>
                    <div>Petugas Kasir: <strong class="text-slate-700">{{ $transaction->user->full_name ?? ($transaction->user->username ?? 'Kasir') }}</strong></div>
                </div>
            </div>

            <!-- Total Box with Guaranteed Inline Non-Wrapping Currency -->
            <div class="w-full sm:w-96 md:w-[440px] text-xs space-y-2.5 flex-shrink-0">
                @if($transaction->original_price && $transaction->original_price > $transaction->total_price)
                    <div class="flex justify-between items-center gap-4 text-slate-500">
                        <span class="whitespace-nowrap">Total Akumulasi Asli:</span>
                        <span class="font-mono line-through whitespace-nowrap text-right">Rp {{ number_format($transaction->original_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center gap-4 text-emerald-700 font-bold">
                        <span class="whitespace-nowrap">Potongan Negosiasi:</span>
                        <span class="font-mono whitespace-nowrap text-right">- Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                    </div>
                @endif

                <div class="flex justify-between items-center gap-4 text-sm font-black text-blue-900 pt-2 border-t-2 border-slate-200">
                    <span class="whitespace-nowrap">Total Tagihan:</span>
                    <span class="font-mono text-base whitespace-nowrap text-right font-black">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</span>
                </div>

                @if($isPartial)
                    <div class="flex justify-between items-center gap-4 text-emerald-700 font-bold pt-1">
                        <span class="whitespace-nowrap">Uang Muka (DP) Diterima:</span>
                        <span class="font-mono whitespace-nowrap text-right">Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center gap-4 text-red-800 font-black bg-red-50 p-3 rounded-xl border-2 border-red-400 shadow-sm">
                        <span class="flex items-center gap-1.5 whitespace-nowrap"><i class="fa-solid fa-circle-exclamation text-red-600"></i> Sisa Piutang (UNPAID):</span>
                        <span class="font-mono text-base text-red-700 whitespace-nowrap text-right font-black">Rp {{ number_format($transaction->remaining_amount, 0, ',', '.') }}</span>
                    </div>
                @elseif($isUnpaid)
                    <div class="flex justify-between items-center gap-4 text-red-800 font-black bg-red-50 p-3 rounded-xl border-2 border-red-400 shadow-sm">
                        <span class="flex items-center gap-1.5 whitespace-nowrap"><i class="fa-solid fa-circle-xmark text-red-600"></i> Sisa Pembayaran (UNPAID):</span>
                        <span class="font-mono text-base text-red-700 whitespace-nowrap text-right font-black">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</span>
                    </div>
                @else
                    <div class="flex justify-between items-center gap-4 text-slate-600 font-semibold pt-1">
                        <span class="whitespace-nowrap">Jumlah Dibayar:</span>
                        <span class="font-mono whitespace-nowrap text-right">Rp {{ number_format($transaction->paid_amount ?: $transaction->total_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center gap-4 text-emerald-800 font-black bg-emerald-50 p-3 rounded-xl border-2 border-emerald-400 shadow-sm">
                        <span class="flex items-center gap-1.5 whitespace-nowrap"><i class="fa-solid fa-circle-check text-emerald-600"></i> Status Pembayaran:</span>
                        <span class="font-mono text-emerald-700 uppercase whitespace-nowrap text-right font-black">LUNAS (PAID)</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="mt-8 pt-6 border-t border-slate-200 text-center text-[11px] text-slate-400 space-y-1">
            <p class="mb-0 font-medium">Terima kasih atas kepercayaan Anda mencetak di <strong>Snaprint Digital Printing</strong>!</p>
            <p class="mb-0 font-mono">Dokumen resmi ini digenerate otomatis oleh Snaprint ERP System &bull; <a href="https://mysnaprint.com" target="_blank" class="text-blue-600 underline">mysnaprint.com</a></p>
        </div>
    </div>

    <!-- PDF Download Script -->
    <script>
        function downloadPDF() {
            const element = document.getElementById('invoice-printable');
            const opt = {
                margin:       [10, 10, 10, 10],
                filename:     'Faktur_{{ $transaction->invoice_number }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
