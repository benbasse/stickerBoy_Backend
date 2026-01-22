<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Sticker;
use App\Models\ToteBag;
use App\Services\Bictorys\BictorysPaymentService;
use App\Traits\apiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use apiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::all();
        return $this->succesResponse(200, 'done', $orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        $request->validate([
            'customer.firstname' => 'required|string',
            'customer.lastname' => 'required|string',
            'customer.phone' => 'required|string',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string',
            'items.*.product_type' => 'required|in:sticker,tote_bag',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            // 1. Create customer
            $customer = Customer::create([
                'firstname' => $request->customer['firstname'],
                'lastname'  => $request->customer['lastname'],
                'phone'     => $request->customer['phone'],
            ]);

            // 2. Create order
            $order = Order::create([
                'customer_id' => $customer->id,
                'reference'   => Str::uuid(),
                'status'      => 'pending',
                'payment_status' => 'unpaid',
                'total_price' => 0
            ]);

            $total = 0;

            // 3. Create order items
            foreach ($request->items as $item) {

                $product = $item['product_type'] === 'sticker'
                    ? Sticker::findOrFail($item['product_id'])
                    : ToteBag::findOrFail($item['product_id']);

                $subtotal = $product->price * $item['quantity'];
                $total += $subtotal;

                OrderItem::create([
                    'order_id'    => $order->id,
                    'product_id'  => $product->id,
                    'product_type' => $item['product_type'],
                    'price'       => $product->price,
                    'quantity'    => $item['quantity'],
                    'subtotal'    => $subtotal
                ]);
            }

            // 4. Update total
            $order->update([
                'total_price' => $total
            ]);

            DB::commit();

            return $this->succesResponse(
                201,
                'Order created successfully',
                $order->load('items')
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Order creation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        return $this->succesResponse(200, 'done', $order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->delete();
        return $this->succesResponse(200, 'Order deleted successfully', null);
    }

    /**
     *
     * Example request payload:
        {
        "customer": {
            "firstname": "Moussa",
            "lastname": "Basse",
            "phone": "770000000"
        },
        "items": [
            {
            "product_id": "uuid-sticker-1",
            "product_type": "sticker",
            "quantity": 2
            },
            {
            "product_id": "uuid-tote-1",
            "product_type": "tote_bag",
            "quantity": 1
            }
        ]
        }
     */

    /**
     * Pay for the specified order by id order.
     */
    public function pay(Order $order, BictorysPaymentService $bictorys)
    {
        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Order already paid'], 400);
        }

        $payment = $bictorys->createCharge([
            "amount" => $order->total_price,
            "currency" => "XOF",
            "paymentReference" => $order->reference,
            "successRedirectUrl" => env('FRONTEND_SUCCESS_URL'),
            "errorRedirectUrl" => env('FRONTEND_ERROR_URL'),
        ]);

        $order->update([
            'payment_provider' => 'bictorys',
            'payment_status' => 'pending',
            'payment_link' => $payment['paymentUrl'] ?? $payment['checkoutUrl'] ?? null,
        ]);

        return response()->json([
            'payment_url' => $order->payment_link
        ]);
    }
}
