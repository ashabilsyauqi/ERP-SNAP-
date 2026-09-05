<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan Produk & Pemakaian Bahan - {{ $periodLabel }}</title>
    <style>
        @page {
            margin: 28px 30px 32px 30px;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 10.5px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .company-name {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 0 0 2px 0;
        }
        .report-title {
            font-size: 12.5px;
            font-weight: 700;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 4px 0;
        }
        .meta-table {
            width: 100%;
            margin-top: 4px;
            font-size: 9.5px;
            color: #475569;
        }
        .meta-table td {
            padding: 2px 4px;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .kpi-card {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 8px 10px;
            text-align: center;
        }
        .kpi-card .kpi-label {
            font-size: 9px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .kpi-card .kpi-val {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
        }
        .section-header {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            font-size: 10px;
            padding: 5px 8px;
            text-transform: uppercase;
            border-left: 3px solid #1e40af;
            margin-top: 10px;
            margin-bottom: 4px;
        }
        .section-header.material {
            border-left-color: #0d9488;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9.5px;
        }
        table.data-table th {
            background-color: #e2e8f0;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            text-align: left;
            font-size: 8.5px;
        }
        table.data-table th.amount, table.data-table td.amount {
            text-align: right;
        }
        table.data-table th.center, table.data-table td.center {
            text-align: center;
        }
        table.data-table td {
            padding: 4.5px 6px;
            border: 1px solid #e2e8f0;
        }
        table.data-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }
        table.data-table tr.total-row td {
            font-weight: 700;
            border-top: 2px solid #94a3b8;
            border-bottom: 2px solid #94a3b8;
            background-color: #f1f5f9;
        }
        .signatures {
            margin-top: 25px;
            width: 100%;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 9.5px;
        }
        .signature-line {
            width: 160px;
            margin: 45px auto 4px auto;
            border-bottom: 1px solid #475569;
        }
        .footer {
            margin-top: 15px;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-name">SNAPRINT ERP &amp; DIGITAL PRINTING</div>
        <div class="report-title">LAPORAN PENJUALAN PRODUK &amp; PEMAKAIAN BAHAN BAKU</div>
        <table class="meta-table">
            <tr>
                <td style="width: 50%; text-align: left;">
                    <strong>Cabang:</strong> {{ $branchName }}<br>
                    <strong>Periode:</strong> {{ $periodLabel }} ({{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }})
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>Tanggal Cetak:</strong> {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }} WIB<br>
                    <strong>Dicetak Oleh:</strong> {{ auth()->user()->name ?? 'System' }} ({{ ucfirst(auth()->user()->role ?? 'User') }})
                </td>
            </tr>
        </table>
    </div>

    <!-- KPI Ringkasan -->
    <table class="kpi-table">
        <tr>
            <td style="width: 25%; padding: 0 4px 0 0;">
                <div class="kpi-card">
                    <div class="kpi-label">Total Produk Terjual</div>
                    <div class="kpi-val">{{ number_format($totalItemsSold, 0, ',', '.') }} pcs</div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 4px;">
                <div class="kpi-card">
                    <div class="kpi-label">Total Omzet Penjualan</div>
                    <div class="kpi-val" style="color: #0284c7;">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 4px;">
                <div class="kpi-card">
                    <div class="kpi-label">Total Biaya Bahan Terpakai</div>
                    <div class="kpi-val" style="color: #d97706;">Rp {{ number_format($totalMaterialCost, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 0 0 4px;">
                <div class="kpi-card" style="border-color: #10b981; background-color: #f0fdf4;">
                    <div class="kpi-label" style="color: #15803d;">Gross Profit (Margin)</div>
                    <div class="kpi-val" style="color: #16a34a;">Rp {{ number_format($grossProfit, 0, ',', '.') }} ({{ number_format($grossMarginPct, 1) }}%)</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- 1. TABEL PRODUK TERJUAL -->
    <div class="section-header">1. Rekapitulasi Penjualan Produk &amp; Jasa</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="center">No</th>
                <th style="width: 32%;">Nama Produk / Layanan</th>
                <th style="width: 16%;">Kategori</th>
                <th style="width: 10%;" class="center">Qty / Vol</th>
                <th style="width: 14%;" class="amount">Total Omzet</th>
                <th style="width: 14%;" class="amount">Biaya HPP Bahan</th>
                <th style="width: 10%;" class="amount">Gross Margin</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productsSold as $index => $prod)
            @php
                $pMargin = $prod['total_omzet'] > 0 ? (($prod['gross_profit'] / $prod['total_omzet']) * 100) : 0;
            @endphp
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td><strong>{{ $prod['product_name'] }}</strong></td>
                <td>{{ $prod['category'] }}</td>
                <td class="center">
                    {{ number_format($prod['qty_sold'], 0, ',', '.') }} pcs
                    @if($prod['is_area_based'] && $prod['area_sold'] > 0)
                        <br><span style="font-size: 8px; color: #64748b;">({{ number_format($prod['area_sold'], 2, ',', '.') }} m²)</span>
                    @endif
                </td>
                <td class="amount">Rp {{ number_format($prod['total_omzet'], 0, ',', '.') }}</td>
                <td class="amount" style="color: #b45309;">Rp {{ number_format($prod['total_material_cost'], 0, ',', '.') }}</td>
                <td class="amount" style="color: #15803d;">
                    Rp {{ number_format($prod['gross_profit'], 0, ',', '.') }}<br>
                    <span style="font-size: 8px; color: #475569;">({{ number_format($pMargin, 1) }}%)</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="center" style="padding: 12px; color: #94a3b8;">Tidak ada data transaksi penjualan pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($productsSold) > 0)
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right; text-transform: uppercase;">TOTAL KESELURUHAN PRODUK:</td>
                <td class="center">{{ number_format($totalItemsSold, 0, ',', '.') }} pcs</td>
                <td class="amount">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</td>
                <td class="amount" style="color: #b45309;">Rp {{ number_format($totalMaterialCost, 0, ',', '.') }}</td>
                <td class="amount" style="color: #15803d;">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <!-- 2. TABEL PEMAKAIAN BAHAN BAKU -->
    <div class="section-header material">2. Rekapitulasi Pemakaian Bahan Baku &amp; Biaya Bahan</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="center">No</th>
                <th style="width: 30%;">Nama Bahan Baku</th>
                <th style="width: 14%;">Kategori</th>
                <th style="width: 10%;" class="center">Satuan</th>
                <th style="width: 10%;" class="center">Terpakai</th>
                <th style="width: 12%;" class="amount">Harga Beli Satuan</th>
                <th style="width: 12%;" class="amount">Total Biaya Bahan</th>
                <th style="width: 8%;" class="center">Sisa Stok</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotalMaterialCost = 0; @endphp
            @forelse($materialsUsed as $index => $mat)
            @php $grandTotalMaterialCost += $mat['total_material_cost']; @endphp
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td><strong>{{ $mat['material_name'] }}</strong></td>
                <td>{{ $mat['category'] }}</td>
                <td class="center">{{ $mat['unit'] }}</td>
                <td class="center font-bold">
                    {{ $mat['is_area'] ? number_format($mat['usage_qty'], 2, ',', '.') : number_format($mat['usage_qty'], 0, ',', '.') }}
                </td>
                <td class="amount">
                    Rp {{ number_format($mat['purchase_price'], 0, ',', '.') }}
                    @if($mat['click_charge'] > 0)
                        <br><span style="font-size: 8px; color: #64748b;">+Click Rp{{ number_format($mat['click_charge'], 0, ',', '.') }}</span>
                    @endif
                </td>
                <td class="amount" style="font-weight: 700; color: #b45309;">Rp {{ number_format($mat['total_material_cost'], 0, ',', '.') }}</td>
                <td class="center" style="{{ $mat['current_stock'] <= 5 ? 'color: #dc2626; font-weight: bold;' : '' }}">
                    {{ number_format($mat['current_stock'], 0, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="center" style="padding: 12px; color: #94a3b8;">Tidak ada bahan baku yang terpakai pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($materialsUsed) > 0)
        <tfoot>
            <tr class="total-row">
                <td colspan="6" style="text-align: right; text-transform: uppercase;">TOTAL BIAYA BAHAN BAKU TERPAKAI:</td>
                <td class="amount" style="color: #b45309; font-size: 10.5px;">Rp {{ number_format($grandTotalMaterialCost, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <!-- Tanda Tangan Validasi -->
    <table class="signatures">
        <tr>
            <td>
                Dibuat Oleh,<br>
                <strong>Staff Kasir / Produksi</strong>
                <div class="signature-line"></div>
                ({{ auth()->user()->name ?? 'Petugas Toko' }})
            </td>
            <td>
                Diperiksa &amp; Disetujui Oleh,<br>
                <strong>Store Manager / Owner</strong>
                <div class="signature-line"></div>
                ( .................................................. )
            </td>
        </tr>
    </table>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh Sistem Snaprint ERP pada {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i:s') }} WIB. Berkas resmi tersimpan dalam sistem arsip digital.
    </div>

</body>
</html>
