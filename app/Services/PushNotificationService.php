<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChangedNotification;
use App\Notifications\PushNotification;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Envoyer une notification push à un utilisateur
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        try {
            if ($user->pushSubscriptions()->count() === 0) {
                Log::info('PushNotification: User has no subscriptions', ['user_id' => $user->id]);
                return false;
            }

            $user->notify(new PushNotification($title, $body, $data));

            Log::info('PushNotification sent', [
                'user_id' => $user->id,
                'title' => $title,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('PushNotification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification push à plusieurs utilisateurs
     */
    public function sendToUsers($users, string $title, string $body, array $data = []): int
    {
        $sent = 0;

        foreach ($users as $user) {
            if ($this->sendToUser($user, $title, $body, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Envoyer une notification push à tous les admins
     */
    public function sendToAdmins(string $title, string $body, array $data = []): int
    {
        $admins = User::where('role', 'admin')
            ->whereHas('pushSubscriptions')
            ->get();

        return $this->sendToUsers($admins, $title, $body, $data);
    }

    /**
     * Envoyer une notification push à tous les utilisateurs avec subscriptions
     */
    public function broadcast(string $title, string $body, array $data = []): int
    {
        $users = User::whereHas('pushSubscriptions')->get();

        return $this->sendToUsers($users, $title, $body, $data);
    }

    /**
     * Notification pour nouvelle commande (envoyer aux admins)
     */
    public function notifyNewOrder(string $orderId, string $customerName, int $total): int
    {
        return $this->sendToAdmins(
            'Nouvelle commande',
            "Commande de {$customerName} - " . number_format($total) . ' FCFA',
            [
                'type' => 'new_order',
                'order_id' => $orderId,
                'url' => '/admin/orders/' . $orderId,
            ]
        );
    }

    /**
     * Notification pour paiement reçu
     */
    public function notifyPaymentReceived(string $orderId, int $amount): int
    {
        return $this->sendToAdmins(
            'Paiement reçu',
            'Paiement de ' . number_format($amount) . ' FCFA confirmé',
            [
                'type' => 'payment_received',
                'order_id' => $orderId,
                'url' => '/admin/orders/' . $orderId,
            ]
        );
    }

    /**
     * Notification pour changement de statut de commande
     */
    public function notifyOrderStatusChanged(Order $order, string $oldStatus, string $newStatus, string $statusType = 'order'): int
    {
        $labels = OrderStatusChangedNotification::STATUS_LABELS;
        $newLabel = $labels[$newStatus] ?? $newStatus;
        $orderNumber = $order->order_number;

        if ($statusType === 'payment') {
            $title = 'Statut de paiement modifié';
            $body = "Commande {$orderNumber} : paiement \"{$newLabel}\"";
        } else {
            $title = 'Statut de commande modifié';
            $body = "Commande {$orderNumber} : \"{$newLabel}\"";
        }

        // Send push notification to all users (admins and regular users)
        return $this->broadcast(
            $title,
            $body,
            [
                'type' => 'order_status_changed',
                'order_id' => $order->id,
                'order_number' => $orderNumber,
                'status_type' => $statusType,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'url' => '/orders/' . $order->id,
            ]
        );
    }
}
