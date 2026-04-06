<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

class Voucher
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM `vouchers`
             WHERE `code` = ? AND `is_active` = 1
               AND (`expires_at` IS NULL OR `expires_at` > NOW())
             LIMIT 1'
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM `vouchers` ORDER BY `created_at` DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActive(): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM `vouchers`
             WHERE `is_active` = 1 AND (`expires_at` IS NULL OR `expires_at` > NOW())
             ORDER BY `created_at` DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        \Core\Logger::getInstance()->info('Creating new voucher', ['code' => $data['code']]);

        $stmt = $this->db->prepare(
            'INSERT INTO `vouchers` (`code`, `discount_amount`, `min_order_value`, `applicable_product_id`, `expires_at`)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            strtoupper(trim($data['code'])),
            $data['discount_amount'],
            $data['min_order_value'] ?? 0,
            $data['applicable_product_id'] ?? null,
            $data['expires_at'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Calculate actual discount for an order given cart items.
     */
    public function calculateDiscount(array $voucher, array $items, int $subtotal): int
    {
        if ($subtotal < (int) $voucher['min_order_value']) return 0;

        // If voucher is product-specific, check if that product is in cart
        if ($voucher['applicable_product_id']) {
            $hasProduct = array_filter($items, fn($i) => (int)$i['product_id'] === (int)$voucher['applicable_product_id']);
            if (empty($hasProduct)) return 0;
        }

        return min((int) $voucher['discount_amount'], $subtotal);
    }
}
