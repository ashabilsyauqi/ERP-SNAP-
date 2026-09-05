<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CashTransaction;
use App\Models\Transaction;
use App\Models\Branch;
use App\Models\ProfitLossArchive;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ProfitLossController extends Controller
{
    /**
     * Compute financial statement metrics according to unified formula:
     * - Omzet: POS daily/period revenue
     * - HPP: Material cost + Click charge
     * - Gross Profit: Omzet - HPP
     * - OPEX: Cash Outflow (Kas Keluar)
     * - Net Profit: Gross Profit - OPEX
     */
    protected function computeProfitLossData(Request $request): array
    {
        $user = Auth::user();
        $periodType = $request->input('period_type', 'monthly'); // daily, monthly, yearly, custom
        
        $cashQuery = CashTransaction::with('account');
        $salesQuery = Transaction::query()->whereNotIn('order_status', ['draft', 'cancelled']);

        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();

        if (!$isOwnerOrSuper) {
            $branchId = $user->branch_id;
        } else {
            if ($request->has('branch_id')) {
                $branchId = $request->input('branch_id');
                session(['selected_branch_id' => $branchId]);
            } else {
                $branchId = session('selected_branch_id', 'all');
            }
        }

        if ($branchId && $branchId !== 'all') {
            $cashQuery->where('branch_id', $branchId);
            $salesQuery->where('branch_id', $branchId);
        }

        $periodLabel = '';
        $startDate = null;
        $endDate = null;

        if ($periodType === 'daily') {
            $date = $request->input('date', Carbon::today()->toDateString());
            $startDate = $date;
            $endDate = $date;
            $cashQuery->whereDate('tanggal', $date);
            $salesQuery->whereDate('created_at', $date);
            $periodLabel = Carbon::parse($date)->translatedFormat('d F Y');
        } elseif ($periodType === 'yearly') {
            $year = (int) $request->input('year', Carbon::now()->year);
            $startDate = Carbon::createFromDate($year, 1, 1)->toDateString();
            $endDate = Carbon::createFromDate($year, 12, 31)->toDateString();
            $cashQuery->whereYear('tanggal', $year);
            $salesQuery->whereYear('created_at', $year);
            $periodLabel = 'Tahun ' . $year;
        } elseif ($periodType === 'custom') {
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->input('end_date', Carbon::now()->toDateString());
            $cashQuery->whereBetween('tanggal', [$startDate, $endDate]);
            $salesQuery->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
            $periodLabel = Carbon::parse($startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y');
        } else {
            // Default: monthly
            $periodType = 'monthly';
            $month = (int) $request->input('month', Carbon::now()->month);
            $year = (int) $request->input('year', Carbon::now()->year);
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
            $cashQuery->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
            $salesQuery->whereMonth('created_at', $month)->whereYear('created_at', $year);
            $periodLabel = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        }

        $cashTransactions = $cashQuery->get();
        $totalSalesFromTrx = (float) (clone $salesQuery)->sum('total_price');
        $totalHppFromTrx = (float) (clone $salesQuery)->sum('total_hpp');

        // 1. PENDAPATAN USAHA (OMZET POS)
        $pendapatan = collect();
        $totalPendapatan = 0;
        
        if ($totalSalesFromTrx > 0) {
            $pendapatan->push((object)[
                'nama_akun' => 'Pendapatan Penjualan Kasir POS',
                'jumlah' => $totalSalesFromTrx
            ]);
            $totalPendapatan += $totalSalesFromTrx;
        }

        // Tambahan Pendapatan Kas Masuk Non-POS (misal Pendapatan Lain-lain)
        $otherIncome = $cashTransactions->where('tipe', 'masuk')
            ->whereNull('transaction_id')
            ->filter(function($t) { 
                return $t->account && $t->account->tipe === 'pendapatan' && $t->account->kode_akun !== '4-1000'; 
            })
            ->groupBy('account.nama_akun');
            
        foreach ($otherIncome as $akun => $trx) {
            $sum = (float) $trx->sum('jumlah');
            $pendapatan->push((object)['nama_akun' => $akun, 'jumlah' => $sum]);
            $totalPendapatan += $sum;
        }

        if ($totalPendapatan == 0) {
            $generalIncome = (float) $cashTransactions->where('tipe', 'masuk')
                ->filter(function($t) { return $t->account && $t->account->tipe === 'pendapatan'; })
                ->sum('jumlah');
            if ($generalIncome > 0) {
                $pendapatan->push((object)['nama_akun' => 'Pendapatan Kas Masuk Usaha', 'jumlah' => $generalIncome]);
                $totalPendapatan += $generalIncome;
            }
        }

        // 2. HPP (HARGA POKOK PENJUALAN / BAHAN & CLICK CHARGE)
        $hpp = collect();
        $totalHpp = 0;
        
        if ($totalHppFromTrx > 0) {
            $hpp->push((object)[
                'nama_akun' => 'HPP Penjualan (Harga Modal Bahan & Click Charge Mesin)',
                'jumlah' => $totalHppFromTrx
            ]);
            $totalHpp += $totalHppFromTrx;
        } else {
            $hppTrx = $cashTransactions->where('tipe', 'keluar')
                ->filter(function($t) { return $t->account && $t->account->kode_akun === '6-1000'; })
                ->groupBy('account.nama_akun');
                
            foreach ($hppTrx as $akun => $trx) {
                $sum = (float) $trx->sum('jumlah');
                $hpp->push((object)['nama_akun' => $akun, 'jumlah' => $sum]);
                $totalHpp += $sum;
            }
        }

        // Gross Profit (Laba Kotor = Omzet - HPP)
        $labaKotor = $totalPendapatan - $totalHpp;

        // 3. BEBAN OPERASIONAL (OPEX / KAS KELUAR)
        $bebanOperasional = collect();
        $totalBebanOperasional = 0;
        
        $bebanTrx = $cashTransactions->where('tipe', 'keluar')
            ->filter(function($t) { 
                // Exclude manual 6-1000 to prevent double counting HPP
                return !$t->account || $t->account->kode_akun !== '6-1000'; 
            })
            ->groupBy(function($t) {
                return $t->account->nama_akun ?? 'Beban Operasional Kas Keluar';
            });
            
        foreach ($bebanTrx as $akun => $trx) {
            $sum = (float) $trx->sum('jumlah');
            $bebanOperasional->push((object)['nama_akun' => $akun, 'jumlah' => $sum]);
            $totalBebanOperasional += $sum;
        }

        // Net Profit (Laba Bersih = Gross Profit - OPEX)
        $labaBersih = $labaKotor - $totalBebanOperasional;

        $branches = Branch::orderBy('nama_cabang')->get();
        $currentBranch = ($branchId && $branchId !== 'all') ? $branches->firstWhere('id', $branchId) : null;
        $branchName = $currentBranch ? $currentBranch->nama_cabang : 'Semua Cabang (Konsolidasi Enterprise)';

        return compact(
            'pendapatan', 'totalPendapatan',
            'hpp', 'totalHpp',
            'labaKotor',
            'bebanOperasional', 'totalBebanOperasional',
            'labaBersih',
            'periodType', 'periodLabel', 'startDate', 'endDate',
            'branches', 'branchId', 'branchName'
        );
    }

    /**
     * Display the interactive Profit and Loss Statement with Archive Table.
     */
    public function index(Request $request)
    {
        $data = $this->computeProfitLossData($request);

        // Fetch past archives with branch scoping
        $archiveQuery = ProfitLossArchive::with(['branch', 'user'])->orderBy('created_at', 'desc');
        if ($data['branchId'] && $data['branchId'] !== 'all') {
            $archiveQuery->where('branch_id', $data['branchId']);
        }

        $archives = $archiveQuery->paginate(10);
        $data['archives'] = $archives;

        return view('reports.profit-loss', $data);
    }

    /**
     * Export Profit and Loss Statement as instant downloadable PDF.
     */
    public function exportPdf(Request $request)
    {
        $data = $this->computeProfitLossData($request);

        $safePeriod = Str::slug($data['periodLabel'], '_');
        $safeBranch = Str::slug($data['branchName'], '_');
        $filename = "Laporan_Laba_Rugi_{$safePeriod}_{$safeBranch}.pdf";

        $pdf = Pdf::loadView('reports.pdf.profit-loss', $data)
            ->setPaper('a4', 'portrait')
            ->setOption([
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

        return $pdf->download($filename);
    }

    /**
     * Save current Profit and Loss snapshot as an Archive and store PDF on disk.
     */
    public function storeArchive(Request $request)
    {
        $data = $this->computeProfitLossData($request);

        $safePeriod = Str::slug($data['periodLabel'], '_');
        $safeBranch = Str::slug($data['branchName'], '_');
        $timestamp = Carbon::now()->format('Ymd_His');
        $filename = "Laba_Rugi_{$safePeriod}_{$safeBranch}_{$timestamp}.pdf";
        $relativeDir = 'archives/profit_loss';
        $fullPath = storage_path("app/public/{$relativeDir}");

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $pdf = Pdf::loadView('reports.pdf.profit-loss', $data)
            ->setPaper('a4', 'portrait')
            ->setOption([
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

        // Save PDF file to storage
        $savedPath = "{$relativeDir}/{$filename}";
        Storage::disk('public')->put($savedPath, $pdf->output());

        // Create archive database entry
        ProfitLossArchive::create([
            'branch_id' => ($data['branchId'] && $data['branchId'] !== 'all') ? $data['branchId'] : null,
            'user_id' => Auth::id(),
            'period_type' => $data['periodType'],
            'period_label' => $data['periodLabel'],
            'start_date' => $data['startDate'],
            'end_date' => $data['endDate'],
            'total_omzet' => $data['totalPendapatan'],
            'total_hpp' => $data['totalHpp'],
            'gross_profit' => $data['labaKotor'],
            'total_opex' => $data['totalBebanOperasional'],
            'net_profit' => $data['labaBersih'],
            'pdf_filename' => $filename,
            'pdf_path' => $savedPath,
            'notes' => $request->input('notes', 'Arsip Laporan Laba Rugi Otomatis'),
        ]);

        return redirect()->route('reports.profit-loss', $request->all())
            ->with('success', "Laporan Laba Rugi periode {$data['periodLabel']} berhasil dirangkum dalam PDF dan disimpan ke arsip!");
    }

    /**
     * Download an archived PDF file.
     */
    public function downloadArchive($id)
    {
        $archive = ProfitLossArchive::findOrFail($id);

        if (!Storage::disk('public')->exists($archive->pdf_path)) {
            return redirect()->back()->with('error', 'Berkas fisik PDF arsip tidak ditemukan di server.');
        }

        return Storage::disk('public')->download($archive->pdf_path, $archive->pdf_filename);
    }

    /**
     * Delete an archived report.
     */
    public function destroyArchive($id)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isSuperAdmin() && !$user->isManager()) {
            abort(403, 'Anda tidak berwenang menghapus arsip laporan keuangan.');
        }

        $archive = ProfitLossArchive::findOrFail($id);

        if ($archive->pdf_path && Storage::disk('public')->exists($archive->pdf_path)) {
            Storage::disk('public')->delete($archive->pdf_path);
        }

        $archive->delete();

        return redirect()->back()->with('success', 'Arsip Laporan Laba Rugi berhasil dihapus.');
    }
}
