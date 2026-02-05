<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    public function authenticate(Request $request)
    {
        if (!$request->bearerToken()) {
            abort(401, 'No token');
        }

        $user = JWTAuth::setToken($request->bearerToken())->authenticate();

        if (!$user || $user->role !== 'admin') {
            abort(403);
        }

        return Broadcast::auth($request);
    }
}
