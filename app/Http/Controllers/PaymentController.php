<?php
namespace App\Http\Controllers;

use App\Services\Payments\SenePayService;
use App\Models\Order;


class PaymentController extends Controller
{
    public function payWithSenePay($id, SenePayService $senePay)
    {
        $order = Order::findOrFail($id);

        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Commande déjà payée'
            ], 400);
        }

        $charge = $senePay->initiatePayment($order);

        $order->update([
            'payment_provider'  => 'senepay',
            'payment_reference' => $charge['reference'] ?? null,
            'payment_link'      => $charge['redirectUrl'] ?? null,
        ]);

        return response()->json([
            'payment_url' => $order->payment_link,
        ]);
    }
}
