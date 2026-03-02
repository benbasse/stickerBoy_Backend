<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\NabooPayService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class NabooPayWebhookController extends Controller
{
    protected NabooPayService $nabooPayService;

    public function __construct(NabooPayService $nabooPayService)
    {
        $this->nabooPayService = $nabooPayService;
    }

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Naboopay-Signature') ?? $request->header('X-Signature');
        $secret = config('services.naboopay.webhook_secret');

        // Vérifier la signature si elle est présente
        if ($secret && $signature) {
            if (!$this->verifySignature($payload, $signature, $secret)) {
                Log::warning('NabooPay webhook: invalid signature');
                return response()->json(['error' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
            }
        }

        $data = json_decode($payload, true);

        Log::info('NabooPay webhook received', $data ?? []);

        $orderId = $data['order_id'] ?? null;
        $status = $data['transaction_status'] ?? null;

        if (!$orderId) {
            Log::warning('NabooPay webhook: missing order_id');
            return response()->json(['error' => 'Missing order_id'], Response::HTTP_BAD_REQUEST);
        }

        $order = Order::where('payment_reference', $orderId)->first();

        if (!$order) {
            Log::warning('NabooPay webhook: order not found', ['order_id' => $orderId]);
            return response()->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        match ($status) {
            'successful', 'completed', 'paid' => $this->handlePaymentSuccess($order, $data),
            'failed', 'cancelled', 'expired' => $this->handlePaymentFailed($order, $data),
            'pending' => $this->handlePaymentPending($order, $data),
            default => Log::info('NabooPay webhook: unknown status', ['status' => $status]),
        };

        return response()->json(['received' => true]);
    }

    private function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    private function handlePaymentSuccess(Order $order, array $payload): void
    {
        $order->update([
            'payment_status' => 'paid',
            'status' => 'processing',
            'paid_at' => now(),
        ]);

        Log::info('NabooPay payment successful', [
            'order_id' => $order->id,
            'reference' => $order->payment_reference,
        ]);

        // Envoyer push notification aux admins
        $pushService = app(PushNotificationService::class);
        $pushService->notifyPaymentReceived($order->id, (int) $order->total_price);

        // Transférer automatiquement vers le compte principal
        $this->transferToMainAccount($order, $payload);
    }

    private function transferToMainAccount(Order $order, array $payload): void
    {
        $amount = (int) $order->total_price;

        // Vérifier que le montant est valide (entre 11 et 1,500,000 FCFA)
        if ($amount < 11 || $amount > 1500000) {
            Log::warning('NabooPay transfer: invalid amount', [
                'order_id' => $order->id,
                'amount' => $amount,
            ]);
            return;
        }

        try {
            // Transférer via Wave par défaut
            $response = $this->nabooPayService->transferToMainAccountWave(
                $amount,
                'Paiement commande #' . $order->reference
            );

            Log::info('NabooPay transfer initiated', [
                'order_id' => $order->id,
                'amount' => $amount,
                'response' => $response,
            ]);
        } catch (\Exception $e) {
            Log::error('NabooPay transfer failed', [
                'order_id' => $order->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handlePaymentFailed(Order $order, array $payload): void
    {
        $order->update([
            'payment_status' => 'failed',
        ]);

        Log::info('NabooPay payment failed', [
            'order_id' => $order->id,
            'reference' => $order->payment_reference,
            'status' => $payload['transaction_status'] ?? null,
        ]);
    }

    private function handlePaymentPending(Order $order, array $payload): void
    {
        $order->update([
            'payment_status' => 'pending',
        ]);

        Log::info('NabooPay payment pending', [
            'order_id' => $order->id,
            'reference' => $order->payment_reference,
        ]);
    }
}
