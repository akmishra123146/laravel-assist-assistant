<?php

namespace LaravelAssist\Assistant\Analyzers\Database;

use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;

class MissingIndexAnalyzer implements AnalyzerInterface
{
    public function analyze(LaravelInspector $inspector): array
    {
        $findings = [];
        $indexedColumns = $this->getIndexedColumns($inspector);

        $modelFiles = $inspector->getPhpFiles($inspector->getBasePath() . '/app');
        foreach ($modelFiles as $file) {
            $content = $inspector->getFileContents($file);
            if ($content === null) {
                continue;
            }

            $usagePatterns = [
                'where' => '/->where\s*\(\s*[\'"](\w+)[\'"]/',
                'orderBy' => '/->orderBy\s*\(\s*[\'"](\w+)[\'"]/',
                'join' => '/->join\s*\(\s*[\'"]\w+[\'"]\s*,\s*[\'"](\w+)[\'"]/',
            ];

            foreach ($usagePatterns as $type => $pattern) {
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[1] as $column) {
                        if (in_array($column, ['id', 'created_at', 'updated_at', 'type', 'name'])) {
                            continue;
                        }

                        $relativePath = str_replace($inspector->getBasePath() . '/', '', $file);
                        $findings[] = [
                            'severity' => 'warning',
                            'type' => 'missing_index',
                            'file' => $relativePath,
                            'line' => $this->findLineNumber($content, $matches[0][0]),
                            'message' => "Column '{$column}' used in {$type}() may need a database index.",
                            'recommendation' => "Add \$table->index('{$column}') to a migration for better query performance.",
                        ];
                    }
                }
            }
        }

        return $findings;
    }

    public function getName(): string
    {
        return 'Missing Indexes';
    }

    public function getDescription(): string
    {
        return 'Detects database columns used in WHERE/JOIN/ORDER BY without indexes.';
    }

    public function getCategory(): string
    {
        return 'database';
    }

    protected function getIndexedColumns(LaravelInspector $inspector): array
    {
        $indexed = ['id'];

        $migrations = $inspector->getMigrations();
        foreach ($migrations as $migration) {
            $content = $inspector->getFileContents($migration);
            if ($content === null) {
                continue;
            }

            if (preg_match_all('/->index\(\s*\)/', $content, $matches)) {
                preg_match_all('/->(unsignedInteger|integer|string|text|boolean)\s*\(\s*[\'"](\w+)[\'"]/', $content, $colMatches);
                foreach ($colMatches[2] as $col) {
                    $indexed[] = $col;
                }
            }

            if (preg_match_all('/->foreign\s*\(\s*[\'"](\w+)[\'"]/', $content, $fkMatches)) {
                $indexed = array_merge($indexed, $fkMatches[1]);
            }
        }

        return array_unique($indexed);
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
