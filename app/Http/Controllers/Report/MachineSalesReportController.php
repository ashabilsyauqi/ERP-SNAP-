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
    public function index(Request $request)
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

        $selectedTag = $request->input('machine_tag', 'Mesin Pak Gunawan');

        // Profit sharing percentage (Default: 30%)
        $sharingPct = (float) $request->input('sharing_pct', 30);

        // Date & Period Filter
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
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, 12, 31)->endOfMonth();
            $periodLabel = 'Tahun ' . $year;
        } else {
            // Default: month
            $timeframe = 'month';
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
            $periodLabel = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
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

        // Aggregation per Product
        $productsMap = [];
        $totalItemsSold = 0;
        $totalOmzet = 0;
        $totalHpp = 0;

        foreach ($transactionDetails as $detail) {
            $material = $detail->material;
            $materialId = $material->id;
            $materialName = $material->material_name;
            $category = $material->category ?: 'Produk';
            
            $qty = (float) $detail->qty_ordered;
            $sellingPrice = (float) $detail->selling_price;
            $areaM2 = (float) ($detail->area_m2 ?? 0);
            
            $revenue = ($areaM2 > 0) ? ($areaM2 * $sellingPrice) : ($qty * $sellingPrice);
            // Fallback for custom banner unit price
            if ($revenue <= 0 && $detail->selling_price > 0) {
                $revenue = (float) $detail->selling_price;
            }

            $purchasePrice = (float) $material->purchase_price;
            $clickCharge = (float) ($detail->click_charge ?? $material->click_charge ?? 0);
            $itemCost = ($purchasePrice + $clickCharge) * $qty;

            $totalItemsSold += $qty;
            $totalOmzet += $revenue;
            $totalHpp += $itemCost;

            if (!isset($productsMap[$materialId])) {
                $productsMap[$materialId] = [
                    'material_id' => $materialId,
                    'product_name' => $materialName,
                    'category' => $category,
                    'machine_tag' => $material->machine_tag,
                    'unit_price' => $material->retail_price,
                    'qty_sold' => 0,
                    'total_omzet' => 0,
                    'total_hpp' => 0,
                    'gross_profit' => 0,
                ];
            }

            $productsMap[$materialId]['qty_sold'] += $qty;
            $productsMap[$materialId]['total_omzet'] += $revenue;
            $productsMap[$materialId]['total_hpp'] += $itemCost;
            $productsMap[$materialId]['gross_profit'] += ($revenue - $itemCost);
        }

        // Sort products by omzet descending
        uasort($productsMap, fn($a, $b) => $b['total_omzet'] <=> $a['total_omzet']);

        $grossProfit = $totalOmzet - $totalHpp;
        $partnerShareAmount = round($totalOmzet * ($sharingPct / 100));
        $partnerShareProfitAmount = round($grossProfit * ($sharingPct / 100));
        $uniqueInvoicesCount = $transactionDetails->pluck('transaction_id')->unique()->count();

        return view('reports.machine-sales', compact(
            'productsMap',
            'transactionDetails',
            'totalItemsSold',
            'totalOmzet',
            'totalHpp',
            'grossProfit',
            'partnerShareAmount',
            'partnerShareProfitAmount',
            'uniqueInvoicesCount',
            'allMachineTags',
            'selectedTag',
            'sharingPct',
            'branches',
            'branchId',
            'branchName',
            'periodLabel',
            'timeframe',
            'month',
            'year',
            'startDateInput',
            'endDateInput'
        ));
    }
}
