<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index()
    {
        return view('modules.category');
    }

    public function getCategories()
    {
        return Category::orderBy('id', 'DESC')->paginate(10);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $category = new Category();
        $category->category_name = $request->category_name;
        $category->category_name_ar = $request->category_name_ar;
        $category->notes = $request->notes;
        $category->added_by = $user->name ?? 'system';
        $category->user_id = $user->id ?? 1;

        $category->save();

        return response()->json($category);
    }

    public function update(Request $request, Category $category)
    {
        $user = Auth::user();
        $previousData = $category->toArray();

        $category->category_name = $request->category_name;
        $category->category_name_ar = $request->category_name_ar;
        $category->notes = $request->notes;
        $category->updated_by = $user->name ?? 'system_update';
        $category->save();

        History::create([
            'operation' => 'update',
            'source' => 'category',
            'previous_data' => $previousData,
            'new_data' => $category->fresh()->toArray(),
            'added_by' => $user->name ?? $user->user_name ?? 'system',
            'user_id' => $user->id ?? null,
            'added_at' => now(),
        ]);

        return response()->json($category);
    }

    public function show(Category $category)
    {
        return response()->json($category);
    }

    public function destroy(Category $category)
    {
        $user = Auth::user();
        $previousData = $category->toArray();

        History::create([
            'operation' => 'delete',
            'source' => 'category',
            'previous_data' => $previousData,
            'new_data' => null,
            'added_by' => $user->name ?? $user->user_name ?? 'system',
            'user_id' => $user->id ?? null,
            'added_at' => now(),
        ]);

        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
