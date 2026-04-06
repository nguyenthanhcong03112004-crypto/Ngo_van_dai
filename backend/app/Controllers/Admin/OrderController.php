<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Core\BaseController;
use Core\Middleware;
use Models\Order;
use Models\DisputeChat;

class OrderController extends BaseController
{
    private Order $orderModel;
    private DisputeChat $chatModel;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->chatModel  = new DisputeChat();
    }

    /**
     * GET /api/admin/orders
     */
    public function index(): void
    {
        Middleware::requireRole('admin');
        
        $status = $_GET['status'] ?? null;
        $page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

        try {
            $orders = $this->orderModel->getAll($status, $page, $limit);
            $this->success($orders, 'Admin orders fetched successfully');
        } catch (\Exception $e) {
            $this->logger->error("Admin Error fetching orders: " . $e->getMessage());
            $this->error('Internal Server Error', 500);
        }
    }

    /**
     * GET /api/admin/orders/{id}
     */
    public function show(string $id): void
    {
        Middleware::requireRole('admin');
        $oid = (int)$id;

        try {
            $order = $this->orderModel->getById($oid);
            if (!$order) {
                $this->error('Order not found', 404);
            }
            $this->success($order, 'Order details fetched');
        } catch (\Exception $e) {
            $this->logger->error("Admin Error fetching order {$oid}: " . $e->getMessage());
            $this->error('Internal Server Error', 500);
        }
    }

    /**
     * PUT /api/admin/orders/{id}/status
     */
    public function updateStatus(string $id): void
    {
        Middleware::requireRole('admin');
        $oid = (int)$id;
        $body = $this->getBody();

        if (empty($body['status'])) {
            $this->error('Missing status', 422);
        }

        try {
            $success = $this->orderModel->updateStatus($oid, $body['status']);
            if ($success) {
                $this->success(null, "Order status updated to {$body['status']}");
            } else {
                $this->error('Failed to update order status');
            }
        } catch (\Exception $e) {
            $this->logger->error("Admin Error updating order status for {$oid}: " . $e->getMessage());
            $this->error('Internal Server Error', 500);
        }
    }

    /**
     * GET /api/admin/orders/{id}/chat
     */
    public function getChat(string $id): void
    {
        Middleware::requireRole('admin');
        $oid = (int)$id;

        try {
            $messages = $this->chatModel->getByOrder($oid);
            $this->success($messages, 'Chat history fetched');
        } catch (\Exception $e) {
            $this->logger->error("Admin Error fetching chat for order {$oid}: " . $e->getMessage());
            $this->error('Internal Server Error', 500);
        }
    }

    /**
     * POST /api/admin/orders/{id}/chat
     */
    public function sendChat(string $id): void
    {
        $payload = Middleware::requireRole('admin');
        $oid = (int)$id;
        $body = $this->getBody();

        if (empty($body['message'])) {
            $this->error('Missing message', 422);
        }

        try {
            $this->chatModel->create([
                'order_id'    => $oid,
                'user_id'     => (int)$payload['user_id'],
                'message'     => $body['message'],
                'sender_role' => 'admin'
            ]);
            $this->success(null, 'Message sent');
        } catch (\Exception $e) {
            $this->logger->error("Admin Error sending chat message for order {$oid}: " . $e->getMessage());
            $this->error('Internal Server Error', 500);
        }
    }
}
