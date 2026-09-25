<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Branch;
use App\Models\Material;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MachineSalesReportController extends Controller
{
    private function getReportData(Request $request): array
    {
        $user = Auth::user();
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();

        // Branch Filter
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
        $branchName = $selectedBranch ? $selectedBranch->nama_cabang : 'Semua Cabang (Konsolidasi)';

        // Machine Tag Filter
        $allMachineTags = Material::whereNotNull('machine_tag')
            ->distinct()
            ->pluck('machine_tag')
            ->filter()
            ->values();

        $selectedTag = $request->input('machine_tag', 'Mesin KM');

        // Date & Period Filter (Cut-off date: 21st of each month)
        $timeframe = $request->input('timeframe', 'month');
        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);
        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');

        if ($startDateInput && $endDateInput) {
            $timeframe = 'custom';
        }

        $startDate = null;
        $endDate = null;
        $periodLabel = '';

        if ($timeframe === 'custom' && $startDateInput && $endDateInput) {
            $startDate = Carbon::parse($startDateInput)->startOfDay();
            $endDate = Carbon::parse($endDateInput)->endOfDay();
            $periodLabel = Carbon::parse($startDateInput)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDateInput)->translatedFormat('d M Y');
        } elseif ($timeframe === 'today' || $timeframe === '1D') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
            $periodLabel = 'Hari Ini (' . Carbon::today()->translatedFormat('d F Y') . ')';
        } elseif ($timeframe === '7days' || $timeframe === '7D') {
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            $periodLabel = '7 Hari Terakhir (' . $startDate->translatedFormat('d M') . ' - ' . $endDate->translatedFormat('d M Y') . ')';
        } elseif ($timeframe === 'year' || $timeframe === '1Y') {
            $startDate = Carbon::createFromDate($year - 1, 12, 21)->startOfDay();
            $endDate = Carbon::createFromDate($year, 12, 20)->endOfDay();
            $periodLabel = 'Tahun ' . $year . ' (21 Des ' . ($year - 1) . ' - 20 Des ' . $year . ')';
        } else {
            // Default: Monthly cut-off on 21st (21st of previous month to 20th of current month)
            $timeframe = 'month';
            $startDate = Carbon::createFromDate($year, $month, 21)->subMonth()->startOfDay();
            $endDate = Carbon::createFromDate($year, $month, 20)->endOfDay();
            $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
            $periodLabel = $startDate->translatedFormat('d M Y') . ' s/d ' . $endDate->translatedFormat('d M Y') . ' (' . $monthName . ')';
        }

        // Query TransactionDetails with matching material machine_tag
        $detailsQuery = TransactionDetail::query()
            ->whereHas('transaction', function ($q) use ($startDate, $endDate, $branchId) {
                $q->whereNotIn('order_status', ['draft', 'cancelled'])
                  ->whereBetween('created_at', [$startDate, $endDate]);
                if ($branchId && $branchId !== 'all') {
                    $q->where('branch_id', $branchId);
                }
            })
            ->whereHas('material', function ($q) use ($selectedTag) {
                if ($selectedTag && $selectedTag !== 'all') {
                    $q->where('machine_tag', $selectedTag);
                } else {
                    $q->whereNotNull('machine_tag');
                }
            })
            ->with(['transaction.branch', 'transaction.user', 'material']);

        $transactionDetails = $detailsQuery->orderBy('created_at', 'desc')->get();

        // Aggregation per Product (Pure Sales)
        $productsMap = [];
        $totalItemsSold = 0;
        $totalOmzet = 0;

        foreach ($transactionDetails as $detail) {
            $material = $detail->material;
            $materialId = $material->id;
            $materialName = $material->material_name;
            $category = $material->category ?: 'Produk';
            
            $qty = (float) $detail->qty_ordered;
            $sellingPrice = (float) $detail->selling_price;
            $areaM2 = (float) ($detail->area_m2 ?? 0);
            
            $revenue = ($areaM2 > 0) ? ($areaM2 * $sellingPrice) : ($qty * $sellingPrice);
            if ($revenue <= 0 && $detail->selling_price > 0) {
                $revenue = (float) $detail->selling_price;
            }

            $totalItemsSold += $qty;
            $totalOmzet += $revenue;

            if (!isset($productsMap[$materialId])) {
                $productsMap[$materialId] = [
                    'material_id' => $materialId,
                    'product_name' => $materialName,
                    'category' => $category,
                    'machine_tag' => $material->machine_tag,
                    'unit_price' => $material->retail_price,
                    'qty_sold' => 0,
                    'total_omzet' => 0,
                ];
            }

            $productsMap[$materialId]['qty_sold'] += $qty;
            $productsMap[$materialId]['total_omzet'] += $revenue;
        }

        // Sort products by omzet descending
        uasort($productsMap, fn($a, $b) => $b['total_omzet'] <=> $a['total_omzet']);

        $uniqueInvoicesCount = $transactionDetails->pluck('transaction_id')->unique()->count();
        $avgTransactionValue = ($uniqueInvoicesCount > 0) ? round($totalOmzet / $uniqueInvoicesCount) : 0;

        return [
            'productsMap' => $productsMap,
            'transactionDetails' => $transactionDetails,
            'totalItemsSold' => $totalItemsSold,
            'totalOmzet' => $totalOmzet,
            'uniqueInvoicesCount' => $uniqueInvoicesCount,
            'avgTransactionValue' => $avgTransactionValue,
            'allMachineTags' => $allMachineTags,
            'selectedTag' => $selectedTag,
            'branches' => $branches,
            'branchId' => $branchId,
            'branchName' => $branchName,
            'periodLabel' => $periodLabel,
            'timeframe' => $timeframe,
            'month' => $month,
            'year' => $year,
            'startDateInput' => $startDateInput,
            'endDateInput' => $endDateInput
        ];
    }

    public function index(Request $request)
    {
        $data = $this->getReportData($request);
        return view('reports.machine-sales', $data);
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getReportData($request);

        $tagSanitized = preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['selectedTag'] ?: 'Mesin');
        $periodSanitized = preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['periodLabel']);
        $filename = "Rekap_Mesin_{$tagSanitized}_{$periodSanitized}.xls";

        return response()->view('reports.machine-sales-excel', $data, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
