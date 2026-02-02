<?php

use Carbon\Carbon;
use App\Models\SMS;
use App\Models\Staff;
use App\Models\Stock;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\ColorSize;
use App\Models\PosOrders;
use App\Models\Appointment;
use App\Models\SessionData;
use App\Models\SpecialOrder;
use App\Models\TransferItem;
use App\Models\PosOrdersDetail;
use App\Models\SpecialOrderItem;
use App\Models\AppointmentPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;



// app/helpers.php
function genUuid()
{
    return sprintf(
        '%04x%04x%04x%04x%04x%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),
        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,
        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,
        // 48 bits for "node"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

function get_sms($params)
{

    // Default variables
    $customer_name = "";
    $pos_order_no = "";
    $special_order_no = "";
    $customer_phone_number = "";
    $abaya_name = "";
    $abaya_code = "";
    $abaya_category = "";
    $color = "";
    $size = "";
    $abaya_length = "";
    $bust = "";
    $sleeves_length = "";
    $buttons = "";
    $special_order_number = "";
    $quantity = "";
    $total_amount = "";
    $remaining_amount = "";
    $paid_amount = "";
    $discount = "";
    $delivery_charges = "";
    $tailor_name = "";
    $delivery_date = "";
    $pos_order_status = "";
    $special_order_status = "";


    // Template fetch
    $sms_text = SMS::where('sms_status', $params['sms_status'])->first();

    // If no template found, return empty string
    if (!$sms_text || !$sms_text->sms) {
        return '';
    }

    // Case: POS Order (sms_status == 1)
    if ($params['sms_status'] == 1) {
        $orderId = $params['order_id'] ?? $params['patient_id'] ?? null;

        if ($orderId) {
            // Fetch the POS order with all relationships
            $order = PosOrders::with([
                'customer',
                'details.stock.category',
                'details.stock',
                'details.color',
                'details.size'
            ])->find($orderId);

            if ($order) {
                // Customer details
                $customer_name = $order->customer ? ($order->customer->name ?? '') : '';
                $customer_phone_number = $order->customer ? ($order->customer->phone ?? '') : '';

                // Order details
                $pos_order_no = $order->order_no ?? '';
                $total_amount = number_format($order->total_amount ?? 0, 3);
                $discount = number_format($order->total_discount ?? 0, 3);
                $paid_amount = number_format($order->paid_amount ?? 0, 3);
                $remaining_amount = number_format(($order->total_amount ?? 0) - ($order->paid_amount ?? 0), 3);
                $delivery_charges = number_format($order->delivery_charges ?? 0, 3);

                // Get order status
                $pos_order_status = '';
                if ($order->delivery_status) {
                    $pos_order_status = $order->delivery_status;
                } elseif ($order->return_status) {
                    $pos_order_status = 'Returned';
                } else {
                    $pos_order_status = 'Pending';
                }

                // Get first item details (or combine if multiple items)
                $firstDetail = $order->details->first();
                if ($firstDetail) {
                    $stock = $firstDetail->stock;
                    if ($stock) {
                        $abaya_name = $stock->design_name ?? $stock->abaya_code ?? '';
                        $abaya_code = $stock->abaya_code ?? '';

                        // Get category name based on locale
                        $locale = session('locale', 'en');
                        if ($stock->category) {
                            if ($locale === 'ar') {
                                $abaya_category = $stock->category->category_name_ar ?? $stock->category->category_name ?? '';
                            } else {
                                $abaya_category = $stock->category->category_name ?? $stock->category->category_name_ar ?? '';
                            }
                        } else {
                            $abaya_category = '';
                        }
                    }

                    // Get color and size from first detail
                    $locale = session('locale', 'en');
                    if ($firstDetail->color) {
                        if ($locale === 'ar') {
                            $color = $firstDetail->color->color_name_ar ?? $firstDetail->color->color_name_en ?? '';
                        } else {
                            $color = $firstDetail->color->color_name_en ?? $firstDetail->color->color_name_ar ?? '';
                        }
                    } else {
                        $color = '';
                    }

                    if ($firstDetail->size) {
                        if ($locale === 'ar') {
                            $size = $firstDetail->size->size_name_ar ?? $firstDetail->size->size_name_en ?? '';
                        } else {
                            $size = $firstDetail->size->size_name_en ?? $firstDetail->size->size_name_ar ?? '';
                        }
                    } else {
                        $size = '';
                    }

                    // Calculate total quantity from all order details
                    $quantity = $order->details->sum('item_quantity') ?? 1;
                } else {
                    $abaya_name = '';
                    $abaya_code = '';
                    $abaya_category = '';
                    $color = '';
                    $size = '';
                    $quantity = 1;
                }
            }
        }
    }

    // Case: Special Order (sms_status == 2) and Special Order lifecycle: in progress (6), ready (7), delivered (8)
    else if ($params['sms_status'] == 2 || in_array($params['sms_status'], [6, 7, 8])) {
        // Get special_order_id from params
        $specialOrderId = $params['special_order_id'] ?? null;

        if ($specialOrderId) {
            // Fetch the Special Order with all relationships
            $specialOrder = SpecialOrder::with([
                'customer.area',
                'customer.city',
                'items.stock.category',
                'items.stock',
                'items.tailor'
            ])->find($specialOrderId);

            // Refresh items relationship to ensure data is loaded
            if ($specialOrder) {
                $specialOrder->load('items');
            }

            if ($specialOrder) {
                // Customer details
                $customer_name = $specialOrder->customer ? ($specialOrder->customer->name ?? '') : '';
                $customer_phone_number = $specialOrder->customer ? ($specialOrder->customer->phone ?? '') : '';

                // Generate order number: YYYY-00ID (e.g., 2025-0001)
                $orderDate = \Carbon\Carbon::parse($specialOrder->created_at);
                $special_order_no = $specialOrder->special_order_no ?? '';
                $special_order_number = $special_order_no; // Alias

                // Order details
                $totalAmountValue = (float)($specialOrder->total_amount ?? 0);
                $paidAmountValue = (float)($specialOrder->paid_amount ?? 0);
                $remainingAmountValue = $totalAmountValue - $paidAmountValue;

                $total_amount = number_format($totalAmountValue, 3);
                $paid_amount = number_format($paidAmountValue, 3);
                $remaining_amount = number_format($remainingAmountValue, 3);
                $delivery_charges = number_format($specialOrder->shipping_fee ?? 0, 3);
                $special_order_status = $specialOrder->status ?? 'new';

                // Calculate delivery date: 2 weeks after order creation date
                $delivery_date = $orderDate->copy()->addWeeks(2)->format('Y-m-d');

                // Get first item details (or combine if multiple items)
                $firstItem = $specialOrder->items->first();
                if ($firstItem) {
                    $stock = $firstItem->stock;
                    if ($stock) {
                        $abaya_name = $firstItem->design_name ?? $stock->design_name ?? $stock->abaya_code ?? '';
                        $abaya_code = $firstItem->abaya_code ?? $stock->abaya_code ?? '';

                        // Get category name based on locale
                        $locale = session('locale', 'en');
                        if ($stock->category) {
                            if ($locale === 'ar') {
                                $abaya_category = $stock->category->category_name_ar ?? $stock->category->category_name ?? '';
                            } else {
                                $abaya_category = $stock->category->category_name ?? $stock->category->category_name_ar ?? '';
                            }
                        } else {
                            $abaya_category = '';
                        }
                    } else {
                        $abaya_name = $firstItem->design_name ?? $firstItem->abaya_code ?? '';
                        $abaya_code = $firstItem->abaya_code ?? '';
                        $abaya_category = '';
                    }

                    // Get abaya measurements (check if value exists and is numeric, including 0)
                    $abayaLengthValue = $firstItem->abaya_length;
                    if ($abayaLengthValue !== null && $abayaLengthValue !== '' && is_numeric($abayaLengthValue)) {
                        $abaya_length = number_format((float)$abayaLengthValue, 2);
                    } else {
                        $abaya_length = '';
                    }

                    $bustValue = $firstItem->bust;
                    if ($bustValue !== null && $bustValue !== '' && is_numeric($bustValue)) {
                        $bust = number_format((float)$bustValue, 2);
                    } else {
                        $bust = '';
                    }

                    $sleevesLengthValue = $firstItem->sleeves_length;
                    if ($sleevesLengthValue !== null && $sleevesLengthValue !== '' && is_numeric($sleevesLengthValue)) {
                        $sleeves_length = number_format((float)$sleevesLengthValue, 2);
                    } else {
                        $sleeves_length = '';
                    }

                    $buttons = $firstItem->buttons ? (session('locale', 'en') === 'ar' ? 'نعم' : 'Yes') : (session('locale', 'en') === 'ar' ? 'لا' : 'No');

                    // Get tailor name
                    if ($firstItem->tailor) {
                        $tailor_name = $firstItem->tailor->tailor_name ?? '';
                    } elseif ($specialOrder->notes) {
                        $tailor_name = $specialOrder->notes;
                    } else {
                        $tailor_name = '';
                    }

                    // Calculate total quantity from all items
                    $quantity = $specialOrder->items->sum('quantity') ?? 1;

                    // Note: Special orders use custom measurements (abaya_length, bust, sleeves_length)
                    // instead of standard sizes, so $size remains empty for special orders
                    $size = '';
                } else {
                    $abaya_name = '';
                    $abaya_code = '';
                    $abaya_category = '';
                    $abaya_length = '';
                    $bust = '';
                    $sleeves_length = '';
                    $buttons = '';
                    $tailor_name = '';
                    $size = '';
                    $quantity = 1;
                    // Ensure payment values are set even if no items
                    $total_amount = number_format($specialOrder->total_amount ?? 0, 3);
                    $paid_amount = number_format($specialOrder->paid_amount ?? 0, 3);
                    $remaining_amount = number_format(($specialOrder->total_amount ?? 0) - ($specialOrder->paid_amount ?? 0), 3);
                    $delivery_date = $orderDate->copy()->addWeeks(2)->format('Y-m-d');
                }
            }
        }
    }

    // Case: Session done
    else if ($params['sms_status'] == 5) {
    }


    // Define template replacement variables
    $variables = [
        'customer_name'        => $customer_name,
        'pos_order_no'         => $pos_order_no,
        'special_order_no'     => $special_order_no,
        'customer_phone_number' => $customer_phone_number,
        'abaya_name'           => $abaya_name,
        'abaya_code'           => $abaya_code,
        'abaya_category'       => $abaya_category,
        'color'                => $color,
        'size'                 => $size,
        'abaya_length'         => $abaya_length,
        'bust'                 => $bust,
        'sleeves_length'       => $sleeves_length,
        'buttons'              => $buttons,
        'special_order_number' => $special_order_number,
        'quantity'             => $quantity,
        'total_amount'         => $total_amount,
        'remaining_amount'     => $remaining_amount,
        'paid_amount'          => $paid_amount,
        'discount'             => $discount,
        'delivery_charges'     => $delivery_charges,
        'tailor_name'          => $tailor_name,
        'delivery_date'        => $delivery_date,
        'pos_order_status'     => $pos_order_status,
        'special_order_status' => $special_order_status,
    ];


    // Replace placeholders in base64 decoded template
    $string = base64_decode($sms_text->sms);
    foreach ($variables as $key => $value) {
        $string = str_replace('{' . $key . '}', $value, $string);
    }

    return $string;
}



function sms_module($contact, $sms)
{
    if (!empty($contact)) {
        $url = "http://myapp3.com/whatsapp_admin_latest/Api_pos/send_request";

        $form_data = [
            'status' => 1,
            'sender_contact' => $contact,
            'customer_id' => 'tatweeersoftweb',
            'instance_id' => '1xwaxr8k',
            'sms' => base64_encode($sms),
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $form_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: application/json",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($resp, true);
    }
}

/**
 * Sync all pending stocks to website API
 * SIMPLE cronjob helper:
 * - Only checks stocks where website_data_delivery_status == 0 OR 1
 * - Sends each record to the website API
 * - Sets status to 2 on HTTP success, else 0
 * 
 * This function is designed to be called via cronjob
 * 
 * @return array Returns summary of sync operation
 */
function syncPendingStocksToWebsite()
{
    $apiUrl = 'http://duo-fashion.com/admin_advance/Api/add_stock_website';
    $results = [
        'picked' => 0,
        'successful' => 0,
        'failed' => 0,
        'details' => [],
    ];

    $limit = (int) (request()->input('limit', 0));
    $query = Stock::with([
        'colorSizes' => function ($query) {
            $query->whereNotNull('color_id')
                ->whereNotNull('size_id')
                ->where('color_id', '!=', '')
                ->where('size_id', '!=', '');
        },
        'images'
    ])->whereIn('website_data_delivery_status', [0, 1])
        ->orderBy('id', 'asc');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $stocks = $query->get();
    $results['picked'] = $stocks->count();

    foreach ($stocks as $stock) {
        $stockId = (int) $stock->id;

        $stockData = [
            'id' => $stock->id,
            'abaya_code' => $stock->abaya_code ?? '',
            'barcode' => $stock->barcode ?? '',
            'design_name' => $stock->design_name ?? '',
            'sales_price' => (float) ($stock->sales_price ?? 0),
            'quantity' => (int) ($stock->quantity ?? 0),
            'category_id' => $stock->category_id ?? null,
        ];

        $colorSizesArray = [];
        foreach ($stock->colorSizes as $cs) {
            $colorSizesArray[] = [
                'color_id' => (int) $cs->color_id,
                'size_id' => (int) $cs->size_id,
                'qty' => (int) ($cs->qty ?? 0),
            ];
        }

        $imagesArray = [];
        foreach ($stock->images as $img) {
            if (!empty($img->image_path)) {
                $imagesArray[] = $img->image_path;
            }
        }

        $payload = [
            'stock' => $stockData,
            'color_sizes' => $colorSizesArray,
            'images' => $imagesArray,
        ];

        try {
            $payload = [
                'stock'       => $stockData,
                'color_sizes' => $colorSizesArray,
                'images'      => $imagesArray,
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent'   => 'TatweerClothingStockSync/1.0', // optional, some APIs like this
                ])
                ->post('https://duo-fashion.com/admin_advance/Api/add_stock_website', $payload);

            // For better debugging
            \Log::info('Website API call', [
                'stock_id'     => $stockId,
                'sent_payload' => $payload, // or json_encode($payload, JSON_PRETTY_PRINT) if too big
                'status'       => $response->status(),
                'body'         => $response->body(),
                'json'         => $response->json(),
            ]);

            if ($response->successful() && $response->json('status') === true) {  // assuming it returns {"status":true,...} on success
                Stock::where('id', $stockId)->update(['website_data_delivery_status' => 2]);
                $results['successful']++;
                $results['details'][] = [
                    'stock_id'    => $stockId,
                    'success'     => true,
                    'http_status' => $response->status(),
                    'api_message' => $response->json('message') ?? 'OK',
                ];
            } else {
                Stock::where('id', $stockId)->update(['website_data_delivery_status' => 1]); // maybe retry status
                $results['failed']++;
                $results['details'][] = [
                    'stock_id'    => $stockId,
                    'success'     => false,
                    'http_status' => $response->status(),
                    'api_response' => $response->json() ?? $response->body(),
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Website sync failed', ['stock_id' => $stockId, 'error' => $e->getMessage()]);
            Stock::where('id', $stockId)->update(['website_data_delivery_status' => 0]);
            $results['failed']++;
            $results['details'][] = ['stock_id' => $stockId, 'success' => false, 'error' => $e->getMessage()];
        }
    }

    return $results;
}



// function syncPendingTransferItemsToWebsite()
// {
//     $apiUrl = 'https://duo-fashion.com/admin_advance/Api/receive_stock_qty';

//     $results = [
//         'picked'     => 0,
//         'successful' => 0,
//         'failed'     => 0,
//         'details'    => [],
//     ];

//     $limit = (int) (request()->input('limit', 0));

//     $query = TransferItem::where('to_location', 'channel-1')
//         ->whereIn('website_data_delivery_status', [0, 1])
//         ->orderBy('id', 'asc');

//     if ($limit > 0) {
//         $query->limit($limit);
//     }

//     $items = $query->get();
//     $results['picked'] = $items->count();

//     foreach ($items as $item) {

//         $transferItemId = (int) $item->id;

//         // Minimal & valid payload
//         $itemPayload = [
//             'stock_id'   => (int) $item->stock_id,
//             'abaya_code' => $item->abaya_code ?? '',
//             'quantity'   => (int) $item->quantity,
//             'color_id'   => (int) ($item->color_id ?? 0),
//             'size_id'    => (int) ($item->size_id ?? 0),
//             'from'       => $item->from_location ?? '',
//             'to'         => $item->to_location ?? '',
//         ];

//         try {

//             $response = Http::timeout(30)
//                 ->withHeaders([
//                     'Accept'       => 'application/json',
//                     'Content-Type' => 'application/json',
//                     'User-Agent'   => 'TatweerClothingTransferSync/1.0',
//                 ])
//                 ->post($apiUrl, [
//                     'item' => $itemPayload, // ✅ Critical: API expects "item"
//                 ]);

//             // Log for debugging
//             Log::info('Transfer API call', [
//                 'transfer_item_id' => $transferItemId,
//                 'payload'          => ['item' => $itemPayload],
//                 'http_status'      => $response->status(),
//                 'response'         => $response->json(),
//             ]);

//             // ✅ Success condition: HTTP 200 + status=true OR empty body
//             $isSuccess = $response->successful() &&
//                 ($response->json('status') === true || empty($response->body()));

//             if ($isSuccess) {

//                 // Mark as delivered
//                 TransferItem::where('id', $transferItemId)
//                     ->update(['website_data_delivery_status' => 2]);

//                 $results['successful']++;
//                 $results['details'][] = [
//                     'transfer_item_id' => $transferItemId,
//                     'success'          => true,
//                     'http_status'      => $response->status(),
//                     'api_message'      => $response->json('message') ?? 'OK',
//                 ];

//             } else {

//                 // Mark as retryable
//                 TransferItem::where('id', $transferItemId)
//                     ->update(['website_data_delivery_status' => 1]);

//                 $results['failed']++;
//                 $results['details'][] = [
//                     'transfer_item_id' => $transferItemId,
//                     'success'          => false,
//                     'http_status'      => $response->status(),
//                     'api_response'     => $response->json() ?? $response->body(),
//                 ];
//             }

//         } catch (\Exception $e) {

//             // Exception → mark failed
//             Log::error('Transfer sync exception', [
//                 'transfer_item_id' => $transferItemId,
//                 'error'            => $e->getMessage(),
//             ]);

//             TransferItem::where('id', $transferItemId)
//                 ->update(['website_data_delivery_status' => 0]);

//             $results['failed']++;
//             $results['details'][] = [
//                 'transfer_item_id' => $transferItemId,
//                 'success'          => false,
//                 'error'            => $e->getMessage(),
//             ];
//         }
//     }

//     return $results;
// }


function syncAllTransferItemsToWebsiteReceiveQty(string $targetToLocation = 'channel-1', int $limit = 0)
{
    $query = TransferItem::query()
        ->whereRaw('TRIM(to_location) = ?', [$targetToLocation])
        ->where('website_data_delivery_status', 1)
        ->orderBy('id', 'asc');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $items = $query->get();
    $results['picked'] = $items->count();

    foreach ($items as $item) {
        $item_data = Stock::where('id', $item->stock_id)
            ->orderBy('id', 'asc')
            ->first();


        $payload_item = $item->toArray();
        $payload_item['barcode'] = $item_data->barcode;

        $payload = json_encode(['item' => $payload_item]);

        $url = "https://duo-fashion.com/admin_advance/Api/receive_stock_qty";

        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ],
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $resp = curl_exec($curl);

        if ($resp === false) {
            echo curl_error($curl);
        } else {
            $result = json_decode($resp, true);
            if ($result['status'] == 1) {
                $item->website_data_delivery_status = 2;
                $item->save();
            }
            // print_r($result);
        }

        curl_close($curl);
    }
}


/**
 * Fetch website current qty from external API.
 * Loops channel_stocks, gets stock_id, size_id, color_id, abaya_code, barcode (from Stock), sends to website_current_qty API.
 * Same HTTP style as add_stock_website / receive_stock_qty.
 *
 * @return array ['success' => bool, 'http_status' => int, 'response' => array|string, 'items_sent' => int]
 */
function fetchWebsiteCurrentQty($stock_id, $barcode, $color_id, $size_id)
{
    $url = "https://duo-fashion.com/admin_advance/Api/website_current_qty";



    // EXACT payload style like receive_stock_qty
    $payload_item = [
        'stock_id' => (int) $stock_id,
        'size_id'  => (int) $size_id,
        'color_id' => (int) $color_id,
        'barcode'  => $barcode,
    ];

    $payload = json_encode(['item' => $payload_item]);

    Log::info('Website current qty payload', ['item' => $payload_item]);

    $curl = curl_init($url);

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
        ],
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $resp = curl_exec($curl);
    $result = json_decode($resp, true);

    if ($resp === false) {
        Log::error('Website current qty CURL error', [
            'error' => curl_error($curl),
        ]);
    } else {


        if (isset($result['status']) && $result['status'] == 1) {
            return $result['qty'];
        } else {
            print_r($result);
            Log::error('Website current qty API failed', [
                'response' => $result,
                'payload' => $payload_item,
            ]);
        }
    }

    curl_close($curl);
}

/**
 * Send area_id, city_id, customer_id, total_quantity to shipping API and return shipping_fee.
 * Same approach as existing duo-fashion API calls (HTTP POST JSON).
 *
 * @param int $area_id
 * @param int $city_id
 * @param int $customer_id
 * @param int $total_quantity
 * @return float|null shipping_fee from API, or null on failure
 */
function get_shipping_fee_from_api($area_id, $city_id, $customer_id, $total_quantity)
{
    $url = 'https://www.duo-fashion.com/admin_advance/Api/send_shipping_price';

    $payload = json_encode([
        'area_id'        => (int) $area_id,
        'city_id'        => (int) $city_id,
        'customer_id'    => (int) $customer_id,
        'total_quantity' => (int) $total_quantity,
    ]);

    try {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);

        $json = json_decode($response, true);

        if ($httpCode === 200 && isset($json['shipping_charges'])) {
            return (float) $json['shipping_charges'];
        }

        \Log::warning('Shipping API unexpected response', [
            'url'      => $url,
            'payload'  => json_decode($payload, true),
            'response' => $json,
            'status'   => $httpCode,
        ]);

        return null;
    } catch (\Exception $e) {
        \Log::error('Shipping API error', [
            'url'     => $url,
            'payload' => json_decode($payload, true),
            'error'   => $e->getMessage(),
        ]);

        return null;
    }
}

/**
 * Get shipping_fee for POS delivery orders from same API as special orders.
 * Sends area_id, city_id, customer_id, total_quantity, phone.
 *
 * @param int $area_id
 * @param int $city_id
 * @param int $customer_id
 * @param int $total_quantity
 * @param string $phone
 * @return float|null shipping_fee from API, or null on failure
 */
function get_shipping_fee_for_pos_order($area_id, $city_id, $customer_id, $total_quantity, $phone = '')
{
    $url = 'https://www.duo-fashion.com/admin_advance/Api/send_shipping_price';

    $payload = [
        'area_id'        => (int) $area_id,
        'city_id'        => (int) $city_id,
        'customer_id'    => (int) $customer_id,
        'total_quantity' => (int) $total_quantity,
    ];
    if ($phone !== null && $phone !== '') {
        $payload['phone'] = (string) $phone;
    }
    $payloadJson = json_encode($payload);

    try {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payloadJson,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false) {
            throw new \Exception(curl_error($ch));
        }
        curl_close($ch);

        $json = json_decode($response, true);

        if ($httpCode !== 200 || !is_array($json)) {
            \Log::warning('POS shipping API unexpected response', [
                'url' => $url,
                'payload' => $payload,
                'response' => $json,
                'status' => $httpCode,
            ]);
            return null;
        }

        if (isset($json['shipping_fee'])) {
            return (float) $json['shipping_fee'];
        }
        if (isset($json['shipping_charges'])) {
            return (float) $json['shipping_charges'];
        }

        \Log::warning('POS shipping API no fee in response', [
            'url' => $url,
            'payload' => $payload,
            'response' => $json,
        ]);
        return null;
    } catch (\Exception $e) {
        \Log::error('POS shipping API error', [
            'url' => $url,
            'payload' => $payload,
            'error' => $e->getMessage(),
        ]);
        return null;
    }
}
