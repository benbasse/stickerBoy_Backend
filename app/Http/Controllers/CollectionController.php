<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Sticker;
use App\Models\ToteBag;
use App\Traits\apiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CollectionController extends Controller
{
    use apiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $collections = Collection::where('is_active', true)
            // ->with('products.product')
            ->get();
        return $this->succesResponse($collections, 'Collections retrieved successfully');
    }



    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|unique:collections,name',
    //         'description' => 'nullable|string',
    //         'is_active' => 'required|boolean',
    //         'theme' => 'required|string|unique:collections,theme',
    //         'itemCount' => 'required|integer|min:1',
    //         'bundle_price' => 'nullable|integer|min:0',

    //         //items
    //         'collection_products' => 'required|array|min:1',
    //         'collection_products.*.product_type' => 'required|string',
    //         'collection_products.*.product_id' => 'required|integer',
    //         'collection_products.*.quantity' => 'required|integer|min:1',
    //     ]);

    //     /**
    //      *Cequelebackendattend
    //         {
    //         "name": "Ma collection",
    //         "description": "Une description",
    //         "itemCount": 5,
    //         "theme": "gaming",
    //         "is_active": true,
    //         "bundle_price": 1000,

    //         "collection_products": [
    //             {
    //             "product_type": "sticker",
    //             "product_id": 1,
    //             "quantity": 2
    //             },
    //             {
    //             "product_type": "tote_bag",
    //             "product_id": 3,
    //             "quantity": 1
    //             },
    //             {
    //             "product_type": "tshirt",
    //             "product_id": 5,
    //             "quantity": 1
    //             }
    //         ]
    //         }
    //      */

    //     $collection = Collection::create([
    //         'name' => $request->name,
    //         'description' => $request->description,
    //         'is_active' => $request->is_active,
    //         'bundle_price' => $request->bundle_price,
    //         'theme' => $request->theme,
    //         'itemCount' => $request->itemCount,
    //     ]);

    //     //fortheitems
    //     foreach ($request->collection_products as $item) {
    //         $this->resolveProduct($item['product_type'], $item['product_id']);

    //         $collection->collection_products()->create([
    //             'product_type' => $item['product_type'],
    //             'product_id' => $item['product_id'],
    //             'quantity' => $item['quantity'],
    //         ]);
    //     }

    //     return $this->succesResponse(201, 'Collection created successfully', $collection);
    // }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:collections,name',
            'description' => 'nullable|string',
            'theme' => 'required|string',
            // 'theme' => 'required|string|unique:collections,theme',
            'is_active' => 'required|boolean',
            'itemCount' => 'required|integer|min:1',
            'bundle_price' => 'nullable|integer|min:0',

            'collection_products' => 'required|array|min:1',
            'collection_products.*.product_type' => 'required|string',
            'collection_products.*.product_id' => 'required|uuid',
            'collection_products.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request, &$collection) {

            // Vérification itemCount
            $totalQty = collect($request->collection_products)->sum('quantity');
            if ($totalQty !== $request->itemCount) {
                throw ValidationException::withMessages([
                    'itemCount' => 'itemCount does not match total quantity'
                ]);
            }

            $collection = Collection::create($request->only([
                'name',
                'description',
                'theme',
                'is_active',
                'bundle_price',
                'itemCount'
            ]));

            foreach ($request->collection_products as $item) {
                $product = $this->resolveProduct(
                    $item['product_type'],
                    $item['product_id']
                );

                $collection->products()->create([
                    'product_type' => $item['product_type'],
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'image' => $product->image,
                ]);
            }
        });

        // return $this->succesResponse(
        //     $collection->load('products'),
        //     'Collection created successfully',
        //     201
        // );
        return response()->json([
            'data' => $collection->load('products'),
            'message' => 'Collection created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $collection = Collection::with('products.sticker', 'products.toteBag')->find($id);
        if (!$collection) {
            return $this->errorResponse('Collection not found', 404);
        }
        return $this->succesResponse($collection, 'Collection retrieved successfully');
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $collection = Collection::findOrFail($id);
        $request->validate([
            'name' => 'sometimes|required|string|unique:collections,name,' . $collection->id,
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|required|boolean',
            'bundle_price' => 'sometimes|nullable|integer|min:0',
            'theme' => 'sometimes|required|string|unique:collections,theme,' . $collection->id,
            'itemCount' => 'sometimes|required|integer|min:1',

            //items
            'collection_products' => 'required|array|min:1',
            'collection_products.*.product_type' => 'required|string',
            'collection_products.*.product_id' => 'required|integer',
            'collection_products.*.quantity' => 'required|integer|min:1',
        ]);

        $collection->update($request->only([
            'name',
            'description',
            'is_active',
            'bundle_price',
            'theme',
            'itemCount',
        ]));

        foreach ($request->collection_products as $item) {
            $this->resolveProduct($item['product_type'], $item['product_id']);

            $collection->products()->create([
                'product_type' => $item['product_type'],
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return $this->succesResponse(
            $collection->load('products.product'),
            'Collection updated successfully',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $collection = Collection::findOrFail($id);
        $collection->delete();
        return $this->succesResponse(null, 'Collection deleted successfully', 200);
    }

    //findtheproductsbycollectionid
    private function resolveProduct(string $type, string $id)
    {
        return match ($type) {
            'sticker' => Sticker::findOrFail($id),
            'tote_bag' => ToteBag::findOrFail($id),
            // 'tshirt' => TShirt::where('is_active', true)->findOrFail($id),
            default => throw ValidationException::withMessages([
                'product_type' => 'Invalid product type'
            ]),
        };
    }
}
