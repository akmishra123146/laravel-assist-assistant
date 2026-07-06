<?php

namespace LaravelAssist\Assistant\AI\Providers;

use Illuminate\Support\Facades\Http;
use LaravelAssist\Assistant\AI\AiProviderInterface;

class ClaudeProvider implements AiProviderInterface
{
    protected string $apiKey;

    protected string $model;

    protected int $timeout;

    public function __construct(string $apiKey, string $model = 'claude-3-sonnet-20240229', int $timeout = 30)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->timeout = $timeout;
    }

    public function enhance(array $findings): array
    {
        if (empty($this->apiKey) || empty($findings)) {
            return $findings;
        }

        $prompt = $this->buildPrompt($findings);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout($this->timeout)->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 2000,
                'system' => 'You are a Laravel expert. Analyze code findings and provide concise, actionable insights. Return a JSON array with the same findings, each with an added "ai_insight" field containing a brief expert recommendation.',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $content = $body['content'][0]['text'] ?? '';
                $enhanced = json_decode($content, true);

                if (is_array($enhanced)) {
                    return $this->mergeEnhancements($findings, $enhanced);
                }
            }
        } catch (\Throwable) {
            // Return original findings if AI enhancement fails
        }

        return $findings;
    }

    public function getName(): string
    {
        return 'Claude';
    }

    protected function buildPrompt(array $findings): string
    {
        $findingsJson = json_encode(array_map(function ($f) {
            return [
                'severity' => $f['severity'],
                'type' => $f['type'],
                'file' => $f['file'],
                'message' => $f['message'],
                'recommendation' => $f['recommendation'],
            ];
        }, $findings), JSON_PRETTY_PRINT);

        return "Analyze these Laravel code findings and provide enhanced recommendations:\n\n{$findingsJson}\n\nReturn a JSON array with the same findings, each with an added 'ai_insight' field.";
    }

    protected function mergeEnhancements(array $original, array $enhanced): array
    {
        $result = [];
        foreach ($original as $index => $finding) {
            $enhancedFinding = $enhanced[$index] ?? $finding;
            $result[] = array_merge($finding, [
                'ai_insight' => $enhancedFinding['ai_insight'] ?? '',
            ]);
        }

        return $result;
    }
}
