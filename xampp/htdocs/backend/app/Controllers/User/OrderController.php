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

    public function show(string $id): void
    {
        $payload = Middleware::requireAuth();
        $oid = (int)$id;
        if (!$this->orderModel->belongsToUser($oid, $payload['user_id'])) {
            $this->error('Order not found or access denied', 404);
        }
        $order = $this->orderModel->getById($oid);
        $this->success($order);
    }

    public function cancel(string $id): void
    {
        $payload = Middleware::requireAuth();
        $userId = $payload['user_id'];
        $oid = (int)$id;

        if (!$this->orderModel->belongsToUser($oid, $userId)) {
            $this->error('Order not found or access denied', 404);
        }

        $order = $this->orderModel->getById($oid);

        if ($order['status'] !== 'pending') {
            $this->error('Chỉ có thể hủy đơn hàng ở trạng thái "Chờ xác nhận".', 409); // 409 Conflict
        }

        if ($this->orderModel->updateStatus($oid, 'cancelled')) {
            $this->logger->info('User cancelled order', ['order_id' => $oid, 'user_id' => $userId]);
            $this->success(null, 'Đơn hàng đã được hủy thành công.');
        } else {
            $this->error('Không thể hủy đơn hàng. Vui lòng thử lại.', 500);
        }
    }

    public function uploadReceipt(string $id): void
    {
        $payload = Middleware::requireAuth();
        $oid = (int)$id;
        if (!$this->orderModel->belongsToUser($oid, $payload['user_id'])) {
            $this->error('Order not found or access denied', 404);
        }

        $order = $this->orderModel->getById($oid);
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

        // Đường dẫn vật lý đến thư mục uploads ở ngoài root
        $uploadDir = dirname(__DIR__, 4) . '/uploads/receipts/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename = 'receipt_' . $id . '_' . time() . '.' . $ext;
        $destination = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $url = '/uploads/receipts/' . $filename;
            if ($this->orderModel->updateReceiptUrl($oid, $url)) {
                $this->logger->info('Receipt uploaded successfully', ['order_id' => $oid, 'url' => $url]);
                $this->success(['url' => $url], 'Tải lên minh chứng thành công. Đơn hàng đang chờ duyệt.');
            } else {
                $this->error('Không thể cập nhật trạng thái đơn hàng.', 500);
            }
        } else {
            $this->error('Không thể lưu file trên máy chủ.', 500);
        }
    }

    public function getChat(string $id): void
    {
        $payload = Middleware::requireAuth();
        $oid = (int)$id;
        if (!$this->orderModel->belongsToUser($oid, $payload['user_id'])) {
            $this->error('Order not found or access denied', 404);
        }

        try {
            $messages = $this->chatModel->getByOrder($oid);
            $this->success($messages, 'Chat history fetched');
        } catch (\Exception $e) {
            $this->logger->error("Error fetching chat for order {$oid}: " . $e->getMessage());
            $this->error('Internal Server Error', 500);
        }
    }

    public function sendChat(string $id): void
    {
        $payload = Middleware::requireAuth();
        $oid = (int)$id;
        if (!$this->orderModel->belongsToUser($oid, $payload['user_id'])) {
            $this->error('Order not found or access denied', 404);
        }

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

            $filename = 'chat_' . $oid . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
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
                'sender_role' => 'user',
                'attachment_url' => $attachmentUrl
            ]);

            // Auto-update order status to 'disputed' when user starts/continues chat
            $this->orderModel->updateStatus($oid, 'disputed');

            // Fetch the newly created message with sender name
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
            $this->logger->error("Error sending chat message for order {$oid}: " . $e->getMessage());
            $this->error('Internal Server Error', 500);
        }
    }
}