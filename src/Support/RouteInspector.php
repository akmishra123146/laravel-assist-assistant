<?php

namespace LaravelAssist\Assistant\Support;

class RouteInspector
{
    protected LaravelInspector $inspector;

    public function __construct(LaravelInspector $inspector)
    {
        $this->inspector = $inspector;
    }

    /**
     * Get all route controller mappings.
     *
     * @return array<int, array{method: string, uri: string, controller: string, action: string, file: string}>
     */
    public function getRouteControllerMap(): array
    {
        return $this->inspector->getRoutes();
    }

    /**
     * Get controllers that are not referenced by any route.
     *
     * @return array<string, string>
     */
    public function getUnusedControllers(): array
    {
        $controllers = $this->inspector->getControllers();
        $routes = $this->inspector->getRoutes();

        $usedControllers = [];
        foreach ($routes as $route) {
            $controller = $route['controller'];
            if ($controller !== 'Closure') {
                $usedControllers[$controller] = true;
            }
        }

        return array_filter(
            $controllers,
            fn ($class) => ! isset($usedControllers[$class])
        );
    }

    /**
     * Get routes that reference non-existent controllers.
     *
     * @return array<int, array{method: string, uri: string, controller: string, file: string}>
     */
    public function getBrokenRoutes(): array
    {
        $controllers = $this->inspector->getControllers();
        $routes = $this->inspector->getRoutes();

        return array_filter(
            $routes,
            fn ($route) => $route['controller'] !== 'Closure' && ! isset($controllers[$route['controller']])
        );
    }

    /**
     * Get routes without throttle middleware.
     *
     * @return array<int, array{method: string, uri: string, middleware: array<int, string>}>
     */
    public function getRoutesWithoutThrottle(): array
    {
        $routes = $this->inspector->getRoutes();

        return array_filter(
            $routes,
            fn ($route) => ! in_array('throttle', $route['middleware'])
        );
    }

    /**
     * Get all routes that are GET routes (potential API surface).
     *
     * @return array<int, array{method: string, uri: string, controller: string}>
     */
    public function getGetRoutes(): array
    {
        $routes = $this->inspector->getRoutes();

        return array_filter(
            $routes,
            fn ($route) => in_array('GET', explode('|', $route['method']))
        );
    }
}
