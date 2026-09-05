<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi - {{ $periodLabel }}</title>
    <style>
        @page {
            margin: 28px 32px 35px 32px;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .company-name {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 0 0 3px 0;
        }
        .report-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 6px 0;
        }
        .meta-table {
            width: 100%;
            margin-top: 6px;
            font-size: 10px;
            color: #475569;
        }
        .meta-table td {
            padding: 2px 4px;
        }
        .section-header {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            font-size: 10.5px;
            padding: 6px 10px;
            text-transform: uppercase;
            border-left: 4px solid #1e40af;
            margin-top: 12px;
            margin-bottom: 4px;
        }
        .section-header.hpp {
            border-left-color: #d97706;
        }
        .section-header.opex {
            border-left-color: #e11d48;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        table.data-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        table.data-table tr.total-row td {
            font-weight: 700;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            background-color: #f8fafc;
        }
        .amount {
            text-align: right;
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            white-space: nowrap;
        }
        .summary-box {
            padding: 8px 12px;
            border-radius: 4px;
            margin: 10px 0;
            font-weight: 700;
        }
        .gross-profit-box {
            background-color: #f8fafc;
            border: 1.5px solid #cbd5e1;
            color: #0f172a;
        }
        .net-profit-box {
            background-color: #eff6ff;
            border: 2px solid #1e40af;
            color: #1e3a8a;
            font-size: 12px;
        }
        .net-profit-box.loss {
            background-color: #fff1f2;
            border-color: #e11d48;
            color: #9f1239;
        }
        .signatures {
            margin-top: 35px;
            width: 100%;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 10px;
        }
        .signature-line {
            width: 180px;
            border-bottom: 1px solid #0f172a;
            margin: 50px auto 4px auto;
        }
        .footer-note {
            margin-top: 25px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px dashed #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1 class="company-name">PT Duta Raya Berjaya (Snaprint)</h1>
        <h2 class="report-title">Laporan Laba Rugi Komprehensif (Profit & Loss)</h2>
        <table class="meta-table">
            <tr>
                <td style="width: 50%; text-align: left;">
                    <strong>Periode:</strong> {{ $periodLabel }}<br>
                    <strong>Entitas / Cabang:</strong> {{ $branchName }}
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>Tanggal Cetak:</strong> {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }} WIB<br>
                    <strong>Diterbitkan Oleh:</strong> {{ auth()->user()->full_name ?: auth()->user()->name }} ({{ strtoupper(auth()->user()->role) }})
                </td>
            </tr>
        </table>
    </div>

    <!-- I. PENDAPATAN USAHA (OMZET POS) -->
    <div class="section-header">I. Pendapatan Usaha (Revenue / Omzet)</div>
    <table class="data-table">
        <tbody>
            @foreach($pendapatan as $p)
            <tr>
                <td style="padding-left: 18px;">{{ $p->nama_akun }}</td>
                <td class="amount">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row" style="color: #1e40af;">
                <td style="padding-left: 18px;">Total Pendapatan Usaha (Omzet Bersih)</td>
                <td class="amount">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- II. HARGA POKOK PENJUALAN (HPP) -->
    <div class="section-header hpp">II. Harga Pokok Penjualan (HPP / Cost of Goods Sold)</div>
    <table class="data-table">
        <tbody>
            @forelse($hpp as $h)
            <tr>
                <td style="padding-left: 18px;">{{ $h->nama_akun }}</td>
                <td class="amount">Rp {{ number_format($h->jumlah, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td style="padding-left: 18px; color: #94a3b8; font-style: italic;">Tidak ada pos HPP tercatat</td>
                <td class="amount" style="color: #94a3b8;">Rp 0</td>
            </tr>
            @endforelse
            <tr class="total-row" style="color: #b45309;">
                <td style="padding-left: 18px;">Total Biaya Pokok Penjualan (HPP)</td>
                <td class="amount">(Rp {{ number_format($totalHpp, 0, ',', '.') }})</td>
            </tr>
        </tbody>
    </table>

    <!-- LABA KOTOR (GROSS PROFIT) -->
    <table class="data-table summary-box gross-profit-box" style="margin-top: 8px; margin-bottom: 8px;">
        <tr>
            <td style="border: none; text-transform: uppercase; font-size: 11px;">
                <strong>Laba Kotor (Gross Profit = Omzet &minus; HPP)</strong>
            </td>
            <td class="amount" style="border: none; font-size: 12px; font-weight: 800; color: {{ $labaKotor >= 0 ? '#15803d' : '#be123c' }};">
                Rp {{ number_format($labaKotor, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <!-- III. BEBAN OPERASIONAL (OPEX / KAS KELUAR) -->
    <div class="section-header opex">III. Beban Operasional (OPEX / Pengeluaran Kas Keluar)</div>
    <table class="data-table">
        <tbody>
            @forelse($bebanOperasional as $b)
            <tr>
                <td style="padding-left: 18px;">{{ $b->nama_akun }}</td>
                <td class="amount">Rp {{ number_format($b->jumlah, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td style="padding-left: 18px; color: #94a3b8; font-style: italic;">Tidak ada pos pengeluaran kas keluar</td>
                <td class="amount" style="color: #94a3b8;">Rp 0</td>
            </tr>
            @endforelse
            <tr class="total-row" style="color: #be123c;">
                <td style="padding-left: 18px;">Total Beban Operasional (OPEX)</td>
                <td class="amount">(Rp {{ number_format($totalBebanOperasional, 0, ',', '.') }})</td>
            </tr>
        </tbody>
    </table>

    <!-- LABA BERSIH (NET PROFIT) -->
    <table class="data-table summary-box net-profit-box {{ $labaBersih < 0 ? 'loss' : '' }}" style="margin-top: 10px;">
        <tr>
            <td style="border: none; text-transform: uppercase; font-size: 12px;">
                <strong>Laba Bersih {{ $labaBersih >= 0 ? '' : '(Rugi)' }} Periode Berjalan (Gross Profit &minus; OPEX)</strong>
            </td>
            <td class="amount" style="border: none; font-size: 13px; font-weight: 800;">
                Rp {{ number_format($labaBersih, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <!-- TANDA TANGAN PENGESAHAN -->
    <table class="signatures">
        <tr>
            <td>
                Disetujui Oleh,<br>
                <div class="signature-line"></div>
                <strong>DIREKTUR / OWNER</strong><br>
                <span>PT Duta Raya Berjaya</span>
            </td>
            <td>
                Dibuat Oleh,<br>
                <div class="signature-line"></div>
                <strong>BAGIAN KEUANGAN & AKUNTING</strong><br>
                <span>{{ auth()->user()->full_name ?: auth()->user()->name }}</span>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen Laporan Keuangan Sah dicetak secara otomatis melalui Sistem ERP Snaprint Terintegrasi.<br>
        PT Duta Raya Berjaya &copy; {{ date('Y') }}. Seluruh hak cipta dilindungi undang-undang.
    </div>

</body>
</html>
