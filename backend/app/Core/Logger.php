<?php

namespace Core;

/**
 * Logger - PSR-3 Inspired, Zero-Dependency PHP Logger
 *
 * Features:
 * - 5 log levels: DEBUG, INFO, WARNING, ERROR, CRITICAL
 * - Daily log rotation (logs/app-YYYY-MM-DD.log)
 * - Structured JSON lines format for easy parsing
 * - Request pipeline logging (method, URL, IP, execution time)
 * - SQL query logging (DEBUG) with timing
 * - Sensitive field masking (password, token, etc.)
 *
 * Usage:
 *   $logger = Logger::getInstance();
 *   $logger->info('User logged in', ['user_id' => 5]);
 *   $logger->error('DB query failed', ['sql' => $sql, 'error' => $e->getMessage()]);
 */
class Logger
{
    // ─── Log Level Constants ──────────────────────────────────────────────────
    const DEBUG    = 'DEBUG';
    const INFO     = 'INFO';
    const WARNING  = 'WARNING';
    const ERROR    = 'ERROR';
    const CRITICAL = 'CRITICAL';

    // ─── Config ───────────────────────────────────────────────────────────────
    private string $logDir;
    private float  $requestStartTime;
    private int    $currentUserId = 0;

    /** Fields that must NEVER be written to logs in plain text */
    private array $maskedFields = [
        'password', 'password_confirmation', 'token',
        'access_token', 'refresh_token', 'secret', 'card_number',
    ];

    // ─── Singleton ───────────────────────────────────────────────────────────
    private static ?Logger $instance = null;

    private function __construct()
    {
        $this->logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0775, true);
        }
        $this->requestStartTime = microtime(true);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Set authenticated user ID for contextual log entries */
    public function setUserId(int $userId): void
    {
        $this->currentUserId = $userId;
    }

    // ─── Public Log Methods ───────────────────────────────────────────────────

    public function debug(string $message, array $context = []): void
    {
        $this->write(self::DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write(self::INFO, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write(self::WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write(self::ERROR, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->write(self::CRITICAL, $message, $context);
    }

    // ─── Request Pipeline Logging ─────────────────────────────────────────────

    /**
     * Call at the beginning of every request (in Router or index.php).
     * Logs: method, URL, IP, user agent, and sanitized request body.
     */
    public function logRequest(): void
    {
        $body = [];
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $raw  = file_get_contents('php://input');
            $body = json_decode($raw, true) ?? [];
        }

        $this->info('Incoming HTTP Request', [
            'method'     => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'url'        => $this->getCurrentUrl(),
            'ip'         => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'payload'    => $this->maskSensitiveFields($body),
        ]);
    }

    /**
     * Call just before sending the HTTP response.
     * Logs: status code, execution time in ms, response size.
     */
    public function logResponse(int $statusCode, int $responseBytes = 0): void
    {
        $executionMs = round((microtime(true) - $this->requestStartTime) * 1000, 2);

        $level = $statusCode >= 500 ? self::ERROR
               : ($statusCode >= 400 ? self::WARNING
               : self::INFO);

        $this->write($level, 'HTTP Response Sent', [
            'status_code'    => $statusCode,
            'execution_ms'   => $executionMs,
            'response_bytes' => $responseBytes,
        ]);
    }

    // ─── Database Query Logging ───────────────────────────────────────────────

    /**
     * Log a successful PDO query execution (level: DEBUG).
     * Call this in Database.php after each query.
     */
    public function logQuery(string $sql, array $params = [], float $durationMs = 0): void
    {
        $this->debug('SQL Query Executed', [
            'sql'         => $this->truncate($sql, 500),
            'params'      => $this->maskSensitiveFields($params),
            'duration_ms' => round($durationMs, 3),
        ]);
    }

    /**
     * Log a PDOException (level: ERROR).
     * Call this in a catch block around PDO calls.
     */
    public function logQueryError(string $sql, \PDOException $e, array $params = []): void
    {
        $this->error('SQL Query Failed', [
            'sql'     => $this->truncate($sql, 500),
            'params'  => $this->maskSensitiveFields($params),
            'error'   => $e->getMessage(),
            'code'    => $e->getCode(),
        ]);
    }

    // ─── Core Write Method ────────────────────────────────────────────────────

    private function write(string $level, string $message, array $context = []): void
    {
        $entry = json_encode([
            'timestamp'  => date('Y-m-d H:i:s'),
            'level'      => $level,
            'user_id'    => $this->currentUserId ?: null,
            'ip'         => $this->getClientIp(),
            'method'     => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'endpoint'   => $this->getCurrentUrl(),
            'message'    => $message,
            'context'    => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $filePath = $this->logDir . '/app-' . date('Y-m-d') . '.log';

        // file_put_contents with LOCK_EX is atomic enough for most non-extreme loads.
        // For high-volume production, consider using an async queue or syslog.
        file_put_contents($filePath, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function maskSensitiveFields(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $this->maskedFields)) {
                $data[$key] = '***MASKED***';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitiveFields($value);
            }
        }
        return $data;
    }

    private function getClientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    private function getCurrentUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        return "{$scheme}://{$host}{$uri}";
    }

    private function truncate(string $str, int $maxLength): string
    {
        return strlen($str) > $maxLength
            ? substr($str, 0, $maxLength) . '...[truncated]'
            : $str;
    }
}
