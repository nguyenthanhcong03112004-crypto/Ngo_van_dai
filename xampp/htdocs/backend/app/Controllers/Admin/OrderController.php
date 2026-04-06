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

        $message = $_POST['message'] ?? '';
        $body = $this->getBody();
        if (empty($message) && !empty($body['message'])) {
            $message = $body['message'];
        }

        $hasAttachment = isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK;

        if (empty(trim($message)) && !$hasAttachment) {
            $this->error('Missing message or attachment', 422);
        }

        $attachmentUrl = null;
        if ($hasAttachment) {
            $file = $_FILES['attachment'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov', 'pdf', 'doc', 'docx'])) {
                $this->error('Chỉ hỗ trợ file ảnh (jpg, png, webp), video (mp4, mov) và tài liệu (pdf, doc, docx).', 400);
            }
            if ($file['size'] > 10 * 1024 * 1024) {
                $this->error('Dung lượng tệp tin đính kèm không vượt quá 10MB.', 400);
            }

            $uploadDir = dirname(__DIR__, 4) . '/uploads/chats/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = 'admin_chat_' . $oid . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $attachmentUrl = '/uploads/chats/' . $filename;
            } else {
                $this->error('Lưu file thất bại. Vui lòng thử lại.', 500);
            }
        }

        try {
            $chatId = $this->chatModel->create([
                'order_id'    => $oid,
                'user_id'     => (int)$payload['user_id'],
                'message'     => $message,
                'sender_role' => 'admin',
                'attachment_url' => $attachmentUrl
            ]);

            // Fetch newly created message
            $stmt = \Core\Database::getInstance()->getConnection()->prepare(
                'SELECT dc.*, u.name AS sender_name 
                 FROM `dispute_chats` dc 
                 JOIN `users` u ON dc.user_id = u.id 
                 WHERE dc.id = ?'
            );
            $stmt->execute([$chatId]);
            $msg = $stmt->fetch();

            $this->success($msg, 'Message sent');
        } catch (\Exception $e) {
            $this->logger->error("Admin Error sending chat message for order {$oid}: " . $e->getMessage());
            $this->error('Internal Server Error', 500);
        }
    }
}
