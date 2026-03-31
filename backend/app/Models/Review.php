<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

class Review
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByProduct(int $productId, ?int $rating = null): array
    {
        $sql = 'SELECT r.*, u.name as user_name, u.avatar_url 
                FROM `product_reviews` r 
                JOIN `users` u ON r.user_id = u.id 
                WHERE r.product_id = ?';
        
        $params = [$productId];
        if ($rating !== null) {
            $sql .= ' AND r.rating = ?';
            $params[] = $rating;
        }
        $sql .= ' ORDER BY r.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getSummary(int $productId): array
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as total_reviews, COALESCE(AVG(rating), 0) as average_rating FROM `product_reviews` WHERE `product_id` = ?');
        $stmt->execute([$productId]);
        $result = $stmt->fetch();
        return [
            'total_reviews'  => (int) $result['total_reviews'],
            'average_rating' => round((float) $result['average_rating'], 1)
        ];
    }

    public function getRecentAll(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, u.name as user_name, u.avatar_url, p.name as product_name
             FROM `product_reviews` r
             JOIN `users` u ON r.user_id = u.id
             JOIN `products` p ON r.product_id = p.id
             ORDER BY r.created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllAdmin(int $page = 1, int $limit = 10, ?string $search = null): array
    {
        $offset = ($page - 1) * $limit;
        $sql = 'SELECT r.*, u.name as user_name, p.name as product_name, p.image_url as product_image
                FROM `product_reviews` r
                JOIN `users` u ON r.user_id = u.id
                JOIN `products` p ON r.product_id = p.id';
        
        if ($search) {
            $sql .= ' WHERE u.name LIKE :search OR p.name LIKE :search OR r.comment LIKE :search';
        }
        $sql .= ' ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        if ($search) $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAllAdmin(?string $search = null): int
    {
        $sql = 'SELECT COUNT(*) FROM `product_reviews` r
                JOIN `users` u ON r.user_id = u.id
                JOIN `products` p ON r.product_id = p.id';
        
        if ($search) {
            $sql .= ' WHERE u.name LIKE :search OR p.name LIKE :search OR r.comment LIKE :search';
        }
        $stmt = $this->db->prepare($sql);
        if ($search) $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO `product_reviews` (`product_id`, `user_id`, `order_id`, `rating`, `comment`) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$data['product_id'], $data['user_id'], $data['order_id'], $data['rating'], $data['comment']]);
        return (int) $this->db->lastInsertId();
    }

    public function hasReviewed(int $userId, int $productId, int $orderId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM `product_reviews` WHERE `user_id` = ? AND `product_id` = ? AND `order_id` = ?');
        $stmt->execute([$userId, $productId, $orderId]);
        return (bool) $stmt->fetch();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM `product_reviews` WHERE `id` = ?');
        return $stmt->execute([$id]);
    }
}