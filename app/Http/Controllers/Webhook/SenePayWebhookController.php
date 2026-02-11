<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SenePayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-SENEPAY-SIGNATURE');
        $secret = config('services.senepay.webhook_secret');

        if (!$signature || !$this->verifySignature($payload, $signature, $secret)) {
            Log::warning('SenePay webhook invalid signature');
            return response('Invalid signature', Response::HTTP_UNAUTHORIZED);
        }

        $event = json_decode($payload, true);

        match ($event['type'] ?? null) {
            'payment.completed' => $this->handlePaymentCompleted($event['data']),
            'payment.failed'    => $this->handlePaymentFailed($event['data']),
            default             => Log::info('SenePay event ignored', $event),
        };

        return response()->json(['received' => true]);
    }

    private function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $parts = collect(explode(',', $signature))
            ->mapWithKeys(fn ($part) => explode('=', $part, 2));

        $timestamp = $parts['t'] ?? null;
        $hash = $parts['v1'] ?? null;

        if (!$timestamp || !$hash) {
            return false;
        }

        // Expiration 5 minutes
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            $secret
        );

        return hash_equals($hash, $expected);
    }

    private function handlePaymentCompleted(array $data): void
    {
        // Exemple : tokenPay ou reference
        $paymentReference = $data['reference'] ?? null;

        if (!$paymentReference) return;

        $order = \App\Models\Order::where('payment_reference', $paymentReference)->first();

        if (!$order) return;

        $order->update([
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        // Optionnel : notifier admin / client
    }

    private function handlePaymentFailed(array $data): void
    {
        $paymentReference = $data['reference'] ?? null;

        if (!$paymentReference) return;

        \App\Models\Order::where('payment_reference', $paymentReference)
            ->update(['payment_status' => 'failed']);
    }
}
