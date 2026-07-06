<?php

namespace LaravelAssist\Assistant\Contracts;

interface FormatterInterface
{
    /**
     * Format the analysis result into a readable output.
     *
     * @param array{findings: array, summary: array} $result
     */
    public function format(array $result): string;
}
