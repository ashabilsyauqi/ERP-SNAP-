<?php

namespace App\Http\Controllers;

use App\Models\Branch;
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

        // 1. Fetch Banner Materials in this branch that have roll tracking
        $bannerMaterials = Material::where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where('material_name', 'like', '%flexy%')
                  ->orWhere('material_name', 'like', '%banner%')
                  ->orWhere('material_name', 'like', '%albatros%')
                  ->orWhere('material_name', 'like', '%ritrama%')
                  ->orWhere('material_name', 'like', '%oneway%')
                  ->orWhere('material_name', 'like', '%sablon dtf meteran%')
                  ->orWhere('category', 'like', '%outdoor%')
                  ->orWhere('category', 'like', '%banner%');
            })
            ->with(['rolls' => fn($rq) => $rq->orderBy('roll_width_m', 'asc')])
            ->orderBy('material_name')
            ->get();

        if (!$selectedMaterialId && $bannerMaterials->isNotEmpty()) {
            $selectedMaterialId = $bannerMaterials->first()->id;
        }

        $activeMaterial = $bannerMaterials->firstWhere('id', $selectedMaterialId) ?: $bannerMaterials->first();

        // 2. Fetch Active Rolls for selected material
        $activeRolls = $activeMaterial ? $activeMaterial->rolls : collect();

        // Calculate total physical area across rolls
        $totalPhysicalRollArea = $activeRolls->sum(function ($r) {
            return (float)$r->current_length_m * (float)$r->roll_width_m;
        });

        $erpStockArea = $activeMaterial ? (float)$activeMaterial->stock_qty : 0;
        $syncDifference = round($totalPhysicalRollArea - $erpStockArea, 2);

        // 3. Fetch Pending Production Queue (Only PAID or PARTIAL orders)
        $queueQuery = TransactionDetail::with(['transaction.customer', 'transaction.user', 'material'])
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
            });

        if ($selectedMaterialId) {
            $queueQuery->where('material_id', $selectedMaterialId);
        }

        $pendingQueue = $queueQuery->orderBy('created_at', 'asc')->get();

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
        $pendingCountTotal = TransactionDetail::where('production_status', 'pending')
            ->whereHas('transaction', fn($tq) => $tq->where('branch_id', $branchId)->whereIn('payment_status', ['PAID', 'PARTIAL']))
            ->count();

        $readyForPickupCount = $readyForPickup->count();
        $totalPrintedTodayCount = $completedToday->count();

        return view('production.banner.index', compact(
            'branches',
            'branch',
            'branchId',
            'bannerMaterials',
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
            'totalPrintedTodayCount'
        ));
    }

    /**
     * Execute print on chosen roll and deduct stock linear length & area.
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

        if ((float)$roll->current_length_m < $cutLength) {
            return redirect()->back()->with('error', "Gagal! Panjang sisa roll {$roll->roll_name} ({$roll->current_length_m}m) tidak cukup untuk tarikan {$cutLength}m.");
        }

        DB::beginTransaction();
        try {
            // 1. Deduct linear meters from physical roll
            $roll->current_length_m = max(0, round((float)$roll->current_length_m - $cutLength, 2));
            $roll->save();

            // 2. Physical area used from the roll (Length x Width)
            $physicalAreaUsedM2 = round($cutLength * (float)$roll->roll_width_m, 2);

            // 3. Load all selected transaction details
            $details = TransactionDetail::with('transaction')->whereIn('id', $request->detail_ids)->get();
            $totalOrderAreaM2 = 0;

            foreach ($details as $d) {
                $itemArea = (float)($d->area_m2 ?: 1.0);
                $totalOrderAreaM2 += round($itemArea * (int)$d->qty_ordered, 2);
            }

            // Offcut waste area (selisih luas fisik roll vs luas orderan)
            $offcutAreaM2 = max(0, round($physicalAreaUsedM2 - $totalOrderAreaM2, 2));

            // Generate Batch ID for grouped print
            $batchId = 'BATCH-' . date('Ymd-His') . '-' . strtoupper(Str::random(4));

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
                // Deduct the actual physical area consumed from the roll
                $stockDeduction = max(1, (int)ceil($physicalAreaUsedM2));
                $material->stock_qty = max(0, (float)$material->stock_qty - $stockDeduction);
                $material->save();
            }

            // 6. Update parent transactions to 'ready_for_pickup'
            $affectedTransactions = $details->pluck('transaction')->unique('id');
            foreach ($affectedTransactions as $tx) {
                if ($tx) {
                    // Check if all items in transaction are completed
                    $remainingPending = $tx->transactionDetails()->where('production_status', 'pending')->count();
                    if ($remainingPending === 0) {
                        $tx->order_status = 'ready_for_pickup';
                        $tx->save();
                    }
                }
            }

            DB::commit();

            $orderCount = count($request->detail_ids);
            return redirect()->back()->with('success', "✅ Berhasil memproses {$orderCount} order cetak! Roll '{$roll->roll_name}' terpotong {$cutLength}m ({$physicalAreaUsedM2} m²). Stok ERP tersinkronisasi. Status order kini: SIAP DIANTAR KE KASIR.");

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
