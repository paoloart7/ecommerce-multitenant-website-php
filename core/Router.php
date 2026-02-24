<?php
/**
 * Router - match routes + params + middleware
 */

class Router
{
    private array $routes = [];
    private array $params = [];

    public function __construct()
    {
        $this->routes = require dirname(__DIR__) . '/config/routes.php';
    }

    public function dispatch(string $url): void
    {
        $url = $this->sanitizeUrl($url);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        foreach (($this->routes[$method] ?? []) as $route => $action) {
            if ($this->match($route, $url)) {

                // Route avec middleware
                if (is_array($action)) {
                    $middleware = $action['middleware'] ?? null;
                    $actionString = $action['action'] ?? '';

                    if ($middleware) {
                        $this->runMiddleware($middleware);
                    }

                    $this->execute($actionString);
                    return;
                }

                // Route simple
                $this->execute($action);
                return;
            }
        }

        $this->error404();
    }

    private function sanitizeUrl(string $url): string
    {
        $url = trim($url, '/');
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return $url ?: '';
    }

    private function match(string $route, string $url): bool
    {
        $route = trim($route, '/');
        $url   = trim($url, '/');

        if ($route === '' && $url === '') {
            return true;
        }

        if ($route === $url) {
            return true;
        }
        $pattern = preg_replace('/\{([a-z]+)\}/', '(?P<$1>[^/]+)', $route);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $url, $matches)) {
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $this->params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    private function execute(string $action): void
    {
        if (!$action || !str_contains($action, '@')) {
            $this->error404();
        }

        [$controller, $method] = explode('@', $action, 2);

        $controllerFile = dirname(__DIR__) . '/app/Controllers/' . $controller . '.php';

        if (!file_exists($controllerFile)) {
            $this->error404();
        }

        require_once $controllerFile;

        if (!class_exists($controller)) {
            $this->error404();
        }

        $instance = new $controller();

        if (!method_exists($instance, $method)) {
            $this->error404();
        }

        call_user_func_array([$instance, $method], $this->params);
    }

    private function runMiddleware(string|array $middleware): void
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];

        foreach ($middlewares as $m) {
            $parts = explode(':', $m, 2);
            $name = $parts[0];
            $param = $parts[1] ?? null;

            $file = dirname(__DIR__) . '/core/Middleware/' . $name . '.php';
            if (!file_exists($file)) {
                throw new Exception("Middleware introuvable: $name");
            }

            require_once $file;

            $obj = $param ? new $name($param) : new $name();
            $obj->handle();
        }
    }

    private function error404(): void
    {
        http_response_code(404);
        $file = dirname(__DIR__) . '/app/Views/errors/404.php';

        if (file_exists($file)) {
            require $file;
        } else {
            echo "404 - Page non trouvée";
        }
        exit;
    }

    public static function redirect(string $path = ''): void
    {
        $config = require dirname(__DIR__) . '/config/app.php';
        header('Location: ' . rtrim($config['base_url'], '/') . '/' . ltrim($path, '/'));
        exit;
    }
}