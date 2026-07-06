<?php

namespace LaravelAssist\Assistant\Reports\Formatters;

use LaravelAssist\Assistant\Contracts\FormatterInterface;

class JsonFormatter implements FormatterInterface
{
    public function format(array $result): string
    {
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
