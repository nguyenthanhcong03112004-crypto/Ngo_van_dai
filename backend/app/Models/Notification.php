<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

class Notification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM `notifications` WHERE `user_id` = ? ORDER BY `created_at` DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, string $title, string $message, string $type = 'info'): int
    {
        $stmt = $this->db->prepare('INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $title, $message, $type]);
        return (int) $this->db->lastInsertId();
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare('UPDATE `notifications` SET `is_read` = 1 WHERE `id` = ? AND `user_id` = ?');
        return $stmt->execute([$id, $userId]);
    }

    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->db->prepare('UPDATE `notifications` SET `is_read` = 1 WHERE `user_id` = ?');
        return $stmt->execute([$userId]);
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ? AND `is_read` = 0');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }
}