<?php

namespace LaravelAssist\Assistant\Tests\Unit\Analyzers\Performance;

use LaravelAssist\Assistant\Analyzers\Performance\QueueAnalyzer;
use LaravelAssist\Assistant\Support\LaravelInspector;
use LaravelAssist\Assistant\Tests\TestCase;

class QueueAnalyzerTest extends TestCase
{
    public function test_returns_correct_metadata(): void
    {
        $analyzer = new QueueAnalyzer();

        $this->assertEquals('Queue Analysis', $analyzer->getName());
        $this->assertEquals('performance', $analyzer->getCategory());
    }

    public function test_analyze_returns_array(): void
    {
        $inspector = new LaravelInspector(app('files'));
        $inspector->setBasePath($this->app->basePath());

        $analyzer = new QueueAnalyzer();
        $result = $analyzer->analyze($inspector);

        $this->assertIsArray($result);
    }
}
