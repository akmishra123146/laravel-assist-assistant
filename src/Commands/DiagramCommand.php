<?php

namespace LaravelAssist\Assistant\Commands;

use Illuminate\Console\Command;
use LaravelAssist\Assistant\Analyzers\Diagrams\ModelDiagramGenerator;
use LaravelAssist\Assistant\Analyzers\Diagrams\DependencyGraphGenerator;
use LaravelAssist\Assistant\Support\LaravelInspector;

class DiagramCommand extends Command
{
    protected $signature = 'assistant:diagram
        {--type=model : Diagram type (model, dependency)}
        {--output= : File path to save the diagram}
        {--show-columns : Include model columns in the diagram}';

    protected $description = 'Generate model diagrams and dependency graphs';

    public function handle(LaravelInspector $inspector): int
    {
        $this->info('');
        $this->info('  Laravel Assistant - Diagram Generator');
        $this->info('  =====================================');
        $this->info('');

        $type = $this->option('type');

        $generator = match ($type) {
            'model' => new ModelDiagramGenerator($inspector),
            'dependency' => new DependencyGraphGenerator($inspector),
            default => null,
        };

        if ($generator === null) {
            $this->error("  Unknown diagram type: {$type}");
            $this->info('  Available types: model, dependency');
            return self::FAILURE;
        }

        $this->line("  Generating {$type} diagram...");

        $showColumns = $this->option('show-columns');
        $diagram = $type === 'model'
            ? $generator->generate($showColumns)
            : $generator->generate();

        $outputPath = $this->option('output');

        if ($outputPath) {
            file_put_contents($outputPath, $diagram);
            $this->info("  Diagram saved to: {$outputPath}");
        } else {
            $this->line('');
            $this->line($diagram);
        }

        $this->info('');
        $this->info('  Render this Mermaid diagram at: https://mermaid.live');
        $this->info('');

        return self::SUCCESS;
    }
}
