import os
from reportlab.lib.pagesizes import landscape, A4
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak
)
from reportlab.pdfgen import canvas
import fitz

# Palette SnapPrint
NAVY_DARK = colors.HexColor('#0B132B')
NAVY_CARD = colors.HexColor('#1E293B')
BLUE_PRIMARY = colors.HexColor('#1D4ED8')
BLUE_LIGHT = colors.HexColor('#EFF6FF')
BLUE_BORDER = colors.HexColor('#93C5FD')
TEAL_ACCENT = colors.HexColor('#0D9488')
EMERALD_GREEN = colors.HexColor('#059669')
EMERALD_LIGHT = colors.HexColor('#ECFDF5')
AMBER_ACCENT = colors.HexColor('#D97706')
AMBER_LIGHT = colors.HexColor('#FFFBEB')
SLATE_900 = colors.HexColor('#0F172A')
SLATE_700 = colors.HexColor('#334155')
SLATE_500 = colors.HexColor('#64748B')
SLATE_200 = colors.HexColor('#CBD5E1')
SLATE_100 = colors.HexColor('#F1F5F9')
SLATE_50 = colors.HexColor('#F8FAFC')
WHITE = colors.HexColor('#FFFFFF')

PAGE_WIDTH, PAGE_HEIGHT = landscape(A4) # 841.89 x 595.27 pt
LOGO_PATH = '/Users/kingashabil/Desktop/Skirpsi/public/images/logosnaprint.jpeg'
OUTPUT_PDF = '/Users/kingashabil/Desktop/Skirpsi/public/SnapPrint_Franchise_Pitch_Deck.pdf'
ARTIFACT_DIR = '/Users/kingashabil/.gemini/antigravity/brain/80681852-77f3-4bf7-8c4f-4ff7a3e38580'
ARTIFACT_PDF = os.path.join(ARTIFACT_DIR, 'SnapPrint_Franchise_Pitch_Deck.pdf')

class NumberedCanvas(canvas.Canvas):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_decorations(num_pages)
            canvas.Canvas.showPage(self)
        canvas.Canvas.save(self)

    def draw_decorations(self, page_count):
        if self._pageNumber == 1:
            return  # Cover slide has custom layout
        
        # Header Line
        self.setStrokeColor(SLATE_200)
        self.setLineWidth(0.75)
        self.line(40, PAGE_HEIGHT - 38, PAGE_WIDTH - 40, PAGE_HEIGHT - 38)

        # Header Logo & Brand
        if os.path.exists(LOGO_PATH):
            try:
                self.drawImage(LOGO_PATH, 42, PAGE_HEIGHT - 34, width=22, height=22, preserveAspectRatio=True, mask='auto')
            except Exception:
                pass
        self.setFont("Helvetica-Bold", 10)
        self.setFillColor(NAVY_DARK)
        self.drawString(70, PAGE_HEIGHT - 28, "SNAPPRINT")
        self.setFont("Helvetica", 9)
        self.setFillColor(SLATE_500)
        self.drawString(138, PAGE_HEIGHT - 28, "|   Franchise & Business Opportunity Pitch Deck 2026")

        # Top Right Confidential Tag
        self.setFont("Helvetica-Bold", 8)
        self.setFillColor(TEAL_ACCENT)
        self.drawRightString(PAGE_WIDTH - 42, PAGE_HEIGHT - 28, "CONFIDENTIAL & PROPRIETARY")

        # Footer Line
        self.line(40, 35, PAGE_WIDTH - 40, 35)

        # Footer Text & Page Number
        self.setFont("Helvetica", 8)
        self.setFillColor(SLATE_500)
        self.drawString(42, 22, "PT SNAPPRINT DIGITAL NUSANTARA   •   www.snaprint.co.id   •   Sistem Ekosistem ERP Terintegrasi")
        page_str = f"Halaman {self._pageNumber} dari {page_count}"
        self.drawRightString(PAGE_WIDTH - 42, 22, page_str)

def build_pdf():
    doc = SimpleDocTemplate(
        OUTPUT_PDF,
        pagesize=landscape(A4),
        leftMargin=40,
        rightMargin=40,
        topMargin=50,
        bottomMargin=45
    )

    styles = getSampleStyleSheet()

    style_cover_title = ParagraphStyle(
        'CoverTitle',
        fontName='Helvetica-Bold',
        fontSize=26,
        leading=32,
        textColor=WHITE
    )
    style_cover_desc = ParagraphStyle(
        'CoverDesc',
        fontName='Helvetica',
        fontSize=10,
        leading=14,
        textColor=colors.HexColor('#E2E8F0')
    )

    style_section_title = ParagraphStyle(
        'SectionTitle',
        fontName='Helvetica-Bold',
        fontSize=16,
        leading=20,
        textColor=NAVY_DARK,
        spaceAfter=3
    )
    style_section_subtitle = ParagraphStyle(
        'SectionSubtitle',
        fontName='Helvetica',
        fontSize=9.5,
        leading=13,
        textColor=SLATE_500,
        spaceAfter=8
    )

    style_card_title = ParagraphStyle(
        'CardTitle',
        fontName='Helvetica-Bold',
        fontSize=10.5,
        leading=14,
        textColor=NAVY_DARK
    )
    style_table_header = ParagraphStyle(
        'TableHeader',
        fontName='Helvetica-Bold',
        fontSize=9,
        leading=12,
        textColor=WHITE
    )
    style_card_body = ParagraphStyle(
        'CardBody',
        fontName='Helvetica',
        fontSize=8.5,
        leading=12,
        textColor=SLATE_700
    )
    style_card_bullet = ParagraphStyle(
        'CardBullet',
        fontName='Helvetica',
        fontSize=8,
        leading=11.5,
        textColor=SLATE_700
    )

    story = []

    # =========================================================================
    # SLIDE 1: COVER SLIDE
    # =========================================================================
    cover_data = [
        [
            Paragraph("""
            <font color="#38BDF8"><b>FRANCHISE INVESTMENT PROPOSAL 2026</b></font><br/><br/>
            <b>SNAPPRINT DIGITAL PRINTING</b><br/>
            <font size="17" color="#94A3B8">Ekosistem Percetakan Modern Berbasis ERP</font><br/><br/>
            <font color="#E2E8F0" size="10">Peluang kemitraan bisnis percetakan ritel & korporat dengan sistem otomasi cerdas, kepastian rantai pasok terpusat, dan <b>Return on Investment (ROI) teruji 34% per tahun</b>.</font>
            """, style_cover_title),
            Paragraph(f"""
            <div align="center">
                <img src="{LOGO_PATH}" width="110" height="110"/><br/><br/>
                <font color="#38BDF8" size="11"><b>PT SNAPPRINT DIGITAL NUSANTARA</b></font><br/>
                <font color="#94A3B8" size="8.5">Legalitas Badan Usaha Resmi • Standar ISO 9001 SOP</font><br/><br/>
                <font color="#10B981" size="9.5"><b>✓ ROI TERUJI 34% / TAHUN</b></font><br/>
                <font color="#FBBF24" size="8.5"><b>✓ INTEGRASI SNAPPRINT CLOUD ERP</b></font>
            </div>
            """, style_cover_desc)
        ]
    ]

    cover_table = Table(cover_data, colWidths=[480, 280])
    cover_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), NAVY_DARK),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('PADDING', (0, 0), (-1, -1), 28),
        ('ROUNDEDCORNERS', [14, 14, 14, 14]),
    ]))

    # Highlight metric cards at bottom of cover
    metric_cards = [
        [
            Paragraph("<b>TARGET PAYBACK PERIOD</b><br/><font size='13' color='#1D4ED8'><b>~ 2.9 Tahun (35 Bln)</b></font><br/><font size='7.5' color='#64748B'>Arus kas positif sejak bulan ke-3</font>", style_card_body),
            Paragraph("<b>ESTIMASI ANNUAL ROI</b><br/><font size='13' color='#059669'><b>34.0% per Tahun</b></font><br/><font size='7.5' color='#64748B'>Skenario moderat teruji</font>", style_card_body),
            Paragraph("<b>SISTEM DIGITAL ERP</b><br/><font size='13' color='#7C3AED'><b>100% Terintegrasi</b></font><br/><font size='7.5' color='#64748B'>POS, Inventory, Accounting COA</font>", style_card_body),
            Paragraph("<b>REPEAT ORDER RATE</b><br/><font size='13' color='#D97706'><b>72% Pelanggan B2B</b></font><br/><font size='7.5' color='#64748B'>Kontrak korporat & UMKM lokal</font>", style_card_body),
        ]
    ]
    metric_table = Table(metric_cards, colWidths=[185, 185, 185, 185])
    metric_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), WHITE),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 1, SLATE_200),
        ('PADDING', (0, 0), (-1, -1), 10),
        ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ]))

    story.append(cover_table)
    story.append(Spacer(1, 14))
    story.append(metric_table)
    story.append(PageBreak())

    # =========================================================================
    # SLIDE 2: 1. EXECUTIVE SUMMARY & NILAI UNIK BRAND (USP)
    # =========================================================================
    story.append(Paragraph("1. Executive Summary & Nilai Unik Brand (USP)", style_section_title))
    story.append(Paragraph("Fondasi bisnis solid, legalitas resmi, dan keunggulan kompetitif dibanding percetakan ritel konvensional.", style_section_subtitle))

    slide2_data = [
        [
            Paragraph("""
            <b>PROFIL BRAND & VISI PERUSAHAAN</b><br/><br/>
            <b>SnapPrint</b> adalah jaringan modern digital printing & merchandise hub yang menghadirkan pengalaman cetak cepat, presisi tinggi, dan transparan bagi pelanggan ritel maupun korporasi.<br/><br/>
            • <b>Visi:</b> Menjadi jaringan percetakan digital terdepan di Indonesia yang terstandardisasi melalui otomasi teknologi ERP dan kepuasan pelanggan prima.<br/>
            • <b>Misi:</b> Menyediakan solusi cetak dokumen, promosi, dan kemasan dengan harga kompetitif, QC terjaga, dan SLA pengerjaan tercepat.<br/>
            • <b>Legalitas:</b> PT SnapPrint Digital Nusantara (NIB, NPWP Badan, Hak Cipta Merek Kemenkumham terdaftar resmi).
            """, style_card_body),
            Paragraph("""
            <b>UNIQUE SELLING PROPOSITION (USP)</b><br/><br/>
            Mengapa SnapPrint unggul telak dari kompetitor percetakan lokal konvensional:<br/><br/>
            <b>1. SnapPrint Cloud ERP & Auto-Quote:</b><br/>
            Kalkulasi harga otomatis instan per meter persegi atau per lembar, antrean cetak digital (Work Order), dan eliminasi salah hitung kasir.<br/><br/>
            <b>2. Jaminan Garansi Cetak 100% (QC SLA):</b><br/>
            Garansi reprint gratis bila hasil cetak cacat warna / salah potong demi loyalitas pelanggan korporat tanpa kompromi.<br/><br/>
            <b>3. Centralized Supply Chain (Harga Pabrik):</b><br/>
            Pasokan bahan baku kertas, vinyl, tinta, dan blank merchandise langsung dari distributor tier-1 dengan margin laba kotor 55%–65%.
            """, style_card_body)
        ]
    ]

    slide2_table = Table(slide2_data, colWidths=[365, 385])
    slide2_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, 0), SLATE_50),
        ('BACKGROUND', (1, 0), (1, 0), BLUE_LIGHT),
        ('BOX', (0, 0), (0, 0), 1, SLATE_200),
        ('BOX', (1, 0), (1, 0), 1, BLUE_BORDER),
        ('PADDING', (0, 0), (-1, -1), 14),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ]))
    story.append(slide2_table)

    summary_pillars = [
        [
            Paragraph("<font color='#1D4ED8'><b>1. Kecepatan Layanan (SLA)</b></font><br/><font size='7.5' color='#475569'>Print A3+ & Banner kilat 15-30 menit selesai berkat alur file RIP otomatis.</font>", style_card_body),
            Paragraph("<font color='#059669'><b>2. Akuntansi & Kas Terpadu</b></font><br/><font size='7.5' color='#475569'>Owner memantau omset, mutasi kas/bank COA, dan laba bersih dari smartphone.</font>", style_card_body),
            Paragraph("<font color='#7C3AED'><b>3. Proteksi Wilayah Eksklusif</b></font><br/><font size='7.5' color='#475569'>Radius proteksi teritori kemitraan min. 3-5 km antar cabang outlet SnapPrint.</font>", style_card_body),
        ]
    ]
    summary_table = Table(summary_pillars, colWidths=[245, 245, 260])
    summary_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), WHITE),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 1, SLATE_200),
        ('PADDING', (0, 0), (-1, -1), 9),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ]))
    story.append(Spacer(1, 10))
    story.append(summary_table)
    story.append(PageBreak())

    # =========================================================================
    # SLIDE 3: 2. PELUANG PASAR & TARGET KONSUMEN
    # =========================================================================
    story.append(Paragraph("2. Peluang Pasar & Karakteristik Konsumen", style_section_title))
    story.append(Paragraph("Permintaan cetak yang tidak pernah mati dengan basis repeat order tinggi di sektor komersial dan ritel.", style_section_subtitle))

    slide3_cards = [
        [
            Paragraph("""
            <b>SEKTOR B2B & KORPORAT (45% REVENUE)</b><br/><br/>
            • <b>UMKM & Brand Lokal:</b> Stiker label kemasan, standing pouch, paper bag, kartu nama, hangtag baju.<br/>
            • <b>Perkantoran & Institusi:</b> Kop surat, amplop, map folder, form continuous, brosur company profile.<br/>
            • <b>Sifat Belanja:</b> Volume besar, repeat order rutin 2–4 kali per bulan, sensitif terhadap ketepatan waktu.
            """, style_card_body),
            Paragraph("""
            <b>SEKTOR EVENT & PENDIDIKAN (30% REVENUE)</b><br/><br/>
            • <b>Event Organizer & Komunitas:</b> Backdrop, roll-up banner, photobooth, wristband, lanyard, ID card.<br/>
            • <b>Kampus & Sekolah:</b> Cetak modul, jilid skripsi kilat, sertifikat berhologram, buku tahunan siswa.<br/>
            • <b>Sifat Belanja:</b> Musiman dengan lonjakan omset signifikan (*seasonal peak*) pada masa kelulusan & event.
            """, style_card_body),
            Paragraph("""
            <b>SEKTOR B2C / PERORANGAN (25% REVENUE)</b><br/><br/>
            • <b>Personal & Freelancer:</b> Print foto, cetak dokumen PDF, sablon kaos satuan DTF, tumbler kustom.<br/>
            • <b>Keluarga & Pernikahan:</b> Undangan pernikahan, souvenir mug, banner ucapan selamat, stempel flash.<br/>
            • <b>Sifat Belanja:</b> Margin retail tebal (markup 60%–70%), pembayaran tunai/QRIS instan di kasir.
            """, style_card_body),
        ]
    ]

    slide3_table = Table(slide3_cards, colWidths=[245, 245, 260])
    slide3_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), WHITE),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 1, SLATE_200),
        ('PADDING', (0, 0), (-1, -1), 12),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ]))
    story.append(slide3_table)

    season_data = [
        [
            Paragraph("""
            <b>DINAMIKA SIKLUS BELANJA & REPEAT ORDER SEPANJANG TAHUN:</b><br/>
            • <b>Q1 (Jan–Mar):</b> Laporan tahunan korporat, materi promosi awal tahun, kalender susulan, dan pameran niaga.<br/>
            • <b>Q2 (Apr–Jun):</b> Musim kelulusan sekolah/kampus, skripsi, cetak sertifikat, event seminar, dan merchandise reuni.<br/>
            • <b>Q3 (Jul–Sep):</b> Masa Orientasi Siswa/MABA (lanyard, kaos, booklet), promosi HUT RI (banner & umbul-umbul masif).<br/>
            • <b>Q4 (Okt–Des):</b> Puncak belanja akhir tahun: Kalender meja/dinding, agenda kerja, promo diskon retail, kampanye event.
            """, style_card_body)
        ]
    ]
    season_table = Table(season_data, colWidths=[750])
    season_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), AMBER_LIGHT),
        ('BOX', (0, 0), (-1, -1), 1, colors.HexColor('#FDE68A')),
        ('PADDING', (0, 0), (-1, -1), 10),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ]))
    story.append(Spacer(1, 10))
    story.append(season_table)
    story.append(PageBreak())

    # =========================================================================
    # SLIDE 4: 3. KATALOG PRODUK & LAYANAN MULTI-KATEGORI
    # =========================================================================
    story.append(Paragraph("3. Portofolio Produk & Layanan Multi-Kategori", style_section_title))
    story.append(Paragraph("Kombinasi layanan lengkap one-stop print center untuk memaksimalkan average order value (AOV) tiap pelanggan.", style_section_subtitle))

    slide4_data = [
        [
            Paragraph("""
            <b>1. DIGITAL PRINT & DOKUMEN</b><br/>
            <font size='7.5' color='#64748B'><i>Mesin Digital Laser A3+ High-Definition</i></font><br/><br/>
            • <b>Print Lembaran A3+:</b> Art Paper (150-260g), Ivory, Matte Paper, Kraft, Linen, Concorde.<br/>
            • <b>Packaging & Retail Print:</b> Label stiker chromo/vinyl kiss-cut, kartu nama, flyer, brosur lipat.<br/>
            • <b>Jilid & Finishing:</b> Hardcover skripsi mewah berlogo emas, softcover laminasi doff/glossy, jilid spiral kawat, booklet jahitan tengah.<br/>
            • <b>Dokumen Korporasi:</b> Buku panduan, proposal tender, sertifikat berhologram anti-pemalsuan.
            """, style_card_body),
            Paragraph("""
            <b>2. LARGE FORMAT & OUTDOOR</b><br/>
            <font size='7.5' color='#64748B'><i>Plotter Eco-Solvent / UV 1.8m – 3.2m</i></font><br/><br/>
            • <b>Spanduk & Banner:</b> Flexi China 280g/340g, Flexi Korea 440g, Flexi Jerman (anti robek tahan cuaca).<br/>
            • <b>Media Indoor Premium:</b> Albatros matte, Luster Silk berkilau, Duratrans backlit neon box.<br/>
            • <b>Sticker & Decal:</b> Stiker vinyl Ritrama/Orajet laminasi, stiker one-way vision kaca mobil/toko.<br/>
            • <b>Display Hardware:</b> X-Banner, Y-Banner, Roll-up Aluminium, Event Desk, Tripod Poster, Plang Toko.
            """, style_card_body),
            Paragraph("""
            <b>3. MERCHANDISE & PACKAGING</b><br/>
            <font size='7.5' color='#64748B'><i>Sablon Digital DTF, UV Flatbed, Press</i></font><br/><br/>
            • <b>Apparel Sablon DTF:</b> Kaos katun combed 24s/30s, polo shirt, jaket hoodie, totebag canvas, topi.<br/>
            • <b>Souvenir & Drinkware:</b> Tumbler vacuum flask gravir/print UV, mug keramik sublim, gantungan kunci akrilik.<br/>
            • <b>Office Accessories:</b> Tali lanyard printing satin stopper, ID card PVC tebal (mirip kartu ATM).<br/>
            • <b>Kemasan Custom:</b> Box kardus corrugated cetak sablon, paper box makanan, stempel otomatis kilat.
            """, style_card_body),
        ]
    ]

    slide4_table = Table(slide4_data, colWidths=[245, 245, 260])
    slide4_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, 0), SLATE_50),
        ('BACKGROUND', (1, 0), (1, 0), BLUE_LIGHT),
        ('BACKGROUND', (2, 0), (2, 0), EMERALD_LIGHT),
        ('BOX', (0, 0), (0, 0), 1, SLATE_200),
        ('BOX', (1, 0), (1, 0), 1, BLUE_BORDER),
        ('BOX', (2, 0), (2, 0), 1, colors.HexColor('#6EE7B7')),
        ('PADDING', (0, 0), (-1, -1), 11),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ]))
    story.append(slide4_table)

    margin_cards = [
        [
            Paragraph("<b>Gross Margin Digital A3+:</b> <font color='#1D4ED8'><b>60% – 70%</b></font>", style_card_body),
            Paragraph("<b>Gross Margin Banner / Outdoor:</b> <font color='#059669'><b>50% – 60%</b></font>", style_card_body),
            Paragraph("<b>Gross Margin Merchandise DTF:</b> <font color='#7C3AED'><b>65% – 75%</b></font>", style_card_body),
        ]
    ]
    margin_table = Table(margin_cards, colWidths=[245, 245, 260])
    margin_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), WHITE),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 1, SLATE_200),
        ('PADDING', (0, 0), (-1, -1), 8),
        ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ]))
    story.append(Spacer(1, 10))
    story.append(margin_table)
    story.append(PageBreak())

    # =========================================================================
    # SLIDE 5: 4. SKEMA SISTEM & TEKNOLOGI PENDUKUNG (SNAPPRINT ERP)
    # =========================================================================
    story.append(Paragraph("4. Ekosistem Teknologi: SnapPrint Cloud ERP", style_section_title))
    story.append(Paragraph("Semua operasional toko diotomasi oleh sistem software ERP terintegrasi: nol kebocoran kas, akurasi stok 100%.", style_section_subtitle))

    erp_modules = [
        [
            Paragraph("""
            <b>1. SMART POS & AUTO-CALCULATOR</b><br/><br/>
            • <b>Kalkulasi Otomatis Luas & Satuan:</b> Kasir cukup menginput panjang $\\times$ lebar dan memilih bahan; sistem otomatis menghitung HPP dan harga jual tanpa risiko salah hitung.<br/>
            • <b>Tiering Wholesale Otomatis:</b> Diskon kuantiti dinamis untuk pelanggan partai besar.<br/>
            • <b>Pembayaran Multi-Metode:</b> QRIS, Transfer Bank, Tunai, dan Down Payment (DP min 50%) dengan sistem Piutang terdata otomatis.
            """, style_card_body),
            Paragraph("""
            <b>2. INVENTORY & WORK ORDER MANAGEMENT</b><br/><br/>
            • <b>Live Stock Decrement:</b> Stok kertas lembaran, meteran banner, dan tinta berkurang otomatis per pesanan cetak selesai.<br/>
            • <b>Stock Opname Digital:</b> Verifikasi fisik stok berkala dengan pencatatan selisih otomatis.<br/>
            • <b>Antrean Cetak Mesin (Work Order):</b> Desainer & operator melihat antrean status cetak secara realtime di layar produksi.
            """, style_card_body),
        ],
        [
            Paragraph("""
            <b>3. CENTRAL PURCHASING & BILLS</b><br/><br/>
            • <b>Purchase Plan Bundle:</b> Pengajuan rencana belanja multi-produk cabang ke Owner via sistem RFQ.<br/>
            • <b>Tagihan Supplier & Rekening Otomatis:</b> Rincian rekening bank vendor dan nominal tagihan muncul otomatis saat di-ACC Owner.<br/>
            • <b>Pemeriksaan Gudang (GRN):</b> Verifikasi fisik kedatangan barang sebelum stok ditambahkan.
            """, style_card_body),
            Paragraph("""
            <b>4. EXECUTIVE FINANCIAL DASHBOARD</b><br/><br/>
            • <b>Laporan Laba Rugi Real-Time:</b> Pendapatan harian dikurangi HPP bahan dan beban operasional.<br/>
            • <b>Mutasi Kas/Bank (COA Akuntansi):</b> Setiap rupiah keluar/masuk tercatat ke buku kas besar.<br/>
            • <b>Mobile Accessibility:</b> Owner dapat memantau performa toko, menyetujui PO, dan memeriksa omset dari mana saja.
            """, style_card_body),
        ]
    ]

    erp_table = Table(erp_modules, colWidths=[365, 385])
    erp_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), WHITE),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 1, SLATE_200),
        ('PADDING', (0, 0), (-1, -1), 10),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ]))
    story.append(erp_table)
    story.append(PageBreak())

    # =========================================================================
    # SLIDE 6: 5. PILIHAN PAKET KEMITRAAN & ALOKASI DANA INVESTASI
    # =========================================================================
    story.append(Paragraph("5. Pilihan Paket Kemitraan & Rincian Investasi", style_section_title))
    story.append(Paragraph("Struktur investasi transparan dengan masa lisensi 5 tahun, mesin bergaransi resmi, dan perlengkapan siap jalan.", style_section_subtitle))

    packages_data = [
        [
            Paragraph("<b>TIER 1: COMPACT EXPRESS</b><br/><font size='7.5' color='#64748B'>Format Kios Kampus / Ruko Mini</font>", style_card_title),
            Paragraph("<b>TIER 2: STANDARD STUDIO [POPULAR]</b><br/><font size='7.5' color='#1D4ED8'><b>Paling Populer & Ideal</b></font>", style_card_title),
            Paragraph("<b>TIER 3: FULL PRODUCTION HUB</b><br/><font size='7.5' color='#64748B'>Pusat Produksi Wilayah / B2B</font>", style_card_title),
        ],
        [
            Paragraph("<font size='13' color='#1D4ED8'><b>Rp 120.000.000</b></font>", style_card_body),
            Paragraph("<font size='13' color='#059669'><b>Rp 250.000.000</b></font>", style_card_body),
            Paragraph("<font size='13' color='#7C3AED'><b>Rp 450.000.000</b></font>", style_card_body),
        ],
        [
            Paragraph("""
            <b>Alokasi Hardware & Mesin:</b><br/>
            • Mesin Digital Laser A3+ High Speed<br/>
            • Mesin Pemotong Kertas Elektrik 45cm<br/>
            • Mesin Jilid Kawat & Hardcover Kit<br/>
            • Mesin Laminasi Panas Roll A3+<br/>
            • 1 PC Kasir + 1 PC Desain RIP Station<br/><br/>
            <b>Fasilitas Kemitraan:</b><br/>
            • Lisensi Brand SnapPrint (5 Tahun)<br/>
            • Fit-out Branding & Neon Box Kios<br/>
            • Bahan Baku Awal (Kertas & Toner Rp 15 Juta)<br/>
            • Setup SnapPrint Cloud ERP POS
            """, style_card_bullet),
            Paragraph("""
            <b>Alokasi Hardware & Mesin:</b><br/>
            • Mesin Digital Laser A3+ Production Unit<br/>
            • <b>Plotter Eco-Solvent 1.8m (Banner Outdoor)</b><br/>
            • Mesin Cutting Sticker Otomatis 60cm<br/>
            • Mesin Press Sublim & DTF Apparel<br/>
            • Mesin Jilid Hardcover + Pemotong Kertas<br/>
            • 1 PC Kasir + 2 PC Desain/RIP Station<br/><br/>
            <b>Fasilitas Kemitraan:</b><br/>
            • Lisensi Brand SnapPrint (5 Tahun)<br/>
            • Interior Toko, Meja Kasir & Signage<br/>
            • Bahan Baku Awal Komplit (Rp 30 Juta)<br/>
            • Training Intensif & Marketing Launching
            """, style_card_bullet),
            Paragraph("""
            <b>Alokasi Hardware & Mesin:</b><br/>
            • High-End Production Digital Press A3+<br/>
            • <b>Plotter Outdoor 3.2m + Plotter UV 1.8m</b><br/>
            • Mesin Sablon DTF Roll-to-Roll Auto-Shake<br/>
            • High-Precision Flatbed Die-Cutter Box<br/>
            • Mesin Laminating Heavy Duty & Bending<br/>
            • 1 Server + 4 Workstation Desain/RIP<br/><br/>
            <b>Fasilitas Kemitraan:</b><br/>
            • Lisensi Hub Regional (5 Tahun)<br/>
            • Branding Total Toko & Booth Display<br/>
            • Bahan Baku Awal Skala Besar (Rp 60 Juta)<br/>
            • Prioritas Tender B2B Pusat
            """, style_card_bullet),
        ]
    ]

    packages_table = Table(packages_data, colWidths=[245, 245, 260])
    packages_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, 1), SLATE_50),
        ('BACKGROUND', (1, 0), (1, 1), BLUE_LIGHT),
        ('BACKGROUND', (2, 0), (2, 1), SLATE_50),
        ('BACKGROUND', (0, 2), (0, 2), WHITE),
        ('BACKGROUND', (1, 2), (1, 2), colors.HexColor('#F8FAFC')),
        ('BACKGROUND', (2, 2), (2, 2), WHITE),
        ('BOX', (0, 0), (0, -1), 1, SLATE_200),
        ('BOX', (1, 0), (1, -1), 2, BLUE_PRIMARY),
        ('BOX', (2, 0), (2, -1), 1, SLATE_200),
        ('PADDING', (0, 0), (-1, -1), 8),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ]))
    story.append(packages_table)
    story.append(PageBreak())

    # =========================================================================
    # SLIDE 7: 6. PROYEKSI FINANSIAL & ANALISIS BALIK MODAL (ROI 34% P.A.)
    # =========================================================================
    story.append(Paragraph("6. Proyeksi Finansial & Analisis Balik Modal (ROI 34% p.a.)", style_section_title))
    story.append(Paragraph("Simulasi cash flow riil berbasis performa cabang SnapPrint Standard Studio (Investasi Rp 250 Juta).", style_section_subtitle))

    fin_table_data = [
        [
            Paragraph("<b>KOMPONEN LAPORAN KEUANGAN (BULANAN)</b>", style_table_header),
            Paragraph("<b>KONSERVATIF</b>", style_table_header),
            Paragraph("<b>MODERAT (REALISTIS)</b>", style_table_header),
            Paragraph("<b>AGRESIF (OPTIMAL)</b>", style_table_header),
        ],
        [
            Paragraph("<b>Rata-rata Omset Penjualan (Revenue)</b>", style_card_body),
            Paragraph("Rp 45.000.000", style_card_body),
            Paragraph("<b>Rp 65.000.000</b>", style_card_body),
            Paragraph("Rp 90.000.000", style_card_body),
        ],
        [
            Paragraph("Harga Pokok Penjualan / Bahan Baku (COGS ~40%)", style_card_body),
            Paragraph("(Rp 18.000.000)", style_card_body),
            Paragraph("(Rp 26.000.000)", style_card_body),
            Paragraph("(Rp 36.000.000)", style_card_body),
        ],
        [
            Paragraph("<b>Laba Kotor (Gross Profit ~60%)</b>", style_card_body),
            Paragraph("Rp 27.000.000", style_card_body),
            Paragraph("<b>Rp 39.000.000</b>", style_card_body),
            Paragraph("Rp 54.000.000", style_card_body),
        ],
        [
            Paragraph("Biaya Sewa Tempat (Amortisasi bulanan)", style_card_body),
            Paragraph("(Rp 4.500.000)", style_card_body),
            Paragraph("(Rp 4.500.000)", style_card_body),
            Paragraph("(Rp 5.500.000)", style_card_body),
        ],
        [
            Paragraph("Gaji SDM (Kasir, Operator Mesin, Desainer Grafis)", style_card_body),
            Paragraph("(Rp 9.500.000)", style_card_body),
            Paragraph("(Rp 12.000.000)", style_card_body),
            Paragraph("(Rp 15.000.000)", style_card_body),
        ],
        [
            Paragraph("Listrik Daya Besar, Internet & Utilitas", style_card_body),
            Paragraph("(Rp 3.200.000)", style_card_body),
            Paragraph("(Rp 4.000.000)", style_card_body),
            Paragraph("(Rp 5.200.000)", style_card_body),
        ],
        [
            Paragraph("Royalty & Cloud ERP Maintenance Fee (5%)", style_card_body),
            Paragraph("(Rp 2.250.000)", style_card_body),
            Paragraph("(Rp 3.250.000)", style_card_body),
            Paragraph("(Rp 4.500.000)", style_card_body),
        ],
        [
            Paragraph("Cadangan Maintenance & Sparepart Mesin", style_card_body),
            Paragraph("(Rp 1.000.000)", style_card_body),
            Paragraph("(Rp 1.500.000)", style_card_body),
            Paragraph("(Rp 2.000.000)", style_card_body),
        ],
        [
            Paragraph("<b>LABA BERSIH BULANAN (NET PROFIT / EBITDA)</b>", style_card_title),
            Paragraph("<font color='#1D4ED8'><b>Rp 6.550.000</b></font>", style_card_body),
            Paragraph("<font color='#059669' size='10'><b>Rp 7.083.333</b></font>", style_card_body),
            Paragraph("<font color='#7C3AED'><b>Rp 17.800.000</b></font>", style_card_body),
        ],
        [
            Paragraph("<b>PROYEKSI LABA BERSIH TAHUNAN</b>", style_card_title),
            Paragraph("Rp 78.600.000 / thn", style_card_body),
            Paragraph("<font color='#059669'><b>Rp 85.000.000 / thn</b></font>", style_card_body),
            Paragraph("Rp 213.600.000 / thn", style_card_body),
        ],
        [
            Paragraph("<b>ANNUAL RETURN ON INVESTMENT (ROI)</b>", style_card_title),
            Paragraph("31.4% / thn", style_card_body),
            Paragraph("<font color='#059669' size='11'><b>34.0% / TAHUN</b></font>", style_card_body),
            Paragraph("85.4% / thn", style_card_body),
        ],
        [
            Paragraph("<b>ESTIMASI PAYBACK PERIOD (BALIK MODAL)</b>", style_card_title),
            Paragraph("38 Bulan (~3.1 Thn)", style_card_body),
            Paragraph("<font color='#059669'><b>35 Bulan (~2.9 Thn)</b></font>", style_card_body),
            Paragraph("14 Bulan (~1.2 Thn)", style_card_body),
        ]
    ]

    fin_table = Table(fin_table_data, colWidths=[290, 150, 165, 145])
    fin_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), NAVY_DARK),
        ('BACKGROUND', (2, 1), (2, -1), EMERALD_LIGHT),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 0.5, SLATE_200),
        ('LINEBELOW', (0, 3), (-1, 3), 1.5, SLATE_700),
        ('LINEBELOW', (0, 9), (-1, 9), 1.5, SLATE_700),
        ('PADDING', (0, 0), (-1, -1), 4.5),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ]))
    story.append(fin_table)

    bep_note = [
        [
            Paragraph("<b>BREAK EVEN POINT (BEP) OPERASIONAL:</b> Titik impas berada pada omset <b>Rp 42.083.333 / bulan</b> (hanya butuh ~18 order banner + 40 lembar A3+ per hari untuk mencapai titik aman operasional).", style_card_body)
        ]
    ]
    bep_table = Table(bep_note, colWidths=[750])
    bep_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), BLUE_LIGHT),
        ('BOX', (0, 0), (-1, -1), 1, BLUE_BORDER),
        ('PADDING', (0, 0), (-1, -1), 6),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ]))
    story.append(Spacer(1, 6))
    story.append(bep_table)
    story.append(PageBreak())

    # =========================================================================
    # SLIDE 8: 7. DUKUNGAN FRANCHISOR MENYELURUH (FRANCHISOR SUPPORT)
    # =========================================================================
    story.append(Paragraph("7. Dukungan Franchisor Menyeluruh (Franchisor Support)", style_section_title))
    story.append(Paragraph("Mitra didampingi secara komprehensif mulai dari persiapan, rekrutmen SDM, hingga operasional harian.", style_section_subtitle))

    slide7_support = [
        [
            Paragraph("""
            <b>1. PELATIHAN SDM & ACADEMY (14 HARI)</b><br/><br/>
            • <b>Operator Mesin:</b> SOP kalibrasi warna, perawatan harian printhead, setting ICC profile media cetak, dan penanganan problem kertas/tinta.<br/>
            • <b>Desainer Grafis:</b> Prepress check (CMYK vs RGB, resolusi 300 DPI, bleed potong), software RIP otomatis, dan katalog template desain siap pakai.<br/>
            • <b>Kasir / Front Officer:</b> Pelatihan software SnapPrint ERP POS, upselling paket jilid, kalkulasi pesanan kustom, dan etika ramah melayani pelanggan.
            """, style_card_body),
            Paragraph("""
            <b>2. MARKETING NASIONAL & LOKAL</b><br/><br/>
            • <b>Optimasi Digital Lokal:</b> Setup Google Business Profile (Google Maps) dengan SEO lokal agar outlet menduduki ranking #1 pencarian percetakan di wilayah sekitar.<br/>
            • <b>Kampanye Digital Terarah:</b> Iklan berbayar Meta Ads (Instagram/FB) & TikTok radius 5–10 km di sekitar outlet mitra pada masa grand opening.<br/>
            • <b>Materi Pemasaran Siap Pakai:</b> Brosur fisik, katalog harga B2B, spanduk opening, dan konten media sosial berkala dari tim kreatif pusat.
            """, style_card_body),
        ],
        [
            Paragraph("""
            <b>3. TEKNISI & MAINTENANCE BERKALA</b><br/><br/>
            • <b>Preventive Maintenance:</b> Kunjungan audit dan servis berkala mesin setiap bulan oleh tim teknisi bersertifikasi pusat.<br/>
            • <b>Emergency Response Hotline:</b> Bantuan teknis cepat & penyediaan mesin cadangan (backup) jika terjadi kendala produksi mendesak.<br/>
            • <b>Jaminan Suku Cadang Resmi:</b> Ketersediaan printhead, motor servo, belt, dan modul elektronik asli dengan harga khusus mitra.
            """, style_card_body),
            Paragraph("""
            <b>4. SUPPLY CHAIN & JAMINAN HARGA</b><br/><br/>
            • <b>Gudang Bahan Baku Terpusat:</b> Kepastian suplai kertas A3+, banner flexi, albatros, sticker vinyl, tinta, dan blank merchandise tanpa putus.<br/>
            • <b>Harga Beli Skala Distributor:</b> Mitra mendapatkan harga modal tier-1 sehingga profit margin tetap tinggi meski bersaing ketat di pasar lokal.<br/>
            • <b>Pemesanan Otomatis di ERP:</b> Restok bahan dilakukan langsung via menu Purchase Plan di sistem ERP tanpa repot manual.
            """, style_card_body),
        ]
    ]

    slide7_table = Table(slide7_support, colWidths=[365, 385])
    slide7_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), WHITE),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 1, SLATE_200),
        ('PADDING', (0, 0), (-1, -1), 10),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ]))
    story.append(slide7_table)
    story.append(PageBreak())

    # =========================================================================
    # SLIDE 9: 8. KRITERIA LOKASI & ROADMAP KEMITRAAN
    # =========================================================================
    story.append(Paragraph("8. Kriteria Lokasi & Alur Kemitraan (Roadmap)", style_section_title))
    story.append(Paragraph("Tahapan terstruktur menuju Grand Opening outlet SnapPrint dalam waktu 30–45 hari kerja.", style_section_subtitle))

    slide8_data = [
        [
            Paragraph("""
            <b>SPESIFIKASI LOKASI OUTLET IDEAL:</b><br/><br/>
            • <b>Luas Bangunan:</b> Min. 30 m² – 60 m² (Lebar muka ruko minimal 4 meter agar display mesin & kasir leluasa).<br/>
            • <b>Kebutuhan Daya Listrik:</b><br/>
              - Paket Compact: Min. 5.500 VA (1 Phase stabil)<br/>
              - Paket Standard: Min. 7.700 VA – 11.000 VA<br/>
              - Paket Full Hub: Min. 16.500 VA (3 Phase)<br/>
            • <b>Karakteristik Wilayah:</b> Terletak di jalan arteri/kolektor ramai, dekat pusat perkantoran/perbankan, sentra niaga UMKM, atau kawasan pendidikan/kampus.<br/>
            • <b>Aksesibilitas:</b> Memiliki area parkir motor & mobil yang memadai untuk bongkar muat bahan dan kenyamanan pelanggan.
            """, style_card_body),
            Paragraph("""
            <b>6-STEP ONBOARDING ROADMAP MENUJU OPENING:</b><br/><br/>
            <b>Step 1: Pendaftaran & Diskusi Awal</b><br/>
            Pengisian formulir minat kemitraan dan seleksi profil calon mitra.<br/><br/>
            <b>Step 2: Survei Lokasi & Studi Kelayakan</b><br/>
            Tim ekspansi pusat melakukan analisis traffic, kompetitor, dan daya beli.<br/><br/>
            <b>Step 3: Penandatanganan MoU & Pelunasan Capex</b><br/>
            Perjanjian resmi kemitraan franchise berjangka 5 tahun.<br/><br/>
            <b>Step 4: Renovasi, Branding & Pengiriman Mesin</b><br/>
            Pemasangan signage toko, interior counter, instalasi kelistrikan & mesin.<br/><br/>
            <b>Step 5: Training SDM & Integrasi SnapPrint ERP</b><br/>
            Pelatihan teknis intensif operator, desainer, kasir, serta uji coba produksi.<br/><br/>
            <b>Step 6: Grand Opening & Launching Promo</b><br/>
            Eksekusi kampanye iklan digital lokal, promo cetak perdana, & operasional penuh!
            """, style_card_body),
        ]
    ]

    slide8_table = Table(slide8_data, colWidths=[330, 420])
    slide8_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, 0), SLATE_50),
        ('BACKGROUND', (1, 0), (1, 0), WHITE),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 1, SLATE_200),
        ('PADDING', (0, 0), (-1, -1), 10),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ]))
    story.append(slide8_table)

    # CTA Contact Banner
    cta_data = [
        [
            Paragraph("""
            <div align="center">
                <font color="#FFFFFF" size="11"><b>MARI BERGABUNG MENJADI BAGIAN DARI KELUARGA BESAR SNAPPRINT DIGITAL PRINTING!</b></font><br/>
                <font color="#93C5FD" size="8.5">Konsultasikan pilihan paket kemitraan dan jadwal survei lokasi bersama Tim Business Expansion kami:</font><br/>
                <font color="#FBBF24" size="9"><b>WhatsApp: +62 812-9988-7766   •   Email: franchise@snaprint.co.id   •   Website: www.snaprint.co.id</b></font>
            </div>
            """, style_card_body)
        ]
    ]
    cta_table = Table(cta_data, colWidths=[750])
    cta_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), NAVY_DARK),
        ('BOX', (0, 0), (-1, -1), 1, NAVY_CARD),
        ('PADDING', (0, 0), (-1, -1), 10),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('ROUNDEDCORNERS', [8, 8, 8, 8]),
    ]))
    story.append(Spacer(1, 8))
    story.append(cta_table)

    doc.build(story, canvasmaker=NumberedCanvas)

    # Copy to artifacts directory
    os.system(f"cp '{OUTPUT_PDF}' '{ARTIFACT_PDF}'")
    
    # Re-render PNG images for artifact preview
    doc_fitz = fitz.open(OUTPUT_PDF)
    for i, page in enumerate(doc_fitz):
        pix = page.get_pixmap(dpi=150)
        pix.save(os.path.join(ARTIFACT_DIR, f'slide_{i+1}.png'))

    print(f"Successfully generated Franchise Pitch Deck PDF & slide previews at:\n1. {OUTPUT_PDF}\n2. {ARTIFACT_PDF}")

if __name__ == '__main__':
    build_pdf()
