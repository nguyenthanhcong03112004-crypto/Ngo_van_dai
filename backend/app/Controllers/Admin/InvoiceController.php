<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Core\BaseController;
use Core\Middleware;
use Core\EmailService;
use Models\Order;

class InvoiceController extends BaseController
{
    public function sendEmail(int $orderId): void
    {
        Middleware::requireRole('admin');
        
        $orderModel = new Order();
        $order = $orderModel->getById($orderId);
        
        if (!$order) {
            $this->error('Không tìm thấy đơn hàng.', 404);
        }

        if (empty($order['user_email'])) {
            $this->error('Khách hàng này không có địa chỉ email.', 400);
        }

        EmailService::getInstance()->sendInvoicePdf($order['user_email'], $order['user_name'] ?? 'Khách hàng', $order);
        $this->success(null, 'Đã gửi Hóa đơn PDF qua email cho khách hàng thành công.');
    }
}