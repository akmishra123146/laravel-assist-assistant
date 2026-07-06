<?php

namespace LaravelAssist\Assistant\Analyzers\Performance;

use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;

class QueueAnalyzer implements AnalyzerInterface
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

            if (preg_match_all('/dispatch\s*\(\s*new\s+(\w+)/', $content, $matches)) {
                foreach ($matches[1] as $index => $jobClass) {
                    if (str_contains($content, 'dispatchSync(') || str_contains($content, 'Bus::dispatchNow(')) {
                        $findings[] = [
                            'severity' => 'warning',
                            'type' => 'sync_dispatch',
                            'file' => $relativePath,
                            'line' => $this->findLineNumber($content, $matches[0][$index]),
                            'message' => "Job '{$jobClass}' is dispatched synchronously, blocking the request.",
                            'recommendation' => 'Use dispatch() instead of dispatchSync() for non-critical background work.',
                        ];
                    }
                }
            }

            if (preg_match_all('/dispatchSync\s*\(/', $content, $matches)) {
                foreach ($matches[0] as $index => $match) {
                    $findings[] = [
                        'severity' => 'warning',
                        'type' => 'sync_dispatch',
                        'file' => $relativePath,
                        'line' => $this->findLineNumber($content, $match),
                        'message' => 'Synchronous dispatch detected. This blocks the request until the job completes.',
                        'recommendation' => 'Use dispatch() for background processing to improve response times.',
                    ];
                }
            }
        }

        $jobFiles = $inspector->getPhpFiles($inspector->getBasePath() . '/app/Jobs');
        foreach ($jobFiles as $file) {
            $content = $inspector->getFileContents($file);
            if ($content === null) {
                continue;
            }

            $relativePath = str_replace($inspector->getBasePath() . '/', '', $file);

            if (str_contains($content, 'implements ShouldQueue') && ! str_contains($content, 'public $tries')) {
                $findings[] = [
                    'severity' => 'info',
                    'type' => 'missing_retry',
                    'file' => $relativePath,
                    'line' => 0,
                    'message' => 'Queue job does not define $tries property. Will retry indefinitely on failure.',
                    'recommendation' => 'Add public int $tries = 3; to limit retry attempts.',
                ];
            }

            if (str_contains($content, 'implements ShouldQueue') && ! str_contains($content, 'failed(')) {
                $findings[] = [
                    'severity' => 'info',
                    'type' => 'missing_failed_handler',
                    'file' => $relativePath,
                    'line' => 0,
                    'message' => 'Queue job does not implement a failed() method for error handling.',
                    'recommendation' => 'Add a public function failed(Throwable $exception) method to handle failures gracefully.',
                ];
            }
        }

        return $findings;
    }

    public function getName(): string
    {
        return 'Queue Analysis';
    }

    public function getDescription(): string
    {
        return 'Detects synchronous dispatches, missing retry logic, and queue bottlenecks.';
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
