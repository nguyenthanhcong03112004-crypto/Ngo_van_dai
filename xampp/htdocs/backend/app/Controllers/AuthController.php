<?php
declare(strict_types=1);

namespace Controllers;

use Core\BaseController;
use Core\Middleware;
use Models\User;

class AuthController extends BaseController
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    /**
     * POST /api/auth/login
     * Body: { "email": "...", "password": "..." }
     */
    public function login(): void
    {
        $body = $this->getBody();
        $this->logger->info('Login attempt', ['email' => $body['email'] ?? '']);

        $missing = $this->validate($body, ['email', 'password']);

        if (!empty($missing)) {
            $this->error('Missing fields: ' . implode(', ', $missing), 422);
        }

        $user = $this->userModel->findByEmail($body['email']);

        if (!$user || !$this->userModel->verifyPassword($body['password'], $user['password'])) {
            $this->logger->warning('Failed login attempt: Invalid credentials', ['email' => $body['email']]);
            $this->error('Invalid email or password', 401);
        }

        if ($user['status'] === 'locked') {
            $this->logger->warning('Failed login attempt: Account is locked', ['email' => $body['email']]);
            $this->error('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.', 403);
        }

        $token = Middleware::generateToken([
            'user_id' => $user['id'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ]);

        $this->logger->info('User logged in successfully', ['user_id' => $user['id']]);

        $this->success([
            'token' => $token,
            'user'  => [
                'id'         => $user['id'],
                'name'       => $user['name'],
                'email'      => $user['email'],
                'role'       => $user['role'],
                'avatar_url' => $user['avatar_url'],
            ],
        ], 'Login successful');
    }

    /**
     * POST /api/auth/register
     * Body: { "name": "...", "email": "...", "password": "..." }
     */
    public function register(): void
    {
        $body = $this->getBody();
        $this->logger->info('Registration attempt', ['email' => $body['email'] ?? '']);

        $missing = $this->validate($body, ['name', 'email', 'password']);

        if (!empty($missing)) {
            $this->error('Missing fields: ' . implode(', ', $missing), 422);
        }

        if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email format', 422);
        }

        if (strlen($body['password']) < 6) {
            $this->error('Password must be at least 6 characters', 422);
        }

        if ($this->userModel->findByEmail($body['email'])) {
            $this->logger->warning('Registration failed: Email already exists', ['email' => $body['email']]);
            $this->error('Email is already registered', 409);
        }

        $userId = $this->userModel->create($body);
        $user   = $this->userModel->findById($userId);

        // Send welcome email
        \Core\EmailService::getInstance()->sendWelcomeEmail($user['email'], $user['name']);

        $token = Middleware::generateToken([
            'user_id' => $user['id'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ]);

        $this->logger->info('User registered successfully', ['user_id' => $user['id']]);

        $this->success([
            'token' => $token,
            'user'  => $user,
        ], 'Registration successful', 201);
    }

    /**
     * POST /api/auth/forgot-password
     * Body: { "email": "..." }
     */
    public function forgotPassword(): void
    {
        $body = $this->getBody();
        if (empty($body['email'])) {
            $this->error('Vui lòng cung cấp email.', 422);
        }

        $user = $this->userModel->findByEmail($body['email']);
        if ($user) {
            $token = bin2hex(random_bytes(32)); // Tạo chuỗi ngẫu nhiên 64 ký tự
            $expiresAt = date('Y-m-d H:i:s', time() + 900); // Token hết hạn sau 15 phút
            
            $this->userModel->saveResetToken($user['email'], $token, $expiresAt);
            
            // URL tới trang Đặt lại mật khẩu ở Frontend (ví dụ port 5180 hoặc 5173 tuỳ bạn đang chạy frontend)
            $resetLink = "http://localhost:5180/reset-password.html?token=" . $token;
            \Core\EmailService::getInstance()->sendPasswordResetEmail($user['email'], $resetLink);
        }

        // Trả về thông báo thành công dù email có tồn tại hay không (Tránh lộ lọt danh sách user)
        $this->success(null, 'Nếu email tồn tại trong hệ thống, bạn sẽ nhận được hướng dẫn đặt lại mật khẩu.');
    }

    /**
     * POST /api/auth/reset-password
     * Body: { "token": "...", "new_password": "..." }
     */
    public function resetPassword(): void
    {
        $body = $this->getBody();
        $missing = $this->validate($body, ['token', 'new_password']);
        if (!empty($missing)) $this->error('Thiếu thông tin bắt buộc.', 422);
        if (strlen($body['new_password']) < 6) $this->error('Mật khẩu mới phải có ít nhất 6 ký tự.', 422);
        $user = $this->userModel->findByResetToken($body['token']);
        if (!$user) $this->error('Mã khôi phục không hợp lệ hoặc đã hết hạn.', 400);
        $this->userModel->updatePassword($user['id'], password_hash($body['new_password'], PASSWORD_BCRYPT));
        $this->success(null, 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.');
    }
}
