@extends('layouts.app')

@section('title', 'Cash Outflow Report')
@section('page-title', 'Laporan Pengeluaran Kas Keluar (Disbursements)')

@section('action-buttons')
<button type="button" onclick="window.print()" class="btn-odoo-primary" title="Cetak Laporan PDF / Print">
    <i class="fa-solid fa-print"></i>
    <span>Print Laporan</span>
</button>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Filter Toolbar -->
    <div class="o_form_sheet mb-3 p-3 bg-white print:hidden">
        <form method="GET" action="{{ route('reports.cash-out') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm">
            </div>
            
            @if(Auth::user()->isOwner())
            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Cabang</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="all" {{ ($branchId ?? 'all') === 'all' ? 'selected' : '' }}>Semua Cabang (Konsolidasi)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ ($branchId ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->nama_cabang }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-12 col-md-2">
                <label class="form-label font-semibold text-slate-700 text-xs uppercase">Pilih Akun Beban</label>
                <select name="account_id" class="form-select form-select-sm">
                    <option value="">Semua Akun Beban</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->nama_akun }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-2">
                <button type="submit" class="btn-odoo-primary w-100 py-1 text-xs">
                    <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Data Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden bg-white print:p-0 print:border-0 print:shadow-none">
        
        <!-- Header Dokumen Cetak (Hanya Tampil Saat Print) -->
        <div class="d-none d-print-block p-4 text-center border-bottom mb-3">
            <h4 class="fw-bold text-slate-900 mb-0 uppercase tracking-wide">SNAPPRINT ERP &bull; PERCETAKAN</h4>
            <h5 class="fw-bold text-rose-800 mb-1">LAPORAN PENGELUARAN KAS KELUAR (DISBURSEMENTS)</h5>
            <p class="text-xs text-slate-500 mb-0">
                Periode: {{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d M Y') : 'Awal' }} s/d {{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d M Y') : 'Sekarang' }}
                @if(($branchId ?? request('branch_id')) && ($branchId ?? request('branch_id')) !== 'all')
                    &bull; Cabang: {{ $branches->firstWhere('id', ($branchId ?? request('branch_id')))->nama_cabang ?? '' }}
                @else
                    &bull; Semua Cabang (Konsolidasi)
                @endif
            </p>
        </div>

        <div class="table-responsive">
            <table class="table table-hover o_list_table mb-0" id="main-table">
                <thead>
                    <tr>
                        <th class="ps-3 sortable">No. Referensi / Tanggal</th>
                        <th class="sortable">Akun Beban & Pengeluaran</th>
                        <th class="sortable">Cabang</th>
                        <th class="sortable">Keterangan / Keperluan</th>
                        <th class="text-center" style="width: 100px;">Bukti Nota</th>
                        <th class="sortable text-end pe-4">Jumlah Keluar (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashTransactions as $trx)
                        <tr class="search-row">
                            <td class="ps-3">
                                <span class="fw-bold font-mono text-rose-700 text-xs">{{ $trx->nomor_referensi }}</span>
                                <div class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($trx->tanggal)->format('d M Y') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-slate-800">{{ $trx->account->nama_akun ?? 'Akun Beban' }}</div>
                                <span class="text-[10px] text-slate-400">{{ $trx->account->kode_akun ?? '' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-slate-100 text-slate-700 border text-[11px] font-normal">
                                    {{ $trx->branch->nama_cabang ?? 'Pusat' }}
                                </span>
                            </td>
                            <td class="text-slate-700 text-xs">
                                {{ $trx->keterangan ?? '-' }}
                            </td>
                            <td class="text-center">
                                @if($trx->bukti_transaksi)
                                    @if($trx->isBuktiPdf())
                                        <a href="{{ $trx->bukti_url }}" target="_blank" class="btn btn-xs btn-outline-danger px-2 py-1 rounded-pill font-bold text-[10px] d-inline-flex align-items-center gap-1 shadow-xs" title="Buka Dokumen PDF">
                                            <i class="fa-solid fa-file-pdf"></i>
                                            <span>PDF</span>
                                        </a>
                                    @else
                                        <button type="button" 
                                                onclick="showReceiptModal('{{ $trx->bukti_url }}', '{{ $trx->nomor_referensi }}', '{{ addslashes($trx->account->nama_akun ?? '') }}', '{{ number_format($trx->jumlah, 0, ',', '.') }}')" 
                                                class="btn btn-xs btn-outline-primary px-2 py-1 rounded-pill font-bold text-[10px] d-inline-flex align-items-center gap-1 shadow-xs" title="Lihat Foto Struk / Nota">
                                            <i class="fa-solid fa-receipt"></i>
                                            <span>Lihat</span>
                                        </button>
                                    @endif
                                @else
                                    <span class="text-slate-300 text-xs font-mono">-</span>
                                @endif
                            </td>
                            <td class="text-end pe-4 font-mono fw-bold text-rose-700 fs-6">
                                - Rp {{ number_format($trx->jumlah, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada data kas keluar pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($cashTransactions) > 0)
                <tfoot>
                    <tr class="fw-bold bg-rose-50 border-top border-rose-200">
                        <td colspan="5" class="ps-3 text-uppercase text-rose-900 fw-bold">TOTAL PENGELUARAN KAS KELUAR</td>
                        <td class="text-end pe-4 font-mono fw-extrabold text-emerald-600 fs-5">- Rp {{ number_format($totalKeluar, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if($cashTransactions->hasPages())
            <div class="p-3 border-top bg-slate-50 d-flex justify-content-between align-items-center">
                <span class="text-xs text-slate-500">Menampilkan {{ $cashTransactions->firstItem() }} - {{ $cashTransactions->lastItem() }} dari {{ $cashTransactions->total() }} data</span>
                <div>{{ $cashTransactions->links('pagination::bootstrap-4') }}</div>
            </div>
        @endif
    </div>
</div>

<!-- Modal Preview Bukti Nota -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-3 border-0 shadow-2xl overflow-hidden">
            <div class="modal-header bg-slate-900 text-white py-3 px-4">
                <div>
                    <h6 class="modal-title font-bold text-sm mb-0 d-flex align-items-center gap-2" id="receiptModalLabel">
                        <i class="fa-solid fa-receipt text-rose-400"></i>
                        <span>Bukti Pengeluaran Kas: <span id="modalRefNo" class="text-rose-300 font-mono"></span></span>
                    </h6>
                    <p class="text-[11px] text-slate-300 mb-0 mt-0.5" id="modalSubTitle"></p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="#" id="modalDownloadBtn" target="_blank" download class="btn btn-sm btn-outline-light text-xs rounded-lg px-2.5 py-1 font-semibold">
                        <i class="fa-solid fa-download me-1"></i> Unduh
                    </a>
                    <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3 bg-slate-100 text-center d-flex align-items-center justify-content-center" style="min-height: 350px; max-height: 80vh; overflow: auto;">
                <img src="" id="receiptImage" alt="Bukti Nota" class="img-fluid rounded-2 shadow-sm border border-slate-300" style="max-height: 72vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<script>
function showReceiptModal(url, ref, account, amount) {
    document.getElementById('modalRefNo').textContent = ref;
    document.getElementById('modalSubTitle').textContent = account + ' • Rp ' + amount;
    document.getElementById('receiptImage').src = url;
    document.getElementById('modalDownloadBtn').href = url;
    const modalEl = document.getElementById('receiptModal');
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}
</script>
@endsection
