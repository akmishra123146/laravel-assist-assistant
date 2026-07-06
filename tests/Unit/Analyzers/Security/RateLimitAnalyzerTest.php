<?php

namespace LaravelAssist\Assistant\Tests\Unit\Analyzers\Security;

use LaravelAssist\Assistant\Analyzers\Security\RateLimitAnalyzer;
use LaravelAssist\Assistant\Support\LaravelInspector;
use LaravelAssist\Assistant\Tests\TestCase;

class RateLimitAnalyzerTest extends TestCase
{
    public function test_returns_correct_metadata(): void
    {
        $analyzer = new RateLimitAnalyzer();

        $this->assertEquals('Rate Limiting', $analyzer->getName());
        $this->assertEquals('security', $analyzer->getCategory());
        $this->assertNotEmpty($analyzer->getDescription());
    }

    public function test_analyze_returns_array(): void
    {
        $inspector = new LaravelInspector(app('files'));
        $inspector->setBasePath($this->app->basePath());

        $analyzer = new RateLimitAnalyzer();
        $result = $analyzer->analyze($inspector);

        $this->assertIsArray($result);
    }
}
