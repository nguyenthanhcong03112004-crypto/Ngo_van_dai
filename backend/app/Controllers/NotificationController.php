<?php
declare(strict_types=1);

namespace Controllers;

use Core\BaseController;
use Core\Middleware;
use Models\Notification;

class NotificationController extends BaseController
{
    private Notification $notificationModel;

    public function __construct()
    {
        parent::__construct();
        $this->notificationModel = new Notification();
    }

    public function index(): void
    {
        $payload = Middleware::requireAuth();
        $notifications = $this->notificationModel->getByUser($payload['user_id']);
        $this->success($notifications);
    }

    public function markAsRead(int $id): void
    {
        $payload = Middleware::requireAuth();
        $this->notificationModel->markAsRead($id, $payload['user_id']);
        $this->success(null, 'Đã đánh dấu là đã đọc.');
    }

    public function markAllAsRead(): void
    {
        $payload = Middleware::requireAuth();
        // Dùng cho endpoint PUT /api/notifications/read-all
        $this->notificationModel->markAllAsRead($payload['user_id']);
        $this->success(null, 'Đã đánh dấu tất cả là đã đọc.');
    }

    public function unreadCount(): void
    {
        $payload = Middleware::requireAuth();
        $count = $this->notificationModel->getUnreadCount($payload['user_id']);
        $this->success(['unread_count' => $count]);
    }
}