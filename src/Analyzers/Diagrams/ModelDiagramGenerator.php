<?php

namespace LaravelAssist\Assistant\Analyzers\Diagrams;

use LaravelAssist\Assistant\Support\LaravelInspector;
use LaravelAssist\Assistant\Support\ModelResolver;

class ModelDiagramGenerator
{
    protected LaravelInspector $inspector;

    protected ModelResolver $modelResolver;

    public function __construct(LaravelInspector $inspector)
    {
        $this->inspector = $inspector;
        $this->modelResolver = new ModelResolver();
    }

    public function generate(bool $showColumns = false): string
    {
        $models = $this->inspector->getModels();
        $diagram = ['erDiagram'];

        $relationships = [];

        foreach ($models as $modelClass => $file) {
            $shortName = class_basename($modelClass);
            $diagram[] = "    {$shortName} {";

            if ($showColumns) {
                $guarded = $this->modelResolver->getGuardedAttributes($modelClass);
                if (! empty($guarded['fillable'])) {
                    foreach ($guarded['fillable'] as $column) {
                        $diagram[] = "        string {$column}";
                    }
                }
            }

            $diagram[] = "    }";

            $relations = $this->modelResolver->getRelationships($modelClass);
            foreach ($relations as $relation) {
                $relatedShort = class_basename($relation['related']);
                $relationships[] = [
                    'from' => $shortName,
                    'to' => $relatedShort,
                    'type' => $relation['type'],
                    'name' => $relation['name'],
                ];
            }
        }

        foreach ($relationships as $rel) {
            $symbol = match ($rel['type']) {
                'hasMany' => '||--o{',
                'hasOne' => '||--||',
                'belongsTo' => '}o--||',
                'belongsToMany' => '}o--o{',
                'morphTo' => '||--o{',
                'morphMany' => '||--o{',
                default => '||--o{',
            };

            $diagram[] = "    {$rel['from']} {$symbol} {$rel['to']} : \"{$rel['name']}\"";
        }

        return implode("\n", $diagram);
    }
}
