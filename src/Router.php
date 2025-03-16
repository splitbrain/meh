<?php

namespace splitbrain\meh;

class Router
{
    private $routes = [];

    /**
     * Register a route with the router
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $path URL path to match
     * @param string $controller Controller class name
     * @param string $action Method to call on the controller
     */
    public function addRoute($method, $path, $controller, $action)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    /**
     * Handle the incoming request and route to the appropriate controller
     *
     * @param string $method HTTP method
     * @param string $path Request path
     * @param array $data Request data (from JSON body)
     * @return mixed Response from the controller
     * @throws \Exception When no matching route is found
     */
    public function dispatch($method, $path, $data = [])
    {
        $method = strtoupper($method);
        
        foreach ($this->routes as $route) {
            // Simple path matching (could be enhanced with regex patterns)
            if ($route['method'] === $method && $this->matchPath($route['path'], $path)) {
                $controllerClass = $route['controller'];
                $action = $route['action'];
                
                $controller = new $controllerClass();
                return $controller->$action($data);
            }
        }
        
        throw new \Exception("No route found for $method $path", 404);
    }
    
    /**
     * Simple path matching
     * 
     * @param string $routePath Route pattern
     * @param string $requestPath Actual request path
     * @return bool Whether the path matches
     */
    private function matchPath($routePath, $requestPath)
    {
        // For now, just do exact matching
        // This could be enhanced with parameter extraction
        return $routePath === $requestPath;
    }
}
