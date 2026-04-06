<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

class Product
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(?string $search = null, ?int $categoryId = null, int $page = 1, int $limit = 20, string $sortBy = 'newest'): array
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        $where  = ['p.is_active = 1'];

        if ($search !== null && $search !== '') {
            $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($categoryId !== null) {
            $where[]  = 'p.category_id = ?';
            $params[] = $categoryId;
        }

        $whereClause = implode(' AND ', $where);

        // Xác định ORDER BY dựa trên sortBy
        $orderBy = match($sortBy) {
            'popular'    => 'COALESCE(SUM(oi.quantity), 0) DESC, p.created_at DESC',
            'rating'     => 'COALESCE(AVG(r.rating), 0) DESC, COUNT(DISTINCT r.id) DESC',
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            default      => 'p.created_at DESC',  // newest
        };

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare(
            "SELECT p.*, c.name AS category_name,
                    COUNT(DISTINCT r.id) AS review_count,
                    COALESCE(AVG(r.rating), 0) AS average_rating,
                    COALESCE(SUM(oi.quantity), 0) AS sold_count
             FROM `products` p
             LEFT JOIN `categories` c ON p.category_id = c.id
             LEFT JOIN `product_reviews` r ON p.id = r.product_id
             LEFT JOIN `order_items` oi ON oi.product_id = p.id
             LEFT JOIN `orders` o ON oi.order_id = o.id AND o.status IN ('confirmed','completed','shipping')
             WHERE {$whereClause}
             GROUP BY p.id
             ORDER BY {$orderBy}
             LIMIT ? OFFSET ?"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, c.name AS category_name,
                    COUNT(r.id) AS review_count,
                    COALESCE(AVG(r.rating), 0) AS average_rating
             FROM `products` p
             LEFT JOIN `categories` c ON p.category_id = c.id
             LEFT JOIN `product_reviews` r ON p.id = r.product_id
             WHERE p.id = ? AND p.is_active = 1
             GROUP BY p.id LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        \Core\Logger::getInstance()->info('Creating new product', ['name' => $data['name'], 'category_id' => $data['category_id']]);

        $stmt = $this->db->prepare(
            'INSERT INTO `products` (`category_id`,`name`,`slug`,`description`,`price`,`stock`,`image_url`)
             VALUES (?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $data['category_id'],
            $data['name'],
            $data['slug'] ?? $this->slugify($data['name']),
            $data['description'] ?? null,
            $data['price'],
            $data['stock'] ?? 0,
            $data['image_url'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        \Core\Logger::getInstance()->info('Updating product', ['product_id' => $id, 'updated_fields' => array_keys($data)]);

        $fields = [];
        $params = [];
        $allowed = ['category_id','name','slug','description','price','stock','image_url','is_active'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = ?";
                $params[]  = $data[$field];
            }
        }
        if (empty($fields)) return false;
        $params[] = $id;
        return $this->db->prepare('UPDATE `products` SET ' . implode(',', $fields) . ' WHERE `id` = ?')->execute($params);
    }

    public function softDelete(int $id): bool
    {
        \Core\Logger::getInstance()->info('Soft deleting product', ['product_id' => $id]);
        $stmt = $this->db->prepare('UPDATE `products` SET `is_active` = 0 WHERE `id` = ?');
        return $stmt->execute([$id]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM `products` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/\s+/', '-', $text);
        $text = preg_replace('/[^a-z0-9\-]/', '', $text);
        return uniqid($text . '-');
    }
}
