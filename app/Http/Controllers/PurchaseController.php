<?php

namespace App\Http\Controllers;

use App\Models\PurchaseDraft;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchasePayment;
use App\Models\History;
use App\Models\Material;
use App\Models\MaterialQuantityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class PurchaseController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        return view('stock.purchase');
    }

    public function view_purchase()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        return view('stock.view_purchase');
    }

    /** List drafts and completed purchases in one table (drafts first, then completed) */
    public function getPurchaseDrafts(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $page = max(1, (int) $request->get('page', 1));

        $drafts = PurchaseDraft::with('supplier:id,supplier_name,phone')
            ->orderBy('updated_at', 'DESC')
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'is_completed' => false,
                    'supplier' => $d->supplier,
                    'invoice_no' => $d->invoice_no,
                    'invoice_amount' => $d->invoice_amount,
                    'shipping_cost' => $d->shipping_cost,
                    'total_quantity' => $d->total_quantity,
                    'total_amount' => $d->total_amount,
                ];
            });

        $completed = Purchase::with('supplier:id,supplier_name,phone')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'is_completed' => true,
                    'supplier' => $p->supplier,
                    'invoice_no' => $p->invoice_no,
                    'invoice_amount' => $p->invoice_amount,
                    'shipping_cost' => $p->total_shipping_price,
                    'total_quantity' => $p->total_quantity,
                    'total_amount' => $p->total_amount,
                ];
            });

        $merged = $drafts->concat($completed->values())->values();
        $total = $merged->count();
        $slice = $merged->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator($slice, $total, $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]);
        return $paginator;
    }

    /** Save new draft (stage 1) */
    public function storeDraft(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_no' => 'nullable|string|max:100',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'materials' => 'required|array',
            'materials.*.material_id' => 'required',
            'materials.*.quantity' => 'required|numeric|min:0',
        ]);
        $user = Auth::user();
        $materials = $request->materials;
        $totalQuantity = 0;
        $totalAmount = 0;
        foreach ($materials as $m) {
            $qty = (float) ($m['quantity'] ?? 0);
            $totalQuantity += $qty;
            $totalAmount += (float) ($m['total'] ?? 0);
        }
        $draft = PurchaseDraft::create([
            'supplier_id' => $request->supplier_id,
            'invoice_no' => $request->invoice_no,
            'invoice_amount' => (float) ($request->invoice_amount ?? 0),
            'shipping_cost' => (float) ($request->shipping_cost ?? 0),
            'notes' => $request->notes,
            'materials_json' => $materials,
            'total_quantity' => $totalQuantity,
            'total_items' => count($materials),
            'total_amount' => $totalAmount,
            'user_id' => $user->id ?? null,
            'added_by' => $user->user_name ?? $user->name ?? 'system',
        ]);
        return response()->json([
            'status' => 'success',
            'message' => trans('messages.purchase_draft_saved', [], session('locale', 'en')),
            'draft_id' => $draft->id,
            'redirect_url' => url('view_purchase'),
        ]);
    }

    /** Get single draft (for edit form or view material popup) */
    public function getDraft($id)
    {
        $draft = PurchaseDraft::with('supplier:id,supplier_name,phone')->findOrFail($id);
        return response()->json(['status' => 'success', 'draft' => $draft]);
    }

    /** Get completed purchase (for view materials popup) */
    public function getPurchase($id)
    {
        $purchase = Purchase::with(['supplier:id,supplier_name,phone', 'details'])->findOrFail($id);
        $materialsJson = $purchase->details ? $purchase->details->materials_json : [];
        $draft = (object) ['materials_json' => $materialsJson];
        return response()->json(['status' => 'success', 'draft' => $draft]);
    }

    /** Update draft */
    public function updateDraft(Request $request, $id)
    {
        $draft = PurchaseDraft::with('supplier')->findOrFail($id);
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_no' => 'nullable|string|max:100',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'materials' => 'required|array',
            'materials.*.material_id' => 'required',
            'materials.*.quantity' => 'required|numeric|min:0',
        ]);
        $user = Auth::user();
        $dataBefore = $draft->toArray();
        $dataBefore['supplier_name'] = $draft->supplier->supplier_name ?? null;
        $materials = $request->materials;
        $totalQuantity = 0;
        $totalAmount = 0;
        foreach ($materials as $m) {
            $qty = (float) ($m['quantity'] ?? 0);
            $totalQuantity += $qty;
            $totalAmount += (float) ($m['total'] ?? 0);
        }
        $draft->update([
            'supplier_id' => $request->supplier_id,
            'invoice_no' => $request->invoice_no,
            'invoice_amount' => (float) ($request->invoice_amount ?? 0),
            'shipping_cost' => (float) ($request->shipping_cost ?? 0),
            'notes' => $request->notes,
            'materials_json' => $materials,
            'total_quantity' => $totalQuantity,
            'total_items' => count($materials),
            'total_amount' => $totalAmount,
            'updated_by' => $user->user_name ?? $user->name ?? 'system',
        ]);
        $dataAfter = $draft->fresh()->toArray();
        $dataAfter['supplier_name'] = $draft->supplier->supplier_name ?? null;
        History::create([
            'operation' => 'update',
            'source' => 'purchase',
            'previous_data' => $dataBefore,
            'new_data' => $dataAfter,
            'added_by' => $user->user_name ?? $user->name ?? 'system',
            'user_id' => $user->id ?? null,
            'added_at' => now(),
        ]);
        return response()->json([
            'status' => 'success',
            'message' => trans('messages.purchase_draft_updated', [], session('locale', 'en')),
            'redirect_url' => url('view_purchase'),
        ]);
    }

    /** Complete draft -> save to purchases, purchase_details, purchase_payments; then delete draft */
    public function completeDraft(Request $request, $id)
    {
        $draft = PurchaseDraft::with('supplier')->findOrFail($id);
        $user = Auth::user();
        DB::beginTransaction();
        try {
            $purchase = Purchase::create([
                'supplier_id' => $draft->supplier_id,
                'invoice_no' => $draft->invoice_no,
                'invoice_amount' => $draft->invoice_amount ?? $draft->total_amount,
                'total_quantity' => $draft->total_quantity,
                'total_items' => $draft->total_items,
                'total_amount' => $draft->total_amount,
                'total_shipping_price' => $draft->shipping_cost,
                'notes' => $draft->notes,
                'user_id' => $user->id ?? null,
                'added_by' => $user->user_name ?? $user->name ?? 'system',
            ]);
            PurchaseDetail::create([
                'purchase_id' => $purchase->id,
                'supplier_id' => $draft->supplier_id,
                'invoice_no' => $draft->invoice_no,
                'materials_json' => $draft->materials_json,
                'user_id' => $user->id ?? null,
                'added_by' => $user->user_name ?? $user->name ?? 'system',
            ]);
            // Purchase payments are added only via Record Payment popup on profile page
            // Update material quantity and average buy_price for each line
            $totalQty = (float) $draft->total_quantity;
            $shippingPerUnit = $totalQty > 0 ? (float) $draft->shipping_cost / $totalQty : 0;
            foreach ($draft->materials_json ?? [] as $line) {
                $materialId = (int) ($line['material_id'] ?? 0);
                if (!$materialId) continue;
                $material = Material::find($materialId);
                if (!$material) continue;
                $qty = (float) ($line['quantity'] ?? 0);
                $unitPricePlusShipping = (float) ($line['unit_price_plus_shipping'] ?? 0);
                if ($unitPricePlusShipping <= 0) {
                    $unitPrice = (float) ($line['price'] ?? 0);
                    $unitPricePlusShipping = $unitPrice + $shippingPerUnit;
                }
                $previousQuantity = (float) ($material->quantity ?? 0);
                $material->quantity = $previousQuantity + $qty;
                $currentPrice = (float) ($material->buy_price ?? 0);
                $material->buy_price = ($currentPrice + $unitPricePlusShipping) / 2;
                $material->updated_by = $user->user_name ?? $user->name ?? 'system';
                $material->save();
                // Audit: material quantity added from purchase
                try {
                    MaterialQuantityAudit::create([
                        'material_id' => $material->id,
                        'material_name' => $material->material_name,
                        'operation_type' => 'quantity_added',
                        'source' => 'purchase',
                        'previous_quantity' => $previousQuantity,
                        'new_quantity' => $material->quantity,
                        'quantity_change' => $qty,
                        'remaining_quantity' => $material->quantity,
                        'user_id' => $user->id ?? null,
                        'added_by' => $user->user_name ?? $user->name ?? 'system',
                        'notes' => 'Purchase completed. Invoice: ' . ($draft->invoice_no ?: $purchase->id),
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Material quantity audit (purchase): ' . $e->getMessage());
                }
            }
            $draft->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
        return response()->json([
            'status' => 'success',
            'message' => trans('messages.purchase_completed', [], session('locale', 'en')),
            'redirect_url' => url('view_purchase'),
        ]);
    }

    /** Delete draft + save deleted data to history */
    public function deleteDraft($id)
    {
        $draft = PurchaseDraft::with('supplier')->findOrFail($id);
        $user = Auth::user();
        $deletedData = $draft->toArray();
        $deletedData['supplier_name'] = $draft->supplier->supplier_name ?? null;
        History::create([
            'operation' => 'delete',
            'source' => 'purchase',
            'previous_data' => $deletedData,
            'new_data' => null,
            'added_by' => $user->user_name ?? $user->name ?? 'system',
            'user_id' => $user->id ?? null,
            'added_at' => now(),
        ]);
        $draft->delete();
        return response()->json([
            'status' => 'success',
            'message' => trans('messages.purchase_draft_deleted', [], session('locale', 'en')),
        ]);
    }

    /** Store purchase payment from profile page */
    public function storePurchasePayment(Request $request, $id)
    {
        $purchase = Purchase::findOrFail($id);
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:visa,cash,bank',
        ]);
        $user = Auth::user();
        PurchasePayment::create([
            'purchase_id' => $purchase->id,
            'amount' => (float) $request->amount,
            'payment_method' => $request->payment_method,
            'payment_date' => $request->payment_date,
            'user_id' => $user->id ?? null,
            'added_by' => $user->user_name ?? $user->name ?? 'system',
        ]);
        return response()->json([
            'status' => 'success',
            'message' => trans('messages.payment_recorded_successfully', [], session('locale', 'en')),
        ]);
    }

    /** Purchase profile page (completed purchases only) */
    public function purchaseProfile($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $purchase = Purchase::with(['supplier', 'details', 'payments'])->findOrFail($id);
        return view('stock.purchase_profile', ['purchase' => $purchase]);
    }

    /** Edit draft page (same form as purchase, pre-filled) */
    public function editDraft($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $draft = PurchaseDraft::with('supplier')->findOrFail($id);
        return view('stock.purchase', ['draft' => $draft, 'is_edit' => true]);
    }
}
