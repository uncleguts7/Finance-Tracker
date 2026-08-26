<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Index method
    function index()
    {
        $categories = Category::all();
        return response()->json($categories, 200);
    }

    // Store method
    function store(Request $request)
    {
        $request->validate([
            'name'=> 'required|string',
            'default_type'=> 'required|string|in:fixed,variable',
        ]);

        $category = Category::create([
            'name'=>$request->name,
            'default_type'=>$request->default_type,
        ]);

        return response()->json($category, 201);
    }

    // Update method
    function update(Request $request, $id)
    {
        $request->validate([
            'name'=> 'required|string',
            'default_type'=> 'required|string|in:fixed,variable',
        ]);

        $category = Category::find($id);
        if(!$category) {
            return response()->json(['message'=>'Category not found'], 404);
        }

        $category->update([
            'name'=> $request->name,
            'default_type'=> $request->default_type,
        ]);

        return response()->json($category, 200);
    }

    // Destroy method
    function destroy($id)
    {
        $category = Category::find($id);
        if(!$category) {
            return response()->json(['message'=>'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message'=> 'deleted successfully'], 200);
    }
}
