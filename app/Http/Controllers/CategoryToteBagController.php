<?php

namespace App\Http\Controllers;

use App\Models\CategoryToteBag;
use App\Traits\apiResponseTrait;
use Illuminate\Http\Request;

class CategoryToteBagController extends Controller
{
    use apiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categoryToteBags = CategoryToteBag::with('toteBags')->get();
        return $this->succesResponse($categoryToteBags, 'Category Tote Bags retrieved successfully');
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
        $categoryToteBag = CategoryToteBag::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);
        return $this->succesResponse($categoryToteBag, 'Category Tote Bag created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $categoryToteBag = CategoryToteBag::with('toteBags')->find($id);
        if (!$categoryToteBag) {
            return $this->errorResponse('Category Tote Bag not found', 404);
        }
        return $this->succesResponse($categoryToteBag, 'Category Tote Bag retrieved successfully', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $categoryToteBag = CategoryToteBag::find($id);
        if (!$categoryToteBag) {
            return $this->errorResponse('Category Tote Bag not found', 404);
        }
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $categoryToteBag->update($request->only(['name', 'description']));
        return $this->succesResponse($categoryToteBag, 'Category Tote Bag updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $categoryToteBag = CategoryToteBag::find($id);
        if (!$categoryToteBag) {
            return $this->errorResponse('Category Tote Bag not found', 404);
        }
        $categoryToteBag->delete();
        return $this->succesResponse(null, 'Category Tote Bag deleted successfully', 204);
    }
}
