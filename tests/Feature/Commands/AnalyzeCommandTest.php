<?php

namespace LaravelAssist\Assistant\Tests\Feature\Commands;

use LaravelAssist\Assistant\Tests\TestCase;
use Artisan;

class AnalyzeCommandTest extends TestCase
{
    public function test_analyze_command_runs_successfully(): void
    {
        $this->artisan('assistant:analyze')
            ->assertExitCode(0);
    }

    public function test_analyze_command_with_json_format(): void
    {
        $this->artisan('assistant:analyze', ['--format' => 'json'])
            ->assertExitCode(0);
    }

    public function test_analyze_command_with_only_option(): void
    {
        $this->artisan('assistant:analyze', ['--only' => 'security'])
            ->assertExitCode(0);
    }

    public function test_security_command_runs_successfully(): void
    {
        $this->artisan('assistant:security')
            ->assertExitCode(0);
    }

    public function test_security_command_with_json_format(): void
    {
        $this->artisan('assistant:security', ['--format' => 'json'])
            ->assertExitCode(0);
    }
}
