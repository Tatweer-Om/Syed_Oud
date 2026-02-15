<?php

namespace App\Http\Controllers;

use App\Models\ProductionDraft;
use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\ProductionWastageMaterial;
use App\Models\Packaging;
use App\Models\PackagingWastageMaterial;
use App\Models\ProductionHistory;
use App\Models\Stock;
use App\Models\Material;
use App\Models\History;
use App\Models\MaterialQuantityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductionController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        return view('stock.production');
    }

    public function viewProduction()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        return view('stock.view_production');
    }

    /** Get all stocks for searchable select (id, stock_name, barcode) */
    public function getStocksForProduction()
    {
        $stocks = Stock::select('id', 'stock_name', 'barcode')
            ->whereNotNull('stock_name')
            ->orderBy('stock_name', 'ASC')
            ->get();
        return response()->json($stocks);
    }

    /** Get materials for production (id, material_name, unit) - no price needed */
    public function getMaterialsForProduction()
    {
        $materials = Material::select('id', 'material_name', 'unit')
            ->orderBy('material_name', 'ASC')
            ->get();
        return response()->json($materials);
    }

    /** List production drafts */
    public function getProductionDrafts(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $page = max(1, (int) $request->get('page', 1));

        $drafts = ProductionDraft::with('stock:id,stock_name,barcode')
            ->orderBy('updated_at', 'DESC')
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'stock' => $d->stock,
                    'estimated_output' => $d->estimated_output,
                    'total_quantity' => $d->total_quantity,
                    'total_items' => $d->total_items,
                    'notes' => $d->notes,
                    'created_at' => $d->created_at,
                    'updated_at' => $d->updated_at,
                ];
            });

        $total = $drafts->count();
        $slice = $drafts->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator($slice, $total, $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]);
        return $paginator;
    }

    /** Save new draft */
    public function storeDraft(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'production_date' => 'nullable|date',
            'estimated_output' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'materials' => 'required|array',
            'materials.*.material_id' => 'required',
            'materials.*.quantity' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $userName = $user->user_name ?? $user->name ?? 'system';
        $materials = $request->materials;
        $totalQuantity = 0;
        
        foreach ($materials as $m) {
            $totalQuantity += (float) ($m['quantity'] ?? 0);
        }

        $totalAmount = (float) ($request->total_amount ?? 0);
        $estimatedOutput = (float) ($request->estimated_output ?? 0);
        $costPerUnit = $estimatedOutput > 0 ? ($totalAmount / $estimatedOutput) : 0;

        $draft = ProductionDraft::create([
            'stock_id' => $request->stock_id,
            'production_date' => $request->production_date ?? now()->toDateString(),
            'estimated_output' => $estimatedOutput,
            'notes' => $request->notes,
            'materials_json' => $materials,
            'total_quantity' => $totalQuantity,
            'total_items' => count($materials),
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'cost_per_unit' => $costPerUnit,
            'user_id' => $user->id ?? null,
            'added_by' => $userName,
        ]);

        // Get stock name for history
        $stock = Stock::find($request->stock_id);
        $draftData = $draft->toArray();
        $draftData['stock_name'] = $stock->stock_name ?? null;

        // Log to history
        History::create([
            'operation' => 'create',
            'source' => 'production_draft',
            'previous_data' => null,
            'new_data' => $draftData,
            'added_by' => $userName,
            'user_id' => $user->id ?? null,
            'added_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.production_draft_saved', [], session('locale', 'en')),
            'draft_id' => $draft->id,
            'redirect_url' => url('view_production'),
        ]);
    }

    /** Get single draft (for edit form or view popup) */
    public function getDraft($id)
    {
        $draft = ProductionDraft::with('stock:id,stock_name,barcode')->findOrFail($id);
        return response()->json(['status' => 'success', 'draft' => $draft]);
    }

    /** Update draft */
    public function updateDraft(Request $request, $id)
    {
        $draft = ProductionDraft::with('stock')->findOrFail($id);
        
        $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'production_date' => 'nullable|date',
            'estimated_output' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'materials' => 'required|array',
            'materials.*.material_id' => 'required',
            'materials.*.quantity' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $dataBefore = $draft->toArray();
        $dataBefore['stock_name'] = $draft->stock->stock_name ?? null;

        $materials = $request->materials;
        $totalQuantity = 0;
        
        foreach ($materials as $m) {
            $totalQuantity += (float) ($m['quantity'] ?? 0);
        }

        $totalAmount = (float) ($request->total_amount ?? $draft->total_amount ?? 0);
        $estimatedOutput = (float) ($request->estimated_output ?? 0);
        $costPerUnit = $estimatedOutput > 0 ? ($totalAmount / $estimatedOutput) : 0;

        $draft->update([
            'stock_id' => $request->stock_id,
            'production_date' => $request->production_date ?? $draft->production_date,
            'estimated_output' => $estimatedOutput,
            'notes' => $request->notes,
            'materials_json' => $materials,
            'total_quantity' => $totalQuantity,
            'total_items' => count($materials),
            'total_amount' => $totalAmount,
            'cost_per_unit' => $costPerUnit,
            'updated_by' => $user->user_name ?? $user->name ?? 'system',
        ]);

        $dataAfter = $draft->fresh()->toArray();
        $dataAfter['stock_name'] = $draft->stock->stock_name ?? null;

        History::create([
            'operation' => 'update',
            'source' => 'production',
            'previous_data' => $dataBefore,
            'new_data' => $dataAfter,
            'added_by' => $user->user_name ?? $user->name ?? 'system',
            'user_id' => $user->id ?? null,
            'added_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.production_draft_updated', [], session('locale', 'en')),
            'redirect_url' => url('view_production'),
        ]);
    }

    /** Delete draft + save to history */
    public function deleteDraft($id)
    {
        $draft = ProductionDraft::with('stock')->findOrFail($id);
        $user = Auth::user();

        $deletedData = $draft->toArray();
        $deletedData['stock_name'] = $draft->stock->stock_name ?? null;

        History::create([
            'operation' => 'delete',
            'source' => 'production',
            'previous_data' => $deletedData,
            'new_data' => null,
            'added_by' => $user->user_name ?? $user->name ?? 'system',
            'user_id' => $user->id ?? null,
            'added_at' => now(),
        ]);

        $draft->delete();

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.production_draft_deleted', [], session('locale', 'en')),
        ]);
    }

    /** Edit draft page */
    public function editDraft($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $draft = ProductionDraft::with('stock')->findOrFail($id);
        return view('stock.production', ['draft' => $draft, 'is_edit' => true]);
    }

    /** Complete/Approve draft - move to productions table */
    public function completeDraft($id)
    {
        $draft = ProductionDraft::with('stock')->findOrFail($id);
        $user = Auth::user();
        $userName = $user->user_name ?? $user->name ?? 'system';

        DB::beginTransaction();
        try {
            // Calculate cost per unit
            $totalAmount = (float) ($draft->total_amount ?? 0);
            $estimatedOutput = (float) ($draft->estimated_output ?? 0);
            $costPerUnit = $estimatedOutput > 0 ? ($totalAmount / $estimatedOutput) : 0;

            // Create production record (status: under_process until completed)
            $production = Production::create([
                'stock_id' => $draft->stock_id,
                'production_date' => $draft->production_date ?? now()->toDateString(),
                'estimated_output' => $draft->estimated_output,
                'total_quantity' => $draft->total_quantity,
                'total_items' => $draft->total_items,
                'total_amount' => $totalAmount,
                'status' => 'under_process',
                'cost_per_unit' => $costPerUnit,
                'notes' => $draft->notes,
                'user_id' => $user->id ?? null,
                'added_by' => $userName,
            ]);

            // Generate production_id, filling_id, and batch_id
            $production->production_id = Production::generateProductionId($production->id);
            $production->filling_id = Production::generateFillingId($production->id);
            $production->batch_id = Production::generateBatchId($production->production_id, $production->filling_id);
            $production->save();

            // Create production detail record
            ProductionDetail::create([
                'production_id' => $production->id,
                'stock_id' => $draft->stock_id,
                'materials_json' => $draft->materials_json,
                'user_id' => $user->id ?? null,
                'added_by' => $userName,
            ]);

            // Deduct materials from inventory and create audit records
            $materials = $draft->materials_json ?? [];
            foreach ($materials as $materialData) {
                $materialId = $materialData['material_id'] ?? null;
                $quantityUsed = floatval($materialData['quantity'] ?? 0);
                $materialName = $materialData['material_name'] ?? null;

                if (!$materialId || $quantityUsed <= 0) {
                    continue;
                }

                $material = Material::find($materialId);
                if (!$material) {
                    continue;
                }

                // Get current quantity (stored in 'quantity' field, accessed via meters_per_roll accessor)
                $previousQuantity = floatval($material->quantity ?? 0);
                $newQuantity = max(0, $previousQuantity - $quantityUsed);

                // Update material quantity
                $material->quantity = $newQuantity;
                $material->updated_by = $userName;
                $material->save();

                // Create MaterialQuantityAudit record
                MaterialQuantityAudit::create([
                    'material_id' => $material->id,
                    'material_name' => $material->material_name ?? $materialName,
                    'stock_id' => $draft->stock_id,
                    'stock_code' => $draft->stock->barcode ?? null,
                    'source' => 'production',
                    'operation_type' => 'production_deducted',
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $newQuantity,
                    'quantity_change' => -$quantityUsed,
                    'remaining_quantity' => $newQuantity,
                    'user_id' => $user->id ?? null,
                    'added_by' => $userName,
                    'notes' => 'Material deducted for production: ' . $production->production_id,
                ]);
            }

            // Log to history
            History::create([
                'operation' => 'approve',
                'source' => 'production',
                'previous_data' => $draft->toArray(),
                'new_data' => array_merge($production->toArray(), [
                    'stock_name' => $draft->stock->stock_name ?? null,
                    'materials_json' => $draft->materials_json,
                ]),
                'added_by' => $userName,
                'user_id' => $user->id ?? null,
                'added_at' => now(),
            ]);

            // Delete the draft
            $draft->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.production_approved', [], session('locale', 'en')),
                'production_id' => $production->id,
                'redirect_url' => url('production/' . $production->id . '/profile'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => trans('messages.error', [], session('locale', 'en')) . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /** Get a single production (approved) for API */
    public function getProduction($id)
    {
        $production = Production::with(['stock:id,stock_name,barcode', 'details'])->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'draft' => [
                'id' => $production->id,
                'production_id' => $production->production_id,
                'filling_id' => $production->filling_id,
                'stock' => $production->stock,
                'estimated_output' => $production->estimated_output,
                'total_quantity' => $production->total_quantity,
                'total_items' => $production->total_items,
                'notes' => $production->notes,
                'materials_json' => $production->details->materials_json ?? [],
                'is_completed' => true,
                'created_at' => $production->created_at,
                'updated_at' => $production->updated_at,
            ],
        ]);
    }

    /** Production profile page */
    public function productionProfile($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $production = Production::with(['stock', 'details', 'packagings'])->findOrFail($id);
        return view('stock.production_profile', ['production' => $production]);
    }

    /** Production invoice - printable summary */
    public function productionInvoice($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $production = Production::with(['stock', 'details', 'packagings.details'])->findOrFail($id);
        $productionMaterials = $production->details->materials_json ?? [];
        $packaging = $production->packagings->first();
        $packagingMaterials = $packaging ? ($packaging->details->materials_json ?? []) : [];
        $productionWastage = ProductionWastageMaterial::where('production_id', $id)->get();
        $packagingWastage = $packaging ? PackagingWastageMaterial::where('packaging_id', $packaging->id)->get() : collect();
        $totalProductionCost = collect($productionMaterials)->sum('total');
        $totalPackagingCost = collect($packagingMaterials)->sum('total');
        $estimatedOutput = (float) ($production->estimated_output ?? 0);
        $productionActualOutput = (float) ($production->actual_output ?? 0);
        $packagingActualOutput = $packaging ? (float) ($packaging->actual_output ?? 0) : 0;
        // Final actual output: packaging actual (when packaging completed) is the true output; else production actual
        $actualOutput = ($packaging && ($packaging->status ?? '') === 'completed' && $packagingActualOutput > 0)
            ? $packagingActualOutput
            : $productionActualOutput;
        $totalMaterialCost = $totalProductionCost + $totalPackagingCost;
        $costPerUnitEstimated = $estimatedOutput > 0 ? ($totalMaterialCost / $estimatedOutput) : 0;
        $finalOutput = $actualOutput > 0 ? $actualOutput : $estimatedOutput;
        $costPerUnitActual = $finalOutput > 0 ? ($totalMaterialCost / $finalOutput) : 0;

        return view('stock.production_invoice', [
            'production' => $production,
            'productionMaterials' => $productionMaterials,
            'packagingMaterials' => $packagingMaterials,
            'productionWastage' => $productionWastage,
            'packagingWastage' => $packagingWastage,
            'totalMaterialCost' => $totalMaterialCost,
            'estimatedOutput' => $estimatedOutput,
            'actualOutput' => $actualOutput,
            'finalOutput' => $finalOutput,
            'costPerUnitEstimated' => $costPerUnitEstimated,
            'costPerUnitActual' => $costPerUnitActual,
        ]);
    }

    /** Get all productions (drafts + approved) for view_production table */
    public function getAllProductions(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $page = max(1, (int) $request->get('page', 1));

        // Get drafts
        $drafts = ProductionDraft::with('stock:id,stock_name,barcode')
            ->orderBy('updated_at', 'DESC')
            ->get()
            ->map(function ($d) {
                return [
                    'id' => 'draft_' . $d->id,
                    'draft_id' => $d->id,
                    'production_id' => null,
                    'filling_id' => null,
                    'batch_id' => null,
                    'stock' => $d->stock,
                    'estimated_output' => $d->estimated_output,
                    'total_quantity' => $d->total_quantity,
                    'total_items' => $d->total_items,
                    'total_amount' => $d->total_amount ?? 0,
                    'status' => $d->status ?? 'draft',
                    'cost_per_unit' => $d->cost_per_unit ?? 0,
                    'notes' => $d->notes,
                    'is_completed' => false,
                    'created_at' => $d->created_at ? $d->created_at->toISOString() : null,
                    'updated_at' => $d->updated_at ? $d->updated_at->toISOString() : null,
                ];
            });

        // Get approved productions with packaging status
        $productions = Production::with(['stock:id,stock_name,barcode', 'packagings'])
            ->orderBy('updated_at', 'DESC')
            ->get()
            ->map(function ($p) {
                $packaging = $p->packagings->first();
                $packagingStatus = '-';
                if ($packaging) {
                    $packagingStatus = ($packaging->status ?? '') === 'completed'
                        ? trans('messages.completed', [], session('locale', 'en'))
                        : trans('messages.under_process', [], session('locale', 'en'));
                }
                return [
                    'id' => $p->id,
                    'draft_id' => null,
                    'production_id' => $p->production_id,
                    'filling_id' => $p->filling_id,
                    'batch_id' => $p->batch_id,
                    'stock' => $p->stock,
                    'estimated_output' => $p->estimated_output,
                    'total_quantity' => $p->total_quantity,
                    'total_items' => $p->total_items,
                    'total_amount' => $p->total_amount ?? 0,
                    'status' => $p->status ?? 'under_process',
                    'packaging_status' => $packagingStatus,
                    'cost_per_unit' => $p->cost_per_unit ?? 0,
                    'notes' => $p->notes,
                    'is_completed' => true,
                    'created_at' => $p->created_at ? $p->created_at->toISOString() : null,
                    'updated_at' => $p->updated_at ? $p->updated_at->toISOString() : null,
                ];
            });

        // Merge and sort by updated_at descending
        $all = $drafts->concat($productions)->sortByDesc('updated_at')->values();

        $total = $all->count();
        $slice = $all->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator($slice, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
        return $paginator;
    }

    /** Add material to approved production */
    public function addMaterialToProduction(Request $request, $id)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ]);

        $production = Production::with('details')->findOrFail($id);
        $user = Auth::user();
        $userName = $user->user_name ?? $user->name ?? 'system';

        $material = Material::findOrFail($request->material_id);
        $quantity = (float) $request->quantity;

        DB::beginTransaction();
        try {
            // Get current materials from production detail
            $materials = $production->details->materials_json ?? [];
            
            // Check if material already exists
            $found = false;
            foreach ($materials as &$m) {
                if ($m['material_id'] == $request->material_id) {
                    $m['quantity'] = (float) $m['quantity'] + $quantity;
                    $m['total'] = (float) ($m['unit_price'] ?? 0) * $m['quantity'];
                    $found = true;
                    break;
                }
            }
            
            // If not found, add new material
            if (!$found) {
                $materials[] = [
                    'material_id' => $material->id,
                    'material_name' => $material->material_name,
                    'unit' => $material->unit,
                    'unit_price' => (float) $material->buy_price,
                    'quantity' => $quantity,
                    'total' => (float) $material->buy_price * $quantity,
                ];
            }
            
            // Update production detail
            $production->details->materials_json = $materials;
            $production->details->save();
            
            // Recalculate totals
            $totalQty = 0;
            $totalAmount = 0;
            foreach ($materials as $m) {
                $totalQty += (float) ($m['quantity'] ?? 0);
                $totalAmount += (float) ($m['total'] ?? 0);
            }
            
            $production->total_quantity = $totalQty;
            $production->total_items = count($materials);
            $production->total_amount = $totalAmount;
            $production->cost_per_unit = $production->estimated_output > 0 ? ($totalAmount / $production->estimated_output) : 0;
            $production->save();
            
            // Deduct from material inventory
            $material->quantity = (float) $material->quantity - $quantity;
            $material->save();
            
            // Create audit record (previous_quantity = before deduction, new_quantity/remaining_quantity = after)
            $previousQty = (float) $material->quantity + $quantity;
            $newQty = (float) $material->quantity;
            MaterialQuantityAudit::create([
                'material_id' => $material->id,
                'material_name' => $material->material_name,
                'operation_type' => 'production_deducted',
                'quantity_change' => -$quantity,
                'previous_quantity' => $previousQty,
                'new_quantity' => $newQty,
                'remaining_quantity' => $newQty,
                'source' => 'production',
                'notes' => 'Material added to production ' . $production->batch_id,
                'user_id' => $user->id ?? null,
                'added_by' => $userName,
            ]);
            
            // Log history
            History::create([
                'operation' => 'add_material',
                'source' => 'production',
                'previous_data' => null,
                'new_data' => [
                    'production_id' => $production->id,
                    'batch_id' => $production->batch_id,
                    'material_id' => $material->id,
                    'material_name' => $material->material_name,
                    'quantity' => $quantity,
                ],
                'added_by' => $userName,
                'user_id' => $user->id ?? null,
                'added_at' => now(),
            ]);
            
            // Production history
            ProductionHistory::create([
                'production_id' => $production->id,
                'batch_id' => $production->batch_id,
                'action' => 'addition',
                'material_id' => $material->id,
                'material_name' => $material->material_name,
                'quantity' => $quantity,
                'unit' => $material->unit,
                'notes' => $request->notes,
                'added_by' => $userName,
                'user_id' => $user->id ?? null,
            ]);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.material_added_success', [], session('locale', 'en')),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /** Remove material from approved production */
    public function removeMaterialFromProduction(Request $request, $id)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $production = Production::with('details')->findOrFail($id);
        $user = Auth::user();
        $userName = $user->user_name ?? $user->name ?? 'system';

        $material = Material::findOrFail($request->material_id);
        $quantity = (float) $request->quantity;

        DB::beginTransaction();
        try {
            // Get current materials from production detail
            $materials = $production->details->materials_json ?? [];
            
            // Find and reduce/remove material
            $found = false;
            $newMaterials = [];
            foreach ($materials as $m) {
                if ($m['material_id'] == $request->material_id) {
                    $found = true;
                    $currentQty = (float) $m['quantity'];
                    if ($quantity >= $currentQty) {
                        // Remove completely
                        $quantity = $currentQty; // Adjust to actual removed amount
                        continue;
                    } else {
                        // Reduce quantity
                        $m['quantity'] = $currentQty - $quantity;
                        $m['total'] = (float) ($m['unit_price'] ?? 0) * $m['quantity'];
                        $newMaterials[] = $m;
                    }
                } else {
                    $newMaterials[] = $m;
                }
            }
            
            if (!$found) {
                return response()->json([
                    'status' => 'error',
                    'message' => trans('messages.material_not_found_in_production', [], session('locale', 'en')),
                ], 400);
            }
            
            // Update production detail
            $production->details->materials_json = $newMaterials;
            $production->details->save();
            
            // Recalculate totals
            $totalQty = 0;
            $totalAmount = 0;
            foreach ($newMaterials as $m) {
                $totalQty += (float) ($m['quantity'] ?? 0);
                $totalAmount += (float) ($m['total'] ?? 0);
            }
            
            $production->total_quantity = $totalQty;
            $production->total_items = count($newMaterials);
            $production->total_amount = $totalAmount;
            $production->cost_per_unit = $production->estimated_output > 0 ? ($totalAmount / $production->estimated_output) : 0;
            $production->save();
            
            // Return to material inventory
            $material->quantity = (float) $material->quantity + $quantity;
            $material->save();
            
            // Create audit record (previous_quantity = before return, new_quantity/remaining_quantity = after)
            $previousQty = (float) $material->quantity - $quantity;
            $newQty = (float) $material->quantity;
            MaterialQuantityAudit::create([
                'material_id' => $material->id,
                'material_name' => $material->material_name,
                'operation_type' => 'production_returned',
                'quantity_change' => $quantity,
                'previous_quantity' => $previousQty,
                'new_quantity' => $newQty,
                'remaining_quantity' => $newQty,
                'source' => 'production',
                'notes' => 'Material removed from production ' . $production->batch_id,
                'user_id' => $user->id ?? null,
                'added_by' => $userName,
            ]);
            
            // Log history
            History::create([
                'operation' => 'remove_material',
                'source' => 'production',
                'previous_data' => null,
                'new_data' => [
                    'production_id' => $production->id,
                    'batch_id' => $production->batch_id,
                    'material_id' => $material->id,
                    'material_name' => $material->material_name,
                    'quantity' => $quantity,
                ],
                'added_by' => $userName,
                'user_id' => $user->id ?? null,
                'added_at' => now(),
            ]);
            
            // Production history
            ProductionHistory::create([
                'production_id' => $production->id,
                'batch_id' => $production->batch_id,
                'action' => 'removal',
                'material_id' => $material->id,
                'material_name' => $material->material_name,
                'quantity' => $quantity,
                'unit' => $material->unit,
                'notes' => $request->notes,
                'added_by' => $userName,
                'user_id' => $user->id ?? null,
            ]);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.material_removed_success', [], session('locale', 'en')),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /** Add wastage to production */
    public function addWastage(Request $request, $id)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0.01',
            'wastage_types' => 'required|array|min:1',
        ]);

        $production = Production::findOrFail($id);
        $user = Auth::user();
        $userName = $user->user_name ?? $user->name ?? 'system';

        $material = Material::findOrFail($request->material_id);
        $quantity = (float) $request->quantity;
        $wastageTypes = implode(',', $request->wastage_types);

        DB::beginTransaction();
        try {
            // Create wastage record
            ProductionWastageMaterial::create([
                'production_id' => $production->id,
                'batch_id' => $production->batch_id,
                'material_id' => $material->id,
                'material_name' => $material->material_name,
                'quantity' => $quantity,
                'unit' => $material->unit,
                'wastage_type' => $wastageTypes,
                'notes' => $request->notes,
                'user_id' => $user->id ?? null,
                'added_by' => $userName,
            ]);
            
            // Deduct from material inventory
            $material->quantity = (float) $material->quantity - $quantity;
            $material->save();
            
            // Create audit record
            $previousQty = (float) $material->quantity + $quantity;
            $newQty = (float) $material->quantity;
            MaterialQuantityAudit::create([
                'material_id' => $material->id,
                'material_name' => $material->material_name,
                'operation_type' => 'wastage',
                'quantity_change' => -$quantity,
                'previous_quantity' => $previousQty,
                'new_quantity' => $newQty,
                'remaining_quantity' => $newQty,
                'source' => 'production_wastage',
                'notes' => 'Wastage (' . $wastageTypes . ') for production ' . $production->batch_id,
                'user_id' => $user->id ?? null,
                'added_by' => $userName,
            ]);
            
            // Log history
            History::create([
                'operation' => 'add_wastage',
                'source' => 'production',
                'previous_data' => null,
                'new_data' => [
                    'production_id' => $production->id,
                    'batch_id' => $production->batch_id,
                    'material_id' => $material->id,
                    'material_name' => $material->material_name,
                    'quantity' => $quantity,
                    'wastage_type' => $wastageTypes,
                ],
                'added_by' => $userName,
                'user_id' => $user->id ?? null,
                'added_at' => now(),
            ]);
            
            // Production history
            ProductionHistory::create([
                'production_id' => $production->id,
                'batch_id' => $production->batch_id,
                'action' => 'wastage',
                'material_id' => $material->id,
                'material_name' => $material->material_name,
                'quantity' => $quantity,
                'unit' => $material->unit,
                'wastage_type' => $wastageTypes,
                'notes' => $request->notes,
                'added_by' => $userName,
                'user_id' => $user->id ?? null,
            ]);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.wastage_added_success', [], session('locale', 'en')),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /** Get wastage records for a production */
    public function getWastages($id)
    {
        $wastages = ProductionWastageMaterial::where('production_id', $id)
            ->orderBy('created_at', 'DESC')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'wastages' => $wastages,
        ]);
    }

    /** Get materials for a production (for select dropdown) */
    public function getProductionMaterials($id)
    {
        $production = Production::with('details')->findOrFail($id);
        $materials = $production->details->materials_json ?? [];
        
        return response()->json([
            'status' => 'success',
            'materials' => $materials,
        ]);
    }

    /** Get production history */
    public function getProductionHistory($id)
    {
        $history = ProductionHistory::where('production_id', $id)
            ->orderBy('created_at', 'DESC')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'history' => $history,
        ]);
    }

    /** Complete production and send to packaging phase */
    public function completeProduction(Request $request, $id)
    {
        $production = Production::with('stock')->findOrFail($id);
        
        // Check if already completed
        if ($production->status === 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => trans('messages.production_already_completed', [], session('locale', 'en')),
            ], 400);
        }

        $user = Auth::user();
        $userName = $user->user_name ?? $user->name ?? 'system';
        $actualOutput = (float) ($request->actual_output ?? $production->estimated_output ?? 0);

        DB::beginTransaction();
        try {
            // Update production
            $production->actual_output = $actualOutput;
            $production->status = 'completed';
            $production->completed_at = now();
            $production->save();
            
            // Add actual output to stock quantity
            $stock = $production->stock;
            if ($stock && $actualOutput > 0) {
                $previousQty = (float) $stock->quantity;
                $stock->quantity = $previousQty + $actualOutput;
                $stock->save();
                
                // Log history for stock update
                History::create([
                    'operation' => 'production_completed',
                    'source' => 'stock',
                    'previous_data' => [
                        'stock_id' => $stock->id,
                        'stock_name' => $stock->stock_name,
                        'quantity_before' => $previousQty,
                    ],
                    'new_data' => [
                        'production_id' => $production->id,
                        'batch_id' => $production->batch_id,
                        'actual_output' => $actualOutput,
                        'quantity_after' => $stock->quantity,
                    ],
                    'added_by' => $userName,
                    'user_id' => $user->id ?? null,
                    'added_at' => now(),
                ]);
            }
            
            // Production history - production completed
            ProductionHistory::create([
                'production_id' => $production->id,
                'batch_id' => $production->batch_id,
                'action' => 'production_completed',
                'material_id' => null,
                'material_name' => null,
                'quantity' => 0,
                'notes' => trans('messages.production_sent_to_packaging', [], session('locale', 'en')),
                'added_by' => $userName,
                'user_id' => $user->id ?? null,
            ]);
            
            // Log history for production completion
            History::create([
                'operation' => 'complete_production',
                'source' => 'production',
                'previous_data' => [
                    'status' => 'under_process',
                    'actual_output' => null,
                ],
                'new_data' => [
                    'production_id' => $production->id,
                    'batch_id' => $production->batch_id,
                    'estimated_output' => $production->estimated_output,
                    'actual_output' => $actualOutput,
                    'status' => 'completed',
                ],
                'added_by' => $userName,
                'user_id' => $user->id ?? null,
                'added_at' => now(),
            ]);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.production_completed_success', [], session('locale', 'en')),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
