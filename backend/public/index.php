<?php
declare(strict_types=1);

// Load .env variables
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // Only set if not already set (allow Docker environment to take precedence)
            if (!isset($_ENV[$name]) && getenv($name) === false) {
                $_ENV[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }
}

// Autoloader
spl_autoload_register(function (string $class): void {
    $base = dirname(__DIR__) . '/app/';
    $file = $base . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Bootstrap router
use Core\Router;

$router = new Router();
require_once dirname(__DIR__) . '/app/routes.php';
$router->dispatch();
