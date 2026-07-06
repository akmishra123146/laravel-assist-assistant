<?php

namespace LaravelAssist\Assistant\Reports;

use LaravelAssist\Assistant\Reports\Formatters\ConsoleFormatter;
use LaravelAssist\Assistant\Reports\Formatters\HtmlFormatter;
use LaravelAssist\Assistant\Reports\Formatters\JsonFormatter;

class ReportGenerator
{
    public function generate(array $result, string $format = 'console'): string
    {
        $formatter = $this->getFormatter($format);

        return $formatter->format($result);
    }

    public function saveReport(string $report, string $path): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $report);
    }

    protected function getFormatter(string $format): \LaravelAssist\Assistant\Contracts\FormatterInterface
    {
        return match ($format) {
            'html' => new HtmlFormatter(),
            'json' => new JsonFormatter(),
            default => new ConsoleFormatter(),
        };
    }
}
