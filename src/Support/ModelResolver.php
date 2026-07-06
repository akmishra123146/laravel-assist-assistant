<?php

namespace LaravelAssist\Assistant\Support;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class ModelResolver
{
    /**
     * Get all relationship methods from a model class.
     *
     * @return array<int, array{name: string, type: string, related: string}>
     */
    public function getRelationships(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        $relationships = [];
        $reflection = new ReflectionClass($modelClass);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        $relationshipTypes = [
            'hasOne', 'hasMany', 'belongsTo', 'belongsToMany',
            'morphTo', 'morphMany', 'morphToMany', 'morphedByMany',
            'hasOneThrough', 'hasManyThrough',
        ];

        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() === $modelClass
                && ! str_starts_with($method->getName(), 'get')
                && ! str_starts_with($method->getName(), 'set')
                && ! str_starts_with($method->getName(), 'scope')
                && ! str_starts_with($method->getName(), '__')
            ) {
                $body = $this->getMethodBody($method);
                foreach ($relationshipTypes as $type) {
                    if (str_contains($body, '$this->' . $type . '(')) {
                        $related = $this->extractRelatedClass($body, $type);
                        $relationships[] = [
                            'name' => $method->getName(),
                            'type' => $type,
                            'related' => $related,
                        ];
                        break;
                    }
                }
            }
        }

        return $relationships;
    }

    /**
     * Get the fillable and guarded attributes of a model.
     */
    public function getGuardedAttributes(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            return ['fillable' => [], 'guarded' => [], 'guarded_empty' => false];
        }

        $reflection = new ReflectionClass($modelClass);

        $fillable = $this->getStaticPropertyValue($reflection, 'fillable', []);
        $guarded = $this->getStaticPropertyValue($reflection, 'guarded', []);

        return [
            'fillable' => $fillable,
            'guarded' => $guarded,
            'guarded_empty' => $guarded === [],
        ];
    }

    /**
     * Get the table name for a model.
     */
    public function getTableName(string $modelClass): ?string
    {
        if (! class_exists($modelClass)) {
            return null;
        }

        $reflection = new ReflectionClass($modelClass);

        if ($reflection->hasProperty('table')) {
            $property = $reflection->getProperty('table');
            $property->setAccessible(true);
            $instance = $reflection->newInstanceWithoutConstructor();

            return $property->getValue($instance);
        }

        return null;
    }

    protected function getMethodBody(ReflectionMethod $method): string
    {
        $fileName = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if ($fileName === false || $startLine === false || $endLine === false) {
            return '';
        }

        $lines = file($fileName);
        if ($lines === false) {
            return '';
        }

        return implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
    }

    protected function extractRelatedClass(string $body, string $type): string
    {
        $pattern = '/\$this->' . preg_quote($type, '/') . '\(\s*([A-Z]\w+(?:::[A-Z]\w+)*)/';
        if (preg_match($pattern, $body, $matches)) {
            return $matches[1];
        }

        return 'Unknown';
    }

    protected function getStaticPropertyValue(ReflectionClass $reflection, string $propertyName, mixed $default): mixed
    {
        if (! $reflection->hasProperty($propertyName)) {
            return $default;
        }

        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        try {
            $instance = $reflection->newInstanceWithoutConstructor();

            return $property->getValue($instance);
        } catch (\Throwable) {
            return $default;
        }
    }
}
