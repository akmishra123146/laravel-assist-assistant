<?php

namespace LaravelAssist\Assistant\AI;

use LaravelAssist\Assistant\AI\Providers\OpenAiProvider;
use LaravelAssist\Assistant\AI\Providers\ClaudeProvider;
use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;

class RulesEngine
{
    protected array $analyzerConfig;

    protected array $aiConfig;

    /** @var array<string, AnalyzerInterface> */
    protected array $analyzers = [];

    public function __construct(array $analyzerConfig, array $aiConfig)
    {
        $this->analyzerConfig = $analyzerConfig;
        $this->aiConfig = $aiConfig;
    }

    /**
     * Register an analyzer instance.
     */
    public function registerAnalyzer(string $key, AnalyzerInterface $analyzer): static
    {
        $this->analyzers[$key] = $analyzer;

        return $this;
    }

    /**
     * Get all registered analyzers.
     *
     * @return array<string, AnalyzerInterface>
     */
    public function getAnalyzers(): array
    {
        return $this->analyzers;
    }

    /**
     * Run all enabled analyzers and collect findings.
     *
     * @return array{findings: array<int, array{severity: string, type: string, file: string, line: int, message: string, recommendation: string}>, summary: array{total: int, critical: int, warning: int, info: int}}
     */
    public function run(LaravelInspector $inspector): array
    {
        $allFindings = [];

        $enabledAnalyzers = $this->getEnabledAnalyzers();

        foreach ($enabledAnalyzers as $key => $analyzer) {
            if (isset($this->analyzers[$key])) {
                $findings = $this->analyzers[$key]->analyze($inspector);
                $allFindings = array_merge($allFindings, $findings);
            }
        }

        usort($allFindings, function ($a, $b) {
            $order = ['critical' => 0, 'warning' => 1, 'info' => 2];
            return ($order[$a['severity']] ?? 3) - ($order[$b['severity']] ?? 3);
        });

        $summary = [
            'total' => count($allFindings),
            'critical' => count(array_filter($allFindings, fn ($f) => $f['severity'] === 'critical')),
            'warning' => count(array_filter($allFindings, fn ($f) => $f['severity'] === 'warning')),
            'info' => count(array_filter($allFindings, fn ($f) => $f['severity'] === 'info')),
        ];

        return [
            'findings' => $allFindings,
            'summary' => $summary,
        ];
    }

    /**
     * Run analysis and optionally enhance with AI.
     *
     * @return array{findings: array, summary: array, ai_enhanced: bool}
     */
    public function runWithAi(LaravelInspector $inspector): array
    {
        $result = $this->run($inspector);

        if ($this->aiConfig['enabled'] && ! empty($this->aiConfig['api_key'])) {
            $provider = $this->getAiProvider();
            if ($provider) {
                $result['findings'] = $provider->enhance($result['findings']);
                $result['ai_enhanced'] = true;
            } else {
                $result['ai_enhanced'] = false;
            }
        } else {
            $result['ai_enhanced'] = false;
        }

        return $result;
    }

    protected function getEnabledAnalyzers(): array
    {
        $enabled = [];

        foreach ($this->analyzerConfig as $category => $analyzers) {
            if (is_array($analyzers)) {
                foreach ($analyzers as $name => $active) {
                    if ($active === true) {
                        $enabled[$name] = true;
                    }
                }
            }
        }

        return $enabled;
    }

    protected function getAiProvider(): ?AiProviderInterface
    {
        $providerName = $this->aiConfig['provider'] ?? 'openai';

        return match ($providerName) {
            'openai' => new OpenAiProvider(
                $this->aiConfig['api_key'] ?? '',
                $this->aiConfig['model'] ?? 'gpt-4',
                $this->aiConfig['timeout'] ?? 30
            ),
            'claude' => new ClaudeProvider(
                $this->aiConfig['api_key'] ?? '',
                $this->aiConfig['model'] ?? 'claude-3-sonnet-20240229',
                $this->aiConfig['timeout'] ?? 30
            ),
            default => null,
        };
    }
}
