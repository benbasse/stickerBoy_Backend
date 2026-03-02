<?php
namespace App\Http\Controllers;

use App\Services\NabooPayService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


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

    /**
     * Vérifier le statut d'un paiement auprès de NabooPay
     */
    public function checkPaymentStatus($id, NabooPayService $nabooPay)
    {
        $order = Order::findOrFail($id);

        if (!$order->payment_reference) {
            return response()->json([
                'message' => 'Aucune référence de paiement trouvée',
                'order_status' => $order->status,
                'payment_status' => $order->payment_status,
            ], 400);
        }

        // Récupérer le statut auprès de NabooPay
        $nabooPayStatus = $nabooPay->getTransactionStatus($order->payment_reference);

        Log::info('NabooPay status check', [
            'order_id' => $order->id,
            'payment_reference' => $order->payment_reference,
            'naboopay_response' => $nabooPayStatus,
        ]);

        return response()->json([
            'order_id' => $order->id,
            'order_status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_reference' => $order->payment_reference,
            'naboopay_status' => $nabooPayStatus,
        ]);
    }

    /**
     * Synchroniser le statut du paiement avec NabooPay
     */
    public function syncPaymentStatus($id, NabooPayService $nabooPay)
    {
        $order = Order::findOrFail($id);

        if (!$order->payment_reference) {
            return response()->json([
                'message' => 'Aucune référence de paiement trouvée',
            ], 400);
        }

        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Commande déjà marquée comme payée',
                'order' => $order,
            ]);
        }

        // Récupérer le statut auprès de NabooPay
        $nabooPayStatus = $nabooPay->getTransactionStatus($order->payment_reference);
        $transactionStatus = $nabooPayStatus['transaction_status'] ?? null;

        Log::info('NabooPay sync status', [
            'order_id' => $order->id,
            'naboopay_status' => $transactionStatus,
            'full_response' => $nabooPayStatus,
        ]);

        // Mettre à jour selon le statut
        if (in_array($transactionStatus, ['successful', 'completed', 'paid', 'success'])) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'paid_at' => now(),
            ]);

            return response()->json([
                'message' => 'Paiement confirmé et synchronisé',
                'order' => $order->fresh(),
                'naboopay_status' => $transactionStatus,
            ]);
        }

        if (in_array($transactionStatus, ['failed', 'cancelled', 'expired'])) {
            $order->update([
                'payment_status' => 'failed',
            ]);

            return response()->json([
                'message' => 'Paiement échoué',
                'order' => $order->fresh(),
                'naboopay_status' => $transactionStatus,
            ]);
        }

        return response()->json([
            'message' => 'Statut non reconnu ou en attente',
            'order' => $order,
            'naboopay_status' => $transactionStatus,
            'naboopay_response' => $nabooPayStatus,
        ]);
    }
}
