<?php

namespace App\Http\Controllers;

use App\Models\Ads;
use App\Traits\apiResponseTrait;
use Illuminate\Http\Request;

class AdsController extends Controller
{
    use apiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ads = Ads::orderBy('order', 'asc')->get();
        return $this->succesResponse($ads, 'Ads retrieved successfully', 200);
    }


    public function getActiveAds()
    {
        $ads = Ads::where('status', 'active')->orderBy('order', 'asc')->get();
        return $this->succesResponse($ads, 'Active ads retrieved successfully', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse

     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cta_text' => 'required|string|max:100',
            'cta_link' => 'required|string|max:255',
            'theme' => 'required|in:warm,cool,nature,dark',
            'target' => 'required|in:homepage,category,collection',
            'status' => 'sometimes|in:active,inactive',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'order' => 'nullable|integer|min:0'
        ]);

        $ad = Ads::create(
            [
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'description' => $request->description,
                'cta_text' => $request->cta_text,
                'cta_link' => $request->cta_link,
                'theme' => $request->theme,
                'target' => $request->target,
                'status' => $request->status ?? 'active',
                'image' => $request->hasFile('image') ? $this->storeImage($request->file('image')) : null,
                'order' => $request->order ?? 0,
            ]
        );

        return $this->succesResponse($ad, 'Ad created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $ads = Ads::find($id);
        if (!$ads) {
            return $this->errorResponse('Ad not found', 404);
        }
        return $this->succesResponse($ads, 'Ad retrieved successfully', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cta_text' => 'sometimes|string|max:100',
            'cta_link' => 'sometimes|string|max:255',
            'theme' => 'sometimes|in:warm,cool,nature,dark',
            'target' => 'sometimes|in:homepage,category,collection',
            'status' => 'sometimes|in:active,inactive',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'order' => 'nullable|integer|min:0'
        ]);

        $ad = Ads::find($id);
        if (!$ad) {
            return $this->errorResponse('Ad not found', 404);
        }

        $ad->update($request->only([
            'title',
            'subtitle',
            'description',
            'cta_text',
            'cta_link',
            'theme',
            'target',
            'status',
            'order'
        ]));

        if ($request->hasFile('image')) {
            $ad->image = $this->storeImage($request->file('image'));
            $ad->save();
        }

        return $this->succesResponse($ad, 'Ad updated successfully', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $ad = Ads::find($id);
        if (!$ad) {
            return $this->errorResponse('Ad not found', 404);
        }

        $ad->delete();
        return $this->succesResponse(null, 'Ad deleted successfully', 200);
    }

    public function storeImage($image)
    {
        return $image->store('ads', 'public');
    }

    //toggleStatus
    public function toggleStatus($id)
    {
        $ad = Ads::find($id);
        if (!$ad) {
            return $this->errorResponse('Ad not found', 404);
        }

        $ad->status = ($ad->status === 'active') ? 'inactive' : 'active';
        $ad->save();

        return $this->succesResponse($ad, 'Ad status toggled successfully', 200);
    }
}
