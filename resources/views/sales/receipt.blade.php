<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk POS 58mm - {{ $transaction->invoice_number }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Courier New', Courier, 'Lucida Console', Monaco, monospace;
            font-size: 11px;
            line-height: 1.25;
            color: #000000;
            background: #ffffff;
            width: 58mm;
            max-width: 58mm;
            padding: 6px 4px 15px 4px;
            margin: 0 auto;
        }
        @media print {
            body {
                width: 58mm;
                padding: 4px 2px;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        
        .header {
            text-align: center;
            margin-bottom: 6px;
        }
        .header img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 4px;
            display: inline-block;
        }
        .header h1 {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin: 1px 0;
        }
        .header p {
            font-size: 10px;
            color: #111;
            margin: 1px 0;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .divider-double {
            border-top: 1px double #000;
            margin: 6px 0;
        }

        .meta-table {
            width: 100%;
            font-size: 10px;
            margin-bottom: 4px;
        }
        .meta-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .items-table th {
            border-bottom: 1px dashed #000;
            padding: 3px 0;
            font-size: 10px;
        }
        .items-table td {
            padding: 2.5px 0;
            vertical-align: top;
        }

        .item-name {
            font-weight: bold;
            word-break: break-word;
        }
        .item-meta {
            font-size: 9.5px;
            color: #222;
        }

        .totals-table {
            width: 100%;
            font-size: 11px;
        }
        .totals-table td {
            padding: 1.5px 0;
        }

        .stamp-lunas {
            display: inline-block;
            border: 1px solid #000;
            padding: 2px 6px;
            font-weight: 900;
            font-size: 10.5px;
            letter-spacing: 0.5px;
            margin: 4px 0;
        }

        .footer {
            text-align: center;
            margin-top: 8px;
            font-size: 9.5px;
            line-height: 1.3;
        }

        .action-buttons {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .btn-action {
            display: block;
            width: 100%;
            padding: 8px;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            font-family: sans-serif;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            border: none;
        }
        .btn-print {
            background: #2563EB;
            color: #ffffff;
        }
        .btn-back {
            background: #64748B;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <!-- Header & Logo -->
    <div class="header">
        <img src="{{ asset('images/logosnaprint.jpeg') }}" alt="Snaprint">
        <h1>SNAPRINT</h1>
        <p>Digital Printing & Adv.</p>
        <p>Cabang: <strong>{{ $transaction->branch->nama_cabang ?? 'Pusat' }}</strong></p>
        <p>{{ $transaction->branch->alamat ?? 'Jl. Margonda Raya No. 45' }}</p>
    </div>

    <div class="divider"></div>

    <!-- Meta Info -->
    <table class="meta-table">
        <tr>
            <td style="width: 35%;">No. Inv</td>
            <td>: <strong>{{ $transaction->invoice_number }}</strong></td>
        </tr>
        <tr>
            <td>Waktu</td>
            <td>: {{ $transaction->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Kasir</td>
            <td>: {{ $transaction->user->full_name ?: ($transaction->user->username ?? 'Kasir') }}</td>
        </tr>
        @if($transaction->customer_name)
        <tr>
            <td>Client</td>
            <td>: <strong>{{ $transaction->customer_name }}</strong></td>
        </tr>
        @endif
        @if($transaction->customer_phone)
        <tr>
            <td>WhatsApp</td>
            <td>: {{ $transaction->customer_phone }}</td>
        </tr>
        @endif
        @if($transaction->due_date)
        <tr>
            <td>Deadline</td>
            <td>: <strong>{{ $transaction->due_date->format('d/m/Y') }}</strong></td>
        </tr>
        @endif
    </table>

    <div class="divider"></div>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-left">Item / Qty</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->transactionDetails as $detail)
            <tr>
                <td class="text-left">
                    <div class="item-name">{{ $detail->material->material_name ?? 'Bahan Cetak' }}</div>
                    <div class="item-meta">{{ $detail->qty_ordered }} x Rp {{ number_format($detail->selling_price, 0, ',', '.') }}</div>
                </td>
                <td class="text-right fw-bold" style="white-space: nowrap;">
                    Rp {{ number_format($detail->qty_ordered * $detail->selling_price, 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <!-- Totals Table -->
    <table class="totals-table">
        <tr>
            <td>Total Tagihan</td>
            <td class="text-right fw-bold">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</td>
        </tr>
        
        @if($transaction->payment_status === 'PARTIAL' || $transaction->remaining_amount > 0)
        <tr>
            <td>DP Masuk ({{ $transaction->payment_method }})</td>
            <td class="text-right">Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</td>
        </tr>
        <tr style="font-weight: bold; border-top: 1px dashed #000;">
            <td style="padding-top: 2px;">SISA PIUTANG</td>
            <td class="text-right" style="padding-top: 2px;">Rp {{ number_format($transaction->remaining_amount, 0, ',', '.') }}</td>
        </tr>
        @else
        <tr>
            <td>Bayar ({{ $transaction->payment_method }})</td>
            <td class="text-right">Rp {{ number_format($transaction->paid_amount ?: $transaction->total_price, 0, ',', '.') }}</td>
        </tr>
        <tr style="font-weight: bold;">
            <td>STATUS</td>
            <td class="text-right">LUNAS</td>
        </tr>
        @endif
    </table>

    @if($transaction->production_notes)
    <div class="divider"></div>
    <div style="font-size: 9.5px; margin: 2px 0;">
        <strong>Catatan Produksi:</strong><br>
        {{ $transaction->production_notes }}
    </div>
    @endif

    <div class="divider"></div>

    <!-- Status & Footer -->
    <div class="text-center">
        @if($transaction->payment_status === 'PARTIAL' || $transaction->remaining_amount > 0)
            <div class="stamp-lunas">*** BUKTI DP / UANG MUKA ***</div>
            <div style="font-size: 9px; color: #333;">Sisa tagihan wajib dilunasi saat pengambilan.</div>
        @else
            <div class="stamp-lunas">*** LUNAS (PAID) ***</div>
        @endif
    </div>

    <div class="footer">
        <p>Terima kasih atas pesanan Anda!</p>
        <p>Snaprint Digital Printing ERP</p>
    </div>

    <!-- Screen Buttons (Hidden when printing) -->
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="btn-action btn-print">
            🖨️ Cetak Struk 58mm
        </button>
        <a href="{{ route('pos.index') }}" class="btn-action btn-back">
            &larr; Kembali ke Kasir (POS)
        </a>
    </div>

    <script>
        // Auto trigger print dialog when opened
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
