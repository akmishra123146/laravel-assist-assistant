<?php

namespace LaravelAssist\Assistant\Tests\Feature\Commands;

use LaravelAssist\Assistant\Tests\TestCase;

class ReportCommandTest extends TestCase
{
    public function test_report_command_runs_successfully(): void
    {
        $this->artisan('assistant:report')
            ->assertExitCode(0);
    }

    public function test_report_command_with_json_format(): void
    {
        $this->artisan('assistant:report', ['--format' => 'json'])
            ->assertExitCode(0);
    }

    public function test_report_command_with_output_file(): void
    {
        $outputPath = sys_get_temp_dir() . '/test-report.html';

        $this->artisan('assistant:report', [
            '--format' => 'html',
            '--output' => $outputPath,
        ])->assertExitCode(0);

        $this->assertFileExists($outputPath);

        @unlink($outputPath);
    }
}
