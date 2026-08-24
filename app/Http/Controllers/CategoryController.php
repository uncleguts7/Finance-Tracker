<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    function index()
    {
        $categories = Category::where('is_deleted', false)->get();
        return response()->json($categories, 200);
    }

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

    function update(Request $request, $id)
    {
        $request->validate([
            'name'=> 'required|string',
            'default_type'=> 'required|string|in:fixed,variable',
        ]);

        $category = Category::find($id);
        if(!$category || $category->is_deleted) {
            return response()->json(['message'=>'Category not found'], 404);
        }

        $category->update([
            'name'=> $request->name,
            'default_type'=> $request->default_type,
        ]);

        return response()->json($category, 200);
    }

    function destroy($id)
    {
        $category = Category::find($id);
        if(!$category || $category->is_deleted) {
            return response()->json(['message'=>'Category not found'], 404);
        }

        $category->is_deleted = true;
        $category->save();

        return response()->json(['message'=> 'deleted successfully'], 200);
    }
}
