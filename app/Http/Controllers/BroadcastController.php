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

        if (!$user) {
            abort(403, 'User not authenticated');
        }

        // Injecter l'utilisateur dans la request pour Broadcast::auth()
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return Broadcast::auth($request);
    }
}
