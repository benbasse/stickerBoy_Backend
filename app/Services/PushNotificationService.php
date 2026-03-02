<?php

namespace App\Services;

use App\Models\User;
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
}
