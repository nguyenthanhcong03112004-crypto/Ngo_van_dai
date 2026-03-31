<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

class Order
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new order and its items in a transaction.
     * Returns the new order ID.
     */
    public function create(array $data): int
    {
        \Core\Logger::getInstance()->info('Starting order creation transaction', ['user_id' => $data['user_id'], 'total_amount' => $data['total_amount']]);
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO `orders`
                 (`user_id`, `voucher_id`, `shipping_address`, `shipping_region`,
                  `shipping_cost`, `subtotal`, `discount`, `total_amount`, `note`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $data['user_id'],
                $data['voucher_id'] ?? null,
                $data['shipping_address'],
                $data['shipping_region'],
                $data['shipping_cost'],
                $data['subtotal'],
                $data['discount'] ?? 0,
                $data['total_amount'],
                $data['note'] ?? null,
            ]);

            $orderId = (int) $this->db->lastInsertId();

            // Insert order items
            $itemStmt = $this->db->prepare(
                'INSERT INTO `order_items`
                 (`order_id`, `product_id`, `product_name`, `product_price`, `quantity`)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($data['items'] as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['product_price'],
                    $item['quantity'],
                ]);
            }

            $this->db->commit();
            \Core\Logger::getInstance()->info('Order created successfully', ['order_id' => $orderId]);
            return $orderId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            \Core\Logger::getInstance()->error('Order creation failed, transaction rolled back', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get all orders for a specific user, newest first.
     */
    public function getByUser(int $userId, ?int $month = null, ?int $year = null): array
    {
        $sql = 'SELECT o.*,
                       u.name AS user_name, u.email AS user_email,
                       v.code AS voucher_code
                FROM `orders` o
                LEFT JOIN `users`    u ON o.user_id    = u.id
                LEFT JOIN `vouchers` v ON o.voucher_id = v.id
                WHERE o.user_id = ?';
        
        $params = [$userId];

        if ($month && $year) {
            $sql .= ' AND MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?';
            $params[] = $month;
            $params[] = $year;
        }

        $sql .= ' ORDER BY o.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        return array_map(fn($o) => $this->attachItems($o), $orders);
    }

    /**
     * Get all orders (admin), with optional status filter.
     */
    public function getAll(?string $status = null, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        if ($status !== null) {
            $stmt = $this->db->prepare(
                'SELECT o.*, u.name AS user_name, u.email AS user_email, v.code AS voucher_code
                 FROM `orders` o
                 LEFT JOIN `users`    u ON o.user_id    = u.id
                 LEFT JOIN `vouchers` v ON o.voucher_id = v.id
                 WHERE o.status = ?
                 ORDER BY o.created_at DESC
                 LIMIT ? OFFSET ?'
            );
            $stmt->execute([$status, $limit, $offset]);
        } else {
            $stmt = $this->db->prepare(
                'SELECT o.*, u.name AS user_name, u.email AS user_email, v.code AS voucher_code
                 FROM `orders` o
                 LEFT JOIN `users`    u ON o.user_id    = u.id
                 LEFT JOIN `vouchers` v ON o.voucher_id = v.id
                 ORDER BY o.created_at DESC
                 LIMIT ? OFFSET ?'
            );
            $stmt->execute([$limit, $offset]);
        }

        $orders = $stmt->fetchAll();
        return array_map(fn($o) => $this->attachItems($o), $orders);
    }

    /**
     * Get a single order by ID with its items.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT o.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
                    v.code AS voucher_code
             FROM `orders` o
             LEFT JOIN `users`    u ON o.user_id    = u.id
             LEFT JOIN `vouchers` v ON o.voucher_id = v.id
             WHERE o.id = ?'
        );
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) return null;

        return $this->attachItems($order);
    }

    /**
     * Update order status.
     */
    public function updateStatus(int $id, string $status): bool
    {
        \Core\Logger::getInstance()->info('Updating order status', ['order_id' => $id, 'new_status' => $status]);
        $stmt = $this->db->prepare('UPDATE `orders` SET `status` = ? WHERE `id` = ?');
        return $stmt->execute([$status, $id]);
    }

    /**
     * Save the payment receipt URL for an order.
     */
    public function updateReceiptUrl(int $id, string $url): bool
    {
        \Core\Logger::getInstance()->info('Uploading payment receipt for order', ['order_id' => $id]);
        $stmt = $this->db->prepare(
            'UPDATE `orders` SET `payment_receipt_url` = ?, `status` = "confirmed" WHERE `id` = ?'
        );
        return $stmt->execute([$url, $id]);
    }

    /**
     * Verify that an order belongs to a specific user.
     */
    public function belongsToUser(int $orderId, int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM `orders` WHERE `id` = ? AND `user_id` = ?');
        $stmt->execute([$orderId, $userId]);
        return (bool) $stmt->fetch();
    }

    public function verifyPurchase(int $userId, int $productId, int $orderId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM `orders` o 
             JOIN `order_items` oi ON o.id = oi.order_id 
             WHERE o.id = ? AND o.user_id = ? AND oi.product_id = ? AND o.status = "completed"'
        );
        $stmt->execute([$orderId, $userId, $productId]);
        return (bool) $stmt->fetch();
    }

    // ----------------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------------

    private function attachItems(array $order): array
    {
        $stmt = $this->db->prepare('SELECT * FROM `order_items` WHERE `order_id` = ?');
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
        return $order;
    }
}
