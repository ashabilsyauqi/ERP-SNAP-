<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; color: #1e293b; }
        .title-main { font-size: 16pt; font-weight: bold; color: #1e1b4b; }
        .title-sub { font-size: 12pt; font-weight: bold; color: #4338ca; }
        .meta-label { font-weight: bold; color: #475569; }
        .meta-value { color: #0f172a; }
        
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 10pt; }
        th { background-color: #1e293b; color: #ffffff; font-weight: bold; text-align: center; vertical-align: middle; }
        
        .th-product { background-color: #312e81; color: #ffffff; }
        .th-tx { background-color: #0f172a; color: #ffffff; }
        
        .kpi-table th { background-color: #f1f5f9; color: #334155; font-size: 9pt; text-align: left; }
        .kpi-table td { font-size: 11pt; font-weight: bold; color: #0f172a; }
        .kpi-highlight { background-color: #ecfdf5; color: #065f46; font-size: 12pt; font-weight: bold; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .bg-total { background-color: #f8fafc; font-weight: bold; }
        
        .num { mso-number-format: "\#\,\#\#0"; text-align: right; }
        .currency { mso-number-format: "\"Rp \"\#\,\#\#0"; text-align: right; }
        .text { mso-number-format: "\@"; }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table>
        <tr>
            <td colspan="8" class="title-main" style="border: none;">SNAPRINT DIGITAL PRINTING</td>
        </tr>
        <tr>
            <td colspan="8" class="title-sub" style="border: none;">LAPORAN REKAPITULASI KINERJA PENJUALAN MESIN</td>
        </tr>
        <tr>
            <td colspan="8" style="border: none; height: 10px;"></td>
        </tr>
        <tr>
            <td style="border: none; font-weight: bold; width: 140px;">Label Mesin:</td>
            <td colspan="3" style="border: none;"><strong>{{ $selectedTag === 'all' ? 'Semua Mesin Berlabel' : $selectedTag }}</strong></td>
            <td style="border: none; font-weight: bold; width: 120px;">Periode:</td>
            <td colspan="3" style="border: none;"><strong>{{ $periodLabel }}</strong></td>
        </tr>
        <tr>
            <td style="border: none; font-weight: bold;">Cabang:</td>
            <td colspan="3" style="border: none;"><strong>{{ $branchName }}</strong></td>
            <td style="border: none; font-weight: bold;">Tanggal Unduh:</td>
            <td colspan="3" style="border: none;">{{ now()->translatedFormat('d F Y H:i') }}</td>
        </tr>
    </table>

    <br/>

    <!-- Summary KPI Table -->
    <table>
        <thead>
            <tr>
                <th colspan="4" style="background-color: #1e293b; color: #ffffff; text-align: left;">RINGKASAN TOTAL KINERJA FINANSIAL MESIN</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="background-color: #f8fafc; font-weight: bold; width: 25%;">Total Omzet Mesin</td>
                <td class="currency" style="font-weight: bold; width: 25%;">{{ $totalOmzet }}</td>
                <td style="background-color: #f8fafc; font-weight: bold; width: 25%;">Total Volume Cetak</td>
                <td class="num" style="font-weight: bold; width: 25%;">{{ $totalItemsSold }} Lembar/Pcs</td>
            </tr>
            <tr>
                <td style="background-color: #f8fafc; font-weight: bold;">Total Modal Bahan (HPP)</td>
                <td class="currency" style="font-weight: bold;">{{ $totalHpp }}</td>
                <td style="background-color: #ecfdf5; font-weight: bold; color: #065f46;">Total Laba Kotor Mesin</td>
                <td class="currency kpi-highlight">{{ $grossProfit }}</td>
            </tr>
        </tbody>
    </table>

    <br/>

    <!-- Product Breakdown Table -->
    <table>
        <thead>
            <tr>
                <th colspan="8" class="th-product" style="text-align: left; font-size: 11pt;">TABEL 1: REKAPITULASI PENJUALAN PER PRODUK</th>
            </tr>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 260px;">Nama Produk / Bahan</th>
                <th style="width: 140px;">Kategori</th>
                <th style="width: 150px;">Label Mesin</th>
                <th style="width: 120px;">Harga Satuan</th>
                <th style="width: 90px;">Qty Terjual</th>
                <th style="width: 140px;">Total Omzet</th>
                <th style="width: 130px;">Total HPP Bahan</th>
                <th style="width: 140px;">Laba Kotor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productsMap as $idx => $prod)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-left" style="font-weight: bold;">{{ $prod['product_name'] }}</td>
                <td class="text-left">{{ $prod['category'] }}</td>
                <td class="text-center">{{ $prod['machine_tag'] ?: '-' }}</td>
                <td class="currency">{{ $prod['unit_price'] }}</td>
                <td class="num">{{ $prod['qty_sold'] }}</td>
                <td class="currency fw-bold">{{ $prod['total_omzet'] }}</td>
                <td class="currency">{{ $prod['total_hpp'] }}</td>
                <td class="currency fw-bold" style="color: #065f46;">{{ $prod['gross_profit'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center" style="padding: 20px; color: #94a3b8;">Tidak ada data transaksi penjualan pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-total">
                <td colspan="5" class="text-center fw-bold" style="background-color: #f1f5f9;">TOTAL REKAP PRODUK</td>
                <td class="num fw-bold" style="background-color: #f1f5f9;">{{ $totalItemsSold }}</td>
                <td class="currency fw-bold" style="background-color: #f1f5f9;">{{ $totalOmzet }}</td>
                <td class="currency fw-bold" style="background-color: #f1f5f9;">{{ $totalHpp }}</td>
                <td class="currency fw-bold" style="background-color: #d1fae5; color: #065f46;">{{ $grossProfit }}</td>
            </tr>
        </tfoot>
    </table>

    <br/>

    <!-- Detailed Invoice Log Table -->
    <table>
        <thead>
            <tr>
                <th colspan="9" class="th-tx" style="text-align: left; font-size: 11pt;">TABEL 2: LOG RINCIAN TRANSAKSI NOTA / INVOICE</th>
            </tr>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 150px;">No. Invoice</th>
                <th style="width: 130px;">Tanggal & Jam</th>
                <th style="width: 140px;">Cabang</th>
                <th style="width: 160px;">Nama Pelanggan</th>
                <th style="width: 240px;">Produk Dicetak</th>
                <th style="width: 80px;">Qty</th>
                <th style="width: 120px;">Harga Satuan</th>
                <th style="width: 130px;">Subtotal Omzet</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactionDetails as $detail)
            @php
                $tx = $detail->transaction;
                $mat = $detail->material;
                $lineQty = (float) $detail->qty_ordered;
                $linePrice = (float) $detail->selling_price;
                $areaM2 = (float) ($detail->area_m2 ?? 0);
                $lineSubtotal = ($areaM2 > 0) ? ($areaM2 * $linePrice) : ($lineQty * $linePrice);
                if ($lineSubtotal <= 0 && $linePrice > 0) {
                    $lineSubtotal = $linePrice;
                }
            @endphp
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text text-center fw-bold">{{ $tx ? $tx->invoice_number : '-' }}</td>
                <td class="text-center">{{ $detail->created_at->format('d/m/Y H:i') }}</td>
                <td class="text-left">{{ $tx && $tx->branch ? $tx->branch->nama_cabang : '-' }}</td>
                <td class="text-left">{{ $tx ? ($tx->customer_name ?: 'Pelanggan Umum') : '-' }}</td>
                <td class="text-left">{{ $mat ? $mat->material_name : '-' }}</td>
                <td class="num">{{ $lineQty }}</td>
                <td class="currency">{{ $linePrice }}</td>
                <td class="currency fw-bold">{{ $lineSubtotal }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center" style="padding: 20px; color: #94a3b8;">Tidak ada transaksi.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-total">
                <td colspan="6" class="text-center fw-bold" style="background-color: #f1f5f9;">TOTAL RINCIAN TRANSAKSI</td>
                <td class="num fw-bold" style="background-color: #f1f5f9;">{{ $totalItemsSold }}</td>
                <td style="background-color: #f1f5f9;"></td>
                <td class="currency fw-bold" style="background-color: #f1f5f9;">{{ $totalOmzet }}</td>
            </tr>
        </tfoot>
    </table>

    <br/><br/>

    <!-- Signatures Section -->
    <table>
        <tr>
            <td colspan="3" class="text-center" style="border: none;">
                Dibuat Oleh,<br/><br/><br/><br/>
                ( _________________________ )<br/>
                <strong>Finance / Kasir</strong>
            </td>
            <td colspan="2" style="border: none;"></td>
            <td colspan="3" class="text-center" style="border: none;">
                Diketahui Oleh,<br/><br/><br/><br/>
                ( <strong>{{ $selectedTag === 'all' ? 'Penanggung Jawab Mesin' : $selectedTag }}</strong> )<br/>
                <strong>Penanggung Jawab Mesin</strong>
            </td>
        </tr>
    </table>

</body>
</html>
