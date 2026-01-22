<?php

namespace App\Services\Bictorys;

use Illuminate\Support\Facades\Http;

class BictorysPaymentService
{
    public function createCharge(array $payload): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Api-Key' => config('services.bictorys.api_key'),
        ])->post(
            config('services.bictorys.base_url') . '/pay/v1/charges',
            $payload
        );

        if ($response->failed()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }
}
