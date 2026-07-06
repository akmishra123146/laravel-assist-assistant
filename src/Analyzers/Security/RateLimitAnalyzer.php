<?php

namespace LaravelAssist\Assistant\Analyzers\Security;

use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;

class RateLimitAnalyzer implements AnalyzerInterface
{
    public function analyze(LaravelInspector $inspector): array
    {
        $findings = [];
        $routes = $inspector->getRoutes();

        $publicMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $criticalRoutes = array_filter(
            $routes,
            fn ($route) => in_array($route['method'], $publicMethods)
                && ! in_array('throttle', $route['middleware'])
                && ! in_array('auth', $route['middleware'])
                && ! in_array('auth:sanctum', $route['middleware'])
        );

        foreach ($criticalRoutes as $route) {
            $findings[] = [
                'severity' => 'warning',
                'type' => 'missing_rate_limit',
                'file' => $route['file'] ?? 'routes/web.php',
                'line' => 0,
                'message' => "Route {$route['method']} {$route['uri']} has no throttle middleware.",
                'recommendation' => "Add ->middleware('throttle:60,1') or use RateLimiter::for() to define rate limits.",
            ];
        }

        $rateLimiterPath = $inspector->getBasePath() . '/app/Providers/RouteServiceProvider.php';
        $rateLimiterContent = $inspector->getFileContents($rateLimiterPath);

        if ($rateLimiterContent === null || ! str_contains($rateLimiterContent, 'configureRateLimiting')) {
            $findings[] = [
                'severity' => 'info',
                'type' => 'no_rate_limiter_config',
                'file' => 'app/Providers/RouteServiceProvider.php',
                'line' => 0,
                'message' => 'No custom rate limiting configuration found in RouteServiceProvider.',
                'recommendation' => "Define RateLimiter::for('api', ...) in your RouteServiceProvider for fine-grained control.",
            ];
        }

        return $findings;
    }

    public function getName(): string
    {
        return 'Rate Limiting';
    }

    public function getDescription(): string
    {
        return 'Identifies routes missing rate limiting protection.';
    }

    public function getCategory(): string
    {
        return 'security';
    }
}
