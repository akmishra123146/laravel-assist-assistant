<?php

namespace LaravelAssist\Assistant\Tests\Unit\Analyzers\Database;

use LaravelAssist\Assistant\Analyzers\Database\MissingIndexAnalyzer;
use LaravelAssist\Assistant\Support\LaravelInspector;
use LaravelAssist\Assistant\Tests\TestCase;

class MissingIndexAnalyzerTest extends TestCase
{
    public function test_returns_correct_metadata(): void
    {
        $analyzer = new MissingIndexAnalyzer();

        $this->assertEquals('Missing Indexes', $analyzer->getName());
        $this->assertEquals('database', $analyzer->getCategory());
    }

    public function test_analyze_returns_array(): void
    {
        $inspector = new LaravelInspector(app('files'));
        $inspector->setBasePath($this->app->basePath());

        $analyzer = new MissingIndexAnalyzer();
        $result = $analyzer->analyze($inspector);

        $this->assertIsArray($result);
    }
}
