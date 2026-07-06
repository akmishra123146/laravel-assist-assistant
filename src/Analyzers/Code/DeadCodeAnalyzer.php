<?php

namespace LaravelAssist\Assistant\Analyzers\Code;

use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;

class DeadCodeAnalyzer implements AnalyzerInterface
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

            if (preg_match_all('/private\s+function\s+(\w+)\s*\(/', $content, $matches)) {
                foreach ($matches[1] as $index => $methodName) {
                    $callPattern = '/\$this->' . preg_quote($methodName, '/') . '\s*\(/';
                    $declarations = substr_count($content, 'function ' . $methodName);

                    if (! preg_match($callPattern, $content) && $declarations <= 1) {
                        $findings[] = [
                            'severity' => 'info',
                            'type' => 'dead_code',
                            'file' => $relativePath,
                            'line' => $this->findLineNumber($content, 'function ' . $methodName),
                            'message' => "Private method '{$methodName}' is never called within this class.",
                            'recommendation' => 'Remove unused private methods to reduce code complexity.',
                        ];
                    }
                }
            }

            if (preg_match_all('/protected\s+function\s+(\w+)\s*\(/', $content, $matches)) {
                foreach ($matches[1] as $index => $methodName) {
                    $callPattern = '/\$this->' . preg_quote($methodName, '/') . '\s*\(/';
                    $declarations = substr_count($content, 'function ' . $methodName);

                    if (! preg_match($callPattern, $content) && $declarations <= 1) {
                        $findings[] = [
                            'severity' => 'info',
                            'type' => 'dead_code',
                            'file' => $relativePath,
                            'line' => $this->findLineNumber($content, 'function ' . $methodName),
                            'message' => "Protected method '{$methodName}' may not be called within this class. Check subclasses.",
                            'recommendation' => 'Verify if this method is used by child classes before removing.',
                        ];
                    }
                }
            }
        }

        return $findings;
    }

    public function getName(): string
    {
        return 'Dead Code';
    }

    public function getDescription(): string
    {
        return 'Detects unused private and protected methods within classes.';
    }

    public function getCategory(): string
    {
        return 'code';
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
