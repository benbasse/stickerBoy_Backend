<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\apiResponseTrait;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use apiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::with('subcategories', 'stickers')->get();
        return $this->succesResponse($categories, 'Categories retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return $this->succesResponse($category, 'Category created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $category = Category::with('subcategories', 'stickers')->find($category->id);
        if (!$category) {
            return $this->errorResponse('Category not found', 404);
        }
        return $this->succesResponse($category, 'Category retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $category = Category::find($id);
        $category->name = $request->name;
        $category->description = $request->description;
        $category->save();

        return $this->succesResponse($category, 'Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->errorResponse('Category not found', 404);
        }
        $category->delete();
        return $this->succesResponse(null, 'Category deleted successfully');
    }
}
