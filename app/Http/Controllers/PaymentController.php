<?php
namespace App\Http\Controllers;

use App\Services\NabooPayService;
use App\Services\PushNotificationService;
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
     * Synchroniser le statut du paiement avec NabooPay et déclencher le payout automatiquement
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
                'status' => 'processing',
                'paid_at' => now(),
            ]);

            // Déclencher le payout automatiquement
            $payoutResult = $this->executePayout($order, $nabooPay);

            // Envoyer notification push
            $pushService = app(PushNotificationService::class);
            $pushService->notifyPaymentReceived($order->id, (int) $order->total_price);

            return response()->json([
                'message' => 'Paiement confirmé, synchronisé et payout déclenché',
                'order' => $order->fresh(),
                'naboopay_status' => $transactionStatus,
                'payout' => $payoutResult,
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

    /**
     * Exécuter le payout pour une commande
     */
    private function executePayout(Order $order, NabooPayService $nabooPay): array
    {
        $amount = (int) $order->total_price;

        // Vérifier le montant
        if ($amount < 11 || $amount > 1500000) {
            Log::warning('Payout skipped: invalid amount', [
                'order_id' => $order->id,
                'amount' => $amount,
            ]);
            return [
                'success' => false,
                'reason' => 'Montant invalide: ' . $amount,
            ];
        }

        try {
            $response = $nabooPay->transferToMainAccountWave(
                $amount,
                'Paiement commande #' . $order->reference
            );

            Log::info('Auto payout triggered', [
                'order_id' => $order->id,
                'amount' => $amount,
                'response' => $response,
            ]);

            return [
                'success' => true,
                'amount' => $amount,
                'response' => $response,
            ];

        } catch (\Exception $e) {
            Log::error('Auto payout failed', [
                'order_id' => $order->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Déclencher manuellement le payout pour une commande payée
     */
    public function triggerPayout($id, NabooPayService $nabooPay)
    {
        $order = Order::findOrFail($id);

        // Vérifier que la commande est payée
        if ($order->payment_status !== 'paid') {
            return response()->json([
                'message' => 'La commande n\'est pas encore payée',
                'payment_status' => $order->payment_status,
            ], 400);
        }

        $amount = (int) $order->total_price;

        // Vérifier le montant
        if ($amount < 11 || $amount > 1500000) {
            return response()->json([
                'message' => 'Montant invalide pour le payout',
                'amount' => $amount,
            ], 400);
        }

        try {
            // Effectuer le payout via Wave
            $response = $nabooPay->transferToMainAccountWave(
                $amount,
                'Paiement commande #' . $order->reference
            );

            Log::info('Manual payout triggered', [
                'order_id' => $order->id,
                'amount' => $amount,
                'response' => $response,
            ]);

            // Envoyer notification push
            $pushService = app(PushNotificationService::class);
            $pushService->notifyPaymentReceived($order->id, $amount);

            return response()->json([
                'message' => 'Payout déclenché avec succès',
                'order_id' => $order->id,
                'amount' => $amount,
                'naboopay_response' => $response,
            ]);

        } catch (\Exception $e) {
            Log::error('Manual payout failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Échec du payout',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Déclencher le payout pour toutes les commandes payées sans payout
     */
    public function triggerAllPendingPayouts(NabooPayService $nabooPay)
    {
        $paidOrders = Order::where('payment_status', 'paid')
            ->where('status', 'processing')
            ->get();

        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => [],
        ];

        foreach ($paidOrders as $order) {
            $amount = (int) $order->total_price;

            if ($amount < 11 || $amount > 1500000) {
                $results['skipped'][] = [
                    'order_id' => $order->id,
                    'reason' => 'Montant invalide: ' . $amount,
                ];
                continue;
            }

            try {
                $response = $nabooPay->transferToMainAccountWave(
                    $amount,
                    'Paiement commande #' . $order->reference
                );

                $results['success'][] = [
                    'order_id' => $order->id,
                    'amount' => $amount,
                    'response' => $response,
                ];

                Log::info('Batch payout success', [
                    'order_id' => $order->id,
                    'amount' => $amount,
                ]);

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Batch payout failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Batch payout terminé',
            'total_orders' => $paidOrders->count(),
            'results' => $results,
        ]);
    }
}
