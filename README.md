# Laravel Assistant

AI-powered code analysis, security auditing, and health reporting for Laravel applications.

[![Latest Stable Version](https://poser.pugx.org/laravel-assist/assistant/v/stable)](https://packagist.org/packages/laravel-assist/assistant)
[![License](https://poser.pugx.org/laravel-assist/assistant/license)](https://packagist.org/packages/laravel-assist/assistant)
[![Tests](https://github.com/laravel-assist/assistant/actions/workflows/tests.yml/badge.svg)](https://github.com/laravel-assist/assistant/actions)

## Features

- **Missing Database Indexes** - Detect columns used in WHERE/JOIN without indexes
- **N+1 Query Detection** - Find relationships accessed without eager loading
- **Unused Code Detection** - Routes, controllers, views, service providers, dead code
- **Security Warnings** - Mass assignment, debug settings, missing rate limits
- **Cache & Queue Analysis** - Cache opportunities and queue bottleneck detection
- **Model Diagrams** - Generate Mermaid ER diagrams from Eloquent relationships
- **Health Reports** - Comprehensive deployment readiness reports
- **AI Enhancement** - Optional OpenAI/Claude integration for enriched recommendations

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12

## Installation

```bash
composer require laravel-assist/assistant
```

The service provider is auto-discovered. No manual registration needed.

Publish the config file:

```bash
php artisan vendor:publish --tag=config
```

## Usage

### Run Full Analysis

```bash
php artisan assistant:analyze
```

### Analyze Specific Categories

```bash
php artisan assistant:analyze --only=security
php artisan assistant:analyze --only=database,performance
```

### Security Scan

```bash
php artisan assistant:security
```

### Generate Health Report

```bash
php artisan assistant:report
php artisan assistant:report --format=html --output=report.html
```

### Generate Diagrams

```bash
php artisan assistant:diagram --type=model
php artisan assistant:diagram --type=dependency --output=graph.md
```

### Enable AI Enhancement

Set environment variables:

```env
ASSISTANT_AI_ENABLED=true
ASSISTANT_AI_PROVIDER=openai
ASSISTANT_AI_API_KEY=your-api-key
```

Then run with the `--ai` flag:

```bash
php artisan assistant:analyze --ai
```

## Configuration

The published config file at `config/assistant.php` allows you to:

- Enable/disable specific analyzers
- Exclude paths from analysis
- Configure AI providers (OpenAI/Claude)
- Set minimum severity levels
- Configure diagram output

## Output Formats

| Command | Formats |
|---------|---------|
| `assistant:analyze` | `table`, `json` |
| `assistant:security` | `table`, `json` |
| `assistant:report` | `console`, `json`, `html` |
| `assistant:diagram` | Mermaid (stdout or file) |

## Health Score

The health score is calculated as:

- Starting at 100 points
- Critical issues: -15 points each
- Warnings: -5 points each
- Info: -1 point each

| Score | Meaning |
|-------|---------|
| 80-100 | Excellent - ready for deployment |
| 50-79 | Good - consider addressing warnings |
| 0-49 | Needs work - critical issues detected |

## Extending

### Custom Analyzer

Create a class implementing `AnalyzerInterface`:

```php
use LaravelAssist\Assistant\Contracts\AnalyzerInterface;
use LaravelAssist\Assistant\Support\LaravelInspector;

class MyCustomAnalyzer implements AnalyzerInterface
{
    public function analyze(LaravelInspector $inspector): array
    {
        $findings = [];
        // Your analysis logic here
        return $findings;
    }

    public function getName(): string
    {
        return 'My Custom Check';
    }

    public function getDescription(): string
    {
        return 'Description of what this checks.';
    }

    public function getCategory(): string
    {
        return 'custom';
    }
}
```

Register via a service provider:

```php
use LaravelAssist\Assistant\AI\RulesEngine;

$this->app->booted(function ($app) {
    $engine = $app->make(RulesEngine::class);
    $engine->registerAnalyzer('my_custom', new MyCustomAnalyzer());
});
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
