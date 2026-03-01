<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    protected MaintenanceService $maintenanceService;

    public function __construct(MaintenanceService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    /**
     * GET /api/maintenance/status (PUBLIC - pas d'auth)
     * Retourne le statut actuel
     */
    public function status()
    {
        return response()->json($this->maintenanceService->getConfig());
    }

    /**
     * POST /api/maintenance/enable (ADMIN ONLY)
     * Body: { title?, message?, target_date?, show_countdown? }
     */
    public function enable(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000',
            'target_date' => 'nullable|date',
            'show_countdown' => 'nullable|boolean'
        ]);

        $this->maintenanceService->enable($validated);

        return response()->json([
            'success' => true,
            'message' => 'Mode maintenance activé',
            'config' => $this->maintenanceService->getConfig()
        ]);
    }

    /**
     * POST /api/maintenance/disable (ADMIN ONLY)
     */
    public function disable()
    {
        $this->maintenanceService->disable();

        return response()->json([
            'success' => true,
            'message' => 'Mode maintenance désactivé',
            'config' => $this->maintenanceService->getConfig()
        ]);
    }

    /**
     * PUT /api/maintenance/update (ADMIN ONLY)
     * Met à jour les paramètres sans changer enabled/disabled
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000',
            'target_date' => 'nullable|date',
            'show_countdown' => 'nullable|boolean'
        ]);

        $this->maintenanceService->updateConfig($validated);

        return response()->json([
            'success' => true,
            'message' => 'Configuration mise à jour',
            'config' => $this->maintenanceService->getConfig()
        ]);
    }
}
