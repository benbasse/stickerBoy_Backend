<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Sticker;
use App\Models\ToteBag;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Services\Bictorys\BictorysPaymentService;
use App\Services\PushNotificationService;
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
        $orders = Order::with('orderItems', 'customer', 'invoice')->get();

        // Ajouter l'URL de la facture pour chaque commande
        $ordersData = $orders->map(function ($order) {
            $orderArray = $order->toArray();
            $orderArray['invoice_url'] = url("/api/orders/{$order->id}/invoice");
            return $orderArray;
        });

        return $this->succesResponse($ordersData, 'done', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Summary of store
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *jsonattended
    {
        "customer": {
            "firstname": "John",
            "lastname": "Doe",
            "phone": "770000000"
        },
        "items": [
            {
                "type": "product",
                "product_type": "sticker",
                "product_id": "1",
                "quantity": 2
            },
            {
                "type": "collection",
                "collection_id": 3
            }
        ]
    }
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        $request->validate([
            'customer.firstname' => 'required|string',
            'customer.lastname'  => 'required|string',
            'customer.phone'     => 'required|string',
            'customer.address'   => 'nullable|string',

            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:product,collection',

            // product rules
            'items.*.product_id'   => 'required_if:items.*.type,product',
            'items.*.product_type' => 'required_if:items.*.type,product|in:sticker,tote_bag',
            'items.*.quantity'     => 'required_if:items.*.type,product|integer|min:1',
            'items.*.size'         => 'nullable|in:small,medium,large',

            // collection rules
            'items.*.collection_id' => 'required_if:items.*.type,collection'
        ]);

        try {
            // 1. Create customer
            $customer = Customer::create([
                'firstname' => $request->customer['firstname'],
                'lastname'  => $request->customer['lastname'],
                'phone'     => $request->customer['phone'],
                'address'   => $request->customer['address'] ?? null,
            ]);

            // 2. Create order
            $order = Order::create([
                'customer_id'    => $customer->id,
                'reference'      => Str::uuid(),
                'status'         => 'pending',
                'payment_status' => 'unpaid',
                'total_price'    => 0
            ]);

            $total = 0;

            // 3. Handle items
            foreach ($request->items as $item) {
                // 🟢 SIMPLE PRODUCT
                if ($item['type'] === 'product') {
                    $product = $item['product_type'] === 'sticker'
                        ? Sticker::findOrFail($item['product_id'])
                        : ToteBag::findOrFail($item['product_id']);
                    $subtotal = $product->price * $item['quantity'];
                    $total += $subtotal;
                    OrderItem::create([
                        'order_id'    => $order->id,
                        'product_id'  => $product->id,
                        'product_type' => $item['product_type'],
                        'unit_price'  => $product->price,
                        'quantity'    => $item['quantity'],
                        'size'        => $item['size'] ?? null,
                        'subtotal'    => $subtotal,
                        'is_bundle_item' => false
                    ]);
                }
                // 🟣 COLLECTION (BUNDLE)
                if ($item['type'] === 'collection') {
                    $collection = Collection::with('products')->findOrFail($item['collection_id']);
                    // prix du bundle
                    if ($collection->bundle_price) {
                        $total += $collection->bundle_price;
                    }
                    foreach ($collection->products as $product) {
                        // Récupérer le vrai produit
                        if ($product->product_type === 'sticker') {
                            $realProduct = $product->sticker;
                        } elseif ($product->product_type === 'tote_bag') {
                            $realProduct = $product->toteBag;
                        } else {
                            continue; // ou gestion d'erreur
                        }
                        $unitPrice = $realProduct ? $realProduct->price : 0;
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->product_id,
                            'product_type' => $product->product_type, // sticker | tote_bag
                            'unit_price' => $unitPrice,
                            'quantity' => $product->quantity,
                            'subtotal' => $unitPrice * $product->quantity,
                            'from_collection_id' => $collection->id,
                            'is_bundle_item' => true
                        ]);
                    }
                }
            }

            // 4. Update total
            $order->update([
                'total_price' => $total
            ]);

            DB::commit();


            // Create an invoice for the order
            $invoice = Invoice::create([
                'order_id' => $order->id,
                'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . $order->id,
                'invoice_date' => now(),
                'total_amount' => $order->total_price,
                'status' => 'issued'
            ]);


            // notifier tous les admins (notification DB)
            $admins = User::where('role', 'admin')->get();

            foreach ($admins as $admin) {
                $admin->notify(new NewOrderNotification($order));
            }

            // Envoyer push notification aux admins
            $pushService = app(PushNotificationService::class);
            $pushService->notifyNewOrder(
                $order->id,
                $customer->firstname . ' ' . $customer->lastname,
                $total
            );

            // Préparer la réponse avec le lien de la facture
            $orderData = $order->load('orderItems', 'invoice', 'customer')->toArray();
            $orderData['invoice_url'] = url("/api/orders/{$order->id}/invoice");

            return $this->succesResponse(
                $orderData,
                'Order created successfully',
                201
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
    public function show($id)
    {
        $order = Order::with('orderItems', 'customer', 'invoice')->findOrFail($id);
        $orderData = $order->toArray();
        $orderData['invoice_url'] = url("/api/orders/{$order->id}/invoice");

        return $this->succesResponse($orderData, 'done', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);

        $order->delete();
        return $this->succesResponse(null, 'Order deleted successfully', 200);
    }


    /**
     *Updatestatus order
     */
    public function updateStatus($id, Request $request)
    {
        $order = Order::findOrFail($id);
        $request->validate([
            'status' => 'required|in:pending,processing,paid,refunded,failed,completed,cancelled'
        ]);

        $order->update([
            'status' => $request->status
        ]);

        return $this->succesResponse($order, 'Order status updated successfully', 200);
    }
}
