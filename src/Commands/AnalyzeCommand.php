<?php

namespace LaravelAssist\Assistant\Commands;

use Illuminate\Console\Command;
use LaravelAssist\Assistant\AI\RulesEngine;
use LaravelAssist\Assistant\Analyzers\Database\MissingIndexAnalyzer;
use LaravelAssist\Assistant\Analyzers\Database\NPlusOneQueryAnalyzer;
use LaravelAssist\Assistant\Analyzers\Code\UnusedRouteAnalyzer;
use LaravelAssist\Assistant\Analyzers\Code\UnusedControllerAnalyzer;
use LaravelAssist\Assistant\Analyzers\Code\UnusedViewAnalyzer;
use LaravelAssist\Assistant\Analyzers\Code\DeadCodeAnalyzer;
use LaravelAssist\Assistant\Analyzers\Code\UnusedServiceProviderAnalyzer;
use LaravelAssist\Assistant\Analyzers\Security\MassAssignmentAnalyzer;
use LaravelAssist\Assistant\Analyzers\Security\DebugSettingsAnalyzer;
use LaravelAssist\Assistant\Analyzers\Security\RateLimitAnalyzer;
use LaravelAssist\Assistant\Analyzers\Performance\CacheAnalyzer;
use LaravelAssist\Assistant\Analyzers\Performance\QueueAnalyzer;
use LaravelAssist\Assistant\Analyzers\Performance\EagerLoadingAnalyzer;
use LaravelAssist\Assistant\Support\LaravelInspector;

class AnalyzeCommand extends Command
{
    protected $signature = 'assistant:analyze
        {--only= : Comma-separated list of analyzer categories to run (database,code,security,performance)}
        {--format=table : Output format (table, json)}
        {--ai : Enable AI-enhanced recommendations}';

    protected $description = 'Run code analysis and provide actionable recommendations';

    public function handle(RulesEngine $rulesEngine, LaravelInspector $inspector): int
    {
        $this->registerAnalyzers($rulesEngine);

        $this->info('');
        $this->info('  Laravel Assistant - Code Analysis');
        $this->info('  ================================');
        $this->info('');

        $only = $this->option('only');
        if ($only) {
            $this->info('  Running analyzers: ' . $only);
        } else {
            $this->info('  Running all analyzers...');
        }
        $this->info('');

        if ($this->option('ai')) {
            $result = $rulesEngine->runWithAi($inspector);
            $aiEnhanced = $result['ai_enhanced'] ?? false;
        } else {
            $result = $rulesEngine->run($inspector);
            $aiEnhanced = false;
        }

        $findings = $result['findings'];
        $summary = $result['summary'];

        if ($only) {
            $categories = explode(',', $only);
            $findings = array_filter($findings, function ($f) use ($categories) {
                return in_array($f['type'], $categories) || $this->getCategoryForType($f['type']) === $categories[0];
            });
            $findings = array_values($findings);
        }

        $minSeverity = config('assistant.min_severity', 'info');
        $findings = $this->filterBySeverity($findings, $minSeverity);

        if (empty($findings)) {
            $this->info('  No issues found. Your code looks healthy!');
            return self::SUCCESS;
        }

        $summary = [
            'total' => count($findings),
            'critical' => count(array_filter($findings, fn ($f) => $f['severity'] === 'critical')),
            'warning' => count(array_filter($findings, fn ($f) => $f['severity'] === 'warning')),
            'info' => count(array_filter($findings, fn ($f) => $f['severity'] === 'info')),
        ];

        if ($this->option('format') === 'json') {
            $this->line(json_encode(['findings' => $findings, 'summary' => $summary], JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->printSummary($summary);
        $this->printFindings($findings, $aiEnhanced);

        $score = $this->calculateScore($summary);
        $this->info('');
        $this->info("  Health Score: {$score}/100");
        $this->info('');

        if ($score < 50) {
            $this->error('  Critical issues detected. Please address them before deployment.');
        } elseif ($score < 80) {
            $this->warn('  Some issues found. Consider addressing them for better code quality.');
        } else {
            $this->info('  Your code is in good shape!');
        }

        return self::SUCCESS;
    }

    protected function registerAnalyzers(RulesEngine $engine): void
    {
        $engine
            ->registerAnalyzer('missing_index', new MissingIndexAnalyzer())
            ->registerAnalyzer('n_plus_one', new NPlusOneQueryAnalyzer())
            ->registerAnalyzer('unused_route', new UnusedRouteAnalyzer())
            ->registerAnalyzer('unused_controller', new UnusedControllerAnalyzer())
            ->registerAnalyzer('unused_view', new UnusedViewAnalyzer())
            ->registerAnalyzer('dead_code', new DeadCodeAnalyzer())
            ->registerAnalyzer('unused_service_provider', new UnusedServiceProviderAnalyzer())
            ->registerAnalyzer('mass_assignment', new MassAssignmentAnalyzer())
            ->registerAnalyzer('debug_settings', new DebugSettingsAnalyzer())
            ->registerAnalyzer('rate_limit', new RateLimitAnalyzer())
            ->registerAnalyzer('cache', new CacheAnalyzer())
            ->registerAnalyzer('queue', new QueueAnalyzer())
            ->registerAnalyzer('eager_loading', new EagerLoadingAnalyzer());
    }

    protected function printSummary(array $summary): void
    {
        $this->info('  Summary:');
        $this->line('  ─────────────────────────────────────');
        $this->line("  Total:     {$summary['total']}");
        $this->error("  Critical:  {$summary['critical']}");
        $this->warn("  Warning:   {$summary['warning']}");
        $this->info("  Info:      {$summary['info']}");
        $this->line('');
    }

    protected function printFindings(array $findings, bool $aiEnhanced): void
    {
        foreach ($findings as $finding) {
            $severity = $finding['severity'];
            $icon = match ($severity) {
                'critical' => '✗',
                'warning' => '⚠',
                'info' => 'ℹ',
                default => '•',
            };

            $color = match ($severity) {
                'critical' => 'error',
                'warning' => 'warn',
                default => 'info',
            };

            $this->{$color}("  {$icon} [{$severity}] {$finding['message']}");
            $this->line("    File: {$finding['file']}" . ($finding['line'] > 0 ? ":{$finding['line']}" : ''));

            if (! empty($finding['recommendation'])) {
                $this->line("    Fix: {$finding['recommendation']}");
            }

            if ($aiEnhanced && ! empty($finding['ai_insight'])) {
                $this->line("    AI: {$finding['ai_insight']}");
            }

            $this->line('');
        }
    }

    protected function calculateScore(array $summary): int
    {
        if ($summary['total'] === 0) {
            return 100;
        }

        $score = 100;
        $score -= $summary['critical'] * 15;
        $score -= $summary['warning'] * 5;
        $score -= $summary['info'] * 1;

        return max(0, min(100, $score));
    }

    protected function filterBySeverity(array $findings, string $minSeverity): array
    {
        $levels = ['info' => 0, 'warning' => 1, 'critical' => 2];
        $minLevel = $levels[$minSeverity] ?? 0;

        return array_filter($findings, function ($f) use ($levels, $minLevel) {
            return ($levels[$f['severity']] ?? 0) >= $minLevel;
        });
    }

    protected function getCategoryForType(string $type): string
    {
        return match ($type) {
            'missing_index', 'n_plus_one' => 'database',
            'unused_route', 'unused_controller', 'unused_view', 'dead_code', 'unused_service_provider' => 'code',
            'mass_assignment', 'debug_settings', 'rate_limit' => 'security',
            'cache', 'queue', 'eager_loading' => 'performance',
            default => 'unknown',
        };
    }
}
