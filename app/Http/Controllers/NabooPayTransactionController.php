<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\NabooPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NabooPayTransactionController extends Controller
{
    protected NabooPayService $nabooPayService;

    public function __construct(NabooPayService $nabooPayService)
    {
        $this->nabooPayService = $nabooPayService;
    }

    /**
     * Liste toutes les transactions depuis NabooPay
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'page', 'limit', 'status', 'payment_method',
            'is_escrow', 'is_merchant', 'include_deleted',
            'customer_phone', 'min_amount', 'max_amount',
            'start_date', 'end_date', 'search'
        ]);

        $transactions = $this->nabooPayService->getAllTransactions($filters);

        return response()->json($transactions);
    }

    /**
     * Synchroniser toutes les commandes avec les transactions NabooPay
     */
    public function syncAll(Request $request)
    {
        $limit = $request->input('limit', 50);

        // Récupérer les transactions payées depuis NabooPay
        $paidTransactions = $this->nabooPayService->getPaidTransactions(1, $limit);

        $synced = [];
        $errors = [];

        $transactions = $paidTransactions['data'] ?? $paidTransactions['transactions'] ?? $paidTransactions;

        if (!is_array($transactions)) {
            return response()->json([
                'message' => 'Format de réponse inattendu de NabooPay',
                'raw_response' => $paidTransactions,
            ], 500);
        }

        foreach ($transactions as $transaction) {
            $orderId = $transaction['order_id'] ?? null;
            $status = $transaction['transaction_status'] ?? $transaction['status'] ?? null;

            if (!$orderId) continue;

            $order = Order::where('payment_reference', $orderId)->first();

            if (!$order) continue;

            // Si la transaction est payée mais la commande ne l'est pas
            if (in_array($status, ['paid', 'successful', 'completed', 'success'])
                && $order->payment_status !== 'paid') {

                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'paid_at' => now(),
                ]);

                $synced[] = [
                    'order_id' => $order->id,
                    'reference' => $order->reference,
                    'payment_reference' => $orderId,
                    'naboopay_status' => $status,
                ];

                Log::info('Order synced from NabooPay', [
                    'order_id' => $order->id,
                    'payment_reference' => $orderId,
                ]);
            }
        }

        return response()->json([
            'message' => count($synced) . ' commande(s) synchronisée(s)',
            'synced' => $synced,
            'total_transactions_checked' => count($transactions),
        ]);
    }

    /**
     * Synchroniser les commandes en attente uniquement
     */
    public function syncPending()
    {
        // Récupérer les commandes avec paiement en attente
        $pendingOrders = Order::where('payment_provider', 'naboopay')
            ->where('payment_status', '!=', 'paid')
            ->whereNotNull('payment_reference')
            ->get();

        $synced = [];
        $stillPending = [];
        $failed = [];

        foreach ($pendingOrders as $order) {
            try {
                $nabooPayStatus = $this->nabooPayService->getTransactionStatus($order->payment_reference);
                $status = $nabooPayStatus['transaction_status'] ?? $nabooPayStatus['status'] ?? null;

                Log::info('Checking order payment status', [
                    'order_id' => $order->id,
                    'payment_reference' => $order->payment_reference,
                    'naboopay_status' => $status,
                    'full_response' => $nabooPayStatus,
                ]);

                if (in_array($status, ['paid', 'successful', 'completed', 'success'])) {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing',
                        'paid_at' => now(),
                    ]);

                    $synced[] = [
                        'order_id' => $order->id,
                        'reference' => $order->reference,
                        'naboopay_status' => $status,
                    ];
                } elseif (in_array($status, ['failed', 'cancelled', 'expired', 'refunded'])) {
                    $order->update([
                        'payment_status' => 'failed',
                    ]);

                    $failed[] = [
                        'order_id' => $order->id,
                        'reference' => $order->reference,
                        'naboopay_status' => $status,
                    ];
                } else {
                    $stillPending[] = [
                        'order_id' => $order->id,
                        'reference' => $order->reference,
                        'naboopay_status' => $status,
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Error syncing order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Synchronisation terminée',
            'synced_paid' => count($synced),
            'still_pending' => count($stillPending),
            'failed' => count($failed),
            'details' => [
                'paid' => $synced,
                'pending' => $stillPending,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Récupérer les statistiques des transactions
     */
    public function stats()
    {
        $paidTransactions = $this->nabooPayService->getPaidTransactions(1, 100);
        $pendingTransactions = $this->nabooPayService->getPendingTransactions(1, 100);

        // Compter les commandes locales
        $localStats = [
            'total_orders' => Order::where('payment_provider', 'naboopay')->count(),
            'paid_orders' => Order::where('payment_provider', 'naboopay')
                ->where('payment_status', 'paid')->count(),
            'pending_orders' => Order::where('payment_provider', 'naboopay')
                ->where('payment_status', 'pending')->count(),
            'unpaid_orders' => Order::where('payment_provider', 'naboopay')
                ->where('payment_status', 'unpaid')->count(),
        ];

        return response()->json([
            'local_stats' => $localStats,
            'naboopay_paid' => $paidTransactions,
            'naboopay_pending' => $pendingTransactions,
        ]);
    }
}
