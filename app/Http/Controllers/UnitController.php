<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        return view('stock.view_units');
    }

    /** Paginated list for view_units page */
    public function getUnits(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        return Unit::orderBy('unit_name', 'ASC')->paginate($perPage);
    }

    /** All units for select boxes (e.g. material popup) */
    public function getAllUnits()
    {
        return Unit::orderBy('unit_name', 'ASC')->get(['id', 'unit_name']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'unit_name' => 'required|string|max:100',
        ]);
        $unit = Unit::create(['unit_name' => $request->unit_name]);
        return response()->json([
            'status' => 'success',
            'message' => __('Unit added successfully'),
            'unit' => $unit,
        ]);
    }

    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'unit_name' => 'required|string|max:100',
        ]);
        $unit->unit_name = $request->unit_name;
        $unit->save();
        return response()->json([
            'status' => 'success',
            'message' => __('Unit updated successfully'),
            'unit' => $unit,
        ]);
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();
        return response()->json([
            'status' => 'success',
            'message' => __('Unit deleted successfully'),
        ]);
    }
}
