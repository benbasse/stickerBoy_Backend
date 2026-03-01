<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $enabled;
    public string $title;
    public string $message;
    public ?string $targetDate;
    public bool $showCountdown;

    public function __construct(array $config)
    {
        $this->enabled = $config['enabled'];
        $this->title = $config['title'];
        $this->message = $config['message'];
        $this->targetDate = $config['target_date'];
        $this->showCountdown = $config['show_countdown'];
    }

    /**
     * Canal public - tous les visiteurs peuvent écouter
     */
    public function broadcastOn(): Channel
    {
        return new Channel('maintenance');
    }

    /**
     * Nom de l'événement côté client
     */
    public function broadcastAs(): string
    {
        return 'status.changed';
    }

    /**
     * Données envoyées avec l'événement
     */
    public function broadcastWith(): array
    {
        return [
            'enabled' => $this->enabled,
            'title' => $this->title,
            'message' => $this->message,
            'target_date' => $this->targetDate,
            'show_countdown' => $this->showCountdown,
            'timestamp' => now()->toISOString(),
        ];
    }
}
