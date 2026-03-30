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
        $this->userModel = new User();
    }

    /**
     * POST /api/auth/login
     * Body: { "email": "...", "password": "..." }
     */
    public function login(): void
    {
        $body = $this->getBody();
        $missing = $this->validate($body, ['email', 'password']);

        if (!empty($missing)) {
            $this->error('Missing fields: ' . implode(', ', $missing), 422);
        }

        $user = $this->userModel->findByEmail($body['email']);

        if (!$user || !$this->userModel->verifyPassword($body['password'], $user['password'])) {
            $this->error('Invalid email or password', 401);
        }

        $token = Middleware::generateToken([
            'user_id' => $user['id'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ]);

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
            $this->error('Email is already registered', 409);
        }

        $userId = $this->userModel->create($body);
        $user   = $this->userModel->findById($userId);

        $token = Middleware::generateToken([
            'user_id' => $user['id'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ]);

        $this->success([
            'token' => $token,
            'user'  => $user,
        ], 'Registration successful', 201);
    }
}
