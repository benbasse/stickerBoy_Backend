<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

class SenePayService
{
    protected string $baseUrl;
    protected string $publicKey;
    protected string $secretKey;

    public function __construct()
    {
        $this->baseUrl  = config('services.senepay.base_url');
        $this->publicKey = config('services.senepay.public_key');
        $this->secretKey = config('services.senepay.secret_key');

    }

    public function initiatePayment(Order $order): array
    {
        $response = Http::withHeaders([
            'X-Api-Key'    => $this->publicKey,
            'X-Api-Secret' => $this->secretKey,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/payments/initiate', [
            'amount' => (int) $order->total_price,
            'currency' => 'XOF',
            'orderId' => $order->reference,

            'customerName' => trim(
                $order->customer->firstname . ' ' . $order->customer->lastname
            ),

            // 'customerPhone' => '+221' . ltrim($order->customer->phone, '0'),
            'customerPhone' => $order->customer->phone,

            // URL FRONTEND
            // 'returnUrl' => config('app.frontend_url') . '/payment/success',
            'returnUrl' => 'https://429c-41-83-170-180.ngrok-free.app/',

            'metadata' => [
                'order_id' => $order->id,
                'description' => 'Paiement commande ' . $order->reference,
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception(
                'SenePay error (' . $response->status() . '): ' . $response->body()
            );
        }

        return $response->json();
    }
}
