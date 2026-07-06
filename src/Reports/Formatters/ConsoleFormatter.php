<?php

namespace LaravelAssist\Assistant\Reports\Formatters;

use LaravelAssist\Assistant\Contracts\FormatterInterface;

class ConsoleFormatter implements FormatterInterface
{
    public function format(array $result): string
    {
        $findings = $result['findings'] ?? [];
        $summary = $result['summary'] ?? [];

        $output = [];
        $output[] = '';
        $output[] = '  Laravel Assistant - Analysis Report';
        $output[] = '  ====================================';
        $output[] = '';
        $output[] = '  Summary:';
        $output[] = '  ─────────────────────────────────────';
        $output[] = "  Total:     {$summary['total']}";
        $output[] = "  Critical:  {$summary['critical']}";
        $output[] = "  Warning:   {$summary['warning']}";
        $output[] = "  Info:      {$summary['info']}";
        $output[] = '';

        $score = $this->calculateScore($summary);
        $output[] = "  Health Score: {$score}/100";
        $output[] = '';

        if (! empty($findings)) {
            $output[] = '  Findings:';
            $output[] = '  ─────────────────────────────────────';
            $output[] = '';

            foreach ($findings as $finding) {
                $severity = $finding['severity'];
                $icon = match ($severity) {
                    'critical' => '✗',
                    'warning' => '⚠',
                    'info' => 'ℹ',
                    default => '•',
                };

                $output[] = "  {$icon} [{$severity}] {$finding['message']}";
                $output[] = "    File: {$finding['file']}" . ($finding['line'] > 0 ? ":{$finding['line']}" : '');

                if (! empty($finding['recommendation'])) {
                    $output[] = "    Fix: {$finding['recommendation']}";
                }

                if (! empty($finding['ai_insight'])) {
                    $output[] = "    AI: {$finding['ai_insight']}";
                }

                $output[] = '';
            }
        }

        return implode("\n", $output);
    }

    protected function calculateScore(array $summary): int
    {
        if ($summary['total'] === 0) {
            return 100;
        }

        $score = 100;
        $score -= $summary['critical'] * 15;
        $score -= $summary['warning'] * 5;
        $score -= $summary['info'] * 1;

        return max(0, min(100, $score));
    }
}
