<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Traits\apiResponseTrait;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    use apiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $collections = Collection::with('products')->get();
        return $this->succesResponse(200, 'done', $collections);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:collections,name',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'slug' => 'required|string|unique:collections,slug',
            'bundle_price' => 'nullable|integer|min:0',
        ]);

        $collection = Collection::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active,
            'slug' => $request->slug,
            'bundle_price' => $request->bundle_price,
        ]);

        return $this->succesResponse(201, 'Collection created successfully', $collection);
    }

    /**
     * Display the specified resource.
     */
    public function show(Collection $collection)
    {
        return $this->succesResponse(200, 'done', $collection);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Collection $collection)
    {
        $request->validate([
            'name' => 'sometimes|required|string|unique:collections,name,' . $collection->id,
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|required|boolean',
            'slug' => 'sometimes|required|string|unique:collections,slug,' . $collection->id,
            'bundle_price' => 'sometimes|nullable|integer|min:0',
        ]);

        $collection->update($request->only([
            'name',
            'description',
            'is_active',
            'slug',
            'bundle_price',
        ]));

        return $this->succesResponse(200, 'Collection updated successfully', $collection);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Collection $collection)
    {
        $collection->delete();
        return $this->succesResponse(200, 'Collection deleted successfully', null);
    }
}
