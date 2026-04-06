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
            'SELECT `id`,`name`,`email`,`role`,`phone`,`address`,`avatar_url`,`bank_account`,`bank_name`,`bank_bin`,`created_at`
             FROM `users` WHERE `id` = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(array $data): int
    {
        \Core\Logger::getInstance()->info('Creating new user account', ['email' => $data['email'], 'role' => $data['role'] ?? 'user']);

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
        \Core\Logger::getInstance()->info('Updating user profile', ['user_id' => $id, 'updated_fields' => array_keys($data)]);

        $fields = [];
        $params = [];

        $allowed = ['name', 'phone', 'address', 'bank_account', 'bank_name', 'bank_bin'];
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

    public function updateStatus(int $id, string $status): bool
    {
        \Core\Logger::getInstance()->info('Updating user status', ['user_id' => $id, 'new_status' => $status]);
        $stmt = $this->db->prepare('UPDATE `users` SET `status` = ? WHERE `id` = ?');
        return $stmt->execute([$status, $id]);
    }

    public function updateAvatar(int $id, string $url): bool
    {
        $stmt = $this->db->prepare('UPDATE `users` SET `avatar_url` = ? WHERE `id` = ?');
        return $stmt->execute([$url, $id]);
    }

    public function saveResetToken(string $email, string $token, string $expiresAt): bool
    {
        $stmt = $this->db->prepare('UPDATE `users` SET `reset_token` = ?, `reset_token_expires_at` = ? WHERE `email` = ?');
        return $stmt->execute([$token, $expiresAt, $email]);
    }

    public function findByResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM `users` WHERE `reset_token` = ? AND `reset_token_expires_at` > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        \Core\Logger::getInstance()->info('Updating user password', ['user_id' => $id]);
        $stmt = $this->db->prepare('UPDATE `users` SET `password` = ?, `reset_token` = NULL, `reset_token_expires_at` = NULL WHERE `id` = ?');
        return $stmt->execute([$passwordHash, $id]);
    }

    public function getAll(int $page = 1, int $limit = 100): array
    {
        $offset = ($page - 1) * $limit;
        $stmt   = $this->db->prepare(
            'SELECT u.id, u.name, u.email, u.role, u.phone, u.created_at, u.status, COUNT(o.id) as orders_count
             FROM `users` u
             LEFT JOIN `orders` o ON u.id = o.user_id
             WHERE u.role = "user"
             GROUP BY u.id
             ORDER BY u.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(id) FROM `users` WHERE `role` = "user"');
        return (int) $stmt->fetchColumn();
    }

    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
