<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Material;
use App\Models\History;
use App\Models\MaterialQuantityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class MaterialController extends Controller
{
     public function add_material_view()
     {
         return view('stock.add_material');
     }

   public function edit_material($id) {
    $material = Material::where('id', $id)->first();
    return view('stock.edit_material', compact('material'));
}

public function getmaterial()
{
    // Eager load relationships
    $material = Material::
                   orderBy('id', 'DESC')
                   ->paginate(10);

    return response()->json($material);
}


  public function add_material(Request $request)
{
    // Validate input (popup form: name, unit, purchase price, notes only)
    $request->validate([
        'material_name' => 'required|string|max:255',
        'material_type' => 'nullable|string|in:production,packaging',
        'material_unit' => 'required|string',
        'quantity' => 'nullable|numeric|min:0',
        'purchase_price' => 'nullable|numeric|min:0',
        'material_notes' => 'nullable|string',
  
    
    ]);

    $user = Auth::user();


    $material = new Material();
    $material->material_name   = $request->material_name;
    $material->material_type   = $request->material_type ?? 'production';
    $material->description     = $request->material_notes ?? null;
    $material->unit            = $request->material_unit;
    $material->unit_price      = $request->purchase_price ?? 0;
    $quantity = floatval($request->quantity ?? $request->meters_pieces ??   0);
    $material->added_by        = $user ? ($user->user_name ?? $user->name ?? 'system') : 'system';
    $material->user_id         = $user ? $user->id : 1;
    $material->save();

    // Calculate initial quantity based on unit
    $initialQuantity = 0;
    if ($material->unit === 'roll') {
        $initialQuantity = floatval($material->rolls_count ?? 0);
    } elseif ($material->unit === 'meter' || $material->unit === 'piece') {
        $initialQuantity = floatval($material->meters_per_roll ?? 0);
    } else {
        $initialQuantity = floatval($material->meters_per_roll ?? 0);
    }

    // Log audit entry for material addition
    try {
        MaterialQuantityAudit::create([
            'material_id' => $material->id,
            'material_name' => $material->material_name,
            'operation_type' => 'added',
            'previous_quantity' => 0,
            'new_quantity' => $initialQuantity,
            'quantity_change' => $initialQuantity,
            'remaining_quantity' => $initialQuantity,
            'user_id' => $user ? $user->id : null,
            'added_by' => $user ? ($user->user_name ?? $user->name ?? 'system') : 'system',
            'notes' => 'Material added to inventory',
        ]);
    } catch (\Exception $e) {
        \Log::error('Error creating material quantity audit: ' . $e->getMessage());
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Material added successfully!',
        'material_id' => $material->id,
        'redirect_url' => url('view_material'),
    ]);
}



     public function view_material()
    {
        return view('stock.view_material');
    }
 
 public function update_material(Request $request)
{
    // Validate input (same columns as add_material)
    $request->validate([
        'material_id' => 'required|exists:materials,id',
        'material_name' => 'required|string|max:255',
        'material_type' => 'nullable|string|in:production,packaging',
        'material_unit' => 'required|string',
        'quantity' => 'nullable|numeric|min:0',
        'purchase_price' => 'nullable|numeric|min:0',
        'material_notes' => 'nullable|string',
    ]);

    $material_id = $request->material_id;
    $material = Material::findOrFail($material_id);
    $user = Auth::user();
    $previousData = $material->toArray();

    $material->material_name   = $request->material_name;
    $material->material_type   = $request->material_type ?? $material->material_type ?? 'production';
    $material->description     = $request->material_notes ?? $material->description;
    $material->unit            = $request->material_unit;
    $material->unit_price      = $request->purchase_price ?? $material->unit_price ?? 0;
    $quantity = floatval($request->quantity ?? 0);
    $material->quantity        = $quantity;
    $material->updated_by      = $user ? ($user->user_name ?? $user->name ?? 'system') : 'system';
    $material->user_id         = $user ? $user->id : 1;

    $material->save();

    History::create([
        'operation' => 'update',
        'source' => 'material',
        'previous_data' => $previousData,
        'new_data' => $material->fresh()->toArray(),
        'added_by' => $user->user_name ?? $user->name ?? 'system',
        'user_id' => $user->id ?? null,
        'added_at' => now(),
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Material updated successfully!',
        'material_id' => $material->id
    ]);
}


public function delete_material($id)
{


    $material = Material::find($id);

    if (!$material) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Material not found'
        ], 404);
    }

    $user = Auth::user();
    $previousData = $material->toArray();

    History::create([
        'operation' => 'delete',
        'source' => 'material',
        'previous_data' => $previousData,
        'new_data' => null,
        'added_by' => $user->user_name ?? $user->name ?? 'system',
        'user_id' => $user->id ?? null,
        'added_at' => now(),
    ]);

    $material->delete();

    return response()->json([
        'status'  => 'success',
        'message' => 'Material deleted successfully!'
    ]);
}

public function getAllMaterials()
{
    $materials = Material::select('id', 'material_name', 'unit', 'category', 'rolls_count', 'meters_per_roll')
        ->orderBy('material_name', 'ASC')
        ->get()
        ->map(function($material) {
            // Calculate available quantity based on unit
            $availableQty = 0;
            if ($material->unit === 'roll') {
                $availableQty = floatval($material->rolls_count ?? 0);
            } elseif ($material->unit === 'meter') {
                // Available = rolls_count * meters_per_roll (but rolls_count is usually 1, so it's just meters_per_roll)
                $availableQty = floatval($material->rolls_count ?? 1) * floatval($material->meters_per_roll ?? 0);
            } elseif ($material->unit === 'piece') {
                // For pieces, available = meters_per_roll (which stores total pieces)
                $availableQty = floatval($material->meters_per_roll ?? 0);
            } else {
                // Default: use meters_per_roll as total quantity
                $availableQty = floatval($material->meters_per_roll ?? 0);
            }
            
            return [
                'id' => $material->id,
                'material_name' => $material->material_name,
                'unit' => $material->unit,
                'category' => $material->category,
                'rolls_count' => floatval($material->rolls_count ?? 0),
                'meters_per_roll' => floatval($material->meters_per_roll ?? 0),
                'available_quantity' => $availableQty
            ];
        });
    
    return response()->json($materials);
}

/** Materials for purchase page select (id, name, unit, buy_price) */
    public function getMaterialsForPurchase()
    {
        $materials = Material::orderBy('material_name', 'ASC')
            ->get(['id', 'material_name', 'unit', 'unit_price'])
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'material_name' => $m->material_name ?? '',
                    'unit' => $m->unit ?? '-',
                    'buy_price' => (float) ($m->unit_price ?? 0),
                ];
            });
        return response()->json($materials->values()->all());
    }

    public function getMaterial33($id)
{
    $material = Material::find($id);
    
    if (!$material) {
        return response()->json([
            'status' => 'error',
            'message' => 'Material not found'
        ], 404);
    }
    
    return response()->json([
        'status' => 'success',
        'material' => $material
    ]);
}

public function addQuantity(Request $request)
{
    try {
        $materialId = $request->input('material_id');
        $newMetersPieces = floatval($request->input('new_meters_pieces', 0));
        $newBuyPrice = $request->input('new_buy_price') ? floatval($request->input('new_buy_price')) : null;
        
        if (!$materialId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Material ID is required'
            ], 422);
        }
        
        if ($newMetersPieces <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'New meters/pieces must be greater than zero'
            ], 422);
        }
        
        $material = Material::findOrFail($materialId);
        $user = Auth::user();
        
        // Get current values
        $currentRollsCount = floatval($material->rolls_count ?? 1);
        $currentMetersPerRoll = floatval($material->meters_per_roll ?? 0);
        
        // Calculate current total meters: rolls_count × meters_per_roll (should be just meters_per_roll since rolls_count is 1)
        $currentTotalMeters = $currentRollsCount * $currentMetersPerRoll;
        
        // Store previous quantity for audit
        $previousQuantity = $currentTotalMeters;
        
        // Calculate final total meters: current + new
        $finalTotalMeters = $currentTotalMeters + $newMetersPieces;
        
        // Store total in meters_per_roll, keep rolls_count as 1
        $material->rolls_count = 1;
        $material->meters_per_roll = $finalTotalMeters;
        
        // Update buy price if provided, otherwise keep current
        if ($newBuyPrice !== null && $newBuyPrice > 0) {
            $material->buy_price = $newBuyPrice;
        }
        
        // Track user
        $material->updated_by = $user ? ($user->user_name ?? $user->name ?? 'system') : 'system';
        if ($user) {
            $material->user_id = $user->id;
        }
        
        $material->save();

        // Log audit entry for quantity addition
        try {
            MaterialQuantityAudit::create([
                'material_id' => $material->id,
                'material_name' => $material->material_name,
                'operation_type' => 'quantity_added',
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $finalTotalMeters,
                'quantity_change' => $newMetersPieces,
                'remaining_quantity' => $finalTotalMeters,
                'user_id' => $user ? $user->id : null,
                'added_by' => $user ? ($user->user_name ?? $user->name ?? 'system') : 'system',
                'notes' => 'Quantity added to material',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating material quantity audit: ' . $e->getMessage());
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Quantity added successfully. Total meters/pieces: ' . number_format($finalTotalMeters, 2),
            'material' => [
                'id' => $material->id,
                'rolls_count' => $material->rolls_count,
                'meters_per_roll' => $material->meters_per_roll,
                'buy_price' => $material->buy_price
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error adding quantity: ' . $e->getMessage()
        ], 500);
    }
}

public function materialQuantityAudit()
{
    if (!Auth::check()) {
        return redirect()->route('login_page')->with('error', 'Please login first');
    }

    $permissions = Auth::user()->permissions ?? [];

    if (!in_array(9, $permissions)) {
        return redirect()->route('login_page')->with('error', 'Permission denied');
    }

    return view('stock.material_quantity_audit');
}

public function getMaterialQuantityAuditData(Request $request)
{
    try {
        $search = $request->input('search', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $page = $request->input('page', 1);
        $perPage = 20;

        $query = MaterialQuantityAudit::with(['material', 'tailor', 'user'])
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC');

        // Search by material name or tailor name
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('material_name', 'like', '%' . $search . '%')
                  ->orWhere('tailor_name', 'like', '%' . $search . '%')
                  ->orWhereHas('material', function($mq) use ($search) {
                      $mq->where('material_name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('tailor', function($tq) use ($search) {
                      $tq->where('tailor_name', 'like', '%' . $search . '%');
                  });
            });
        }

        // Date filter
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $audits = $query->paginate($perPage, ['*'], 'page', $page);

        // Get remaining quantity for searched material if search is provided
        $remainingQuantity = null;
        if ($search) {
            $material = Material::where('material_name', 'like', '%' . $search . '%')->first();
            if ($material) {
                // Calculate remaining quantity based on unit
                if ($material->unit === 'roll') {
                    $remainingQuantity = floatval($material->rolls_count ?? 0);
                } elseif ($material->unit === 'meter' || $material->unit === 'piece') {
                    $remainingQuantity = floatval($material->meters_per_roll ?? 0);
                } else {
                    $remainingQuantity = floatval($material->meters_per_roll ?? 0);
                }
            }
        }

        $data = $audits->map(function($audit) {
            return [
                'id' => $audit->id,
                'date' => $audit->created_at->format('Y-m-d H:i:s'),
                'material_name' => $audit->material_name,
                'stock_code' => $audit->stock_code,
                'source' => $audit->source,
                'source_label' => $this->getSourceLabel($audit->source),
                'special_order_number' => $audit->special_order_number ?? null,
                'operation_type' => $audit->operation_type,
                'operation_type_label' => $this->getOperationTypeLabel($audit->operation_type),
                'previous_quantity' => number_format($audit->previous_quantity, 2),
                'new_quantity' => number_format($audit->new_quantity, 2),
                'quantity_change' => number_format($audit->quantity_change, 2),
                'remaining_quantity' => number_format($audit->remaining_quantity, 2),
                'previous_tailor_material_quantity' => number_format($audit->previous_tailor_material_quantity ?? 0, 2),
                'tailor_material_change' => number_format(($audit->new_tailor_material_quantity ?? 0) - ($audit->previous_tailor_material_quantity ?? 0), 2),
                'new_tailor_material_quantity' => number_format($audit->new_tailor_material_quantity ?? 0, 2),
                'tailor_name' => $audit->tailor_name,
                'added_by' => $audit->added_by,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'current_page' => $audits->currentPage(),
            'last_page' => $audits->lastPage(),
            'per_page' => $audits->perPage(),
            'total' => $audits->total(),
            'remaining_quantity' => $remainingQuantity !== null ? number_format($remainingQuantity, 2) : null,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching audit data: ' . $e->getMessage()
        ], 500);
    }
}

private function getOperationTypeLabel($operationType)
{
    $labels = [
        'added' => trans('messages.material_added', [], session('locale', 'en')) ?: 'Material Added',
        'quantity_added' => trans('messages.quantity_added', [], session('locale', 'en')) ?: 'Quantity Added',
        'sent_to_tailor' => trans('messages.sent_to_tailor', [], session('locale', 'en')) ?: 'Sent to Tailor',
        'material_deducted' => trans('messages.material_deducted', [], session('locale', 'en')) ?: 'Material Deducted',
    ];
    
    return $labels[$operationType] ?? $operationType;
}

private function getSourceLabel($source)
{
    if (!$source) {
        return '-';
    }
    
    $labels = [
        'stock' => trans('messages.stock', [], session('locale', 'en')) ?: 'Stock',
        'special_order' => trans('messages.special_order', [], session('locale', 'en')) ?: 'Special Order',
        'manage_quantity' => trans('messages.manage_quantity', [], session('locale', 'en')) ?: 'Manage Quantity',
    ];
    
    return $labels[$source] ?? $source;
}

private function getStatusLabel($status)
{
    if (!$status) {
        return '-';
    }
    
    $labels = [
        'success' => trans('messages.success', [], session('locale', 'en')) ?: 'Success',
        'insufficient' => trans('messages.insufficient', [], session('locale', 'en')) ?: 'Insufficient',
        'error' => trans('messages.error', [], session('locale', 'en')) ?: 'Error',
    ];
    
    return $labels[$status] ?? $status;
}
}
