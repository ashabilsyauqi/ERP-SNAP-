
        // Guided Tour Engine
        function startGuidedTour() {
            if (typeof driver === 'undefined') return;
            const driverObj = driver.js.driver({
                showProgress: true,
                steps: [
                    { element: '#tour-button', popover: { title: 'Navigasi Snaprint ERP', description: 'Gunakan panduan ini kapan saja untuk melihat fitur di Snaprint ERP.', side: "bottom" } },
                    { element: '.fa-table-cells', popover: { title: 'App Switcher (Home)', description: 'Buka App Matrix full-screen untuk berpindah modul secara cepat.', side: "right" } },
                    { element: '.o_searchview', popover: { title: 'Live Search View', description: 'Filter data tabel secara langsung dengan mengetik kata kunci.', side: "bottom" } }
                ]
            });
            driverObj.drive();
        }

        // Global Table Column Sorting Engine
        document.addEventListener('click', function(e) {
            const th = e.target.closest('th.sortable');
            if (!th) return;

            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr.search-row'));
            if (rows.length === 0) return;

            const thIndex = Array.from(th.parentNode.children).indexOf(th);
            const isAsc = !th.classList.contains('asc');

            table.querySelectorAll('th.sortable').forEach(header => {
                header.classList.remove('asc', 'desc');
                const icon = header.querySelector('.sort-icon');
                if (icon) icon.remove();
            });

            th.classList.toggle('asc', isAsc);
            th.classList.toggle('desc', !isAsc);
            
            const sortIcon = document.createElement('i');
            sortIcon.className = `sort-icon fa-solid fa-arrow-${isAsc ? 'up' : 'down'} text-[10px] text-blue-600 ms-1`;
            th.appendChild(sortIcon);

            rows.sort((rowA, rowB) => {
                const cellA = rowA.children[thIndex]?.innerText.trim() || '';
                const cellB = rowB.children[thIndex]?.innerText.trim() || '';

                const cleanNumA = cellA.replace(/[^0-9.-]+/g, '');
                const cleanNumB = cellB.replace(/[^0-9.-]+/g, '');
                const isNum = cleanNumA !== '' && cleanNumB !== '' && !isNaN(cleanNumA) && !isNaN(cleanNumB);

                if (isNum) {
                    return isAsc ? (parseFloat(cleanNumA) - parseFloat(cleanNumB)) : (parseFloat(cleanNumB) - parseFloat(cleanNumA));
                }
                return isAsc ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });

            rows.forEach(row => tbody.appendChild(row));
        });

        // Global Table Live Search Filter Engine
        document.addEventListener('input', function(e) {
            if (!e.target.classList.contains('table-search-input')) return;
            const query = e.target.value.toLowerCase().trim();
            const wrapper = document.querySelector('[data-view-wrapper]') || document;

            wrapper.querySelectorAll('tr.search-row').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });

            wrapper.querySelectorAll('.search-card').forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        });

        // Global List & Kanban Dual View Mode Switcher
        function toggleViewMode(mode, wrapperId) {
            const wrapper = document.getElementById(wrapperId) || document.querySelector('[data-view-wrapper]');
            if (!wrapper) return;

            const tableView = wrapper.querySelector('.table-view-container');
            const gridView = wrapper.querySelector('.grid-view-container');
            const btnList = document.querySelector('.btn-view-list');
            const btnGrid = document.querySelector('.btn-view-grid');

            if (mode === 'list') {
                if (tableView) tableView.classList.remove('d-none');
                if (gridView) gridView.classList.add('d-none');
                if (btnList) { btnList.classList.add('active', 'text-slate-700'); btnList.classList.remove('text-slate-400'); }
                if (btnGrid) { btnGrid.classList.remove('active', 'text-slate-700'); btnGrid.classList.add('text-slate-400'); }
            } else {
                if (tableView) tableView.classList.add('d-none');
                if (gridView) gridView.classList.remove('d-none');
                if (btnGrid) { btnGrid.classList.add('active', 'text-slate-700'); btnGrid.classList.remove('text-slate-400'); }
                if (btnList) { btnList.classList.remove('active', 'text-slate-700'); btnList.classList.add('text-slate-400'); }
            }
        }

        // Global Excel Export Engine (SheetJS)
        function exportTableToExcel(tableId, filename = 'Snaprint_Export') {
            const table = document.getElementById(tableId);
            if (!table) {
                alert('Tabel tidak ditemukan untuk diekspor.');
                return;
            }
            if (typeof XLSX === 'undefined') {
                alert('Library Excel Export sedang dimuat, silakan coba sesaat lagi.');
                return;
            }
            const wb = XLSX.utils.table_to_book(table, { sheet: "Data" });
            XLSX.writeFile(wb, `${filename}_${new Date().toISOString().slice(0,10)}.xlsx`);
        }

        // Global Invoice Viewer Helper
        window.openSnaprintInvoice = function(invData) {
            window.dispatchEvent(new CustomEvent('open-invoice-modal', { detail: invData }));
        };

        // Global Printable Invoice Generator
        window.printSnaprintInvoice = function(inv) {
            const printWindow = window.open('', '_blank');
            const logoUrl = "http://localhost/images/logosnaprint.jpeg";
            const isPartial = inv.payment_status === 'PARTIAL' || (inv.remaining_amount && inv.remaining_amount > 0);
            
            const itemsHtml = (inv.items && inv.items.length > 0) ? inv.items.map((it, idx) => `
                <tr>
                    <td style="text-align: center; padding: 8px; border: 1px solid #cbd5e1;">${idx + 1}</td>
                    <td style="padding: 8px; border: 1px solid #cbd5e1;">
                        <strong>${it.material_name || it.name || '-'}</strong>
                        ${it.dimension_text ? `<br><small style="color: #1e40af; font-weight: bold;">[${it.dimension_text}]</small>` : ''}
                        ${it.specs ? `<br><small style="color: #64748b;">${it.specs}</small>` : ''}
                    </td>
                    <td style="text-align: center; padding: 8px; border: 1px solid #cbd5e1;">${it.qty_ordered || it.qty || 1}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #cbd5e1; font-family: monospace;">Rp ${Number(it.selling_price || it.price || 0).toLocaleString('id-ID')}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #cbd5e1; font-family: monospace; font-weight: bold;">Rp ${Number(it.subtotal || ((it.qty_ordered || it.qty || 1) * (it.selling_price || it.price || 0))).toLocaleString('id-ID')}</td>
                </tr>
            `).join('') : `
                <tr>
                    <td style="text-align: center; padding: 8px; border: 1px solid #cbd5e1;">1</td>
                    <td style="padding: 8px; border: 1px solid #cbd5e1;"><strong>${inv.keterangan || 'Transaksi Penjualan Kasir POS'}</strong></td>
                    <td style="text-align: center; padding: 8px; border: 1px solid #cbd5e1;">1</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #cbd5e1; font-family: monospace;">Rp ${Number(inv.total_price || 0).toLocaleString('id-ID')}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #cbd5e1; font-family: monospace; font-weight: bold;">Rp ${Number(inv.total_price || 0).toLocaleString('id-ID')}</td>
                </tr>
            `;

            printWindow.document.write(`
                <html>
                <head>
                    <title>Invoice - ${inv.invoice_number || 'Document'}</title>
                    <style>
                        body { font-family: 'Helvetica Neue', Arial, sans-serif; padding: 40px; color: #1e293b; font-size: 13px; }
                        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #1e3a8a; padding-bottom: 15px; margin-bottom: 20px; align-items: center; }
                        .brand-container { display: flex; align-items: center; gap: 14px; }
                        .brand-logo { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; }
                        .brand { font-size: 22px; font-weight: bold; color: #1e3a8a; }
                        .title { font-size: 18px; font-weight: bold; text-align: right; color: #0f172a; }
                        .stamp { display: inline-block; padding: 4px 12px; border: 2px solid ${isPartial ? '#d97706' : '#059669'}; color: ${isPartial ? '#d97706' : '#059669'}; font-weight: 800; border-radius: 6px; text-transform: uppercase; margin-bottom: 8px; font-size: 12px; }
                        .client-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 20px; }
                        .info-table { width: 100%; margin-bottom: 20px; }
                        .info-table td { padding: 4px 0; font-size: 13px; }
                        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                        .items-table th { background: #f1f5f9; padding: 10px; border: 1px solid #cbd5e1; text-align: left; font-size: 12px; }
                        .totals-table { width: 100%; margin-top: 15px; border-collapse: collapse; }
                        .totals-table td { padding: 6px 10px; text-align: right; }
                        .footer { margin-top: 40px; text-align: center; border-top: 1px solid #cbd5e1; padding-top: 15px; font-size: 11px; color: #64748b; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="brand-container">
                            <img src="${logoUrl}" alt="Snaprint" class="brand-logo">
                            <div>
                                <div class="brand">Snaprint</div>
                                <div style="font-size: 12px; color: #64748b;">Digital Printing & Advertising Solutions</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Cabang: <strong>${inv.branch_name || 'Pusat'}</strong></div>
                            </div>
                        </div>
                        <div class="title">
                            <div class="stamp">${isPartial ? '⚠ DP / UANG MUKA' : '✓ PAID (LUNAS)'}</div>
                            <div>FAKTUR / INVOICE ${isPartial ? '& SPK' : ''}</div>
                            <div style="font-size: 12px; font-weight: normal; color: #64748b; font-family: monospace;">No: ${inv.invoice_number || '-'}</div>
                        </div>
                    </div>

                    ${(inv.customer_name || inv.customer_phone || inv.due_date || inv.production_notes) ? `
                    <div class="client-box">
                        <table style="width: 100%; font-size: 12px;">
                            <tr>
                                <td style="width: 50%;"><strong>Client:</strong> ${inv.customer_name || 'Pelanggan Umum'}</td>
                                <td><strong>WhatsApp:</strong> ${inv.customer_phone || '-'}</td>
                            </tr>
                            ${(inv.due_date || inv.production_notes) ? `
                            <tr>
                                <td style="padding-top: 6px;"><strong>Deadline:</strong> ${inv.due_date || '-'}</td>
                                <td style="padding-top: 6px;"><strong>Catatan Produksi:</strong> ${inv.production_notes || '-'}</td>
                            </tr>` : ''}
                        </table>
                    </div>` : ''}

                    <table class="info-table">
                        <tr>
                            <td><strong>Tanggal Transaksi:</strong> ${inv.created_at || '-'}</td>
                            <td style="text-align: right;"><strong>Metode Pembayaran:</strong> ${inv.payment_method || 'Cash'}</td>
                        </tr>
                        <tr>
                            <td><strong>Petugas Kasir:</strong> ${inv.cashier_name || 'Kasir'}</td>
                            <td style="text-align: right;"><strong>Status:</strong> Dokumen Resmi Terverifikasi</td>
                        </tr>
                    </table>

                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="width: 30px; text-align: center;">No</th>
                                <th>Deskripsi Item / Pesanan</th>
                                <th style="width: 80px; text-align: center;">Qty</th>
                                <th style="width: 130px; text-align: right;">Harga Satuan</th>
                                <th style="width: 140px; text-align: right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>

                    <table class="totals-table">
                        <tr>
                            <td colspan="4" style="font-weight: bold;">Total Nilai Pesanan:</td>
                            <td style="font-weight: bold; font-family: monospace; font-size: 15px; color: #1e3a8a; width: 140px;">Rp ${Number(inv.total_price || 0).toLocaleString('id-ID')}</td>
                        </tr>
                        ${isPartial ? `
                        <tr>
                            <td colspan="4" style="font-weight: bold; color: #059669;">Uang Muka (DP) Dibayar:</td>
                            <td style="font-weight: bold; font-family: monospace; color: #059669;">Rp ${Number(inv.paid_amount || 0).toLocaleString('id-ID')}</td>
                        </tr>
                        <tr style="background: #fffbeb;">
                            <td colspan="4" style="font-weight: bold; color: #b45309;">Sisa Piutang (Pelunasan):</td>
                            <td style="font-weight: bold; font-family: monospace; font-size: 15px; color: #b45309;">Rp ${Number(inv.remaining_amount || 0).toLocaleString('id-ID')}</td>
                        </tr>
                        ` : ''}
                    </table>

                    <div class="footer">
                        Terima kasih atas kepercayaan Anda mencetak di Snaprint.<br>
                        <strong>Kunjungi halaman kami: mysnaprint.com</strong> &bull; Snaprint "great spot to print"
                    </div>

                    <script>
                        window.onload = function() { window.print(); }
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        };

        // Universal Dropdown Toggle & Auto-Close Engine
        document.addEventListener('click', function(e) {
            const toggle = e.target.closest('[data-bs-toggle="dropdown"]');
            const isInsideMenu = e.target.closest('.dropdown-menu');

            if (!toggle && !isInsideMenu) {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                    const parentToggle = menu.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
                    if (parentToggle) {
                        parentToggle.setAttribute('aria-expanded', 'false');
                        parentToggle.classList.remove('show');
                    }
                });
                return;
            }

            if (toggle) {
                e.preventDefault();
                e.stopPropagation();
                const dropdownParent = toggle.closest('.dropdown');
                const menu = dropdownParent ? dropdownParent.querySelector('.dropdown-menu') : null;
                if (!menu) return;

                const isShown = menu.classList.contains('show');

                // Close all other open dropdowns
                document.querySelectorAll('.dropdown-menu.show').forEach(m => {
                    if (m !== menu) {
                        m.classList.remove('show');
                        const t = m.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
                        if (t) {
                            t.setAttribute('aria-expanded', 'false');
                            t.classList.remove('show');
                        }
                    }
                });

                if (isShown) {
                    menu.classList.remove('show');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.classList.remove('show');
                } else {
                    menu.classList.add('show');
                    toggle.setAttribute('aria-expanded', 'true');
                    toggle.classList.add('show');
                }
            }
        });
    