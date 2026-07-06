<?php

namespace LaravelAssist\Assistant\Analyzers\Security;

use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;

class MassAssignmentAnalyzer implements AnalyzerInterface
{
    public function analyze(LaravelInspector $inspector): array
    {
        $findings = [];
        $models = $inspector->getModels();

        foreach ($models as $modelClass => $file) {
            $fullPath = $inspector->getBasePath() . '/' . $file;
            $content = $inspector->getFileContents($fullPath);

            if ($content === null) {
                continue;
            }

            $guardedEmpty = $this->hasEmptyGuarded($content);
            $fillable = $this->getFillable($content);

            if ($guardedEmpty && empty($fillable)) {
                $findings[] = [
                    'severity' => 'critical',
                    'type' => 'mass_assignment',
                    'file' => $file,
                    'line' => 0,
                    'message' => "Model {$this->getShortName($modelClass)} has empty \$guarded and no \$fillable defined. All attributes are mass-assignable.",
                    'recommendation' => "Define \$fillable array with allowed attributes, or set \$guarded to prevent mass assignment vulnerabilities.",
                ];
            } elseif ($guardedEmpty) {
                $findings[] = [
                    'severity' => 'warning',
                    'type' => 'mass_assignment',
                    'file' => $file,
                    'line' => 0,
                    'message' => "Model {$this->getShortName($modelClass)} has empty \$guarded. While \$fillable is defined, consider using \$guarded for better security.",
                    'recommendation' => "Use \$guarded with an empty array only if you are sure no attributes should be mass-assigned, or switch to explicit \$fillable.",
                ];
            }
        }

        return $findings;
    }

    public function getName(): string
    {
        return 'Mass Assignment';
    }

    public function getDescription(): string
    {
        return 'Detects models with overly permissive mass assignment protection.';
    }

    public function getCategory(): string
    {
        return 'security';
    }

    protected function hasEmptyGuarded(string $content): bool
    {
        if (preg_match('/protected\s+\\\\?\$guarded\s*=\s*\[\s*\]/', $content)) {
            return true;
        }

        if (preg_match('/protected\s+\\\\?\$guarded\s*=\s*array\s*\(\s*\)/', $content)) {
            return true;
        }

        return false;
    }

    protected function getFillable(string $content): array
    {
        if (preg_match('/protected\s+\\\\?\$fillable\s*=\s*\[(.*?)\]/s', $content, $matches)) {
            preg_match_all('/[\'"](\w+)[\'"]/', $matches[1], $fillMatches);
            return $fillMatches[1] ?? [];
        }

        return [];
    }

    protected function getShortName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }
}
