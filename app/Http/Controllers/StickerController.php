<?php

namespace App\Http\Controllers;

use App\Models\Sticker;
use App\Traits\apiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StickerController extends Controller
{
    use apiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stickers = Sticker::with(['category', 'subcategory'])->get();
        return $this->succesResponse($stickers, 'Stickers retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'required|in:jpeg,png,jpg,gif,svg|base64image',
            'category_id' => 'required|uuid|exists:categories,id',
            'sub_category_id' => 'required|uuid|exists:sub_categories,id',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'quantity' => 'required|integer',
        ]);

        $sticker = Sticker::create([
            'name' => $request->name,
            'image' => $this->storeImage($request->image),
            'category_id' => $request->category_id,
            'sub_category_id' => $request->sub_category_id,
            'price' => $request->price,
            'description' => $request->description,
            'quantity' => $request->quantity,
        ]);

        return $this->succesResponse($sticker, 'Sticker created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sticker $sticker)
    {
        $sticker = Sticker::with(['category', 'subcategory'])->find($sticker->id);
        if (!$sticker) {
            return $this->errorResponse('Sticker not found', 404);
        }
        return $this->succesResponse($sticker, 'Sticker retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sticker $sticker)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'image' => 'sometimes|required|in:jpeg,png,jpg,gif,svg|base64image',
            'category_id' => 'sometimes|required|uuid|exists:categories,id',
            'sub_category_id' => 'sometimes|required|uuid|exists:sub_categories,id',
            'price' => 'sometimes|required|numeric',
            'description' => 'nullable|string',
            'quantity' => 'sometimes|required|integer',
        ]);

        $data = $request->only(['name', 'category_id', 'sub_category_id', 'price', 'description', 'quantity']);

        if ($request->has('image')) {
            // Delete old image
            Storage::disk('public')->delete($sticker->image);
            // Store new image
            $data['image'] = $this->storeImage($request->image);
        }

        $sticker->update($data);

        return $this->succesResponse($sticker, 'Sticker updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sticker $sticker)
    {
        // Delete image
        Storage::disk('public')->delete($sticker->image);
        $sticker->delete();
        return $this->succesResponse(null, 'Sticker deleted successfully');
    }

    public function storeImage($image)
    {
        return $image->store('stickers', 'public');
    }
}
