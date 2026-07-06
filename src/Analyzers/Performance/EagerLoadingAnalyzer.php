<?php

namespace LaravelAssist\Assistant\Analyzers\Performance;

use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;

class EagerLoadingAnalyzer implements AnalyzerInterface
{
    public function analyze(LaravelInspector $inspector): array
    {
        $findings = [];
        $phpFiles = $inspector->getPhpFiles($inspector->getBasePath() . '/app/Http');

        foreach ($phpFiles as $file) {
            $content = $inspector->getFileContents($file);
            if ($content === null) {
                continue;
            }

            $relativePath = str_replace($inspector->getBasePath() . '/', '', $file);

            if (preg_match_all('/(\w+)::(where|latest|oldest|orderBy|paginate|get|first|find)\s*\(/', $content, $matches)) {
                foreach ($matches[1] as $index => $model) {
                    $method = $matches[2][$index];
                    $hasEagerLoad = str_contains($content, '->with(')
                        || str_contains($content, '->withCount(')
                        || str_contains($content, '->load(')
                        || str_contains($content, '->loadCount(');

                    if (! $hasEagerLoad && $method !== 'find') {
                        $lineNum = $this->findLineNumber($content, $matches[0][$index]);
                        $context = $this->getContextLines($content, $lineNum, 5);

                        if ($this->mayAccessRelationship($context)) {
                            $findings[] = [
                                'severity' => 'warning',
                                'type' => 'eager_loading',
                                'file' => $relativePath,
                                'line' => $lineNum,
                                'message' => "Query on '{$model}' does not use eager loading. This may cause N+1 queries.",
                                'recommendation' => "Add ->with('relationshipName') to eagerly load related models.",
                            ];
                        }
                    }
                }
            }
        }

        return $findings;
    }

    public function getName(): string
    {
        return 'Eager Loading';
    }

    public function getDescription(): string
    {
        return 'Suggests eager loading for queries where relationships are likely accessed.';
    }

    public function getCategory(): string
    {
        return 'performance';
    }

    protected function mayAccessRelationship(string $context): bool
    {
        return str_contains($context, '->')
            || str_contains($context, '@foreach')
            || str_contains($context, 'foreach');
    }

    protected function getContextLines(string $content, int $lineNumber, int $radius): string
    {
        $lines = explode("\n", $content);
        $start = max(0, $lineNumber - $radius - 1);
        $end = min(count($lines), $lineNumber + $radius);

        return implode("\n", array_slice($lines, $start, $end - $start));
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
