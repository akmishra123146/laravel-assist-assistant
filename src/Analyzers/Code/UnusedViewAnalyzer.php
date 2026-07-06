<?php

namespace LaravelAssist\Assistant\Analyzers\Code;

use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;

class UnusedViewAnalyzer implements AnalyzerInterface
{
    public function analyze(LaravelInspector $inspector): array
    {
        $findings = [];
        $views = $inspector->getViews();

        $referencedViews = $this->getReferencedViews($inspector);

        foreach ($views as $viewFile) {
            $relativePath = str_replace($inspector->getBasePath() . '/', '', $viewFile);
            $viewName = $this->getBladeViewName($viewFile, $inspector);

            if ($viewName && ! isset($referencedViews[$viewName])) {
                $findings[] = [
                    'severity' => 'warning',
                    'type' => 'unused_view',
                    'file' => $relativePath,
                    'line' => 0,
                    'message' => "View '{$viewName}' is not referenced by any view() call, @extends, or @include.",
                    'recommendation' => 'Remove unused views or ensure they are properly referenced.',
                ];
            }
        }

        return $findings;
    }

    public function getName(): string
    {
        return 'Unused Views';
    }

    public function getDescription(): string
    {
        return 'Finds Blade views that are not rendered by any view() call or template directive.';
    }

    public function getCategory(): string
    {
        return 'code';
    }

    protected function getReferencedViews(LaravelInspector $inspector): array
    {
        $referenced = [];

        $phpFiles = $inspector->getPhpFiles($inspector->getBasePath() . '/app');
        foreach ($phpFiles as $file) {
            $content = $inspector->getFileContents($file);
            if ($content === null) {
                continue;
            }

            if (preg_match_all('/view\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                foreach ($matches[1] as $viewName) {
                    $referenced[$viewName] = true;
                }
            }
        }

        $bladeFiles = $inspector->getViews();
        foreach ($bladeFiles as $bladeFile) {
            $content = $inspector->getFileContents($bladeFile);
            if ($content === null) {
                continue;
            }

            if (preg_match_all('/@extends\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                foreach ($matches[1] as $viewName) {
                    $referenced[$viewName] = true;
                }
            }

            if (preg_match_all('/@include\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                foreach ($matches[1] as $viewName) {
                    $referenced[$viewName] = true;
                }
            }

            if (preg_match_all('/@component\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                foreach ($matches[1] as $viewName) {
                    $referenced[$viewName] = true;
                }
            }
        }

        return $referenced;
    }

    protected function getBladeViewName(string $filePath, LaravelInspector $inspector): ?string
    {
        $viewPath = $inspector->getBasePath() . '/resources/views';
        $relativePath = str_replace($viewPath . '/', '', $filePath);
        $viewName = str_replace(['.blade.php', '/'], ['', '.'], $relativePath);

        return $viewName;
    }
}
