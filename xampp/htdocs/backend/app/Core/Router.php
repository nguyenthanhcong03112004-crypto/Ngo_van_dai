<?php
declare(strict_types=1);

namespace Core;

class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $pattern, callable|array $handler): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function get(string $pattern, callable|array $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable|array $handler): void
    {
        $this->addRoute('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, callable|array $handler): void
    {
        $this->addRoute('DELETE', $pattern, $handler);
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // Handle CORS preflight
        if ($method === 'OPTIONS') {
            $this->setCorsHeaders();
            http_response_code(204);
            exit;
        }

        $this->setCorsHeaders();

        // Get URI relative to script directory
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strip base path if running in a subdirectory
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }

        $uri = '/' . ltrim($uri, '/');

        // Tự động phục vụ các file tĩnh và file HTML từ thư mục frontend
        if (strpos($uri, '/api/') !== 0) {
            $frontendPath = dirname(__DIR__, 3) . '/frontend';
            $targetFile = $uri === '/' ? '/index.html' : $uri;
            
            // Tự động gắn đuôi .html nếu người dùng gõ URL không có đuôi (VD: /login)
            if (!pathinfo($targetFile, PATHINFO_EXTENSION)) {
                $targetFile .= '.html';
            }

            $realPath = realpath($frontendPath . $targetFile);

            // Chỉ phục vụ file nếu file tồn tại và nằm bên trong thư mục frontend
            if ($realPath && file_exists($realPath) && strpos($realPath, realpath($frontendPath)) === 0) {
                $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
                $mimeTypes = [
                    'html' => 'text/html; charset=utf-8',
                    'css'  => 'text/css; charset=utf-8',
                    'js'   => 'application/javascript; charset=utf-8',
                    'png'  => 'image/png',
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif'  => 'image/gif',
                    'svg'  => 'image/svg+xml',
                    'webp' => 'image/webp',
                    'ico'  => 'image/x-icon',
                    'json' => 'application/json; charset=utf-8'
                ];
                $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
                
                header("Content-Type: $mime");
                readfile($realPath);
                exit;
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $uri);
            if ($params !== null) {
                $handler = $route['handler'];

                Logger::getInstance()->info('Route matched', [
                    'pattern' => $route['pattern'],
                    'handler' => is_array($handler) ? $handler[0] . '@' . $handler[1] : 'Closure',
                    'params'  => $params,
                ]);

                if (is_array($handler)) {
                    [$class, $actionMethod] = $handler;
                    $controller = new $class();
                    $controller->$actionMethod(...array_values($params));
                } else {
                    $handler(...array_values($params));
                }
                return;
            }
        }

        // 404
        Logger::getInstance()->warning('Endpoint not found (404)', ['uri' => $uri, 'method' => $method]);

        // Phân biệt request từ Fetch/AJAX và request truy cập thẳng từ Browser
        $isAjax = (isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'cors') || 
                  (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Endpoint not found',
                'data'    => null,
            ]);
        } else {
            // Chuyển hướng mọi API không có trong route (truy cập thẳng) về index.html kèm tham số
            header('Location: /index.html?error=404');
        }
        exit;
    }

    private function match(string $pattern, string $uri): ?array
    {
        // Convert route pattern like /api/orders/{id} to regex
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '@^' . $regex . '$@';

        if (preg_match($regex, $uri, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return null;
    }

    private function setCorsHeaders(): void
    {
        $allowedOrigins = explode(',', $_ENV['ALLOWED_ORIGINS'] ?? 'http://localhost:3000');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
        } else {
            header('Access-Control-Allow-Origin: http://localhost:3000');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        
        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            header('Content-Type: application/json; charset=utf-8');
        }
    }
}
