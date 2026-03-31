<?php
declare(strict_types=1);

// Autoloader for dependencies from Composer
require_once dirname(__DIR__) . '/vendor/autoload.php';

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
use Core\Logger;

// Initialize logger and log the request
$logger = Logger::getInstance();
$logger->logRequest();

// Set a global exception handler to log uncaught exceptions
set_exception_handler(function (\Throwable $e) use ($logger) {
    $logger->critical('Uncaught Exception', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => mb_substr($e->getTraceAsString(), 0, 2000), // Truncate trace
    ]);

    // Send a generic 500 response if headers not already sent
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo '{"status":"error","message":"An internal server error occurred.","data":null}';
    }
});

$router = new Router();
require_once dirname(__DIR__) . '/app/routes.php';
$router->dispatch();
