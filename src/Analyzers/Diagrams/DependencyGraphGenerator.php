<?php

namespace LaravelAssist\Assistant\Analyzers\Diagrams;

use LaravelAssist\Assistant\Support\LaravelInspector;

class DependencyGraphGenerator
{
    protected LaravelInspector $inspector;

    public function __construct(LaravelInspector $inspector)
    {
        $this->inspector = $inspector;
    }

    public function generate(): string
    {
        $diagram = ['graph TD'];
        $nodes = [];
        $edges = [];

        $controllers = $this->inspector->getControllers();
        $providers = $this->inspector->getProviders();
        $jobs = $this->inspector->getJobs();
        $models = $this->inspector->getModels();

        foreach ($controllers as $controllerClass => $file) {
            $shortName = class_basename($controllerClass);
            $nodeId = 'C_' . md5($controllerClass);
            $nodes[] = "    {$nodeId}[\"{$shortName}\"]";
        }

        foreach ($models as $modelClass => $file) {
            $shortName = class_basename($modelClass);
            $nodeId = 'M_' . md5($modelClass);
            $nodes[] = "    {$nodeId}{{\"{$shortName}\"}}";
        }

        foreach ($providers as $providerClass => $file) {
            $shortName = class_basename($providerClass);
            $nodeId = 'P_' . md5($providerClass);
            $nodes[] = "    {$nodeId}(\"{$shortName}\")";
        }

        foreach ($jobs as $jobClass => $file) {
            $shortName = class_basename($jobClass);
            $nodeId = 'J_' . md5($jobClass);
            $nodes[] = "    {$nodeId}[\"{$shortName}\"]:::job";
        }

        foreach ($controllers as $controllerClass => $file) {
            $controllerId = 'C_' . md5($controllerClass);
            $content = $this->inspector->getFileContents($this->inspector->getBasePath() . '/' . $file);

            if ($content !== null) {
                foreach ($models as $modelClass => $modelFile) {
                    $shortName = class_basename($modelClass);
                    if (str_contains($content, $shortName)) {
                        $modelId = 'M_' . md5($modelClass);
                        $edges[] = "    {$controllerId} --> {$modelId}";
                    }
                }
            }
        }

        $diagram = array_merge($diagram, $nodes, $edges);
        $diagram[] = '';
        $diagram[] = '    classDef job fill:#f9f,stroke:#333,stroke-width:2px;';

        return implode("\n", $diagram);
    }
}
