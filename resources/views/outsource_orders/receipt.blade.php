<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pesanan - {{ $order->order_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace;
        }
        body {
            background-color: #f1f5f9;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .receipt-card {
            background: #fff;
            width: 320px;
            padding: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #475569;
            margin: 8px 0;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 4px;
        }
        .title {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 10.5px;
            color: #334155;
            margin-top: 2px;
        }
        .btn-print {
            display: block;
            width: 100%;
            background: #2563eb;
            color: white;
            border: none;
            padding: 8px;
            font-weight: bold;
            border-radius: 6px;
            margin-top: 12px;
            cursor: pointer;
            font-size: 12px;
        }
        @media print {
            body { background: white; padding: 0; }
            .receipt-card { width: 100%; max-width: 80mm; box-shadow: none; border-radius: 0; padding: 0; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt-card">
    <div class="text-center">
        <div class="title">{{ $order->branch->nama_cabang ?? 'PERCETAKAN & DIGITAL PRINT' }}</div>
        <div class="subtitle">{{ $order->branch->alamat ?? 'Jl. Grand Wisata Bekasi' }}</div>
        <div class="subtitle">Telp: {{ $order->branch->telepon ?? '021-88005678' }}</div>
    </div>

    <div class="divider"></div>

    <div style="font-size: 11px; line-height: 1.4;">
        <div><strong>TANDA TERIMA PESANAN</strong></div>
        <div>No. Order: <strong>#{{ $order->order_number }}</strong></div>
        <div>Tanggal  : {{ $order->created_at->format('d/m/Y H:i') }}</div>
        <div>Kasir    : {{ $order->user->full_name ?? ($order->user->username ?? 'Kasir') }}</div>
        <div>Customer : <strong>{{ $order->customer_name }}</strong></div>
        @if($order->customer_phone)
            <div>Telepon  : {{ $order->customer_phone }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <div style="font-size: 12px;">
        <div class="font-bold">{{ $order->job_title }}</div>
        @if($order->description)
            <div style="font-size: 10px; color: #475569; margin-bottom: 2px;">{{ $order->description }}</div>
        @endif
        <div class="item-row">
            <span>{{ $order->qty }} {{ $order->unit }} x @ Rp {{ number_format($order->customer_price / $order->qty, 0, ',', '.') }}</span>
            <span class="font-bold">Rp {{ number_format($order->customer_price, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="divider"></div>

    <div style="font-size: 12px;">
        <div class="item-row">
            <span>Total Tagihan:</span>
            <span class="font-bold">Rp {{ number_format($order->customer_price, 0, ',', '.') }}</span>
        </div>
        <div class="item-row">
            <span>Metode Bayar:</span>
            <span>{{ $order->payment_method }}</span>
        </div>
        <div class="item-row">
            <span>Dibayar ({{ $order->payment_status }}):</span>
            <span class="font-bold">Rp {{ number_format($order->paid_amount, 0, ',', '.') }}</span>
        </div>
        @if($order->remaining_amount > 0)
            <div class="item-row" style="color: #b45309; font-weight: bold;">
                <span>Sisa Piutang:</span>
                <span>Rp {{ number_format($order->remaining_amount, 0, ',', '.') }}</span>
            </div>
        @else
            <div class="item-row" style="color: #059669; font-weight: bold;">
                <span>Status:</span>
                <span>LUNAS</span>
            </div>
        @endif
    </div>

    <div class="divider"></div>

    <div class="text-center" style="font-size: 10px; color: #64748b; line-height: 1.3;">
        <div>Simpan struk ini sebagai bukti pengambilan barang.</div>
        <div style="margin-top: 4px;">Terima kasih atas kepercayaan Anda!</div>
    </div>

    <button onclick="window.print()" class="btn-print">CETAK STRUK (PRINT)</button>
</div>

</body>
</html>
