<?php

namespace LaravelAssist\Assistant\Commands;

use Illuminate\Console\Command;
use LaravelAssist\Assistant\AI\RulesEngine;
use LaravelAssist\Assistant\Analyzers\Security\MassAssignmentAnalyzer;
use LaravelAssist\Assistant\Analyzers\Security\DebugSettingsAnalyzer;
use LaravelAssist\Assistant\Analyzers\Security\RateLimitAnalyzer;
use LaravelAssist\Assistant\Support\LaravelInspector;

class SecurityCommand extends Command
{
    protected $signature = 'assistant:security
        {--format=table : Output format (table, json)}
        {--ai : Enable AI-enhanced recommendations}';

    protected $description = 'Run security-focused code analysis';

    public function handle(RulesEngine $rulesEngine, LaravelInspector $inspector): int
    {
        $engine = new RulesEngine(
            config('assistant.analyzers', []),
            config('assistant.ai', [])
        );

        $engine
            ->registerAnalyzer('mass_assignment', new MassAssignmentAnalyzer())
            ->registerAnalyzer('debug_settings', new DebugSettingsAnalyzer())
            ->registerAnalyzer('rate_limit', new RateLimitAnalyzer());

        $this->info('');
        $this->info('  Laravel Assistant - Security Scan');
        $this->info('  ================================');
        $this->info('');

        $this->line('  Running security analyzers...');

        if ($this->option('ai')) {
            $result = $engine->runWithAi($inspector);
        } else {
            $result = $engine->run($inspector);
        }

        $findings = $result['findings'];
        $summary = $result['summary'];

        if (empty($findings)) {
            $this->info('  No security issues found!');
            return self::SUCCESS;
        }

        if ($this->option('format') === 'json') {
            $this->line(json_encode(['findings' => $findings, 'summary' => $summary], JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->printSummary($summary);
        $this->printSecurityFindings($findings);

        $this->info('');

        return self::SUCCESS;
    }

    protected function printSummary(array $summary): void
    {
        $this->info('  Security Summary:');
        $this->line('  ─────────────────────────────────────');
        $this->line("  Total:     {$summary['total']}");
        $this->error("  Critical:  {$summary['critical']}");
        $this->warn("  Warning:   {$summary['warning']}");
        $this->info("  Info:      {$summary['info']}");
        $this->line('');
    }

    protected function printSecurityFindings(array $findings): void
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

            $this->line('');
        }
    }
}
