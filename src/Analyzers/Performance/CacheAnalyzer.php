<?php

namespace LaravelAssist\Assistant\Analyzers\Performance;

use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;

class CacheAnalyzer implements AnalyzerInterface
{
    public function analyze(LaravelInspector $inspector): array
    {
        $findings = [];
        $phpFiles = $inspector->getPhpFiles($inspector->getBasePath() . '/app');

        foreach ($phpFiles as $file) {
            $content = $inspector->getFileContents($file);
            if ($content === null) {
                continue;
            }

            $relativePath = str_replace($inspector->getBasePath() . '/', '', $file);

            if (preg_match_all('/DB::table\s*\(\s*[\'"](\w+)[\'"]\s*\)->(get|count|sum|avg|first)\s*\(/', $content, $matches)) {
                foreach ($matches[1] as $index => $table) {
                    $method = $matches[2][$index];
                    $hasCache = str_contains($content, 'Cache::')
                        || str_contains($content, 'cache(')
                        || str_contains($content, '->remember(')
                        || str_contains($content, '->rememberForever(');

                    if (! $hasCache) {
                        $findings[] = [
                            'severity' => 'info',
                            'type' => 'cache_opportunity',
                            'file' => $relativePath,
                            'line' => $this->findLineNumber($content, $matches[0][$index]),
                            'message' => "Query on table '{$table}' using {$method}() could benefit from caching.",
                            'recommendation' => "Wrap the query with Cache::remember('{$table}.{$method}', 60, fn() => ...).",
                        ];
                    }
                }
            }

            if (preg_match_all('/Model::all\s*\(\s*\)/', $content, $matches)) {
                $hasCache = str_contains($content, 'Cache::');
                if (! $hasCache) {
                    $findings[] = [
                        'severity' => 'info',
                        'type' => 'cache_opportunity',
                        'file' => $relativePath,
                        'line' => $this->findLineNumber($content, $matches[0][0]),
                        'message' => 'Model::all() fetches all records without caching.',
                        'recommendation' => 'Consider using Cache::remember() for frequently accessed datasets.',
                    ];
                }
            }
        }

        return $findings;
    }

    public function getName(): string
    {
        return 'Cache Opportunities';
    }

    public function getDescription(): string
    {
        return 'Identifies queries and data fetches that could benefit from caching.';
    }

    public function getCategory(): string
    {
        return 'performance';
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
