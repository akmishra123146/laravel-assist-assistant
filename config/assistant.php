<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled Analyzers
    |--------------------------------------------------------------------------
    |
    | Control which analyzers are active. Set to false to disable any analyzer.
    | Categories: database, code, security, performance
    |
    */

    'analyzers' => [

        'database' => [
            'missing_index' => true,
            'n_plus_one' => true,
        ],

        'code' => [
            'unused_route' => true,
            'unused_controller' => true,
            'unused_view' => true,
            'dead_code' => true,
            'unused_service_provider' => true,
        ],

        'security' => [
            'mass_assignment' => true,
            'debug_settings' => true,
            'rate_limit' => true,
        ],

        'performance' => [
            'cache' => true,
            'queue' => true,
            'eager_loading' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths
    |--------------------------------------------------------------------------
    |
    | Directories and files to exclude from analysis.
    | Paths are relative to the project root.
    |
    */

    'excludes' => [
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
        'tests',
        '.git',
    ],

    /*
    |--------------------------------------------------------------------------
    | Severity Levels
    |--------------------------------------------------------------------------
    |
    | Minimum severity level to report. Options: critical, warning, info
    |
    */

    'min_severity' => 'info',

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the optional AI enhancement layer. When enabled, findings
    | are sent to an LLM for enriched recommendations.
    |
    */

    'ai' => [
        'enabled' => env('ASSISTANT_AI_ENABLED', false),
        'provider' => env('ASSISTANT_AI_PROVIDER', 'openai'),
        'api_key' => env('ASSISTANT_AI_API_KEY'),
        'model' => env('ASSISTANT_AI_MODEL', 'gpt-4'),
        'timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Configuration
    |--------------------------------------------------------------------------
    |
    | Default output format and path for reports.
    |
    */

    'output' => [
        'format' => env('ASSISTANT_OUTPUT_FORMAT', 'console'),
        'path' => env('ASSISTANT_OUTPUT_PATH'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Diagram Options
    |--------------------------------------------------------------------------
    |
    | Configuration for model relationship diagram generation.
    |
    */

    'diagrams' => [
        'format' => 'mermaid',
        'show_columns' => false,
        'show_foreign_keys' => true,
    ],

];
