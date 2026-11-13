<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\Branch;
use App\Models\MaterialRoll;
use App\Models\DailyClosingWaste;
use App\Models\DailyClosingReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * 1. Data Stok & Opname (Inventory & Valuation)
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Material::with(['supplier', 'branch', 'wholesalePrices', 'rolls', 'parentMaterial', 'linkedVariants'])->orderBy('material_name', 'asc');

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
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where('material_name', 'like', "%{$request->search}%");
        }

        if ($request->get('filter') === 'draft_opname') {
            $query->whereNotNull('opname_draft_qty');
        }

        // Count pending draft opname items in selected branch
        $baseBranchQuery = Material::query();
        if ($branchId && $branchId !== 'all') {
            $baseBranchQuery->where('branch_id', $branchId);
        }
        $draftOpnameCount = (clone $baseBranchQuery)->whereNotNull('opname_draft_qty')->count();

        // Calculate summary metrics across full dataset
        $totalItems = (clone $query)->count();
        $totalStockQty = (clone $query)->sum('stock_qty');
        $totalAssetValue = (clone $query)->selectRaw('SUM(stock_qty * purchase_price) as total_val')->value('total_val') ?: 0;
        $lowStockCount = (clone $query)->where('stock_qty', '<', 5)->count();

        $perPage = (int)$request->input('per_page', 50);
        $materials = $query->paginate($perPage)->withQueryString();

        // Count pending inspection items for badge counter
        $pendingCount = Purchase::where('status', 'pending_verification')
            ->when(!$user->isOwner(), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })->count();

        $branches = Branch::orderBy('nama_cabang')->get();
        $suppliers = \App\Models\Supplier::orderBy('name')->get();

        $catQuery = Material::query();
        if ($branchId && $branchId !== 'all') {
            $catQuery->where('branch_id', $branchId);
        }
        $categories = $catQuery->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category');

        $selectedCategory = $request->get('category', 'all');

        $activeBranchId = ($branchId && $branchId !== 'all') ? (int)$branchId : ($user->branch_id ?: 1);

        $materialsForWaste = Material::with(['rolls' => fn($rq) => $rq->orderBy('roll_width_m', 'asc')])
            ->where('branch_id', $activeBranchId)
            ->orderBy('category')
            ->orderBy('material_name')
            ->get(['id', 'branch_id', 'material_name', 'category', 'unit', 'purchase_price', 'stock_qty']);

        $recentWastes = DailyClosingWaste::with(['material', 'materialRoll', 'user', 'branch'])
            ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get();

        return view('stock.index', compact(
            'materials',
            'totalItems',
            'totalStockQty',
            'totalAssetValue',
            'lowStockCount',
            'draftOpnameCount',
            'pendingCount',
            'branches',
            'suppliers',
            'categories',
            'selectedCategory',
            'branchId',
            'materialsForWaste',
            'recentWastes'
        ));
    }

    /**
     * Store a newly created Material/Product directly from Stock & Inventory
     */
    public function storeProduct(Request $request)
    {
        $request->validate([
            'material_name'  => 'required|string|max:255',
            'category'       => 'nullable|string|max:100',
            'supplier_id'    => 'nullable|exists:suppliers,id',
            'unit'           => 'nullable|string|max:50',
            'fixed_size'     => 'nullable|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'has_click_charge' => 'nullable|boolean',
            'click_charge'   => 'nullable|numeric|min:0',
            'retail_price'   => 'required|numeric|min:0',
            'stock_qty'      => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $branchId = ($user->isOwner() || $user->isSuperAdmin()) && $request->filled('branch_id') && $request->branch_id !== 'all' 
            ? $request->branch_id 
            : ($user->branch_id ?: (\App\Models\Branch::first()->id ?? 1));

        $hasClickCharge = $request->boolean('has_click_charge');
        $clickCharge = $hasClickCharge ? (float) $request->input('click_charge', 0) : 0;

        $material = Material::create([
            'branch_id'      => $branchId,
            'category'       => $request->category ?: 'Lainnya',
            'supplier_id'    => $request->supplier_id ?: null,
            'material_name'  => $request->material_name,
            'unit'           => $request->unit ?: 'Pcs',
            'fixed_size'     => $request->fixed_size,
            'purchase_price' => $request->purchase_price,
            'has_click_charge' => $hasClickCharge,
            'click_charge'   => $clickCharge,
            'retail_price'   => $request->retail_price,
            'stock_qty'      => $request->stock_qty,
        ]);

        // Save wholesale tiers if provided
        $wholesaleTiers = [];
        if ($request->has('wholesale') && is_array($request->wholesale)) {
            foreach ($request->wholesale as $tier) {
                if (isset($tier['min_qty']) && $tier['min_qty'] > 0 && isset($tier['price']) && $tier['price'] > 0) {
                    $wholesaleTiers[] = ['min_qty' => $tier['min_qty'], 'wholesale_price' => $tier['price']];
                    \App\Models\MaterialWholesalePrice::create([
                        'material_id'     => $material->id,
                        'min_qty'         => $tier['min_qty'],
                        'wholesale_price' => $tier['price'],
                    ]);
                }
            }
        } elseif ($request->filled('wholesale_min_qty') && is_array($request->wholesale_min_qty)) {
            foreach ($request->wholesale_min_qty as $index => $minQty) {
                if ($minQty > 0 && isset($request->wholesale_price[$index]) && $request->wholesale_price[$index] > 0) {
                    $tierPrice = $request->wholesale_price[$index];
                    $wholesaleTiers[] = ['min_qty' => $minQty, 'wholesale_price' => $tierPrice];
                    \App\Models\MaterialWholesalePrice::create([
                        'material_id'     => $material->id,
                        'min_qty'         => $minQty,
                        'wholesale_price' => $tierPrice,
                    ]);
                }
            }
        }

        // Replicate to all other branches with stock_qty = 0
        $otherBranches = Branch::where('id', '!=', $branchId)->get();
        foreach ($otherBranches as $otherBranch) {
            $otherMat = Material::where('branch_id', $otherBranch->id)->where('material_name', $material->material_name)->first();
            if (!$otherMat) {
                $createdMat = Material::create([
                    'branch_id'      => $otherBranch->id,
                    'category'       => $material->category,
                    'supplier_id'    => $material->supplier_id,
                    'material_name'  => $material->material_name,
                    'unit'           => $material->unit ?: 'Pcs',
                    'fixed_size'     => $material->fixed_size,
                    'purchase_price' => $material->purchase_price,
                    'has_click_charge' => $material->has_click_charge,
                    'click_charge'   => $material->click_charge,
                    'retail_price'   => $material->retail_price,
                    'stock_qty'      => 0, // initial stock 0 for other branches
                ]);

                foreach ($wholesaleTiers as $tier) {
                    \App\Models\MaterialWholesalePrice::create([
                        'material_id'     => $createdMat->id,
                        'min_qty'         => $tier['min_qty'],
                        'wholesale_price' => $tier['wholesale_price'],
                    ]);
                }
            }
        }

        return redirect()->route('stock.index')->with('success', "Produk / Bahan baku '{$material->material_name}' berhasil ditambahkan dan disinkronkan ke seluruh cabang!");
    }

    /**
     * 2. Pemeriksaan Barang Masuk (Pending Inspection / GRN)
     */
    public function inspection(?Request $request = null)
    {
        $request = $request ?? request();
        $user = Auth::user();

        $pendingQuery = Purchase::with(['material', 'supplier', 'user', 'branch'])
            ->where('status', 'pending_verification')
            ->orderBy('created_at', 'desc');

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
            $pendingQuery->where('branch_id', $branchId);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $pendingQuery->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('vendor_ref', 'like', "%{$search}%")
                  ->orWhereHas('material', function ($mq) use ($search) {
                      $mq->where('material_name', 'like', "%{$search}%");
                  });
            });
        }

        $pendingPurchases = $pendingQuery->get();
        $pendingCount = $pendingPurchases->count();
        $branches = Branch::orderBy('nama_cabang')->get();

        return view('stock.inspection', compact('pendingPurchases', 'pendingCount', 'branches', 'branchId'));
    }

    /**
     * 3. Riwayat Retur & Reject (Quality Control Return History)
     */
    public function rejected(?Request $request = null)
    {
        $request = $request ?? request();
        $user = Auth::user();

        $rejectedQuery = Purchase::with(['material', 'supplier', 'user', 'branch', 'verifiedBy'])
            ->where('status', 'rejected')
            ->orderBy('verified_at', 'desc');

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
            $rejectedQuery->where('branch_id', $branchId);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $rejectedQuery->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('vendor_ref', 'like', "%{$search}%")
                  ->orWhereHas('material', function ($mq) use ($search) {
                      $mq->where('material_name', 'like', "%{$search}%");
                  });
            });
        }

        $rejectedPurchases = $rejectedQuery->get();
        $rejectedCount = $rejectedPurchases->count();
        $branches = Branch::orderBy('nama_cabang')->get();

        return view('stock.rejected', compact('rejectedPurchases', 'rejectedCount', 'branches'));
    }

    /**
     * Update Stock Opname (Simpan Draft Sementara / Terapkan ke Stok Sistem / Batalkan Draft)
     */
    public function update(Request $request, Material $material)
    {
        $user = Auth::user();

        if (!$user->isOwner() && !$user->isSuperAdmin() && $material->branch_id != $user->branch_id) {
            abort(403, 'Anda tidak berhak mengubah stok cabang lain.');
        }

        $validated = $request->validate([
            'category' => 'nullable|string|max:100',
            'stock_qty' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'has_click_charge' => 'nullable|boolean',
            'click_charge' => 'nullable|numeric|min:0',
            'retail_price' => 'nullable|numeric|min:0',
            'opname_notes' => 'nullable|string|max:500',
            'opname_action' => 'nullable|string|in:draft,apply,discard',
            'wholesale' => 'nullable|array',
            'wholesale.*.min_qty' => 'nullable|integer|min:1',
            'wholesale.*.price' => 'nullable|numeric|min:0',
        ]);

        $action = $request->input('opname_action', 'apply');

        // 1. BATALKAN DRAFT OPNAME
        if ($action === 'discard') {
            DB::transaction(function () use ($material, $user) {
                $primaryId = $material->parent_material_id ?: $material->id;
                Material::where('id', $primaryId)->orWhere('parent_material_id', $primaryId)->update([
                    'opname_draft_qty' => null,
                    'opname_draft_notes' => null,
                    'opname_draft_at' => null,
                    'opname_draft_by' => null,
                ]);

                \App\Models\StockOpnameLog::create([
                    'branch_id' => $material->branch_id,
                    'material_id' => $material->id,
                    'user_id' => $user->id,
                    'system_stock' => (float)$material->stock_qty,
                    'physical_stock' => 0,
                    'difference' => 0,
                    'status' => 'discarded',
                    'notes' => 'Draft opname dibatalkan oleh operator.',
                ]);
            });

            return redirect()->route('stock.index')->with('success', "Draft hitung fisik untuk {$material->material_name} telah dibatalkan.");
        }

        $physicalStock = round((float)$validated['stock_qty'], 2);
        $systemStock = round((float)$material->stock_qty, 2);
        $diff = round($physicalStock - $systemStock, 2);

        DB::transaction(function () use ($request, $material, $validated, $user, $action, $physicalStock, $systemStock, $diff) {
            $primaryId = $material->parent_material_id ?: $material->id;

            if ($action === 'draft') {
                // HANYA SIMPAN SEMENTARA SEBAGAI DRAFT - JANGAN UBAH STOK SISTEM!
                $material->opname_draft_qty = $physicalStock;
                $material->opname_draft_notes = $validated['opname_notes'] ?? null;
                $material->opname_draft_at = now();
                $material->opname_draft_by = $user->id;

                // Sync draft ke varian linked (1 Sisi / 2 Sisi)
                Material::where('id', $primaryId)->orWhere('parent_material_id', $primaryId)->update([
                    'opname_draft_qty' => $physicalStock,
                    'opname_draft_notes' => $validated['opname_notes'] ?? null,
                    'opname_draft_at' => now(),
                    'opname_draft_by' => $user->id,
                ]);
            } else {
                // TERAPKAN KE STOK SISTEM & BERSIHKAN DRAFT
                $material->stock_qty = $physicalStock;
                $material->opname_draft_qty = null;
                $material->opname_draft_notes = null;
                $material->opname_draft_at = null;
                $material->opname_draft_by = null;

                // Sinkronisasi stok fisik ke varian linked (1 Sisi / 2 Sisi berbagi 1 stok)
                Material::where('id', $primaryId)->orWhere('parent_material_id', $primaryId)->update([
                    'stock_qty' => $physicalStock,
                    'opname_draft_qty' => null,
                    'opname_draft_notes' => null,
                    'opname_draft_at' => null,
                    'opname_draft_by' => null,
                ]);
            }

            if ($request->filled('category')) {
                $material->category = $validated['category'];
            }
            if ($request->filled('purchase_price')) {
                $material->purchase_price = $validated['purchase_price'];
            }
            if ($request->has('has_click_charge')) {
                $material->has_click_charge = $request->boolean('has_click_charge');
                $material->click_charge = $material->has_click_charge ? (float) $request->input('click_charge', 0) : 0;
            }
            if ($request->filled('retail_price')) {
                $material->retail_price = $validated['retail_price'];
            }

            $material->save();

            // Sync Wholesale Price Tiers
            if ($request->has('wholesale')) {
                $material->wholesalePrices()->delete();
                if (is_array($request->wholesale)) {
                    foreach ($request->wholesale as $tier) {
                        if (!empty($tier['min_qty']) && !empty($tier['price'])) {
                            $material->wholesalePrices()->create([
                                'min_qty' => (int) $tier['min_qty'],
                                'wholesale_price' => (float) $tier['price'],
                            ]);
                        }
                    }
                }
            }

            // Catat log riwayat audit opname
            \App\Models\StockOpnameLog::create([
                'branch_id' => $material->branch_id,
                'material_id' => $material->id,
                'user_id' => $user->id,
                'system_stock' => $systemStock,
                'physical_stock' => $physicalStock,
                'difference' => $diff,
                'status' => $action === 'draft' ? 'draft' : 'applied',
                'notes' => $validated['opname_notes'] ?? ($action === 'draft' ? 'Draft Opname Sementara' : 'Penyesuaian Stok Resmi'),
            ]);
        });

        $diffFormatted = ($diff > 0 ? "+{$diff}" : "{$diff}");
        if ($action === 'draft') {
            return redirect()->route('stock.index')->with('success', "📝 Draft hitung fisik {$material->material_name} berhasil disimpan sementara (Fisik: {$physicalStock}, Selisih: {$diffFormatted} Unit). Stok sistem TIDAK diubah (tetap {$systemStock}).");
        } else {
            return redirect()->route('stock.index')->with('success', "✅ Stok sistem {$material->material_name} resmi diperbarui menjadi {$physicalStock} Unit (Penyesuaian: {$diffFormatted}).");
        }
    }

    /**
     * Verify & Accept Goods Receipt
     */
    public function verify(Request $request, Purchase $purchase)
    {
        $user = Auth::user();

        if (!$user->isOwner() && !$user->isSuperAdmin() && $purchase->branch_id != $user->branch_id) {
            abort(403, 'Anda tidak berhak memverifikasi penerimaan barang cabang lain.');
        }

        if ($purchase->status !== 'pending_verification') {
            return redirect()->route('stock.inspection')->with('error', 'Transaksi pengadaan ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'qty_received' => 'required|integer|min:1',
            'verification_notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($purchase, $validated, $user) {
            $qtyReceived = (int) $validated['qty_received'];

            // 1. Increment Material Stock & sync linked variants
            $material = $purchase->material;
            $newStock = round((float)$material->stock_qty + $qtyReceived, 2);
            $material->syncStockToLinked($newStock);

            // 2. Update Purchase Status
            $purchase->status = 'received';
            $purchase->verified_at = now();
            $purchase->verified_by = $user->id;
            $purchase->verification_notes = $validated['verification_notes'] ?? 'Diterima & Sesuai fisik.';
            $purchase->save();
        });

        return redirect()->route('stock.inspection')->with('success', "Penerimaan barang #{$purchase->po_number} berhasil diverifikasi dan stok telah ditambahkan ke inventaris.");
    }

    /**
     * Reject Goods Receipt
     */
    public function reject(Request $request, Purchase $purchase)
    {
        $user = Auth::user();

        if (!$user->isOwner() && !$user->isSuperAdmin() && $purchase->branch_id != $user->branch_id) {
            abort(403, 'Anda tidak berhak menolak penerimaan barang cabang lain.');
        }

        if ($purchase->status !== 'pending_verification') {
            return redirect()->route('stock.inspection')->with('error', 'Transaksi pengadaan ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'verification_notes' => 'required|string|max:500',
        ]);

        $purchase->status = 'rejected';
        $purchase->verified_at = now();
        $purchase->verified_by = $user->id;
        $purchase->verification_notes = $validated['verification_notes'];
        $purchase->save();

        return redirect()->route('stock.rejected')->with('success', "Pengadaan barang #{$purchase->po_number} berhasil ditolak/retur dan dicatat pada riwayat retur.");
    }

    /**
     * Store material waste (Trial & Error Mesin) directly from Stock
     */
    public function storeWaste(Request $request)
    {
        $user = Auth::user();
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();

        $request->validate([
            'material_id'      => 'required|exists:materials,id',
            'material_roll_id' => 'nullable|exists:material_rolls,id',
            'waste_qty'        => 'required|numeric|min:0.01',
            'reason'           => 'required|string|max:255',
            'notes'            => 'nullable|string|max:500',
            'branch_id'        => 'nullable|exists:branches,id',
        ]);

        $material = Material::findOrFail($request->material_id);
        $branchId = ($isOwnerOrSuper && $request->filled('branch_id')) 
            ? (int)$request->branch_id 
            : ($material->branch_id ?: ($user->branch_id ?: 1));

        $rollId = $request->filled('material_roll_id') ? (int)$request->material_roll_id : null;
        $wasteQty = (float)$request->waste_qty;

        return DB::transaction(function () use ($request, $user, $material, $branchId, $rollId, $wasteQty) {
            $roll = $rollId ? MaterialRoll::find($rollId) : null;

            if ($roll) {
                $unit = 'm';
                $rollWidth = (float)$roll->roll_width_m ?: 1.0;
                $hppPerUnit = (float)$material->purchase_price * $rollWidth;
                $totalHpp = round($wasteQty * $hppPerUnit, 2);

                $newLength = max(0, round((float)$roll->current_length_m - $wasteQty, 2));
                $roll->update(['current_length_m' => $newLength]);
            } else {
                $unit = $material->unit ?: 'pcs';
                $hppPerUnit = (float)$material->purchase_price;
                $totalHpp = round($wasteQty * $hppPerUnit, 2);

                $newStock = max(0, round((float)$material->stock_qty - $wasteQty, 2));
                $material->syncStockToLinked($newStock);
            }

            // Check if there is an existing daily closing report today for this branch
            $todayDate = date('Y-m-d');
            $existingReport = DailyClosingReport::where('branch_id', $branchId)
                ->whereDate('closing_date', $todayDate)
                ->first();

            $wasteRecord = DailyClosingWaste::create([
                'daily_closing_report_id' => $existingReport ? $existingReport->id : null,
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'material_id' => $material->id,
                'material_roll_id' => $roll ? $roll->id : null,
                'waste_qty' => $wasteQty,
                'unit' => $unit,
                'hpp_per_unit' => $hppPerUnit,
                'total_hpp' => $totalHpp,
                'reason' => $request->reason,
                'notes' => $request->notes,
            ]);

            // If report exists for today, update its total_waste_hpp and snapshot
            if ($existingReport) {
                $existingReport->increment('total_waste_hpp', $totalHpp);
                $snap = $existingReport->waste_data ?: [];
                $snap[] = [
                    'material_id' => $material->id,
                    'material_name' => $material->material_name,
                    'material_roll_id' => $roll ? $roll->id : null,
                    'roll_name' => $roll ? ($roll->roll_name ?: "Roll {$roll->roll_width_m}m") : null,
                    'waste_qty' => $wasteQty,
                    'unit' => $unit,
                    'hpp_per_unit' => $hppPerUnit,
                    'total_hpp' => $totalHpp,
                    'reason' => $request->reason,
                    'notes' => $request->notes,
                ];
                $existingReport->update(['waste_data' => $snap]);
            }

            $rollInfo = $roll ? " (Roll {$roll->roll_width_m}m, sisa {$roll->current_length_m}m)" : " (Sisa: {$material->stock_qty} {$unit})";
            return redirect()->back()->with('success', "Waste bahan '{$material->material_name}'{$rollInfo} sebanyak {$wasteQty} {$unit} berhasil dicatat! Sisa stok telah dipotong otomatis.");
        });
    }

    /**
     * Halaman Produk Sentral & Sebaran Stok Lintas Cabang (1 Master Row per Produk).
     * Dapat diakses oleh Owner/SuperAdmin maupun Manager untuk memantau stok terbaru seluruh cabang.
     */
    public function central(Request $request)
    {
        $user = auth()->user();
        if (!$user || !($user->isOwner() || $user->isSuperAdmin() || $user->isManager())) {
            abort(403, 'Akses ditolak. Hanya Owner dan Manager yang dapat melihat stok sentral seluruh cabang.');
        }

        $branches = \App\Models\Branch::orderBy('id')->get();
        $branchMap = $branches->keyBy('id');

        $allMaterials = \App\Models\Material::with([
                'branch',
                'parentMaterial',
                'rolls' => function ($q) {
                    $q->where('status', 'active');
                },
            ])
            ->whereNull('deleted_at')
            ->orderBy('material_name')
            ->orderBy('branch_id')
            ->get();

        // Kumpulkan opsi filter dari data aktif
        $categories = $allMaterials->pluck('category')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $machineTags = $allMaterials->pluck('machine_tag')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // Konsolidasi 1 row per nama produk (case-insensitive trimmed key)
        $grouped = [];
        foreach ($allMaterials as $mat) {
            $canonicalName = trim((string) $mat->material_name);
            if ($canonicalName === '') {
                continue;
            }
            $key = mb_strtolower($canonicalName);

            $effectiveQty = ($mat->parent_material_id && $mat->parentMaterial)
                ? (float) $mat->parentMaterial->stock_qty
                : (float) $mat->stock_qty;

            $activeRolls = $mat->rolls ?? collect();
            $rollCount = $activeRolls->count();
            $rollTotalMeters = (float) $activeRolls->sum('current_length_m');

            $branchObj = $mat->branch ?? ($mat->branch_id ? $branchMap->get($mat->branch_id) : null);
            $fullBranchName = ($branchObj && $branchObj->nama_cabang) ? $branchObj->nama_cabang : ('Cabang #' . ($mat->branch_id ?? '-'));
            $shortBranchName = trim(str_ireplace(
                ['Cabang ', 'Home Snaprint ', 'Snaprint ', '(Pusat)'],
                '',
                $fullBranchName
            ));
            if ($shortBranchName === '') {
                $shortBranchName = $fullBranchName;
            }

            $stockState = $effectiveQty <= 0 ? 'empty' : ($effectiveQty <= 5 ? 'low' : 'healthy');
            $unit = $mat->unit ?: 'Pcs';

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'key' => $key,
                    'material_name' => $canonicalName,
                    'category' => $mat->category ?: 'Umum',
                    'unit' => $unit,
                    'machine_tag' => $mat->machine_tag,
                    'has_click_charge' => (bool) $mat->has_click_charge,
                    'click_charge' => (float) ($mat->click_charge ?? 0),
                    'print_sides' => $mat->print_sides,
                    'is_linked_variant' => (bool) $mat->parent_material_id,
                    'parent_name' => $mat->parentMaterial ? $mat->parentMaterial->material_name : null,
                    'purchase_price' => (float) ($mat->purchase_price ?? 0),
                    'retail_price' => (float) ($mat->retail_price ?? 0),
                    'min_retail_price' => (float) ($mat->retail_price ?? 0),
                    'max_retail_price' => (float) ($mat->retail_price ?? 0),
                    'total_stock' => 0.0,
                    'total_asset_value' => 0.0,
                    'branch_count' => 0,
                    'empty_branches' => 0,
                    'low_branches' => 0,
                    'healthy_branches' => 0,
                    'has_opname_draft' => false,
                    'total_rolls' => 0,
                    'total_roll_meters' => 0.0,
                    'latest_updated_at' => $mat->updated_at,
                    'branch_stocks' => [],
                ];
            }

            // Update harga rentang & tag jika ada baris cabang yang memiliki nilai lebih lengkap
            $retailPrice = (float) ($mat->retail_price ?? 0);
            if ($retailPrice > 0) {
                if ($grouped[$key]['min_retail_price'] <= 0 || $retailPrice < $grouped[$key]['min_retail_price']) {
                    $grouped[$key]['min_retail_price'] = $retailPrice;
                }
                if ($retailPrice > $grouped[$key]['max_retail_price']) {
                    $grouped[$key]['max_retail_price'] = $retailPrice;
                }
            }
            if (!$grouped[$key]['machine_tag'] && $mat->machine_tag) {
                $grouped[$key]['machine_tag'] = $mat->machine_tag;
            }
            if (!$grouped[$key]['has_click_charge'] && $mat->has_click_charge) {
                $grouped[$key]['has_click_charge'] = true;
                $grouped[$key]['click_charge'] = (float) ($mat->click_charge ?? 0);
            }
            if ($mat->purchase_price > 0 && $grouped[$key]['purchase_price'] <= 0) {
                $grouped[$key]['purchase_price'] = (float) $mat->purchase_price;
            }
            if ($mat->updated_at && (!$grouped[$key]['latest_updated_at'] || $mat->updated_at->gt($grouped[$key]['latest_updated_at']))) {
                $grouped[$key]['latest_updated_at'] = $mat->updated_at;
            }
            if ($mat->opname_draft_qty !== null) {
                $grouped[$key]['has_opname_draft'] = true;
            }

            $grouped[$key]['total_stock'] += $effectiveQty;
            $grouped[$key]['total_asset_value'] += max(0, $effectiveQty) * (float) ($mat->purchase_price ?? 0);
            $grouped[$key]['branch_count']++;
            $grouped[$key]['total_rolls'] += $rollCount;
            $grouped[$key]['total_roll_meters'] += $rollTotalMeters;

            if ($stockState === 'empty') {
                $grouped[$key]['empty_branches']++;
            } elseif ($stockState === 'low') {
                $grouped[$key]['low_branches']++;
            } else {
                $grouped[$key]['healthy_branches']++;
            }

            $grouped[$key]['branch_stocks'][] = [
                'material_id' => $mat->id,
                'branch_id' => $mat->branch_id,
                'branch_name' => $fullBranchName,
                'short_name' => $shortBranchName,
                'stock_qty' => $effectiveQty,
                'unit' => $unit,
                'status' => $stockState,
                'retail_price' => $retailPrice,
                'purchase_price' => (float) ($mat->purchase_price ?? 0),
                'roll_count' => $rollCount,
                'roll_meters' => $rollTotalMeters,
                'opname_draft_qty' => $mat->opname_draft_qty,
                'is_linked' => (bool) $mat->parent_material_id,
                'updated_at' => $mat->updated_at,
            ];
        }

        // Urutkan label cabang di dalam setiap produk berdasar branch_id
        foreach ($grouped as &$item) {
            usort($item['branch_stocks'], function ($a, $b) {
                return ((int) $a['branch_id']) <=> ((int) $b['branch_id']);
            });
        }
        unset($item);

        $masterCollection = collect(array_values($grouped));

        // Hitung KPI Sentral Global sebelum filter tabel
        $summary = [
            'total_master_products' => $masterCollection->count(),
            'total_branches' => $branches->count(),
            'total_combined_stock' => $masterCollection->sum('total_stock'),
            'total_asset_value' => $masterCollection->sum('total_asset_value'),
            'products_with_empty_branch' => $masterCollection->where('empty_branches', '>', 0)->count(),
            'products_with_low_branch' => $masterCollection->where('low_branches', '>', 0)->count(),
        ];

        // Terapkan filter
        $search = trim((string) $request->input('search', ''));
        $selectedCategory = $request->input('category', 'all');
        $selectedMachine = $request->input('machine_tag', 'all');
        $selectedBranchId = $request->input('branch_id', 'all');
        $selectedStatus = $request->input('stock_status', 'all');
        $sort = $request->input('sort', 'name_asc');

        $filtered = $masterCollection->filter(function ($item) use ($search, $selectedCategory, $selectedMachine, $selectedBranchId, $selectedStatus) {
            if ($search !== '') {
                $needle = mb_strtolower($search);
                $haystack = mb_strtolower(
                    $item['material_name'] . ' ' .
                    $item['category'] . ' ' .
                    ($item['machine_tag'] ?? '') . ' ' .
                    implode(' ', array_column($item['branch_stocks'], 'branch_name'))
                );
                if (!str_contains($haystack, $needle)) {
                    return false;
                }
            }

            if ($selectedCategory !== 'all' && $selectedCategory !== '' && $item['category'] !== $selectedCategory) {
                return false;
            }

            if ($selectedMachine !== 'all' && $selectedMachine !== '') {
                if ($selectedMachine === 'km_only') {
                    if (stripos((string) $item['machine_tag'], 'KM') === false && !$item['has_click_charge']) {
                        return false;
                    }
                } elseif ($selectedMachine === 'non_km') {
                    if (stripos((string) $item['machine_tag'], 'KM') !== false || $item['has_click_charge']) {
                        return false;
                    }
                } elseif ($item['machine_tag'] !== $selectedMachine) {
                    return false;
                }
            }

            if ($selectedBranchId !== 'all' && $selectedBranchId !== '') {
                $hasBranch = collect($item['branch_stocks'])->contains(function ($bs) use ($selectedBranchId) {
                    return (string) $bs['branch_id'] === (string) $selectedBranchId;
                });
                if (!$hasBranch) {
                    return false;
                }
            }

            if ($selectedStatus === 'empty' && $item['empty_branches'] <= 0) {
                return false;
            } elseif ($selectedStatus === 'low' && $item['low_branches'] <= 0) {
                return false;
            } elseif ($selectedStatus === 'available' && $item['total_stock'] <= 0) {
                return false;
            }

            return true;
        });

        // Sorting
        $filtered = match ($sort) {
            'stock_desc' => $filtered->sortByDesc('total_stock'),
            'stock_asc' => $filtered->sortBy('total_stock'),
            'updated_desc' => $filtered->sortByDesc(fn ($item) => $item['latest_updated_at'] ? $item['latest_updated_at']->timestamp : 0),
            default => $filtered->sortBy(fn ($item) => mb_strtolower($item['material_name'])),
        };

        $filtered = $filtered->values();

        // Pagination (default 250 agar seluruh master produk dapat langsung difilter lewat Search bar atas)
        $perPage = (int) $request->input('per_page', 250);
        if ($perPage < 25 || $perPage > 500) {
            $perPage = 250;
        }
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $pageItems = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $masterProducts = new \Illuminate\Pagination\LengthAwarePaginator(
            $pageItems,
            $filtered->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $canViewPurchasePrice = $user->isOwner() || $user->isSuperAdmin();
        $isOwnerOrSuper = $user->isOwner() || $user->isSuperAdmin();

        return view('stock.central', compact(
            'masterProducts',
            'branches',
            'categories',
            'machineTags',
            'summary',
            'search',
            'selectedCategory',
            'selectedMachine',
            'selectedBranchId',
            'selectedStatus',
            'sort',
            'perPage',
            'canViewPurchasePrice',
            'isOwnerOrSuper'
        ));
    }
}
