<?php
declare(strict_types=1);

namespace Controllers\User;

use Core\BaseController;
use Core\Database;
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

    public function changePassword(): void
    {
        $payload = Middleware::requireAuth();
        $body = $this->getBody();
        $userId = $payload['user_id'];

        $oldPassword = $body['current_password'] ?? '';
        $newPassword = $body['new_password'] ?? '';
        $confirmPassword = $body['confirm_password'] ?? '';

        if (empty($oldPassword) || empty($newPassword)) {
            $this->error('Vui lòng điền đầy đủ các trường yêu cầu.', 400);
        }

        if ($newPassword !== $confirmPassword) {
            $this->error('Mật khẩu mới và mật khẩu xác nhận không khớp.', 400);
        }

        if (strlen($newPassword) < 6) {
            $this->error('Mật khẩu mới phải có ít nhất 6 ký tự.', 400);
        }

        // Search for user in DB to verify current password
        $sql = 'SELECT `password` FROM `users` WHERE `id` = ?';
        $stmt = Database::getInstance()->getConnection()->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($oldPassword, $user['password'])) {
            $this->error('Mật khẩu hiện tại không chính xác.', 401);
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        if ($this->userModel->updatePassword($userId, $newHash)) {
            $this->logger->info('Admin changed password successfully', ['user_id' => $userId]);
            $this->success(null, 'Đổi mật khẩu thành công. Hệ thống sẽ đăng xuất.');
        } else {
            $this->error('Có lỗi xảy ra khi cập nhật mật khẩu.', 500);
        }
    }
}