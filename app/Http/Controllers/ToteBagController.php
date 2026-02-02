<?php

namespace App\Http\Controllers;

use App\Models\ToteBag;
use App\Traits\apiResponseTrait;
use Illuminate\Http\Request;

class ToteBagController extends Controller
{
    use apiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $totebags = ToteBag::with('categoryToteBag')->get();
        return $this->succesResponse($totebags, 'Tote Bags retrieved successfully');
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|integer',
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'stock' => 'required|integer',
            'category_tote_bag_id' => 'required|exists:category_tote_bags,id',
        ]);


        // $imagePath = $this->storeImage($request->file('image'));
        $imagePaths = $this->storeImages($request->file('images'));

        $totebag = ToteBag::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'images' => json_encode($imagePaths), // 👈 clé ici
            'stock' => $request->stock,
            'category_tote_bag_id' => $request->category_tote_bag_id,
        ]);

        return $this->succesResponse(
            $totebag,
            'Tote Bag created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $toteBag = ToteBag::findOrFail($id);
        return $this->succesResponse($toteBag->load('categoryToteBag'), 'Tote Bag retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|integer',
            // 'image' => 'sometimes|image|max:2048',
            'images' => 'sometimes|array|min:1',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'stock' => 'sometimes|required|integer',
            'category_tote_bag_id' => 'sometimes|required|exists:category_tote_bags,id',
        ]);

        // $data = $request->only(['name', 'description', 'price', 'stock', 'category_tote_bag_id']);
        $toteBag = ToteBag::findOrFail($id);
        $toteBag->name = $request->input('name', $toteBag->name);
        $toteBag->description = $request->input('description', $toteBag->description);
        $toteBag->price = $request->input('price', $toteBag->price);
        $toteBag->stock = $request->input('stock', $toteBag->stock);
        $toteBag->category_tote_bag_id = $request->input('category_tote_bag_id', $toteBag->category_tote_bag_id);
        if ($request->hasFile('images')) {
            $toteBag->images = json_encode($this->storeImages($request->file('images')));
        }

        $toteBag->update();

        return $this->succesResponse($toteBag, 'Tote Bag updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $toteBag = ToteBag::findOrFail($id);
        $toteBag->delete();
        return $this->succesResponse(null, 'Tote Bag deleted successfully', 204);
    }

    public function storeImage($image)
    {
        return $image->store('tote_bags', 'public');
    }

    //storageformultipleimages
    public function storeImages($images)
    {
        $imagePaths = [];
        foreach ($images as $image) {
            $imagePaths[] = $image->store('tote_bags', 'public');
        }
        return $imagePaths;
    }
}
