<?php

namespace LaravelAssist\Assistant\Analyzers\Code;

use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;
use LaravelAssist\Assistant\Support\RouteInspector;

class UnusedRouteAnalyzer implements AnalyzerInterface
{
    protected ?RouteInspector $routeInspector = null;

    public function __construct()
    {
    }

    public function analyze(LaravelInspector $inspector): array
    {
        $this->routeInspector = new RouteInspector($inspector);
        $findings = [];

        $brokenRoutes = $this->routeInspector->getBrokenRoutes();

        foreach ($brokenRoutes as $route) {
            $findings[] = [
                'severity' => 'critical',
                'type' => 'broken_route',
                'file' => $route['file'] ?? 'routes/web.php',
                'line' => 0,
                'message' => "Route {$route['method']} {$route['uri']} references non-existent controller: {$route['controller']}",
                'recommendation' => 'Ensure the controller class exists and is properly imported.',
            ];
        }

        $routes = $inspector->getRoutes();
        $routeFileContents = [];

        foreach ($routes as $route) {
            $file = $route['file'] ?? '';
            if (! isset($routeFileContents[$file])) {
                $routeFileContents[$file] = $inspector->getFileContents($file);
            }
        }

        foreach ($routeFileContents as $file => $content) {
            if ($content === null) {
                continue;
            }

            $pattern = '/Route::\w+\s*\(\s*[\'"]([^\'"]+)[\'"]/';
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $uri) {
                    if (str_starts_with($uri, '/')) {
                        $relativePath = str_replace($inspector->getBasePath() . '/', '', $file);
                        $findings[] = [
                            'severity' => 'info',
                            'type' => 'route_info',
                            'file' => $relativePath,
                            'line' => 0,
                            'message' => "Route defined: {$uri}",
                            'recommendation' => '',
                        ];
                    }
                }
            }
        }

        return $findings;
    }

    public function getName(): string
    {
        return 'Unused Routes';
    }

    public function getDescription(): string
    {
        return 'Finds routes that reference non-existent controllers or methods.';
    }

    public function getCategory(): string
    {
        return 'code';
    }
}
