<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Traits\apiResponseTrait;
use Illuminate\Http\Request;

/**
 * Summary of ProductController
 *this controller manages the CRUD operations for products.
 *this product is for the collections products
 */
class ProductController extends Controller
{
    use apiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::all();
        return $this->succesResponse($products, 'done', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:products,name',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'sku' => 'required|string|unique:products,sku', //stock keeping unit
            'collection_id' => 'required|exists:collections,id',
            'type' => 'required|string', //sticker,tote bag,tshirt,this product can be stickers, tote bags, or shirts
        ]);

        /**
         *Cequelebackendattend
                {
                "name": "Sticker Cool",
                "description": "Un sticker trop cool",
                "price": 500,
                "sku": "STICKER-COOL-001",
                "collection_id": 1,
                "type": "sticker"
                }
         */
        //checkifthetypeissticker
        if ($request->type == "sticker") {
            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'sku' => $request->sku,
                'collection_id' => $request->collection_id,
                'type' => $request->type,
            ]);
        }



        return $this->succesResponse(201, 'Product created successfully', $product);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
