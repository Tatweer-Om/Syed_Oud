<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\Packaging;
use App\Models\PackagingDetail;
use App\Models\PackagingHistory;
use App\Models\PackagingWastageMaterial;
use App\Models\Stock;
use App\Models\Material;
use App\Models\History;
use App\Models\MaterialQuantityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PackagingController extends Controller
{
    /** Packaging create page for a production */
    public function create($productionId)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $production = Production::with(['stock', 'stock.productionUnit'])->findOrFail($productionId);
        $existingPackaging = Packaging::where('production_id', $productionId)->first();
        if ($existingPackaging) {
            return redirect()->route('packaging.profile', $existingPackaging->id);
        }
        return view('stock.packaging', ['production' => $production]);
    }

    /** Store new packaging with materials */
    public function store(Request $request)
    {
        $request->validate([
            'production_id' => 'required|exists:productions,id',
            'packaging_date' => 'required|date',
            'production_output_taken' => 'nullable|numeric|min:0',
            'expected_packaging_units' => 'nullable|numeric|min:0',
            'materials' => 'required|array|min:1',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $production = Production::with('stock')->findOrFail($request->production_id);
        $actualOutput = (float) ($production->actual_output ?? 0);
        $productionOutputTaken = (float) ($request->production_output_taken ?? 0);
        if ($productionOutputTaken > $actualOutput) {
            return response()->json(['status' => 'error', 'message' => trans('messages.production_output_taken_exceeds_available', [], session('locale', 'en'))], 422);
        }
        if (Packaging::where('production_id', $production->id)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Packaging already exists for this production'], 400);
        }

        $user = Auth::user();
        $userName = $user->user_name ?? $user->name ?? 'system';

        DB::beginTransaction();
        try {
            $materials = $request->materials;
            $totalQty = 0;
            $totalAmount = 0;
            $materialsJson = [];

            foreach ($materials as $m) {
                $mat = Material::findOrFail($m['material_id']);
                $qty = (float) $m['quantity'];
                $unitPrice = (float) ($mat->unit_price ?? 0);
                $total = $unitPrice * $qty;
                $materialsJson[] = [
                    'material_id' => $mat->id,
                    'material_name' => $mat->material_name,
                    'unit' => $mat->unit,
                    'unit_price' => $unitPrice,
                    'quantity' => $qty,
                    'total' => $total,
                ];
                $totalQty += $qty;
                $totalAmount += $total;

                $mat->quantity = (float) $mat->quantity - $qty;
                $mat->save();

                MaterialQuantityAudit::create([
                    'material_id' => $mat->id,
                    'material_name' => $mat->material_name,
                    'operation_type' => 'packaging_deducted',
                    'quantity_change' => -$qty,
                    'previous_quantity' => (float) $mat->quantity + $qty,
                    'new_quantity' => (float) $mat->quantity,
                    'remaining_quantity' => (float) $mat->quantity,
                    'source' => 'packaging',
                    'notes' => 'Material added to packaging',
                    'user_id' => $user->id ?? null,
                    'added_by' => $userName,
                ]);
            }

            $estimatedOutput = (float) $production->estimated_output;
            $costPerUnit = $estimatedOutput > 0 ? ($totalAmount / $estimatedOutput) : 0;

            $packaging = Packaging::create([
                'production_id' => $production->id,
                'batch_id' => $production->batch_id,
                'stock_id' => $production->stock_id,
                'estimated_output' => $estimatedOutput,
                'total_quantity' => $totalQty,
                'total_items' => count($materialsJson),
                'total_amount' => $totalAmount,
                'cost_per_unit' => $costPerUnit,
                'status' => 'under_process',
                'user_id' => $user->id ?? null,
                'added_by' => $userName,
            ]);

            $packaging->packaging_id = Packaging::generatePackagingId($packaging->id);
            $packaging->filling_id = Packaging::generateFillingId($packaging->id);
            $packaging->save();

            PackagingDetail::create([
                'packaging_id' => $packaging->id,
                'stock_id' => $production->stock_id,
                'materials_json' => $materialsJson,
                'user_id' => $user->id ?? null,
                'added_by' => $userName,
            ]);

            PackagingHistory::create([
                'packaging_id' => $packaging->id,
                'phase' => 1,
                'production_id' => $production->id,
                'batch_id' => $packaging->batch_id,
                'filling_id' => $packaging->filling_id,
                'packaging_date' => $request->packaging_date,
                'materials_json' => $materialsJson,
                'production_output_taken' => $productionOutputTaken,
                'expected_packaging_units' => (float) ($request->expected_packaging_units ?? 0),
                'action' => 'packaging_entry',
                'added_by' => $userName,
                'user_id' => $user->id ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.packaging_created_success', [], session('locale', 'en')),
                'packaging_id' => $packaging->id,
                'redirect' => route('packaging.profile', $packaging->id),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /** Packaging profile page */
    public function profile($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $packaging = Packaging::with(['production.stock.productionUnit', 'stock', 'details'])->findOrFail($id);
        $actualOutput = (float) ($packaging->production->actual_output ?? 0);
        $takenSoFar = PackagingHistory::where('packaging_id', $id)
            ->where('action', 'packaging_entry')
            ->sum('production_output_taken');
        $remainingActualOutput = max(0, $actualOutput - (float) $takenSoFar);
        $phaseEntries = PackagingHistory::where('packaging_id', $id)
            ->where('action', 'packaging_entry')
            ->orderBy('phase', 'DESC')
            ->get();
        $latestPhase = $phaseEntries->first();
        $latestPhaseNumber = $latestPhase ? (int) $latestPhase->phase : 0;
        $isLatestPhaseCompleted = $latestPhase && $latestPhase->phase_completed_at !== null;
        $latestPhaseOutputTaken = $latestPhase ? (float) ($latestPhase->production_output_taken ?? 0) : 0;
        $latestPhaseExpectedPackaging = $latestPhase ? (float) ($latestPhase->expected_packaging_units ?? 0) : 0;
        $nextPhase = $latestPhaseNumber + 1;
        return view('stock.packaging_profile', [
            'packaging' => $packaging,
            'remainingActualOutput' => $remainingActualOutput,
            'nextPhase' => $nextPhase,
            'latestPhaseNumber' => $latestPhaseNumber,
            'isLatestPhaseCompleted' => $isLatestPhaseCompleted,
            'latestPhaseOutputTaken' => $latestPhaseOutputTaken,
            'latestPhaseExpectedPackaging' => $latestPhaseExpectedPackaging,
        ]);
    }

    /** Complete phase - mark phase as completed and save actual pieces packed. If remaining=0, also complete packaging. */
    public function completePhase(Request $request, $id)
    {
        $request->validate([
            'phase' => 'required|integer|min:1',
            'actual_pieces_packed' => 'required|numeric|min:0',
        ]);
        $packaging = Packaging::with('stock')->findOrFail($id);
        if ($packaging->status === 'completed') {
            return response()->json(['status' => 'error', 'message' => trans('messages.packaging_already_completed', [], session('locale', 'en'))], 400);
        }
        $history = PackagingHistory::where('packaging_id', $id)
            ->where('phase', $request->phase)
            ->where('action', 'packaging_entry')
            ->first();
        if (!$history) {
            return response()->json(['status' => 'error', 'message' => trans('messages.phase_not_found', [], session('locale', 'en'))], 404);
        }
        if ($history->phase_completed_at) {
            return response()->json(['status' => 'error', 'message' => trans('messages.phase_already_completed', [], session('locale', 'en'))], 400);
        }
        $history->phase_completed_at = now();
        $actualPiecesPacked = (float) $request->actual_pieces_packed;
        $history->actual_pieces_packed = $actualPiecesPacked;
        $history->save();

        // Add actual pieces packed to stock quantity
        $user = Auth::user();
        $userName = $user->user_name ?? $user->name ?? 'system';
        $stock = $packaging->stock;
        if ($stock && $actualPiecesPacked > 0) {
            $previousQty = (float) $stock->quantity;
            $stock->quantity = $previousQty + $actualPiecesPacked;
            $stock->save();
            History::create([
                'operation' => 'phase_completed_stock_add',
                'source' => 'stock',
                'previous_data' => ['stock_id' => $stock->id, 'quantity_before' => $previousQty, 'phase' => $request->phase],
                'new_data' => ['packaging_id' => $packaging->id, 'actual_pieces_packed' => $actualPiecesPacked, 'quantity_after' => $stock->quantity],
                'added_by' => $userName,
                'user_id' => $user->id ?? null,
                'added_at' => now(),
            ]);
        }

        // Update actual_packaging_output: sum of actual_pieces_packed from all completed phases
        $packagingActualPackagingOutput = PackagingHistory::where('packaging_id', $id)
            ->where('action', 'packaging_entry')
            ->whereNotNull('actual_pieces_packed')
            ->sum('actual_pieces_packed');
        $packaging->actual_packaging_output = (float) $packagingActualPackagingOutput;
        $packaging->save();

        // Update production's actual_packaging_output: sum from all its packagings
        $production = $packaging->production;
        $productionActualPackagingOutput = Packaging::where('production_id', $production->id)->sum('actual_packaging_output');
        $production->actual_packaging_output = (float) $productionActualPackagingOutput;
        $production->save();

        $actualOutput = (float) ($packaging->production->actual_output ?? 0);
        $takenSoFar = PackagingHistory::where('packaging_id', $id)->where('action', 'packaging_entry')->sum('production_output_taken');
        $remaining = max(0, $actualOutput - (float) $takenSoFar);

        if ($remaining <= 0) {
            $user = Auth::user();
            $userName = $user->user_name ?? $user->name ?? 'system';
            $outputToAdd = (float) $actualOutput;

            DB::beginTransaction();
            try {
                $packaging->actual_output = $outputToAdd;
                $packaging->status = 'completed';
                $packaging->completed_at = now();
                $packaging->save();

                // Stock already updated per phase with actual_pieces_packed; no additional add here

                PackagingHistory::create([
                    'packaging_id' => $packaging->id,
                    'batch_id' => $packaging->batch_id,
                    'action' => 'packaging_completed',
                    'notes' => trans('messages.packaging_completed', [], session('locale', 'en')),
                    'added_by' => $userName,
                    'user_id' => $user->id ?? null,
                ]);

                DB::commit();
                $stock = $packaging->stock;
                $stockName = $stock ? ($stock->stock_name ?? '') : '';
                return response()->json([
                    'status' => 'ok',
                    'message' => trans('messages.phase_completed', [], session('locale', 'en')),
                    'packaging_completed' => true,
                    'packaging_completed_message' => trans('messages.packaging_completed_success', [], session('locale', 'en')),
                    'stock_name' => $stockName,
                    'actual_pieces_packed' => (float) $request->actual_pieces_packed,
                    'actual_packaging_output' => (float) $packaging->actual_packaging_output,
                    'output_added_to_stock' => (float) $packaging->actual_packaging_output,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
        }

        $stockName = $packaging->stock ? ($packaging->stock->stock_name ?? '') : '';
        return response()->json([
            'status' => 'ok',
            'message' => trans('messages.phase_completed', [], session('locale', 'en')),
            'stock_name' => $stockName,
            'actual_pieces_packed' => (float) $request->actual_pieces_packed,
            'actual_packaging_output' => (float) $packaging->actual_packaging_output,
            'output_added_to_stock' => (float) $request->actual_pieces_packed,
        ]);
    }

    /** Add phase page */
    public function addPhasePage($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $packaging = Packaging::with(['production.stock.productionUnit', 'stock', 'details'])->findOrFail($id);
        if ($packaging->status === 'completed') {
            return redirect()->route('packaging.profile', $id)->with('error', trans('messages.packaging_already_completed', [], session('locale', 'en')));
        }
        $latestPhase = PackagingHistory::where('packaging_id', $id)
            ->where('action', 'packaging_entry')
            ->orderBy('phase', 'DESC')
            ->first();
        $isLatestPhaseCompleted = $latestPhase && $latestPhase->phase_completed_at !== null;
        if (!$isLatestPhaseCompleted && $latestPhase) {
            return redirect()->route('packaging.profile', $id)->with('error', trans('messages.complete_current_phase_first', [], session('locale', 'en')));
        }
        $actualOutput = (float) ($packaging->production->actual_output ?? 0);
        $takenSoFar = PackagingHistory::where('packaging_id', $id)
            ->where('action', 'packaging_entry')
            ->sum('production_output_taken');
        $remainingActualOutput = max(0, $actualOutput - (float) $takenSoFar);
        if ($remainingActualOutput <= 0) {
            return redirect()->route('packaging.profile', $id)->with('info', trans('messages.all_actual_output_packaged', [], session('locale', 'en')));
        }
        $nextPhase = $latestPhase ? ((int) $latestPhase->phase) + 1 : 1;
        return view('stock.packaging_add_phase', [
            'packaging' => $packaging,
            'remainingActualOutput' => $remainingActualOutput,
            'nextPhase' => $nextPhase,
        ]);
    }

    /** Add phase - merge materials, create history */
    public function addPhase(Request $request, $id)
    {
        $request->validate([
            'packaging_date' => 'required|date',
            'production_output_taken' => 'nullable|numeric|min:0',
            'expected_packaging_units' => 'nullable|numeric|min:0',
            'materials' => 'required|array|min:1',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $packaging = Packaging::with(['production', 'details'])->findOrFail($id);
        if ($packaging->status === 'completed') {
            return response()->json(['status' => 'error', 'message' => trans('messages.packaging_already_completed', [], session('locale', 'en'))], 400);
        }

        $actualOutput = (float) ($packaging->production->actual_output ?? 0);
        $takenSoFar = PackagingHistory::where('packaging_id', $id)->where('action', 'packaging_entry')->sum('production_output_taken');
        $remainingActualOutput = max(0, $actualOutput - (float) $takenSoFar);
        $productionOutputTaken = (float) ($request->production_output_taken ?? 0);

        if ($productionOutputTaken > $remainingActualOutput) {
            return response()->json(['status' => 'error', 'message' => trans('messages.production_output_taken_exceeds_available', [], session('locale', 'en'))], 422);
        }

        $user = Auth::user();
        $userName = $user->user_name ?? $user->name ?? 'system';
        $nextPhase = PackagingHistory::where('packaging_id', $id)->where('action', 'packaging_entry')->count() + 1;

        DB::beginTransaction();
        try {
            $phaseMaterials = [];
            $existingMaterials = $packaging->details->materials_json ?? [];

            foreach ($request->materials as $m) {
                $mat = Material::findOrFail($m['material_id']);
                $qty = (float) $m['quantity'];
                $unitPrice = (float) ($mat->unit_price ?? 0);
                $total = $unitPrice * $qty;
                $phaseMaterials[] = [
                    'material_id' => $mat->id,
                    'material_name' => $mat->material_name,
                    'unit' => $mat->unit,
                    'unit_price' => $unitPrice,
                    'quantity' => $qty,
                    'total' => $total,
                ];

                $found = false;
                foreach ($existingMaterials as &$em) {
                    if (($em['material_id'] ?? null) == $mat->id) {
                        $em['quantity'] = (float) ($em['quantity'] ?? 0) + $qty;
                        $em['total'] = (float) ($em['unit_price'] ?? 0) * $em['quantity'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $existingMaterials[] = [
                        'material_id' => $mat->id,
                        'material_name' => $mat->material_name,
                        'unit' => $mat->unit,
                        'unit_price' => $unitPrice,
                        'quantity' => $qty,
                        'total' => $total,
                    ];
                }

                $mat->quantity = (float) $mat->quantity - $qty;
                $mat->save();

                MaterialQuantityAudit::create([
                    'material_id' => $mat->id,
                    'material_name' => $mat->material_name,
                    'operation_type' => 'packaging_deducted',
                    'quantity_change' => -$qty,
                    'previous_quantity' => (float) $mat->quantity + $qty,
                    'new_quantity' => (float) $mat->quantity,
                    'remaining_quantity' => (float) $mat->quantity,
                    'source' => 'packaging',
                    'source_id' => $packaging->id,
                    'notes' => 'Phase ' . $nextPhase . ' - Material added to packaging',
                    'user_id' => $user->id ?? null,
                    'added_by' => $userName,
                ]);
            }

            $packaging->details->materials_json = $existingMaterials;
            $packaging->details->save();

            $totalQty = 0;
            $totalAmount = 0;
            foreach ($existingMaterials as $m) {
                $totalQty += (float) ($m['quantity'] ?? 0);
                $totalAmount += (float) ($m['total'] ?? 0);
            }
            $packaging->total_quantity = $totalQty;
            $packaging->total_items = count($existingMaterials);
            $packaging->total_amount = $totalAmount;
            $packaging->cost_per_unit = $packaging->estimated_output > 0 ? ($totalAmount / $packaging->estimated_output) : 0;
            $packaging->save();

            PackagingHistory::create([
                'packaging_id' => $packaging->id,
                'phase' => $nextPhase,
                'production_id' => $packaging->production_id,
                'batch_id' => $packaging->batch_id,
                'filling_id' => $packaging->filling_id,
                'packaging_date' => $request->packaging_date,
                'materials_json' => $phaseMaterials,
                'production_output_taken' => $productionOutputTaken,
                'expected_packaging_units' => (float) ($request->expected_packaging_units ?? 0),
                'action' => 'packaging_entry',
                'added_by' => $userName,
                'user_id' => $user->id ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.phase_added_success', [], session('locale', 'en')),
                'redirect' => route('packaging.profile', $packaging->id),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /** Add material to packaging */
    public function addMaterial(Request $request, $id)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $packaging = Packaging::with('details')->findOrFail($id);
        if ($packaging->status === 'completed') {
            return response()->json(['status' => 'error', 'message' => trans('messages.packaging_already_completed', [], session('locale', 'en'))], 400);
        }

        $user = Auth::user();
        $userName = $user->user_name ?? $user->name ?? 'system';
        $material = Material::findOrFail($request->material_id);
        $quantity = (float) $request->quantity;

        DB::beginTransaction();
        try {
            $materials = $packaging->details->materials_json ?? [];
            $found = false;
            foreach ($materials as &$m) {
                if ($m['material_id'] == $request->material_id) {
                    $m['quantity'] = (float) $m['quantity'] + $quantity;
                    $m['total'] = (float) ($m['unit_price'] ?? 0) * $m['quantity'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $materials[] = [
                    'material_id' => $material->id,
                    'material_name' => $material->material_name,
                    'unit' => $material->unit,
                    'unit_price' => (float) $material->unit_price,
                    'quantity' => $quantity,
                    'total' => (float) $material->unit_price * $quantity,
                ];
            }

            $packaging->details->materials_json = $materials;
            $packaging->details->save();

            $totalQty = 0;
            $totalAmount = 0;
            foreach ($materials as $m) {
                $totalQty += (float) ($m['quantity'] ?? 0);
                $totalAmount += (float) ($m['total'] ?? 0);
            }
            $packaging->total_quantity = $totalQty;
            $packaging->total_items = count($materials);
            $packaging->total_amount = $totalAmount;
            $packaging->cost_per_unit = $packaging->estimated_output > 0 ? ($totalAmount / $packaging->estimated_output) : 0;
            $packaging->save();

            $material->quantity = (float) $material->quantity - $quantity;
            $material->save();

            MaterialQuantityAudit::create([
                'material_id' => $material->id,
                'material_name' => $material->material_name,
                'operation_type' => 'packaging_deducted',
                'quantity_change' => -$quantity,
                'previous_quantity' => (float) $material->quantity + $quantity,
                'new_quantity' => (float) $material->quantity,
                'remaining_quantity' => (float) $material->quantity,
                'source' => 'packaging',
                'source_id' => $packaging->id,
                'notes' => 'Material added to packaging ' . $packaging->batch_id,
                'user_id' => $user->id ?? null,
                'added_by' => $userName,
            ]);

            PackagingHistory::create([
                'packaging_id' => $packaging->id,
                'batch_id' => $packaging->batch_id,
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
            return response()->json(['status' => 'success', 'message' => trans('messages.material_added_success', [], session('locale', 'en'))]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /** Remove material from packaging */
    public function removeMaterial(Request $request, $id)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $packaging = Packaging::with('details')->findOrFail($id);
        $user = Auth::user();
        $userName = $user->user_name ?? $user->name ?? 'system';
        $material = Material::findOrFail($request->material_id);
        $quantity = (float) $request->quantity;

        DB::beginTransaction();
        try {
            $materials = $packaging->details->materials_json ?? [];
            $found = false;
            $newMaterials = [];
            foreach ($materials as $m) {
                if ($m['material_id'] == $request->material_id) {
                    $found = true;
                    $currentQty = (float) $m['quantity'];
                    if ($quantity >= $currentQty) {
                        $quantity = $currentQty;
                    } else {
                        $m['quantity'] = $currentQty - $quantity;
                        $m['total'] = (float) ($m['unit_price'] ?? 0) * $m['quantity'];
                        $newMaterials[] = $m;
                    }
                } else {
                    $newMaterials[] = $m;
                }
            }
            if (!$found) {
                return response()->json(['status' => 'error', 'message' => trans('messages.material_not_found_in_production', [], session('locale', 'en'))], 400);
            }

            $packaging->details->materials_json = $newMaterials;
            $packaging->details->save();

            $totalQty = 0;
            $totalAmount = 0;
            foreach ($newMaterials as $m) {
                $totalQty += (float) ($m['quantity'] ?? 0);
                $totalAmount += (float) ($m['total'] ?? 0);
            }
            $packaging->total_quantity = $totalQty;
            $packaging->total_items = count($newMaterials);
            $packaging->total_amount = $totalAmount;
            $packaging->cost_per_unit = $packaging->estimated_output > 0 ? ($totalAmount / $packaging->estimated_output) : 0;
            $packaging->save();

            $material->quantity = (float) $material->quantity + $quantity;
            $material->save();

            MaterialQuantityAudit::create([
                'material_id' => $material->id,
                'material_name' => $material->material_name,
                'operation_type' => 'packaging_returned',
                'quantity_change' => $quantity,
                'previous_quantity' => (float) $material->quantity - $quantity,
                'new_quantity' => (float) $material->quantity,
                'remaining_quantity' => (float) $material->quantity,
                'source' => 'packaging',
                'source_id' => $packaging->id,
                'notes' => 'Material removed from packaging ' . $packaging->batch_id,
                'user_id' => $user->id ?? null,
                'added_by' => $userName,
            ]);

            PackagingHistory::create([
                'packaging_id' => $packaging->id,
                'batch_id' => $packaging->batch_id,
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
            return response()->json(['status' => 'success', 'message' => trans('messages.material_removed_success', [], session('locale', 'en'))]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /** Add wastage to packaging */
    public function addWastage(Request $request, $id)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $packaging = Packaging::findOrFail($id);
        $user = Auth::user();
        $userName = $user->user_name ?? $user->name ?? 'system';
        $material = Material::findOrFail($request->material_id);
        $quantity = (float) $request->quantity;
        $wastageTypes = (!empty($request->wastage_types) && is_array($request->wastage_types)) ? implode(',', $request->wastage_types) : null;

        DB::beginTransaction();
        try {
            PackagingWastageMaterial::create([
                'packaging_id' => $packaging->id,
                'batch_id' => $packaging->batch_id,
                'material_id' => $material->id,
                'material_name' => $material->material_name,
                'quantity' => $quantity,
                'unit' => $material->unit,
                'wastage_type' => $wastageTypes,
                'notes' => $request->notes,
                'user_id' => $user->id ?? null,
                'added_by' => $userName,
            ]);

            $material->quantity = (float) $material->quantity - $quantity;
            $material->save();

            MaterialQuantityAudit::create([
                'material_id' => $material->id,
                'material_name' => $material->material_name,
                'operation_type' => 'wastage',
                'quantity_change' => -$quantity,
                'previous_quantity' => (float) $material->quantity + $quantity,
                'new_quantity' => (float) $material->quantity,
                'remaining_quantity' => (float) $material->quantity,
                'source' => 'packaging_wastage',
                'source_id' => $packaging->id,
                'notes' => ($wastageTypes ? 'Wastage (' . $wastageTypes . ') for ' : 'Wastage for ') . 'packaging ' . $packaging->batch_id,
                'user_id' => $user->id ?? null,
                'added_by' => $userName,
            ]);

            PackagingHistory::create([
                'packaging_id' => $packaging->id,
                'batch_id' => $packaging->batch_id,
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
            return response()->json(['status' => 'success', 'message' => trans('messages.wastage_added_success', [], session('locale', 'en'))]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /** Complete packaging - add actual output to stock */
    public function complete(Request $request, $id)
    {
        $request->validate([
            'actual_output' => 'required|numeric|min:0.01',
        ]);

        $packaging = Packaging::with('stock')->findOrFail($id);
        if ($packaging->status === 'completed') {
            return response()->json(['status' => 'error', 'message' => trans('messages.packaging_already_completed', [], session('locale', 'en'))], 400);
        }

        $user = Auth::user();
        $userName = $user->user_name ?? $user->name ?? 'system';
        $actualOutput = (float) $request->actual_output;

        DB::beginTransaction();
        try {
            $packaging->actual_output = $actualOutput;
            $packaging->status = 'completed';
            $packaging->completed_at = now();
            $packaging->save();

            $stock = $packaging->stock;
            if ($stock) {
                $previousQty = (float) $stock->quantity;
                $stock->quantity = $previousQty + $actualOutput;
                $stock->save();

                History::create([
                    'operation' => 'packaging_completed',
                    'source' => 'stock',
                    'previous_data' => ['stock_id' => $stock->id, 'quantity_before' => $previousQty],
                    'new_data' => ['packaging_id' => $packaging->id, 'actual_output' => $actualOutput, 'quantity_after' => $stock->quantity],
                    'added_by' => $userName,
                    'user_id' => $user->id ?? null,
                    'added_at' => now(),
                ]);
            }

            PackagingHistory::create([
                'packaging_id' => $packaging->id,
                'batch_id' => $packaging->batch_id,
                'action' => 'packaging_completed',
                'notes' => trans('messages.packaging_completed', [], session('locale', 'en')),
                'added_by' => $userName,
                'user_id' => $user->id ?? null,
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => trans('messages.packaging_completed_success', [], session('locale', 'en'))]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getMaterials($id)
    {
        $packaging = Packaging::with('details')->findOrFail($id);
        return response()->json(['status' => 'success', 'materials' => $packaging->details->materials_json ?? []]);
    }

    public function getHistory($id)
    {
        $history = PackagingHistory::where('packaging_id', $id)->orderBy('created_at', 'DESC')->get();
        return response()->json(['status' => 'success', 'history' => $history]);
    }
}
