<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
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

        $supplier->supplier_name = $request->supplier_name;
        $supplier->phone = $request->phone;
        $supplier->notes = $request->notes;
        $supplier->updated_by = $user->user_name ?? 'system_update';
        $supplier->save();

        return response()->json($supplier);
    }

    public function show(Supplier $supplier)
    {
        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
