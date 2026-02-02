<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Stock;
use App\Models\Tailor;
use App\Models\Customer;
use App\Models\SpecialOrder;
use App\Models\Settings;
use App\Models\Account;
use App\Models\Color;
use App\Models\Size;
use App\Models\ColorSize;
use App\Models\StockAuditLog;
use App\Models\MaterialAuditLog;
use App\Models\MaterialQuantityAudit;
use App\Models\AbayaMaterial;
use App\Models\Material;
use App\Models\TailorMaterial;
use Illuminate\Http\Request;
use App\Models\SpecialOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SpecialOrderController extends Controller
{
       public function index(){
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(5, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $stock = Stock::select('id', 'abaya_code as code', 'design_name as name', 'sales_price')->get();
        $colors = Color::all();
        $sizes = Size::all();

        return view ('special_orders.special_order', compact('stock', 'colors', 'sizes'));

    }

  public function show_specialorder()
{
    $sno = 0;

    $view_authspecialorder = SpecialOrder::with('items')->get(); // eager load items
    $json = [];

    if ($view_authspecialorder->count() > 0) {
        foreach ($view_authspecialorder as $order) {

            // Format created date
            $add_data = Carbon::parse($order->created_at)->format('d-m-Y (h:i a)');

            // Modal actions
            $modal = '
            <a href="javascript:void(0);" class="me-3 edit-staff" data-bs-toggle="modal" data-bs-target="#add_specialorder_modal" onclick=edit("'.$order->id.'")>
                <i class="fa fa-pencil fs-18 text-success"></i>
            </a>
            <a href="javascript:void(0);" onclick=del("'.$order->id.'")>
                <i class="fa fa-trash fs-18 text-danger"></i>
            </a>';

            // Increment serial number
            $sno++;

            // Loop through items and combine their info into a string
            $items_info = '';
            foreach ($order->items as $item) {
                $items_info .= '<div class="mb-1">';
                $items_info .= '<strong>'.$item->item_name.'</strong>';
                $items_info .= ' | Abaya Length: '.$item->abaya_length;
                $items_info .= ' | Bust: '.$item->bust;
                $items_info .= ' | Sleeves: '.$item->sleeves_length;
                $items_info .= ' | Buttons: '.($item->buttons ? 'Yes' : 'No');
                $items_info .= ' | Notes: '.$item->notes;
                $items_info .= '</div>';
            }

            $json[] = [
                '<span class="patient-info ps-0">'. $sno . '</span>',
                '<span class="text-nowrap ms-2">' . $order->customer_name . '</span>',
                '<span >' . $order->source . '</span>',
                '<span >' . ($order->send_as_gift ? 'Yes' : 'No') . '</span>',
                '<span >' . $order->gift_text . '</span>',
                '<span >' . $order->notes . '</span>',
                '<span >' . $order->added_by . '</span>',
                '<span >' . $add_data . '</span>',
                '<span>' . $items_info . '</span>',
                $modal
            ];
        }

        $response = [
            'success' => true,
            'aaData' => $json,
        ];
        echo json_encode($response);

    } else {
        $response = [
            'sEcho' => 0,
            'iTotalRecords' => 0,
            'iTotalDisplayRecords' => 0,
            'aaData' => [],
        ];
        echo json_encode($response);
    }
}


 public function add_specialorder(Request $request)
{
    try {
        DB::beginTransaction();

    $user_id = Auth::id();
    $user = User::find($user_id);
        $user_name = $user->user_name ?? 'System';

        // Log the incoming request for debugging
        \Log::info('Special Order Request:', [
            'customer' => $request->input('customer'),
            'orders_count' => count($request->input('orders', [])),
            'orders' => $request->input('orders'),
            'order_type' => $request->input('order_type', 'customer'),
        ]);

        // Check if this is a stock special order
        $isStockOrder = $request->input('order_type') === 'stock';

        if ($isStockOrder) {
            // Validate stock order fields
            $request->validate([
                'orders' => 'required|array|min:1',
                'orders.*.stock_id' => 'required|exists:stocks,id',
                'orders.*.color_id' => 'required|exists:colors,id',
                'orders.*.size_id' => 'required|exists:sizes,id',
                'orders.*.quantity' => 'required|integer|min:1',
                'orders.*.price' => 'required|numeric|min:0',
            ]);
        } else {
            // Validate customer order fields
            $request->validate([
                'customer.name' => 'required|string|max:255',
                'customer.phone' => 'required|string|max:20',
                'customer.source' => 'required|string|in:whatsapp,walkin',
                'customer.area_id' => 'required|exists:areas,id',
                'customer.city_id' => 'required|exists:cities,id',
                'customer.address' => 'required|string',
                'orders' => 'required|array|min:1',
                'orders.*.stock_id' => 'nullable|exists:stocks,id',
                'orders.*.quantity' => 'required|integer|min:1',
                'orders.*.price' => 'required|numeric|min:0',
            ]);
        }

        $customer = null;
        if (!$isStockOrder) {
            // Create or find customer
            $phone = $request->input('customer.phone');
            $areaId = $request->input('customer.area_id'); // Governorate ID
            $cityId = $request->input('customer.city_id'); // State/Area ID
            $address = $request->input('customer.address');
            
            if (!empty($phone)) {
                // If phone exists, find or create by phone
                $customer = Customer::firstOrCreate(
                    ['phone' => $phone],
                    [
                        'name' => $request->input('customer.name'),
                        'area_id' => $areaId,
                        'city_id' => $cityId,
                        'address' => $address,
                    ]
                );

                // Update customer if phone exists but data changed
                if ($customer->wasRecentlyCreated === false) {
                    $customer->name = $request->input('customer.name');
                    $customer->area_id = $areaId;
                    $customer->city_id = $cityId;
                    $customer->address = $address;
                    $customer->save();
                }
            } else {
                // If no phone, create new customer
                $customer = new Customer();
                $customer->name = $request->input('customer.name');
                $customer->phone = null;
                $customer->area_id = $areaId;
                $customer->city_id = $cityId;
                $customer->address = $address;
                $customer->save();
            }
        }

        // Calculate total amount (0 for stock orders)
        $totalAmount = 0;
        if (!$isStockOrder) {
            $totalAmount = $request->input('shipping_fee', 0);
            foreach ($request->input('orders', []) as $orderData) {
                $totalAmount += ($orderData['price'] ?? 0) * ($orderData['quantity'] ?? 1);
            }
        }

        // Create special order
        $specialOrder = new SpecialOrder();
        if ($isStockOrder) {
            // Generate special order number for stock orders (st- prefix)
            $specialOrder->special_order_no = $this->generateSpecialOrderNo(true);
            $specialOrder->source = 'stock';
            $specialOrder->customer_id = null;
            $specialOrder->send_as_gift = false;
            $specialOrder->gift_text = null;
            $specialOrder->shipping_fee = 0;
            $specialOrder->total_amount = 0;
            $specialOrder->paid_amount = 0;
        } else {
            // Generate special order number for customer orders (sc- prefix)
            $specialOrder->special_order_no = $this->generateSpecialOrderNo(false);
            $specialOrder->source = $request->input('customer.source');
            $specialOrder->customer_id = $customer->id;
            $specialOrder->send_as_gift = $request->input('customer.is_gift') === 'yes' ? true : false;
            $specialOrder->gift_text = $request->input('customer.gift_message');
            $specialOrder->shipping_fee = $request->input('shipping_fee', 0);
            $specialOrder->total_amount = $totalAmount;
            $specialOrder->paid_amount = 0;
        }
        $specialOrder->status = 'new';
        $specialOrder->notes = $request->input('notes');
        $specialOrder->user_id = 1;
        $specialOrder->added_by = 'system_add';
        $specialOrder->save();

        // Save order items
        foreach ($request->input('orders', []) as $orderData) {
            $item = new SpecialOrderItem();
            $item->special_order_id = $specialOrder->id;
            $item->stock_id = $orderData['stock_id'] ?? null;
            $item->abaya_code = $orderData['abaya_code'] ?? null;
            $item->design_name = $orderData['design_name'] ?? null;
            $item->quantity = $orderData['quantity'] ?? 1;
            $item->price = $orderData['price'] ?? 0;
            
            // For stock orders, save color and size
            if ($isStockOrder) {
                $item->color_id = $orderData['color_id'] ?? null;
                $item->size_id = $orderData['size_id'] ?? null;
            }
            
            // For customer orders, save measurements
            if (!$isStockOrder) {
                $item->abaya_length = $orderData['length'] ?? null;
                $item->bust = $orderData['bust'] ?? null;
                $item->sleeves_length = $orderData['sleeves'] ?? null;
                $item->buttons = ($orderData['buttons'] ?? 'yes') === 'yes' ? true : false;
            }
            
            $item->notes = $orderData['notes'] ?? null;
            $item->save();
        }

        DB::commit();
        
        // Refresh the special order to ensure items are loaded
        $specialOrder->refresh();
        $specialOrder->load('items');
        
        // Only send SMS for customer orders (not stock orders)
        if (!$isStockOrder && $customer) {
            // Get customer contact (phone) for SMS
            $customerContact = $customer->phone ?? null;
            
            // Prepare SMS parameters for Special Order
            $smsParams = [
                'sms_status' => 2, // 2 is for Special Order
                'special_order_id' => $specialOrder->id,
                'contact' => $customerContact, // Customer phone number for sending SMS
            ];

            $smsContent = \get_sms($smsParams);
            
            // Send SMS if contact is available and content is generated
            if (!empty($customerContact) && !empty($smsContent)) {
                \sms_module($customerContact, $smsContent);
            }
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.order_saved_successfully', [], session('locale')),
            'special_order_id' => $specialOrder->id
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error saving order: ' . $e->getMessage()
        ], 500);
    }
}


  public function edit_specialorder(Request $request)
{
    $specialorder_id = $request->input('id');
    $specialorder = SpecialOrder::with('items')->find($specialorder_id);

    if (!$specialorder) {
        return response()->json(['error' => 'Special order not found'], 404);
    }

    $data = [
        'specialorder_id' => $specialorder->id,
        'source' => $specialorder->source,
        'name' => $specialorder->customer_name,
        'contact' => $specialorder->contact,
        'city' => $specialorder->city,
        'area' => $specialorder->area,
        'send_as_gift' => $specialorder->send_as_gift,
        'gift_text' => $specialorder->gift_text,
        'notes' => $specialorder->notes,
        'items' => $specialorder->items, // Array of dresses
    ];

    return response()->json($data);
}


public function update_specialorder(Request $request)
{
    $user_id = Auth::id();
    $user_name = Auth::user()->user_name;
    $specialorder_id = $request->input('specialorder_id');

    $specialorder = SpecialOrder::find($specialorder_id);
    if (!$specialorder) {
        return response()->json(['error' => 'Special order not found'], 404);
    }

    $previousData = $specialorder->toArray();
    $specialorder->source = $request['source'];
    $specialorder->customer_name = $request['name'];
    $specialorder->contact = $request['contact'];
    $specialorder->city = $request['city'];
    $specialorder->area = $request['area'];
    $specialorder->send_as_gift = $request['send_as_gift'] ?? 0;
    $specialorder->gift_text = $request['gift_text'] ?? null;
    $specialorder->notes = $request['notes'] ?? null;
    $specialorder->added_by = 'system_update';
    $specialorder->user_id =  1;
    $specialorder->save();

    // Delete old items and insert new ones
    $specialorder->items()->delete();
    foreach ($request->items as $item) {
        $specialorder_item = new Stock();
        $specialorder_item->special_order_id = $specialorder->id;
        $specialorder_item->item_name = $item['item_name'];
        $specialorder_item->abaya_length = $item['abaya_length'] ?? null;
        $specialorder_item->bust = $item['bust'] ?? null;
        $specialorder_item->sleeves_length = $item['sleeves_length'] ?? null;
        $specialorder_item->buttons = $item['buttons'] ?? 0;
        $specialorder_item->notes = $item['notes'] ?? null;
        $specialorder_item->save();
    }

    // You can add history logging here following your previous pattern

    return response()->json(['message' => 'Special order updated successfully']);
}



public function delete_specialorder(Request $request)
{
    $user_id = Auth::id();
    $user_name = Auth::user()->user_name;
    $specialorder_id = $request->input('id');

    $specialorder = SpecialOrder::find($specialorder_id);
    if (!$specialorder) {
        return response()->json(['error' => 'Special order not found'], 404);
    }

    // Delete related items first
    $specialorder->items()->delete();

    // Add history logging here if needed

    $specialorder->delete();

    return response()->json(['message' => 'Special order deleted successfully']);
}


public function searchAbayas(Request $request)
{
    $search = $request->input('search', '');
    
    $query = Stock::with(['images']);
    
    if (!empty($search)) {
        $query->where(function($q) use ($search) {
            $q->where('abaya_code', 'LIKE', '%' . $search . '%')
              ->orWhere('design_name', 'LIKE', '%' . $search . '%')
              ->orWhere('barcode', 'LIKE', '%' . $search . '%');
        });
    }
    
    $stocks = $query->limit(20)->get();
    
    $abayas = $stocks->map(function($stock) {
        return [
            'id' => $stock->id,
            'code' => $stock->abaya_code,
            'name' => $stock->design_name ?? $stock->abaya_code,
            'price' => $stock->sales_price ?? 0,
            'image' => $stock->images->first() ? $stock->images->first()->image_path : '/images/placeholder.png'
        ];
    });
    
    return response()->json($abayas);
}

public function view_special_order()
{
    if (!Auth::check()) {
        return redirect()->route('login_page')->with('error', 'Please login first');
    }

    $permissions = Auth::user()->permissions ?? [];

    if (!in_array(5, $permissions)) {
        return redirect()->route('login_page')->with('error', 'Permission denied');
    }

    return view('special_orders.view_special_order');
}

public function getOrdersList(Request $request)
{
    try {
        $orders = SpecialOrder::with(['customer.area', 'customer.city', 'items.stock.images', 'items.tailor'])
            ->orderBy('created_at', 'DESC')
            ->get();

        $formattedOrders = $orders->map(function($order) {
            // Get first item image or placeholder (for list view)
            $firstItem = $order->items->first();
            $image = '/images/placeholder.png';
            if ($firstItem && $firstItem->stock && $firstItem->stock->images->first()) {
                $image = $firstItem->stock->images->first()->image_path;
            }

            // Get all items with their details
            $items = $order->items->map(function($item) {
                $itemImage = '/images/placeholder.png';
                if ($item->stock && $item->stock->images->first()) {
                    $itemImage = $item->stock->images->first()->image_path;
                }

                // Get original tailor from stock
                $originalTailor = '';
                $originalTailorName = '';
                if ($item->stock && $item->stock->tailor_id) {
                    $tailorIds = json_decode($item->stock->tailor_id, true);
                    if (!is_array($tailorIds)) {
                        $tailorIds = [$tailorIds];
                    }
                    if (!empty($tailorIds)) {
                        $tailors = \App\Models\Tailor::whereIn('id', $tailorIds)->pluck('tailor_name')->toArray();
                        $originalTailorName = implode(', ', $tailors);
                        $originalTailor = $item->stock->tailor_id;
                    }
                }

                // Get current tailor (if changed when sending to tailor)
                $currentTailorName = $item->tailor ? $item->tailor->tailor_name : null;

                // Get color and size info for stock orders
                $colorName = '';
                $sizeName = '';
                if ($item->color_id) {
                    $color = \App\Models\Color::find($item->color_id);
                    if ($color) {
                        $locale = session('locale', 'en');
                        $colorName = $locale === 'ar' ? ($color->color_name_ar ?? $color->color_name_en) : ($color->color_name_en ?? $color->color_name_ar);
                    }
                }
                if ($item->size_id) {
                    $size = \App\Models\Size::find($item->size_id);
                    if ($size) {
                        $locale = session('locale', 'en');
                        $sizeName = $locale === 'ar' ? ($size->size_name_ar ?? $size->size_name_en) : ($size->size_name_en ?? $size->size_name_ar);
                    }
                }

                return [
                    'id' => $item->id,
                    'abaya_code' => $item->abaya_code ?? 'N/A',
                    'design_name' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                    'quantity' => $item->quantity ?? 1,
                    'price' => floatval($item->price ?? 0),
                    'image' => $itemImage,
                    'length' => $item->abaya_length,
                    'bust' => $item->bust,
                    'sleeves' => $item->sleeves_length,
                    'buttons' => $item->buttons ?? false,
                    'notes' => $item->notes ?? '',
                    'tailor_status' => $item->tailor_status ?? 'new',
                    'tailor_id' => $item->tailor_id,
                    'tailor_name' => $currentTailorName,
                    'original_tailor' => $originalTailor,
                    'original_tailor_name' => $originalTailorName,
                    'color_id' => $item->color_id,
                    'color_name' => $colorName,
                    'size_id' => $item->size_id,
                    'size_name' => $sizeName,
                ];
            });

            // Get tailor info (if available from items notes or order notes)
            $tailor = 'N/A';
            if ($firstItem && $firstItem->notes) {
                $tailor = $firstItem->notes;
            } elseif ($order->notes) {
                $tailor = $order->notes;
            }

            $customer = $order->customer;
            $isStockOrder = $order->customer_id === null || $order->source === 'stock';
            
            // Get governorate from area relationship or fallback to direct field
            $governorate = '';
            if (!$isStockOrder && $customer && $customer->area) {
                // Use locale to get the correct language version
                $locale = session('locale', 'en');
                if ($locale === 'ar') {
                    $governorate = $customer->area->area_name_ar ?? $customer->area->area_name_en ?? '';
                } else {
                    $governorate = $customer->area->area_name_en ?? $customer->area->area_name_ar ?? '';
                }
            } elseif (!$isStockOrder && $customer && isset($customer->governorate)) {
                $governorate = $customer->governorate;
            }
            
            // Get city/state from city relationship or fallback to direct field
            $city = '';
            if (!$isStockOrder && $customer && $customer->city) {
                // Use locale to get the correct language version
                $locale = session('locale', 'en');
                if ($locale === 'ar') {
                    $city = $customer->city->city_name_ar ?? $customer->city->city_name_en ?? '';
                } else {
                    $city = $customer->city->city_name_en ?? $customer->city->city_name_ar ?? '';
                }
            } elseif (!$isStockOrder && $customer && isset($customer->area)) {
                $city = $customer->area; // Fallback for old data
            }
            
            $location = trim($governorate . ($city ? ' - ' . $city : ''));

            // Calculate and update order status based on items' tailor_status
            $this->updateOrderStatusBasedOnItems($order);
            $calculatedStatus = $order->status;

            // Use special_order_no if available, otherwise fallback to generated number
            $orderNumber = $order->special_order_no;
            if (!$orderNumber) {
                // Fallback: Generate order number: YYYY-00ID (e.g., 2025-0001)
                $orderDate = Carbon::parse($order->created_at);
                $orderNumber = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
            }

            // For stock orders, always set total, paid, and remaining to 0
            $total = $isStockOrder ? 0 : floatval($order->total_amount ?? 0);
            $paid = $isStockOrder ? 0 : floatval($order->paid_amount ?? 0);
            
            return [
                'id' => $order->id,
                'order_no' => $orderNumber,
                'special_order_no' => $order->special_order_no,
                'customer' => $isStockOrder ? trans('messages.stock_special_order', [], session('locale')) : (optional($customer)->name ?? 'N/A'),
                'governorate' => $governorate,
                'city' => $city,
                'location' => $location,
                'date' => $order->created_at->format('Y-m-d'),
                'status' => $calculatedStatus,
                'source' => $order->source,
                'total' => $total,
                'paid' => $paid,
                'image' => $image, // First item image for list view
                'items' => $items, // All items array
                'tailor' => $tailor,
                'notes' => $order->notes ?? '',
                'is_stock_order' => $isStockOrder,
            ];
        });

        return response()->json([
            'success' => true,
            'orders' => $formattedOrders
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in getOrdersList: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching orders: ' . $e->getMessage(),
            'orders' => []
        ], 500);
    }
}

public function recordPayment(Request $request)
{
    try {
        $orderId = $request->input('order_id');
        $amount = $request->input('amount');
        $accountId = $request->input('account_id');

        $order = SpecialOrder::findOrFail($orderId);
        
        // Check if this is a stock order - stock orders don't accept payments
        $isStockOrder = $order->customer_id === null || $order->source === 'stock';
        if ($isStockOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Stock special orders do not require payment'
            ], 422);
        }
        
        $newPaidAmount = $order->paid_amount + $amount;
        
        // Validate amount doesn't exceed total
        if ($newPaidAmount > $order->total_amount + 0.001) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount exceeds total amount'
            ], 422);
        }

        // Validate account_id is provided
        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'Account is required'
            ], 422);
        }

        $order->paid_amount = $newPaidAmount;
        $order->account_id = $accountId;
        
        // If fully paid and not delivered, set status to ready
        if (abs($order->total_amount - $newPaidAmount) < 0.001 && $order->status !== 'delivered') {
            $order->status = 'ready';
        }
        
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'order' => [
                'id' => $order->id,
                'paid' => floatval($order->paid_amount),
                'status' => $order->status
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error recording payment: ' . $e->getMessage()
        ], 500);
    }
}

public function updateDeliveryStatus(Request $request)
{
    try {
        $orderIds = $request->input('order_ids', []);
        $addToStock = $request->input('add_to_stock', false); // Confirmation flag for stock orders
        
        if (empty($orderIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No orders selected'
            ], 422);
        }

        DB::beginTransaction();
        
        $orders = SpecialOrder::whereIn('id', $orderIds)
            ->where('status', 'ready')
            ->with('items')
            ->get();
        
        $updated = 0;
        foreach ($orders as $order) {
            $isStockOrder = $order->customer_id === null || $order->source === 'stock';
            
            // For stock orders, add to inventory if confirmed
            if ($isStockOrder && $addToStock) {
                $this->addStockOrderItemsToInventory($order);
            }
            
            $order->status = 'delivered';
            $order->save();
            $updated++;

            // Send SMS to customer: "You have received the order" with order details (customer orders only)
            if (!$isStockOrder) {
                try {
                    $this->sendSpecialOrderSms($order->id, 8);
                } catch (\Exception $e) {
                    \Log::error('Error sending special order delivered SMS: ' . $e->getMessage());
                }
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "{$updated} order(s) marked as delivered",
            'updated_count' => $updated
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error updating delivery status: ' . $e->getMessage()
        ], 500);
    }
}

public function deleteOrder(Request $request)
{
    try {
        $orderId = $request->input('order_id');
        
        $order = SpecialOrder::findOrFail($orderId);
        
        // Delete related items first
        $order->items()->delete();
        
        // Delete the order
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error deleting order: ' . $e->getMessage()
        ], 500);
    }
}


public function send_request()
{
    if (!Auth::check()) {
        return redirect()->route('login_page')->with('error', 'Please login first');
    }

    $permissions = Auth::user()->permissions ?? [];

    if (!in_array(7, $permissions)) {
        return redirect()->route('login_page')->with('error', 'Permission denied');
    }

    return view('special_orders.send_request');
}

public function getTailorAssignmentsData(Request $request)
{
    try {
        // Get all tailors
        $tailors = Tailor::select('id', 'tailor_name as name')->get();

        // Get new items (not yet assigned to tailor)
        $newItems = SpecialOrderItem::with(['specialOrder.customer', 'stock.images'])
            ->where(function($query) {
                $query->whereNull('tailor_id')
                      ->orWhere('tailor_status', 'new');
            })
            ->get()
            ->map(function($item) {
                $order = $item->specialOrder;
                $stock = $item->stock;
                $image = optional(optional($stock)->images->first())->image_path ?? '/images/placeholder.png';
                
                if ($stock && $stock->images->first()) {
                    $image = $stock->images->first()->image_path;
                }

                // Get original tailor from stock if exists
        $originalTailor = '';
        $originalTailorId = null;

        if ($stock && $stock->tailor_id) {

            // Convert JSON string to PHP array
            $tailorIds = json_decode($stock->tailor_id, true);

            // Ensure always an array
            if (!is_array($tailorIds)) {
                $tailorIds = [$tailorIds];
            }

            // Get first tailor ID for auto-assignment
            if (!empty($tailorIds)) {
                $originalTailorId = is_numeric($tailorIds[0]) ? (int)$tailorIds[0] : null;
            }

    // Fetch all tailors
    $tailors = Tailor::whereIn('id', $tailorIds)->pluck('tailor_name')->toArray();

    // Join names into a single string
    $originalTailor = implode(', ', $tailors);
}
                
                // Use special_order_no from database, fallback to generated format
                $orderNo = '—';
                if ($order) {
                    if ($order->special_order_no) {
                        $orderNo = $order->special_order_no;
                    } else {
                        // Fallback to generated format: YYYY-00ID
                        $orderDate = Carbon::parse($order->created_at);
                        $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                    }
                }
                
                // Check if stock order
                $isStockOrder = $order && ($order->customer_id === null || $order->source === 'stock');
                
                // Get color and size info for stock orders
                $colorName = '';
                $sizeName = '';
                if ($item->color_id) {
                    $color = \App\Models\Color::find($item->color_id);
                    if ($color) {
                        $locale = session('locale', 'en');
                        $colorName = $locale === 'ar' ? ($color->color_name_ar ?? $color->color_name_en) : ($color->color_name_en ?? $color->color_name_ar);
                    }
                }
                if ($item->size_id) {
                    $size = \App\Models\Size::find($item->size_id);
                    if ($size) {
                        $locale = session('locale', 'en');
                        $sizeName = $locale === 'ar' ? ($size->size_name_ar ?? $size->size_name_en) : ($size->size_name_en ?? $size->size_name_ar);
                    }
                }
                
                return [
                    'rowId' => $item->id,
                    'orderId' => $order->id ?? 0,
                    'order_no' => $orderNo,
                    'source' => $order->source ?? '',
                    'customer' => $isStockOrder ? trans('messages.stock_special_order', [], session('locale')) : ($order->customer->name ?? 'N/A'),
                    'code' => $item->abaya_code ?? '',
                    'abayaName' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                    'image' => $image,
                    'quantity' => $item->quantity ?? 1,
                    'length' => $item->abaya_length,
                    'bust' => $item->bust,
                    'sleeves' => $item->sleeves_length,
                    'buttons' => $item->buttons ?? true,
                    'notes' => $item->notes ?? '',
                    'date' => $order->created_at->format('Y-m-d'),
                    'originalTailor' => $originalTailor,
                    'originalTailorId' => $originalTailorId,
                    'tailor_id' => null,
                    'tailor' => '',
                    'status' => 'new',
                    'color_id' => $item->color_id,
                    'color_name' => $colorName,
                    'size_id' => $item->size_id,
                    'size_name' => $sizeName,
                    'is_stock_order' => $isStockOrder,
                ];
            });

        // Get processing items (assigned to tailor but not received)
        $processingItems = SpecialOrderItem::with(['specialOrder.customer', 'stock.images', 'tailor'])
            ->where('tailor_status', 'processing')
            ->get()
            ->map(function($item) {
                $order = $item->specialOrder;
                $stock = $item->stock;
                $image = '/images/placeholder.png';
                
                if ($stock && $stock->images->first()) {
                    $image = $stock->images->first()->image_path;
                }

                // Get original tailor from stock if exists
                $originalTailor = '';
                $originalTailorId = null;
                if ($stock && $stock->tailor_id) {
                    // Convert JSON string to PHP array
                    $tailorIds = json_decode($stock->tailor_id, true);

                    // Ensure always an array
                    if (!is_array($tailorIds)) {
                        $tailorIds = [$tailorIds];
                    }

                    // Get first tailor ID for reference
                    if (!empty($tailorIds)) {
                        $originalTailorId = is_numeric($tailorIds[0]) ? (int)$tailorIds[0] : null;
                    }

                    // Fetch all tailors
                    $tailors = Tailor::whereIn('id', $tailorIds)->pluck('tailor_name')->toArray();

                    // Join names into a single string
                    $originalTailor = implode(', ', $tailors);
                }

                // Get tailor_order_no and special_order_no separately
                $tailorOrderNo = $item->tailor_order_no ?? '—';
                
                // Get special_order_no
                $specialOrderNo = '—';
                if ($order) {
                    if ($order->special_order_no) {
                        $specialOrderNo = $order->special_order_no;
                    } else {
                        // Fallback to generated format: YYYY-00ID
                        $orderDate = Carbon::parse($order->created_at);
                        $specialOrderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                    }
                }
                
                // Use tailor_order_no as primary order_no for display compatibility
                $orderNo = $tailorOrderNo !== '—' ? $tailorOrderNo : $specialOrderNo;

                // Check if stock order
                $isStockOrder = $order && ($order->customer_id === null || $order->source === 'stock');
                
                // Get color and size info for stock orders
                $colorName = '';
                $sizeName = '';
                if ($item->color_id) {
                    $color = \App\Models\Color::find($item->color_id);
                    if ($color) {
                        $locale = session('locale', 'en');
                        $colorName = $locale === 'ar' ? ($color->color_name_ar ?? $color->color_name_en) : ($color->color_name_en ?? $color->color_name_ar);
                    }
                }
                if ($item->size_id) {
                    $size = \App\Models\Size::find($item->size_id);
                    if ($size) {
                        $locale = session('locale', 'en');
                        $sizeName = $locale === 'ar' ? ($size->size_name_ar ?? $size->size_name_en) : ($size->size_name_en ?? $size->size_name_ar);
                    }
                }

                return [
                    'rowId' => $item->id,
                    'orderId' => $order->id ?? 0,
                    'order_no' => $orderNo,
                    'source' => $order->source ?? '',
                    'customer' => $isStockOrder ? trans('messages.stock_special_order', [], session('locale')) : ($order->customer->name ?? 'N/A'),
                    'code' => $item->abaya_code ?? '',
                    'abayaName' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                    'image' => $image,
                    'quantity' => $item->quantity ?? 1,
                    'length' => $item->abaya_length,
                    'bust' => $item->bust,
                    'sleeves' => $item->sleeves_length,
                    'buttons' => $item->buttons ?? true,
                    'notes' => $item->notes ?? '',
                   'date' => $item->sent_to_tailor_at
    ? \Carbon\Carbon::parse($item->sent_to_tailor_at)->format('Y-m-d')
    : \Carbon\Carbon::parse($order->created_at)->format('Y-m-d'),
                    'originalTailor' => $originalTailor,
                    'originalTailorId' => $originalTailorId,
                    'tailor_id' => $item->tailor_id,
                    'tailor' => $item->tailor ? $item->tailor->tailor_name : '',
                    'tailor_name' => $item->tailor ? $item->tailor->tailor_name : '',
                    'status' => 'processing',
                    'color_id' => $item->color_id,
                    'color_name' => $colorName,
                    'size_id' => $item->size_id,
                    'size_name' => $sizeName,
                    'is_stock_order' => $isStockOrder,
                    'tailor_order_no' => $tailorOrderNo,
                    'special_order_no' => $specialOrderNo,
                ];
            });

        return response()->json([
            'success' => true,
            'tailors' => $tailors,
            'new' => $newItems,
            'processing' => $processingItems,
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in getTailorAssignmentsData: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching data: ' . $e->getMessage(),
            'tailors' => [],
            'new' => [],
            'processing' => [],
        ], 500);
    }
}

    /**
     * Generate tailor order number
     * Format: T0-YYMMDD-001 (e.g., T0-260113-001)
     */
    private function generateTailorOrderNo()
    {
        $now = Carbon::now();
        $year = $now->format('y'); // 2-digit year
        $month = $now->format('m'); // 2-digit month
        $day = $now->format('d'); // 2-digit day
        $datePrefix = $year . $month . $day;
        
        // Find the highest sequence number for today
        $lastOrder = SpecialOrderItem::where('tailor_order_no', 'like', 'T0-' . $datePrefix . '-%')
            ->orderBy('tailor_order_no', 'desc')
            ->first();
        
        $sequence = 1;
        if ($lastOrder && $lastOrder->tailor_order_no) {
            // Extract sequence number from last order (format: T0-YYMMDD-001)
            $parts = explode('-', $lastOrder->tailor_order_no);
            if (count($parts) === 3 && isset($parts[2])) {
                $sequence = (int)$parts[2] + 1;
            }
        }
        
        // Format sequence as 3-digit number
        $sequenceFormatted = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        
        return 'T0-' . $datePrefix . '-' . $sequenceFormatted;
    }

    /**
     * Generate special order number
     * Format for customer orders: sc-YYMMDD-0001 (e.g., sc-260113-0001)
     * Format for stock orders: st-YYMMDD-0001 (e.g., st-260113-0001)
     */
    private function generateSpecialOrderNo($isStockOrder = false)
    {
        $now = Carbon::now();
        $year = $now->format('y'); // 2-digit year
        $month = $now->format('m'); // 2-digit month
        $day = $now->format('d'); // 2-digit day
        $datePrefix = $year . $month . $day;
        
        // Use 'sc' for customer orders, 'st' for stock orders
        $prefix = $isStockOrder ? 'st' : 'sc';
        
        // Find the highest sequence number for today with the same prefix
        $lastOrder = SpecialOrder::where('special_order_no', 'like', $prefix . '-' . $datePrefix . '-%')
            ->orderBy('special_order_no', 'desc')
            ->first();
        
        $sequence = 1;
        if ($lastOrder && $lastOrder->special_order_no) {
            // Extract sequence number from last order (format: sc-YYMMDD-0001 or st-YYMMDD-0001)
            $parts = explode('-', $lastOrder->special_order_no);
            if (count($parts) === 3 && isset($parts[2])) {
                $sequence = (int)$parts[2] + 1;
            }
        }
        
        // Format sequence as 4-digit number
        $sequenceFormatted = str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        return $prefix . '-' . $datePrefix . '-' . $sequenceFormatted;
    }

    /**
     * Update order status based on items' tailor_status
     */
    private function updateOrderStatusBasedOnItems($order)
    {
        if ($order->status === 'delivered') {
            return; // Don't change delivered orders
        }

        if ($order->items->count() === 0) {
            $order->status = 'new';
            $order->save();
            return;
        }

        $itemStatuses = $order->items->pluck('tailor_status')->filter()->toArray();
        
        if (empty($itemStatuses)) {
            $order->status = 'new';
        } else {
            $allNew = count(array_filter($itemStatuses, function($status) {
                return $status === 'new';
            })) === count($itemStatuses);
            
            $allReceived = count(array_filter($itemStatuses, function($status) {
                return $status === 'received';
            })) === count($itemStatuses);
            
            $hasReceived = count(array_filter($itemStatuses, function($status) {
                return $status === 'received';
            })) > 0;
            
            $hasProcessing = count(array_filter($itemStatuses, function($status) {
                return $status === 'processing';
            })) > 0;
            
            $isStockOrder = $order->customer_id === null || $order->source === 'stock';
            
            if ($allNew) {
                $order->status = 'new';
            } elseif ($allReceived) {
                // For stock orders, set status to saved_in_stock (stock already added in markTailorItemsReceived)
                // For customer orders, set status to ready
                if ($isStockOrder) {
                    $order->status = 'saved_in_stock';
                } else {
                    $order->status = 'ready';
                }
            } elseif ($hasReceived && ($hasProcessing || count(array_filter($itemStatuses, function($status) {
                return $status === 'new';
            })) > 0)) {
                // Some items are ready but others are still with tailor or new
                $order->status = 'partially_ready';
            } else {
                $order->status = 'processing';
            }
        }
        
        $order->save();
    }

    /**
     * Send SMS to customer for special order lifecycle (in progress / ready / delivered).
     * Uses get_sms() and sms_module() from helpers. Only sends for customer orders with phone.
     *
     * @param int $orderId Special order ID
     * @param int $smsStatus 6 = in progress (sent to tailor), 7 = ready (received), 8 = delivered
     */
    private function sendSpecialOrderSms($orderId, $smsStatus)
    {
        $order = SpecialOrder::with('customer')->find($orderId);
        if (!$order || $order->customer_id === null || $order->source === 'stock') {
            return;
        }
        $customerContact = $order->customer ? ($order->customer->phone ?? null) : null;
        if (empty($customerContact)) {
            return;
        }
        $smsParams = [
            'sms_status' => $smsStatus,
            'special_order_id' => $order->id,
            'contact' => $customerContact,
        ];
        $smsContent = \get_sms($smsParams);
        if (!empty($smsContent)) {
            \sms_module($customerContact, $smsContent);
        }
    }
    
    /**
     * Add stock order items to color_sizes inventory when order becomes ready
     */
    private function addStockOrderItemsToInventory($order)
    {
        try {
            foreach ($order->items as $item) {
                // Only process items that have stock_id, color_id, and size_id
                if (!$item->stock_id || !$item->color_id || !$item->size_id) {
                    continue;
                }
                
                // Check if color_size entry already exists
                $colorSize = ColorSize::where('stock_id', $item->stock_id)
                    ->where('color_id', $item->color_id)
                    ->where('size_id', $item->size_id)
                    ->first();
                
                $stock = Stock::find($item->stock_id);
                if (!$stock) continue;

                $previousQty = 0;
                if ($colorSize) {
                    // Update existing quantity
                    $previousQty = (int)$colorSize->qty;
                    $colorSize->qty += $item->quantity;
                    $colorSize->save();
                } else {
                    // Create new color_size entry
                    $colorSize = ColorSize::create([
                        'stock_id' => $item->stock_id,
                        'color_id' => $item->color_id,
                        'size_id' => $item->size_id,
                        'qty' => $item->quantity,
                    ]);
                }

                // Log audit entry – use special_order_no when available
                $user = Auth::user();
                $orderNumber = $order->special_order_no ?? (Carbon::parse($order->created_at)->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT));
                
                StockAuditLog::create([
                    'stock_id' => $stock->id,
                    'abaya_code' => $stock->abaya_code,
                    'barcode' => $stock->barcode,
                    'design_name' => $stock->design_name,
                    'operation_type' => 'special_order',
                    'previous_quantity' => $previousQty,
                    'new_quantity' => (int)$colorSize->qty,
                    'quantity_change' => $item->quantity,
                    'related_id' => $orderNumber,
                    'related_type' => 'special_order',
                    'related_info' => ['order_id' => $order->id],
                    'color_id' => $item->color_id,
                    'size_id' => $item->size_id,
                    'user_id' => $user ? $user->id : null,
                    'added_by' => $user ? $user->user_name : 'System',
                    'notes' => 'Added from special order',
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error adding stock order items to inventory: ' . $e->getMessage());
            // Don't throw exception, just log it
        }
    }

public function assignItemsToTailor(Request $request)
{
    try {
        $assignments = $request->input('assignments', []);

        if (empty($assignments)) {
            return response()->json([
                'success' => false,
                'message' => 'No items selected'
            ], 422);
        }

        DB::beginTransaction();

        $orderIds = [];
        $user = Auth::user();
        $userName = $user ? $user->user_name : 'System';
        
        foreach ($assignments as $assignment) {
            $itemId = $assignment['item_id'] ?? null;
            $tailorId = $assignment['tailor_id'] ?? null;

            if (!$itemId || !$tailorId) {
                continue;
            }

            $item = SpecialOrderItem::with('specialOrder')->find($itemId);
            if ($item) {
                // Generate tailor order number if not already set
                if (!$item->tailor_order_no) {
                    $item->tailor_order_no = $this->generateTailorOrderNo();
                }
                $item->tailor_id = $tailorId;
                $item->tailor_status = 'processing';
                $item->sent_to_tailor_at = now();
                $item->save();
                
                // Deduct materials from tailor materials when sending to tailor
                if ($item->stock_id && $item->quantity > 0) {
                    $order = $item->specialOrder;
                    $tailor = Tailor::find($tailorId);
                    $tailorName = $tailor ? $tailor->tailor_name : null;
                    
                    // Use special_order_no from database, fallback to generated number if not available
                    $orderNumber = $order ? ($order->special_order_no ?? (Carbon::parse($order->created_at)->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT))) : null;
                    
                    // Deduct materials from tailor materials (not from main inventory)
                    $this->deductMaterialsFromInventory($item->stock_id, $item->quantity, 'special_order', $tailorId, $tailorName, $order ? $order->id : null, $orderNumber);
                }
                
                if (!in_array($item->special_order_id, $orderIds)) {
                    $orderIds[] = $item->special_order_id;
                }
            }
        }

        // Update order statuses based on items
        foreach ($orderIds as $orderId) {
            $order = SpecialOrder::with('items')->find($orderId);
            if ($order) {
                $this->updateOrderStatusBasedOnItems($order);
            }
        }

        DB::commit();

        // Send SMS to customer: "Your order is in progress" (sent to tailor) — one SMS per affected customer order
        foreach ($orderIds as $orderId) {
            try {
                $this->sendSpecialOrderSms($orderId, 6);
            } catch (\Exception $e) {
                \Log::error('Error sending special order in-progress SMS: ' . $e->getMessage());
            }
        }

        // Check for late deliveries after assignment (don't wait for response)
        try {
            $this->checkAndMarkLateDeliveries();
        } catch (\Exception $e) {
            // Log but don't fail the assignment
            \Log::error('Error checking late deliveries after assignment: ' . $e->getMessage());
        }

        // Get assigned item IDs for Excel export
        $assignedItemIds = [];
        $firstTailorId = null;
        foreach ($assignments as $assignment) {
            if (!empty($assignment['item_id'])) {
                $assignedItemIds[] = $assignment['item_id'];
            }
            // Get the first tailor_id for redirect
            if (!$firstTailorId && !empty($assignment['tailor_id'])) {
                $firstTailorId = $assignment['tailor_id'];
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($assignments) . ' item(s) assigned to tailor successfully',
            'assigned_item_ids' => $assignedItemIds,
            'tailor_id' => $firstTailorId
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error in assignItemsToTailor: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error assigning items: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Export abayas to Excel for tailor assignment
 */
public function exportAbayasToTailorExcel(Request $request)
{
    try {
        // Handle both GET (query) and POST (input) requests
        $itemIds = $request->query('item_ids', $request->input('item_ids', []));
        
        // Ensure item_ids is an array
        if (!is_array($itemIds)) {
            $itemIds = [$itemIds];
        }
        
        // Filter out any empty values
        $itemIds = array_filter($itemIds, function($id) {
            return !empty($id);
        });
        
        if (empty($itemIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No items selected'
            ], 422);
        }

        // Get items with all related data (note: color and size are fetched manually since relationships don't exist)
        $items = SpecialOrderItem::with([
            'specialOrder.customer',
            'stock.images',
            'tailor'
        ])
        ->whereIn('id', $itemIds)
        ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No items found'
            ], 404);
        }

        // Get unique tailors from items for header display
        $uniqueTailorIds = [];
        $uniqueTailors = [];
        foreach ($items as $item) {
            if ($item->tailor && !in_array($item->tailor->id, $uniqueTailorIds)) {
                $uniqueTailorIds[] = $item->tailor->id;
                $uniqueTailors[] = $item->tailor;
            }
        }
        
        // Get total quantity
        $totalQuantity = $items->sum('quantity') ?? $items->count();
        
        // Current date and time
        $currentDate = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');

        // Prepare Excel data with beautiful formatting
        $data = [];
        
        // Header row with styling information (we'll style it in PhpSpreadsheet)
        $headers = [
            trans('messages.order_number', [], session('locale')),
            trans('messages.customer', [], session('locale')),
            trans('messages.abaya', [], session('locale')),
            trans('messages.code', [], session('locale')),
            trans('messages.tailor', [], session('locale')),
            trans('messages.quantity', [], session('locale')),
            trans('messages.color', [], session('locale')),
            trans('messages.size', [], session('locale')),
            trans('messages.abaya_length', [], session('locale')),
            trans('messages.bust_one_side', [], session('locale')),
            trans('messages.sleeves_length', [], session('locale')),
            trans('messages.buttons', [], session('locale')),
            trans('messages.order_date', [], session('locale')),
            trans('messages.notes', [], session('locale'))
        ];
        $data[] = $headers;

        // Data rows
        foreach ($items as $item) {
            $order = $item->specialOrder;
            $stock = $item->stock;
            $tailor = $item->tailor;
            $customer = $order->customer ?? null;
            
            // Generate order number
            $orderNo = '—';
            if ($order) {
                $orderDate = Carbon::parse($order->created_at);
                $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
            }

            // Get color name (manually fetch since relationship doesn't exist)
            $colorName = '';
            if ($item->color_id) {
                $color = Color::find($item->color_id);
                if ($color) {
                    $locale = session('locale', 'en');
                    $colorName = $locale === 'ar' ? ($color->color_name_ar ?? $color->color_name_en) : ($color->color_name_en ?? $color->color_name_ar);
                }
            }

            // Get size name (manually fetch since relationship doesn't exist)
            $sizeName = '';
            if ($item->size_id) {
                $size = Size::find($item->size_id);
                if ($size) {
                    $locale = session('locale', 'en');
                    $sizeName = $locale === 'ar' ? ($size->size_name_ar ?? $size->size_name_en) : ($size->size_name_en ?? $size->size_name_ar);
                }
            }

            // Format date
            $orderDate = $order ? Carbon::parse($order->created_at)->format('Y-m-d') : '—';

            $data[] = [
                $orderNo,
                $customer ? $customer->name : trans('messages.stock_special_order', [], session('locale')),
                $item->design_name ?? $item->abaya_code ?? '—',
                $item->abaya_code ?? '—',
                $tailor ? $tailor->tailor_name : '—',
                $item->quantity ?? 1,
                $colorName ?: '—',
                $sizeName ?: '—',
                $item->abaya_length ?? '—',
                $item->bust ?? '—',
                $item->sleeves_length ?? '—',
                $item->buttons ? trans('messages.yes', [], session('locale')) : trans('messages.no', [], session('locale')),
                $orderDate,
                $item->notes ?? '—'
            ];
        }

        // Use PhpSpreadsheet if available
        if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set sheet title
            $sheet->setTitle('Abayas to Tailor');
            
            // Build tailor names string
            $tailorNames = [];
            foreach ($uniqueTailors as $tailor) {
                if ($tailor && isset($tailor->tailor_name)) {
                    $tailorNames[] = $tailor->tailor_name;
                }
            }
            $tailorNamesStr = !empty($tailorNames) ? implode(', ', $tailorNames) : (trans('messages.not_assigned', [], session('locale')) ?: 'Not Assigned');
            
            // Add professional header section at the top
            // Row 1: Title
            $sheet->setCellValue('A1', trans('messages.abayas_to_send_to_tailor', [], session('locale')) ?: 'Abayas to Send to Tailor');
            $sheet->mergeCells('A1:N1');
            $titleStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 18,
                    'name' => 'Arial'
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'] // Indigo/Purple
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '3B3B98']
                    ]
                ]
            ];
            $sheet->getStyle('A1:N1')->applyFromArray($titleStyle);
            $sheet->getRowDimension(1)->setRowHeight(35);
            
            // Row 2: Tailor Name
            $sheet->setCellValue('A2', trans('messages.tailor', [], session('locale')) . ': ' . $tailorNamesStr);
            $sheet->mergeCells('A2:N2');
            $tailorStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '1E293B'],
                    'size' => 14,
                    'name' => 'Arial'
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E7FF'] // Light indigo
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'C7D2FE']
                    ]
                ]
            ];
            $sheet->getStyle('A2:N2')->applyFromArray($tailorStyle);
            $sheet->getRowDimension(2)->setRowHeight(30);
            
            // Row 3: Date and Summary Information
            $dateLabel = trans('messages.order_date', [], session('locale')) ?: 'Date';
            $totalQtyLabel = trans('messages.total_quantity', [], session('locale')) ?: 'Total Quantity';
            $totalItemsLabel = trans('messages.total_items', [], session('locale')) ?: 'Total Items';
            $infoText = $dateLabel . ': ' . $currentDate . ' | Time: ' . $currentTime . ' | ' . 
                       $totalQtyLabel . ': ' . $totalQuantity . ' | ' . 
                       $totalItemsLabel . ': ' . $items->count();
            $sheet->setCellValue('A3', $infoText);
            $sheet->mergeCells('A3:N3');
            $infoStyle = [
                'font' => [
                    'bold' => false,
                    'color' => ['rgb' => '475569'],
                    'size' => 11,
                    'name' => 'Arial'
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9'] // Light gray
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0']
                    ]
                ]
            ];
            $sheet->getStyle('A3:N3')->applyFromArray($infoStyle);
            $sheet->getRowDimension(3)->setRowHeight(25);
            
            // Row 4: Empty row for spacing
            $sheet->getRowDimension(4)->setRowHeight(5);
            
            // Add data starting from row 5 (after header section)
            $dataStartRow = 5;
            $sheet->fromArray($data, null, 'A' . $dataStartRow);
            
            // Style table header row
            $headerRowStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                    'name' => 'Arial'
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6366F1'] // Bright indigo
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '4F46E5']
                    ]
                ]
            ];
            $sheet->getStyle('A' . $dataStartRow . ':N' . $dataStartRow)->applyFromArray($headerRowStyle);
            $sheet->getRowDimension($dataStartRow)->setRowHeight(30);
            
            // Style data rows - alternate row colors with professional styling
            $dataRowStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB']
                    ]
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'font' => [
                    'size' => 10,
                    'name' => 'Arial'
                ]
            ];
            
            $totalRows = count($data) + $dataStartRow - 1;
            for ($row = $dataStartRow + 1; $row <= $totalRows; $row++) {
                // Alternate colors: light blue for even rows, white for odd rows
                $fillColor = ($row % 2 == 0) ? 'F0F9FF' : 'FFFFFF';
                
                $sheet->getStyle("A{$row}:N{$row}")->applyFromArray($dataRowStyle);
                $sheet->getStyle("A{$row}:N{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($fillColor);
                
                // Highlight quantity column
                $sheet->getStyle("F{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FEF3C7'); // Light yellow
                $sheet->getStyle("F{$row}")->getFont()->setBold(true);
                $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                
                // Center align for numeric columns
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Order No
                $sheet->getStyle("L{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Buttons
                $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Date
                
                // Set row height
                $sheet->getRowDimension($row)->setRowHeight(25);
            }
            
            // Set column widths with better sizing
            $sheet->getColumnDimension('A')->setWidth(18); // Order No
            $sheet->getColumnDimension('B')->setWidth(25); // Customer
            $sheet->getColumnDimension('C')->setWidth(30); // Abaya Name
            $sheet->getColumnDimension('D')->setWidth(18); // Code
            $sheet->getColumnDimension('E')->setWidth(25); // Tailor
            $sheet->getColumnDimension('F')->setWidth(15); // Quantity
            $sheet->getColumnDimension('G')->setWidth(18); // Color
            $sheet->getColumnDimension('H')->setWidth(18); // Size
            $sheet->getColumnDimension('I')->setWidth(18); // Length
            $sheet->getColumnDimension('J')->setWidth(18); // Bust
            $sheet->getColumnDimension('K')->setWidth(18); // Sleeves
            $sheet->getColumnDimension('L')->setWidth(15); // Buttons
            $sheet->getColumnDimension('M')->setWidth(18); // Date
            $sheet->getColumnDimension('N')->setWidth(40); // Notes
            
            // Freeze panes - freeze header section and table headers
            $sheet->freezePane('A' . ($dataStartRow + 1));
            
            // Auto-filter on table headers
            $sheet->setAutoFilter('A' . $dataStartRow . ':N' . $totalRows);
            
            // Add a summary row at the bottom
            $summaryRow = $totalRows + 2;
            $sheet->setCellValue('A' . $summaryRow, trans('messages.total', [], session('locale')) . ':');
            $sheet->mergeCells('A' . $summaryRow . ':E' . $summaryRow);
            $sheet->setCellValue('F' . $summaryRow, $totalQuantity);
            $sheet->mergeCells('F' . $summaryRow . ':N' . $summaryRow);
            
            $summaryStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                    'name' => 'Arial'
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '8B5CF6'] // Purple
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '6D28D9']
                    ]
                ]
            ];
            $sheet->getStyle('A' . $summaryRow . ':N' . $summaryRow)->applyFromArray($summaryStyle);
            $sheet->getRowDimension($summaryRow)->setRowHeight(30);
            
            // Generate filename with date and tailor name
            $tailorNameForFile = 'All';
            if (!empty($tailorNames)) {
                $firstTailorName = $tailorNames[0];
                $tailorNameForFile = preg_replace('/[^a-zA-Z0-9_-]/', '_', $firstTailorName);
                $tailorNameForFile = str_replace(' ', '_', $tailorNameForFile);
                // Limit length
                if (strlen($tailorNameForFile) > 30) {
                    $tailorNameForFile = substr($tailorNameForFile, 0, 30);
                }
            }
            $filename = 'Abayas_to_Tailor_' . $tailorNameForFile . '_' . date('Y-m-d_His') . '.xlsx';
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
        } else {
            // Fallback: CSV export with UTF-8 BOM for Excel
            $filename = 'Abayas_to_Tailor_' . date('Y-m-d_His') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            // Add UTF-8 BOM for proper Excel display
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            foreach ($data as $row) {
                // Ensure UTF-8 encoding
                $row = array_map(function($value) {
                    return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }, $row);
                fputcsv($output, $row);
            }
            fclose($output);
            exit;
        }
    } catch (\Exception $e) {
        \Log::error('Error in exportAbayasToTailorExcel: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error generating Excel: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Export abayas to PDF for tailor assignment
 */
public function exportAbayasToTailorPDF(Request $request)
{
    try {
        // Handle both GET (query) and POST (input) requests
        $itemIds = $request->query('item_ids', $request->input('item_ids', []));
        
        // Ensure item_ids is an array
        if (!is_array($itemIds)) {
            $itemIds = [$itemIds];
        }
        
        // Filter out any empty values
        $itemIds = array_filter($itemIds, function($id) {
            return !empty($id);
        });
        
        if (empty($itemIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No items selected'
            ], 422);
        }

        // Get items with all related data
        $items = SpecialOrderItem::with([
            'specialOrder.customer',
            'stock.images',
            'tailor'
        ])
        ->whereIn('id', $itemIds)
        ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No items found'
            ], 404);
        }

        // Get unique tailors from items for header display
        $uniqueTailorIds = [];
        $uniqueTailors = [];
        foreach ($items as $item) {
            if ($item->tailor && !in_array($item->tailor->id, $uniqueTailorIds)) {
                $uniqueTailorIds[] = $item->tailor->id;
                $uniqueTailors[] = $item->tailor;
            }
        }
        
        // Get total quantity
        $totalQuantity = $items->sum('quantity') ?? $items->count();
        
        // Current date and time
        $currentDate = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');
        
        // Build tailor names string
        $tailorNames = [];
        foreach ($uniqueTailors as $tailor) {
            if ($tailor && isset($tailor->tailor_name)) {
                $tailorNames[] = $tailor->tailor_name;
            }
        }
        $tailorNamesStr = !empty($tailorNames) ? implode(', ', $tailorNames) : (trans('messages.not_assigned', [], session('locale')) ?: 'Not Assigned');

        // Prepare data for PDF
        $formattedItems = [];
        foreach ($items as $item) {
            $order = $item->specialOrder;
            $stock = $item->stock;
            $tailor = $item->tailor;
            $customer = $order->customer ?? null;
            
            // Generate order number
            $orderNo = '—';
            if ($order) {
                $orderDate = Carbon::parse($order->created_at);
                $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
            }

            // Get color name
            $colorName = '';
            if ($item->color_id) {
                $color = Color::find($item->color_id);
                if ($color) {
                    $locale = session('locale', 'en');
                    $colorName = $locale === 'ar' ? ($color->color_name_ar ?? $color->color_name_en) : ($color->color_name_en ?? $color->color_name_ar);
                }
            }

            // Get size name
            $sizeName = '';
            if ($item->size_id) {
                $size = Size::find($item->size_id);
                if ($size) {
                    $locale = session('locale', 'en');
                    $sizeName = $locale === 'ar' ? ($size->size_name_ar ?? $size->size_name_en) : ($size->size_name_en ?? $size->size_name_ar);
                }
            }

            // Format date
            $orderDate = $order ? Carbon::parse($order->created_at)->format('Y-m-d') : '—';

            $formattedItems[] = [
                'order_no' => $orderNo,
                'customer' => $customer ? $customer->name : trans('messages.stock_special_order', [], session('locale')),
                'abaya_name' => $item->design_name ?? $item->abaya_code ?? '—',
                'abaya_code' => $item->abaya_code ?? '—',
                'tailor' => $tailor ? $tailor->tailor_name : '—',
                'quantity' => $item->quantity ?? 1,
                'color' => $colorName ?: '—',
                'size' => $sizeName ?: '—',
                'length' => $item->abaya_length ?? '—',
                'bust' => $item->bust ?? '—',
                'sleeves' => $item->sleeves_length ?? '—',
                'buttons' => $item->buttons ? trans('messages.yes', [], session('locale')) : trans('messages.no', [], session('locale')),
                'order_date' => $orderDate,
                'notes' => $item->notes ?? '—'
            ];
        }

        $html = view('special_orders.abayas_to_tailor_pdf', compact('formattedItems', 'tailorNamesStr', 'totalQuantity', 'currentDate', 'currentTime'))->render();
        
        // Use dompdf if available
        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'landscape');
            return $pdf->stream('abayas_to_tailor_' . date('Y-m-d_His') . '.pdf');
        } else {
            // Fallback: return HTML view for printing
            return view('special_orders.abayas_to_tailor_pdf', compact('formattedItems', 'tailorNamesStr', 'totalQuantity', 'currentDate', 'currentTime'));
        }
    } catch (\Exception $e) {
        \Log::error('Error in exportAbayasToTailorPDF: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error generating PDF: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Check and mark items as late delivery based on settings
 */
public function checkAndMarkLateDeliveries()
{
    try {
        $settings = Settings::getSettings();
        $lateDeliveryWeeks = $settings->late_delivery_weeks ?? 2;
        
        // Get all items that are with tailor (processing status) and not yet marked as late
        $itemsWithTailor = SpecialOrderItem::where('tailor_status', 'processing')
            ->whereNotNull('sent_to_tailor_at')
            ->where('is_late_delivery', false)
            ->get();
        
        $now = Carbon::now();
        $markedLate = 0;
        
        foreach ($itemsWithTailor as $item) {
            $sentDate = Carbon::parse($item->sent_to_tailor_at);
            $weeksPassed = $sentDate->diffInWeeks($now);
            
            // If weeks passed exceeds the late delivery threshold, mark as late
            if ($weeksPassed >= $lateDeliveryWeeks) {
                $item->is_late_delivery = true;
                $item->marked_late_at = $now;
                $item->save();
                $markedLate++;
            }
        }
        
        return response()->json([
            'success' => true,
            'marked_late' => $markedLate
        ]);
    } catch (\Exception $e) {
        \Log::error('Error checking late deliveries: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error checking late deliveries'
        ], 500);
    }
}

/**
 * Get late delivery items for dashboard
 */
public function getLateDeliveries()
{
    $lateItems = SpecialOrderItem::with(['specialOrder.customer', 'tailor', 'stock.images'])
        ->where('is_late_delivery', true)
        ->where('tailor_status', 'processing')
        ->orderBy('marked_late_at', 'DESC')
        ->get()
        ->map(function($item) {
            $order = $item->specialOrder;
            $tailor = $item->tailor;
            $stock = $item->stock;
            
            $image = '/images/placeholder.png';
            if ($stock && $stock->images->first()) {
                $imagePath = $stock->images->first()->image_path;
                if (strpos($imagePath, 'http') === 0) {
                    $image = $imagePath;
                } else {
                    $image = asset('images/stock_images/' . basename($imagePath));
                }
            }
            
            $sentDate = $item->sent_to_tailor_at ? Carbon::parse($item->sent_to_tailor_at) : null;
            $daysLate = $sentDate ? $sentDate->diffInDays(Carbon::now()) : 0;
            
            $orderDate = $order ? Carbon::parse($order->created_at) : null;
            $orderNo = $orderDate ? $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT) : '—';
            
            return [
                'id' => $item->id,
                'order_no' => $orderNo,
                'order_id' => $order->id ?? 0,
                'customer_name' => $order->customer->name ?? 'N/A',
                'customer_phone' => $order->customer->phone ?? 'N/A',
                'abaya_code' => $item->abaya_code ?? 'N/A',
                'design_name' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                'image' => $image,
                'tailor_name' => $tailor->tailor_name ?? 'N/A',
                'tailor_id' => $item->tailor_id,
                'sent_date' => $sentDate ? $sentDate->format('Y-m-d') : null,
                'days_late' => $daysLate,
                'marked_late_at' => $item->marked_late_at ? Carbon::parse($item->marked_late_at)->format('Y-m-d H:i') : null,
            ];
        });
    
    return response()->json([
        'success' => true,
        'items' => $lateItems
    ]);
}

public function markTailorItemsReceived(Request $request)
{
    try {
        $itemIds = $request->input('item_ids', []);

        if (empty($itemIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No items selected'
            ], 422);
        }

        DB::beginTransaction();

        // Get items before update to get order IDs
        $items = SpecialOrderItem::whereIn('id', $itemIds)
            ->where('tailor_status', 'processing')
            ->get();
        
        $orderIds = $items->pluck('special_order_id')->unique()->toArray();

        // Get items before update to check which are stock orders
        $itemsToUpdate = SpecialOrderItem::whereIn('id', $itemIds)
            ->where('tailor_status', 'processing')
            ->with('specialOrder')
            ->get();
        
        // Process stock orders first - add to inventory immediately when received
        foreach ($itemsToUpdate as $item) {
            $order = $item->specialOrder;
            if ($order && ($order->customer_id === null || $order->source === 'stock')) {
                // This is a stock order item - add to inventory immediately
                if ($item->stock_id && $item->color_id && $item->size_id) {
                    $stock = Stock::find($item->stock_id);
                    if (!$stock) continue;

                    $colorSize = ColorSize::where('stock_id', $item->stock_id)
                        ->where('color_id', $item->color_id)
                        ->where('size_id', $item->size_id)
                        ->first();
                    
                    $previousQty = 0;
                    if ($colorSize) {
                        // Update existing quantity
                        $previousQty = (int)$colorSize->qty;
                        $colorSize->qty += $item->quantity;
                        $colorSize->save();
                    } else {
                        // Create new color_size entry
                        $colorSize = ColorSize::create([
                            'stock_id' => $item->stock_id,
                            'color_id' => $item->color_id,
                            'size_id' => $item->size_id,
                            'qty' => $item->quantity,
                        ]);
                    }

                    // Log audit entry – use special_order_no when available
                    $user = Auth::user();
                    $orderNumber = $order->special_order_no ?? (Carbon::parse($order->created_at)->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT));
                    
                    StockAuditLog::create([
                        'stock_id' => $stock->id,
                        'abaya_code' => $stock->abaya_code,
                        'barcode' => $stock->barcode,
                        'design_name' => $stock->design_name,
                        'operation_type' => 'special_order',
                        'previous_quantity' => $previousQty,
                        'new_quantity' => (int)$colorSize->qty,
                        'quantity_change' => $item->quantity,
                        'related_id' => $orderNumber,
                        'related_type' => 'special_order',
                        'related_info' => ['order_id' => $order->id],
                        'color_id' => $item->color_id,
                        'size_id' => $item->size_id,
                        'user_id' => $user ? $user->id : null,
                        'added_by' => $user ? $user->user_name : 'System',
                        'notes' => 'Added from special order (received from tailor)',
                    ]);
                }
            }
        }

        // Now update the items status
        $updated = SpecialOrderItem::whereIn('id', $itemIds)
            ->where('tailor_status', 'processing')
            ->update([
                'tailor_status' => 'received',
                'received_from_tailor_at' => now(),
                'is_late_delivery' => false // Unmark as late when received
            ]);

        // Log material audit entries for each received item
        $user = Auth::user();
        $userName = $user ? $user->user_name : 'System';
        
        foreach ($itemsToUpdate as $item) {
            if (!$item->stock_id) continue;
            
            $stock = Stock::find($item->stock_id);
            if (!$stock) continue;
            
            $order = $item->specialOrder;
            if (!$order) continue;
            
            // Use special_order_no from database, fallback to generated number if not available
            $orderNumber = $order->special_order_no ?? (Carbon::parse($order->created_at)->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT));
            
            // Get tailor name
            $tailorName = null;
            $tailorId = null;
            if ($item->tailor_id) {
                $tailor = Tailor::find($item->tailor_id);
                $tailorName = $tailor ? $tailor->tailor_name : null;
                $tailorId = $item->tailor_id;
            }
            
            // Note: Materials are now deducted when sending to tailor, not when receiving
            // No material deduction needed here when receiving items from tailor

            try {
                MaterialAuditLog::create([
                    'stock_id' => $item->stock_id,
                    'abaya_code' => $stock->abaya_code,
                    'barcode' => $stock->barcode,
                    'design_name' => $stock->design_name,
                    'operation_type' => 'special_order_received',
                    'quantity_added' => $item->quantity,
                    'tailor_id' => $tailorId,
                    'tailor_name' => $tailorName,
                    'special_order_id' => $order->id,
                    'special_order_number' => $orderNumber,
                    'color_id' => $item->color_id,
                    'size_id' => $item->size_id,
                    'user_id' => $user ? $user->id : null,
                    'added_by' => $userName,
                    'added_at' => now(),
                    'notes' => 'Special order received from tailor',
                ]);
            } catch (\Exception $e) {
                \Log::error('Error creating material audit log: ' . $e->getMessage());
            }
        }

        // Update order statuses based on items
        foreach ($orderIds as $orderId) {
            $order = SpecialOrder::with('items')->find($orderId);
            if ($order) {
                $isStockOrder = $order->customer_id === null || $order->source === 'stock';
                
                // For stock orders, check if all items are received to set status
                if ($isStockOrder) {
                    // Refresh items to get latest status
                    $order->refresh();
                    $order->load('items');
                    
                    $allReceived = $order->items->every(function($item) {
                        return $item->tailor_status === 'received';
                    });
                    
                    if ($allReceived && $order->items->count() > 0) {
                        // All items received - set status to saved_in_stock
                        $order->status = 'saved_in_stock';
                        $order->save();
                    } else {
                        $this->updateOrderStatusBasedOnItems($order);
                    }
                } else {
                    $this->updateOrderStatusBasedOnItems($order);
                }
            }
        }

        // Send SMS to customer: "Your order is ready" for orders that became ready (customer orders only)
        foreach ($orderIds as $orderId) {
            try {
                $order = SpecialOrder::with('items')->find($orderId);
                if ($order && $order->customer_id !== null && $order->source !== 'stock' && $order->status === 'ready') {
                    $this->sendSpecialOrderSms($orderId, 7);
                }
            } catch (\Exception $e) {
                \Log::error('Error sending special order ready SMS: ' . $e->getMessage());
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => $updated . ' item(s) marked as received successfully'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error in markTailorItemsReceived: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error marking items as received: ' . $e->getMessage()
        ], 500);
    }
}

public function maintenance()
{
    if (!Auth::check()) {
        return redirect()->route('login_page')->with('error', 'Please login first');
    }

    $permissions = Auth::user()->permissions ?? [];

    if (!in_array(5, $permissions)) {
        return redirect()->route('login_page')->with('error', 'Permission denied');
    }

    return view('special_orders.maintenance');
}

public function getMaintenanceData()
{
    try {
        // Get all tailors for the dropdown
        $tailors = Tailor::select('id', 'tailor_name as name')->get();

        // Get items that are ready for maintenance
        // Only show items where tailor_status = 'received' (item is individually ready)
        // Also include items that are already in maintenance (even from delivered orders)
        // EXCLUDE items from delivered orders that are NOT in maintenance
        // EXCLUDE items that are delivered from maintenance (maintenance_status = 'delivered')
        // EXCLUDE stock orders from main table (they should only appear when selected from search dropdown)
        $rawItems = SpecialOrderItem::with(['specialOrder.customer', 'stock.images', 'maintenanceTailor'])
            ->where(function($query) {
                // Show items where:
                // 1. Item's tailor_status is 'received' (item is ready individually) AND order is NOT delivered AND not yet in maintenance AND NOT a stock order
                // OR
                // 2. Item is already in maintenance (delivered_to_tailor or received_from_tailor) - even from delivered orders or stock orders
                $query->where(function($q) {
                    // Items that are ready (tailor_status = 'received') from non-delivered orders and not yet in maintenance
                    // EXCLUDE stock orders (they should only appear when selected from search)
                    $q->where('tailor_status', 'received')
                      ->whereNull('maintenance_status')
                      ->whereHas('specialOrder', function($orderQ) {
                          $orderQ->where('status', '!=', 'delivered')
                                 ->where('status', '!=', 'saved_in_stock') // Exclude stock orders
                                 ->whereNotNull('customer_id') // Exclude stock orders (they have null customer_id)
                                 ->where(function($sourceQ) {
                                     $sourceQ->whereNull('source')
                                            ->orWhere('source', '!=', 'stock');
                                 });
                      });
                })
                ->orWhere(function($q) {
                    // Items that are already in maintenance (can be from delivered orders or stock orders)
                    // Include these because they were already selected from the search dropdown
                    $q->whereIn('maintenance_status', ['delivered_to_tailor', 'received_from_tailor']);
                });
            })
            ->where(function($query) {
                // EXCLUDE items that are delivered from maintenance (maintenance_status = 'delivered')
                $query->whereNull('maintenance_status')
                      ->orWhereIn('maintenance_status', ['delivered_to_tailor', 'received_from_tailor']);
            })
            ->get();

        // Group items by same type (abaya_code, length, bust, sleeves) and same order
        $groupedItems = [];
        foreach ($rawItems as $item) {
            $order = $item->specialOrder;
            $stock = $item->stock;
            $customer = $order ? $order->customer : null;
            
            // Create a unique key for grouping: order_id + abaya_code + length + bust + sleeves
            $groupKey = ($order ? $order->id : 'no_order') . '_' . 
                       ($item->abaya_code ?? 'no_code') . '_' . 
                       ($item->abaya_length ?? 'no_length') . '_' . 
                       ($item->bust ?? 'no_bust') . '_' . 
                       ($item->sleeves_length ?? 'no_sleeves');
            
            if (!isset($groupedItems[$groupKey])) {
                // Get image
                $image = '/images/placeholder.png';
                if ($stock && $stock->images && $stock->images->first()) {
                    $image = $stock->images->first()->image_path;
                }

                // Use special_order_no from database, fallback to generated format if not available
                $orderNo = '—';
                if ($order) {
                    if ($order->special_order_no) {
                        $orderNo = $order->special_order_no;
                    } else {
                        // Fallback to generated format: YYYY-00ID
                        $orderDate = Carbon::parse($order->created_at);
                        $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                    }
                }

                // Get the first item's maintenance status (if any item in group has maintenance status)
                $maintenanceStatus = null;
                $deliveryCharges = 0;
                $repairCost = 0;
                $costBearer = null;
                $transferNumber = null;
                $maintenanceNotes = null;
                $tailorName = null;
                
                // Check if any item in this group is already in maintenance
                $maintenanceItem = $rawItems->first(function($i) use ($item, $order) {
                    return ($i->specialOrder && $i->specialOrder->id === ($order ? $order->id : null)) &&
                           ($i->abaya_code === $item->abaya_code) &&
                           ($i->abaya_length == $item->abaya_length) &&
                           ($i->bust == $item->bust) &&
                           ($i->sleeves_length == $item->sleeves_length) &&
                           $i->maintenance_status;
                });
                
                if ($maintenanceItem) {
                    $maintenanceStatus = $maintenanceItem->maintenance_status;
                    $deliveryCharges = $maintenanceItem->maintenance_delivery_charges ?? 0;
                    $repairCost = $maintenanceItem->maintenance_repair_cost ?? 0;
                    $costBearer = $maintenanceItem->maintenance_cost_bearer ?? null;
                    $transferNumber = $maintenanceItem->maintenance_transfer_number ?? null;
                    $maintenanceNotes = $maintenanceItem->maintenance_notes ?? null;
                    
                    // Get tailor name if item is assigned to a tailor
                    if ($maintenanceItem->maintenanceTailor) {
                        $tailorName = $maintenanceItem->maintenanceTailor->tailor_name;
                    }
                }

                // Calculate available quantity (only items not yet sent to tailor)
                $availableQty = ($item->maintenance_status === null || $item->maintenance_status !== 'delivered_to_tailor') 
                    ? ($item->quantity ?? 1) 
                    : 0;
                
                // Calculate maintenance quantity (items sent for alteration)
                $maintenanceQty = ($item->maintenance_status && in_array($item->maintenance_status, ['delivered_to_tailor', 'received_from_tailor'])) 
                    ? ($item->quantity ?? 1) 
                    : 0;

                // Check if this is a stock order
                $isStockOrder = $order && ($order->customer_id === null || $order->source === 'stock');
                
                $groupedItems[$groupKey] = [
                    'id' => $item->id, // Use first item's ID as representative
                    'item_ids' => [$item->id], // Store all item IDs in this group
                    'design_name' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                    'abaya_code' => $item->abaya_code ?? 'N/A',
                    'abaya_length' => $item->abaya_length ?? null,
                    'bust' => $item->bust ?? null,
                    'sleeves_length' => $item->sleeves_length ?? null,
                    'order_no' => $orderNo,
                    'order_id' => $order ? $order->id : null,
                    'customer_name' => $isStockOrder ? trans('messages.stock_special_order', [], session('locale')) : ($customer ? $customer->name : 'N/A'),
                    'customer_phone' => $isStockOrder ? '-' : ($customer ? $customer->phone : 'N/A'),
                    'maintenance_status' => $maintenanceStatus,
                    'tailor_name' => $tailorName, // Tailor name for items sent to maintenance
                    'image' => $image,
                    'delivery_charges' => $deliveryCharges,
                    'repair_cost' => $repairCost,
                    'cost_bearer' => $costBearer,
                    'transfer_number' => $transferNumber,
                    'order_status' => $order ? $order->status : null,
                    'maintenance_notes' => $maintenanceNotes,
                    'quantity' => $maintenanceQty, // Quantity sent for alteration (under maintenance)
                    'total_quantity' => $item->quantity ?? 1, // Total quantity in group
                    'available_quantity' => $availableQty, // Quantity available for maintenance
                ];
            } else {
                // Add this item's quantity to the group
                $groupedItems[$groupKey]['total_quantity'] += ($item->quantity ?? 1);
                
                // Only add to available_quantity if item is not yet sent to tailor
                if ($item->maintenance_status === null || $item->maintenance_status !== 'delivered_to_tailor') {
                    $groupedItems[$groupKey]['available_quantity'] += ($item->quantity ?? 1);
                }
                
                // Add to maintenance quantity if item is in maintenance
                if ($item->maintenance_status && in_array($item->maintenance_status, ['delivered_to_tailor', 'received_from_tailor'])) {
                    $groupedItems[$groupKey]['quantity'] += ($item->quantity ?? 1);
                }
                
                $groupedItems[$groupKey]['item_ids'][] = $item->id;
            }
        }

        // Convert grouped items to array
        $items = array_values($groupedItems);

        // Calculate statistics
        $deliveredItems = SpecialOrderItem::where('maintenance_status', 'delivered')
            ->with('specialOrder')
            ->get();

        $customerDeliveredItems = $deliveredItems->filter(function ($i) {
            return ($i->maintenance_cost_bearer ?? null) === 'customer';
        });
        $companyDeliveredItems = $deliveredItems->filter(function ($i) {
            // treat null/other as company
            return ($i->maintenance_cost_bearer ?? null) !== 'customer';
        });
        
        $statistics = [
            'delivered_to_tailor' => SpecialOrderItem::where('maintenance_status', 'delivered_to_tailor')->count(),
            'received_from_tailor' => SpecialOrderItem::where('maintenance_status', 'received_from_tailor')->count(),
            'delivered_count' => $deliveredItems->count(),
            'total_delivery_charges' => $deliveredItems->sum('maintenance_delivery_charges') ?? 0,
            'total_repair_cost' => $deliveredItems->sum('maintenance_repair_cost') ?? 0,
            'customer_delivery_charges' => $customerDeliveredItems->sum('maintenance_delivery_charges') ?? 0,
            'customer_repair_cost' => $customerDeliveredItems->sum('maintenance_repair_cost') ?? 0,
            'company_delivery_charges' => $companyDeliveredItems->sum('maintenance_delivery_charges') ?? 0,
            'company_repair_cost' => $companyDeliveredItems->sum('maintenance_repair_cost') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'items' => $items,
            'tailors' => $tailors,
            'statistics' => $statistics
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in getMaintenanceData: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching maintenance data: ' . $e->getMessage(),
            'items' => [],
            'tailors' => [],
            'statistics' => [
                'delivered_to_tailor' => 0, 
                'received_from_tailor' => 0,
                'delivered_count' => 0,
                'total_delivery_charges' => 0,
                'total_repair_cost' => 0,
                'customer_delivery_charges' => 0,
                'customer_repair_cost' => 0,
                'company_delivery_charges' => 0,
                'company_repair_cost' => 0,
            ]
        ], 500);
    }
}

public function getRepairHistory()
{
    try {
        // Get items that have been received from tailor or delivered (completed maintenance)
        $history = SpecialOrderItem::with(['specialOrder.customer', 'stock.images', 'maintenanceTailor'])
            ->whereIn('maintenance_status', ['received_from_tailor', 'delivered'])
            ->orderBy('repaired_delivered_at', 'DESC')
            ->orderBy('repaired_at', 'DESC')
            ->get()
            ->map(function($item) {
                $order = $item->specialOrder;
                $stock = $item->stock;
                $customer = $order ? $order->customer : null;
                $tailor = $item->maintenanceTailor;

                // Check if this is a stock order
                $isStockOrder = $order && ($order->customer_id === null || $order->source === 'stock');

                // Use special_order_no from database, fallback to generated format if not available
                $orderNo = '—';
                if ($order) {
                    if ($order->special_order_no) {
                        $orderNo = $order->special_order_no;
                    } else {
                        // Fallback to generated format: YYYY-00ID
                        $orderDate = Carbon::parse($order->created_at);
                        $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                    }
                }

                return [
                    'id' => $item->id,
                    'design_name' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                    'abaya_code' => $item->abaya_code ?? 'N/A',
                    'order_no' => $orderNo,
                    'transfer_number' => $item->maintenance_transfer_number ?? '—',
                    'customer_name' => $isStockOrder ? trans('messages.stock_special_order', [], session('locale')) : ($customer ? $customer->name : 'N/A'),
                    'customer_phone' => $isStockOrder ? '-' : ($customer ? $customer->phone : 'N/A'),
                    'tailor_name' => $tailor ? $tailor->tailor_name : 'N/A',
                    'sent_date' => $item->sent_for_repair_at ? Carbon::parse($item->sent_for_repair_at)->format('Y-m-d') : '—',
                    'received_date' => $item->repaired_at ? Carbon::parse($item->repaired_at)->format('Y-m-d') : '—',
                    'delivered_date' => $item->repaired_delivered_at ? Carbon::parse($item->repaired_delivered_at)->format('Y-m-d') : '—',
                    'delivery_charges' => $item->maintenance_delivery_charges ?? 0,
                    'repair_cost' => $item->maintenance_repair_cost ?? 0,
                    'cost_bearer' => $item->maintenance_cost_bearer ?? null,
                    'maintenance_status' => $item->maintenance_status,
                    'maintenance_notes' => $item->maintenance_notes ?? null,
                ];
            });

        return response()->json([
            'success' => true,
            'history' => $history
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in getRepairHistory: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching repair history: ' . $e->getMessage(),
            'history' => []
        ], 500);
    }
}

public function getMaintenancePaymentHistory()
{
    try {
        // Get all delivered maintenance items where customer paid
        $deliveredItems = SpecialOrderItem::with(['specialOrder.customer', 'specialOrder'])
            ->where('maintenance_status', 'delivered')
            ->where('maintenance_cost_bearer', 'customer')
            ->where(function($query) {
                $query->where('maintenance_delivery_charges', '>', 0)
                      ->orWhere('maintenance_repair_cost', '>', 0);
            })
            ->orderBy('repaired_delivered_at', 'DESC')
            ->get();
        
        // Group by order to aggregate payments
        $paymentGroups = [];
        foreach ($deliveredItems as $item) {
            $order = $item->specialOrder;
            if (!$order) continue;
            
            $orderId = $order->id;
            
            if (!isset($paymentGroups[$orderId])) {
                $customer = $order->customer;
                $account = Account::find($order->account_id);
                $user = User::find($order->user_id);
                
                // Generate order number
                $orderDate = Carbon::parse($order->created_at);
                $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                
                $paymentGroups[$orderId] = [
                    'id' => $order->id,
                    'order_no' => $orderNo,
                    'customer_name' => $customer ? $customer->name : 'N/A',
                    'customer_phone' => $customer ? $customer->phone : 'N/A',
                    'payment_amount' => 0,
                    'delivery_charges' => 0,
                    'repair_cost' => 0,
                    'account_id' => $order->account_id,
                    'account_name' => $account ? ($account->account_name ?? 'Account #' . $account->id) : 'N/A',
                    'account_branch' => $account ? $account->account_branch : null,
                    'added_by' => $order->updated_by ?? $order->added_by ?? 'System',
                    'user_name' => $user ? ($user->user_name ?? 'N/A') : 'N/A',
                    'payment_date' => $item->repaired_delivered_at ? Carbon::parse($item->repaired_delivered_at)->format('Y-m-d H:i') : ($order->updated_at ? Carbon::parse($order->updated_at)->format('Y-m-d H:i') : '—'),
                ];
            }
            
            // Add this item's costs to the group
            $paymentGroups[$orderId]['delivery_charges'] += floatval($item->maintenance_delivery_charges ?? 0);
            $paymentGroups[$orderId]['repair_cost'] += floatval($item->maintenance_repair_cost ?? 0);
            $paymentGroups[$orderId]['payment_amount'] += floatval($item->maintenance_delivery_charges ?? 0) + floatval($item->maintenance_repair_cost ?? 0);
        }
        
        // Convert to array and filter out zero payments
        $payments = array_values(array_filter($paymentGroups, function($payment) {
            return $payment['payment_amount'] > 0;
        }));
        
        // Sort by payment date descending
        usort($payments, function($a, $b) {
            return strtotime($b['payment_date']) - strtotime($a['payment_date']);
        });

        return response()->json([
            'success' => true,
            'payments' => $payments
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in getMaintenancePaymentHistory: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching payment history: ' . $e->getMessage(),
            'payments' => []
        ], 500);
    }
}

public function sendForRepair(Request $request)
{
    try {
        DB::beginTransaction();

        $tailorId = $request->input('tailor_id');
        $maintenanceNotes = $request->input('maintenance_notes');
        $items = $request->input('items'); // New format: array of {item_id, quantity}

        if (!$tailorId) {
            return response()->json([
                'success' => false,
                'message' => 'Tailor ID is required'
            ], 422);
        }

        // Handle new format (items array with quantities)
        if ($items && is_array($items) && count($items) > 0) {
            $totalSent = 0;
            
            foreach ($items as $itemData) {
                $itemId = $itemData['item_id'] ?? null;
                $quantity = intval($itemData['quantity'] ?? 1);
                
                if (!$itemId) continue;
                
                $item = SpecialOrderItem::find($itemId);
                if (!$item) {
                    continue; // Skip if item not found
                }
                
                $itemQuantity = $item->quantity ?? 1;
                
                if ($quantity >= $itemQuantity) {
                    // Send entire item
                    $item->maintenance_status = 'delivered_to_tailor';
                    $item->maintenance_tailor_id = $tailorId;
                    $item->maintenance_notes = $maintenanceNotes;
                    $item->sent_for_repair_at = now();
                    $item->save();
                    $totalSent += $itemQuantity;
                } else {
                    // Need to split: create new item for remaining quantity
                    $remainingQty = $itemQuantity - $quantity;
                    
                    // Update current item to send quantity
                    $item->quantity = $quantity;
                    $item->maintenance_status = 'delivered_to_tailor';
                    $item->maintenance_tailor_id = $tailorId;
                    $item->maintenance_notes = $maintenanceNotes;
                    $item->sent_for_repair_at = now();
                    $item->save();
                    $totalSent += $quantity;
                    
                    // Create new item for remaining quantity
                    if ($remainingQty > 0) {
                        $newItem = $item->replicate();
                        $newItem->quantity = $remainingQty;
                        $newItem->maintenance_status = null;
                        $newItem->maintenance_tailor_id = null;
                        $newItem->maintenance_notes = null;
                        $newItem->sent_for_repair_at = null;
                        $newItem->save();
                    }
                }
            }
            
            if ($totalSent > 0) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => $totalSent . ' piece(s) sent to tailor for repair successfully'
                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No items were sent. Please check selected items.'
                ], 422);
            }
        }
        
        // Handle old format (backward compatibility)
        $itemId = $request->input('item_id');
        $itemIds = $request->input('item_ids', [$itemId]);
        $quantity = $request->input('quantity');

        if (!$itemId) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Item ID or items array is required'
            ], 422);
        }

        // Get all items in the group
        $itemsCollection = SpecialOrderItem::whereIn('id', $itemIds)->get();
        
        if ($itemsCollection->isEmpty()) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'No items found'
            ], 404);
        }

        // Calculate total available quantity
        $totalQuantity = $itemsCollection->sum('quantity');
        
        // If quantity is specified and > 1, we need to handle splitting
        if ($quantity && $quantity > 1 && $quantity <= $totalQuantity) {
            $remainingQuantity = $totalQuantity - $quantity;
            $sentCount = 0;
            
            // Send items until we reach the requested quantity
            foreach ($itemsCollection as $item) {
                if ($sentCount >= $quantity) break;
                
                if ($item->quantity <= ($quantity - $sentCount)) {
                    // Send entire item
                    $item->maintenance_status = 'delivered_to_tailor';
                    $item->maintenance_tailor_id = $tailorId;
                    $item->maintenance_notes = $maintenanceNotes;
                    $item->sent_for_repair_at = now();
                    $item->save();
                    $sentCount += $item->quantity;
                } else {
                    // Need to split: create new item for remaining quantity
                    $sendQty = $quantity - $sentCount;
                    $remainingQty = $item->quantity - $sendQty;
                    
                    // Update current item to send quantity
                    $item->quantity = $sendQty;
                    $item->maintenance_status = 'delivered_to_tailor';
                    $item->maintenance_tailor_id = $tailorId;
                    $item->maintenance_notes = $maintenanceNotes;
                    $item->sent_for_repair_at = now();
                    $item->save();
                    $sentCount += $sendQty;
                    
                    // Create new item for remaining quantity
                    if ($remainingQty > 0) {
                        $newItem = $item->replicate();
                        $newItem->quantity = $remainingQty;
                        $newItem->maintenance_status = null;
                        $newItem->maintenance_tailor_id = null;
                        $newItem->maintenance_notes = null;
                        $newItem->sent_for_repair_at = null;
                        $newItem->save();
                    }
                }
            }
            
            $message = $quantity . ' piece(s) sent to tailor for repair successfully';
        } else {
            // Send all items in the group (quantity = 1 or not specified)
            foreach ($itemsCollection as $item) {
                $item->maintenance_status = 'delivered_to_tailor';
                $item->maintenance_tailor_id = $tailorId;
                $item->maintenance_notes = $maintenanceNotes;
                $item->sent_for_repair_at = now();
                $item->save();
            }
            
            $message = 'Item(s) sent to tailor for repair successfully';
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error in sendForRepair: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error sending item for repair: ' . $e->getMessage()
        ], 500);
    }
}

public function receiveFromTailor(Request $request)
{
    try {
        DB::beginTransaction();

        $itemId = $request->input('item_id');

        if (!$itemId) {
            return response()->json([
                'success' => false,
                'message' => 'Item ID is required'
            ], 422);
        }

        $item = SpecialOrderItem::findOrFail($itemId);

        // Just update status to received - no costs at this stage
        $item->maintenance_status = 'received_from_tailor';
        $item->repaired_at = now();
        $item->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Item received from tailor successfully'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error in receiveFromTailor: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error receiving item from tailor: ' . $e->getMessage()
        ], 500);
    }
}

public function markRepairedDelivered(Request $request)
{
    try {
        DB::beginTransaction();

        $itemId = $request->input('item_id');
        $deliveryCharges = floatval($request->input('delivery_charges', 0));
        $repairCost = floatval($request->input('repair_cost', 0));
        $costBearer = $request->input('cost_bearer');
        $accountId = $request->input('account_id');
        $paymentAmount = floatval($request->input('payment_amount', 0));

        if (!$itemId) {
            return response()->json([
                'success' => false,
                'message' => 'Item ID is required'
            ], 422);
        }

        if (!$costBearer) {
            return response()->json([
                'success' => false,
                'message' => 'Cost bearer is required'
            ], 422);
        }

        // If customer is bearer and there are costs, require account and payment
        if ($costBearer === 'customer' && ($deliveryCharges > 0 || $repairCost > 0)) {
            if (!$accountId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account selection is required when customer bears the cost'
                ], 422);
            }
            if ($paymentAmount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount is required when customer bears the cost'
                ], 422);
            }
        }

        // Company bearer still has costs; we just don't take customer payment here.
        if ($deliveryCharges < 0 || $repairCost < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Charges must be 0 or positive'
            ], 422);
        }

        $item = SpecialOrderItem::with('specialOrder')->findOrFail($itemId);
        $order = $item->specialOrder;

        if (!$order) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Special order not found for this item'
            ], 404);
        }

        // Update item with cost details
        $item->maintenance_delivery_charges = $deliveryCharges;
        $item->maintenance_repair_cost = $repairCost;
        $item->maintenance_cost_bearer = $costBearer;
        $item->repaired_delivered_at = now();
        // Mark item as delivered from maintenance - this will exclude it from current items
        $item->maintenance_status = 'delivered';
        
        // Build maintenance notes with cost details
        $costDetails = [];
        if ($deliveryCharges > 0) {
            $costDetails[] = 'Delivery Charges: ' . number_format($deliveryCharges, 3) . ' ر.ع';
        }
        if ($repairCost > 0) {
            $costDetails[] = 'Repair Cost: ' . number_format($repairCost, 3) . ' ر.ع';
        }
        $costDetailsText = !empty($costDetails) ? "\n" . implode("\n", $costDetails) . "\nCost Bearer: " . ($costBearer === 'customer' ? 'Customer' : 'Company') : '';
        
        // Append cost details to maintenance_notes if not empty
        if (!empty($costDetailsText)) {
            $existingNotes = $item->maintenance_notes ?? '';
            $item->maintenance_notes = $existingNotes . $costDetailsText;
        }
        
        $item->save();

        // Update order with costs and payment
        if ($costBearer === 'customer' && ($deliveryCharges > 0 || $repairCost > 0)) {
            $totalAdditionalCharges = $deliveryCharges + $repairCost;
            $order->total_amount = floatval($order->total_amount) + $totalAdditionalCharges;
            
            // Update paid amount and account
            $order->paid_amount = floatval($order->paid_amount) + $paymentAmount;
            $order->account_id = $accountId;
            $order->status = 'delivered';
        }
        
        // Update shipping_cost and repair_cost in special_orders table
        $order->shipping_cost = $deliveryCharges;
        $order->repair_cost = $repairCost;
        $order->save();

        // Update account balance if payment was made
        if ($accountId && $paymentAmount > 0) {
            $account = \App\Models\Account::find($accountId);
            if ($account) {
                $account->opening_balance = floatval($account->opening_balance) + $paymentAmount;
                $account->save();
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Item delivered successfully'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error in markRepairedDelivered: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error delivering item: ' . $e->getMessage()
        ], 500);
    }
}

public function searchDeliveredOrders(Request $request)
{
    try {
        $search = $request->input('search', '');
        
        if (empty($search)) {
            return response()->json([
                'success' => true,
                'orders' => []
            ]);
        }

        // Search delivered orders by customer name, customer phone, or special order number
        // Include both customer orders and stock orders in search
        $numericSearch = preg_replace('/[^0-9]/', '', $search);
        
        $orders = SpecialOrder::with(['customer', 'items.stock.images'])
            ->whereIn('status', ['delivered', 'saved_in_stock']) // Include both delivered customer orders and saved stock orders
            ->where(function($query) use ($search, $numericSearch) {
                // Search by customer name (only for customer orders)
                $query->where(function($q) use ($search) {
                    $q->whereHas('customer', function($customerQ) use ($search) {
                        $customerQ->where('name', 'LIKE', '%' . $search . '%');
                    });
                })
                // Search by customer phone (only for customer orders)
                ->orWhere(function($q) use ($search) {
                    $q->whereHas('customer', function($customerQ) use ($search) {
                        $customerQ->where('phone', 'LIKE', '%' . $search . '%');
                    });
                })
                // Search by special_order_no from database - works for BOTH customer and stock orders
                ->orWhere('special_order_no', 'LIKE', '%' . $search . '%')
                // Fallback: also search by generated format (YYYY-00ID) for backward compatibility
                ->orWhere('id', 'LIKE', '%' . $numericSearch . '%')
                ->orWhereRaw("CONCAT(YEAR(created_at), '-', LPAD(id, 4, '0')) LIKE ?", ['%' . $search . '%'])
                // Search for stock orders by "stock" keyword or related text (in both languages)
                ->orWhere(function($q) use ($search) {
                    $searchLower = strtolower($search);
                    // Check for stock-related keywords
                    if (stripos($search, 'stock') !== false || 
                        stripos($search, 'مخزون') !== false ||
                        stripos($search, 'طلب') !== false ||
                        stripos($search, 'special') !== false) {
                        $q->where(function($stockQ) {
                            $stockQ->whereNull('customer_id')
                                  ->orWhere('source', 'stock');
                        });
                    }
                });
            })
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->get()
            ->map(function($order) {
                $customer = $order->customer;
                $isStockOrder = $order->customer_id === null || $order->source === 'stock';
                
                // Use special_order_no from database, fallback to generated format if not available
                $orderNo = '—';
                if ($order->special_order_no) {
                    $orderNo = $order->special_order_no;
                } else {
                    // Fallback to generated format: YYYY-00ID
                    $orderDate = Carbon::parse($order->created_at);
                    $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                }
                
                return [
                    'id' => $order->id,
                    'order_no' => $orderNo,
                    'customer_name' => $isStockOrder ? trans('messages.stock_special_order', [], session('locale')) : ($customer ? $customer->name : 'N/A'),
                    'customer_phone' => $isStockOrder ? '-' : ($customer ? $customer->phone : 'N/A'),
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in searchDeliveredOrders: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error searching orders: ' . $e->getMessage(),
            'orders' => []
        ], 500);
    }
}

public function getDeliveredOrderItems(Request $request)
{
    try {
        $orderId = $request->input('order_id');
        
        if (!$orderId) {
            return response()->json([
                'success' => false,
                'message' => 'Order ID is required'
            ], 422);
        }

        // Only get orders with status 'delivered' or 'saved_in_stock' (includes both customer and stock orders)
        $order = SpecialOrder::with(['customer', 'items.stock.images'])
            ->whereIn('status', ['delivered', 'saved_in_stock'])
            ->findOrFail($orderId);
        
        // Check if this is a stock order
        $isStockOrder = $order->customer_id === null || $order->source === 'stock';
        
        // Use special_order_no if available, otherwise fallback to generated number
        $orderNo = $order->special_order_no;
        if (!$orderNo) {
            // Fallback: Generate order number
            $orderDate = Carbon::parse($order->created_at);
            $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
        }

        // Only return items from this delivered order
        $items = $order->items->map(function($item) {
            $stock = $item->stock;
            
            // Get image
            $image = '/images/placeholder.png';
            if ($stock && $stock->images && $stock->images->first()) {
                $image = $stock->images->first()->image_path;
            }

            return [
                'id' => $item->id,
                'design_name' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                'abaya_code' => $item->abaya_code ?? 'N/A',
                'abaya_length' => $item->abaya_length ?? null,
                'bust' => $item->bust ?? null,
                'sleeves_length' => $item->sleeves_length ?? null,
                'quantity' => (int)($item->quantity ?? 1), // Ensure quantity is an integer
                'image' => $image,
                'maintenance_status' => $item->maintenance_status ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_no' => $orderNo,
                'special_order_no' => $order->special_order_no,
                'customer_name' => $isStockOrder ? trans('messages.stock_special_order', [], session('locale')) : ($order->customer ? $order->customer->name : 'N/A'),
                'customer_phone' => $isStockOrder ? '-' : ($order->customer ? $order->customer->phone : 'N/A'),
            ],
            'items' => $items
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in getDeliveredOrderItems: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching order items: ' . $e->getMessage()
        ], 500);
    }
}

public function showBill($id)
{
    try {
        $specialOrder = SpecialOrder::with(['customer.area', 'customer.city', 'items.stock.images'])
            ->findOrFail($id);
        
        // Generate order number: YYYY-00ID (e.g., 2025-0001)
        $orderDate = Carbon::parse($specialOrder->created_at);
        $orderNumber = $orderDate->format('Y') . '-' . str_pad($specialOrder->id, 4, '0', STR_PAD_LEFT);
        
        return view('bills.special_order_bill', [
            'specialOrder' => $specialOrder,
            'orderNumber' => $orderNumber
        ]);
    } catch (\Exception $e) {
        \Log::error('Error showing special order bill: ' . $e->getMessage());
        abort(404, 'Special order not found');
    }
    }

    /**
     * Show tailor orders list page
     */
    public function tailorOrdersList()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(7, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $tailors = Tailor::orderBy('tailor_name')->get();
        return view('tailors.tailor_orders_list', compact('tailors'));
    }

    /**
     * Get tailor orders list data
     */
    public function getTailorOrdersList(Request $request)
    {
        try {
            $tailorId = $request->input('tailor_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            if (!$tailorId) {
                return response()->json([
                    'success' => true,
                    'orders' => []
                ]);
            }

            // First, get unique special order IDs with items assigned to this tailor
            $itemsQuery = SpecialOrderItem::where('tailor_id', $tailorId)
                ->whereNotNull('tailor_id')
                // Exclude stock orders - only show special orders with customers
                ->whereHas('specialOrder', function($query) {
                    $query->whereNotNull('customer_id')
                          ->where(function($q) {
                              $q->whereNull('source')
                                ->orWhere('source', '!=', 'stock');
                          });
                })
                // Filter by date range if provided
                ->when($startDate, function($query) use ($startDate) {
                    $query->whereRaw('DATE(COALESCE(sent_to_tailor_at, received_from_tailor_at, created_at)) >= ?', [$startDate]);
                })
                ->when($endDate, function($query) use ($endDate) {
                    $query->whereRaw('DATE(COALESCE(sent_to_tailor_at, received_from_tailor_at, created_at)) <= ?', [$endDate]);
                });

            // Get unique order IDs
            $orderIds = $itemsQuery->distinct()->pluck('special_order_id');

            // Get the special orders with pagination
            $specialOrders = SpecialOrder::with(['customer.city', 'customer.area', 'customer'])
                ->whereIn('id', $orderIds)
                ->orderBy('created_at', 'DESC')
                ->paginate(10);

            // Get all items for these orders assigned to this tailor and group by order
            $allItems = $itemsQuery
                ->whereIn('special_order_id', $specialOrders->pluck('id'))
                ->get()
                ->groupBy('special_order_id');

            $formattedOrders = $specialOrders->map(function($order) use ($allItems) {
                $customer = $order->customer ?? null;
                $items = $allItems->get($order->id, collect());
                
                // Calculate total quantity for this order
                $totalQuantity = $items->sum('quantity');
                
                // Get address from city and area
                $address = '';
                if ($customer) {
                    $addressParts = [];
                    if ($customer->area) $addressParts[] = $customer->area->area_name_ar ?? $customer->area->area_name_en ?? '';
                    if ($customer->city) $addressParts[] = $customer->city->city_name_ar ?? $customer->city->city_name_en ?? '';
                    $address = implode(', ', array_filter($addressParts)) ?: '-';
                }

                // Get earliest sent_at date from items
                $sentAt = $items->min(function($item) {
                    return $item->sent_to_tailor_at ? Carbon::parse($item->sent_to_tailor_at) : null;
                });
                $sentAtFormatted = $sentAt ? $sentAt->format('Y-m-d H:i') : '-';
                
                return [
                    'id' => $order->id,
                    'order_no' => 'SO-' . str_pad($order->id ?? 0, 6, '0', STR_PAD_LEFT),
                    'quantity' => $totalQuantity,
                    'customer_name' => $customer->name ?? '-',
                    'customer_phone' => $customer->phone ?? '-',
                    'customer_address' => $address,
                    'customer_country' => 'Oman',
                    'sent_at' => $sentAtFormatted,
                ];
            });

            return response()->json([
                'success' => true,
                'orders' => $formattedOrders->values()->all(),
                'current_page' => $specialOrders->currentPage(),
                'last_page' => $specialOrders->lastPage(),
                'per_page' => $specialOrders->perPage(),
                'total' => $specialOrders->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export tailor orders to PDF
     */
    public function exportTailorOrdersPDF(Request $request)
    {
        try {
            $tailorId = $request->input('tailor_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            if (!$tailorId) {
                return redirect()->back()->with('error', 'Please select a tailor');
            }

            $tailor = Tailor::findOrFail($tailorId);
            
            // Get unique order IDs with items assigned to this tailor
            $itemsQuery = SpecialOrderItem::where('tailor_id', $tailorId)
                ->whereNotNull('tailor_id')
                // Exclude stock orders - only show special orders with customers
                ->whereHas('specialOrder', function($query) {
                    $query->whereNotNull('customer_id')
                          ->where(function($q) {
                              $q->whereNull('source')
                                ->orWhere('source', '!=', 'stock');
                          });
                })
                // Filter by date range if provided
                ->when($startDate, function($query) use ($startDate) {
                    $query->whereRaw('DATE(COALESCE(sent_to_tailor_at, received_from_tailor_at, created_at)) >= ?', [$startDate]);
                })
                ->when($endDate, function($query) use ($endDate) {
                    $query->whereRaw('DATE(COALESCE(sent_to_tailor_at, received_from_tailor_at, created_at)) <= ?', [$endDate]);
                });

            // Get unique order IDs
            $orderIds = $itemsQuery->distinct()->pluck('special_order_id');

            // Get the special orders
            $specialOrders = SpecialOrder::with(['customer.city', 'customer.area', 'customer'])
                ->whereIn('id', $orderIds)
                ->orderBy('created_at', 'DESC')
                ->get();

            // Get all items for these orders assigned to this tailor and group by order
            $allItems = $itemsQuery
                ->whereIn('special_order_id', $specialOrders->pluck('id'))
                ->get()
                ->groupBy('special_order_id');

            $orders = $specialOrders->map(function($order) use ($allItems) {
                $customer = $order->customer ?? null;
                $items = $allItems->get($order->id, collect());
                
                // Calculate total quantity for this order
                $totalQuantity = $items->sum('quantity');
                
                // Get address from city and area
                $address = '';
                if ($customer) {
                    $addressParts = [];
                    if ($customer->area) $addressParts[] = $customer->area->area_name_ar ?? $customer->area->area_name_en ?? '';
                    if ($customer->city) $addressParts[] = $customer->city->city_name_ar ?? $customer->city->city_name_en ?? '';
                    $address = implode(', ', array_filter($addressParts)) ?: '-';
                }
                
                return [
                    'order_no' => 'SO-' . str_pad($order->id ?? 0, 6, '0', STR_PAD_LEFT),
                    'quantity' => $totalQuantity,
                    'customer_name' => $customer->name ?? '-',
                    'customer_phone' => $customer->phone ?? '-',
                    'customer_address' => $address,
                    'customer_country' => 'Oman',
                    'sent_at' => $items->min(function($item) {
                        return $item->sent_to_tailor_at ? Carbon::parse($item->sent_to_tailor_at)->format('Y-m-d H:i') : null;
                    }) ?? '-',
                ];
            });

            $html = view('tailors.tailor_orders_pdf', compact('orders', 'tailor'))->render();
            
            // Use dompdf if available, otherwise return HTML view
            if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                return $pdf->download('tailor_orders_' . $tailor->tailor_name . '_' . date('Y-m-d') . '.pdf');
            } else {
                // Fallback: return HTML view for printing
                return view('tailors.tailor_orders_pdf', compact('orders', 'tailor'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export tailor orders to Excel
     */
    public function exportTailorOrdersExcel(Request $request)
    {
        try {
            $tailorId = $request->input('tailor_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            if (!$tailorId) {
                return redirect()->back()->with('error', 'Please select a tailor');
            }

            $tailor = Tailor::findOrFail($tailorId);
            
            // Get unique order IDs with items assigned to this tailor
            $itemsQuery = SpecialOrderItem::where('tailor_id', $tailorId)
                ->whereNotNull('tailor_id')
                // Exclude stock orders - only show special orders with customers
                ->whereHas('specialOrder', function($query) {
                    $query->whereNotNull('customer_id')
                          ->where(function($q) {
                              $q->whereNull('source')
                                ->orWhere('source', '!=', 'stock');
                          });
                })
                // Filter by date range if provided
                ->when($startDate, function($query) use ($startDate) {
                    $query->whereRaw('DATE(COALESCE(sent_to_tailor_at, received_from_tailor_at, created_at)) >= ?', [$startDate]);
                })
                ->when($endDate, function($query) use ($endDate) {
                    $query->whereRaw('DATE(COALESCE(sent_to_tailor_at, received_from_tailor_at, created_at)) <= ?', [$endDate]);
                });

            // Get unique order IDs
            $orderIds = $itemsQuery->distinct()->pluck('special_order_id');

            // Get the special orders
            $specialOrders = SpecialOrder::with(['customer.city', 'customer.area', 'customer'])
                ->whereIn('id', $orderIds)
                ->orderBy('created_at', 'DESC')
                ->get();

            // Get all items for these orders assigned to this tailor and group by order
            $allItems = $itemsQuery
                ->whereIn('special_order_id', $specialOrders->pluck('id'))
                ->get()
                ->groupBy('special_order_id');

            $data = [];
            $data[] = ['Order No', 'Quantity', 'Customer Name', 'Phone', 'Address', 'Country', 'Sent Date'];
            
            foreach ($specialOrders as $order) {
                $customer = $order->customer ?? null;
                $items = $allItems->get($order->id, collect());
                
                // Calculate total quantity for this order
                $totalQuantity = $items->sum('quantity');
                
                $address = '';
                if ($customer) {
                    $addressParts = [];
                    if ($customer->area) $addressParts[] = $customer->area->area_name_ar ?? $customer->area->area_name_en ?? '';
                    if ($customer->city) $addressParts[] = $customer->city->city_name_ar ?? $customer->city->city_name_en ?? '';
                    $address = implode(', ', array_filter($addressParts)) ?: '-';
                }
                
                // Get earliest sent_at date from items
                $sentAt = $items->min(function($item) {
                    return $item->sent_to_tailor_at ? Carbon::parse($item->sent_to_tailor_at)->format('Y-m-d H:i') : null;
                });
                $sentAtFormatted = $sentAt ? $sentAt->format('Y-m-d H:i') : '-';
                
                $data[] = [
                    'SO-' . str_pad($order->id ?? 0, 6, '0', STR_PAD_LEFT),
                    $totalQuantity,
                    $customer->name ?? '-',
                    $customer->phone ?? '-',
                    $address,
                    'Oman',
                    $sentAtFormatted,
                ];
            }

            // Use PhpSpreadsheet if available
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                
                // Use fromArray - PhpSpreadsheet handles UTF-8 automatically
                $sheet->fromArray($data, null, 'A1');
                
                // Auto-size columns
                foreach (range('A', 'L') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $filename = 'tailor_orders_' . $tailor->tailor_name . '_' . date('Y-m-d') . '.xlsx';
                
                // UTF-8 encoding is handled by PhpSpreadsheet automatically
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . rawurlencode($filename) . '"');
                header('Cache-Control: max-age=0');
                
                $writer->save('php://output');
                exit;
            } else {
                // Fallback: CSV export with UTF-8 BOM for Excel
                $filename = 'tailor_orders_' . $tailor->tailor_name . '_' . date('Y-m-d') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                // Add UTF-8 BOM for proper Excel display
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                foreach ($data as $row) {
                    // Ensure UTF-8 encoding
                    $row = array_map(function($value) {
                        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }, $row);
                    fputcsv($output, $row);
                }
                fclose($output);
                exit;
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error generating Excel: ' . $e->getMessage());
        }
    }

    /**
     * Deduct required materials from tailor inventory when abayas are added to stock
     */
    private function deductMaterialsFromTailor($stockId, $tailorId, $abayaQuantity)
    {
        try {
            // Get required materials for this abaya
            $abayaMaterial = AbayaMaterial::where('abaya_id', $stockId)->first();
            
            if (!$abayaMaterial || !$abayaMaterial->materials) {
                return; // No materials required for this abaya
            }

            // Process each required material
            foreach ($abayaMaterial->materials as $materialData) {
                $materialId = $materialData['material_id'] ?? null;
                $requiredPerAbaya = floatval($materialData['quantity'] ?? 0);
                
                if (!$materialId || $requiredPerAbaya <= 0) {
                    continue;
                }

                // Calculate total required quantity (per abaya * quantity of abayas added)
                $totalRequired = $requiredPerAbaya * $abayaQuantity;

                // Find TailorMaterial records for this tailor, material, and abaya
                $tailorMaterials = TailorMaterial::where('tailor_id', $tailorId)
                    ->where('material_id', $materialId)
                    ->where(function($q) use ($stockId) {
                        $q->where('abaya_id', $stockId)
                          ->orWhereNull('abaya_id'); // Also check materials not tied to specific abaya
                    })
                    ->orderBy('abaya_id', 'desc') // Prefer abaya-specific materials first
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
     * Deduct materials from main inventory when abayas are added
     * Also deducts from tailor inventory if tailor is provided
     * Creates MaterialQuantityAudit entries for each material deducted
     */
    private function deductMaterialsFromInventory($stockId, $abayaQuantity, $source = 'special_order', $tailorId = null, $tailorName = null, $specialOrderId = null, $specialOrderNumber = null)
    {
        try {
            // Get required materials for this abaya
            $abayaMaterial = AbayaMaterial::where('abaya_id', $stockId)->first();
            
            if (!$abayaMaterial || !$abayaMaterial->materials) {
                return; // No materials required for this abaya
            }

            $stock = Stock::find($stockId);
            if (!$stock) {
                return;
            }

            $user = Auth::user();
            $userName = $user ? ($user->user_name ?? $user->name ?? 'system') : 'system';
            $userId = $user ? $user->id : null;

            // Process each required material
            foreach ($abayaMaterial->materials as $materialData) {
                $materialId = $materialData['material_id'] ?? null;
                $requiredPerAbaya = floatval($materialData['quantity'] ?? 0);
                
                if (!$materialId || $requiredPerAbaya <= 0) {
                    continue;
                }

                // Get material
                $material = Material::find($materialId);
                if (!$material) {
                    continue;
                }

                // Calculate total required quantity (per abaya * quantity of abayas added)
                $totalRequired = $requiredPerAbaya * $abayaQuantity;

                // Get current quantity based on unit
                $getCurrentQuantity = function($mat) {
                    if ($mat->unit === 'roll') {
                        return floatval($mat->rolls_count ?? 0);
                    } else {
                        // For meter and piece units, use meters_per_roll
                        return floatval($mat->meters_per_roll ?? 0);
                    }
                };

                // For special_order: Only deduct from tailor materials, NOT from main inventory
                // Main inventory is only deducted when sending materials directly to tailor
                $previousQuantity = $getCurrentQuantity($material);
                $remainingQuantity = $previousQuantity;

                // If no tailor, skip material deduction (materials must be sent to tailor first)
                if (!$tailorId) {
                    continue; // Skip if no tailor - materials must be sent to tailor before use
                }

                // Deduct from tailor materials (required for special_order operations)
                $tailorMaterialQuantityDeducted = 0;
                $previousTailorMaterialQuantity = 0;
                $newTailorMaterialQuantity = 0;
                
                // Find TailorMaterial records for this tailor, material, and abaya
                $tailorMaterials = TailorMaterial::where('tailor_id', $tailorId)
                    ->where('material_id', $materialId)
                    ->where(function($q) use ($stockId) {
                        $q->where('abaya_id', $stockId)
                          ->orWhereNull('abaya_id'); // Also check materials not tied to specific abaya
                    })
                    ->orderBy('abaya_id', 'desc') // Prefer abaya-specific materials first
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
                                $q->where('abaya_id', $stockId)
                                  ->orWhereNull('abaya_id');
                            })
                            ->first();
                        
                        if ($negativeTailorMaterial) {
                            // Update existing record (may already be negative)
                            $currentQty = floatval($negativeTailorMaterial->quantity ?? 0);
                            $negativeTailorMaterial->quantity = $currentQty - $remainingToDeduct;
                            $negativeTailorMaterial->save();
                        } else {
                            // Create new record with negative quantity
                            $user = Auth::user();
                            $userName = $user ? ($user->user_name ?? $user->name ?? 'system') : 'system';
                            $userId = $user ? $user->id : 1;
                            
                            $negativeTailorMaterial = TailorMaterial::create([
                                'tailor_id' => $tailorId,
                                'material_id' => $materialId,
                                'abaya_id' => $stockId,
                                'quantity' => -$remainingToDeduct,
                                'abayas_expected' => 0,
                                'status' => 'pending',
                                'sent_date' => now()->format('Y-m-d'),
                                'added_by' => $userName,
                                'user_id' => $userId,
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
                            $q->where('abaya_id', $stockId)
                              ->orWhereNull('abaya_id');
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
                        'abaya_code' => $stock->abaya_code,
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
                        'notes' => ($sourceLabels[$source] ?? ucfirst($source)) . ' - Abaya: ' . $stock->abaya_code . ', Quantity: ' . $abayaQuantity . ', Material per abaya: ' . $requiredPerAbaya . ', Total deducted from tailor: ' . $totalRequired . ' (Main inventory unchanged)',
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error creating material quantity audit: ' . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error deducting materials from inventory: ' . $e->getMessage());
        }
    }
    
    public function getShippingFee(Request $request)
{
    try {
        $request->validate([
            'customer.name' => 'required|string|max:255',
            'customer.phone' => 'required|string|max:20',
            'customer.source' => 'required|string|in:whatsapp,walkin',
            'customer.area_id' => 'required|exists:areas,id',
            'customer.city_id' => 'required|exists:cities,id',
            'customer.address' => 'required|string',
            'orders' => 'required|array|min:1',
            'orders.*.quantity' => 'required|integer|min:1',
        ]);

        $customer_id= Customer::where('phone', $request->input('customer.phone'))->value('id');
        $areaId = (int) $request->input('customer.area_id');
        $cityId = (int) $request->input('customer.city_id');
        $phone = $request->input('customer.phone');
        $address = $request->input('customer.address');

        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            [
                'name' => $request->input('customer.name'),
                'area_id' => $areaId,
                'customer_id' => $customer_id,
                'city_id' => $cityId,
            ]
        );
        if (!$customer->wasRecentlyCreated) {
            $customer->name = $request->input('customer.name');
            $customer->area_id = $areaId;
            $customer->city_id = $cityId;
            $customer->address = $address;
            $customer->save();
        }

        $totalQuantity = 0;
        foreach ($request->input('orders', []) as $o) {
            $totalQuantity += (int) ($o['quantity'] ?? 0);
        }

        $shippingFee = get_shipping_fee_from_api($areaId, $cityId, (int) $customer->id, $totalQuantity);
        if ($shippingFee === null) {
            return response()->json([
                'success' => false,
                'message' => 'Shipping fee could not be fetched from API',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'shipping_fee' => (float) $shippingFee,
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

}