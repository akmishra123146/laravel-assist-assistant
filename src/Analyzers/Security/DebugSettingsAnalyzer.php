<?php

namespace LaravelAssist\Assistant\Analyzers\Security;

use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;

class DebugSettingsAnalyzer implements AnalyzerInterface
{
    public function analyze(LaravelInspector $inspector): array
    {
        $findings = [];

        $debugValue = $inspector->getEnvValue('APP_DEBUG', 'false');
        if ($debugValue === 'true') {
            $findings[] = [
                'severity' => 'critical',
                'type' => 'debug_enabled',
                'file' => '.env',
                'line' => 0,
                'message' => 'APP_DEBUG is set to true. This exposes sensitive application details in error pages.',
                'recommendation' => 'Set APP_DEBUG=false in production. Use environment-specific configuration.',
            ];
        }

        $appConfigPath = $inspector->getBasePath() . '/config/app.php';
        $configContent = $inspector->getFileContents($appConfigPath);
        if ($configContent !== null && str_contains($configContent, "'debug'")) {
            if (preg_match('/\[.debug.\]\s*=>\s*(true|1)/i', $configContent)) {
                $hasDebugTrue = ! str_contains($configContent, "env('APP_DEBUG'");
                if ($hasDebugTrue) {
                    $findings[] = [
                        'severity' => 'warning',
                        'type' => 'debug_hardcoded',
                        'file' => 'config/app.php',
                        'line' => 0,
                        'message' => 'Debug mode appears to be hardcoded in config/app.php instead of using env().',
                        'recommendation' => "Use env('APP_DEBUG', false) to allow environment-based control.",
                    ];
                }
            }
        }

        $debugbarConfigPath = $inspector->getBasePath() . '/config/debugbar.php';
        if ($inspector->fileExists($debugbarConfigPath)) {
            $debugbarContent = $inspector->getFileContents($debugbarConfigPath);
            if ($debugbarContent !== null && ! str_contains($debugbarContent, "env('APP_DEBUG'")) {
                $findings[] = [
                    'severity' => 'warning',
                    'type' => 'debugbar_enabled',
                    'file' => 'config/debugbar.php',
                    'line' => 0,
                    'message' => 'Debugbar config exists. Ensure it is disabled in production.',
                    'recommendation' => 'Set debugbar to only enable when APP_DEBUG is true.',
                ];
            }
        }

        $telescopeConfigPath = $inspector->getBasePath() . '/config/telescope.php';
        if ($inspector->fileExists($telescopeConfigPath)) {
            $findings[] = [
                'severity' => 'info',
                'type' => 'telescope_present',
                'file' => 'config/telescope.php',
                'line' => 0,
                'message' => 'Laravel Telescope is installed. Ensure it is disabled or restricted in production.',
                'recommendation' => 'Use Telescope::running() check or environment-based middleware to restrict access.',
            ];
        }

        return $findings;
    }

    public function getName(): string
    {
        return 'Debug Settings';
    }

    public function getDescription(): string
    {
        return 'Checks for debug mode exposure and development tool leakage.';
    }

    public function getCategory(): string
    {
        return 'security';
    }
}
