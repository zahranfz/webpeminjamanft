<?php
namespace App\Support;

class Router {
    private $routes = [];

    public function add($method, $path, $callback) {
        // Convert path to regex (e.g. /room/{id} to /room/([^/]+))
        $path = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[^/]+)', $path);
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => "#^" . $path . "$#",
            'callback' => $callback
        ];
    }

    public function get($path, $callback) {
        $this->add('GET', $path, $callback);
    }

    public function post($path, $callback) {
        $this->add('POST', $path, $callback);
    }

    public function run() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Remove base path if necessary (e.g. if served from subfolder /webpinjamft/public)
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }
        if (empty($uri)) $uri = '/';
        
        $method = $_SERVER['REQUEST_METHOD'];
        
        // CSRF protection for POST/PUT/DELETE
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            Security::verifyCsrfToken($_POST['csrf_token'] ?? '');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['path'], $uri, $matches)) {
                // Filter matches to only include named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $callback = $route['callback'];
                if (is_array($callback) && is_string($callback[0])) {
                    $controller = new $callback[0]();
                    $methodName = $callback[1];
                    return call_user_func_array([$controller, $methodName], $params);
                } else {
                    return call_user_func_array($callback, $params);
                }
            }
        }

        // 404 Not Found
        header("HTTP/1.0 404 Not Found");
        echo "404 Not Found";
    }
}
