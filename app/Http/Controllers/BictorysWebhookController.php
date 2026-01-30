<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class BictorysWebhookController extends Controller
{
    /**
     * Summary of handle
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * Example payload:
    {
        "paymentReference": "order-12345",
        "status": "SUCCESS" // or "FAILED"
    }
     */
    public function handle(Request $request)
    {
        $reference = $request->paymentReference;
        $status = $request->status; // SUCCESS | FAILED

        $order = Order::where('reference', $reference)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($status === 'SUCCESS') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'completed',
                // 'paid_at' => now(),
            ]);
        } else {
            $order->update([
                'payment_status' => 'refunded',
                'status' => 'cancelled',
            ]);
        }

        return response()->json(['message' => 'Webhook processed']);
    }
}
