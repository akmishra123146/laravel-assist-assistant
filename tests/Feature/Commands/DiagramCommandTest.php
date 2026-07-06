<?php

namespace LaravelAssist\Assistant\Tests\Feature\Commands;

use LaravelAssist\Assistant\Tests\TestCase;

class DiagramCommandTest extends TestCase
{
    public function test_diagram_command_model_type(): void
    {
        $this->artisan('assistant:diagram', ['--type' => 'model'])
            ->assertExitCode(0);
    }

    public function test_diagram_command_dependency_type(): void
    {
        $this->artisan('assistant:diagram', ['--type' => 'dependency'])
            ->assertExitCode(0);
    }

    public function test_diagram_command_invalid_type_fails(): void
    {
        $this->artisan('assistant:diagram', ['--type' => 'invalid'])
            ->assertExitCode(1);
    }

    public function test_diagram_command_with_output_file(): void
    {
        $outputPath = sys_get_temp_dir() . '/test-diagram.md';

        $this->artisan('assistant:diagram', [
            '--type' => 'model',
            '--output' => $outputPath,
        ])->assertExitCode(0);

        $this->assertFileExists($outputPath);

        @unlink($outputPath);
    }

    public function test_diagram_command_with_show_columns(): void
    {
        $this->artisan('assistant:diagram', [
            '--type' => 'model',
            '--show-columns' => true,
        ])->assertExitCode(0);
    }
}
