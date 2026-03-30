<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

class Wishlist
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT w.id, w.created_at,
                    p.id AS product_id, p.name AS product_name, p.price,
                    p.image_url, p.stock, c.name AS category_name
             FROM `wishlists` w
             JOIN `products`   p ON w.product_id   = p.id
             JOIN `categories` c ON p.category_id  = c.id
             WHERE w.user_id = ?
             ORDER BY w.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function add(int $userId, int $productId): bool
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO `wishlists` (`user_id`, `product_id`) VALUES (?, ?)'
        );
        return $stmt->execute([$userId, $productId]);
    }

    public function remove(int $userId, int $productId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM `wishlists` WHERE `user_id` = ? AND `product_id` = ?'
        );
        return $stmt->execute([$userId, $productId]);
    }
}
