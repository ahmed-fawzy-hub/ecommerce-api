<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();
        return response()->json([
            'status' => 'success',
            'data' => $categories,
            'message' => 'Categories retrieved successfully',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validate request
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        // generate slug if not provided
        $slug = Str::slug($request->name);
        $count = Category::where('slug', $slug)->count();
        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
        }
        // create category
        $category = Category::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
            'parent_id' => $request->parent_id,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $category,
            'message' => 'Category created successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $category->load(['parent', 'children']);
        return response()->json([
            'status' => 'success',
            'data' => $category,
            'message' => 'Category retrieved successfully',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        //validate request
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'parent_id' => 'nullable|exists:categories,id',
        ]);
        if($request->has('name') && $request->name != $category->name) {
            $slug = Str::slug($request->name);
            $count = Category::where('slug', $slug)
            ->where('id', '!=', $category->id)
            ->count();
            if ($count > 0) {
                $slug = $slug . '-' . ($count + 1);
            }
            $category->name = $request->name;
            $category->slug = $slug;
        }
        if($request->has('parent_id') && $request->parent_id != $category->parent_id) {
            $category->parent_id = $request->parent_id;
        }
        if($request->has('description') && $request->description != $category->description) {
            $category->description = $request->description;
        }
        if($request->has('is_active') && $request->is_active != $category->is_active) {
            $category->is_active = $request->is_active;
        }
        //update category
        $category->save();
        return response()->json([
            'status' => 'success',
            'data' => $category,
            'message' => 'Category updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        foreach($category->children as $child) {
            $child->parent_id = $category->parent_id;
            $child->save();
        }
        $category->delete();
        return response()->json([
            'status' => 'success',
            'data' => $category,
            'message' => 'Category deleted successfully',
        ]);
    }
}
