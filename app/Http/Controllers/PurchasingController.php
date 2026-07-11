<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PurchasingController extends Controller
{
    public function index()
    {
        // For the dropdown list and inventory table, load wholesalePrices relation
        $materials = Material::with('wholesalePrices')->orderBy('material_name', 'asc')->get();
        return view('purchasing.index', compact('materials'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'material_name' => 'required|string',
            'fixed_size' => 'nullable|numeric|min:0',
            'qty_bought' => 'required|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'wholesale' => 'nullable|array',
            'wholesale.*.min_qty' => 'nullable|integer|min:1',
            'wholesale.*.price' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            // Find existing or create new
            $material = Material::where('material_name', $request->material_name)
                ->where('fixed_size', $request->fixed_size)
                ->first();

            if (!$material) {
                $material = Material::create([
                    'material_name' => $request->material_name,
                    'fixed_size' => $request->fixed_size,
                    'purchase_price' => $request->purchase_price,
                    'retail_price' => $request->retail_price,
                    'stock_qty' => 0
                ]);
            } else {
                // Update pricing to the latest
                $material->purchase_price = $request->purchase_price;
                $material->retail_price = $request->retail_price;
            }
            
            // Add stock
            $material->stock_qty += $request->qty_bought;
            $material->save();

            // Log purchase
            Purchase::create([
                'material_id' => $material->id,
                'user_id' => auth()->id(),
                'qty_bought' => $request->qty_bought,
                'total_cost' => $request->qty_bought * $request->purchase_price,
            ]);

            // Sync Wholesale Tiers — filter out empty rows
            $material->wholesalePrices()->delete();
            if ($request->has('wholesale') && is_array($request->wholesale)) {
                foreach ($request->wholesale as $tier) {
                    if (!empty($tier['min_qty']) && !empty($tier['price'])) {
                        $material->wholesalePrices()->create([
                            'min_qty' => $tier['min_qty'],
                            'wholesale_price' => $tier['price']
                        ]);
                    }
                }
            }
        });

        return redirect()->route('purchasing.index')->with('success', 'Stock updated successfully.');
    }
}
