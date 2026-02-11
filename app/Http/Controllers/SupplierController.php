<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index()
    {
        return view('modules.supplier');
    }

    public function getSuppliers()
    {
        return Supplier::orderBy('id', 'DESC')->paginate(10);
    }

    /** All suppliers for searchable select (e.g. purchase page) */
    public function getAllSuppliers()
    {
        return Supplier::orderBy('supplier_name', 'ASC')->get(['id', 'supplier_name', 'phone']);
    }

    public function getTotalCount()
    {
        return response()->json(['count' => Supplier::count()]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $supplier = new Supplier();
        $supplier->supplier_name = $request->supplier_name;
        $supplier->phone = $request->phone;
        $supplier->notes = $request->notes;
        $supplier->added_by = $user->user_name ?? 'system';
        $supplier->user_id = $user->id ?? 1;

        $supplier->save();

        return response()->json($supplier);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $user = Auth::user();
        $previousData = $supplier->toArray();

        $supplier->supplier_name = $request->supplier_name;
        $supplier->phone = $request->phone;
        $supplier->notes = $request->notes;
        $supplier->updated_by = $user->user_name ?? 'system_update';
        $supplier->save();

        History::create([
            'operation' => 'update',
            'source' => 'supplier',
            'previous_data' => $previousData,
            'new_data' => $supplier->fresh()->toArray(),
            'added_by' => $user->user_name ?? 'system',
            'user_id' => $user->id ?? null,
            'added_at' => now(),
        ]);

        return response()->json($supplier);
    }

    public function show(Supplier $supplier)
    {
        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        $user = Auth::user();
        $previousData = $supplier->toArray();

        History::create([
            'operation' => 'delete',
            'source' => 'supplier',
            'previous_data' => $previousData,
            'new_data' => null,
            'added_by' => $user->user_name ?? 'system',
            'user_id' => $user->id ?? null,
            'added_at' => now(),
        ]);

        $supplier->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
