<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Branch;
use App\Models\Material;
use App\Models\ProductSalesArchive;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductSalesReportController extends Controller
{
    /**
     * Compute product sales and material consumption data
     */
    protected function computeReportData(Request $request): array
    {
        $user = Auth::user();
        $periodType = $request->input('period_type', 'monthly'); // daily, weekly, monthly
        
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

        $branches = Branch::all();
        $selectedBranch = ($branchId && $branchId !== 'all') ? Branch::find($branchId) : null;
        $branchName = $selectedBranch ? $selectedBranch->name : 'Semua Cabang (Konsolidasi)';

        $periodLabel = '';
        $startDate = null;
        $endDate = null;
        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);
        $date = $request->input('date', Carbon::today()->toDateString());

        if ($periodType === 'daily') {
            $startDate = $date;
            $endDate = $date;
            $periodLabel = Carbon::parse($date)->translatedFormat('d F Y');
        } elseif ($periodType === 'weekly') {
            $weekRefDate = $request->input('week_date', Carbon::today()->toDateString());
            $cDate = Carbon::parse($weekRefDate);
            $startDate = $request->input('start_date', $cDate->copy()->startOfWeek()->toDateString());
            $endDate = $request->input('end_date', $cDate->copy()->endOfWeek()->toDateString());
            $periodLabel = Carbon::parse($startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y');
        } else {
            // Default: monthly
            $periodType = 'monthly';
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
            $periodLabel = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        }

        // Query transaction details within period and branch
        $detailsQuery = TransactionDetail::query()
            ->whereHas('transaction', function ($q) use ($startDate, $endDate, $branchId) {
                $q->whereNotIn('order_status', ['draft', 'cancelled'])
                  ->whereBetween('created_at', [
                      Carbon::parse($startDate)->startOfDay(),
                      Carbon::parse($endDate)->endOfDay()
                  ]);
                if ($branchId && $branchId !== 'all') {
                    $q->where('branch_id', $branchId);
                }
            })
            ->with(['transaction', 'material']);

        $transactionDetails = $detailsQuery->get();

        // 1. REKAP PRODUK TERJUAL
        $productsMap = [];
        // 2. REKAP PEMAKAIAN BAHAN BAKU & BIAYA BAHAN
        $materialsMap = [];

        $totalItemsSold = 0;
        $totalOmzet = 0;
        $totalMaterialCost = 0;

        foreach ($transactionDetails as $detail) {
            $material = $detail->material;
            $materialId = $detail->material_id ?: ('custom_' . md5($detail->dimension_text ?? 'unknown'));
            $materialName = $material ? $material->material_name : ($detail->dimension_text ?: 'Item Kasir');
            $category = $material ? ($material->category ?: 'Produk') : 'Percetakan';
            
            $qty = (float) $detail->qty_ordered;
            $sellingPrice = (float) $detail->selling_price;
            $revenue = $qty * $sellingPrice;

            $areaM2 = (float) ($detail->area_m2 ?? 0);
            $purchasePrice = $material ? (float) $material->purchase_price : 0;
            
            // Click charge (machine click cost per unit)
            $clickCharge = ($detail->click_charge !== null) 
                ? (float) $detail->click_charge 
                : ($material ? (float) ($material->click_charge ?? 0) : 0);

            // Calculate cost according to banner (area) vs standard item
            if ($areaM2 > 0) {
                $unitCost = round($areaM2 * $purchasePrice) + $clickCharge;
                $usedAreaOrUnit = $areaM2 * $qty;
                $isAreaBased = true;
            } else {
                $unitCost = $purchasePrice + $clickCharge;
                $usedAreaOrUnit = $qty;
                $isAreaBased = false;
            }

            $itemCost = $unitCost * $qty;
            $itemProfit = $revenue - $itemCost;

            $totalItemsSold += $qty;
            $totalOmzet += $revenue;
            $totalMaterialCost += $itemCost;

            // Product summary grouping
            if (!isset($productsMap[$materialId])) {
                $productsMap[$materialId] = [
                    'material_id' => $detail->material_id,
                    'product_name' => $materialName,
                    'category' => $category,
                    'qty_sold' => 0,
                    'area_sold' => 0,
                    'total_omzet' => 0,
                    'total_material_cost' => 0,
                    'gross_profit' => 0,
                    'is_area_based' => $isAreaBased,
                ];
            }
            $productsMap[$materialId]['qty_sold'] += $qty;
            $productsMap[$materialId]['area_sold'] += ($areaM2 * $qty);
            $productsMap[$materialId]['total_omzet'] += $revenue;
            $productsMap[$materialId]['total_material_cost'] += $itemCost;
            $productsMap[$materialId]['gross_profit'] += $itemProfit;

            // Material usage grouping
            if ($material) {
                if (!isset($materialsMap[$material->id])) {
                    $materialsMap[$material->id] = [
                        'material_id' => $material->id,
                        'material_name' => $material->material_name,
                        'category' => $material->category ?: 'Bahan Baku',
                        'unit' => $isAreaBased ? 'm²' : 'Pcs/Lembar',
                        'is_area' => $isAreaBased,
                        'usage_qty' => 0,
                        'purchase_price' => $purchasePrice,
                        'click_charge' => $clickCharge,
                        'total_raw_cost' => 0,
                        'total_click_cost' => 0,
                        'total_material_cost' => 0,
                        'current_stock' => (float) $material->stock_qty,
                    ];
                }
                $materialsMap[$material->id]['usage_qty'] += $usedAreaOrUnit;
                $rawCost = ($isAreaBased ? ($areaM2 * $purchasePrice) : $purchasePrice) * $qty;
                $clickCost = $clickCharge * $qty;
                $materialsMap[$material->id]['total_raw_cost'] += $rawCost;
                $materialsMap[$material->id]['total_click_cost'] += $clickCost;
                $materialsMap[$material->id]['total_material_cost'] += $itemCost;
            }
        }

        // Sort products by omzet descending
        uasort($productsMap, function ($a, $b) {
            return $b['total_omzet'] <=> $a['total_omzet'];
        });

        // Sort materials by total_material_cost descending
        uasort($materialsMap, function ($a, $b) {
            return $b['total_material_cost'] <=> $a['total_material_cost'];
        });

        $grossProfit = $totalOmzet - $totalMaterialCost;
        $grossMarginPct = $totalOmzet > 0 ? ($grossProfit / $totalOmzet) * 100 : 0;

        // Archives list for monthly reports
        $archivesQuery = ProductSalesArchive::with(['branch', 'user'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('created_at', 'desc');

        if ($branchId && $branchId !== 'all') {
            $archivesQuery->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            });
        }
        $archives = $archivesQuery->get();

        return [
            'periodType' => $periodType,
            'periodLabel' => $periodLabel,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'month' => $month,
            'year' => $year,
            'date' => $date,
            'branchId' => $branchId,
            'branchName' => $branchName,
            'branches' => $branches,
            'productsSold' => array_values($productsMap),
            'materialsUsed' => array_values($materialsMap),
            'totalItemsSold' => $totalItemsSold,
            'totalOmzet' => $totalOmzet,
            'totalMaterialCost' => $totalMaterialCost,
            'grossProfit' => $grossProfit,
            'grossMarginPct' => $grossMarginPct,
            'totalMaterialsCount' => count($materialsMap),
            'archives' => $archives,
        ];
    }

    /**
     * Display the Product Sales & Material Usage Report page
     */
    public function index(Request $request)
    {
        $data = $this->computeReportData($request);
        return view('reports.product-sales', $data);
    }

    /**
     * Export Monthly / Period PDF Report
     */
    public function exportPdf(Request $request)
    {
        $data = $this->computeReportData($request);
        
        $safePeriod = Str::slug($data['periodLabel'], '_');
        $safeBranch = Str::slug($data['branchName'], '_');
        $filename = "Laporan_Produk_Bahan_{$safePeriod}_{$safeBranch}.pdf";

        $pdf = Pdf::loadView('reports.pdf.product-sales-monthly', $data)
            ->setPaper('a4', 'portrait')
            ->setOption([
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

        return $pdf->stream($filename);
    }

    /**
     * Store Monthly Report Snapshot and PDF to Archives
     */
    public function storeArchive(Request $request)
    {
        // Enforce monthly snapshot for archiving as per user requirement
        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);

        $request->merge([
            'period_type' => 'monthly',
            'month' => $month,
            'year' => $year
        ]);

        $data = $this->computeReportData($request);

        $safePeriod = Str::slug($data['periodLabel'], '_');
        $safeBranch = Str::slug($data['branchName'], '_');
        $timestamp = Carbon::now()->format('Ymd_His');
        $filename = "Arsip_Produk_Bahan_{$safePeriod}_{$safeBranch}_{$timestamp}.pdf";
        $relativeDir = 'archives/product_sales';
        $fullPath = storage_path("app/public/{$relativeDir}");

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $pdf = Pdf::loadView('reports.pdf.product-sales-monthly', $data)
            ->setPaper('a4', 'portrait')
            ->setOption([
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

        // Save PDF file to public storage
        $savedPath = "{$relativeDir}/{$filename}";
        Storage::disk('public')->put($savedPath, $pdf->output());

        // Create archive record
        ProductSalesArchive::create([
            'branch_id' => ($data['branchId'] && $data['branchId'] !== 'all') ? $data['branchId'] : null,
            'user_id' => Auth::id(),
            'month' => $month,
            'year' => $year,
            'period_label' => $data['periodLabel'],
            'start_date' => $data['startDate'],
            'end_date' => $data['endDate'],
            'total_items_sold' => $data['totalItemsSold'],
            'total_omzet' => $data['totalOmzet'],
            'total_material_cost' => $data['totalMaterialCost'],
            'gross_profit' => $data['grossProfit'],
            'pdf_filename' => $filename,
            'pdf_path' => $savedPath,
            'notes' => $request->input('notes', 'Arsip Laporan Bulanan Penjualan Produk & Pemakaian Bahan Baku'),
        ]);

        return redirect()->route('reports.product-sales', $request->all())
            ->with('success', "Laporan Bulanan periode {$data['periodLabel']} berhasil dirangkum dalam bentuk PDF dan disimpan ke arsip!");
    }

    /**
     * Download an archived PDF file
     */
    public function downloadArchive($id)
    {
        $archive = ProductSalesArchive::findOrFail($id);

        if (!Storage::disk('public')->exists($archive->pdf_path)) {
            return redirect()->back()->with('error', 'Berkas fisik PDF arsip tidak ditemukan di server.');
        }

        return Storage::disk('public')->download($archive->pdf_path, $archive->pdf_filename);
    }

    /**
     * Delete an archived report
     */
    public function destroyArchive($id)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isSuperAdmin() && !$user->isManager()) {
            abort(403, 'Anda tidak berwenang menghapus arsip laporan.');
        }

        $archive = ProductSalesArchive::findOrFail($id);

        if ($archive->pdf_path && Storage::disk('public')->exists($archive->pdf_path)) {
            Storage::disk('public')->delete($archive->pdf_path);
        }

        $archive->delete();

        return redirect()->back()->with('success', 'Arsip Laporan Bulanan berhasil dihapus.');
    }
}
