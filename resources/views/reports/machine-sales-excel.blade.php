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
        
        .kpi-highlight { background-color: #f3e8ff; color: #581c87; font-size: 11pt; font-weight: bold; }
        .kpi-cost { background-color: #ffe4e6; color: #9f1239; font-size: 11pt; font-weight: bold; }
        .kpi-profit { background-color: #dcfce7; color: #166534; font-size: 11pt; font-weight: bold; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .bg-total { background-color: #f8fafc; font-weight: bold; }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table>
        <tr>
            <td colspan="11" class="title-main" style="border: none;">SNAPRINT DIGITAL PRINTING</td>
        </tr>
        <tr>
            <td colspan="11" class="title-sub" style="border: none;">LAPORAN REKAPITULASI PENJUALAN MESIN</td>
        </tr>
        <tr>
            <td colspan="11" style="border: none; height: 10px;"></td>
        </tr>
        <tr>
            <td style="border: none; font-weight: bold; width: 140px;">Label Mesin:</td>
            <td colspan="3" style="border: none;"><strong>{{ $selectedTag === 'all' ? 'Semua Mesin Berlabel' : ($selectedTag === 'KM' ? 'KM (Konica Minolta)' : $selectedTag) }}</strong></td>
            <td style="border: none; font-weight: bold; width: 120px;">Periode:</td>
            <td colspan="6" style="border: none;"><strong>{{ $periodLabel }}</strong></td>
        </tr>
        <tr>
            <td style="border: none; font-weight: bold;">Cabang:</td>
            <td colspan="3" style="border: none;"><strong>{{ $branchName }}</strong></td>
            <td style="border: none; font-weight: bold;">Tanggal Unduh:</td>
            <td colspan="6" style="border: none;">{{ now()->translatedFormat('d F Y H:i') }}</td>
        </tr>
    </table>

    <br/>

    <!-- Summary KPI Table -->
    <table>
        <thead>
            <tr>
                <th colspan="6" style="background-color: #1e293b; color: #ffffff; text-align: left;">RINGKASAN KINERJA PENJUALAN & BIAYA KLIK MESIN</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="background-color: #f8fafc; font-weight: bold; width: 18%;">Total Penjualan (Omzet)</td>
                <td class="kpi-highlight text-right" style="width: 15%;">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</td>
                <td style="background-color: #f8fafc; font-weight: bold; width: 18%;">Total Biaya Klik (HPP)</td>
                <td class="kpi-cost text-right" style="width: 15%;">Rp {{ number_format($totalClickCharge, 0, ',', '.') }}</td>
                <td style="background-color: #f8fafc; font-weight: bold; width: 18%;">Laba Kotor Mesin</td>
                <td class="kpi-profit text-right" style="width: 16%;">Rp {{ number_format($totalGrossProfit, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="background-color: #f8fafc; font-weight: bold;">Total Volume Cetak</td>
                <td class="text-center fw-bold">{{ number_format($totalItemsSold) }} Lembar/Pcs</td>
                <td style="background-color: #f8fafc; font-weight: bold;">Total Klik Mesin (Passes)</td>
                <td class="text-center fw-bold" style="color: #0369a1;">{{ number_format($totalClicks) }} Klik</td>
                <td style="background-color: #f8fafc; font-weight: bold;">Total Nota / Transaksi</td>
                <td class="text-center fw-bold">{{ number_format($uniqueInvoicesCount) }} Invoice (Avg Rp {{ number_format($avgTransactionValue, 0, ',', '.') }})</td>
            </tr>
        </tbody>
    </table>

    <br/>

    <!-- Product Breakdown Table -->
    <table>
        <thead>
            <tr>
                <th colspan="11" class="th-product" style="text-align: left; font-size: 11pt;">TABEL 1: REKAPITULASI PENJUALAN PER PRODUK</th>
            </tr>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 260px;">Nama Produk / Bahan</th>
                <th style="width: 120px;">Kategori</th>
                <th style="width: 100px;">Label Mesin</th>
                <th style="width: 110px;">Harga Satuan</th>
                <th style="width: 90px;">Qty Terjual</th>
                <th style="width: 110px;">Tarif Klik</th>
                <th style="width: 90px;">Total Klik</th>
                <th style="width: 130px;">Biaya Klik</th>
                <th style="width: 140px;">Total Omzet</th>
                <th style="width: 140px;">Laba Mesin</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productsMap as $idx => $prod)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-left" style="font-weight: bold;">{{ $prod['product_name'] }}</td>
                <td class="text-left">{{ $prod['category'] }}</td>
                <td class="text-center">{{ $prod['machine_tag'] ?: '-' }}</td>
                <td class="text-right">Rp {{ number_format($prod['unit_price'], 0, ',', '.') }}</td>
                <td class="text-center">{{ number_format($prod['qty_sold']) }}</td>
                <td class="text-right">{{ $prod['click_rate'] > 0 ? 'Rp ' . number_format($prod['click_rate'], 0, ',', '.') : '-' }}</td>
                <td class="text-center" style="color: #0369a1;">{{ $prod['total_clicks'] > 0 ? number_format($prod['total_clicks']) : '-' }}</td>
                <td class="text-right" style="color: #be123c; font-weight: bold;">Rp {{ number_format($prod['total_click_cost'], 0, ',', '.') }}</td>
                <td class="text-right fw-bold" style="color: #4338ca;">Rp {{ number_format($prod['total_omzet'], 0, ',', '.') }}</td>
                <td class="text-right fw-bold" style="color: #15803d;">Rp {{ number_format($prod['gross_profit'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="text-center" style="padding: 20px; color: #94a3b8;">Tidak ada data transaksi penjualan pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-total">
                <td colspan="5" class="text-center fw-bold" style="background-color: #f1f5f9;">TOTAL KESELURUHAN</td>
                <td class="text-center fw-bold" style="background-color: #f1f5f9;">{{ number_format($totalItemsSold) }}</td>
                <td style="background-color: #f1f5f9;"></td>
                <td class="text-center fw-bold" style="background-color: #f1f5f9; color: #0369a1;">{{ number_format($totalClicks) }}</td>
                <td class="text-right fw-bold" style="background-color: #ffe4e6; color: #9f1239;">Rp {{ number_format($totalClickCharge, 0, ',', '.') }}</td>
                <td class="text-right fw-bold" style="background-color: #f3e8ff; color: #581c87;">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</td>
                <td class="text-right fw-bold" style="background-color: #dcfce7; color: #166534;">Rp {{ number_format($totalGrossProfit, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <br/>

    <!-- Detailed Invoice Log Table -->
    <table>
        <thead>
            <tr>
                <th colspan="11" class="th-tx" style="text-align: left; font-size: 11pt;">TABEL 2: LOG RINCIAN TRANSAKSI NOTA / INVOICE</th>
            </tr>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 140px;">No. Invoice</th>
                <th style="width: 120px;">Tanggal & Jam</th>
                <th style="width: 130px;">Cabang</th>
                <th style="width: 150px;">Nama Pelanggan</th>
                <th style="width: 220px;">Produk Dicetak</th>
                <th style="width: 70px;">Qty</th>
                <th style="width: 110px;">Harga Satuan</th>
                <th style="width: 130px;">Subtotal Omzet</th>
                <th style="width: 100px;">Tarif Klik</th>
                <th style="width: 120px;">Biaya Klik</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactionDetails as $detail)
            @php
                $tx = $detail->transaction;
                $lineQty = (float) $detail->qty_ordered;
                $linePrice = (float) $detail->selling_price;
                $lineSubtotal = $detail->computed_revenue ?? (($detail->area_m2 > 0) ? ($detail->area_m2 * $linePrice) : ($lineQty * $linePrice));
            @endphp
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-center fw-bold">{{ $tx ? $tx->invoice_number : '-' }}</td>
                <td class="text-center">{{ $detail->created_at->format('d/m/Y H:i') }}</td>
                <td class="text-left">{{ $tx && $tx->branch ? $tx->branch->nama_cabang : '-' }}</td>
                <td class="text-left">{{ $tx ? ($tx->customer_name ?: 'Pelanggan Umum') : '-' }}</td>
                <td class="text-left">{{ $detail->item_name }}</td>
                <td class="text-center">{{ number_format($lineQty) }}</td>
                <td class="text-right">Rp {{ number_format($linePrice, 0, ',', '.') }}</td>
                <td class="text-right fw-bold">Rp {{ number_format($lineSubtotal, 0, ',', '.') }}</td>
                <td class="text-right">{{ ($detail->click_rate ?? 0) > 0 ? 'Rp ' . number_format($detail->click_rate, 0, ',', '.') : '-' }}</td>
                <td class="text-right" style="color: #be123c;">Rp {{ number_format($detail->line_click_cost ?? 0, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="text-center" style="padding: 20px; color: #94a3b8;">Tidak ada transaksi.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-total">
                <td colspan="6" class="text-center fw-bold" style="background-color: #f1f5f9;">TOTAL RINCIAN TRANSAKSI</td>
                <td class="text-center fw-bold" style="background-color: #f1f5f9;">{{ number_format($totalItemsSold) }}</td>
                <td style="background-color: #f1f5f9;"></td>
                <td class="text-right fw-bold" style="background-color: #f3e8ff; color: #581c87;">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</td>
                <td style="background-color: #f1f5f9;"></td>
                <td class="text-right fw-bold" style="background-color: #ffe4e6; color: #9f1239;">Rp {{ number_format($totalClickCharge, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <br/><br/>

    <!-- Signatures Section -->
    <table>
        <tr>
            <td colspan="5" class="text-center" style="border: none;">
                Dibuat Oleh,<br/><br/><br/><br/>
                ( _________________________ )<br/>
                <strong>Finance / Kasir</strong>
            </td>
            <td style="border: none;"></td>
            <td colspan="5" class="text-center" style="border: none;">
                Diketahui Oleh,<br/><br/><br/><br/>
                ( <strong>{{ $selectedTag === 'all' ? 'Penanggung Jawab Mesin' : ($selectedTag === 'KM' ? 'KM (Konica Minolta)' : $selectedTag) }}</strong> )<br/>
                <strong>Penanggung Jawab Mesin KM</strong>
            </td>
        </tr>
    </table>

</body>
</html>
