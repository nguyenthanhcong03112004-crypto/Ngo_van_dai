<?php
declare(strict_types=1);

namespace Controllers\User;

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
        $this->chatModel = new DisputeChat();
    }

    public function index(): void
    {
        $payload = Middleware::requireAuth();
        $userId = $payload['user_id'];

        $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
        $year = isset($_GET['year']) ? (int)$_GET['year'] : null;

        $this->success($this->orderModel->getByUser($userId, $month, $year));
    }

    public function show(int $id): void
    {
        $payload = Middleware::requireAuth();
        if (!$this->orderModel->belongsToUser($id, $payload['user_id'])) {
            $this->error('Order not found or access denied', 404);
        }
        $order = $this->orderModel->getById($id);
        $this->success($order);
    }

    public function cancel(int $id): void
    {
        $payload = Middleware::requireAuth();
        $userId = $payload['user_id'];

        if (!$this->orderModel->belongsToUser($id, $userId)) {
            $this->error('Order not found or access denied', 404);
        }

        $order = $this->orderModel->getById($id);

        if ($order['status'] !== 'pending') {
            $this->error('Chỉ có thể hủy đơn hàng ở trạng thái "Chờ xác nhận".', 409); // 409 Conflict
        }

        if ($this->orderModel->updateStatus($id, 'cancelled')) {
            $this->logger->info('User cancelled order', ['order_id' => $id, 'user_id' => $userId]);
            $this->success(null, 'Đơn hàng đã được hủy thành công.');
        } else {
            $this->error('Không thể hủy đơn hàng. Vui lòng thử lại.', 500);
        }
    }

    public function uploadReceipt(int $id): void
    {
        $payload = Middleware::requireAuth();
        if (!$this->orderModel->belongsToUser($id, $payload['user_id'])) {
            $this->error('Order not found or access denied', 404);
        }

        $order = $this->orderModel->getById($id);
        if ($order['status'] !== 'pending') {
            $this->error('Chỉ có thể tải lên biên lai cho đơn hàng chờ xác nhận.', 409);
        }

        if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
            $this->error('Vui lòng chọn một file hợp lệ.', 400);
        }

        $file = $_FILES['receipt'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $this->error('Chỉ chấp nhận định dạng ảnh (JPG, PNG, GIF, WEBP).', 400);
        }

        if ($file['size'] > 5 * 1024 * 1024) { // Tối đa 5MB
            $this->error('Dung lượng file không được vượt quá 5MB.', 400);
        }

        // Đường dẫn vật lý đến thư mục uploads
        $uploadDir = dirname(__DIR__, 3) . '/uploads/receipts/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename = 'receipt_' . $id . '_' . time() . '.' . $ext;
        $destination = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $url = '/uploads/receipts/' . $filename;
            if ($this->orderModel->updateReceiptUrl($id, $url)) {
                $this->logger->info('Receipt uploaded successfully', ['order_id' => $id, 'url' => $url]);
                $this->success(['url' => $url], 'Tải lên minh chứng thành công. Đơn hàng đang chờ duyệt.');
            } else {
                $this->error('Không thể cập nhật trạng thái đơn hàng.', 500);
            }
        } else {
            $this->error('Không thể lưu file trên máy chủ.', 500);
        }
    }

    public function getChat(int $id): void
    {
        $payload = Middleware::requireAuth();
        if (!$this->orderModel->belongsToUser($id, $payload['user_id'])) {
            $this->error('Order not found or access denied', 404);
        }

        try {
            $messages = $this->chatModel->getByOrder($id);
            $this->success($messages, 'Chat history fetched');
        } catch (\Exception $e) {
            $this->logger->error("Error fetching chat for order {$id}: " . $e->getMessage());
            $this->error('Internal Server Error', 500);
        }
    }

    public function sendChat(int $id): void
    {
        $payload = Middleware::requireAuth();
        if (!$this->orderModel->belongsToUser($id, $payload['user_id'])) {
            $this->error('Order not found or access denied', 404);
        }

        $body = $this->getBody();
        if (empty($body['message'])) {
            $this->error('Missing message', 422);
        }

        try {
            $this->chatModel->create([
                'order_id'    => $id,
                'user_id'     => (int)$payload['user_id'],
                'message'     => $body['message'],
                'sender_role' => 'user'
            ]);
            $this->success(null, 'Message sent');
        } catch (\Exception $e) {
            $this->logger->error("Error sending chat message for order {$id}: " . $e->getMessage());
            $this->error('Internal Server Error', 500);
        }
    }
}