<?php
declare(strict_types=1);

namespace Controllers\User;

use Core\BaseController;
use Core\Middleware;
use Models\Order;
use Models\Product;
use Models\Voucher;

class CheckoutController extends BaseController
{
    public function create(): void
    {
        $payload = Middleware::requireAuth();
        $userId = $payload['user_id'];
        $body = $this->getBody();

        $missing = $this->validate($body, ['items', 'shipping_address']);
        if (!empty($missing)) {
            $this->error('Thiếu thông tin đặt hàng: ' . implode(', ', $missing), 422);
        }

        $productModel = new Product();
        $voucherModel = new Voucher();
        $orderModel = new Order();

        $subtotal = 0;
        $orderItems = [];

        // Tính toán lại giá từ DB, tránh trường hợp Client hack giá
        foreach ($body['items'] as $item) {
            $p = $productModel->findById((int)($item['id'] ?? $item['product_id']));
            if (!$p || !$p['is_active']) {
                $this->error("Sản phẩm không tồn tại hoặc đã ngừng bán.", 400);
            }
            
            $qty = (int)$item['quantity'];
            if ($p['stock'] < $qty) {
                $this->error("Sản phẩm '{$p['name']}' chỉ còn tối đa {$p['stock']} sản phẩm trong kho.", 400);
            }

            $subtotal += $p['price'] * $qty;
            $orderItems[] = [
                'product_id' => $p['id'],
                'product_name' => $p['name'],
                'product_price' => $p['price'],
                'quantity' => $qty
            ];
        }

        $discount = 0;
        $voucherId = null;
        if (!empty($body['voucher_code'])) {
            $v = $voucherModel->findByCode($body['voucher_code']);
            if ($v) {
                $discount = $voucherModel->calculateDiscount($v, $orderItems, $subtotal);
                $voucherId = $v['id'];
            }
        }

        $shippingCost = 0; // Tạm thời miễn phí vận chuyển
        $totalAmount = max(0, $subtotal + $shippingCost - $discount);

        $orderData = [
            'user_id' => $userId,
            'voucher_id' => $voucherId,
            'shipping_address' => $body['shipping_address'] . (isset($body['phone']) ? ' - SĐT: ' . $body['phone'] : ''),
            'shipping_region' => 'other',
            'shipping_cost' => $shippingCost,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total_amount' => $totalAmount,
            'note' => $body['note'] ?? null,
            'items' => $orderItems
        ];

        try {
            $orderId = $orderModel->create($orderData);
            
            // Gửi email xác nhận đơn hàng
            $userModel = new \Models\User();
            $user = $userModel->findById($userId);
            if ($user) {
                \Core\EmailService::getInstance()->sendOrderConfirmation($user['email'], $user['name'], (string)$orderId, $totalAmount);
            }
    
            $this->success(['order_id' => $orderId, 'total_amount' => $totalAmount], 'Tạo đơn hàng thành công', 201);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 400);
        }
    }
}