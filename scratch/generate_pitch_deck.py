import os
from reportlab.lib.pagesizes import landscape, A4
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, Image
)
from reportlab.pdfgen import canvas
import fitz

# === CMYK & CREATIVE PRINTING PALETTE ===
CYAN = colors.HexColor('#00A3E0')       # Vivid Cyan
CYAN_LIGHT = colors.HexColor('#E0F2FE')
CYAN_DARK = colors.HexColor('#0284C7')

MAGENTA = colors.HexColor('#E11D48')    # Vivid Magenta / Rose
MAGENTA_LIGHT = colors.HexColor('#FFE4E6')
MAGENTA_DARK = colors.HexColor('#BE123C')

YELLOW = colors.HexColor('#F59E0B')     # Warm Print Yellow
YELLOW_LIGHT = colors.HexColor('#FEF3C7')

KEY_DARK = colors.HexColor('#0B1120')   # Deep Midnight Navy / Black
KEY_SURFACE = colors.HexColor('#1E293B')# Slate Card Dark
KEY_LIGHT = colors.HexColor('#F8FAFC')  # Crisp Off-White

EMERALD = colors.HexColor('#10B981')    # Profit Emerald
EMERALD_LIGHT = colors.HexColor('#D1FAE5')
PURPLE = colors.HexColor('#8B5CF6')     # Tech Violet

SLATE_800 = colors.HexColor('#1E293B')
SLATE_600 = colors.HexColor('#475569')
SLATE_400 = colors.HexColor('#94A3B8')
SLATE_200 = colors.HexColor('#E2E8F0')
SLATE_100 = colors.HexColor('#F1F5F9')
WHITE = colors.HexColor('#FFFFFF')

PAGE_WIDTH, PAGE_HEIGHT = landscape(A4) # 841.89 x 595.27 pt
LOGO_PATH = '/Users/kingashabil/Desktop/Skirpsi/public/images/logosnaprint.jpeg'
OUTPUT_PDF = '/Users/kingashabil/Desktop/Skirpsi/public/SnapPrint_Franchise_Pitch_Deck.pdf'
ARTIFACT_DIR = '/Users/kingashabil/.gemini/antigravity/brain/80681852-77f3-4bf7-8c4f-4ff7a3e38580'
ARTIFACT_PDF = os.path.join(ARTIFACT_DIR, 'SnapPrint_Franchise_Pitch_Deck.pdf')

class CreativePrintCanvas(canvas.Canvas):
    """Custom canvas that draws CMYK bars, registration marks, crop marks, and sleek headers."""
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

    def draw_cmyk_bar(self, x, y, width=64, height=6):
        """Draws 4-color CMYK test patch"""
        w4 = width / 4.0
        self.setFillColor(CYAN)
        self.rect(x, y, w4, height, fill=True, stroke=False)
        self.setFillColor(MAGENTA)
        self.rect(x + w4, y, w4, height, fill=True, stroke=False)
        self.setFillColor(YELLOW)
        self.rect(x + w4*2, y, w4, height, fill=True, stroke=False)
        self.setFillColor(KEY_DARK)
        self.rect(x + w4*3, y, w4, height, fill=True, stroke=False)

    def draw_registration_mark(self, x, y, r=5):
        """Draws a printer registration crosshair mark"""
        self.setStrokeColor(SLATE_400)
        self.setLineWidth(0.6)
        self.circle(x, y, r, stroke=True, fill=False)
        self.circle(x, y, r*0.5, stroke=True, fill=False)
        self.line(x - r - 3, y, x + r + 3, y)
        self.line(x, y - r - 3, x, y + r + 3)

    def draw_decorations(self, page_count):
        if self._pageNumber == 1:
            # Cover Slide: Creative dark background decorations
            # Top gradient accent bar
            self.setFillColor(CYAN)
            self.rect(0, PAGE_HEIGHT - 6, PAGE_WIDTH / 3, 6, fill=True, stroke=False)
            self.setFillColor(MAGENTA)
            self.rect(PAGE_WIDTH / 3, PAGE_HEIGHT - 6, PAGE_WIDTH / 3, 6, fill=True, stroke=False)
            self.setFillColor(YELLOW)
            self.rect((PAGE_WIDTH / 3) * 2, PAGE_HEIGHT - 6, PAGE_WIDTH / 3, 6, fill=True, stroke=False)

            # Registration marks at corners
            self.draw_registration_mark(25, PAGE_HEIGHT - 25, 6)
            self.draw_registration_mark(PAGE_WIDTH - 25, PAGE_HEIGHT - 25, 6)
            self.draw_registration_mark(25, 25, 6)
            self.draw_registration_mark(PAGE_WIDTH - 25, 25, 6)

            # CMYK bottom bar
            self.draw_cmyk_bar(PAGE_WIDTH / 2 - 40, 16, width=80, height=7)
            return

        # Inner Slides Header
        # Top CMYK thin accent bar
        self.setFillColor(CYAN)
        self.rect(0, PAGE_HEIGHT - 4, PAGE_WIDTH / 4, 4, fill=True, stroke=False)
        self.setFillColor(MAGENTA)
        self.rect(PAGE_WIDTH / 4, PAGE_HEIGHT - 4, PAGE_WIDTH / 4, 4, fill=True, stroke=False)
        self.setFillColor(YELLOW)
        self.rect((PAGE_WIDTH / 4) * 2, PAGE_HEIGHT - 4, PAGE_WIDTH / 4, 4, fill=True, stroke=False)
        self.setFillColor(KEY_DARK)
        self.rect((PAGE_WIDTH / 4) * 3, PAGE_HEIGHT - 4, PAGE_WIDTH / 4, 4, fill=True, stroke=False)

        # Header Logo & Subtitle
        if os.path.exists(LOGO_PATH):
            try:
                self.drawImage(LOGO_PATH, 38, PAGE_HEIGHT - 34, width=22, height=22, preserveAspectRatio=True, mask='auto')
            except Exception:
                pass
        
        self.setFont("Helvetica-Bold", 10.5)
        self.setFillColor(KEY_DARK)
        self.drawString(66, PAGE_HEIGHT - 27, "SNAPPRINT")
        
        self.setFont("Helvetica-Bold", 8)
        self.setFillColor(WHITE)
        # Digital Print Badge
        self.setFillColor(CYAN)
        self.roundRect(138, PAGE_HEIGHT - 32, 70, 14, 3, fill=True, stroke=False)
        self.setFillColor(WHITE)
        self.drawString(143, PAGE_HEIGHT - 27, "DIGITAL PRINT")

        self.setFont("Helvetica", 8.5)
        self.setFillColor(SLATE_600)
        self.drawString(218, PAGE_HEIGHT - 27, "•  Franchise & Business Pitch Deck 2026")

        # Top Right CMYK patch & confidential badge
        self.draw_cmyk_bar(PAGE_WIDTH - 190, PAGE_HEIGHT - 28, width=45, height=6)
        self.draw_registration_mark(PAGE_WIDTH - 130, PAGE_HEIGHT - 25, 4.5)

        self.setFillColor(KEY_DARK)
        self.roundRect(PAGE_WIDTH - 115, PAGE_HEIGHT - 33, 75, 15, 3, fill=True, stroke=False)
        self.setFillColor(YELLOW)
        self.setFont("Helvetica-Bold", 7.5)
        self.drawString(PAGE_WIDTH - 108, PAGE_HEIGHT - 27, "CONFIDENTIAL")

        # Top Divider Line
        self.setStrokeColor(SLATE_200)
        self.setLineWidth(0.75)
        self.line(36, PAGE_HEIGHT - 39, PAGE_WIDTH - 36, PAGE_HEIGHT - 39)

        # Bottom Footer Divider & Text
        self.line(36, 32, PAGE_WIDTH - 36, 32)
        
        self.setFont("Helvetica", 8)
        self.setFillColor(SLATE_600)
        self.drawString(38, 18, "PT SNAPPRINT DIGITAL NUSANTARA   •   www.snaprint.co.id   •   Sistem Ekosistem ERP Terintegrasi")

        # Bottom Right Page Number in Badge
        self.setFillColor(SLATE_100)
        self.roundRect(PAGE_WIDTH - 110, 12, 72, 16, 4, fill=True, stroke=False)
        self.setFont("Helvetica-Bold", 8)
        self.setFillColor(KEY_DARK)
        page_str = f"Slide {self._pageNumber} / {page_count}"
        self.drawRightString(PAGE_WIDTH - 48, 18, page_str)

def build_pdf():
    doc = SimpleDocTemplate(
        OUTPUT_PDF,
        pagesize=landscape(A4),
        leftMargin=36,
        rightMargin=36,
        topMargin=46,
        bottomMargin=40
    )

    # Typography Styles
    style_cover_title = ParagraphStyle(
        'CoverTitle',
        fontName='Helvetica-Bold',
        fontSize=27,
        leading=33,
        textColor=WHITE
    )
    style_cover_desc = ParagraphStyle(
        'CoverDesc',
        fontName='Helvetica',
        fontSize=9,
        leading=13,
        textColor=colors.HexColor('#CBD5E1')
    )
    style_section_title = ParagraphStyle(
        'SectionTitle',
        fontName='Helvetica-Bold',
        fontSize=15,
        leading=18,
        textColor=KEY_DARK,
        spaceAfter=2
    )
    style_section_subtitle = ParagraphStyle(
        'SectionSubtitle',
        fontName='Helvetica',
        fontSize=9,
        leading=12,
        textColor=SLATE_600,
        spaceAfter=7
    )
    style_card_title = ParagraphStyle(
        'CardTitle',
        fontName='Helvetica-Bold',
        fontSize=10,
        leading=13,
        textColor=KEY_DARK
    )
    style_table_header = ParagraphStyle(
        'TableHeader',
        fontName='Helvetica-Bold',
        fontSize=8.5,
        leading=11.5,
        textColor=WHITE
    )
    style_card_body = ParagraphStyle(
        'CardBody',
        fontName='Helvetica',
        fontSize=8,
        leading=11.5,
        textColor=SLATE_800
    )
    style_card_bullet = ParagraphStyle(
        'CardBullet',
        fontName='Helvetica',
        fontSize=7.8,
        leading=11,
        textColor=SLATE_800
    )

    story = []

    # =========================================================================
    # SLIDE 1: COVER SLIDE (CREATIVE CMYK DIGITAL PRINTING VIBE)
    # =========================================================================
    logo_img = Image(LOGO_PATH, width=75, height=75) if os.path.exists(LOGO_PATH) else Paragraph("", style_cover_desc)
    
    right_box_data = [
        [logo_img],
        [Paragraph("<font color='#38BDF8' size='10.5'><b>PT SNAPPRINT DIGITAL NUSANTARA</b></font>", ParagraphStyle('R1', fontName='Helvetica-Bold', alignment=1, textColor=WHITE))],
        [Paragraph("<font color='#94A3B8' size='8'>Legalitas Badan Usaha Resmi • Standar ISO 9001 SOP</font>", ParagraphStyle('R2', fontName='Helvetica', alignment=1, textColor=SLATE_400))],
        [Spacer(1, 4)],
        [Paragraph("<font color='#10B981' size='9'><b>✓ RETURN ON INVESTMENT 34% / TAHUN</b></font>", ParagraphStyle('R3', fontName='Helvetica-Bold', alignment=1))],
        [Paragraph("<font color='#38BDF8' size='8.5'><b>✓ INTEGRASI SNAPPRINT CLOUD ERP</b></font>", ParagraphStyle('R4', fontName='Helvetica-Bold', alignment=1))],
        [Paragraph("<font color='#F43F5E' size='8.5'><b>✓ JARINGAN SUPPLY CHAIN PUSAT</b></font>", ParagraphStyle('R5', fontName='Helvetica-Bold', alignment=1))],
    ]
    right_box_table = Table(right_box_data, colWidths=[250])
    right_box_table.setStyle(TableStyle([
        ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('PADDING', (0, 0), (-1, -1), 2),
    ]))

    cover_data = [
        [
            Paragraph("""
            <font color="#38BDF8"><b>SNAPPRINT DIGITAL PRINTING • BUSINESS PROPOSAL 2026</b></font><br/><br/>
            <b>PENAWARAN KEMITRAAN FRANCHISE</b><br/>
            <font size="16" color="#FACC15">Ekosistem Percetakan Modern Berbasis Smart ERP</font><br/><br/>
            <font color="#E2E8F0" size="9.5">Solusi bisnis percetakan komersial & retail generasi baru dengan otomasi kalkulasi harga instan, rantai pasok terpusat, dan <b>Return on Investment (ROI) teruji 34% per tahun</b>.</font>
            """, style_cover_title),
            right_box_table
        ]
    ]

    cover_table = Table(cover_data, colWidths=[495, 270])
    cover_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), KEY_DARK),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('PADDING', (0, 0), (-1, -1), 18),
        ('ROUNDEDCORNERS', [14, 14, 14, 14]),
    ]))

    # Bottom metric highlight badges with vibrant CMYK accents
    metric_cards = [
        [
            Paragraph("<font color='#0284C7'><b>TARGET BALIK MODAL</b></font><br/><font size='13' color='#0F172A'><b>~ 2.9 Tahun</b></font><br/><font size='7' color='#64748B'>35 Bulan (Arus Kas Positif Bln-3)</font>", style_card_body),
            Paragraph("<font color='#059669'><b>ANNUAL NET ROI</b></font><br/><font size='13' color='#059669'><b>34.0% / Tahun</b></font><br/><font size='7' color='#64748B'>Laba Bersih Rp 85 Jt/Thn (Moderat)</font>", style_card_body),
            Paragraph("<font color='#8B5CF6'><b>SNAPPRINT SMART ERP</b></font><br/><font size='13' color='#0F172A'><b>100% Terintegrasi</b></font><br/><font size='7' color='#64748B'>POS, Antrean Cetak, Jurnal COA</font>", style_card_body),
            Paragraph("<font color='#E11D48'><b>CUSTOMER RETENTION</b></font><br/><font size='13' color='#E11D48'><b>72% Repeat Order</b></font><br/><font size='7' color='#64748B'>Pelanggan Komersial & UMKM Lokal</font>", style_card_body),
        ]
    ]
    metric_table = Table(metric_cards, colWidths=[188, 188, 188, 188])
    metric_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, 0), CYAN_LIGHT),
        ('BACKGROUND', (1, 0), (1, 0), EMERALD_LIGHT),
        ('BACKGROUND', (2, 0), (2, 0), colors.HexColor('#F3E8FF')),
        ('BACKGROUND', (3, 0), (3, 0), MAGENTA_LIGHT),
        ('BOX', (0, 0), (0, 0), 1, CYAN),
        ('BOX', (1, 0), (1, 0), 1, EMERALD),
        ('BOX', (2, 0), (2, 0), 1, PURPLE),
        ('BOX', (3, 0), (3, 0), 1, MAGENTA),
        ('PADDING', (0, 0), (-1, -1), 9),
        ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ]))

    story.append(cover_table)
    story.append(Spacer(1, 10))
    story.append(metric_table)
    story.append(PageBreak())

    # =========================================================================
    # SLIDE 2: 1. EXECUTIVE SUMMARY & NILAI UNIK BRAND (USP)
    # =========================================================================
    story.append(Paragraph("1. Executive Summary & Nilai Unik Brand (USP)", style_section_title))
    story.append(Paragraph("Fondasi bisnis solid, legalitas PT resmi, dan keunggulan kompetitif dibanding percetakan ritel konvensional.", style_section_subtitle))

    slide2_data = [
        [
            Paragraph("""
            <font color="#0284C7"><b>■ PROFIL BRAND & VISI PERUSAHAAN</b></font><br/><br/>
            <b>SnapPrint</b> adalah jaringan modern digital printing & merchandise hub yang menghadirkan pengalaman cetak cepat, presisi tinggi, dan transparan bagi pelanggan ritel maupun korporasi.<br/><br/>
            • <b>Visi:</b> Menjadi jaringan percetakan digital terdepan di Indonesia yang terstandardisasi melalui otomasi teknologi ERP dan kepuasan pelanggan prima.<br/>
            • <b>Misi:</b> Menyediakan solusi cetak dokumen, promosi, dan kemasan dengan harga kompetitif, QC terjaga, dan SLA pengerjaan tercepat.<br/>
            • <b>Legalitas:</b> PT SnapPrint Digital Nusantara (NIB, NPWP Badan, Hak Cipta Merek Kemenkumham terdaftar resmi).
            """, style_card_body),
            Paragraph("""
            <font color="#E11D48"><b>■ UNIQUE SELLING PROPOSITION (USP)</b></font><br/><br/>
            Keunggulan mutlak SnapPrint dibanding percetakan lokal konvensional:<br/><br/>
            <b>1. SnapPrint Cloud ERP & Auto-Quote:</b><br/>
            Kalkulasi harga otomatis instan per meter persegi atau per lembar, antrean cetak digital (Work Order), dan eliminasi salah hitung kasir.<br/><br/>
            <b>2. Jaminan Garansi Cetak 100% (QC SLA):</b><br/>
            Garansi reprint gratis bila hasil cetak cacat warna / salah potong demi loyalitas pelanggan korporat tanpa kompromi.<br/><br/>
            <b>3. Centralized Supply Chain (Harga Pabrik):</b><br/>
            Pasokan bahan baku kertas, vinyl, tinta, dan blank merchandise langsung dari distributor tier-1 dengan margin laba kotor 55%–65%.
            """, style_card_body)
        ]
    ]

    slide2_table = Table(slide2_data, colWidths=[370, 395])
    slide2_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, 0), KEY_LIGHT),
        ('BACKGROUND', (1, 0), (1, 0), CYAN_LIGHT),
        ('BOX', (0, 0), (0, 0), 1, SLATE_200),
        ('BOX', (1, 0), (1, 0), 1.5, CYAN_DARK),
        ('PADDING', (0, 0), (-1, -1), 13),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('ROUNDEDCORNERS', [8, 8, 8, 8]),
    ]))
    story.append(slide2_table)

    summary_pillars = [
        [
            Paragraph("<font color='#0284C7'><b>⚡ 1. Kecepatan Layanan (SLA Kilat)</b></font><br/><font size='7.5' color='#475569'>Print A3+ & Banner kilat 15-30 menit selesai berkat alur file RIP otomatis.</font>", style_card_body),
            Paragraph("<font color='#059669'><b>📱 2. Akuntansi & Kas Terpadu</b></font><br/><font size='7.5' color='#475569'>Owner memantau omset, mutasi kas/bank COA, dan laba bersih dari smartphone.</font>", style_card_body),
            Paragraph("<font color='#8B5CF6'><b>🛡️ 3. Proteksi Wilayah Eksklusif</b></font><br/><font size='7.5' color='#475569'>Radius proteksi teritori kemitraan min. 3-5 km antar cabang outlet SnapPrint.</font>", style_card_body),
        ]
    ]
    summary_table = Table(summary_pillars, colWidths=[248, 248, 269])
    summary_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), WHITE),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 1, SLATE_200),
        ('PADDING', (0, 0), (-1, -1), 9),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ]))
    story.append(Spacer(1, 8))
    story.append(summary_table)
    story.append(PageBreak())

    # =========================================================================
    # SLIDE 3: 2. PELUANG PASAR & TARGET KONSUMEN
    # =========================================================================
    story.append(Paragraph("2. Peluang Pasar & Karakteristik Konsumen", style_section_title))
    story.append(Paragraph("Permintaan cetak yang berkelanjutan dengan basis repeat order tinggi di sektor komersial dan ritel.", style_section_subtitle))

    slide3_cards = [
        [
            Paragraph("""
            <font color="#0284C7"><b>SEKTOR B2B & KORPORAT</b></font><br/>
            <font size="11" color="#0F172A"><b>45% REVENUE SHARE</b></font><br/><br/>
            • <b>UMKM & Brand Lokal:</b> Stiker label kemasan, standing pouch, paper bag, kartu nama, hangtag baju.<br/>
            • <b>Perkantoran & Institusi:</b> Kop surat, amplop, map folder, form continuous, brosur company profile.<br/>
            • <b>Sifat Belanja:</b> Volume besar, repeat order rutin 2–4 kali per bulan, sensitif terhadap ketepatan waktu.
            """, style_card_body),
            Paragraph("""
            <font color="#D97706"><b>SEKTOR EVENT & PENDIDIKAN</b></font><br/>
            <font size="11" color="#0F172A"><b>30% REVENUE SHARE</b></font><br/><br/>
            • <b>Event Organizer & Komunitas:</b> Backdrop, roll-up banner, photobooth, wristband, lanyard, ID card.<br/>
            • <b>Kampus & Sekolah:</b> Cetak modul, jilid skripsi kilat, sertifikat berhologram, buku tahunan siswa.<br/>
            • <b>Sifat Belanja:</b> Musiman dengan lonjakan omset signifikan (*seasonal peak*) pada masa kelulusan & event.
            """, style_card_body),
            Paragraph("""
            <font color="#E11D48"><b>SEKTOR B2C / RETAIL</b></font><br/>
            <font size="11" color="#0F172A"><b>25% REVENUE SHARE</b></font><br/><br/>
            • <b>Personal & Freelancer:</b> Print foto, cetak dokumen PDF, sablon kaos satuan DTF, tumbler kustom.<br/>
            • <b>Keluarga & Pernikahan:</b> Undangan pernikahan, souvenir mug, banner ucapan selamat, stempel flash.<br/>
            • <b>Sifat Belanja:</b> Margin retail tebal (markup 60%–70%), pembayaran tunai/QRIS instan di kasir.
            """, style_card_body),
        ]
    ]

    slide3_table = Table(slide3_cards, colWidths=[248, 248, 269])
    slide3_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, 0), CYAN_LIGHT),
        ('BACKGROUND', (1, 0), (1, 0), YELLOW_LIGHT),
        ('BACKGROUND', (2, 0), (2, 0), MAGENTA_LIGHT),
        ('BOX', (0, 0), (0, 0), 1, CYAN),
        ('BOX', (1, 0), (1, 0), 1, YELLOW),
        ('BOX', (2, 0), (2, 0), 1, MAGENTA),
        ('PADDING', (0, 0), (-1, -1), 11),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('ROUNDEDCORNERS', [6, 6, 6, 6]),
    ]))
    story.append(slide3_table)

    season_data = [
        [
            Paragraph("""
            <font color="#92400E"><b>📅 SIKLUS BELANJA REPEAT ORDER TAHUNAN:</b></font><br/>
            • <b>Q1 (Jan–Mar):</b> Laporan tahunan korporat, materi promosi awal tahun, kalender susulan, dan pameran niaga.<br/>
            • <b>Q2 (Apr–Jun):</b> Musim kelulusan sekolah/kampus, skripsi, cetak sertifikat, event seminar, dan merchandise reuni.<br/>
            • <b>Q3 (Jul–Sep):</b> Masa Orientasi Siswa/MABA (lanyard, kaos, booklet), promosi HUT RI (banner & umbul-umbul masif).<br/>
            • <b>Q4 (Okt–Des):</b> Puncak belanja akhir tahun: Kalender meja/dinding, agenda kerja, promo diskon retail, kampanye event.
            """, style_card_body)
        ]
    ]
    season_table = Table(season_data, colWidths=[765])
    season_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), WHITE),
        ('BOX', (0, 0), (-1, -1), 1, colors.HexColor('#FDE68A')),
        ('PADDING', (0, 0), (-1, -1), 9),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ]))
    story.append(Spacer(1, 8))
    story.append(season_table)
    story.append(PageBreak())

    # =========================================================================
    # SLIDE 4: 3. KATALOG PRODUK & LAYANAN MULTI-KATEGORI
    # =========================================================================
    story.append(Paragraph("3. Portofolio Produk & Layanan Multi-Kategori (112 SKU Riil)", style_section_title))
    story.append(Paragraph("Data katalog harga jual & HPP riil terverifikasi dari operasional gerai SnapPrint Dukuh Zamrud dengan average margin 61.7%.", style_section_subtitle))

    slide4_data = [
        [
            Paragraph("""
            <font color="#0284C7"><b>1. DIGITAL PRINT & DOKUMEN</b></font><br/>
            <font size='7' color='#64748B'><i>Laser A3+, HVS, Art Paper, Sticker Kisscut</i></font><br/><br/>
            • <b>Print Lembaran A3+:</b> Art Paper 120/150g, Art Carton 210/260g, Blues White, Linen.<br/>
            • <b>Sticker Label:</b> Chromo & Vinyl Glossy/Doff/Transparan (Tiering grosir s/d Rp 5.000).<br/>
            • <b>Jilid & Finishing:</b> Hardcover skripsi mewah emas, softcover laminasi, kawat spiral No 5–20.<br/>
            • <b>Dokumen Korporasi:</b> Modul materi, fotokopi A4/A3 1-2 sisi, scan resolusi tinggi.
            """, style_card_body),
            Paragraph("""
            <font color="#D97706"><b>2. LARGE FORMAT & OUTDOOR</b></font><br/>
            <font size='7' color='#64748B'><i>Plotter Eco-Solvent / UV 1.8m – 3.2m</i></font><br/><br/>
            • <b>Spanduk & Banner:</b> Flexy China 280g/340g, Flexy Korea 440g tahan cuaca luar ruangan.<br/>
            • <b>Media Indoor Premium:</b> Albatros matte halus, Sticker Ritrama & Oneway Vision kaca.<br/>
            • <b>Display Hardware Komplit:</b> Paket X-Banner 60x160cm, Roll Banner 60x160 & 85x200cm.<br/>
            • <b>Media Promosi Usaha:</b> Plang reklame toko, backdrop panggung, mata ayam ring.
            """, style_card_body),
            Paragraph("""
            <font color="#E11D48"><b>3. MERCHANDISE, NOTA & STEMPEL</b></font><br/>
            <font size='7' color='#64748B'><i>Sablon DTF, Lanyard, Stempel Flash, Nota NCR</i></font><br/><br/>
            • <b>Sablon Apparel DTF:</b> Sablon ukuran A4, A3, dan meteran full colour presisi tinggi.<br/>
            • <b>Souvenir & Kantor:</b> Lanyard 2-2.5cm, ID Card PVC, Pin 44/58mm, Mug keramik + box, Kipas.<br/>
            • <b>Nota NCR Berjilid:</b> Nota 1-3 ply ukuran A6, A5, A4 (Full Color & BW Paket Rim).<br/>
            • <b>Stempel Otomatis:</b> Stempel Flash 1-2 warna (Kecil, Sedang, Besar).
            """, style_card_body),
        ]
    ]

    slide4_table = Table(slide4_data, colWidths=[248, 248, 269])
    slide4_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, 0), CYAN_LIGHT),
        ('BACKGROUND', (1, 0), (1, 0), YELLOW_LIGHT),
        ('BACKGROUND', (2, 0), (2, 0), MAGENTA_LIGHT),
        ('BOX', (0, 0), (0, 0), 1, CYAN),
        ('BOX', (1, 0), (1, 0), 1, YELLOW),
        ('BOX', (2, 0), (2, 0), 1, MAGENTA),
        ('PADDING', (0, 0), (-1, -1), 10),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('ROUNDEDCORNERS', [6, 6, 6, 6]),
    ]))
    story.append(slide4_table)

    margin_cards = [
        [
            Paragraph("<b>Margin Print & Stiker (Riil):</b> <font color='#0284C7'><b>75.6%</b></font>", style_card_body),
            Paragraph("<b>Margin Banner & Outdoor (Riil):</b> <font color='#D97706'><b>46.9%</b></font>", style_card_body),
            Paragraph("<b>Margin Merchandise & Nota (Riil):</b> <font color='#E11D48'><b>55.9%</b></font>", style_card_body),
        ]
    ]
    margin_table = Table(margin_cards, colWidths=[248, 248, 269])
    margin_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), WHITE),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 1, SLATE_200),
        ('PADDING', (0, 0), (-1, -1), 8),
        ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ]))
    story.append(Spacer(1, 8))
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
            <font color="#0284C7"><b>1. SMART POS & AUTO-CALCULATOR</b></font><br/><br/>
            • <b>Kalkulasi Otomatis Luas & Satuan:</b> Kasir cukup menginput panjang $\\times$ lebar dan memilih bahan; sistem otomatis menghitung HPP dan harga jual tanpa risiko salah hitung.<br/>
            • <b>Tiering Wholesale Otomatis:</b> Diskon kuantiti dinamis untuk pelanggan partai besar.<br/>
            • <b>Pembayaran Multi-Metode:</b> QRIS, Transfer Bank, Tunai, dan Down Payment (DP min 50%) dengan sistem Piutang terdata otomatis.
            """, style_card_body),
            Paragraph("""
            <font color="#8B5CF6"><b>2. INVENTORY & WORK ORDER MANAGEMENT</b></font><br/><br/>
            • <b>Live Stock Decrement:</b> Stok kertas lembaran, meteran banner, dan tinta berkurang otomatis per pesanan cetak selesai.<br/>
            • <b>Stock Opname Digital:</b> Verifikasi fisik stok berkala dengan pencatatan selisih otomatis.<br/>
            • <b>Antrean Cetak Mesin (Work Order):</b> Desainer & operator melihat antrean status cetak secara realtime di layar produksi.
            """, style_card_body),
        ],
        [
            Paragraph("""
            <font color="#D97706"><b>3. CENTRAL PURCHASING & BILLS</b></font><br/><br/>
            • <b>Purchase Plan Bundle:</b> Pengajuan rencana belanja multi-produk cabang ke Owner via sistem RFQ.<br/>
            • <b>Tagihan Supplier & Rekening Otomatis:</b> Rincian rekening bank vendor dan nominal tagihan muncul otomatis saat di-ACC Owner.<br/>
            • <b>Pemeriksaan Gudang (GRN):</b> Verifikasi fisik kedatangan barang sebelum stok ditambahkan.
            """, style_card_body),
            Paragraph("""
            <font color="#059669"><b>4. EXECUTIVE FINANCIAL DASHBOARD</b></font><br/><br/>
            • <b>Laporan Laba Rugi Real-Time:</b> Pendapatan harian dikurangi HPP bahan dan beban operasional.<br/>
            • <b>Mutasi Kas/Bank (COA Akuntansi):</b> Setiap rupiah keluar/masuk tercatat ke buku kas besar.<br/>
            • <b>Mobile Accessibility:</b> Owner dapat memantau performa toko, menyetujui PO, dan memeriksa omset dari mana saja.
            """, style_card_body),
        ]
    ]

    erp_table = Table(erp_modules, colWidths=[370, 395])
    erp_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), WHITE),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 1, SLATE_200),
        ('PADDING', (0, 0), (-1, -1), 9.5),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('ROUNDEDCORNERS', [6, 6, 6, 6]),
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
            Paragraph("<font color='#0284C7'><b>TIER 1: COMPACT EXPRESS</b></font><br/><font size='7' color='#64748B'>Format Kios Kampus / Ruko Mini</font>", style_card_title),
            Paragraph("<font color='#059669'><b>TIER 2: STANDARD STUDIO [REKOMENDASI]</b></font><br/><font size='7' color='#059669'><b>Paling Populer & Ideal</b></font>", style_card_title),
            Paragraph("<font color='#8B5CF6'><b>TIER 3: FULL PRODUCTION HUB</b></font><br/><font size='7' color='#64748B'>Pusat Produksi Wilayah / B2B</font>", style_card_title),
        ],
        [
            Paragraph("<font size='12' color='#0284C7'><b>Rp 120.000.000</b></font>", style_card_body),
            Paragraph("<font size='12' color='#059669'><b>Rp 250.000.000</b></font>", style_card_body),
            Paragraph("<font size='12' color='#8B5CF6'><b>Rp 450.000.000</b></font>", style_card_body),
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

    packages_table = Table(packages_data, colWidths=[248, 248, 269])
    packages_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, 1), CYAN_LIGHT),
        ('BACKGROUND', (1, 0), (1, 1), EMERALD_LIGHT),
        ('BACKGROUND', (2, 0), (2, 1), colors.HexColor('#F3E8FF')),
        ('BACKGROUND', (0, 2), (0, 2), WHITE),
        ('BACKGROUND', (1, 2), (1, 2), WHITE),
        ('BACKGROUND', (2, 2), (2, 2), WHITE),
        ('BOX', (0, 0), (0, -1), 1, CYAN),
        ('BOX', (1, 0), (1, -1), 2, EMERALD),
        ('BOX', (2, 0), (2, -1), 1, PURPLE),
        ('PADDING', (0, 0), (-1, -1), 7.5),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('ROUNDEDCORNERS', [6, 6, 6, 6]),
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
            Paragraph("<b>MODERAT (REALISTIS) ⭐</b>", style_table_header),
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
            Paragraph("<font color='#0284C7'><b>Rp 6.550.000</b></font>", style_card_body),
            Paragraph("<font color='#059669' size='9.5'><b>Rp 7.083.333</b></font>", style_card_body),
            Paragraph("<font color='#8B5CF6'><b>Rp 17.800.000</b></font>", style_card_body),
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
            Paragraph("<font color='#059669' size='10.5'><b>34.0% / TAHUN</b></font>", style_card_body),
            Paragraph("85.4% / thn", style_card_body),
        ],
        [
            Paragraph("<b>ESTIMASI PAYBACK PERIOD (BALIK MODAL)</b>", style_card_title),
            Paragraph("38 Bulan (~3.1 Thn)", style_card_body),
            Paragraph("<font color='#059669'><b>35 Bulan (~2.9 Thn)</b></font>", style_card_body),
            Paragraph("14 Bulan (~1.2 Thn)", style_card_body),
        ]
    ]

    fin_table = Table(fin_table_data, colWidths=[295, 150, 170, 150])
    fin_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), KEY_DARK),
        ('BACKGROUND', (2, 1), (2, -1), EMERALD_LIGHT),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 0.5, SLATE_200),
        ('LINEBELOW', (0, 3), (-1, 3), 1.5, SLATE_800),
        ('LINEBELOW', (0, 9), (-1, 9), 1.5, SLATE_800),
        ('PADDING', (0, 0), (-1, -1), 4.5),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ]))
    story.append(fin_table)

    bep_note = [
        [
            Paragraph("<b>BREAK EVEN POINT (BEP) OPERASIONAL:</b> Titik impas berada pada omset <b>Rp 42.083.333 / bulan</b> (hanya butuh ~18 order banner + 40 lembar A3+ per hari untuk mencapai titik aman operasional).", style_card_body)
        ]
    ]
    bep_table = Table(bep_note, colWidths=[765])
    bep_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), CYAN_LIGHT),
        ('BOX', (0, 0), (-1, -1), 1, CYAN),
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
            <font color="#0284C7"><b>🎓 1. PELATIHAN SDM & ACADEMY (14 HARI)</b></font><br/><br/>
            • <b>Operator Mesin:</b> SOP kalibrasi warna, perawatan harian printhead, setting ICC profile media cetak, dan penanganan problem kertas/tinta.<br/>
            • <b>Desainer Grafis:</b> Prepress check (CMYK vs RGB, resolusi 300 DPI, bleed potong), software RIP otomatis, dan katalog template desain siap pakai.<br/>
            • <b>Kasir / Front Officer:</b> Pelatihan software SnapPrint ERP POS, upselling paket jilid, kalkulasi pesanan kustom, dan etika ramah melayani pelanggan.
            """, style_card_body),
            Paragraph("""
            <font color="#E11D48"><b>🚀 2. MARKETING NASIONAL & LOKAL</b></font><br/><br/>
            • <b>Optimasi Digital Lokal:</b> Setup Google Business Profile (Google Maps) dengan SEO lokal agar outlet menduduki ranking #1 pencarian percetakan di wilayah sekitar.<br/>
            • <b>Kampanye Digital Terarah:</b> Iklan berbayar Meta Ads (Instagram/FB) & TikTok radius 5–10 km di sekitar outlet mitra pada masa grand opening.<br/>
            • <b>Materi Pemasaran Siap Pakai:</b> Brosur fisik, katalog harga B2B, spanduk opening, dan konten media sosial berkala dari tim kreatif pusat.
            """, style_card_body),
        ],
        [
            Paragraph("""
            <font color="#D97706"><b>🔧 3. TEKNISI & MAINTENANCE BERKALA</b></font><br/><br/>
            • <b>Preventive Maintenance:</b> Kunjungan audit dan servis berkala mesin setiap bulan oleh tim teknisi bersertifikasi pusat.<br/>
            • <b>Emergency Response Hotline:</b> Bantuan teknis cepat & penyediaan mesin cadangan (backup) jika terjadi kendala produksi mendesak.<br/>
            • <b>Jaminan Suku Cadang Resmi:</b> Ketersediaan printhead, motor servo, belt, dan modul elektronik asli dengan harga khusus mitra.
            """, style_card_body),
            Paragraph("""
            <font color="#059669"><b>📦 4. SUPPLY CHAIN & JAMINAN HARGA</b></font><br/><br/>
            • <b>Gudang Bahan Baku Terpusat:</b> Kepastian suplai kertas A3+, banner flexi, albatros, sticker vinyl, tinta, dan blank merchandise tanpa putus.<br/>
            • <b>Harga Beli Skala Distributor:</b> Mitra mendapatkan harga modal tier-1 sehingga profit margin tetap tinggi meski bersaing ketat di pasar lokal.<br/>
            • <b>Pemesanan Otomatis di ERP:</b> Restok bahan dilakukan langsung via menu Purchase Plan di sistem ERP tanpa repot manual.
            """, style_card_body),
        ]
    ]

    slide7_table = Table(slide7_support, colWidths=[370, 395])
    slide7_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), WHITE),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 1, SLATE_200),
        ('PADDING', (0, 0), (-1, -1), 10),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('ROUNDEDCORNERS', [6, 6, 6, 6]),
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
            <font color="#0284C7"><b>■ SPESIFIKASI LOKASI OUTLET IDEAL:</b></font><br/><br/>
            • <b>Luas Bangunan:</b> Min. 30 m² – 60 m² (Lebar muka ruko minimal 4 meter agar display mesin & kasir leluasa).<br/>
            • <b>Kebutuhan Daya Listrik:</b><br/>
              - Paket Compact: Min. 5.500 VA (1 Phase stabil)<br/>
              - Paket Standard: Min. 7.700 VA – 11.000 VA<br/>
              - Paket Full Hub: Min. 16.500 VA (3 Phase)<br/>
            • <b>Karakteristik Wilayah:</b> Terletak di jalan arteri/kolektor ramai, dekat pusat perkantoran/perbankan, sentra niaga UMKM, atau kawasan pendidikan/kampus.<br/>
            • <b>Aksesibilitas:</b> Memiliki area parkir motor & mobil yang memadai untuk bongkar muat bahan dan kenyamanan pelanggan.
            """, style_card_body),
            Paragraph("""
            <font color="#E11D48"><b>■ 6-STEP ONBOARDING ROADMAP MENUJU OPENING:</b></font><br/><br/>
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

    slide8_table = Table(slide8_data, colWidths=[335, 430])
    slide8_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, 0), KEY_LIGHT),
        ('BACKGROUND', (1, 0), (1, 0), WHITE),
        ('BOX', (0, 0), (-1, -1), 1, SLATE_200),
        ('INNERGRID', (0, 0), (-1, -1), 1, SLATE_200),
        ('PADDING', (0, 0), (-1, -1), 10),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('ROUNDEDCORNERS', [6, 6, 6, 6]),
    ]))
    story.append(slide8_table)

    # CTA Contact Banner with Creative CMYK Colors
    cta_data = [
        [
            Paragraph("""
            <div align="center">
                <font color="#FFFFFF" size="11"><b>MARI BERGABUNG MENJADI BAGIAN DARI KELUARGA BESAR SNAPPRINT DIGITAL PRINTING!</b></font><br/>
                <font color="#93C5FD" size="8.5">Konsultasikan pilihan paket kemitraan dan jadwal survei lokasi bersama Tim Business Expansion kami:</font><br/>
                <font color="#FACC15" size="9.5"><b>WhatsApp: +62 812-9988-7766   •   Email: franchise@snaprint.co.id   •   Website: www.snaprint.co.id</b></font>
            </div>
            """, style_card_body)
        ]
    ]
    cta_table = Table(cta_data, colWidths=[765])
    cta_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), KEY_DARK),
        ('BOX', (0, 0), (-1, -1), 1, KEY_SURFACE),
        ('PADDING', (0, 0), (-1, -1), 10),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('ROUNDEDCORNERS', [8, 8, 8, 8]),
    ]))
    story.append(Spacer(1, 8))
    story.append(cta_table)

    doc.build(story, canvasmaker=CreativePrintCanvas)

    # Copy to artifacts directory
    os.system(f"cp '{OUTPUT_PDF}' '{ARTIFACT_PDF}'")
    
    # Re-render PNG images for artifact preview
    doc_fitz = fitz.open(OUTPUT_PDF)
    for i, page in enumerate(doc_fitz):
        pix = page.get_pixmap(dpi=150)
        pix.save(os.path.join(ARTIFACT_DIR, f'slide_{i+1}.png'))

    print(f"Successfully generated CMYK Creative Franchise Pitch Deck PDF & slide previews at:\n1. {OUTPUT_PDF}\n2. {ARTIFACT_PDF}")

if __name__ == '__main__':
    build_pdf()
