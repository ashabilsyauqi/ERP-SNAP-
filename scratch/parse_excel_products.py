import pandas as pd
import json

excel_path = '/Users/kingashabil/Downloads/DAFTAR PRODUK SNAPRINT ZAMRUD.xlsx'
xl = pd.ExcelFile(excel_path)

all_products = []

# 1. Print Dokumen dan Sticker
df1 = xl.parse('Print Dokumen dan Sticker')
# Row 0: Unnamed: 1='No', Unnamed: 2='Nama Produk', Unnamed: 3='Harga Jual', Unnamed: 7='HPP'
# Row 2: Qty columns: Unnamed: 3: '1 - 10 lbr', Unnamed: 4: '11 - 50 lbr', Unnamed: 5: '50 - 200 lbr', Unnamed: 6: '>200 lbr'
for idx in range(3, len(df1)):
    row = df1.iloc[idx]
    name = str(row['Unnamed: 2']).strip() if pd.notna(row['Unnamed: 2']) else ''
    if not name or name == 'nan':
        continue
    
    # HPP
    hpp = row['Unnamed: 7']
    hpp_val = float(hpp) if pd.notna(hpp) and str(hpp).replace('.','').isdigit() else 0.0
    
    # Retail price (1-10 lbr)
    p1 = row['Unnamed: 3']
    retail_price = float(p1) if pd.notna(p1) and str(p1).replace('.','').isdigit() else 0.0
    
    # Tier 2 (11-50 lbr) -> min_qty: 11
    p2 = row['Unnamed: 4']
    tier2 = float(p2) if pd.notna(p2) and str(p2).replace('.','').isdigit() else None
    
    # Tier 3 (50-200 lbr) -> min_qty: 51
    p3 = row['Unnamed: 5']
    tier3 = float(p3) if pd.notna(p3) and str(p3).replace('.','').isdigit() else None
    
    # Tier 4 (>200 lbr) -> min_qty: 201
    p4 = row['Unnamed: 6']
    # Special fix for typo in Excel where 80000 was typed for 8000 on Vinyl
    if pd.notna(p4):
        p4_val = float(p4)
        if p4_val == 80000.0 and retail_price == 11000.0:
            p4_val = 8000.0
        tier4 = p4_val
    else:
        tier4 = None

    wholesale = []
    if tier2 and tier2 < retail_price:
        wholesale.append({'min_qty': 11, 'price': tier2})
    if tier3 and tier3 < (tier2 or retail_price):
        wholesale.append({'min_qty': 51, 'price': tier3})
    if tier4 and tier4 < (tier3 or tier2 or retail_price):
        wholesale.append({'min_qty': 201, 'price': tier4})

    all_products.append({
        'category': 'Print Dokumen dan Sticker',
        'material_name': name,
        'purchase_price': hpp_val,
        'retail_price': retail_price,
        'fixed_size': 1.0,
        'wholesale': wholesale
    })

# 2. Cetak Outdoor dan Indoor
df2 = xl.parse('Cetak Outdoor dan Indoor')
for idx in range(2, len(df2)):
    row = df2.iloc[idx]
    name = str(row['Unnamed: 1']).strip() if pd.notna(row['Unnamed: 1']) else ''
    if not name or name == 'nan' or name == 'X Banner & Roll Banner' or name == 'Bahan':
        continue
    
    price_val = row['Unnamed: 2']
    hpp_val = row['Unnamed: 3']
    
    retail = float(price_val) if pd.notna(price_val) and str(price_val).replace('.','').isdigit() else 0.0
    hpp = float(hpp_val) if pd.notna(hpp_val) and str(hpp_val).replace('.','').isdigit() else 0.0
    
    # If it's banner meteran, fixed_size could be 1.0
    all_products.append({
        'category': 'Cetak Outdoor dan Indoor',
        'material_name': name,
        'purchase_price': hpp,
        'retail_price': retail,
        'fixed_size': 1.0,
        'wholesale': []
    })

# 3. Finishing
df3 = xl.parse('Finishing')
for idx in range(2, len(df3)):
    row = df3.iloc[idx]
    name = str(row['Unnamed: 1']).strip() if pd.notna(row['Unnamed: 1']) else ''
    if not name or name == 'nan' or name == 'Nama Produk':
        continue
    
    price_val = row['Unnamed: 2']
    hpp_val = row['Unnamed: 3']
    
    retail = float(price_val) if pd.notna(price_val) and str(price_val).replace('.','').isdigit() else 0.0
    hpp = float(hpp_val) if pd.notna(hpp_val) and str(hpp_val).replace('.','').isdigit() else 0.0
    
    all_products.append({
        'category': 'Finishing',
        'material_name': name,
        'purchase_price': hpp,
        'retail_price': retail,
        'fixed_size': 1.0,
        'wholesale': []
    })

# 4. Merchandise Custom
df4 = xl.parse('Merchandise Custom')
for idx in range(2, len(df4)):
    row = df4.iloc[idx]
    name = str(row['Unnamed: 1']).strip() if pd.notna(row['Unnamed: 1']) else ''
    if not name or name == 'nan' or name == 'Nama Produk':
        continue
    
    price_val = row['Unnamed: 2']
    min_order_str = str(row['Unnamed: 3']) if pd.notna(row['Unnamed: 3']) else ''
    hpp_val = row['Unnamed: 4']
    
    retail = float(price_val) if pd.notna(price_val) and str(price_val).replace('.','').isdigit() else 0.0
    hpp = float(hpp_val) if pd.notna(hpp_val) and str(hpp_val).replace('.','').isdigit() else 0.0
    
    all_products.append({
        'category': 'Merchandise Custom',
        'material_name': name + (f" ({min_order_str})" if min_order_str and min_order_str != 'nan' else ''),
        'purchase_price': hpp,
        'retail_price': retail,
        'fixed_size': 1.0,
        'wholesale': []
    })

# 5. Stampel
df6 = xl.parse('Stampel')
# Row 1: ['Ukuran', '1 Warna', '2 Warna']
# Row 2: ['Kecil', '50000', '75000']
# Row 3: ['Sedang', '75000', '100000']
# Row 4: ['Besar', '100000', '125000']
stampel_rows = [
    ('Stempel Flash Kecil 1 Warna', 50000, 20000),
    ('Stempel Flash Kecil 2 Warna', 75000, 25000),
    ('Stempel Flash Sedang 1 Warna', 75000, 28000),
    ('Stempel Flash Sedang 2 Warna', 100000, 35000),
    ('Stempel Flash Besar 1 Warna', 100000, 38000),
    ('Stempel Flash Besar 2 Warna', 125000, 45000),
]
for s_name, s_price, s_hpp in stampel_rows:
    all_products.append({
        'category': 'Stampel',
        'material_name': s_name,
        'purchase_price': float(s_hpp),
        'retail_price': float(s_price),
        'fixed_size': 1.0,
        'wholesale': []
    })

# 6. Nota
# Sheet Nota has A6, A5, A4 in 1-4 pcs, 1/2 Rim (5 Buku), 1 Rim (10 Buku)
df_nota = xl.parse('Nota')
nota_items = [
    # A6 Full Color & BW
    ('Nota A6 1 Ply Full Color (1-4 Buku)', 14000, 6000, [{'min_qty': 5, 'price': 12000}, {'min_qty': 10, 'price': 11000}]),
    ('Nota A6 1 Ply Black & White (1-4 Buku)', 11000, 4500, [{'min_qty': 5, 'price': 9000}, {'min_qty': 10, 'price': 8000}]),
    ('Nota A6 2 Ply Full Color (1-4 Buku)', 18000, 8000, [{'min_qty': 5, 'price': 16000}, {'min_qty': 10, 'price': 15000}]),
    ('Nota A6 2 Ply Black & White (1-4 Buku)', 14000, 6000, [{'min_qty': 5, 'price': 12000}, {'min_qty': 10, 'price': 11000}]),
    ('Nota A6 3 Ply Full Color (1-4 Buku)', 22000, 10000, [{'min_qty': 5, 'price': 20000}, {'min_qty': 10, 'price': 19000}]),
    ('Nota A6 3 Ply Black & White (1-4 Buku)', 17000, 7500, [{'min_qty': 5, 'price': 15000}, {'min_qty': 10, 'price': 14000}]),
    
    # A5 Full Color & BW
    ('Nota A5 1 Ply Full Color (1-4 Buku)', 24000, 11000, [{'min_qty': 5, 'price': 22000}, {'min_qty': 10, 'price': 21000}]),
    ('Nota A5 1 Ply Black & White (1-4 Buku)', 18000, 8000, [{'min_qty': 5, 'price': 16000}, {'min_qty': 10, 'price': 15000}]),
    ('Nota A5 2 Ply Full Color (1-4 Buku)', 30000, 14000, [{'min_qty': 5, 'price': 28000}, {'min_qty': 10, 'price': 27000}]),
    ('Nota A5 2 Ply Black & White (1-4 Buku)', 23000, 10500, [{'min_qty': 5, 'price': 21000}, {'min_qty': 10, 'price': 20000}]),
    ('Nota A5 3 Ply Full Color (1-4 Buku)', 35000, 17000, [{'min_qty': 5, 'price': 34000}, {'min_qty': 10, 'price': 33000}]),
    ('Nota A5 3 Ply Black & White (1-4 Buku)', 28000, 13000, [{'min_qty': 5, 'price': 26000}, {'min_qty': 10, 'price': 25000}]),

    # A4 Full Color & BW
    ('Nota A4 1 Ply Full Color (1-4 Buku)', 37000, 17500, [{'min_qty': 5, 'price': 35000}, {'min_qty': 10, 'price': 34000}]),
    ('Nota A4 1 Ply Black & White (1-4 Buku)', 28000, 13500, [{'min_qty': 5, 'price': 27000}, {'min_qty': 10, 'price': 26000}]),
    ('Nota A4 2 Ply Full Color (1-4 Buku)', 52000, 25000, [{'min_qty': 5, 'price': 50000}, {'min_qty': 10, 'price': 49000}]),
    ('Nota A4 2 Ply Black & White (1-4 Buku)', 43000, 21000, [{'min_qty': 5, 'price': 42000}, {'min_qty': 10, 'price': 41000}]),
    ('Nota A4 3 Ply Full Color (1-4 Buku)', 68000, 33000, [{'min_qty': 5, 'price': 66000}, {'min_qty': 10, 'price': 65000}]),
    ('Nota A4 3 Ply Black & White (1-4 Buku)', 59000, 29000, [{'min_qty': 5, 'price': 58000}, {'min_qty': 10, 'price': 57000}]),
]
for n_name, n_retail, n_hpp, n_ws in nota_items:
    all_products.append({
        'category': 'Nota',
        'material_name': n_name,
        'purchase_price': float(n_hpp),
        'retail_price': float(n_retail),
        'fixed_size': 1.0,
        'wholesale': n_ws
    })

# 7. Brosur
brosur_items = [
    ('Brosur A5 1 Sisi (Paket Rim)', 390000, 180000, [
        {'min_qty': 50, 'price': 1400},   # 70rb / 50
        {'min_qty': 100, 'price': 1150},  # 115rb / 100
        {'min_qty': 250, 'price': 920},   # 230rb / 250
        {'min_qty': 500, 'price': 780},   # 390rb / 500
    ]),
    ('Brosur A5 2 Sisi (Paket Rim)', 640000, 290000, [
        {'min_qty': 50, 'price': 2400},   # 120rb / 50
        {'min_qty': 100, 'price': 1800},  # 180rb / 100
        {'min_qty': 250, 'price': 1520},  # 380rb / 250
        {'min_qty': 500, 'price': 1280},  # 640rb / 500
    ]),
    ('Brosur A4 1 Sisi (Paket Rim)', 765000, 350000, [
        {'min_qty': 50, 'price': 2400},   # 120rb / 50
        {'min_qty': 100, 'price': 2000},  # 200rb / 100
        {'min_qty': 250, 'price': 1660},  # 415rb / 250
        {'min_qty': 500, 'price': 1530},  # 765rb / 500
    ]),
    ('Brosur A4 2 Sisi (Paket Rim)', 1265000, 580000, [
        {'min_qty': 50, 'price': 3800},   # 190rb / 50
        {'min_qty': 100, 'price': 3400},  # 340rb / 100
        {'min_qty': 250, 'price': 2760},  # 690rb / 250
        {'min_qty': 500, 'price': 2530},  # 1265rb / 500
    ]),
]
for b_name, b_retail, b_hpp, b_ws in brosur_items:
    all_products.append({
        'category': 'Brosur',
        'material_name': b_name,
        'purchase_price': float(b_hpp),
        'retail_price': float(b_retail),
        'fixed_size': 1.0,
        'wholesale': b_ws
    })

# 8. Tumbler
tumbler_items = [
    ('Tumbler Sakura LED Polosan', 35000, 22000, []),
    ('Tumbler Sakura Non-LED Polosan', 30000, 18000, []),
    ('Tumbler Sakura LED Custom Grafir/Print', 65000, 32000, [{'min_qty': 10, 'price': 55000}]),
    ('Tumbler Sakura Non-LED Custom Grafir/Print', 55000, 27000, [{'min_qty': 10, 'price': 48000}]),
]
for t_name, t_retail, t_hpp, t_ws in tumbler_items:
    all_products.append({
        'category': 'Tumbler',
        'material_name': t_name,
        'purchase_price': float(t_hpp),
        'retail_price': float(t_retail),
        'fixed_size': 1.0,
        'wholesale': t_ws
    })

print(f"Total Unique Products Extracted: {len(all_products)}")

# Output summary by category
cat_counts = {}
for p in all_products:
    c = p['category']
    cat_counts[c] = cat_counts.get(c, 0) + 1

for c, count in cat_counts.items():
    print(f" - {c}: {count} produk")

with open('/Users/kingashabil/Desktop/Skirpsi/scratch/parsed_zamrud_products.json', 'w') as f:
    json.dump(all_products, f, indent=2)

print("Saved to scratch/parsed_zamrud_products.json")
