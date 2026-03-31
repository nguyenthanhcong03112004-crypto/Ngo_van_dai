<?php
declare(strict_types=1);

namespace Controllers\User;

use Core\BaseController;
use Core\Middleware;
use Models\User;

class ProfileController extends BaseController
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    public function show(): void
    {
        $payload = Middleware::requireAuth();
        $user = $this->userModel->findById($payload['user_id']);
        if (!$user) {
            $this->error('User not found', 404);
        }
        unset($user['password']); // Ẩn password trước khi trả về
        $this->success($user);
    }

    public function update(): void
    {
        $payload = Middleware::requireAuth();
        $body = $this->getBody();
        
        if ($this->userModel->updateProfile($payload['user_id'], $body)) {
            $this->logger->info('User updated profile', ['user_id' => $payload['user_id']]);
            $this->success(null, 'Cập nhật thông tin thành công.');
        } else {
            $this->error('Không có thông tin nào được thay đổi hoặc lỗi cập nhật.', 400);
        }
    }

    public function uploadAvatar(): void
    {
        $payload = Middleware::requireAuth();
        $userId = $payload['user_id'];

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $this->error('Vui lòng chọn một file ảnh hợp lệ.', 400);
        }

        $file = $_FILES['avatar'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowedExts)) {
            $this->error('Chỉ chấp nhận định dạng ảnh (JPG, PNG, GIF, WEBP).', 400);
        }

        if ($file['size'] > 5 * 1024 * 1024) { // Tối đa 5MB
            $this->error('Dung lượng file không được vượt quá 5MB.', 400);
        }

        $uploadDir = dirname(__DIR__, 3) . '/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $destination = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $url = '/uploads/avatars/' . $filename;
            $this->userModel->updateAvatar($userId, $url);
            $this->logger->info('Avatar uploaded successfully', ['user_id' => $userId, 'url' => $url]);
            $this->success(['avatar_url' => $url], 'Cập nhật ảnh đại diện thành công.');
        } else {
            $this->error('Không thể lưu file trên máy chủ.', 500);
        }
    }
}