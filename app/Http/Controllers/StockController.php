<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Category;
use App\Models\StockHistory;
use App\Models\StockAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StockController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(9, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }


        $categories = Category::all();

        return view('stock.add_stock', compact('categories'));
    }

public function edit_stock($id)
{
    $categories = Category::all();
    $stock = Stock::with('category')->findOrFail($id);
    $returnPage = (int) request()->get('page', 1);
    if ($returnPage < 1) {
        $returnPage = 1;
    }

    return view('stock.edit_stock', compact('categories', 'id', 'stock', 'returnPage'));
}




     public function view_stock()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(9, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        return view('stock.view_stock');
    }

public function getstock()
{
    $stocks = Stock::with('category')
        ->orderBy('id', 'DESC')
        ->paginate(10);

    return response()->json($stocks);
}





public function add_stock(Request $request)
{
    $stock = new Stock();
    $stock->stock_name        = $request->stock_name;
    $stock->category_id       = $request->category_id;
    $stock->barcode           = $request->barcode;
    $stock->stock_notes       = $request->stock_notes;

    if ($request->hasFile('image')) {
        $folderPath = public_path('images/stock_images');
        if (!File::isDirectory($folderPath)) {
            File::makeDirectory($folderPath, 0777, true, true);
        }
        $image = $request->file('image');
        $imageName = time() . '_' . Str::random(6) . '.' . $image->getClientOriginalExtension();
        $image->move($folderPath, $imageName);
        $stock->image = 'images/stock_images/' . $imageName;
    }

    $stock->cost_price        = $request->cost_price;
    $stock->sales_price       = $request->sales_price;
    $stock->discount          = $request->discount;
    $stock->tax               = $request->tax;
    $stock->quantity          = $request->quantity ?? 0;
    $stock->notification_limit = $request->notification_limit;
    $stock->added_by          = Auth::user()?->user_name ?? null;
    $stock->updated_by        = Auth::user()?->user_name ?? null;
    $stock->user_id           = Auth::id() ? (string) Auth::id() : null;
    $stock->save();

    return response()->json([
        'status'  => 'success',
        'message' => trans('messages.stock_added_successfully', [], session('locale')) ?: 'Stock added successfully!',
        'redirect_url' => url('view_stock'),
    ]);
}


public function update_stock(Request $request)
{
    $stock = Stock::findOrFail($request->stock_id);
    $oldData = $stock->toArray();

    $stock->stock_name        = $request->stock_name;
    $stock->category_id       = $request->category_id;
    $stock->barcode           = $request->barcode;
    $stock->stock_notes       = $request->stock_notes;

    if ($request->hasFile('image')) {
        if ($stock->image && File::exists(public_path($stock->image))) {
            File::delete(public_path($stock->image));
        }
        $folderPath = public_path('images/stock_images');
        if (!File::isDirectory($folderPath)) {
            File::makeDirectory($folderPath, 0777, true, true);
        }
        $image = $request->file('image');
        $imageName = time() . '_' . Str::random(6) . '.' . $image->getClientOriginalExtension();
        $image->move($folderPath, $imageName);
        $stock->image = 'images/stock_images/' . $imageName;
    }

    $stock->cost_price        = $request->cost_price;
    $stock->sales_price       = $request->sales_price;
    $stock->discount          = $request->discount;
    $stock->tax               = $request->tax;
    $stock->quantity          = $request->quantity ?? 0;
    $stock->notification_limit = $request->notification_limit;
    $stock->updated_by        = Auth::user()?->user_name ?? null;
    $stock->user_id           = Auth::id() ? (string) Auth::id() : null;
    $stock->save();

    StockAuditLog::create([
        'stock_id' => $stock->id,
        'stock_code' => $stock->barcode,
        'barcode' => $stock->barcode,
        'design_name' => $stock->stock_name,
        'operation_type' => 'updated',
        'previous_quantity' => (int) ($oldData['quantity'] ?? 0),
        'new_quantity' => (int) ($stock->quantity ?? 0),
        'quantity_change' => (int) ($stock->quantity ?? 0) - (int) ($oldData['quantity'] ?? 0),
        'user_id' => Auth::id(),
        'added_by' => Auth::user()?->user_name ?? null,
        'notes' => 'Stock details updated',
    ]);

    $returnPage = (int) $request->get('return_page', 1);
    $redirectUrl = url('view_stock');
    if ($returnPage > 1) {
        $redirectUrl .= '?page=' . $returnPage;
    }

    return response()->json([
        'status'  => 'success',
        'message' => trans('messages.stock_updated_successfully', [], session('locale')) ?: 'Stock updated successfully!',
        'redirect_url' => $redirectUrl,
    ]);
}


public function delete_stock($id)
{
    $stock = Stock::find($id);

    if (!$stock) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Stock not found'
        ], 404);
    }

    StockAuditLog::create([
        'stock_id' => $stock->id,
        'stock_code' => $stock->barcode,
        'barcode' => $stock->barcode,
        'design_name' => $stock->stock_name,
        'operation_type' => 'updated',
        'previous_quantity' => (int) ($stock->quantity ?? 0),
        'new_quantity' => 0,
        'quantity_change' => -(int) ($stock->quantity ?? 0),
        'user_id' => Auth::id(),
        'added_by' => Auth::user()?->user_name ?? null,
        'notes' => 'Stock deleted',
    ]);

    $stock->delete();

    return response()->json([
        'status'  => 'success',
        'message' => trans('messages.stock_deleted_successfully', [], session('locale')) ?: 'Stock deleted successfully!'
    ]);
}

/**
 * Simplified stock detail for view modal (no colors/sizes)
 */
public function get_simple_stock_detail(Request $request)
{
    $stock = Stock::with('category')->findOrFail($request->id);
    $imageUrl = $stock->image ? asset($stock->image) : null;
    return response()->json([
        'stock_id' => $stock->id,
        'stock_name' => $stock->stock_name ?? '-',
        'category_name' => $stock->category?->category_name ?? '-',
        'barcode' => $stock->barcode ?? '-',
        'stock_notes' => $stock->stock_notes ?? '-',
        'image' => $imageUrl,
        'cost_price' => $stock->cost_price ?? 0,
        'sales_price' => $stock->sales_price ?? 0,
        'discount' => $stock->discount ?? 0,
        'tax' => $stock->tax ?? 0,
        'quantity' => $stock->quantity ?? 0,
        'notification_limit' => $stock->notification_limit ?? null,
    ]);
}

/**
 * Push (add) quantity to stock
 */
public function stock_push_quantity(Request $request)
{
    $stock = Stock::findOrFail($request->stock_id);
    $qty = (float) ($request->quantity ?? 0);
    if ($qty <= 0) {
        return response()->json(['status' => 'error', 'message' => 'Quantity must be greater than 0'], 422);
    }
    $current = (float) ($stock->quantity ?? 0);
    $newQty = $current + $qty;
    $stock->quantity = (string) $newQty;
    $stock->updated_by = Auth::user()?->user_name ?? null;
    $stock->save();

    $userName = Auth::user()?->user_name ?? null;
    $userId = Auth::id() ? (string) Auth::id() : null;

    StockHistory::create([
        'stock_id' => $stock->id,
        'old_qty' => (int) $current,
        'changed_qty' => (int) $qty,
        'new_qty' => (int) $newQty,
        'action_type' => 1,
        'user_id' => $userId,
        'added_by' => $userName,
    ]);

    StockAuditLog::create([
        'stock_id' => $stock->id,
        'stock_code' => $stock->barcode,
        'barcode' => $stock->barcode,
        'design_name' => $stock->stock_name,
        'operation_type' => 'added',
        'previous_quantity' => (int) $current,
        'new_quantity' => (int) $newQty,
        'quantity_change' => (int) $qty,
        'user_id' => Auth::id(),
        'added_by' => $userName,
        'notes' => 'Quantity added (push)',
    ]);

    return response()->json([
        'status' => 'success',
        'message' => trans('messages.quantity_added_successfully', [], session('locale')) ?: 'Quantity added successfully!',
        'new_quantity' => (float) $stock->quantity,
    ]);
}

/**
 * Pull (subtract) quantity from stock - reason required
 */
public function stock_pull_quantity(Request $request)
{
    $stock = Stock::findOrFail($request->stock_id);
    $qty = (float) ($request->quantity ?? 0);
    $reason = trim($request->pull_reason ?? '');
    if (empty($reason)) {
        return response()->json(['status' => 'error', 'message' => trans('messages.please_enter_pull_notes', [], session('locale')) ?: 'Pull reason is required'], 422);
    }
    if ($qty <= 0) {
        return response()->json(['status' => 'error', 'message' => 'Quantity must be greater than 0'], 422);
    }
    $available = (float) ($stock->quantity ?? 0);
    if ($qty > $available) {
        return response()->json([
            'status' => 'error',
            'message' => (trans('messages.pull_quantity_exceeds_available', [], session('locale')) ?: 'Pull quantity cannot exceed available quantity') . '. ' . (trans('messages.available', [], session('locale')) ?: 'Available') . ': ' . $available
        ], 422);
    }
    $newQty = $available - $qty;
    $stock->quantity = (string) $newQty;
    $stock->updated_by = Auth::user()?->user_name ?? null;
    $stock->save();

    $userName = Auth::user()?->user_name ?? null;
    $userId = Auth::id() ? (string) Auth::id() : null;

    StockHistory::create([
        'stock_id' => $stock->id,
        'old_qty' => (int) $available,
        'changed_qty' => (int) $qty,
        'new_qty' => (int) $newQty,
        'action_type' => 2,
        'pull_notes' => $reason,
        'user_id' => $userId,
        'added_by' => $userName,
    ]);

    StockAuditLog::create([
        'stock_id' => $stock->id,
        'stock_code' => $stock->barcode,
        'barcode' => $stock->barcode,
        'design_name' => $stock->stock_name,
        'operation_type' => 'updated',
        'previous_quantity' => (int) $available,
        'new_quantity' => (int) $newQty,
        'quantity_change' => -(int) $qty,
        'user_id' => Auth::id(),
        'added_by' => $userName,
        'notes' => 'Quantity pulled. Reason: ' . $reason,
    ]);

    return response()->json([
        'status' => 'success',
        'message' => trans('messages.quantity_pulled_successfully', [], session('locale')) ?: 'Quantity pulled successfully!',
        'new_quantity' => (float) $stock->quantity,
    ]);
}

public function stock_detail(Request $request)
{
    $stock = Stock::with(['colors.color', 'sizes.size', 'images',  'colorSizes.size', 'colorSizes.color'])
                  ->findOrFail($request->id);

    $stock_sizes = $stock->sizes;
        $stock_colors = $stock->colors;

$stock_sizescolor = $stock->colorSizes; // Assuming it has color_id, size_id, qty

$htmlSizeColor = '';

foreach ($stock_sizescolor as $index => $item) {
    // Get size name based on session locale
$size_name = session('locale') === 'ar' 
             ? ($item->size?->size_name_ar ?? '-') 
             : ($item->size?->size_name_en ?? '-');

$color_name = session('locale') === 'ar' 
             ? ($item->color?->color_name_ar ?? '-') 
             : ($item->color?->color_name_en ?? '-');

$color_code = $item->color?->color_code ?? '#000'; // fallback to black if null
$qty = $item->qty ?? 0;

    $htmlSizeColor .= '<div class="flex justify-between items-center border rounded-lg p-3 bg-gray-50 text-xs sm:text-sm">
                        <div class="flex flex-col">
                            <span class="font-semibold">Size: ' . $size_name . '</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="w-4 h-4 rounded-full border" style="background:' . $color_code . '"></span>
                                <span>' . $color_name . '</span>
                            </div>
                        </div>
                        <span class="font-bold text-[var(--primary-color)]">' . $qty . ' pcs</span>
                      </div>';
}


    $html = '';
    $color = '';

    foreach ($stock_colors as $index => $stock_color) {
        // Choose size name based on session locale
        $color_name = session('locale') === 'ar' 
                     ? $stock_color->color->color_name_ar 
                     : $stock_color->color->color_name_en;

                     $color_code = $stock_color->color->color_code;

        $color_qty = $stock_color->qty;

        // Use unique IDs if needed, or just remove them
        $color .= '<div class="flex items-center justify-between border rounded-lg p-3 bg-gray-50 text-xs sm:text-sm">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full border" style="background:' . $color_code . '"></span>
                            <span class="font-semibold">' . $color_name . '</span>
                        </div>
                        <span class="font-bold text-[var(--primary-color)]">' . $color_qty . ' pcs</span>
                      </div>';
    }

   foreach ($stock_sizes as $index => $stock_size) {
        // Choose size name based on session locale
        $size_name = session('locale') === 'ar' 
                     ? $stock_size->size->size_name_ar 
                     : $stock_size->size->size_name_en;

        $size_qty = $stock_size->qty;

        // Use unique IDs if needed, or just remove them
        $html .= '<div class="p-3 border rounded-lg bg-gray-50 text-center font-bold text-gray-700 text-xs sm:text-sm">
                    <span id="size_label_'.$index.'">' . $size_name . '</span>
                    <span id="size_qty_'.$index.'" class="block text-[var(--primary-color)] mt-1">' . $size_qty . ' pcs</span>
                  </div>';
    }

    $data = [
        'stock_id' => $stock->id,
        'stock_code' => $stock->stock_code,
        'stock_notes' => $stock->stock_notes,
        'design_name' => $stock->design_name,
        'image_path' => $stock->images->first() ? $stock->images->first()->image_path : null,
        'barcode' => $stock->barcode,
        'status' => 'Available',
        'sizes_html' => $html,
        'size_color_html' => $htmlSizeColor, 
        'color'=>$color,
    ];

    return response()->json($data);
}

public function get_full_stock_details(Request $request)
{
    $locale = session('locale');
    $stock = Stock::with([
        'colorSizes.size',
        'colorSizes.color',
        'images'
    ])->findOrFail($request->id);

    // Calculate total quantity from all color_sizes
    $totalQuantity = 0;
    $colorSizeDetails = [];
    
    foreach ($stock->colorSizes as $item) {
        $size_name = $locale === 'ar' 
            ? ($item->size?->size_name_ar ?? '-') 
            : ($item->size?->size_name_en ?? '-');
        
        $color_name = $locale === 'ar' 
            ? ($item->color?->color_name_ar ?? '-') 
            : ($item->color?->color_name_en ?? '-');
        
        $qty = (int)($item->qty ?? 0);
        $totalQuantity += $qty;
        
        $colorSizeDetails[] = [
            'size_name' => $size_name,
            'color_name' => $color_name,
            'color_code' => $item->color?->color_code ?? '#000000',
            'quantity' => $qty,
        ];
    }

    // Get all images
    $images = $stock->images->map(function($img) {
        return $img->image_path;
    })->toArray();

    // Get tailor names
    $tailorNames = [];
    if ($stock->tailor_id) {
        $tailorIds = json_decode($stock->tailor_id, true);
        if (is_array($tailorIds)) {
            $tailors = Tailor::whereIn('id', $tailorIds)->get();
            foreach ($tailors as $tailor) {
                $tailorNames[] = $tailor->tailor_name;
            }
        }
    }

    $data = [
        'stock_id' => $stock->id,
        'stock_code' => $stock->stock_code ?? '-',
        'design_name' => $stock->design_name ?? '-',
        'barcode' => $stock->barcode ?? '-',
        'stock_notes' => $stock->stock_notes ?? '-',
        'cost_price' => $stock->cost_price ?? 0,
        'sales_price' => $stock->sales_price ?? 0,
        'tailor_charges' => $stock->tailor_charges ?? 0,
        'tailor_names' => $tailorNames,
        'total_quantity' => $totalQuantity,
        'images' => $images,
        'color_size_details' => $colorSizeDetails,
    ];

    return response()->json($data);
}

  public function get_stock_quantity(Request $request)
    {

     $id = $request->id ?? null;

$stock = Stock::with(['colors.color', 'sizes.size', 'images', 'colorSizes.size', 'colorSizes.color'])
    ->findOrFail($id);

       $stock_sizes = $stock->sizes;
        $stock_colors = $stock->colors;

       $stock_sizescolor = $stock->colorSizes; // Assuming it has color_id, size_id, qty // Assuming it has color_id, size_id, qty

       $htmlSizeColor = '<div class="row g-4">'; // start row

foreach ($stock_sizescolor as $item) {
    // Get size name based on session locale
    $size_name = session('locale') === 'ar'
        ? ($item->size?->size_name_ar ?? '-')
        : ($item->size?->size_name_en ?? '-');

    $color_name = session('locale') === 'ar'
        ? ($item->color?->color_name_ar ?? '-')
        : ($item->color?->color_name_en ?? '-');

    $color_code = $item->color?->color_code ?? '#000'; // fallback to black if null
    $qty = $item->qty ?? 0;

 $htmlSizeColor .= '
<div class="col-6 col-md-4 col-lg-3">
    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">

        <div class="card-body p-3">

            <!-- Color Circle + Color Name -->
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="rounded-circle border border-2 shadow-sm"
                    style="width:18px;height:18px;background-color:' . htmlspecialchars($color_code) . ';"></div>

                <span class="fw-semibold text-secondary" style="font-size: 0.8rem;">
                    ' . htmlspecialchars($color_name) . '
                </span>
            </div>

            <!-- Size badge -->
            <div class="mb-2 text-center">
                <span class="badge bg-dark text-light px-2 py-1 rounded-pill"
                      style="font-size: 0.75rem;">
                    Size: ' . htmlspecialchars($size_name) . '
                </span>

                <input type="hidden" name="stock_size_id[]" value="' . htmlspecialchars($item->size_id) . '">
                <input type="hidden" name="stock_color_id[]" value="' . htmlspecialchars($item->color_id) . '">
            </div>

            <!-- Previous Qty -->
            <div class="mb-2 text-center">
                <span class="badge bg-info text-dark px-3 py-1"
                      style="font-size: 0.75rem;">
                    Previous: <strong>' . htmlspecialchars($qty) . '</strong>
                </span>
            </div>

            <!-- Add Quantity Title -->
            <p class="text-center text-muted mb-2" style="font-size: 0.75rem;">
                Add Quantity
            </p>

            <!-- Input -->
            <input type="number" 
                step="1"
                name="size_color_qty[]" 
                class="form-control form-control-sm text-center rounded-pill shadow-sm qty-input"
                placeholder="0"
                data-available-qty="' . htmlspecialchars($qty) . '">
        </div>
    </div>
</div>';

}

$htmlSizeColor .= '</div>'; // end row




      $htmlColor = '<div class="row g-4">';

    foreach ($stock_colors as $stock_color) {
        $color_name = session('locale') === 'ar'
            ? $stock_color->color->color_name_ar
            : $stock_color->color->color_name_en;

        $color_code = $stock_color->color->color_code ?? '#000';
        $qty = $stock_color->qty ?? 0;

        $htmlColor .= '
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                <div class="card-body text-center p-4">
                    <div class="d-flex flex-column align-items-center gap-2 mb-3">
                        <div class="rounded-circle border border-2"
                            style="width:40px;height:40px;background-color:' . htmlspecialchars($color_code) . '"></div>
                        <h6 class="fw-semibold mb-0">' . htmlspecialchars($color_name) . '</h6>
                        <input type="hidden" name="color_id[]" value="' . htmlspecialchars($stock_color->color_id) . '">

                    </div>
                    <input type="number" step="1"
                        class="form-control form-control-lg text-center rounded-pill qty-input" name="color_qty[]"
                        placeholder="0"
                        data-available-qty="' . htmlspecialchars($qty) . '">
                </div>
            </div>
        </div>';
    }

$htmlColor .= '</div>';


$html = '<div class="row g-3">'; // start row

foreach ($stock_sizes as $stock_size) {
    $size_name = session('locale') === 'ar'
        ? $stock_size->size->size_name_ar
        : $stock_size->size->size_name_en;

    $qty = $stock_size->qty;

   $html .= '
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card h-100 border-0 shadow-sm hover-shadow transition">
            <div class="card-body text-center p-4">
                <h5 class="fw-bold text-dark mb-3">' 
                    . htmlspecialchars($size_name) . 
                '</h5>

                <input type="hidden" name="size_id[]" value="' . htmlspecialchars($stock_size->size_id) . '">

                <input type="number" step="1"
                    name="size_qty[]" class="form-control form-control-lg text-center rounded-pill qty-input"
                    placeholder="0"
                    data-available-qty="' . htmlspecialchars($qty) . '">
            </div>
        </div>
    </div>';

}

$html .= '</div>'; // end row


        // Get original tailor information
        $originalTailors = [];
        if ($stock->tailor_id) {
            $tailorIds = json_decode($stock->tailor_id, true);
            if (is_array($tailorIds)) {
                $tailors = Tailor::whereIn('id', $tailorIds)->get();
                foreach ($tailors as $tailor) {
                    $originalTailors[] = [
                        'id' => $tailor->id,
                        'name' => $tailor->tailor_name
                    ];
                }
            }
        }

        // Get all tailors for dropdown
        $allTailors = Tailor::orderBy('tailor_name', 'ASC')->get();
        $tailorsList = [];
        foreach ($allTailors as $tailor) {
            $tailorsList[] = [
                'id' => $tailor->id,
                'name' => $tailor->tailor_name
            ];
        }

        $data = [
            'stock_id' => $stock->id,
            'sizes_html' => $html,
            'size_color_html' => $htmlSizeColor,
            'color' => $htmlColor,
            'original_tailors' => $originalTailors,
            'all_tailors' => $tailorsList,
        ];

        return response()->json($data);
    }



public function add_quantity(Request $request)
{
    $stock_id = $request->stock_id;
    $isPull   = $request->qtyType === "pull";
    $actionType = $isPull ? 2 : 1;
    
    // Get authenticated user info for pull operations
    $user_id = \Illuminate\Support\Facades\Auth::id();
    $user = \App\Models\User::find($user_id);
    $user_name = $user ? $user->user_name : 'System';
    $pull_reason = $isPull ? ($request->pull_reason ?? '') : null;

    if ($request->filled('stock_size_id')) {

        foreach ($request->stock_size_id as $i => $sizeId) {


            $colorId = $request->stock_color_id[$i];

            $item = ColorSize::where('stock_id', $stock_id)
                             ->where('size_id', $sizeId)
                             ->where('color_id', $colorId)
                             ->first();

            if (!$item) continue;

            $old = $item->qty;
            $change = (int) $request->size_color_qty[$i];
            
            // Skip if change is 0 - no need to create history entry or update database
            if ($change == 0) {
                continue;
            }
            
            // For pull mode: validate quantity is positive and doesn't exceed available
            if ($isPull) {
                if ($change <= 0) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Pull quantity must be greater than 0'
                    ], 400);
                }
                if ($change > $old) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Pull quantity (' . $change . ') cannot exceed available quantity (' . $old . ')'
                    ], 400);
                }
                $new = $old - $change;
            } else {
                // For add mode: allow any value (positive or negative)
                $new = $old + $change;
            }

            if ($isPull) {
                Stock::where('id', $stock_id)->decrement('quantity', $change);
            } else {
                Stock::where('id', $stock_id)->increment('quantity', $change);
            }
            $item->update(['qty' => $new]);

            // Get tailor_id only for additions (not for pulls)
            $tailor_id = null;
            if (!$isPull) {
                // Check both selected_tailor_id and tailor_id (for backward compatibility)
                if ($request->filled('selected_tailor_id')) {
                    $tailor_id = (int) $request->selected_tailor_id;
                } elseif ($request->filled('tailor_id')) {
                    $tailor_id = (int) $request->tailor_id;
                }
            }

            // Only create StockHistory entry if change is greater than 0
            // This prevents creating entries with 0 quantity
            StockHistory::create([
                'stock_id'    => $stock_id,
                'size_id'     => $sizeId,
                'color_id'    => $colorId,
                'old_qty'     => $old,
                'changed_qty' => $change,
                'new_qty'     => $new,
                'action_type' => $actionType,
                'tailor_id'   => $tailor_id,
                'pull_notes'  => $pull_reason,
                'user_id'     => $user_id ? (string)$user_id : null,
                'added_by'    => $user_name,
            ]);

            // When pulling: reduce quantity on matching tailor_payment_items (stock received from tailor)
            if ($isPull && $change > 0) {
                $this->reduceTailorPaymentItemsForPull($stock_id, $colorId, $sizeId, $change);
            }

            // Log audit entry
            $stock = Stock::find($stock_id);
            if ($stock) {
                $this->logStockAudit(
                    $stock, 
                    'updated', 
                    $old, 
                    $new, 
                    null, 
                    null, 
                    null, 
                    $colorId, 
                    $sizeId, 
                    $isPull ? ('Quantity pulled: ' . $pull_reason) : 'Quantity added'
                );
                
                // Log material audit entry for quantity addition (only for additions, not pulls)
                if (!$isPull && $change > 0) {
                    // Deduct materials from main inventory and tailor inventory, and create audit logs
                    $tailorName = null;
                    if ($tailor_id) {
                        $tailor = Tailor::find($tailor_id);
                        $tailorName = $tailor ? $tailor->tailor_name : null;
                    }
                    $this->deductMaterialsFromInventory($stock_id, $change, 'manage_quantity', $tailor_id, $tailorName);
                    
                    try {
                        $tailorName = null;
                        if ($tailor_id) {
                            $tailor = Tailor::find($tailor_id);
                            $tailorName = $tailor ? $tailor->tailor_name : null;
                        }
                        
                        MaterialAuditLog::create([
                            'stock_id' => $stock_id,
                            'stock_code' => $stock->stock_code,
                            'barcode' => $stock->barcode,
                            'design_name' => $stock->design_name,
                            'operation_type' => 'quantity_added',
                            'quantity_added' => $change,
                            'tailor_id' => $tailor_id,
                            'tailor_name' => $tailorName,
                            'color_id' => $colorId,
                            'size_id' => $sizeId,
                            'user_id' => $user_id,
                            'added_by' => $user_name,
                            'added_at' => now(),
                            'notes' => 'Quantity added from manage quantities popup',
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Error creating material audit log: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    return response()->json([
        'status'  => 'success',
        'message' => $isPull ? 'Quantity pulled!' : 'Quantity added!'
    ]);
}

    /**
     * When quantity is pulled from stock, reduce the quantity on matching
     * TailorPaymentItems (stock additions that were paid / recorded as received from tailor).
     * Matches by stock_id and the related StockHistory's color_id and size_id.
     * Reduces FIFO (oldest payment items first) until the pull amount is covered.
     */
    protected function reduceTailorPaymentItemsForPull(int $stock_id, ?int $color_id, ?int $size_id, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $query = TailorPaymentItem::where('source', 'stock')
            ->where('stock_id', $stock_id)
            ->whereNotNull('stock_history_id')
            ->whereHas('stockHistory', function ($q) use ($color_id, $size_id) {
                if ($color_id !== null) {
                    $q->where('color_id', $color_id);
                } else {
                    $q->whereNull('color_id');
                }
                if ($size_id !== null) {
                    $q->where('size_id', $size_id);
                } else {
                    $q->whereNull('size_id');
                }
            })
            ->orderBy('id', 'asc');

        $remaining = $amount;
        foreach ($query->get() as $item) {
            if ($remaining <= 0) {
                break;
            }
            $deduct = (int) min($remaining, $item->quantity);
            if ($deduct <= 0) {
                continue;
            }
            $newQty = $item->quantity - $deduct;
            $item->quantity = $newQty;
            $item->total_charge = (float) $item->unit_charge * $newQty;
            $item->save();
            $remaining -= $deduct;
        }
    }

    /**
     * Show stock quantity audit list page
     */
    public function stockAudit()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(9, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        return view('stock.stock_audit');
    }

    /**
     * Show comprehensive stock audit log page
     */
    public function comprehensiveAudit()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(9, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        return view('stock.comprehensive_audit');
    }

    /**
     * Get comprehensive audit logs with search and date filtering
     * Grouped by barcode to aggregate quantities
     */
    public function getComprehensiveAudit(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);

            $query = StockAuditLog::with(['stock', 'user', 'size']);

            // Apply search filter
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('stock_code', 'like', '%' . $search . '%')
                      ->orWhere('barcode', 'like', '%' . $search . '%')
                      ->orWhere('design_name', 'like', '%' . $search . '%');
                });
            }

            // Apply date filter
            if (!empty($fromDate)) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if (!empty($toDate)) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            // Get all logs first
            $allLogs = $query->orderBy('created_at', 'DESC')->get();

            // Group by: barcode + size + operation_type + related_id + date+time (same minute)
            $grouped = [];
            foreach ($allLogs as $log) {
                // Create grouping key: barcode + operation_type + related_id + datetime (rounded to minute)
                $dateTime = \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i');
                $groupKey = ($log->barcode ?? $log->stock_code ?? '') . '|' . 
                           ($log->size_id ?? '') . '|' .
                           ($log->operation_type ?? '') . '|' . 
                           ($log->related_id ?? '') . '|' . 
                           $dateTime;

                if (!isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [
                        'logs' => [],
                        'total_quantity_change' => 0,
                        'min_previous_quantity' => PHP_INT_MAX,
                        'first_log' => $log,
                    ];
                }

                $grouped[$groupKey]['logs'][] = $log;
                $grouped[$groupKey]['total_quantity_change'] += $log->quantity_change;
                
                // Track previous quantity (minimum from all logs in group - this is the starting point before this operation)
                if ($log->previous_quantity < $grouped[$groupKey]['min_previous_quantity']) {
                    $grouped[$groupKey]['min_previous_quantity'] = $log->previous_quantity;
                }
            }

            // Calculate new quantity after grouping
            foreach ($grouped as $key => $group) {
                $grouped[$key]['new_quantity'] = ($group['min_previous_quantity'] === PHP_INT_MAX ? 0 : $group['min_previous_quantity']) + $group['total_quantity_change'];
            }

            // Convert grouped data to array and sort by date
            $groupedArray = array_values($grouped);
            usort($groupedArray, function($a, $b) {
                return strcmp($b['first_log']->created_at, $a['first_log']->created_at);
            });

            // Paginate manually
            $total = count($groupedArray);
            $offset = ($page - 1) * $perPage;
            $paginated = array_slice($groupedArray, $offset, $perPage);

            $locale = session('locale', 'en');
            $operationTypeLabels = [
                'added' => trans('messages.stock_addition', [], $locale) ?: 'Added',
                'updated' => trans('messages.quantity_added', [], $locale) ?: 'Updated',
                'sold' => trans('messages.quantity_sold_pos', [], $locale) ?: 'Sold',
                'transferred' => trans('messages.quantity_transferred_out', [], $locale) ?: 'Transferred',
                'special_order' => trans('messages.special_order', [], $locale) ?: 'Special Order',
            ];

            // Resolve special_order_no for special_order logs (existing rows may have order_id in related_info)
            $orderIdsForSpecialOrder = [];
            foreach ($paginated as $group) {
                $log = $group['first_log'];
                if ($log->operation_type === 'special_order' && !empty($log->related_info['order_id'])) {
                    $orderIdsForSpecialOrder[] = $log->related_info['order_id'];
                }
            }
            $specialOrderNos = [];
            if (!empty($orderIdsForSpecialOrder)) {
                $specialOrderNos = SpecialOrder::whereIn('id', array_unique($orderIdsForSpecialOrder))
                    ->pluck('special_order_no', 'id')->toArray();
            }

            // Resolve customer information for sold operations
            $customerIdsForSold = [];
            foreach ($paginated as $group) {
                $log = $group['first_log'];
                if ($log->operation_type === 'sold' && !empty($log->related_info['customer_id'])) {
                    $customerIdsForSold[] = $log->related_info['customer_id'];
                }
            }
            $customers = [];
            if (!empty($customerIdsForSold)) {
                $customers = Customer::whereIn('id', array_unique($customerIdsForSold))
                    ->get(['id', 'name', 'phone'])
                    ->keyBy('id')
                    ->toArray();
            }

            $formattedData = array_map(function($group) use ($operationTypeLabels, $locale, $specialOrderNos, $customers) {
                $log = $group['first_log'];
                $previousQty = $group['min_previous_quantity'] === PHP_INT_MAX ? 0 : $group['min_previous_quantity'];
                $newQty = $group['new_quantity'];
                $totalChange = $group['total_quantity_change'];

                // Get added_by from first log or combine if multiple users
                $addedBy = $log->added_by ?? 'System';
                if (count($group['logs']) > 1) {
                    $users = array_unique(array_filter(array_map(function($l) {
                        return $l->added_by ?? null;
                    }, $group['logs'])));
                    if (count($users) > 1) {
                        $addedBy = implode(', ', $users);
                    }
                }

                $relatedInfo = $log->related_info;
                $relatedDetails = '';
                if ($log->operation_type === 'transferred' && $relatedInfo) {
                    $relatedDetails = ($relatedInfo['from'] ?? '') . ' → ' . ($relatedInfo['to'] ?? '');
                } elseif ($log->operation_type === 'sold' && $relatedInfo) {
                    $customerId = $relatedInfo['customer_id'] ?? null;
                    if ($customerId && isset($customers[$customerId])) {
                        $customer = $customers[$customerId];
                        $customerName = $customer['name'] ?? 'N/A';
                        $customerPhone = $customer['phone'] ?? 'N/A';
                        $relatedDetails = $customerName . ' (' . $customerPhone . ')';
                    } else {
                        $relatedDetails = 'Customer ID: ' . ($customerId ?? 'N/A');
                    }
                }

                // For special_order: show special_order_no when available (from DB or resolved from related_info)
                $relatedId = $log->related_id ?? '—';
                if ($log->operation_type === 'special_order' && !empty($log->related_info['order_id'])) {
                    $oid = $log->related_info['order_id'];
                    if (!empty($specialOrderNos[$oid])) {
                        $relatedId = $specialOrderNos[$oid];
                    }
                }

                $sizeName = '—';
                if (!empty($log->size)) {
                    $sizeName = $locale === 'ar'
                        ? ($log->size->size_name_ar ?? $log->size->size_name_en ?? '—')
                        : ($log->size->size_name_en ?? $log->size->size_name_ar ?? '—');
                }

                return [
                    'id' => $log->id,
                    'stock_code' => $log->stock_code ?? '—',
                    'barcode' => $log->barcode ?? '—',
                    'size' => $sizeName,
                    'design_name' => $log->design_name ?? '—',
                    'operation_type' => $log->operation_type,
                    'operation_label' => $operationTypeLabels[$log->operation_type] ?? $log->operation_type,
                    'previous_quantity' => $previousQty,
                    'new_quantity' => $newQty,
                    'quantity_change' => $totalChange,
                    'related_id' => $relatedId,
                    'related_details' => $relatedDetails,
                    'added_by' => $addedBy,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'date' => $log->created_at->format('Y-m-d'),
                    'time' => $log->created_at->format('H:i:s'),
                ];
            }, $paginated);

            // Calculate remaining quantity for searched stock (total + per size)
            $remainingQty = null;
            $remainingBySize = null;
            if (!empty($search)) {
                // Get all logs for this barcode and calculate final quantity (by size)
                $barcodeLogs = StockAuditLog::where(function($q) use ($search) {
                    $q->where('stock_code', 'like', '%' . $search . '%')
                      ->orWhere('barcode', 'like', '%' . $search . '%')
                      ->orWhere('design_name', 'like', '%' . $search . '%');
                })->with('size')->orderBy('created_at', 'ASC')->get();
                
                if ($barcodeLogs->count() > 0) {
                    $finalBySize = [];
                    foreach ($barcodeLogs as $l) {
                        $sid = $l->size_id ?? 0;
                        $finalBySize[$sid] = (int)($l->new_quantity ?? 0);
                    }

                    $remainingQty = array_sum($finalBySize);

                    // Build readable breakdown
                    $remainingBySize = [];
                    foreach ($finalBySize as $sid => $qty) {
                        $sizeName = '—';
                        if (!empty($sid)) {
                            $size = $barcodeLogs->firstWhere('size_id', $sid)?->size;
                            if ($size) {
                                $sizeName = $locale === 'ar'
                                    ? ($size->size_name_ar ?? $size->size_name_en ?? '—')
                                    : ($size->size_name_en ?? $size->size_name_ar ?? '—');
                            }
                        }
                        $remainingBySize[] = [
                            'size_id' => (int)$sid,
                            'size' => $sizeName,
                            'quantity' => (int)$qty,
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'remaining_quantity' => $remainingQty,
                'remaining_by_size' => $remainingBySize,
                'current_page' => (int)$page,
                'last_page' => (int)ceil($total / $perPage),
                'total' => $total,
                'per_page' => $perPage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching audit data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock audit list - all stock items with aggregated quantities
     */
    public function getStockAuditList(Request $request)
    {
        try {
            $locale = session('locale', 'en');
            
            // Get all stocks with their color-size combinations
            $stocks = Stock::with(['colorSizes.color', 'colorSizes.size', 'category'])
                ->whereHas('colorSizes')
                ->orderBy('id', 'DESC')
                ->get();

            $auditList = [];
            
            foreach ($stocks as $stock) {
                // Aggregate totals across all color/size combinations for this stock
                $totalAdded = 0;
                $totalPulled = 0;
                $totalPosSold = 0;
                $totalTransferredOut = 0;
                $totalTransferredIn = 0;
                $totalRemaining = 0;

                foreach ($stock->colorSizes as $colorSize) {
                    $colorId = $colorSize->color_id;
                    $sizeId = $colorSize->size_id;
                    $currentQty = (int)($colorSize->qty ?? 0);
                    $totalRemaining += $currentQty;

                    // Get color and size names for matching
                    $color = $colorSize->color;
                    $size = $colorSize->size;
                    $colorName = $color ? ($locale === 'ar' ? ($color->color_name_ar ?? $color->color_name_en) : ($color->color_name_en ?? $color->color_name_ar)) : '';
                    $sizeName = $size ? ($locale === 'ar' ? ($size->size_name_ar ?? $size->size_name_en) : ($size->size_name_en ?? $size->size_name_ar)) : '';

                    // Total added from history (action_type = 1 means addition)
                    $totalAdded += StockHistory::where('stock_id', $stock->id)
                        ->where('color_id', $colorId)
                        ->where('size_id', $sizeId)
                        ->where('action_type', 1)
                        ->sum('changed_qty');

                    // Total pulled from history (action_type = 2 means pull)
                    $totalPulled += StockHistory::where('stock_id', $stock->id)
                        ->where('color_id', $colorId)
                        ->where('size_id', $sizeId)
                        ->where('action_type', 2)
                        ->sum('changed_qty');

                    // Total POS sold
                    $totalPosSold += \App\Models\PosOrdersDetail::where('item_id', $stock->id)
                        ->where('color_id', $colorId)
                        ->where('size_id', $sizeId)
                        ->sum('item_quantity');

                    // Get transfers OUT (from main/warehouse to channels/boutiques)
                    // Transfers where item_code matches and quantity_pulled > 0
                    $transfersOut = \App\Models\TransferItemHistory::where('item_code', $stock->stock_code)
                        ->where('quantity_pulled', '>', 0)
                        ->with('transfer')
                        ->get()
                        ->filter(function($transfer) use ($colorName, $sizeName) {
                            $transferColor = $transfer->item_color ?? '';
                            $transferSize = $transfer->item_size ?? '';
                            $colorMatch = empty($transferColor) || empty($colorName) || $transferColor === $colorName;
                            $sizeMatch = empty($transferSize) || empty($sizeName) || $transferSize === $sizeName;
                            return $colorMatch && $sizeMatch;
                        });
                    
                    $totalTransferredOut += $transfersOut->sum('quantity_pulled');

                    // Get transfers IN (from channels/boutiques to main/warehouse)
                    // Transfers where item_code matches and quantity_pushed > 0
                    $transfersIn = \App\Models\TransferItemHistory::where('item_code', $stock->stock_code)
                        ->where('quantity_pushed', '>', 0)
                        ->with('transfer')
                        ->get()
                        ->filter(function($transferItem) use ($colorName, $sizeName) {
                            $transfer = $transferItem->transfer;
                            if (!$transfer) return false;
                            
                            // Only include transfers TO main warehouse
                            $toLocation = $transfer->to ?? '';
                            if ($toLocation !== 'main') return false;
                            
                            // Match by color and size - be flexible with empty values
                            $transferColor = trim($transferItem->item_color ?? '');
                            $transferSize = trim($transferItem->item_size ?? '');
                            $stockColorName = trim($colorName ?? '');
                            $stockSizeName = trim($sizeName ?? '');
                            
                            // If both color and size are empty in transfer, match this color/size combination
                            if (empty($transferColor) && empty($transferSize)) {
                                return true;
                            }
                            
                            // If transfer has no color but has size, match by size only
                            if (empty($transferColor) && !empty($transferSize)) {
                                return empty($stockSizeName) || strtolower($transferSize) === strtolower($stockSizeName);
                            }
                            
                            // If transfer has color but no size, match by color only
                            if (!empty($transferColor) && empty($transferSize)) {
                                return empty($stockColorName) || strtolower($transferColor) === strtolower($stockColorName);
                            }
                            
                            // Both color and size exist in transfer - both must match
                            $colorMatch = empty($stockColorName) || strtolower($transferColor) === strtolower($stockColorName);
                            $sizeMatch = empty($stockSizeName) || strtolower($transferSize) === strtolower($stockSizeName);
                            
                            return $colorMatch && $sizeMatch;
                        });
                    
                    $totalTransferredIn += $transfersIn->sum('quantity_pushed');
                }

                $auditList[] = [
                    'stock_id' => $stock->id,
                    'barcode' => $stock->barcode ?? '-',
                    'stock_code' => $stock->stock_code,
                    'design_name' => $stock->design_name ?? $stock->stock_code,
                    'quantity_added' => (int)$totalAdded,
                    'stock_addition' => (int)$totalAdded,
                    'quantity_pulled' => (int)$totalPulled,
                    'quantity_sold_pos' => (int)$totalPosSold,
                    'quantity_transferred_out' => (int)$totalTransferredOut,
                    'quantity_received' => (int)$totalTransferredIn,
                    'remaining_quantity' => (int)$totalRemaining,
                ];
            }

            // Paginate results
            $page = $request->input('page', 1);
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            $total = count($auditList);
            $paginated = array_slice($auditList, $offset, $perPage);

            return response()->json([
                'success' => true,
                'data' => $paginated,
                'current_page' => (int)$page,
                'last_page' => (int)ceil($total / $perPage),
                'total' => $total,
                'per_page' => $perPage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock audit details - transfers and POS sales with names
     */
    public function getStockAuditDetails(Request $request)
    {
        try {
            $stockId = $request->input('stock_id');
            $type = $request->input('type'); // 'added', 'pos', 'transferred'
            $locale = session('locale', 'en');

            $stock = Stock::findOrFail($stockId);
            $details = [];

            if ($type === 'transferred') {
                // Get all transfers OUT (to channels/boutiques) - these are the ones we want to show
                $transfersOut = \App\Models\TransferItemHistory::where('item_code', $stock->stock_code)
                    ->where('quantity_pulled', '>', 0)
                    ->with('transfer')
                    ->get();

                foreach ($transfersOut as $transferItem) {
                    $transfer = $transferItem->transfer;
                    if (!$transfer) continue;

                    $toLocation = $transfer->to ?? '';
                    $locationName = $this->getLocationName($toLocation, $locale);

                    // Only include if transferred to a channel or boutique (not main)
                    // This shows where items were transferred TO
                    if ($toLocation !== 'main' && !empty($locationName)) {
                        $details[] = [
                            'name' => $locationName,
                            'quantity' => (int)$transferItem->quantity_pulled,
                            'date' => $transfer->date ? $transfer->date->format('Y-m-d') : '',
                            'type' => strpos($toLocation, 'boutique-') === 0 ? 'boutique' : 'channel',
                            'transfer_code' => $transfer->transfer_code ?? ''
                        ];
                    }
                }
                
                // Also get transfers IN (from channels/boutiques) if user clicked on "received" column
                // But for now, we'll show transfers OUT when type is 'transferred'
                // The "received" column can use the same type but we'll differentiate if needed
            } elseif ($type === 'pos') {
                // Get all POS sales with color and size
                $posSales = \App\Models\PosOrdersDetail::where('item_id', $stockId)
                    ->with(['color', 'size', 'order'])
                    ->get();

                // Group by order_no to show order_no and quantity
                foreach ($posSales as $posSale) {
                    $orderNo = $posSale->order ? ($posSale->order->order_no ?? '-') : '-';
                    
                    $details[] = [
                        'name' => $orderNo,
                        'order_no' => $orderNo,
                        'quantity' => (int)$posSale->item_quantity,
                        'date' => $posSale->order && $posSale->order->created_at ? $posSale->order->created_at->format('Y-m-d') : '',
                        'type' => 'pos'
                    ];
                }
                
                // Aggregate by order_no if there are duplicates
                $aggregated = [];
                foreach ($details as $detail) {
                    $key = $detail['order_no'];
                    if (!isset($aggregated[$key])) {
                        $aggregated[$key] = [
                            'name' => $detail['name'],
                            'order_no' => $detail['order_no'],
                            'quantity' => 0,
                            'date' => $detail['date'],
                            'type' => 'pos'
                        ];
                    }
                    $aggregated[$key]['quantity'] += $detail['quantity'];
                }
                $details = array_values($aggregated);
            } elseif ($type === 'received') {
                // Get all transfers IN (from channels/boutiques to main/warehouse)
                $transfersIn = \App\Models\TransferItemHistory::where('item_code', $stock->stock_code)
                    ->where('quantity_pushed', '>', 0)
                    ->with('transfer')
                    ->get();

                foreach ($transfersIn as $transferItem) {
                    $transfer = $transferItem->transfer;
                    if (!$transfer) continue;

                    $fromLocation = $transfer->from ?? '';
                    $toLocation = $transfer->to ?? '';
                    
                    // Only include if transferred TO main warehouse FROM a channel or boutique
                    if ($toLocation === 'main' && $fromLocation !== 'main') {
                        $locationName = $this->getLocationName($fromLocation, $locale);
                        
                        if (!empty($locationName)) {
                            $details[] = [
                                'name' => $locationName,
                                'quantity' => (int)$transferItem->quantity_pushed,
                                'date' => $transfer->date ? $transfer->date->format('Y-m-d') : '',
                                'type' => strpos($fromLocation, 'boutique-') === 0 ? 'boutique' : 'channel'
                            ];
                        }
                    }
                }
            } elseif ($type === 'pulled') {
                // Get all pulled quantities from history
                $pulledHistory = StockHistory::where('stock_id', $stockId)
                    ->where('action_type', 2)
                    ->with(['size', 'color'])
                    ->orderBy('created_at', 'DESC')
                    ->get();

                foreach ($pulledHistory as $history) {
                    $sizeName = $history->size ? ($locale === 'ar' ? ($history->size->size_name_ar ?? $history->size->size_name_en) : ($history->size->size_name_en ?? $history->size->size_name_ar)) : '-';
                    $colorName = $history->color ? ($locale === 'ar' ? ($history->color->color_name_ar ?? $history->color->color_name_en) : ($history->color->color_name_en ?? $history->color->color_name_ar)) : '-';
                    
                    $details[] = [
                        'name' => ($sizeName !== '-' && $colorName !== '-') ? "{$sizeName} / {$colorName}" : ($sizeName !== '-' ? $sizeName : ($colorName !== '-' ? $colorName : 'N/A')),
                        'quantity' => (int)$history->changed_qty,
                        'date' => $history->created_at ? $history->created_at->format('Y-m-d H:i') : '',
                        'user' => $history->added_by ?? '-',
                        'reason' => $history->pull_notes ?? '-',
                    ];
                }
            } elseif ($type === 'added') {
                // Get all additions from stock history
                $additions = StockHistory::where('stock_id', $stockId)
                    ->where('action_type', 1)
                    ->with(['size', 'color'])
                    ->orderBy('created_at', 'DESC')
                    ->get();

                foreach ($additions as $addition) {
                    $sizeName = $addition->size ? ($locale === 'ar' ? ($addition->size->size_name_ar ?? $addition->size->size_name_en) : ($addition->size->size_name_en ?? $addition->size->size_name_ar)) : '';
                    $colorName = $addition->color ? ($locale === 'ar' ? ($addition->color->color_name_ar ?? $addition->color->color_name_en) : ($addition->color->color_name_en ?? $addition->color->color_name_ar)) : '';
                    
                    // Format name based on what's available
                    $name = '';
                    if (!empty($sizeName) && !empty($colorName)) {
                        $name = "{$sizeName} / {$colorName}";
                    } elseif (!empty($sizeName)) {
                        $name = $sizeName;
                    } elseif (!empty($colorName)) {
                        $name = $colorName;
                    } else {
                        $name = 'N/A';
                    }
                    
                    $details[] = [
                        'name' => $name,
                        'quantity' => (int)$addition->changed_qty,
                        'date' => $addition->created_at ? $addition->created_at->format('Y-m-d H:i') : '',
                        'added_by' => $addition->added_by ?? '-',
                        'added_on' => $addition->created_at ? $addition->created_at->format('Y-m-d H:i') : '',
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $details
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function to get location name (channel or boutique)
     */
    private function getLocationName($locationId, $locale = 'en')
    {
        if (empty($locationId) || $locationId === 'main') {
            return 'Main Warehouse';
        }

        // Check if it's a boutique
        if (strpos($locationId, 'boutique-') === 0) {
            $boutiqueId = str_replace('boutique-', '', $locationId);
            $boutique = \App\Models\Boutique::find($boutiqueId);
            return $boutique ? $boutique->boutique_name : $locationId;
        }

        // Check if it's a channel
        if (strpos($locationId, 'channel-') === 0) {
            $channelId = str_replace('channel-', '', $locationId);
            $channel = \App\Models\Channel::find($channelId);
            if ($channel) {
                return $locale == 'ar' ? ($channel->channel_name_ar ?? $channel->channel_name_en) : ($channel->channel_name_en ?? $channel->channel_name_ar);
            }
        }

        return $locationId;
    }

    /**
     * Show stock materials page
     */
    public function stockMaterials()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(9, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        return view('stock.stock_materials');
    }

    /**
     * Get stocks with materials (paginated)
     */
    public function getstockMaterials(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');

            $query = Stock::with(['stockMaterials', 'images', 'category']);

            // Apply search filter
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('stock_code', 'like', '%' . $search . '%')
                      ->orWhere('design_name', 'like', '%' . $search . '%')
                      ->orWhere('barcode', 'like', '%' . $search . '%');
                });
            }

            $stocks = $query->orderBy('id', 'DESC')->paginate($perPage, ['*'], 'page', $page);

            $formattedData = $stocks->map(function($stock) {
                // Get materials for this stock
                $materials = [];
                if ($stock->stockMaterials && $stock->stockMaterials->count() > 0) {
                    $stockMaterial = $stock->stockMaterials->first();
                    if ($stockMaterial && $stockMaterial->materials) {
                        foreach ($stockMaterial->materials as $materialData) {
                            $material = Material::find($materialData['material_id'] ?? null);
                            if ($material) {
                                $materials[] = [
                                    'id' => $material->id,
                                    'name' => $material->material_name,
                                    'quantity' => $materialData['quantity'] ?? 0,
                                    'unit' => $materialData['unit'] ?? $material->unit ?? 'pieces',
                                ];
                            }
                        }
                    }
                }

                // Get first image
                $image = '/images/placeholder.png';
                if ($stock->images && $stock->images->count() > 0) {
                    $firstImage = $stock->images->first();
                    if ($firstImage && $firstImage->image_path) {
                        $image = $firstImage->image_path;
                    }
                }

                return [
                    'id' => $stock->id,
                    'stock_code' => $stock->stock_code ?? '—',
                    'design_name' => $stock->design_name ?? '—',
                    'barcode' => $stock->barcode ?? '—',
                    'category' => $stock->category ? $stock->category->category_name : '—',
                    'image' => $image,
                    'materials' => $materials,
                    'materials_count' => count($materials),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'current_page' => $stocks->currentPage(),
                'last_page' => $stocks->lastPage(),
                'total' => $stocks->total(),
                'per_page' => $stocks->perPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to log stock audit entries
     */
    private function logStockAudit($stock, $operationType, $previousQty, $newQty, $relatedId = null, $relatedType = null, $relatedInfo = null, $colorId = null, $sizeId = null, $notes = null)
    {
        $user = Auth::user();
        $userName = $user ? $user->user_name : 'System';
        
        StockAuditLog::create([
            'stock_id' => $stock->id,
            'stock_code' => $stock->stock_code,
            'barcode' => $stock->barcode,
            'design_name' => $stock->design_name,
            'operation_type' => $operationType,
            'previous_quantity' => $previousQty,
            'new_quantity' => $newQty,
            'quantity_change' => $newQty - $previousQty,
            'related_id' => $relatedId,
            'related_type' => $relatedType,
            'related_info' => $relatedInfo ? (is_array($relatedInfo) ? $relatedInfo : ['info' => $relatedInfo]) : null,
            'color_id' => $colorId,
            'size_id' => $sizeId,
            'user_id' => $user ? $user->id : null,
            'added_by' => $userName,
            'notes' => $notes,
        ]);
    }

    /**
     * Deduct required materials from tailor inventory when stocks are added to stock
     */
    private function deductMaterialsFromTailor($stockId, $tailorId, $stockQuantity)
    {
        try {
            // Get required materials for this stock
            $stockMaterial = stockMaterial::where('stock_id', $stockId)->first();
            
            if (!$stockMaterial || !$stockMaterial->materials) {
                return; // No materials required for this stock
            }

            // Process each required material
            foreach ($stockMaterial->materials as $materialData) {
                $materialId = $materialData['material_id'] ?? null;
                $requiredPerstock = floatval($materialData['quantity'] ?? 0);
                
                if (!$materialId || $requiredPerstock <= 0) {
                    continue;
                }

                // Calculate total required quantity (per stock * quantity of stocks added)
                $totalRequired = $requiredPerstock * $stockQuantity;

                // Find TailorMaterial records for this tailor, material, and stock
                $tailorMaterials = TailorMaterial::where('tailor_id', $tailorId)
                    ->where('material_id', $materialId)
                    ->where(function($q) use ($stockId) {
                        $q->where('stock_id', $stockId)
                          ->orWhereNull('stock_id'); // Also check materials not tied to specific stock
                    })
                    ->orderBy('stock_id', 'desc') // Prefer stock-specific materials first
                    ->get();

                // Deduct from tailor materials
                $remainingToDeduct = $totalRequired;
                
                foreach ($tailorMaterials as $tailorMaterial) {
                    if ($remainingToDeduct <= 0) {
                        break;
                    }

                    $currentQuantity = floatval($tailorMaterial->quantity ?? 0);
                    
                    if ($currentQuantity > 0) {
                        $deductAmount = min($currentQuantity, $remainingToDeduct);
                        $newQuantity = max(0, $currentQuantity - $deductAmount);
                        
                        $tailorMaterial->quantity = $newQuantity;
                        $tailorMaterial->save();
                        
                        $remainingToDeduct -= $deductAmount;
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error deducting materials from tailor inventory: ' . $e->getMessage());
        }
    }

    /**
     * Deduct materials from main inventory when stocks are added
     * Also deducts from tailor inventory if tailor is provided
     * Creates MaterialQuantityAudit entries for each material deducted
     */
    private function deductMaterialsFromInventory($stockId, $stockQuantity, $source = 'stock', $tailorId = null, $tailorName = null, $specialOrderId = null, $specialOrderNumber = null)
    {
        try {
            // Get required materials for this stock
            $stockMaterial = stockMaterial::where('stock_id', $stockId)->first();
            
            if (!$stockMaterial || !$stockMaterial->materials) {
                return; // No materials required for this stock
            }

            $stock = Stock::find($stockId);
            if (!$stock) {
                return;
            }

            $user = Auth::user();
            $userName = $user ? ($user->user_name ?? $user->name ?? 'system') : 'system';
            $userId = $user ? $user->id : null;

            // Process each required material
            foreach ($stockMaterial->materials as $materialData) {
                $materialId = $materialData['material_id'] ?? null;
                $requiredPerstock = floatval($materialData['quantity'] ?? 0);
                
                if (!$materialId || $requiredPerstock <= 0) {
                    continue;
                }

                // Get material
                $material = Material::find($materialId);
                if (!$material) {
                    continue;
                }

                // Calculate total required quantity (per stock * quantity of stocks added)
                $totalRequired = $requiredPerstock * $stockQuantity;

                // Get current quantity based on unit
                $getCurrentQuantity = function($mat) {
                    if ($mat->unit === 'roll') {
                        return floatval($mat->rolls_count ?? 0);
                    } else {
                        // For meter and piece units, use meters_per_roll
                        return floatval($mat->meters_per_roll ?? 0);
                    }
                };

                // For stock and manage_quantity: Only deduct from tailor materials, NOT from main inventory
                // Main inventory is only deducted when sending materials directly to tailor
                $previousQuantity = $getCurrentQuantity($material);
                $remainingQuantity = $previousQuantity;

                // If no tailor, skip material deduction (materials must be sent to tailor first)
                if (!$tailorId) {
                    continue; // Skip if no tailor - materials must be sent to tailor before use
                }

                // Deduct from tailor materials (required for stock and manage_quantity operations)
                $tailorMaterialQuantityDeducted = 0;
                $previousTailorMaterialQuantity = 0;
                $newTailorMaterialQuantity = 0;
                
                // Find TailorMaterial records for this tailor, material, and stock
                $tailorMaterials = TailorMaterial::where('tailor_id', $tailorId)
                    ->where('material_id', $materialId)
                    ->where(function($q) use ($stockId) {
                        $q->where('stock_id', $stockId)
                          ->orWhereNull('stock_id'); // Also check materials not tied to specific stock
                    })
                    ->orderBy('stock_id', 'desc') // Prefer stock-specific materials first
                    ->get();

                // Calculate total previous tailor material quantity
                foreach ($tailorMaterials as $tailorMaterial) {
                    $previousTailorMaterialQuantity += floatval($tailorMaterial->quantity ?? 0);
                }

                // Check if sufficient quantity available in tailor materials
                $status = 'success';
                $allowNegative = true; // Allow negative balance when adding stock
                
                // Deduct from tailor materials (allow negative if insufficient)
                if ($totalRequired > 0) {
                    $remainingToDeduct = $totalRequired;
                    
                    foreach ($tailorMaterials as $tailorMaterial) {
                        if ($remainingToDeduct <= 0) {
                            break;
                        }

                        $currentQuantity = floatval($tailorMaterial->quantity ?? 0);
                        
                        if ($currentQuantity > 0) {
                            $deductAmount = min($currentQuantity, $remainingToDeduct);
                            $newQuantity = $currentQuantity - $deductAmount;
                            
                            $tailorMaterial->quantity = $newQuantity;
                            $tailorMaterial->save();
                            
                            $tailorMaterialQuantityDeducted += $deductAmount;
                            $remainingToDeduct -= $deductAmount;
                        }
                    }
                    
                    // If still need to deduct more and allow negative, create/update a record with negative quantity
                    if ($remainingToDeduct > 0 && $allowNegative) {
                        // Find or create a TailorMaterial record for this combination
                        $negativeTailorMaterial = TailorMaterial::where('tailor_id', $tailorId)
                            ->where('material_id', $materialId)
                            ->where(function($q) use ($stockId) {
                                $q->where('stock_id', $stockId)
                                  ->orWhereNull('stock_id');
                            })
                            ->first();
                        
                        if ($negativeTailorMaterial) {
                            // Update existing record (may already be negative)
                            $currentQty = floatval($negativeTailorMaterial->quantity ?? 0);
                            $negativeTailorMaterial->quantity = $currentQty - $remainingToDeduct;
                            $negativeTailorMaterial->save();
                        } else {
                            // Create new record with negative quantity
                            $negativeTailorMaterial = TailorMaterial::create([
                                'tailor_id' => $tailorId,
                                'material_id' => $materialId,
                                'stock_id' => $stockId,
                                'quantity' => -$remainingToDeduct,
                                'stocks_expected' => 0,
                                'status' => 'pending',
                                'sent_date' => now()->format('Y-m-d'),
                                'added_by' => $userName,
                                'user_id' => $userId ?? 1,
                            ]);
                        }
                        
                        $tailorMaterialQuantityDeducted += $remainingToDeduct;
                        $status = 'insufficient'; // Mark as insufficient but allowed
                    } else if ($remainingToDeduct > 0) {
                        $status = 'insufficient';
                    }

                    // Recalculate total new tailor material quantity after deduction (including negative)
                    $tailorMaterials = TailorMaterial::where('tailor_id', $tailorId)
                        ->where('material_id', $materialId)
                        ->where(function($q) use ($stockId) {
                            $q->where('stock_id', $stockId)
                              ->orWhereNull('stock_id');
                        })
                        ->get();
                    
                    foreach ($tailorMaterials as $tailorMaterial) {
                        $newTailorMaterialQuantity += floatval($tailorMaterial->quantity ?? 0);
                    }
                }

                // Create MaterialQuantityAudit entry
                try {
                    $sourceLabels = [
                        'stock' => 'Stock Added',
                        'special_order' => 'Special Order Received',
                        'manage_quantity' => 'Quantity Added (Manage Quantity)'
                    ];

                    MaterialQuantityAudit::create([
                        'material_id' => $materialId,
                        'stock_id' => $stockId,
                        'stock_code' => $stock->stock_code,
                        'source' => $source,
                        'status' => $status,
                        'special_order_id' => $specialOrderId,
                        'special_order_number' => $specialOrderNumber,
                        'material_name' => $material->material_name,
                        'operation_type' => 'material_deducted',
                        'previous_quantity' => $previousQuantity, // Main inventory quantity (unchanged)
                        'new_quantity' => $remainingQuantity, // Main inventory quantity (unchanged)
                        'quantity_change' => 0, // No change to main inventory
                        'remaining_quantity' => $remainingQuantity,
                        'tailor_material_quantity_deducted' => $tailorMaterialQuantityDeducted,
                        'previous_tailor_material_quantity' => $previousTailorMaterialQuantity,
                        'new_tailor_material_quantity' => $newTailorMaterialQuantity,
                        'tailor_id' => $tailorId,
                        'tailor_name' => $tailorName,
                        'user_id' => $userId,
                        'added_by' => $userName,
                        'notes' => ($sourceLabels[$source] ?? ucfirst($source)) . ' - stock: ' . $stock->stock_code . ', Quantity: ' . $stockQuantity . ', Material per stock: ' . $requiredPerstock . ', Total deducted from tailor: ' . $totalRequired . ' (Main inventory unchanged)',
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error creating material quantity audit: ' . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error deducting materials from inventory: ' . $e->getMessage());
        }
    }

    /**
     * Show material audit page
     */
    public function materialAudit()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(9, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        return view('stock.material_audit');
    }

    /**
     * Get material audit data
     */
    public function getMaterialAuditData(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');

            $locale = session('locale', 'en');

            // Get material audit logs
            $query = MaterialAuditLog::with(['stock', 'stock.stockMaterials', 'tailor'])
                ->whereHas('stock')
                ->orderBy('added_at', 'DESC')
                ->orderBy('id', 'DESC');

            // Apply search filter
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('stock_code', 'like', '%' . $search . '%')
                      ->orWhere('design_name', 'like', '%' . $search . '%')
                      ->orWhere('barcode', 'like', '%' . $search . '%');
                });
            }

            $auditLogs = $query->get();

            // Group logs by barcode/stock_code/design_name and same added_at (within 1 minute) and same user
            $groupedLogs = [];
            foreach ($auditLogs as $log) {
                $key = ($log->barcode ?? '') . '|' . ($log->stock_code ?? '') . '|' . ($log->design_name ?? '');
                $addedAt = $log->added_at ? $log->added_at->format('Y-m-d H:i') : ($log->created_at ? $log->created_at->format('Y-m-d H:i') : '');
                $userId = $log->user_id;
                
                // Create a more specific key for grouping entries from the same operation
                $groupKey = $key . '|' . $addedAt . '|' . ($userId ?? '');
                
                if (!isset($groupedLogs[$groupKey])) {
                    $groupedLogs[$groupKey] = [
                        'logs' => [],
                        'total_quantity' => 0,
                        'first_log' => $log,
                    ];
                }
                
                $groupedLogs[$groupKey]['logs'][] = $log;
                $groupedLogs[$groupKey]['total_quantity'] += $log->quantity_added ?? 0;
            }

            // Convert grouped logs to array and apply pagination manually
            $groupedArray = array_values($groupedLogs);
            $totalGroups = count($groupedArray);
            $offset = ($page - 1) * $perPage;
            $paginatedGroups = array_slice($groupedArray, $offset, $perPage);

            $formattedData = collect($paginatedGroups)->map(function($group) use ($locale) {
                $log = $group['first_log'];
                $totalQuantity = $group['total_quantity'];
                $stock = $log->stock;
                if (!$stock) {
                    return null;
                }

                // Get materials required for stock
                $requiredMaterials = [];
                $materialStockQuantities = [];
                $stockMaterial = stockMaterial::where('stock_id', $stock->id)->first();
                if ($stockMaterial && $stockMaterial->materials) {
                    foreach ($stockMaterial->materials as $materialData) {
                        $material = Material::find($materialData['material_id'] ?? null);
                        if ($material) {
                            // Multiply required quantity by total_quantity (sum of all grouped entries)
                            $requiredQty = ($materialData['quantity'] ?? 0) * $totalQuantity;
                            $unit = $materialData['unit'] ?? $material->unit ?? 'pieces';
                            
                            // Calculate available quantity in stock based on unit
                            $availableQty = 0;
                            if ($material->unit === 'roll') {
                                $availableQty = floatval($material->rolls_count ?? 0);
                            } elseif ($material->unit === 'meter') {
                                // Available = rolls_count * meters_per_roll (but rolls_count is usually 1, so it's meters_per_roll)
                                $availableQty = floatval($material->rolls_count ?? 1) * floatval($material->meters_per_roll ?? 0);
                            } elseif ($material->unit === 'piece') {
                                // For pieces, available = meters_per_roll (which stores total pieces)
                                $availableQty = floatval($material->meters_per_roll ?? 0);
                            } else {
                                // Default: use meters_per_roll as total quantity
                                $availableQty = floatval($material->meters_per_roll ?? 0);
                            }
                            
                            $requiredMaterials[] = [
                                'id' => $material->id,
                                'name' => $material->material_name,
                                'quantity' => $requiredQty,
                                'unit' => $unit,
                            ];
                            
                            $materialStockQuantities[] = [
                                'id' => $material->id,
                                'name' => $material->material_name,
                                'quantity' => $availableQty,
                                'unit' => $unit,
                            ];
                        }
                    }
                }

                // Get materials sent to tailor for this stock
                $tailorMaterialsInfo = [];
                $materialBalances = [];
                
                if (!empty($requiredMaterials)) {
                    $materialIds = array_column($requiredMaterials, 'id');
                    
                    // Get tailor materials for this stock and tailor (if applicable)
                    $tailorMaterialsQuery = TailorMaterial::whereIn('material_id', $materialIds)
                        ->where(function($q) use ($stock, $log) {
                            $q->where('stock_id', $stock->id)
                              ->orWhereNull('stock_id');
                        });
                    
                    // If log has tailor_id, filter by it
                    if ($log->tailor_id) {
                        $tailorMaterialsQuery->where('tailor_id', $log->tailor_id);
                    }
                    
                    $tailorMaterials = $tailorMaterialsQuery->with(['material', 'tailor'])->get();

                    foreach ($requiredMaterials as $reqMaterial) {
                        $materialId = $reqMaterial['id'];
                        $requiredQty = $reqMaterial['quantity'];
                        
                        // Get total sent to tailor for this material
                        $sentToTailor = 0;
                        $tailorsForMaterial = [];
                        
                        foreach ($tailorMaterials as $tm) {
                            if ($tm->material_id == $materialId) {
                                $tailorName = $tm->tailor ? $tm->tailor->tailor_name : 'Unknown';
                                $sentQty = floatval($tm->quantity ?? 0);
                                $sentToTailor += $sentQty;
                                
                                if (!isset($tailorsForMaterial[$tailorName])) {
                                    $tailorsForMaterial[$tailorName] = 0;
                                }
                                $tailorsForMaterial[$tailorName] += $sentQty;
                            }
                        }
                        
                        // Calculate balance: required - sent
                        $balance = $requiredQty - $sentToTailor;
                        
                        $tailorMaterialsInfo[] = [
                            'material_id' => $materialId,
                            'material_name' => $reqMaterial['name'],
                            'required_quantity' => $requiredQty,
                            'sent_quantity' => $sentToTailor,
                            'balance' => $balance,
                            'unit' => $reqMaterial['unit'],
                            'tailors' => array_map(function($name, $qty) {
                                return ['name' => $name, 'quantity' => $qty];
                            }, array_keys($tailorsForMaterial), $tailorsForMaterial),
                        ];
                    }
                }

                // Determine source based on operation_type and notes
                $source = '-';
                if ($log->operation_type === 'stock_added') {
                    $source = trans('messages.stock_page', [], $locale) ?: 'Stock Page';
                } elseif ($log->operation_type === 'quantity_added') {
                    $source = trans('messages.manage_quantity_popup', [], $locale) ?: 'Manage Quantity Popup';
                } elseif ($log->operation_type === 'special_order_received') {
                    $orderNumber = $log->special_order_number ?? '-';
                    $source = (trans('messages.special_order', [], $locale) ?: 'Special Order') . ' (' . $orderNumber . ')';
                } else {
                    // Fallback to notes if available
                    $source = $log->notes ?? '-';
                }

                return [
                    'id' => $log->id,
                    'stock_code' => $log->stock_code ?? '-',
                    'design_name' => $log->design_name ?? '-',
                    'barcode' => $log->barcode ?? '-',
                    'tailor_name' => $log->tailor_name ?? '-',
                    'source' => $source,
                    'quantity_added' => $totalQuantity,
                    'added_by' => $log->added_by ?? '-',
                    'added_at' => $log->added_at ? $log->added_at->format('Y-m-d H:i:s') : ($log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-'),
                    'operation_type' => $log->operation_type,
                    'special_order_number' => $log->special_order_number,
                    'required_materials' => $requiredMaterials,
                    'material_stock_quantities' => $materialStockQuantities,
                    'tailor_materials' => $tailorMaterialsInfo,
                ];
            })->filter(function($item) {
                return $item !== null;
            });

            // Calculate pagination info
            $lastPage = ceil($totalGroups / $perPage);

            return response()->json([
                'success' => true,
                'data' => $formattedData->values()->toArray(),
                'current_page' => $page,
                'last_page' => $lastPage,
                'total' => $totalGroups,
                'per_page' => $perPage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching material audit data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test website sync - simplified (StockWebsiteSyncService removed)
     */
    public function testWebsiteSync(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Website sync service is not available'
        ], 501);
    }

    /**
     * Sync all pending stocks to website API
     * This method calls the helper function to sync all stocks where website_data_delivery_status = 1
     * Can be called via cronjob (HTTP request) or directly
     * 
     * @return \Illuminate\Http\Response
     */
    public function syncPendingStocks()
    {
        try {
            // Call the helper function
            $results = syncPendingStocksToWebsite();

            return response()->json([
                'success' => true,
                'message' => 'Sync completed',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Error in syncPendingStocks controller method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    
    
    
     public function move_stock_to_system(Request $request)
    {
        $payload = $request->all();

        if (empty($payload) && $request->getContent()) {
            $payload = json_decode($request->getContent(), true) ?? [];
        }

        Log::info('receive_website_orders HIT', [
            'payload' => $payload,
            'headers' => $request->headers->all(),
        ]);

        $item   = json_decode($payload['item'] ?? '{}', true);
        $qty = json_decode($payload['qty'] ?? '[]', true);

         
        
       
    
        // ---------------- BARCODE
        $barcode = !empty($item['barcode']) ? $item['barcode'] : rand(100000000,999999999);
    
        // ---------------- CATEGORY CHECK
        $cat = $this->db->get_where('categories',['id'=>$item['ad_category']])->row();
    
        $category_id = $cat ? $item['ad_category'] : NULL;
    
        // ---------------- DELETE OLD IF EXISTS (because same ID)
        $this->db->where('id',$item['ad_id'])->delete('stocks');
    
        // ---------------- STOCK ARRAY
        $insert_array = [
            'id'        => $item['ad_id'],        // MANUAL ID
            'stock_code'=> $item['store_code'],
            'barcode'   => $barcode,
            'category_id'=> $category_id,
            'sales_price'=> $item['ad_price'],
            'design_name'=> $item['ad_title'],
            'stock_notes'=> $item['ad_description'],
            'notification_limit'=>5,
            'website_data_delivery_status'=>2,
        ];
    
        $this->db->insert('stocks',$insert_array);
        $stock_id = $item['ad_id'];
        
        
        $from_url = 'https://duo-fashion.com/cover_images/';

        $destination = public_path('images/stock_images/');
        
        if(!empty($item['ad_cover'])) 
        {
            $imageName = $item['ad_cover'];
        
            $source = $from_url . $imageName;
            $target = $destination . $imageName;
        
            // download image from URL and save locally
            file_put_contents($target, file_get_contents($source));
        
            $insert_array_img = [
                'stock_id'   => $stock_id,
                'image_path' => 'images/stock_images/'.$imageName, // store relative path
            ];
        
            $this->db->insert('stock_images',$insert_array_img); 
        }

    
        
    
        // ---------------- QTY INSERT
        if(!empty($qty) && is_array($qty)){
    
            foreach($qty as $q){
    
                $q['stock_id']=$stock_id;
                unset($q['product_id']);
                unset($q['uid']);
    
                $this->db->insert('color_sizes',$q);
            }
        }
    
        
        
         return response()->json([
            'status'       => 'success',
            'message'      => 'Website order processed successfully',
            'payload'     => $payload,
            
        ]);
    }

	


}
