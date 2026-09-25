<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; color: #1e293b; }
        .title-main { font-size: 16pt; font-weight: bold; color: #1e1b4b; }
        .title-sub { font-size: 12pt; font-weight: bold; color: #4338ca; }
        
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 10pt; }
        th { background-color: #1e293b; color: #ffffff; font-weight: bold; text-align: center; vertical-align: middle; }
        
        .th-product { background-color: #312e81; color: #ffffff; }
        .th-tx { background-color: #0f172a; color: #ffffff; }
        
        .kpi-highlight { background-color: #f3e8ff; color: #581c87; font-size: 12pt; font-weight: bold; }
        
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
            <td colspan="7" class="title-main" style="border: none;">SNAPRINT DIGITAL PRINTING</td>
        </tr>
        <tr>
            <td colspan="7" class="title-sub" style="border: none;">LAPORAN REKAPITULASI PENJUALAN MESIN</td>
        </tr>
        <tr>
            <td colspan="7" style="border: none; height: 10px;"></td>
        </tr>
        <tr>
            <td style="border: none; font-weight: bold; width: 140px;">Label Mesin:</td>
            <td colspan="2" style="border: none;"><strong>{{ $selectedTag === 'all' ? 'Semua Mesin Berlabel' : $selectedTag }}</strong></td>
            <td style="border: none; font-weight: bold; width: 120px;">Periode:</td>
            <td colspan="3" style="border: none;"><strong>{{ $periodLabel }}</strong></td>
        </tr>
        <tr>
            <td style="border: none; font-weight: bold;">Cabang:</td>
            <td colspan="2" style="border: none;"><strong>{{ $branchName }}</strong></td>
            <td style="border: none; font-weight: bold;">Tanggal Unduh:</td>
            <td colspan="3" style="border: none;">{{ now()->translatedFormat('d F Y H:i') }}</td>
        </tr>
    </table>

    <br/>

    <!-- Summary KPI Table -->
    <table>
        <thead>
            <tr>
                <th colspan="4" style="background-color: #1e293b; color: #ffffff; text-align: left;">RINGKASAN TOTAL PENJUALAN MESIN</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="background-color: #f8fafc; font-weight: bold; width: 25%;">Total Penjualan (Omzet)</td>
                <td class="currency kpi-highlight" style="width: 25%;">{{ $totalOmzet }}</td>
                <td style="background-color: #f8fafc; font-weight: bold; width: 25%;">Total Volume Cetak</td>
                <td class="num" style="font-weight: bold; width: 25%;">{{ $totalItemsSold }} Lembar/Pcs</td>
            </tr>
            <tr>
                <td style="background-color: #f8fafc; font-weight: bold;">Total Transaksi / Nota</td>
                <td class="num" style="font-weight: bold;">{{ $uniqueInvoicesCount }} Invoice</td>
                <td style="background-color: #f8fafc; font-weight: bold;">Rata-rata per Transaksi</td>
                <td class="currency" style="font-weight: bold;">{{ $avgTransactionValue }}</td>
            </tr>
        </tbody>
    </table>

    <br/>

    <!-- Product Breakdown Table -->
    <table>
        <thead>
            <tr>
                <th colspan="7" class="th-product" style="text-align: left; font-size: 11pt;">TABEL 1: REKAPITULASI PENJUALAN PER PRODUK</th>
            </tr>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 280px;">Nama Produk / Bahan</th>
                <th style="width: 140px;">Kategori</th>
                <th style="width: 150px;">Label Mesin</th>
                <th style="width: 120px;">Harga Satuan</th>
                <th style="width: 100px;">Qty Terjual</th>
                <th style="width: 160px;">Total Penjualan (Omzet)</th>
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
                <td class="currency fw-bold" style="color: #4338ca;">{{ $prod['total_omzet'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8;">Tidak ada data transaksi penjualan pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-total">
                <td colspan="5" class="text-center fw-bold" style="background-color: #f1f5f9;">TOTAL KESELURUHAN PENJUALAN</td>
                <td class="num fw-bold" style="background-color: #f1f5f9;">{{ $totalItemsSold }}</td>
                <td class="currency fw-bold" style="background-color: #f3e8ff; color: #581c87;">{{ $totalOmzet }}</td>
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
                <th style="width: 250px;">Produk Dicetak</th>
                <th style="width: 80px;">Qty</th>
                <th style="width: 120px;">Harga Satuan</th>
                <th style="width: 140px;">Subtotal Penjualan</th>
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
                <td colspan="6" class="text-center fw-bold" style="background-color: #f1f5f9;">TOTAL RINCIAN PENJUALAN</td>
                <td class="num fw-bold" style="background-color: #f1f5f9;">{{ $totalItemsSold }}</td>
                <td style="background-color: #f1f5f9;"></td>
                <td class="currency fw-bold" style="background-color: #f3e8ff; color: #581c87;">{{ $totalOmzet }}</td>
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
            <td colspan="2" class="text-center" style="border: none;">
                Diketahui Oleh,<br/><br/><br/><br/>
                ( <strong>{{ $selectedTag === 'all' ? 'Penanggung Jawab Mesin' : $selectedTag }}</strong> )<br/>
                <strong>Penanggung Jawab Mesin</strong>
            </td>
        </tr>
    </table>

</body>
</html>
