<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Abdalmolood\AiSecurityGuardian\Contracts\ScannerInterface;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class RouteScanner implements ScannerInterface
{
    protected array $sensitiveRoutePatterns = [
        'admin', 'settings', 'users', 'roles', 'permissions', 
        'invoices', 'payments', 'reports', 'webhooks', 'uploads', 'files'
    ];

    public function getName(): string
    {
        return 'Route Security Scanner';
    }

    public function scan(): Collection
    {
        $findings = collect();
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $uri = $route->uri();
            $middlewares = $route->gatherMiddleware();
            
            $isSensitive = false;
            foreach ($this->sensitiveRoutePatterns as $pattern) {
                if (str_contains(strtolower($uri), $pattern)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                // Check if route has auth or auth:sanctum etc middleware
                $hasAuth = false;
                $hasThrottle = false;
                
                foreach ($middlewares as $m) {
                    if (is_string($m)) {
                        if (str_starts_with($m, 'auth') || str_contains($m, 'tenant') || str_contains($m, 'role') || str_contains($m, 'permission')) {
                            $hasAuth = true;
                        }
                        if (str_starts_with($m, 'throttle')) {
                            $hasThrottle = true;
                        }
                    }
                }

                if (!$hasAuth) {
                    $findings->push(new Finding(
                        title: 'Sensitive Route without Authentication',
                        description: "The route `$uri` appears to be sensitive but is missing authentication/authorization middleware.",
                        severity: Severity::HIGH,
                        category: 'broken_access_control',
                        affectedFile: 'routes/web.php or routes/api.php',
                        recommendation: 'Add `auth` or appropriate tenant/role middleware to this route.',
                        safeAutoFixAllowed: false,
                    ));
                }

                if (!$hasThrottle && str_contains($uri, 'api')) {
                    $findings->push(new Finding(
                        title: 'API Route without Rate Limiting',
                        description: "The API route `$uri` is missing rate limiting (`throttle` middleware).",
                        severity: Severity::MEDIUM,
                        category: 'rate_limiting',
                        recommendation: 'Add the `throttle` middleware to prevent abuse.',
                        safeAutoFixAllowed: true,
                    ));
                }
            }
        }

        return $findings;
    }
}
