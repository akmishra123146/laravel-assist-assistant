<?php

namespace LaravelAssist\Assistant\Contracts;

use LaravelAssist\Assistant\Support\LaravelInspector;

interface AnalyzerInterface
{
    /**
     * Run the analysis and return findings.
     *
     * @return array<int, array{severity: string, type: string, file: string, line: int, message: string, recommendation: string}>
     */
    public function analyze(LaravelInspector $inspector): array;

    /**
     * Get the display name of this analyzer.
     */
    public function getName(): string;

    /**
     * Get a brief description of what this analyzer checks.
     */
    public function getDescription(): string;

    /**
     * Get the category this analyzer belongs to.
     */
    public function getCategory(): string;
}
