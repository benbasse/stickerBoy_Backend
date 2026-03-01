<?php
namespace App\Http\Controllers;

use App\Services\NabooPayService;
use App\Models\Order;
use Illuminate\Http\Request;


class PaymentController extends Controller
{
    public function payWithNabooPay($id, Request $request, NabooPayService $nabooPay)
    {
        $order = Order::with('orderItems.product')->findOrFail($id);

        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Commande déjà payée'
            ], 400);
        }

        // Transformer les orderItems en products pour NabooPay
        $products = $order->orderItems->map(function ($item) {
            return [
                'name' => $item->product?->name ?? 'Produit',
                'price' => (int) $item->unit_price,
                'quantity' => (int) $item->quantity,
            ];
        })->toArray();

        // Si pas d'items, créer un produit avec le total
        if (empty($products)) {
            $products = [
                [
                    'name' => 'Commande #' . $order->reference,
                    'price' => (int) $order->total_price,
                    'quantity' => 1,
                ]
            ];
        }

        // Méthodes de paiement (par défaut Orange Money et Wave)
        $paymentMethods = $request->input('payment_methods', [
            NabooPayService::PAYMENT_ORANGE_MONEY,
            NabooPayService::PAYMENT_WAVE,
        ]);

        $successUrl = config('services.naboopay.success_url');
        $errorUrl = config('services.naboopay.error_url');

        $response = $nabooPay->createTransaction(
            products: $products,
            paymentMethods: $paymentMethods,
            successUrl: $successUrl,
            errorUrl: $errorUrl
        );

        if (isset($response['order_id'])) {
            $order->update([
                'payment_provider'  => 'naboopay',
                'payment_reference' => $response['order_id'],
                'payment_link'      => $response['checkout_url'] ?? null,
            ]);

            return response()->json([
                'payment_url' => $response['checkout_url'],
                'order_id' => $response['order_id'],
            ]);
        }

        return response()->json([
            'message' => 'Erreur lors de la création du paiement',
            'error' => $response,
        ], 500);
    }
}
