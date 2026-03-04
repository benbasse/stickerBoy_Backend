<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// routes/web.php
Route::get('/payment/success', fn() => redirect(env('FRONTEND_SUCCESS_URL')));
Route::get('/payment/error', fn() => redirect(env('FRONTEND_ERROR_URL')));
  Route::get('/storage/{path}', function ($path) {
      $fullPath = storage_path('app/public/' . $path);

      if (!file_exists($fullPath)) {
          abort(404);
      }

      return response()->file($fullPath);
  })->where('path', '.*');
