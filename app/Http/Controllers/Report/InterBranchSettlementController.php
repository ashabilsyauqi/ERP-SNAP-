<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InterBranchSettlementController extends Controller
{
    public function getReportData(Request $request): array
    {
        $user = Auth::user();
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();
        $branches = Branch::all();

        if (!$isOwnerOrSuper && $user->branch_id) {
            // Non-owner: locked to logged-in user's branch
            $branch1Id = $user->branch_id;
            $branch2Id = $request->input('branch_2');
            if (!$branch2Id || $branch2Id == $branch1Id) {
                $otherBranch = $branches->firstWhere('id', '!=', $branch1Id);
                $branch2Id = $otherBranch ? $otherBranch->id : $branch1Id;
            }
        } else {
            // Owner / SuperAdmin: can select any branch pair
            $branch1Id = $request->input('branch_1');
            $branch2Id = $request->input('branch_2');

            if (!$branch1Id || !$branch2Id) {
                $zamrud = $branches->first(function ($b) {
                    return str_contains(strtolower($b->nama_cabang), 'zamrud');
                });
                $grandwis = $branches->first(function ($b) {
                    return str_contains(strtolower($b->nama_cabang), 'grand');
                });

                $branch1Id = $branch1Id ?: ($zamrud ? $zamrud->id : ($branches[0]->id ?? 1));
                $branch2Id = $branch2Id ?: ($grandwis ? $grandwis->id : ($branches[1]->id ?? 2));
                
                // If they happen to be the same, pick a different second branch
                if ($branch1Id == $branch2Id && count($branches) > 1) {
                    $otherBranch = $branches->firstWhere('id', '!=', $branch1Id);
                    if ($otherBranch) {
                        $branch2Id = $otherBranch->id;
                    }
                }
            }
        }

        $branch1 = Branch::find($branch1Id);
        $branch2 = Branch::find($branch2Id);

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

        if ($timeframe === 'month') {
            $startDate = Carbon::create($year, $month, 1)->startOfDay();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
            $periodLabel = 'Bulan ' . $startDate->translatedFormat('F Y');
        } elseif ($timeframe === 'custom' && $startDateInput && $endDateInput) {
            $startDate = Carbon::parse($startDateInput)->startOfDay();
            $endDate = Carbon::parse($endDateInput)->endOfDay();
            $periodLabel = $startDate->format('d M Y') . ' s/d ' . $endDate->format('d M Y');
        } else {
            $periodLabel = 'Semua Periode Transaksi';
        }

        // Query Cross-Branch Transactions
        $query = Transaction::with(['branch', 'fulfillmentBranch', 'user', 'customer', 'transactionDetails.material'])
            ->where('is_cross_branch', true)
            ->where('order_status', '!=', 'cancelled');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $allCrossTransactions = $query->orderBy('created_at', 'desc')->get();

        // Direction 1: Branch 1 -> Branch 2 (Customer orders at B1, fulfilled by B2)
        $trxB1toB2 = $allCrossTransactions->filter(function ($t) use ($branch1Id, $branch2Id) {
            return $t->branch_id == $branch1Id && $t->fulfillment_branch_id == $branch2Id;
        });

        // Direction 2: Branch 2 -> Branch 1 (Customer orders at B2, fulfilled by B1)
        $trxB2toB1 = $allCrossTransactions->filter(function ($t) use ($branch1Id, $branch2Id) {
            return $t->branch_id == $branch2Id && $t->fulfillment_branch_id == $branch1Id;
        });

        // Calculations for Bilateral Pair
        $b1CashIn = (float) $trxB1toB2->sum('total_price');
        $b2CashIn = (float) $trxB2toB1->sum('total_price');

        $b1OwnShare = round($b1CashIn * 0.25, 2);
        $b2WorkShare = round($b1CashIn * 0.75, 2);

        $b2OwnShare = round($b2CashIn * 0.25, 2);
        $b1WorkShare = round($b2CashIn * 0.75, 2);

        $b1TotalRevenue = $b1OwnShare + $b1WorkShare;
        $b2TotalRevenue = $b2OwnShare + $b2WorkShare;

        $b1OwesB2 = $b2WorkShare;
        $b2OwesB1 = $b1WorkShare;

        $netDiff = $b1OwesB2 - $b2OwesB1;

        $settlement = [
            'net_diff' => $netDiff,
            'is_balanced' => abs($netDiff) < 0.01,
            'payer' => $netDiff > 0 ? $branch1 : ($netDiff < 0 ? $branch2 : null),
            'receiver' => $netDiff > 0 ? $branch2 : ($netDiff < 0 ? $branch1 : null),
            'amount' => abs($netDiff),
        ];

        // Filtered transactions for the pair
        $pairTransactions = $allCrossTransactions->filter(function ($t) use ($branch1Id, $branch2Id) {
            return ($t->branch_id == $branch1Id && $t->fulfillment_branch_id == $branch2Id)
                || ($t->branch_id == $branch2Id && $t->fulfillment_branch_id == $branch1Id);
        });

        return [
            'branches' => $branches,
            'branch1' => $branch1,
            'branch2' => $branch2,
            'branch1Id' => $branch1Id,
            'branch2Id' => $branch2Id,
            'timeframe' => $timeframe,
            'month' => $month,
            'year' => $year,
            'startDate' => $startDate ? $startDate->format('Y-m-d') : '',
            'endDate' => $endDate ? $endDate->format('Y-m-d') : '',
            'periodLabel' => $periodLabel,
            'b1CashIn' => $b1CashIn,
            'b2CashIn' => $b2CashIn,
            'b1OwnShare' => $b1OwnShare,
            'b1WorkShare' => $b1WorkShare,
            'b2OwnShare' => $b2OwnShare,
            'b2WorkShare' => $b2WorkShare,
            'b1TotalRevenue' => $b1TotalRevenue,
            'b2TotalRevenue' => $b2TotalRevenue,
            'b1OwesB2' => $b1OwesB2,
            'b2OwesB1' => $b2OwesB1,
            'settlement' => $settlement,
            'trxB1toB2' => $trxB1toB2,
            'trxB2toB1' => $trxB2toB1,
            'pairTransactions' => $pairTransactions,
            'allCrossTransactions' => $allCrossTransactions,
            'isOwnerOrSuper' => $isOwnerOrSuper,
        ];
    }

    public function index(Request $request)
    {
        $data = $this->getReportData($request);
        return view('reports.inter-branch-settlement', $data);
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getReportData($request);
        $b1Name = $data['branch1'] ? $data['branch1']->nama_cabang : 'Cabang 1';
        $b2Name = $data['branch2'] ? $data['branch2']->nama_cabang : 'Cabang 2';

        $filename = 'Rekonsiliasi_Kliring_25_75_' . str_replace(' ', '_', $b1Name) . '_vs_' . str_replace(' ', '_', $b2Name) . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($data, $b1Name, $b2Name) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($file, ['BERITA ACARA REKONSILIASI & SETTLEMENT BAGI HASIL 25/75']);
            fputcsv($file, ['Cabang Pihak 1', $b1Name]);
            fputcsv($file, ['Cabang Pihak 2', $b2Name]);
            fputcsv($file, ['Periode', $data['periodLabel']]);
            fputcsv($file, ['Tanggal Cetak', date('d/m/Y H:i')]);
            fputcsv($file, []);

            // Summary Table
            fputcsv($file, ['RINGKASAN POSISI KAS & HAK PENDAPATAN RIIL']);
            fputcsv($file, ['Parameter', $b1Name, $b2Name]);
            fputcsv($file, ['Total Kas Fisik Masuk (100%)', $data['b1CashIn'], $data['b2CashIn']]);
            fputcsv($file, ['Hak Komisi Order Sendiri (25%)', $data['b1OwnShare'], $data['b2OwnShare']]);
            fputcsv($file, ['Hak Pengerjaan Order Mitra (75%)', $data['b1WorkShare'], $data['b2WorkShare']]);
            fputcsv($file, ['Total Hak Pendapatan Bersih', $data['b1TotalRevenue'], $data['b2TotalRevenue']]);
            fputcsv($file, ['Kewajiban Setoran ke Mitra', $data['b1OwesB2'], $data['b2OwesB1']]);
            fputcsv($file, []);

            // Net Settlement
            fputcsv($file, ['STATUS KLIRING AKHIR (NETTING)']);
            if ($data['settlement']['is_balanced']) {
                fputcsv($file, ['Hasil Kliring', 'SALDO SEIMBANG / IMPAS (Rp 0)']);
            } else {
                fputcsv($file, ['Pihak yang Mentransfer', $data['settlement']['payer']->nama_cabang]);
                fputcsv($file, ['Pihak Penerima Transfer', $data['settlement']['receiver']->nama_cabang]);
                fputcsv($file, ['Nominal Net Kliring', $data['settlement']['amount']]);
            }
            fputcsv($file, []);

            // Details Table
            fputcsv($file, ['RINCIAN TRANSAKSI LINTAS CABANG']);
            fputcsv($file, [
                'No. Invoice',
                'Tanggal',
                'Cabang Asal (Pembuat Order)',
                'Cabang Pelaksana (Produksi)',
                'Pelanggan',
                'Keterangan / Produk',
                'Metode Bayar',
                'Status Bayar',
                'Total Nilai (100%)',
                "Hak {$b1Name}",
                "Hak {$b2Name}",
            ]);

            foreach ($data['pairTransactions'] as $trx) {
                $detailsDesc = $trx->transactionDetails->map(function ($d) {
                    $name = $d->material ? $d->material->material_name : ($d->is_vendor_job ? "[Offset] {$d->vendor_name}" : 'Item');
                    return "{$name} ({$d->qty_ordered}x)";
                })->implode(', ');

                $isB1Origin = ($trx->branch_id == $data['branch1Id']);
                $b1Share = $isB1Origin ? ($trx->total_price * 0.25) : ($trx->total_price * 0.75);
                $b2Share = $isB1Origin ? ($trx->total_price * 0.75) : ($trx->total_price * 0.25);

                fputcsv($file, [
                    $trx->invoice_number,
                    $trx->created_at->format('d/m/Y H:i'),
                    $trx->branch ? $trx->branch->nama_cabang : '-',
                    $trx->fulfillmentBranch ? $trx->fulfillmentBranch->nama_cabang : '-',
                    $trx->customer_name ?: ($trx->customer ? $trx->customer->name : 'Umum'),
                    $detailsDesc,
                    $trx->payment_method,
                    $trx->payment_status,
                    $trx->total_price,
                    $b1Share,
                    $b2Share,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
