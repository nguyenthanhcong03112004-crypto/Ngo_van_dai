<?php
declare(strict_types=1);

namespace Controllers;

use Core\BaseController;
use Core\Middleware;

class LogController extends BaseController
{
    public function store(): void
    {
        // Yêu cầu xác thực bằng JWT. Token phải được gửi qua header: Authorization: Bearer <token>
        $payload = Middleware::requireAuth();

        $body = $this->getBody();
        $level = $body['level'] ?? 'INFO';
        $message = $body['message'] ?? 'Frontend Log';
        $context = $body['context'] ?? [];
        $context['source'] = 'frontend'; // Phân biệt log này đến từ frontend
        // Gắn ID người dùng từ payload JWT vào log để dễ dàng truy vết
        $context['auth_user_id'] = $payload['user_id'] ?? null;

        match (strtoupper($level)) {
            'ERROR'    => $this->logger->error($message, $context),
            'WARN'     => $this->logger->warning($message, $context),
            'DEBUG'    => $this->logger->debug($message, $context),
            default    => $this->logger->info($message, $context),
        };

        $this->success(null, 'Log received');
    }
}