<?php

namespace LaravelAssist\Assistant\Analyzers\Database;

use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;

class NPlusOneQueryAnalyzer implements AnalyzerInterface
{
    public function analyze(LaravelInspector $inspector): array
    {
        $findings = [];

        $findings = array_merge($findings, $this->analyzeControllers($inspector));
        $findings = array_merge($findings, $this->analyzeBladeTemplates($inspector));

        return $findings;
    }

    public function getName(): string
    {
        return 'N+1 Queries';
    }

    public function getDescription(): string
    {
        return 'Identifies potential N+1 query problems in controllers and Blade templates.';
    }

    public function getCategory(): string
    {
        return 'database';
    }

    protected function analyzeControllers(LaravelInspector $inspector): array
    {
        $findings = [];
        $controllers = $inspector->getControllers();

        foreach ($controllers as $controllerClass => $file) {
            $content = $inspector->getFileContents($inspector->getBasePath() . '/' . $file);
            if ($content === null) {
                continue;
            }

            if (preg_match_all('/@foreach\s*\(\s*\$\w+->(\w+)\s+as/', $content, $matches)) {
                foreach ($matches[1] as $relation) {
                    if ($this->isLikelyRelationship($relation)) {
                        $findings[] = [
                            'severity' => 'warning',
                            'type' => 'n_plus_one',
                            'file' => $file,
                            'line' => $this->findLineNumber($content, $matches[0][0]),
                            'message' => "Potential N+1 query: accessing '{$relation}' relationship in a loop without eager loading.",
                            'recommendation' => "Add ->with('{$relation}') to your query, or use eager loading: Model::with('{$relation}')->get().",
                        ];
                    }
                }
            }

            if (preg_match_all('/foreach\s*\(\s*\$\w+->(\w+)\s+as/', $content, $matches)) {
                foreach ($matches[1] as $relation) {
                    if ($this->isLikelyRelationship($relation)) {
                        $findings[] = [
                            'severity' => 'warning',
                            'type' => 'n_plus_one',
                            'file' => $file,
                            'line' => $this->findLineNumber($content, $matches[0][0]),
                            'message' => "Potential N+1 query: accessing '{$relation}' relationship in a PHP loop without eager loading.",
                            'recommendation' => "Add ->with('{$relation}') to your query, or use eager loading: Model::with('{$relation}')->get().",
                        ];
                    }
                }
            }
        }

        return $findings;
    }

    protected function analyzeBladeTemplates(LaravelInspector $inspector): array
    {
        $findings = [];
        $views = $inspector->getViews();

        foreach ($views as $viewFile) {
            $content = $inspector->getFileContents($viewFile);
            if ($content === null) {
                continue;
            }

            $relativePath = str_replace($inspector->getBasePath() . '/', '', $viewFile);

            if (preg_match_all('/->(\w+)\s*\)/', $content, $matches)) {
                foreach ($matches[1] as $relation) {
                    if ($this->isLikelyRelationship($relation) && ! in_array($relation, ['keys', 'values', 'all', 'first', 'last', 'count', 'isEmpty'])) {
                        $findings[] = [
                            'severity' => 'info',
                            'type' => 'n_plus_one_view',
                            'file' => $relativePath,
                            'line' => $this->findLineNumber($content, $matches[0][0]),
                            'message' => "Potential N+1 query: accessing '{$relation}' in view. Ensure eager loading in the controller.",
                            'recommendation' => "Verify that the controller providing data to this view eager loads the '{$relation}' relationship.",
                        ];
                    }
                }
            }
        }

        return $findings;
    }

    protected function isLikelyRelationship(string $name): bool
    {
        $excluded = [
            'id', 'created_at', 'updated_at', 'deleted_at', 'email', 'name',
            'password', 'remember_token', 'email_verified_at', 'type', 'status',
            'title', 'description', 'body', 'content', 'slug', 'url', 'path',
        ];

        return ! in_array(strtolower($name), $excluded) && strlen($name) > 2;
    }

    protected function findLineNumber(string $content, string $needle): int
    {
        $lines = explode("\n", $content);
        foreach ($lines as $index => $line) {
            if (str_contains($line, $needle)) {
                return $index + 1;
            }
        }

        return 1;
    }
}
