<?php

namespace LaravelAssist\Assistant\Analyzers\Code;

use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;
use LaravelAssist\Assistant\Support\RouteInspector;

class UnusedControllerAnalyzer implements AnalyzerInterface
{
    protected ?RouteInspector $routeInspector = null;

    public function __construct()
    {
    }

    public function analyze(LaravelInspector $inspector): array
    {
        $this->routeInspector = new RouteInspector($inspector);
        $findings = [];

        $unusedControllers = $this->routeInspector->getUnusedControllers();

        foreach ($unusedControllers as $controllerClass => $file) {
            $relativePath = str_replace($inspector->getBasePath() . '/', '', $file);
            $shortName = class_basename($controllerClass);

            $findings[] = [
                'severity' => 'warning',
                'type' => 'unused_controller',
                'file' => $relativePath,
                'line' => 0,
                'message' => "Controller '{$shortName}' is not referenced by any route.",
                'recommendation' => 'Remove unused controllers or add routes that reference them.',
            ];
        }

        return $findings;
    }

    public function getName(): string
    {
        return 'Unused Controllers';
    }

    public function getDescription(): string
    {
        return 'Finds controller classes that are not referenced by any route.';
    }

    public function getCategory(): string
    {
        return 'code';
    }
}
