<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

class DisputeChat
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByOrder(int $orderId): array
    {
        $stmt = $this->db->prepare(
            'SELECT dc.*, u.name AS sender_name
             FROM `dispute_chats` dc
             JOIN `users` u ON dc.user_id = u.id
             WHERE dc.order_id = ?
             ORDER BY dc.created_at ASC'
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        \Core\Logger::getInstance()->info('Creating dispute chat message', [
            'order_id' => $data['order_id'],
            'user_id' => $data['user_id'],
            'sender_role' => $data['sender_role']
        ]);

        $stmt = $this->db->prepare(
            'INSERT INTO `dispute_chats` (`order_id`, `user_id`, `sender_role`, `message`, `attachment_url`)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['order_id'],
            $data['user_id'],
            $data['sender_role'],   // 'admin' or 'user'
            $data['message'],
            $data['attachment_url'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
