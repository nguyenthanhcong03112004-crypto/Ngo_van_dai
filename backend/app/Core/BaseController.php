<?php
declare(strict_types=1);

namespace Core;

abstract class BaseController
{
    /**
     * Send a standardized JSON success response.
     */
    protected function success(mixed $data = null, string $message = 'OK', int $code = 200): void
    {
        http_response_code($code);
        echo json_encode([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send a standardized JSON error response.
     */
    protected function error(string $message = 'An error occurred', int $code = 400, mixed $data = null): void
    {
        http_response_code($code);
        echo json_encode([
            'status'  => 'error',
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Get decoded JSON body from request.
     */
    protected function getBody(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        return json_decode($raw, true) ?? [];
    }

    /**
     * Validate that required fields exist and are non-empty in input array.
     * Returns list of missing field names.
     */
    protected function validate(array $data, array $required): array
    {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }
        return $missing;
    }
}
