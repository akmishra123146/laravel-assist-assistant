<?php

namespace LaravelAssist\Assistant;

use Illuminate\Support\ServiceProvider;

class AssistantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/assistant.php',
            'assistant'
        );

        $this->app->singleton(AI\RulesEngine::class, function ($app) {
            return new AI\RulesEngine(
                $app['config']->get('assistant.analyzers', []),
                $app['config']->get('assistant.ai', [])
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/assistant.php' => config_path('assistant.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\AnalyzeCommand::class,
                Commands\ReportCommand::class,
                Commands\DiagramCommand::class,
                Commands\SecurityCommand::class,
            ]);
        }
    }
}
