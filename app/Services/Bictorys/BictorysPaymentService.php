<?php

namespace App\Services\Bictorys;

use Illuminate\Support\Facades\Http;

class BictorysPaymentService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.bictorys.base_url');
        $this->apiKey  = config('services.bictorys.api_key');
    }

    public function createPayment(array $data): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
        ])->post($this->baseUrl . '/payments', $data);

        if ($response->failed()) {
            throw new \Exception('Bictorys payment failed');
        }

        return $response->json();
    }
}

