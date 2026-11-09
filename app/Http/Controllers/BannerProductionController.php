<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DailyClosingReport;
use App\Models\DailyClosingWaste;
use App\Models\Material;
use App\Models\MaterialRoll;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BannerProductionController extends Controller
{
    /**
     * Display Banner Production Workstation & Queue.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();

        // Branch selection
        if (!$isOwnerOrSuper) {
            $branchId = (int)$user->branch_id;
        } else {
            if ($request->has('branch_id')) {
                $branchId = (int)$request->input('branch_id');
                session(['production_branch_id' => $branchId]);
            } else {
                $branchId = (int)session('production_branch_id', $user->branch_id ?: Branch::first()->id);
            }
        }

        $branch = Branch::find($branchId) ?: Branch::first();
        $branches = Branch::orderBy('nama_cabang')->get();

        // Filter material if requested
        $selectedMaterialId = $request->input('material_id');

        // 1. Fetch pure roll banner materials in this branch (exclude hardware & paket items)
        $bannerMaterials = Material::where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where('material_name', 'like', '%flexy%')
                  ->orWhere('material_name', 'like', '%albatros%')
                  ->orWhere('material_name', 'like', '%ritrama%')
                  ->orWhere('material_name', 'like', '%oneway%')
                  ->orWhere('material_name', 'like', '%sablon dtf meteran%');
            })
            ->where('material_name', 'not like', '%paket%')
            ->where('material_name', 'not like', '%tiang%')
            ->where('material_name', 'not like', '%tripod%')
            ->where('material_name', 'not like', '%stand%')
            ->with(['rolls' => fn($rq) => $rq->orderBy('roll_width_m', 'asc')])
            ->orderBy('material_name')
            ->get();

        // 2. Build hierarchical groups by family
        $groupedMaterials = [];
        foreach ($bannerMaterials as $m) {
            $name = $m->material_name;
            $family = 'Lainnya';
            if (stripos($name, 'Flexy China') !== false) {
                $family = 'Flexy China';
            } elseif (stripos($name, 'Flexy Korea') !== false) {
                $family = 'Flexy Korea';
            } elseif (stripos($name, 'Albatros') !== false || stripos($name, 'Luster') !== false) {
                $family = 'Indoor & Luster';
            } elseif (stripos($name, 'Ritrama') !== false || stripos($name, 'Oneway') !== false) {
                $family = 'Stiker & Oneway';
            } elseif (stripos($name, 'DTF') !== false) {
                $family = 'Sablon DTF';
            }

            $totalMeters = (float)$m->rolls->sum('current_length_m');
            $totalArea = (float)$m->rolls->sum(fn($r) => (float)$r->current_length_m * (float)$r->roll_width_m);

            $groupedMaterials[$family][] = [
                'material' => $m,
                'total_meters' => round($totalMeters, 2),
                'total_area' => round($totalArea, 2),
                'rolls' => $m->rolls,
            ];
        }

        // Active material default: prefer Flexy China 280 if available, else first material
        if (!$selectedMaterialId && $bannerMaterials->isNotEmpty()) {
            $defaultMat = $bannerMaterials->first(fn($m) => stripos($m->material_name, 'Flexy China 280') !== false)
                ?: $bannerMaterials->first();
            $selectedMaterialId = $defaultMat->id;
        }

        $activeMaterial = $bannerMaterials->firstWhere('id', $selectedMaterialId) ?: $bannerMaterials->first();

        // Active rolls for selected material
        $activeRolls = $activeMaterial ? $activeMaterial->rolls : collect();

        // Calculate total physical area across active rolls
        $totalPhysicalRollArea = $activeRolls->sum(function ($r) {
            return (float)$r->current_length_m * (float)$r->roll_width_m;
        });

        $erpStockArea = $activeMaterial ? (float)$activeMaterial->stock_qty : 0;
        $syncDifference = round($totalPhysicalRollArea - $erpStockArea, 2);

        // 3. Fetch Pending Production Queue (ALL pending banner orders for this branch)
        $pendingQueue = TransactionDetail::with(['transaction.customer', 'transaction.user', 'material.rolls'])
            ->where('production_status', 'pending')
            ->whereHas('transaction', function ($tq) use ($branchId) {
                $tq->where('branch_id', $branchId)
                   ->whereIn('payment_status', ['PAID', 'PARTIAL']);
            })
            ->where(function ($q) {
                $q->whereNotNull('dimension_text')
                  ->orWhere('area_m2', '>', 0)
                  ->orWhereHas('material', function ($mq) {
                      $mq->where('material_name', 'like', '%flexy%')
                        ->orWhere('material_name', 'like', '%banner%')
                        ->orWhere('material_name', 'like', '%albatros%')
                        ->orWhere('material_name', 'like', '%ritrama%')
                        ->orWhere('material_name', 'like', '%oneway%')
                        ->orWhere('material_name', 'like', '%sablon dtf meteran%');
                  });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // 4. Fetch Ready for Pickup Orders (Waiting for Kasir Handover / Closing)
        $readyForPickup = Transaction::with(['transactionDetails.material', 'customer', 'user'])
            ->where('branch_id', $branchId)
            ->where('order_status', 'ready_for_pickup')
            ->orderBy('updated_at', 'desc')
            ->take(20)
            ->get();

        // 5. Fetch Completed Prints Today
        $completedToday = TransactionDetail::with(['transaction', 'material', 'materialRoll', 'printerUser'])
            ->where('production_status', 'completed')
            ->whereDate('printed_at', Carbon::today())
            ->whereHas('transaction', fn($tq) => $tq->where('branch_id', $branchId))
            ->orderBy('printed_at', 'desc')
            ->take(15)
            ->get();

        // Count metrics
        $pendingCountTotal = $pendingQueue->count();
        $readyForPickupCount = $readyForPickup->count();
        $totalPrintedTodayCount = $completedToday->count();

        // Prepare JSON structure of all materials & their rolls for quick dynamic client-side usage
        $bannerMaterialsJson = $bannerMaterials->map(function ($m) {
            return [
                'id' => $m->id,
                'name' => $m->material_name,
                'category' => $m->category,
                'stock_qty' => (float)$m->stock_qty,
                'total_meters' => round((float)$m->rolls->sum('current_length_m'), 2),
                'total_area' => round((float)$m->rolls->sum(fn($r) => (float)$r->current_length_m * (float)$r->roll_width_m), 2),
                'rolls' => $m->rolls->map(fn($r) => [
                    'id' => $r->id,
                    'name' => $r->roll_name,
                    'width' => (float)$r->roll_width_m,
                    'length' => (float)$r->current_length_m,
                    'initial_length' => (float)$r->initial_length_m,
                    'area' => round((float)$r->current_length_m * (float)$r->roll_width_m, 2),
                ])->values(),
            ];
        })->values();

        return view('production.banner.index', compact(
            'branches',
            'branch',
            'branchId',
            'bannerMaterials',
            'groupedMaterials',
            'selectedMaterialId',
            'activeMaterial',
            'activeRolls',
            'totalPhysicalRollArea',
            'erpStockArea',
            'syncDifference',
            'pendingQueue',
            'readyForPickup',
            'completedToday',
            'pendingCountTotal',
            'readyForPickupCount',
            'totalPrintedTodayCount',
            'bannerMaterialsJson'
        ));
    }

    /**
     * Execute print on chosen roll, deduct stock, validate width capacity (+10cm tolerance), and record waste.
     */
    public function executePrint(Request $request)
    {
        $request->validate([
            'detail_ids' => 'required|array|min:1',
            'detail_ids.*' => 'required|integer|exists:transaction_details,id',
            'material_roll_id' => 'required|integer|exists:material_rolls,id',
            'cut_length_m' => 'required|numeric|min:0.1',
            'operator_notes' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $roll = MaterialRoll::with('material')->findOrFail($request->material_roll_id);
        $cutLength = (float)$request->cut_length_m;

        // Check roll length
        if ((float)$roll->current_length_m < $cutLength) {
            return redirect()->back()->with('error', "Gagal! Panjang sisa roll {$roll->roll_name} ({$roll->current_length_m}m) tidak cukup untuk tarikan {$cutLength}m.");
        }

        // Load all selected transaction details
        $details = TransactionDetail::with(['transaction', 'material'])->whereIn('id', $request->detail_ids)->get();

        // Validate roll width capacity against all items in this print run.
        // Supplier roll dari pabrik sudah melebihkan ukuran fisik (+10cm toleransi), sehingga:
        // - Order nominal s/d 1.0m muat & aman di roll 1M (karena roll fisik ~1.10m mencakup lipatan 10cm)
        // - Order nominal s/d 2.0m muat & aman di roll 2M
        // - Order nominal s/d 3.0m muat & aman di roll 3M
        // Jika lebar pesanan melebihi kapasitas nominal roll (misal order 1.15m di roll 1M), sistem menolak dan minta roll 2M/3M.
        $maxOrderWidth = 0;
        foreach ($details as $d) {
            $wM = (float)($d->custom_width_cm ? round($d->custom_width_cm / 100, 2) : 1.0);
            $lM = (float)($d->fixed_length_m ?: 1.0);
            $smallerDim = min($wM, $lM);
            if ($smallerDim > $maxOrderWidth) {
                $maxOrderWidth = $smallerDim;
            }
        }

        if ((float)$roll->roll_width_m < $maxOrderWidth) {
            return redirect()->back()->with('error', "Gagal! Lebar pesanan (" . number_format($maxOrderWidth, 2) . "m) melebihi kapasitas gulungan roll {$roll->roll_name} ({$roll->roll_width_m}m). Silakan pilih roll yang lebih lebar (Roll 2M / 3M).");
        }

        DB::beginTransaction();
        try {
            // 1. Deduct linear meters from physical roll
            $roll->current_length_m = max(0, round((float)$roll->current_length_m - $cutLength, 2));
            $roll->save();

            // 2. Physical area used from the roll (Length x Width)
            $physicalAreaUsedM2 = round($cutLength * (float)$roll->roll_width_m, 2);

            // 3. Clean Order Area
            $totalOrderAreaM2 = 0;
            foreach ($details as $d) {
                $itemArea = (float)($d->area_m2 ?: 1.0);
                $totalOrderAreaM2 += round($itemArea * (int)$d->qty_ordered, 2);
            }

            // Total Waste (Offcut samping roll + lipatan/margin yang terbuang)
            $offcutAreaM2 = max(0, round($physicalAreaUsedM2 - $totalOrderAreaM2, 2));

            // Generate Batch ID for grouped print
            $batchId = 'BATCH-' . date('Ymd-His') . '-' . strtoupper(Str::random(4));
            $invoicesList = $details->pluck('transaction.invoice_number')->filter()->unique()->implode(', ');

            // 4. Update each transaction detail
            foreach ($details as $d) {
                $d->production_status = 'completed';
                $d->material_roll_id = $roll->id;
                $d->cut_length_m = $cutLength;
                $d->roll_width_snapshot = (float)$roll->roll_width_m;
                $d->roll_area_used_m2 = $physicalAreaUsedM2;
                $d->offcut_area_m2 = $offcutAreaM2;
                $d->printed_at = Carbon::now();
                $d->printed_by = $user->id;
                $d->print_batch_id = $batchId;
                $d->save();
            }

            // 5. Synchronize Material stock_qty (ERP Master Stock)
            if ($roll->material) {
                $material = $roll->material;
                $stockDeduction = round($physicalAreaUsedM2, 2);
                $material->stock_qty = max(0, round((float)$material->stock_qty - $stockDeduction, 2));
                $material->save();
            }

            // 6. RECORD PRODUCTION WASTE (Offcut & Lipatan) INTO DAILY CLOSING WASTE LEDGER
            if ($offcutAreaM2 > 0) {
                $hppPerM2 = (float)($roll->material->purchase_price ?? 0);
                $totalWasteHpp = round($offcutAreaM2 * $hppPerM2, 2);

                $wasteRecord = DailyClosingWaste::create([
                    'daily_closing_report_id' => null,
                    'branch_id' => $roll->branch_id,
                    'user_id' => $user->id,
                    'material_id' => $roll->material_id,
                    'material_roll_id' => $roll->id,
                    'waste_qty' => $offcutAreaM2,
                    'unit' => 'm2',
                    'hpp_per_unit' => $hppPerM2,
                    'total_hpp' => $totalWasteHpp,
                    'reason' => 'Offcut & Lipatan Banner',
                    'notes' => "Offcut {$offcutAreaM2} m2 dari cetak {$batchId} (Inv: {$invoicesList}) pada roll {$roll->roll_name} ({$roll->roll_width_m}m)",
                ]);

                // If today's DailyClosingReport exists for this branch, update it
                $todayClosing = DailyClosingReport::where('branch_id', $roll->branch_id)
                    ->whereDate('closing_date', Carbon::today())
                    ->where('status', 'draft')
                    ->first();

                if ($todayClosing) {
                    $wasteRecord->update(['daily_closing_report_id' => $todayClosing->id]);
                    $todayClosing->increment('total_waste_hpp', $totalWasteHpp);
                    $snap = $todayClosing->waste_data ?: [];
                    $snap[] = [
                        'material_id' => $roll->material_id,
                        'material_name' => $roll->material->material_name ?? 'Banner',
                        'waste_qty' => $offcutAreaM2,
                        'unit' => 'm2',
                        'hpp_per_unit' => $hppPerM2,
                        'total_hpp' => $totalWasteHpp,
                        'reason' => 'Offcut & Lipatan Banner',
                        'notes' => "Cetak Inv: {$invoicesList}",
                        'time' => now()->format('H:i'),
                    ];
                    $todayClosing->update(['waste_data' => $snap]);
                }
            }

            // 7. Update parent transactions to 'ready_for_pickup'
            $affectedTransactions = $details->pluck('transaction')->unique('id');
            foreach ($affectedTransactions as $tx) {
                if ($tx) {
                    $remainingPending = $tx->transactionDetails()->where('production_status', 'pending')->count();
                    if ($remainingPending === 0) {
                        $tx->order_status = 'ready_for_pickup';
                        $tx->save();
                    }
                }
            }

            DB::commit();

            $orderCount = count($request->detail_ids);
            $wasteMsg = $offcutAreaM2 > 0 ? " Sisa pinggiran roll & lipatan ({$offcutAreaM2} m²) otomatis dicatat ke riwayat waste produksi." : "";
            return redirect()->back()->with('success', "✅ Berhasil memproses {$orderCount} order cetak! Roll '{$roll->roll_name}' terpotong {$cutLength}m ({$physicalAreaUsedM2} m²).{$wasteMsg} Status order kini: SIAP DIANTAR KE KASIR.");

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses cetak banner: ' . $e->getMessage());
        }
    }

    /**
     * Handover printed banner to customer and complete transaction (Closing by Cashier).
     */
    public function handoverToCustomer(Request $request, $id)
    {
        $tx = Transaction::with('transactionDetails')->findOrFail($id);

        if ($tx->payment_status === 'PARTIAL' && (float)$tx->remaining_amount > 0) {
            return redirect()->back()->with('error', "Pesanan #{$tx->invoice_number} masih memiliki sisa tagihan Rp " . number_format($tx->remaining_amount, 0, ',', '.') . ". Harap lunasi terlebih dahulu di kasir.");
        }

        $tx->order_status = 'completed';
        $tx->save();

        return redirect()->back()->with('success', "🎉 Pesanan #{$tx->invoice_number} berhasil diserahkan ke pelanggan dan status transaksi resmi CLOSED (COMPLETED).");
    }

    /**
     * Store Physical Stock Opname for Roll Materials and Synchronize Master ERP Stock.
     */
    public function storeOpname(Request $request)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'rolls' => 'required|array|min:1',
            'rolls.*' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $material = Material::with('rolls')->findOrFail($request->input('material_id'));

        DB::beginTransaction();
        try {
            $rollsInput = $request->input('rolls', []);
            $totalNewArea = 0;
            $diffSummary = [];

            foreach ($material->rolls as $roll) {
                if (isset($rollsInput[$roll->id])) {
                    $oldLen = (float)$roll->current_length_m;
                    $newLen = round((float)$rollsInput[$roll->id], 2);
                    $roll->current_length_m = $newLen;
                    if ($newLen > (float)$roll->initial_length_m) {
                        $roll->initial_length_m = $newLen;
                    }
                    $roll->save();

                    $diffM = round($newLen - $oldLen, 2);
                    if ($diffM != 0) {
                        $diffSummary[] = "{$roll->roll_name}: {$oldLen}m → {$newLen}m (" . ($diffM > 0 ? "+{$diffM}m" : "{$diffM}m") . ")";
                    }
                }
                $totalNewArea += round((float)$roll->current_length_m * (float)$roll->roll_width_m, 2);
            }

            // Synchronize Master Stock ERP
            $oldErpStock = (float)$material->stock_qty;
            $material->stock_qty = round($totalNewArea, 2);
            $material->save();

            DB::commit();

            $diffMsg = count($diffSummary) > 0 ? implode(', ', $diffSummary) : 'Semua roll sesuai fisik.';
            return redirect()->route('production.banner.index', ['material_id' => $material->id, 'branch_id' => $material->branch_id])
                ->with('success', "✅ Stock Opname berhasil disimpan! Stok fisik {$material->material_name} kini {$material->stock_qty} m² (100% sinkron dengan Master ERP). Rincian: {$diffMsg}");

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan stock opname roll: ' . $e->getMessage());
        }
    }

    /**
     * Quick JSON API to get roll stocks for a material.
     */
    public function getRollsByMaterial($materialId)
    {
        $material = Material::with(['rolls' => fn($q) => $q->orderBy('roll_width_m', 'asc')])->findOrFail($materialId);
        return response()->json([
            'material_id' => $material->id,
            'material_name' => $material->material_name,
            'stock_qty' => (float)$material->stock_qty,
            'rolls' => $material->rolls->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->roll_name,
                'width_m' => (float)$r->roll_width_m,
                'current_length_m' => (float)$r->current_length_m,
                'initial_length_m' => (float)$r->initial_length_m,
                'area_m2' => round((float)$r->current_length_m * (float)$r->roll_width_m, 2),
            ]),
        ]);
    }
}
