<?php
declare(strict_types=1);

namespace Controllers;

use Core\BaseController;
use Core\Middleware;

class LogController extends BaseController
{
    public function store(): void
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if ($headers === false) $headers = [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $userId = null;
        
        if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            $parts = explode('.', $matches[1]);
            if (count($parts) === 3) {
                $payloadData = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                if (is_array($payloadData) && isset($payloadData['user_id'])) {
                    $userId = $payloadData['user_id'];
                }
            }
        }

        $body = $this->getBody();
        $level = $body['level'] ?? 'INFO';
        $message = $body['message'] ?? 'Frontend Log';
        $context = is_array($body['context'] ?? null) ? $body['context'] : [];
        $context['source'] = 'frontend'; // Phân biệt log này đến từ frontend
        $context['auth_user_id'] = $userId;

        match (strtoupper($level)) {
            'ERROR'    => $this->logger->error($message, $context),
            'WARN'     => $this->logger->warning($message, $context),
            'DEBUG'    => $this->logger->debug($message, $context),
            default    => $this->logger->info($message, $context),
        };

        $this->success(null, 'Log received');
    }
}