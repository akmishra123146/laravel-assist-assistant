<?php

namespace LaravelAssist\Assistant\Tests\Unit\Analyzers\Security;

use LaravelAssist\Assistant\Analyzers\Security\DebugSettingsAnalyzer;
use LaravelAssist\Assistant\Support\LaravelInspector;
use LaravelAssist\Assistant\Tests\TestCase;
use Illuminate\Support\Facades\File;

class DebugSettingsAnalyzerTest extends TestCase
{
    protected string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = sys_get_temp_dir() . '/assistant_debug_test_' . uniqid();
        File::makeDirectory($this->testDir, 0755, true, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testDir);
        parent::tearDown();
    }

    public function test_detects_app_debug_true(): void
    {
        File::put($this->testDir . '/.env', 'APP_DEBUG=true');

        $inspector = new LaravelInspector(app('files'));
        $inspector->setBasePath($this->testDir);

        $analyzer = new DebugSettingsAnalyzer();
        $findings = $analyzer->analyze($inspector);

        $criticalFindings = array_filter($findings, fn ($f) => $f['severity'] === 'critical');
        $this->assertNotEmpty($criticalFindings);
    }

    public function test_no_findings_when_debug_false(): void
    {
        File::put($this->testDir . '/.env', 'APP_DEBUG=false');

        $inspector = new LaravelInspector(app('files'));
        $inspector->setBasePath($this->testDir);

        $analyzer = new DebugSettingsAnalyzer();
        $findings = $analyzer->analyze($inspector);

        $criticalFindings = array_filter($findings, fn ($f) => $f['severity'] === 'critical');
        $this->assertEmpty($criticalFindings);
    }

    public function test_returns_correct_metadata(): void
    {
        $analyzer = new DebugSettingsAnalyzer();

        $this->assertEquals('Debug Settings', $analyzer->getName());
        $this->assertEquals('security', $analyzer->getCategory());
    }
}
