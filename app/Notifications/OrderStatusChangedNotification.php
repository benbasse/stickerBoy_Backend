<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public Order $order;
    public string $statusType;
    public string $oldStatus;
    public string $newStatus;

    /**
     * Status labels in French for display
     */
    public const STATUS_LABELS = [
        // Order status
        'pending' => 'En attente',
        'processing' => 'En cours de traitement',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
        // Payment status
        'unpaid' => 'Non payée',
        'paid' => 'Payée',
        'refunded' => 'Remboursée',
        'failed' => 'Échec du paiement',
    ];

    /**
     * Payment-specific labels (different from order labels)
     */
    public const PAYMENT_STATUS_LABELS = [
        'pending' => 'Paiement en attente',
        'unpaid' => 'Non payée',
        'paid' => 'Payée',
        'refunded' => 'Remboursée',
        'failed' => 'Échec du paiement',
    ];

    /**
     * Create a new notification instance.
     *
     * @param Order $order
     * @param string $statusType 'order' or 'payment'
     * @param string $oldStatus
     * @param string $newStatus
     */
    public function __construct(Order $order, string $statusType, string $oldStatus, string $newStatus)
    {
        $this->order = $order;
        $this->statusType = $statusType;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the label for a status based on status type
     */
    public function getStatusLabel(string $status): string
    {
        if ($this->statusType === 'payment') {
            return self::PAYMENT_STATUS_LABELS[$status] ?? self::STATUS_LABELS[$status] ?? $status;
        }

        return self::STATUS_LABELS[$status] ?? $status;
    }

    /**
     * Build the notification message
     */
    protected function buildMessage(): string
    {
        $orderNumber = $this->order->order_number;
        $newLabel = $this->getStatusLabel($this->newStatus);

        return "Votre commande {$orderNumber} : {$newLabel}";
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'type' => 'order_status_changed',
            'status_type' => $this->statusType,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'old_status_label' => $this->getStatusLabel($this->oldStatus),
            'new_status_label' => $this->getStatusLabel($this->newStatus),
            'message' => $this->buildMessage(),
            'customer_name' => $this->order->customer?->firstname . ' ' . $this->order->customer?->lastname,
            'total_price' => $this->order->total_price,
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'type' => 'order_status_changed',
            'status_type' => $this->statusType,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'old_status_label' => $this->getStatusLabel($this->oldStatus),
            'new_status_label' => $this->getStatusLabel($this->newStatus),
            'message' => $this->buildMessage(),
            'customer_name' => $this->order->customer?->firstname . ' ' . $this->order->customer?->lastname,
            'total_price' => $this->order->total_price,
        ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status_type' => $this->statusType,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }
}
