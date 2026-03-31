<?php
declare(strict_types=1);

namespace Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class Middleware
{
    private static string $secret;

    private static function getSecret(): string
    {
        if (empty(self::$secret)) {
            self::$secret = $_ENV['JWT_SECRET'] ?? 'fallback_secret_key';
        }
        return self::$secret;
    }

    /**
     * Generate a JWT token for the given user payload.
     */
    public static function generateToken(array $payload): string
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + (60 * 60 * 24); // 24 hours

        // Simple JWT implementation without external library
        return self::createToken($payload);
    }

    /**
     * Require a valid JWT. Returns decoded payload or sends 401 and exits.
     */
    public static function requireAuth(): array
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            Logger::getInstance()->warning('Authentication failed: Missing token');
            self::unauthorized('Authorization token required');
        }

        $token = $matches[1];
        $payload = self::verifyToken($token);

        if ($payload === null) {
            Logger::getInstance()->warning('Authentication failed: Invalid or expired token');
            self::unauthorized('Invalid or expired token');
        }

        // Set User ID in the Logger so all subsequent logs in this request have context
        if (isset($payload['user_id'])) {
            $logger = Logger::getInstance();
            $logger->setUserId((int)$payload['user_id']);
            $logger->debug('User authenticated via JWT', ['user_id' => $payload['user_id'], 'role' => $payload['role'] ?? '']);
        }

        return $payload;
    }

    /**
     * Require auth AND a specific role (admin or user).
     */
    public static function requireRole(string $role): array
    {
        $payload = self::requireAuth();

        if (($payload['role'] ?? '') !== $role) {
            Logger::getInstance()->warning("Access forbidden: User lacks required role", [
                'required_role' => $role,
                'actual_role'   => $payload['role'] ?? null
            ]);

            http_response_code(403);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Access forbidden: insufficient permissions',
                'data'    => null,
            ]);
            exit;
        }

        return $payload;
    }

    // ----------------------------------------------------------------
    // Simple HS256 JWT without external dependency
    // ----------------------------------------------------------------

    private static function createToken(array $payload): string
    {
        $header  = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = self::base64UrlEncode(json_encode($payload));
        $sig     = self::base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", self::getSecret(), true));

        return "{$header}.{$payload}.{$sig}";
    }

    private static function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$header, $payload, $sig] = $parts;
        $expectedSig = self::base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", self::getSecret(), true));

        if (!hash_equals($expectedSig, $sig)) return null;

        $data = json_decode(self::base64UrlDecode($payload), true);
        if (!$data || (isset($data['exp']) && $data['exp'] < time())) return null;

        return $data;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }

    private static function unauthorized(string $message): never
    {
        http_response_code(401);
        echo json_encode([
            'status'  => 'error',
            'message' => $message,
            'data'    => null,
        ]);
        exit;
    }
}
