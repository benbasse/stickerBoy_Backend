<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use App\Traits\apiResponseTrait;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    use apiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subcategories = SubCategory::with('category', 'stickers')->get();
        return $this->succesResponse($subcategories, 'SubCategories retrieved successfully', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|uuid|exists:categories,id',
        ]);

        $subcategory = SubCategory::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
        ]);

        return $this->succesResponse($subcategory, 'SubCategory created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $subCategory = SubCategory::with('category', 'stickers')->find($id);
        if (!$subCategory) {
            return $this->errorResponse('SubCategory not found', 404);
        }
        return $this->succesResponse($subCategory, 'SubCategory retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $subCategory = SubCategory::find($id);
        if (!$subCategory) {
            return $this->errorResponse('SubCategory not found', 404);
        }
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|uuid|exists:categories,id',
        ]);
        $subCategory->update($request->only(['name', 'category_id']));
        return $this->succesResponse($subCategory, 'SubCategory updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $subCategory = SubCategory::find($id);
        if (!$subCategory) {
            return $this->errorResponse('SubCategory not found', 404);
        }
        $subCategory->delete();
        return $this->succesResponse(null, 'SubCategory deleted successfully');
    }
}
