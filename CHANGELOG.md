# Changelog

All notable changes to `laravel-assist/assistant` will be documented in this file.

## [1.0.0] - 2026-07-06

### Added

- Initial release
- `assistant:analyze` command with full code analysis
- `assistant:security` command for security-focused scanning
- `assistant:report` command for deployment health reports
- `assistant:diagram` command for model and dependency diagrams
- 13 built-in analyzers:
  - Database: MissingIndexAnalyzer, NPlusOneQueryAnalyzer
  - Code: UnusedRouteAnalyzer, UnusedControllerAnalyzer, UnusedViewAnalyzer, DeadCodeAnalyzer, UnusedServiceProviderAnalyzer
  - Security: MassAssignmentAnalyzer, DebugSettingsAnalyzer, RateLimitAnalyzer
  - Performance: CacheAnalyzer, QueueAnalyzer, EagerLoadingAnalyzer
- ModelDiagramGenerator for Mermaid ER diagrams
- DependencyGraphGenerator for application architecture visualization
- Optional AI enhancement with OpenAI and Claude providers
- Configurable severity levels and analyzer selection
- Multiple output formats (console, JSON, HTML)
- Health score calculation (0-100)
- Publishable configuration file
- Support for Laravel 10, 11, and 12
