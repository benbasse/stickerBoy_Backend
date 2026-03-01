<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Events\MaintenanceStatusChanged;

class MaintenanceService
{
    /**
     * Vérifier si le mode maintenance est activé
     */
    public function isEnabled(): bool
    {
        return SiteSetting::get('maintenance_enabled', '0') === '1';
    }

    /**
     * Récupérer la configuration complète du mode maintenance
     */
    public function getConfig(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'title' => SiteSetting::get('maintenance_title', 'Coming Soon'),
            'message' => SiteSetting::get('maintenance_message', 'We are working on something amazing...'),
            'target_date' => SiteSetting::get('maintenance_target_date'),
            'show_countdown' => SiteSetting::get('maintenance_show_countdown', '1') === '1',
        ];
    }

    /**
     * Activer le mode maintenance
     */
    public function enable(array $config = []): void
    {
        SiteSetting::set('maintenance_enabled', '1');

        if (!empty($config)) {
            $this->updateConfig($config);
        } else {
            $this->broadcastStatus();
        }
    }

    /**
     * Désactiver le mode maintenance
     */
    public function disable(): void
    {
        SiteSetting::set('maintenance_enabled', '0');
        $this->broadcastStatus();
    }

    /**
     * Mettre à jour la configuration du mode maintenance
     */
    public function updateConfig(array $config): void
    {
        $mapping = [
            'title' => 'maintenance_title',
            'message' => 'maintenance_message',
            'target_date' => 'maintenance_target_date',
            'show_countdown' => 'maintenance_show_countdown',
        ];

        foreach ($config as $key => $value) {
            if (isset($mapping[$key]) && $value !== null) {
                $storeValue = is_bool($value) ? ($value ? '1' : '0') : $value;
                SiteSetting::set($mapping[$key], $storeValue);
            }
        }

        // Broadcast si maintenance est active
        if ($this->isEnabled()) {
            $this->broadcastStatus();
        }
    }

    /**
     * Diffuser le statut via Pusher
     */
    protected function broadcastStatus(): void
    {
        event(new MaintenanceStatusChanged($this->getConfig()));
    }
}
