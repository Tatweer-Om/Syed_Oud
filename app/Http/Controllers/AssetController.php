<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssetController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

         

        return view('asset.index');
    }

    public function getassets()
    {
        return Asset::orderBy('id', 'DESC')->paginate(10);
    }

    /**
     * Return all assets (for dropdowns / payment)
     */
    

    public function store(Request $request)
    {
        $user = Auth::user();

        $asset = new Asset();
        $asset->name = $request->name;
        $asset->department = $request->department;
        $asset->purchase_date = $request->purchase_date;
        $asset->next_maintenance_date = $request->next_maintenance_date;
        $asset->purchase_cost = $request->purchase_cost ?? 0;
        $asset->usage = $request->usage ?? "";
        $asset->status = $request->status ?? 1;
        $asset->added_by = $user->user_name ?? 'system';
        $asset->user_id = $user->id ?? 1;

        $asset->save();

        return response()->json($asset);
    }

    public function update(Request $request, asset $asset)
    {
        $user = Auth::user();

        $asset->name = $request->name;
        $asset->department = $request->department;
        $asset->purchase_date = $request->purchase_date;
        $asset->next_maintenance_date = $request->next_maintenance_date;
        $asset->purchase_cost = $request->purchase_cost ?? 0;
        $asset->usage = $request->usage ?? "";
        $asset->status = $request->status ?? 1;
        $asset->updated_by = $user->user_name ?? 'system_update';
        $asset->save();

        return response()->json($asset);
    }

    public function show(asset $asset)
    {
        return response()->json($asset);
    }

    public function destroy(asset $asset)
    {
        $asset->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
