<?php
declare(strict_types=1);

/**
 * Route definitions — wires URL patterns to Controller actions.
 * $router is injected from public/index.php
 */

use Controllers\AuthController;
use Controllers\User\CheckoutController;
use Controllers\User\OrderController     as UserOrderController;
use Controllers\User\WishlistController;
use Controllers\User\ProfileController;
use Controllers\User\ProductController   as UserProductController;
use Controllers\Admin\OrderController    as AdminOrderController;
use Controllers\Admin\ProductController  as AdminProductController;
use Controllers\Admin\AnalyticsController;
use Controllers\Admin\CustomerController;

// ─── Auth ────────────────────────────────────────────────────────────────────
$router->post('/api/auth/login',    [AuthController::class, 'login']);
$router->post('/api/auth/register', [AuthController::class, 'register']);

// ─── User: Products & Vouchers ────────────────────────────────────────────────
$router->get('/api/user/products',          [UserProductController::class, 'index']);
$router->get('/api/user/products/{id}',     [UserProductController::class, 'show']);
$router->get('/api/user/vouchers',          [UserProductController::class, 'vouchers']);

// ─── User: Checkout ──────────────────────────────────────────────────────────
$router->post('/api/user/checkout', [CheckoutController::class, 'create']);

// ─── User: Orders ────────────────────────────────────────────────────────────
$router->get('/api/user/orders',                        [UserOrderController::class, 'index']);
$router->get('/api/user/orders/{id}',                   [UserOrderController::class, 'show']);
$router->post('/api/user/orders/{id}/upload-receipt',   [UserOrderController::class, 'uploadReceipt']);
$router->get('/api/user/orders/{id}/chat',              [UserOrderController::class, 'getChat']);
$router->post('/api/user/orders/{id}/chat',             [UserOrderController::class, 'sendChat']);

// ─── User: Wishlist ──────────────────────────────────────────────────────────
$router->get('/api/user/wishlist',                    [WishlistController::class, 'index']);
$router->post('/api/user/wishlist',                   [WishlistController::class, 'add']);
$router->delete('/api/user/wishlist/{product_id}',    [WishlistController::class, 'remove']);

// ─── User: Profile ───────────────────────────────────────────────────────────
$router->get('/api/user/profile', [ProfileController::class, 'show']);
$router->put('/api/user/profile', [ProfileController::class, 'update']);

// ─── Admin: Orders ───────────────────────────────────────────────────────────
$router->get('/api/admin/orders',                       [AdminOrderController::class, 'index']);
$router->get('/api/admin/orders/{id}',                  [AdminOrderController::class, 'show']);
$router->put('/api/admin/orders/{id}/status',           [AdminOrderController::class, 'updateStatus']);
$router->get('/api/admin/orders/{id}/chat',             [AdminOrderController::class, 'getChat']);
$router->post('/api/admin/orders/{id}/chat',            [AdminOrderController::class, 'sendChat']);

// ─── Admin: Products ─────────────────────────────────────────────────────────
$router->get('/api/admin/products',          [AdminProductController::class, 'index']);
$router->post('/api/admin/products',         [AdminProductController::class, 'create']);
$router->put('/api/admin/products/{id}',     [AdminProductController::class, 'update']);
$router->delete('/api/admin/products/{id}',  [AdminProductController::class, 'delete']);

// ─── Admin: Customers ────────────────────────────────────────────────────────
$router->get('/api/admin/customers',       [CustomerController::class, 'index']);
$router->get('/api/admin/customers/{id}',  [CustomerController::class, 'show']);

// ─── Admin: Analytics ────────────────────────────────────────────────────────
$router->get('/api/admin/analytics', [AnalyticsController::class, 'index']);
