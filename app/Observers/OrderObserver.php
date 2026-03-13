<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChangedNotification;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    protected PushNotificationService $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Check if order status changed
        if ($order->isDirty('status')) {
            $oldStatus = $order->getOriginal('status');
            $newStatus = $order->status;

            $this->notifyStatusChange($order, $oldStatus, $newStatus, 'order');
        }

        // Check if payment status changed
        if ($order->isDirty('payment_status')) {
            $oldStatus = $order->getOriginal('payment_status');
            $newStatus = $order->payment_status;

            $this->notifyStatusChange($order, $oldStatus, $newStatus, 'payment');
        }
    }

    /**
     * Send notifications for status change
     */
    protected function notifyStatusChange(Order $order, string $oldStatus, string $newStatus, string $statusType): void
    {
        // Load customer relationship if not loaded
        $order->loadMissing('customer');

        Log::info('Order status changed', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status_type' => $statusType,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        $notification = new OrderStatusChangedNotification(
            $order,
            $statusType,
            $oldStatus,
            $newStatus
        );

        // Send notification to all users (admins and regular users)
        $users = User::all();

        foreach ($users as $user) {
            $user->notify(clone $notification);
        }

        // Send push notification
        $this->pushService->notifyOrderStatusChanged(
            $order,
            $oldStatus,
            $newStatus,
            $statusType
        );
    }
}
