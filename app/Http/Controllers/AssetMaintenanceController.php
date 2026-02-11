<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssetMaintenanceController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $asset = Asset::all();
        return view('asset.maintenance',compact('asset'));
    }

    public function getassetsmaintenance()
    {
        return AssetMaintenance::with('asset')->orderBy('id', 'DESC')->paginate(10);
    }

    /**
     * Return all assets (for dropdowns / payment)
     */
    

    public function store(Request $request)
    {
        $user = Auth::user();

        $asset = new AssetMaintenance();
        $asset->asset_id = $request->asset_id;
        $asset->maintenance_type = $request->maintenance_type;
        $asset->maintenance_date = $request->maintenance_date;
        $asset->next_maintenance_date = $request->next_maintenance_date;
        $asset->description = $request->description;
        $asset->performed_by = $request->performed_by;
        $asset->cost = $request->cost;
        $asset->added_by = $user->user_name ?? 'system';
        $asset->user_id = $user->id ?? 1;

        $asset->save();

        return response()->json($asset);
    }

    public function update(Request $request, AssetMaintenance $asset)
    {
        $user = Auth::user();

        $asset->asset_id = $request->asset_id;
        $asset->maintenance_type = $request->maintenance_type;
        $asset->maintenance_date = $request->maintenance_date;
        $asset->next_maintenance_date = $request->next_maintenance_date;
        $asset->description = $request->description;
        $asset->performed_by = $request->performed_by;
        $asset->cost = $request->cost;
        $asset->updated_by = $user->user_name ?? 'system_update';
        $asset->save();

        return response()->json($asset);
    }

    public function show(AssetMaintenance $asset)
    {
        return response()->json($asset);
    }

    public function destroy(AssetMaintenance $asset)
    {
        $asset->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
