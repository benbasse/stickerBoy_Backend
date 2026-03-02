<?php

namespace App\Http\Controllers;

use App\Traits\apiResponseTrait;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    use apiResponseTrait;

    /**
     * Stocker une nouvelle subscription push
     */
    public function store(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $user = $request->user();

        $user->updatePushSubscription(
            $request->input('endpoint'),
            $request->input('keys.p256dh'),
            $request->input('keys.auth')
        );

        return $this->succesResponse(null, 'Subscription enregistrée avec succès', 201);
    }

    /**
     * Supprimer une subscription push
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
        ]);

        $user = $request->user();

        $user->deletePushSubscription($request->input('endpoint'));

        return $this->succesResponse(null, 'Subscription supprimée avec succès', 200);
    }

    /**
     * Récupérer la clé publique VAPID
     */
    public function vapidPublicKey()
    {
        return response()->json([
            'vapid_public_key' => config('webpush.vapid.public_key'),
        ]);
    }
}
