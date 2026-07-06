<?php

namespace LaravelAssist\Assistant\AI;

interface AiProviderInterface
{
    /**
     * Send findings to the AI provider for enhanced recommendations.
     *
     * @param array<int, array{severity: string, type: string, file: string, line: int, message: string, recommendation: string}> $findings
     * @return array<int, array{severity: string, type: string, file: string, line: int, message: string, recommendation: string, ai_insight: string}>
     */
    public function enhance(array $findings): array;

    /**
     * Get the provider name.
     */
    public function getName(): string;
}
