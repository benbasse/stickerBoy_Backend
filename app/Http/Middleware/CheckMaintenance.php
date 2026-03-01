<?php

namespace App\Http\Middleware;

use App\Services\MaintenanceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenance
{
    protected MaintenanceService $maintenanceService;

    /**
     * Routes exclues du mode maintenance
     */
    protected array $except = [
        'api/maintenance/*',
        'api/auth/*',
        'api/login',
        'api/register',
    ];

    public function __construct(MaintenanceService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Bypass pour les routes exclues
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // Bypass pour les admins authentifiés
        if (auth('api')->check()) {
            $user = auth('api')->user();
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return $next($request);
            }
            // Alternative: vérifier le rôle directement
            if (isset($user->role) && $user->role === 'admin') {
                return $next($request);
            }
        }

        // Vérifier le mode maintenance
        if ($this->maintenanceService->isEnabled()) {
            return response()->json([
                'maintenance' => true,
                ...$this->maintenanceService->getConfig()
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $next($request);
    }
}
