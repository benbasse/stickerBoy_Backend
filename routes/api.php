<?php

use App\Http\Controllers\AdsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BictorysWebhookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryToteBagController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StickerController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\ToteBagController;
use App\Models\Sticker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BroadcastController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['auth:api'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
});


Route::middleware(['auth:api', 'access:admin'])->group(function () {
    //category routes
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

    //subcategory routes
    Route::post('subcategories', [SubCategoryController::class, 'store']);
    Route::put('subcategories/{id}', [SubCategoryController::class, 'update']);
    Route::delete('subcategories/{id}', [SubCategoryController::class, 'destroy']);

    //Stickers routes
    Route::get('stickers', [StickerController::class, 'index']);
    Route::post('stickers', [StickerController::class, 'store']);
    Route::put('stickers/{id}', [StickerController::class, 'update']);
    Route::delete('stickers/{id}', [StickerController::class, 'destroy']);

    //CategoryTB
    Route::get('categoryTB', [CategoryToteBagController::class, 'index']);
    Route::post('categoryTB', [CategoryToteBagController::class, 'store']);
    Route::get('categoryTB/{id}', [CategoryToteBagController::class, 'show']);
    Route::put('categoryTB/{id}', [CategoryToteBagController::class, 'update']);
    Route::delete('categoryTB/{id}', [CategoryToteBagController::class, 'destroy']);

    //ToteBags routes
    Route::get('toteBags', [ToteBagController::class, 'index']);
    Route::post('toteBags', [ToteBagController::class, 'store']);
    Route::put('toteBags/{id}', [ToteBagController::class, 'update']);
    Route::delete('toteBags/{id}', [ToteBagController::class, 'destroy']);

    //Orders routes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);
    Route::patch('/orders/status/{order}', [OrderController::class, 'updateStatus']);

    //Collections routes
    // Route::Post('collections', [CollectionController::class, 'store']);
    Route::put('collections/{collection}', [CollectionController::class, 'update']);
    Route::delete('collections/{collection}', [CollectionController::class, 'destroy']);
});

Route::post('/orders', [OrderController::class, 'store']);
Route::post('/orders/{order}/pay', [OrderController::class, 'pay']);
Route::post('/webhooks/bictorys', [BictorysWebhookController::class, 'handle'])
    ->name('bictorys.webhook');

Route::get('collections', [CollectionController::class, 'index']);
Route::post('collections', [CollectionController::class, 'store']);
Route::get('collections/{collection}', [CollectionController::class, 'show']);
Route::get('categories', [CategoryController::class, 'index']);
Route::get('subcategories', [SubCategoryController::class, 'index']);
Route::get('stickers', [StickerController::class, 'index']);
Route::get('stickers/{sticker}', [StickerController::class, 'show']);
Route::get('toteBags', [ToteBagController::class, 'index']);
Route::get('toteBags/{toteBag}', [ToteBagController::class, 'show']);
Route::get('toteBags/{id}', [ToteBagController::class, 'show']);

Route::get('categoryTB', [CategoryToteBagController::class, 'index']);

// Vérification de la référence de paiement (callback ou redirection)
Route::get('/orders/verify-payment', [OrderController::class, 'verifyPaymentReference']);
Route::get('/orders/{order}', [OrderController::class, 'show']);

// Ads routes
Route::get('ads', [AdsController::class, 'index']);
Route::get('ads/{id}', [AdsController::class, 'show']);
Route::post('ads', [AdsController::class, 'store']);
Route::put('ads/{id}', [AdsController::class, 'update']);
Route::delete('ads/{id}', [AdsController::class, 'destroy']);
Route::patch('ads/{id}/toggle', [AdsController::class, 'toggleStatus']);
Route::get('ads/list/actives', [AdsController::class, 'getActiveAds']);


Route::middleware('auth:api')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});
// Route::post('/broadcasting/auth', [BroadcastController::class, 'authenticate']);
// routes/api.php
Route::post('/broadcasting/auth', [BroadcastController::class, 'authenticate']);
