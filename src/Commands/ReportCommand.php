<?php

namespace LaravelAssist\Assistant\Commands;

use Illuminate\Console\Command;
use LaravelAssist\Assistant\AI\RulesEngine;
use LaravelAssist\Assistant\Reports\ReportGenerator;
use LaravelAssist\Assistant\Support\LaravelInspector;

class ReportCommand extends Command
{
    protected $signature = 'assistant:report
        {--output= : File path to save the report}
        {--format=html : Output format (html, json, console)}
        {--ai : Enable AI-enhanced recommendations}';

    protected $description = 'Generate a comprehensive health report before deployment';

    public function handle(RulesEngine $rulesEngine, LaravelInspector $inspector, ReportGenerator $reportGenerator): int
    {
        $this->info('');
        $this->info('  Laravel Assistant - Health Report');
        $this->info('  ================================');
        $this->info('');

        $this->registerAnalyzers($rulesEngine);

        $this->line('  Running full analysis...');

        if ($this->option('ai')) {
            $result = $rulesEngine->runWithAi($inspector);
        } else {
            $result = $rulesEngine->run($inspector);
        }

        $format = $this->option('format');
        $outputPath = $this->option('output');

        $report = $reportGenerator->generate($result, $format);

        if ($outputPath) {
            $reportGenerator->saveReport($report, $outputPath);
            $this->info("  Report saved to: {$outputPath}");
        } else {
            $this->line('');
            $this->line($report);
        }

        $this->info('');

        return self::SUCCESS;
    }

    protected function registerAnalyzers(RulesEngine $engine): void
    {
        $engine
            ->registerAnalyzer('missing_index', new \LaravelAssist\Assistant\Analyzers\Database\MissingIndexAnalyzer())
            ->registerAnalyzer('n_plus_one', new \LaravelAssist\Assistant\Analyzers\Database\NPlusOneQueryAnalyzer())
            ->registerAnalyzer('unused_route', new \LaravelAssist\Assistant\Analyzers\Code\UnusedRouteAnalyzer())
            ->registerAnalyzer('unused_controller', new \LaravelAssist\Assistant\Analyzers\Code\UnusedControllerAnalyzer())
            ->registerAnalyzer('unused_view', new \LaravelAssist\Assistant\Analyzers\Code\UnusedViewAnalyzer())
            ->registerAnalyzer('dead_code', new \LaravelAssist\Assistant\Analyzers\Code\DeadCodeAnalyzer())
            ->registerAnalyzer('unused_service_provider', new \LaravelAssist\Assistant\Analyzers\Code\UnusedServiceProviderAnalyzer())
            ->registerAnalyzer('mass_assignment', new \LaravelAssist\Assistant\Analyzers\Security\MassAssignmentAnalyzer())
            ->registerAnalyzer('debug_settings', new \LaravelAssist\Assistant\Analyzers\Security\DebugSettingsAnalyzer())
            ->registerAnalyzer('rate_limit', new \LaravelAssist\Assistant\Analyzers\Security\RateLimitAnalyzer())
            ->registerAnalyzer('cache', new \LaravelAssist\Assistant\Analyzers\Performance\CacheAnalyzer())
            ->registerAnalyzer('queue', new \LaravelAssist\Assistant\Analyzers\Performance\QueueAnalyzer())
            ->registerAnalyzer('eager_loading', new \LaravelAssist\Assistant\Analyzers\Performance\EagerLoadingAnalyzer());
    }
}
