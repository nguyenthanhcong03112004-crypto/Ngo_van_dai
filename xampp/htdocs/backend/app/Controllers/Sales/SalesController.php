<?php
declare(strict_types=1);

namespace Controllers\Sales;

use Core\BaseController;
use Core\Middleware;
use Core\Database;
use PDO;

/**
 * SalesController — handles all Sales Staff API endpoints.
 *
 * Routes:
 *   GET  /api/sales/cabinets           → getCabinets()
 *   POST /api/sales/cabinets           → createCabinet()
 *   GET  /api/sales/shelf              → getShelf()
 *   GET  /api/sales/warehouse          → getWarehouse()
 *   POST /api/sales/shelf/add          → addToShelf()
 *   POST /api/sales/shelf/remove       → removeFromShelf()
 *   POST /api/sales/check-customer     → checkCustomer()
 *   POST /api/sales/create-order       → createOrder()
 *   POST /api/sales/confirm-payment    → confirmPayment()
 *   GET  /api/sales/orders             → getOrders()
 *   POST /api/sales/cancel-order       → cancelOrder()
 */
class SalesController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Require auth for either 'admin' or 'sales' role.
     */
    private function requireSalesOrAdmin(): array
    {
        $payload = Middleware::requireAuth();
        $role = $payload['role'] ?? '';
        if (!in_array($role, ['admin', 'sales'], true)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Chỉ nhân viên bán hàng hoặc Admin mới có quyền truy cập.', 'data' => null]);
            exit;
        }
        return $payload;
    }

    // ─── GET /api/sales/shelf ────────────────────────────────────────────────

    /**
     * Returns a 5×5 grid. Each cell maps to a shelf_row/shelf_col slot.
     * Empty slots return null. Occupied slots return product info.
     */
    // ─── GET /api/sales/cabinets ───────────────────────────────────────────
    public function getCabinets(): void
    {
        $this->requireSalesOrAdmin();
        $stmt = $this->db->query("SELECT * FROM `cabinets` ORDER BY id ASC");
        $this->success($stmt->fetchAll(PDO::FETCH_ASSOC), 'Cabinets loaded');
    }

    // ─── POST /api/sales/cabinets ──────────────────────────────────────────
    public function createCabinet(): void
    {
        $this->requireSalesOrAdmin();
        $body = $this->getBody();
        $name = trim($body['name'] ?? 'Tủ mới');
        $rows = max(1, min(10, (int)($body['rows'] ?? 5)));
        $cols = max(1, min(10, (int)($body['cols'] ?? 5)));

        $stmt = $this->db->prepare("INSERT INTO `cabinets` (`name`, `rows`, `cols`) VALUES (?, ?, ?)");
        $stmt->execute([$name, $rows, $cols]);
        
        $this->success(['id' => $this->db->lastInsertId()], 'Đã tạo tủ mới thành công.');
    }

    // ─── GET /api/sales/shelf ────────────────────────────────────────────────
    public function getShelf(): void
    {
        $this->requireSalesOrAdmin();
        $cabinetId = (int)($_GET['cabinet_id'] ?? 1);

        // Get cabinet info
        $stmtCab = $this->db->prepare("SELECT * FROM `cabinets` WHERE id = ?");
        $stmtCab->execute([$cabinetId]);
        $cabinet = $stmtCab->fetch(PDO::FETCH_ASSOC);

        if (!$cabinet) {
            $this->error('Không tìm thấy tủ này.', 404);
        }

        // Get slots
        $stmt = $this->db->prepare(
            "SELECT cs.*, p.name, p.price, p.image_url
             FROM `cabinet_slots` cs
             JOIN `products` p ON p.id = cs.product_id
             WHERE cs.cabinet_id = ?"
        );
        $stmt->execute([$cabinetId]);
        $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build grid
        $grid = [];
        for ($r = 1; $r <= $cabinet['rows']; $r++) {
            for ($c = 1; $c <= $cabinet['cols']; $c++) {
                $grid[$r][$c] = null;
            }
        }

        foreach ($slots as $s) {
            $r = (int)$s['row'];
            $c = (int)$s['col'];
            $grid[$r][$c] = [
                'slot_id'      => (int)$s['id'],
                'id'           => (int)$s['product_id'],
                'name'         => $s['name'],
                'price'        => (int)$s['price'],
                'thumb'        => $s['image_url'],
                'shelf_status' => $s['status'],
            ];
        }

        $this->success([
            'cabinet' => $cabinet,
            'grid'    => $grid
        ], 'Shelf data loaded');
    }

    // ─── GET /api/sales/warehouse ──────────────────────────────────────────
    public function getWarehouse(): void
    {
        $this->requireSalesOrAdmin();
        $q = trim($_GET['q'] ?? '');
        
        $sql = "SELECT id, name, price, stock, image_url as thumb FROM `products` WHERE `is_active` = 1 AND `stock` > 0";
        $params = [];
        if ($q !== '') {
            $sql .= " AND name LIKE ?";
            $params[] = "%$q%";
        }
        $sql .= " ORDER BY name ASC LIMIT 20";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $this->success($stmt->fetchAll(PDO::FETCH_ASSOC), 'Warehouse data loaded');
    }

    // ─── POST /api/sales/shelf/add ─────────────────────────────────────────
    public function addToShelf(): void
    {
        $this->requireSalesOrAdmin();
        $body      = $this->getBody();
        $cabinetId = (int)($body['cabinet_id'] ?? 0);
        $productId = (int)($body['product_id'] ?? 0);
        $row       = (int)($body['row'] ?? 0);
        $col       = (int)($body['col'] ?? 0);
        $isReplace = !empty($body['replace_slot_id']);
        $replaceId = (int)($body['replace_slot_id'] ?? 0);

        if (!$cabinetId || !$productId || !$row || !$col) {
            $this->error('Thiếu thông tin vị trí hoặc sản phẩm.', 422);
        }

        $this->db->beginTransaction();
        try {
            // 1. Check stock
            $stmt = $this->db->prepare("SELECT stock FROM `products` WHERE id = ? FOR UPDATE");
            $stmt->execute([$productId]);
            $p = $stmt->fetch();
            if (!$p || $p['stock'] < 1) {
                throw new \Exception('Sản phẩm đã hết hàng trong kho.');
            }

            // 2. Decrement stock
            $this->db->prepare("UPDATE `products` SET `stock` = `stock` - 1 WHERE id = ?")->execute([$productId]);

            if ($isReplace) {
                // Update existing slot (used for sold items)
                $this->db->prepare("UPDATE `cabinet_slots` SET `product_id` = ?, `status` = 'available' WHERE id = ?")
                         ->execute([$productId, $replaceId]);
            } else {
                // Create new slot
                $this->db->prepare("INSERT INTO `cabinet_slots` (`cabinet_id`, `row`, `col`, `product_id`, `status`) VALUES (?, ?, ?, ?, 'available')")
                         ->execute([$cabinetId, $row, $col, $productId]);
            }

            $this->db->commit();
            $this->success(null, 'Đã đưa sản phẩm lên kệ thành công.');
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->error($e->getMessage(), 400);
        }
    }

    // ─── POST /api/sales/shelf/remove ──────────────────────────────────────
    public function removeFromShelf(): void
    {
        $this->requireSalesOrAdmin();
        $body   = $this->getBody();
        $slotId = (int)($body['slot_id'] ?? 0);

        if (!$slotId) $this->error('Thiếu Slot ID.', 422);

        $stmt = $this->db->prepare("SELECT * FROM `cabinet_slots` WHERE id = ?");
        $stmt->execute([$slotId]);
        $slot = $stmt->fetch();

        if (!$slot) $this->error('Không tìm thấy vị trí này.', 404);

        $this->db->beginTransaction();
        try {
            // If not sold/reserved, return to stock
            if ($slot['status'] === 'available') {
                $this->db->prepare("UPDATE `products` SET `stock` = `stock` + 1 WHERE id = ?")
                         ->execute([$slot['product_id']]);
            }

            // Delete slot
            $this->db->prepare("DELETE FROM `cabinet_slots` WHERE id = ?")->execute([$slotId]);

            $this->db->commit();
            $this->success(null, 'Đã gỡ sản phẩm khỏi kệ.');
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->error('Lỗi khi gỡ sản phẩm: ' . $e->getMessage(), 500);
        }
    }

    // ─── POST /api/sales/check-customer ──────────────────────────────────────

    /**
     * Input:  { "phone": "0901234567" }
     * Output: { found: true|false, user: {...}|null }
     */
    public function checkCustomer(): void
    {
        $this->requireSalesOrAdmin();
        $body  = $this->getBody();
        $phone = trim($body['phone'] ?? '');

        if (empty($phone)) {
            $this->error('Vui lòng nhập số điện thoại.', 422);
        }

        $stmt = $this->db->prepare(
            "SELECT id, name, email, phone FROM `users` WHERE `phone` = ? AND `role` = 'user' LIMIT 1"
        );
        $stmt->execute([$phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $this->success(['exists' => true,  'customer' => $user], 'Tìm thấy khách hàng');
        } else {
            $this->success(['exists' => false, 'customer' => null],  'Khách vãng lai');
        }
    }

    // ─── POST /api/sales/create-order ────────────────────────────────────────

    /**
     * Input:  { "product_id": 5, "customer_phone": "0901234567",
     *           "customer_name": "Nguyễn Văn A", "user_id": 12|null,
     *           "payment_method": "cash"|"transfer" }
     * Output: { order_id: 42, sale_price: 15990000, ... }
     */
    public function createOrder(): void
    {
        $payload = $this->requireSalesOrAdmin();
        $body    = $this->getBody();

        $productId     = (int)($body['product_id'] ?? 0);
        $customerPhone = trim($body['customer_phone'] ?? '');
        $customerName  = trim($body['customer_name'] ?? 'Khách vãng lai');
        $userId        = isset($body['user_id']) && $body['user_id'] ? (int)$body['user_id'] : null;
        $payMethod     = in_array($body['payment_method'] ?? '', ['cash', 'transfer']) ? $body['payment_method'] : 'transfer';

        $slotId = (int)($body['slot_id'] ?? 0);

        if (!$productId || !$customerPhone || !$slotId) {
            $this->error('Thiếu thông tin sản phẩm, vị trí kệ hoặc khách hàng.', 422);
        }

        // Fetch slot
        $stmtSlot = $this->db->prepare("SELECT id, status FROM `cabinet_slots` WHERE id = ? FOR UPDATE");
        $stmtSlot->execute([$slotId]);
        $slot = $stmtSlot->fetch();

        if (!$slot) $this->error('Vị trí kệ không hợp lệ.', 404);
        if ($slot['status'] !== 'available') $this->error('Sản phẩm tại vị trí này không khả dụng.', 409);

        // Fetch product
        $stmt = $this->db->prepare("SELECT id, name, price FROM `products` WHERE `id` = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) $this->error('Sản phẩm không tồn tại.', 404);

        $this->db->beginTransaction();
        try {
            // 1. Mark slot as reserved
            $this->db->prepare("UPDATE `cabinet_slots` SET `status` = 'reserved' WHERE `id` = ?")
                     ->execute([$slotId]);

            // 2. Create offline order
            $insert = $this->db->prepare(
                "INSERT INTO `offline_orders`
                 (`product_id`, `slot_id`, `user_id`, `customer_phone`, `customer_name`, `sale_price`, `staff_id`, `payment_method`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $insert->execute([
                $productId,
                $slotId,
                $userId,
                $customerPhone,
                $customerName,
                (int)$product['price'],
                (int)$payload['user_id'],
                $payMethod,
            ]);

            $orderId = (int)$this->db->lastInsertId();
            $this->db->commit();
            
            $this->success([
                'order' => [
                    'id'             => $orderId,
                    'product_name'   => $product['name'],
                    'sale_price'     => (int)$product['price'],
                    'customer_phone' => $customerPhone,
                    'customer_name'  => $customerName,
                    'payment_method' => $payMethod,
                ]
            ], 'Đơn hàng đã được tạo.');
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->error($e->getMessage(), 500);
        }
    }

    // ─── POST /api/sales/confirm-payment ─────────────────────────────────────

    /**
     * Input:  { "order_id": 42 }
     * Output: { success: true }
     */
    public function confirmPayment(): void
    {
        $payload = $this->requireSalesOrAdmin();
        $body    = $this->getBody();
        $orderId = (int)($body['order_id'] ?? 0);

        if (!$orderId) {
            $this->error('Thiếu Order ID.', 422);
        }

        // Fetch the offline order
        $stmt = $this->db->prepare(
            "SELECT * FROM `offline_orders` WHERE `id` = ? AND `status` = 'pending'"
        );
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $this->error('Không tìm thấy đơn hàng hoặc đơn đã được xử lý.', 404);
        }

        $this->db->beginTransaction();
        try {
            // 1. Update order status to paid, set confirmer
            $this->db->prepare(
                "UPDATE `offline_orders` SET 
                    `status` = 'paid', 
                    `confirmed_by` = ?, 
                    `confirmed_at` = NOW() 
                 WHERE `id` = ?"
            )->execute([(int)$payload['user_id'], $orderId]);

            // 2. Mark slot as sold. Stock is NOT decremented here because it was done in addToShelf.
            $this->db->prepare(
                "UPDATE `cabinet_slots` SET `status` = 'sold' WHERE `id` = ?"
            )->execute([$order['slot_id']]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->error('Lỗi xác nhận thanh toán: ' . $e->getMessage(), 500);
        }

        $this->success(['order_id' => $orderId], 'Xác nhận thanh toán thành công!');
    }

    // ─── GET /api/sales/orders ───────────────────────────────────────────────

    /**
     * Helper to get GET parameters
     */
    protected function getQueryParams(): array
    {
        return $_GET;
    }

    /**
     * Returns recent offline orders (limit 50) with staff confirmation details.
     */
    /**
     * Warehouse quick look - Returns paginated products with search & filtering.
     * Query: page, limit, search, category_id, sort_by, sort_dir
     */
    public function getInventory(): void
    {
        $this->requireSalesOrAdmin();
        $q = $this->getQueryParams();

        $page     = (int)($q['page'] ?? 1);
        $limit    = (int)($q['limit'] ?? 10);
        $search   = trim($q['search'] ?? '');
        $catId    = isset($q['category_id']) && $q['category_id'] !== '' ? (int)$q['category_id'] : null;
        $sortBy   = in_array($q['sort_by'] ?? '', ['id', 'name', 'price', 'stock']) ? $q['sort_by'] : 'id';
        $sortDir  = strtoupper($q['sort_dir'] ?? '') === 'DESC' ? 'DESC' : 'ASC';

        $offset = ($page - 1) * $limit;
        $where  = ['1=1'];
        $params = [];

        if ($search) {
            $where[]  = "(name LIKE ? OR description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($catId) {
            $where[]  = "category_id = ?";
            $params[] = $catId;
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM `products` WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Fetch items
        $stmt = $this->db->prepare(
            "SELECT id, name, price, stock, image_url AS thumb, category_id 
             FROM `products` 
             WHERE {$whereClause}
             ORDER BY {$sortBy} {$sortDir}
             LIMIT ? OFFSET ?"
        );
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->success([
            'items' => $products,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit
        ], 'Inventory loaded');
    }

    /**
     * Recent offline orders with pagination, search & status/date filters.
     */
    public function getOrders(): void
    {
        $this->requireSalesOrAdmin();
        $q = $this->getQueryParams();

        $page      = (int)($q['page'] ?? 1);
        $limit     = (int)($q['limit'] ?? 10);
        $search    = trim($q['search'] ?? '');
        $status    = trim($q['status'] ?? '');
        $startDate = trim($q['start_date'] ?? '');
        $endDate   = trim($q['end_date'] ?? '');
        $sortBy    = in_array($q['sort_by'] ?? '', ['id', 'created_at', 'sale_price']) ? $q['sort_by'] : 'created_at';
        $sortDir   = strtoupper($q['sort_dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

        $offset = ($page - 1) * $limit;
        $where  = ['1=1'];
        $params = [];

        if ($search) {
            $where[]  = "(oo.customer_name LIKE ? OR oo.customer_phone LIKE ? OR p.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($status) {
            $where[]  = "oo.status = ?";
            $params[] = $status;
        }
        if ($startDate) {
            $where[]  = "DATE(oo.created_at) >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $where[]  = "DATE(oo.created_at) <= ?";
            $params[] = $endDate;
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `offline_orders` oo JOIN `products` p ON p.id = oo.product_id WHERE {$whereClause}"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Fetch items
        $stmt = $this->db->prepare(
            "SELECT oo.*, 
                    p.name AS product_name, p.image_url AS product_thumb,
                    u1.name AS staff_name,
                    u2.name AS confirmer_name
             FROM `offline_orders` oo
             JOIN `products` p ON p.id = oo.product_id
             LEFT JOIN `users` u1 ON u1.id = oo.staff_id
             LEFT JOIN `users` u2 ON u2.id = oo.confirmed_by
             WHERE {$whereClause}
             ORDER BY oo.{$sortBy} {$sortDir}
             LIMIT ? OFFSET ?"
        );
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->success([
            'items' => $orders,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit
        ], 'Order history loaded');
    }

    // ─── POST /api/sales/cancel-order ────────────────────────────────────────

    /**
     * Cancel a pending offline order and release the product reservation.
     * Input:  { "order_id": 42 }
     */
    public function cancelOrder(): void
    {
        $this->requireSalesOrAdmin();
        $body    = $this->getBody();
        $orderId = (int)($body['order_id'] ?? 0);

        if (!$orderId) {
            $this->error('Thiếu Order ID.', 422);
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM `offline_orders` WHERE `id` = ? AND `status` = 'pending'"
        );
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->error('Không tìm thấy đơn hàng.', 404);
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "UPDATE `offline_orders` SET `status` = 'cancelled' WHERE `id` = ?"
            )->execute([$orderId]);

            // Release slot status back to available
            $this->db->prepare(
                "UPDATE `cabinet_slots` SET `status` = 'available' WHERE `id` = ? AND `status` = 'reserved'"
            )->execute([$order['slot_id']]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->error('Lỗi hủy đơn: ' . $e->getMessage(), 500);
        }

        $this->success(null, 'Đã hủy đơn hàng.');
    }
    /**
     * CRUD: Create Product
     */
    public function createProduct(): void
    {
        $this->requireSalesOrAdmin();
        $body = $this->getBody();

        $name   = trim($body['name'] ?? '');
        $price  = (int)($body['price'] ?? 0);
        $stock  = (int)($body['stock'] ?? 0);
        $catId  = (int)($body['category_id'] ?? 1);
        $imgUrl = trim($body['image_url'] ?? '');
        $desc   = trim($body['description'] ?? '');

        if (!$name || $price <= 0) $this->error('Tên và giá không hợp lệ.', 422);

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-')) . '-' . time();

        $stmt = $this->db->prepare(
            "INSERT INTO `products` (`name`, `slug`, `price`, `stock`, `category_id`, `image_url`, `description`, `is_active`) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $stmt->execute([$name, $slug, $price, $stock, $catId, $imgUrl, $desc]);

        $this->success(['id' => $this->db->lastInsertId()], 'Đã thêm sản phẩm.');
    }

    /**
     * CRUD: Update Product
     */
    public function updateProduct(array $params): void
    {
        $this->requireSalesOrAdmin();
        $id   = (int)($params['id'] ?? 0);
        $body = $this->getBody();

        $fields = []; $vals = [];
        foreach (['name', 'price', 'stock', 'category_id', 'description', 'image_url'] as $f) {
            if (isset($body[$f])) {
                $fields[] = "`$f` = ?";
                $vals[] = $body[$f];
            }
        }
        if (empty($fields)) $this->error('Không có dữ liệu cập nhật.', 422);

        $vals[] = $id;
        $stmt = $this->db->prepare("UPDATE `products` SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($vals);
        $this->success(null, 'Đã cập nhật sản phẩm.');
    }

    /**
     * CRUD: Delete Product
     */
    public function deleteProduct(array $params): void
    {
        $this->requireSalesOrAdmin();
        $id = (int)($params['id'] ?? 0);
        $this->db->prepare("DELETE FROM `products` WHERE id = ?")->execute([$id]);
        $this->success(null, 'Đã xóa sản phẩm.');
    }

    /**
     * Export Inventory to Excel
     */
    public function exportInventory(): void
    {
        $this->requireSalesOrAdmin();
        $stmt = $this->db->query("SELECT p.id, p.name, p.price, p.stock, c.name as category FROM `products` p JOIN `categories` c ON c.id = p.category_id");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['ID', 'Tên sản phẩm', 'Giá', 'Tồn kho', 'Danh mục'], NULL, 'A1');
        $sheet->fromArray($data, NULL, 'A2');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Inventory_'.date('Ymd').'.xlsx"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export Orders to Excel
     */
    public function exportOrders(): void
    {
        $this->requireSalesOrAdmin();
        $stmt = $this->db->query("
            SELECT oo.id, oo.customer_name, oo.customer_phone, oo.sale_price, oo.status, u.name as confirmer_name, oo.created_at 
            FROM `offline_orders` oo 
            LEFT JOIN `users` u ON u.id = oo.confirmed_by 
            ORDER BY oo.created_at DESC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Mã đơn', 'Khách hàng', 'SĐT', 'Tổng tiền', 'Trạng thái', 'Người xác nhận', 'Ngày tạo'], NULL, 'A1');
        $sheet->fromArray($data, NULL, 'A2');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Orders_'.date('Ymd').'.xlsx"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Generate Invoice PDF
     */
    public function generateInvoicePDF(array $params): void
    {
        $this->requireSalesOrAdmin();
        $id = (int)($params['id'] ?? 0);
        $stmt = $this->db->prepare("SELECT oo.*, p.name as product_name FROM `offline_orders` oo JOIN `products` p ON p.id = oo.product_id WHERE oo.id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) die("Order not found");

        $qrUrl = "https://img.vietqr.io/image/MB-0335011404-compact2.png?amount={$order['sale_price']}&addInfo=ORDER{$order['id']}";

        $html = "
        <div style='font-family: DejaVu Sans, sans-serif; text-align: center;'>
            <h1 style='margin-bottom: 5px;'>HOÁ ĐƠN BÁN LẺ</h1>
            <p style='font-size: 12px; color: #666;'>ElectroHub - Chuyên smartphone chính hãng</p>
            <hr>
            <div style='text-align: left; margin: 20px 0;'>
                <p><strong>Mã đơn:</strong> #{$order['id']}</p>
                <p><strong>Khách hàng:</strong> {$order['customer_name']}</p>
                <p><strong>Số điện thoại:</strong> {$order['customer_phone']}</p>
                <p><strong>Sản phẩm:</strong> {$order['product_name']}</p>
                <p><strong>Ngày tạo:</strong> " . date('d/m/Y H:i', strtotime($order['created_at'])) . "</p>
            </div>
            <hr>
            <h2 style='color: #0066cc;'>Tổng tiền: " . number_format($order['sale_price'], 0, ',', '.') . " VNĐ</h2>
            <div style='margin-top: 30px;'>
                <p style='font-size: 10px; text-transform: uppercase; font-weight: bold;'>Quét mã để thanh toán</p>
                <img src='{$qrUrl}' style='width: 200px; height: 200px; border: 1px solid #eee; padding: 10px; border-radius: 10px;'>
            </div>
            <p style='margin-top: 40px; font-size: 10px; font-style: italic;'>Cảm ơn quý khách đã mua hàng!</p>
        </div>";
        
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();
        $dompdf->stream("Invoice_{$id}.pdf", ["Attachment" => false]);
        exit;
    }

    /**
     * Get order history for a specific product
     */
    public function getProductOrders(array $params): void
    {
        $this->requireSalesOrAdmin();
        $id = (int)($params['id'] ?? 0);
        $stmt = $this->db->prepare("
            SELECT oo.id, oo.customer_name, oo.customer_phone, oo.sale_price, oo.status, oo.created_at, u.name as confirmer_name
            FROM `offline_orders` oo
            LEFT JOIN `users` u ON u.id = oo.confirmed_by
            WHERE oo.product_id = ?
            ORDER BY oo.created_at DESC
        ");
        $stmt->execute([$id]);
        $this->success($stmt->fetchAll(PDO::FETCH_ASSOC), 'Product order history loaded');
    }

    // ─── GET /api/sales/categories ────────────────────────────────────────────

    /**
     * Returns all product categories.
     */
    public function getCategories(): void
    {
        $this->requireSalesOrAdmin();
        $stmt = $this->db->query("SELECT id, name FROM `categories` ORDER BY name ASC");
        $this->success($stmt->fetchAll(PDO::FETCH_ASSOC), 'Categories loaded');
    }

    /**
     * Helper to ensure the current user is an admin
     */
    private function requireAdmin(): array
    {
        $payload = Middleware::requireAuth();
        if (($payload['role'] ?? '') !== 'admin') {
            $this->error('Admin privileges required', 403);
        }
        return $payload;
    }

    /**
     * Get all users (Admin only)
     */
    public function getUsers(): void
    {
        $this->requireAdmin();
        // Đọc query string từ $_GET (không phải URL route params)
        $q_params = $this->getQueryParams();
        $search = trim($q_params['search'] ?? '');
        $page   = (int)($q_params['page']  ?? 1);
        $limit  = (int)($q_params['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $like = "%$search%";
        $stmt = $this->db->prepare("
            SELECT id, name, email, phone, role, status, created_at 
            FROM `users` 
            WHERE (name LIKE ? OR email LIKE ? OR phone LIKE ?)
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$like, $like, $like, $limit, $offset]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM `users` WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?");
        $stmtCount->execute([$like, $like, $like]);
        $total = $stmtCount->fetchColumn();

        $this->success(['items' => $items, 'total' => (int)$total, 'page' => $page, 'limit' => $limit], 'Users loaded');
    }

    /**
     * Create a new user (Admin only)
     */
    public function createUser(): void
    {
        $this->requireAdmin();
        $data = $this->getBody();
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = password_hash($data['password'] ?? '123456', PASSWORD_DEFAULT);
        $role = $data['role'] ?? 'sales';
        $phone = $data['phone'] ?? '';

        if (!$name || !$email) $this->error('Name and email are required');

        $stmt = $this->db->prepare("INSERT INTO `users` (name, email, password, role, phone, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role, $phone, date('Y-m-d H:i:s')]);
        $this->success(['id' => $this->db->lastInsertId()], 'User created successfully');
    }

    /**
     * Update an existing user (Admin only)
     */
    public function updateUser(array $params): void
    {
        $this->requireAdmin();
        $id = (int)($params['id'] ?? 0);
        $data = $this->getBody();

        $sql = "UPDATE `users` SET name = ?, email = ?, phone = ?, role = ? ";
        $vals = [$data['name'], $data['email'], $data['phone'], $data['role']];

        if (!empty($data['password'])) {
            $sql .= ", password = ? ";
            $vals[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $vals[] = $id;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($vals);
        $this->success(null, 'User updated successfully');
    }

    /**
     * Delete a user (Admin only)
     */
    public function deleteUser(array $params): void
    {
        $this->requireAdmin();
        $id = (int)($params['id'] ?? 0);
        $stmt = $this->db->prepare("DELETE FROM `users` WHERE id = ?");
        $stmt->execute([$id]);
        $this->success(null, 'User deleted successfully');
    }
}
