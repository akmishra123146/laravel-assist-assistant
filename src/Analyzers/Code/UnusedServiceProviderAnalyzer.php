<?php

namespace LaravelAssist\Assistant\Analyzers\Code;

use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;

class UnusedServiceProviderAnalyzer implements AnalyzerInterface
{
    public function analyze(LaravelInspector $inspector): array
    {
        $findings = [];
        $providers = $inspector->getProviders();

        $kernelPath = $inspector->getBasePath() . '/app/Http/Kernel.php';
        $kernelContent = $inspector->getFileContents($kernelPath);

        foreach ($providers as $providerClass => $provider) {
            $shortName = class_basename($provider);

            if (in_array($shortName, [
                'AppServiceProvider',
                'AuthServiceProvider',
                'BroadcastServiceProvider',
                'EventServiceProvider',
                'RouteServiceProvider',
                'FortifyServiceProvider',
                'JetstreamServiceProvider',
            ])) {
                continue;
            }

            $providerPath = $inspector->getBasePath() . '/app/Providers/' . $shortName . '.php';
            $content = $inspector->getFileContents($providerPath);

            if ($content !== null) {
                $hasBootLogic = str_contains($content, '$this->app[')
                    || str_contains($content, 'Route::')
                    || str_contains($content, 'View::')
                    || str_contains($content, 'Gate::')
                    || str_contains($content, 'event(')
                    || str_contains($content, 'LoadMigrationsFrom')
                    || str_contains($content, 'publishes(');

                $hasRegisterLogic = str_contains($content, '$this->app->bind(')
                    || str_contains($content, '$this->app->singleton(')
                    || str_contains($content, '$this->app->alias(')
                    || str_contains($content, '$this->mergeConfigFrom(');

                if (! $hasBootLogic && ! $hasRegisterLogic) {
                    $relativePath = str_replace($inspector->getBasePath() . '/', '', $providerPath);
                    $findings[] = [
                        'severity' => 'info',
                        'type' => 'unused_service_provider',
                        'file' => $relativePath,
                        'line' => 0,
                        'message' => "ServiceProvider '{$shortName}' has empty boot() and register() methods.",
                        'recommendation' => 'Remove unused service providers to improve application bootstrap performance.',
                    ];
                }
            }
        }

        return $findings;
    }

    public function getName(): string
    {
        return 'Unused Service Providers';
    }

    public function getDescription(): string
    {
        return 'Finds service providers with empty boot/register methods.';
    }

    public function getCategory(): string
    {
        return 'code';
    }
}
