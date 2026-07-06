<?php

namespace LaravelAssist\Assistant\Tests\Unit\AI;

use LaravelAssist\Assistant\AI\RulesEngine;
use LaravelAssist\Assistant\Analyzers\Security\MassAssignmentAnalyzer;
use LaravelAssist\Assistant\Analyzers\Code\DeadCodeAnalyzer;
use LaravelAssist\Assistant\Support\LaravelInspector;
use LaravelAssist\Assistant\Tests\TestCase;

class RulesEngineTest extends TestCase
{
    public function test_can_register_and_run_analyzers(): void
    {
        $engine = new RulesEngine(
            ['security' => ['mass_assignment' => true]],
            ['enabled' => false]
        );

        $engine->registerAnalyzer('mass_assignment', new MassAssignmentAnalyzer());

        $analyzers = $engine->getAnalyzers();
        $this->assertArrayHasKey('mass_assignment', $analyzers);
    }

    public function test_run_returns_findings_and_summary(): void
    {
        $engine = new RulesEngine(
            ['security' => ['mass_assignment' => true]],
            ['enabled' => false]
        );

        $engine->registerAnalyzer('mass_assignment', new MassAssignmentAnalyzer());

        $inspector = new LaravelInspector(app('files'));
        $inspector->setBasePath($this->app->basePath());

        $result = $engine->run($inspector);

        $this->assertArrayHasKey('findings', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('total', $result['summary']);
        $this->assertArrayHasKey('critical', $result['summary']);
        $this->assertArrayHasKey('warning', $result['summary']);
        $this->assertArrayHasKey('info', $result['summary']);
    }

    public function test_disabled_analyzers_are_not_run(): void
    {
        $engine = new RulesEngine(
            ['security' => ['mass_assignment' => false]],
            ['enabled' => false]
        );

        $engine->registerAnalyzer('mass_assignment', new MassAssignmentAnalyzer());

        $inspector = new LaravelInspector(app('files'));
        $inspector->setBasePath($this->app->basePath());

        $result = $engine->run($inspector);

        $this->assertEquals(0, $result['summary']['total']);
    }

    public function test_run_with_ai_returns_ai_enhanced_flag(): void
    {
        $engine = new RulesEngine(
            ['security' => ['mass_assignment' => true]],
            ['enabled' => false, 'provider' => 'openai', 'api_key' => '']
        );

        $engine->registerAnalyzer('mass_assignment', new MassAssignmentAnalyzer());

        $inspector = new LaravelInspector(app('files'));
        $inspector->setBasePath($this->app->basePath());

        $result = $engine->runWithAi($inspector);

        $this->assertArrayHasKey('ai_enhanced', $result);
        $this->assertFalse($result['ai_enhanced']);
    }

    public function test_analyzers_are_sorted_by_severity(): void
    {
        $engine = new RulesEngine(
            [
                'security' => ['mass_assignment' => true],
                'code' => ['dead_code' => true],
            ],
            ['enabled' => false]
        );

        $engine
            ->registerAnalyzer('mass_assignment', new MassAssignmentAnalyzer())
            ->registerAnalyzer('dead_code', new DeadCodeAnalyzer());

        $inspector = new LaravelInspector(app('files'));
        $inspector->setBasePath($this->app->basePath());

        $result = $engine->run($inspector);

        $this->assertArrayHasKey('findings', $result);

        if (count($result['findings']) > 1) {
            $severityOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];
            $prevSeverity = 0;
            foreach ($result['findings'] as $finding) {
                $currentSeverity = $severityOrder[$finding['severity']] ?? 3;
                $this->assertGreaterThanOrEqual($prevSeverity, $currentSeverity);
                $prevSeverity = $currentSeverity;
            }
        }
    }
}
