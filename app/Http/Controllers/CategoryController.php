<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Index method
    public function index()
    {
        $categories = auth()->user()->categories()->get();

        return CategoryResource::collection($categories);
    }

    // Store method
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'default_type' => 'required|string|in:fixed,variable',
        ]);

        $category = Category::forceCreate([
            'name' => $request->name,
            'default_type' => $request->default_type,
            'user_id' => auth()->id(),
        ]);

        return CategoryResource::make($category)->response()->setStatusCode(201);
    }

    // Update method
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'default_type' => 'required|string|in:fixed,variable',
        ]);

        $category = auth()->user()->categories()->find($id);
        if (! $category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->update([
            'name' => $request->name,
            'default_type' => $request->default_type,
        ]);

        return new CategoryResource($category);
    }

    // Destroy method
    public function destroy($id)
    {
        $category = auth()->user()->categories()->find($id);
        if (! $category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'deleted successfully'], 200);
    }
}
