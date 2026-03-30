<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM `users` WHERE `email` = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT `id`,`name`,`email`,`role`,`phone`,`address`,`avatar_url`,`created_at`
             FROM `users` WHERE `id` = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role'] ?? 'user',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateProfile(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $allowed = ['name', 'phone', 'address'];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = ?";
                $params[]  = $data[$field];
            }
        }

        if (empty($fields)) return false;

        $params[] = $id;
        $sql = 'UPDATE `users` SET ' . implode(', ', $fields) . ' WHERE `id` = ?';
        return $this->db->prepare($sql)->execute($params);
    }

    public function updateAvatar(int $id, string $url): bool
    {
        $stmt = $this->db->prepare('UPDATE `users` SET `avatar_url` = ? WHERE `id` = ?');
        return $stmt->execute([$url, $id]);
    }

    public function getAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $stmt   = $this->db->prepare(
            'SELECT `id`,`name`,`email`,`role`,`phone`,`created_at`
             FROM `users` WHERE `role` = "user"
             ORDER BY `created_at` DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
