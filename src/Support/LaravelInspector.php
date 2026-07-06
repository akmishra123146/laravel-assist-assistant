<?php

namespace LaravelAssist\Assistant\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class LaravelInspector
{
    protected Filesystem $files;

    protected ?string $basePath = null;

    protected array $models = [];

    protected array $routes = [];

    protected array $controllers = [];

    protected array $views = [];

    protected array $migrations = [];

    protected array $providers = [];

    protected array $jobs = [];

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        $this->basePath = base_path();
    }

    public function setBasePath(string $path): static
    {
        $this->basePath = $path;
        $this->models = [];
        $this->routes = [];
        $this->controllers = [];
        $this->views = [];
        $this->migrations = [];
        $this->providers = [];
        $this->jobs = [];

        return $this;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get all Eloquent model classes in the application.
     *
     * @return array<string, string>
     */
    public function getModels(): array
    {
        if (! empty($this->models)) {
            return $this->models;
        }

        $paths = [
            $this->basePath . '/app/Models',
            $this->basePath . '/app',
        ];

        foreach ($paths as $path) {
            if (! $this->files->isDirectory($path)) {
                continue;
            }

            $files = $this->getPhpFilesRecursive($path);

            foreach ($files as $file) {
                $content = $this->files->get($file);
                if (str_contains($content, 'extends Model') || str_contains($content, 'extends Authenticatable')) {
                    $relativePath = str_replace($this->basePath . '/', '', $file);
                    $className = $this->resolveClassName($file);
                    if ($className) {
                        $this->models[$className] = $relativePath;
                    }
                }
            }
        }

        return $this->models;
    }

    /**
     * Recursively get all PHP files in a directory.
     *
     * @return array<int, string>
     */
    protected function getPhpFilesRecursive(string $directory): array
    {
        $results = [];
        $files = $this->files->glob($directory . '/*.php');
        if ($files) {
            $results = array_merge($results, $files);
        }
        $subdirs = $this->files->glob($directory . '/*');
        if ($subdirs) {
            foreach ($subdirs as $subdir) {
                if ($this->files->isDirectory($subdir)) {
                    $results = array_merge($results, $this->getPhpFilesRecursive($subdir));
                }
            }
        }
        return $results;
    }

    /**
     * Get all registered routes.
     *
     * @return array<int, array{method: string, uri: string, controller: string, action: string, middleware: array<int, string>}>
     */
    public function getRoutes(): array
    {
        if (! empty($this->routes)) {
            return $this->routes;
        }

        $routeFiles = [
            $this->basePath . '/routes/web.php',
            $this->basePath . '/routes/api.php',
            $this->basePath . '/routes/console.php',
        ];

        foreach ($routeFiles as $file) {
            if (! $this->files->exists($file)) {
                continue;
            }
            $content = $this->files->get($file);
            $this->routes = array_merge($this->routes, $this->parseRoutes($content, $file));
        }

        return $this->routes;
    }

    /**
     * Get all controller classes.
     *
     * @return array<string, string>
     */
    public function getControllers(): array
    {
        if (! empty($this->controllers)) {
            return $this->controllers;
        }

        $controllerPaths = [
            $this->basePath . '/app/Http/Controllers',
        ];

        foreach ($controllerPaths as $path) {
            if (! $this->files->isDirectory($path)) {
                continue;
            }

            $files = $this->files->glob($path . '/**/*.php');

            foreach ($files as $file) {
                $content = $this->files->get($file);
                if (str_contains($content, 'extends Controller') || str_contains($content, 'extends BaseController')) {
                    $relativePath = str_replace($this->basePath . '/', '', $file);
                    $className = $this->resolveClassName($file);
                    if ($className) {
                        $this->controllers[$className] = $relativePath;
                    }
                }
            }
        }

        return $this->controllers;
    }

    /**
     * Get all Blade view files.
     *
     * @return array<int, string>
     */
    public function getViews(): array
    {
        if (! empty($this->views)) {
            return $this->views;
        }

        $viewPath = $this->basePath . '/resources/views';

        if ($this->files->isDirectory($viewPath)) {
            $this->views = $this->files->glob($viewPath . '/**/*.blade.php');
        }

        return $this->views;
    }

    /**
     * Get all database migrations.
     *
     * @return array<int, string>
     */
    public function getMigrations(): array
    {
        if (! empty($this->migrations)) {
            return $this->migrations;
        }

        $migrationPath = $this->basePath . '/database/migrations';

        if ($this->files->isDirectory($migrationPath)) {
            $this->migrations = $this->files->glob($migrationPath . '/*.php');
        }

        return $this->migrations;
    }

    /**
     * Get all registered service providers.
     *
     * @return array<string, string>
     */
    public function getProviders(): array
    {
        if (! empty($this->providers)) {
            return $this->providers;
        }

        $configPath = $this->basePath . '/config/app.php';

        if ($this->files->exists($configPath)) {
            $config = require $configPath;
            $providerList = $config['providers'] ?? [];

            foreach ($providerList as $provider) {
                $this->providers[$provider] = $provider;
            }
        }

        return $this->providers;
    }

    /**
     * Get all queue job classes.
     *
     * @return array<string, string>
     */
    public function getJobs(): array
    {
        if (! empty($this->jobs)) {
            return $this->jobs;
        }

        $jobPaths = [
            $this->basePath . '/app/Jobs',
        ];

        foreach ($jobPaths as $path) {
            if (! $this->files->isDirectory($path)) {
                continue;
            }

            $files = $this->files->glob($path . '/**/*.php');

            foreach ($files as $file) {
                $content = $this->files->get($file);
                if (str_contains($content, 'implements ShouldQueue') || str_contains($content, 'implements ShouldBeUnique')) {
                    $relativePath = str_replace($this->basePath . '/', '', $file);
                    $className = $this->resolveClassName($file);
                    if ($className) {
                        $this->jobs[$className] = $relativePath;
                    }
                }
            }
        }

        return $this->jobs;
    }

    /**
     * Get a file's contents.
     */
    public function getFileContents(string $path): ?string
    {
        if ($this->files->exists($path)) {
            return $this->files->get($path);
        }

        return null;
    }

    /**
     * Check if a file exists.
     */
    public function fileExists(string $path): bool
    {
        return $this->files->exists($path);
    }

    /**
     * Get all PHP files in a directory recursively.
     *
     * @return array<int, string>
     */
    public function getPhpFiles(string $directory): array
    {
        if (! $this->files->isDirectory($directory)) {
            return [];
        }

        return $this->files->glob($directory . '/**/*.php');
    }

    /**
     * Get the application's namespace.
     */
    public function getAppNamespace(): string
    {
        if ($this->files->exists($this->basePath . '/app')) {
            $composerJson = json_decode($this->files->get($this->basePath . '/composer.json'), true);
            $psr4 = $composerJson['autoload']['psr-4'] ?? [];
            foreach ($psr4 as $namespace => $path) {
                if ($path === 'app/') {
                    return rtrim($namespace, '\\');
                }
            }
        }

        return 'App';
    }

    /**
     * Read the .env file for configuration values.
     */
    public function getEnvValue(string $key, mixed $default = null): mixed
    {
        $envPath = $this->basePath . '/.env';

        if (! $this->files->exists($envPath)) {
            return $default;
        }

        $lines = $this->files->lines($envPath);

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), $key . '=')) {
                $value = substr($line, strlen($key) + 1);
                return trim($value, '"\'');
            }
        }

        return $default;
    }

    protected function resolveClassName(string $filePath): ?string
    {
        $relativePath = str_replace($this->basePath . '/', '', $filePath);
        $relativePath = str_replace('.php', '', $relativePath);
        $relativePath = str_replace('/', '\\', $relativePath);

        $appNamespace = $this->getAppNamespace();

        return $appNamespace . '\\' . $relativePath;
    }

    /**
     * Parse route definitions from a routes file.
     *
     * @return array<int, array{method: string, uri: string, controller: string, action: string, middleware: array<int, string>}>
     */
    protected function parseRoutes(string $content, string $file): array
    {
        $routes = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || str_starts_with($line, '#') || str_starts_with($line, '//')) {
                continue;
            }

            foreach (['get', 'post', 'put', 'patch', 'delete', 'any', 'match', 'resource'] as $method) {
                if (preg_match('/Route::' . $method . '\s*\(\s*[\'"](.+?)[\'"]/', $line, $matches)) {
                    $uri = $matches[1];

                    if (preg_match('/([A-Za-z\\\\]+Controller)@(\w+)/', $line, $controllerMatches)) {
                        $controller = $controllerMatches[1];
                        $action = $controllerMatches[2];
                    } else {
                        $controller = 'Closure';
                        $action = '__invoke';
                    }

                    $middleware = [];
                    if (preg_match('/->middleware\(\s*[\'"](.+?)[\'"]\s*\)/', $line, $middlewareMatches)) {
                        $middleware = array_map('trim', explode(',', $middlewareMatches[1]));
                    }

                    $routes[] = [
                        'method' => $method === 'match' ? 'GET|POST' : strtoupper($method),
                        'uri' => $uri,
                        'controller' => $controller,
                        'action' => $action,
                        'middleware' => $middleware,
                        'file' => $file,
                    ];
                }
            }
        }

        return $routes;
    }
}
