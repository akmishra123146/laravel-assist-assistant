<?php

namespace LaravelAssist\Assistant\Tests\Unit\Analyzers\Database;

use LaravelAssist\Assistant\Analyzers\Database\NPlusOneQueryAnalyzer;
use LaravelAssist\Assistant\Support\LaravelInspector;
use LaravelAssist\Assistant\Tests\TestCase;

class NPlusOneQueryAnalyzerTest extends TestCase
{
    public function test_returns_correct_metadata(): void
    {
        $analyzer = new NPlusOneQueryAnalyzer();

        $this->assertEquals('N+1 Queries', $analyzer->getName());
        $this->assertEquals('database', $analyzer->getCategory());
    }

    public function test_analyze_returns_array(): void
    {
        $inspector = new LaravelInspector(app('files'));
        $inspector->setBasePath($this->app->basePath());

        $analyzer = new NPlusOneQueryAnalyzer();
        $result = $analyzer->analyze($inspector);

        $this->assertIsArray($result);
    }
}
