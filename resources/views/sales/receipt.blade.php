<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $transaction->invoice_number }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
            width: 80mm; /* Typical thermal printer width */
            margin: 0 auto;
        }
        @media print {
            body {
                width: auto;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            margin: 0;
        }
        .header p {
            margin: 5px 0;
            font-size: 12px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .meta-info {
            font-size: 12px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 5px 0;
            font-size: 12px;
        }
        .text-right {
            text-align: right;
        }
        .font-bold {
            font-weight: bold;
        }
        .totals {
            margin-top: 10px;
        }
        .totals p {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
        }
        .btn-print {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            background: #4f46e5;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-family: sans-serif;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>SNAPRINT ERP</h1>
        <p>{{ $transaction->branch->nama_cabang ?? 'Pusat' }}</p>
        <p>{{ $transaction->branch->alamat ?? 'Alamat tidak tersedia' }}</p>
    </div>

    <div class="meta-info">
        <div><strong>No:</strong> {{ $transaction->invoice_number }}</div>
        <div><strong>Tgl:</strong> {{ $transaction->created_at->format('d/m/Y H:i') }}</div>
        <div><strong>Kasir:</strong> {{ $transaction->user->username ?? 'Unknown' }}</div>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->transactionDetails as $detail)
            <tr>
                <td>{{ $detail->material->material_name ?? 'Unknown Item' }}<br><small>@ {{ number_format($detail->selling_price, 0, ',', '.') }}</small></td>
                <td>{{ $detail->qty_ordered }}</td>
                <td class="text-right">{{ number_format($detail->qty_ordered * $detail->selling_price, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="totals">
        <p><span>Subtotal:</span> <span>Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</span></p>
        <p><span>Pembayaran:</span> <span>{{ $transaction->payment_method }}</span></p>
        <p class="font-bold"><span>Total:</span> <span>Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</span></p>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <p>Terima kasih atas kunjungan Anda!</p>
        <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</p>
    </div>

    <a href="#" onclick="window.print()" class="btn-print no-print">🖨️ Cetak Struk</a>
    <a href="javascript:history.back()" class="btn-print no-print" style="background:#64748b; margin-top:10px;">Kembali</a>

</body>
</html>
