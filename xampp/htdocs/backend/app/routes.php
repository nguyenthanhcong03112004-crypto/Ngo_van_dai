<?php
declare(strict_types=1);

/**
 * Route definitions — wires URL patterns to Controller actions.
 * $router is injected from public/index.php
 */

use Controllers\AuthController;
use Controllers\User\CheckoutController;
use Controllers\User\OrderController as UserOrderController;
use Controllers\User\WishlistController;
use Controllers\User\ProfileController;
use Controllers\User\ProductController as UserProductController;
use Controllers\User\ReviewController;
use Controllers\Admin\OrderController as AdminOrderController;
use Controllers\Admin\ProductController as AdminProductController;
use Controllers\Admin\AnalyticsController;
use Controllers\Admin\CustomerController;
use Controllers\Admin\VoucherController as AdminVoucherController;
use Controllers\LogController;
use Controllers\NotificationController;
use Controllers\Admin\InvoiceController;
use Controllers\Admin\ReviewController as AdminReviewController;
use Controllers\Sales\SalesController;

// ─── Auth ────────────────────────────────────────────────────────────────────
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);

// ─── User: Products & Vouchers ────────────────────────────────────────────────
$router->get('/api/user/products', [UserProductController::class, 'index']);
$router->get('/api/user/products/{id}', [UserProductController::class, 'show']);
$router->get('/api/user/vouchers', [UserProductController::class, 'vouchers']);

// ─── System / Remote Logging ─────────────────────────────────────────────────
$router->post('/api/logs', [LogController::class, 'store']);

// ─── Notifications (User & Admin) ────────────────────────────────────────────
$router->get('/api/notifications', [NotificationController::class, 'index']);
$router->get('/api/notifications/unread-count', [NotificationController::class, 'unreadCount']);
$router->put('/api/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
$router->put('/api/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

// ─── User: Checkout ──────────────────────────────────────────────────────────
$router->post('/api/user/checkout', [CheckoutController::class, 'create']);

// ─── User: Orders ────────────────────────────────────────────────────────────
$router->get('/api/user/orders', [UserOrderController::class, 'index']);
$router->get('/api/user/orders/{id}', [UserOrderController::class, 'show']);
$router->put('/api/user/orders/{id}/cancel', [UserOrderController::class, 'cancel']);
$router->post('/api/user/orders/{id}/upload-receipt', [UserOrderController::class, 'uploadReceipt']);
$router->get('/api/user/orders/{id}/chat', [UserOrderController::class, 'getChat']);
$router->post('/api/user/orders/{id}/chat', [UserOrderController::class, 'sendChat']);

// ─── User: Wishlist ──────────────────────────────────────────────────────────
$router->get('/api/user/wishlist', [WishlistController::class, 'index']);
$router->post('/api/user/wishlist', [WishlistController::class, 'add']);
$router->delete('/api/user/wishlist/{product_id}', [WishlistController::class, 'remove']);

// ─── User: Reviews ───────────────────────────────────────────────────────────
$router->get('/api/products/{id}/reviews', [ReviewController::class, 'index']);
$router->post('/api/products/{id}/reviews', [ReviewController::class, 'store']);
$router->get('/api/products/{id}/eligible-orders', [ReviewController::class, 'eligibleOrders']);

// ─── User: Profile ───────────────────────────────────────────────────────────
$router->get('/api/user/profile', [ProfileController::class, 'show']);
$router->put('/api/user/profile', [ProfileController::class, 'update']);
$router->put('/api/user/change-password', [ProfileController::class, 'changePassword']);
$router->post('/api/user/avatar', [ProfileController::class, 'uploadAvatar']);

// ─── Admin: Orders ───────────────────────────────────────────────────────────
$router->get('/api/admin/orders', [AdminOrderController::class, 'index']);
$router->get('/api/admin/orders/{id}', [AdminOrderController::class, 'show']);
$router->put('/api/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
$router->get('/api/admin/orders/{id}/chat', [AdminOrderController::class, 'getChat']);
$router->post('/api/admin/orders/{id}/chat', [AdminOrderController::class, 'sendChat']);
$router->post('/api/admin/orders/{id}/send-invoice', [InvoiceController::class, 'sendEmail']);

// ─── Admin: Products ─────────────────────────────────────────────────────────
$router->get('/api/admin/products', [AdminProductController::class, 'index']);
$router->post('/api/admin/products', [AdminProductController::class, 'create']);
$router->put('/api/admin/products/{id}', [AdminProductController::class, 'update']);
$router->delete('/api/admin/products/{id}', [AdminProductController::class, 'delete']);

// ─── Admin: Customers ────────────────────────────────────────────────────────
$router->get('/api/admin/customers', [CustomerController::class, 'index']);
$router->get('/api/admin/customers/export', [CustomerController::class, 'export']);
$router->get('/api/admin/customers/{id}', [CustomerController::class, 'show']);
$router->put('/api/admin/customers/{id}/status', [CustomerController::class, 'updateStatus']);

// ─── Admin: Vouchers ─────────────────────────────────────────────────────────
$router->get('/api/admin/vouchers', [AdminVoucherController::class, 'index']);
$router->post('/api/admin/vouchers', [AdminVoucherController::class, 'create']);

// ─── Admin: Analytics ────────────────────────────────────────────────────────
$router->get('/api/admin/analytics', [AnalyticsController::class, 'index']);

// ─── Admin: Reviews ──────────────────────────────────────────────────────────
$router->get('/api/admin/reviews', [AdminReviewController::class, 'index']);
$router->get('/api/admin/reviews/recent', [AdminReviewController::class, 'recent']);
$router->delete('/api/admin/reviews/{id}', [AdminReviewController::class, 'delete']);

// ─── Sales Staff ───────────────────────────────────────────────────────────────────
$router->get('/api/sales/cabinets', [SalesController::class, 'getCabinets']);
$router->post('/api/sales/cabinets', [SalesController::class, 'createCabinet']);
$router->get('/api/sales/categories', [SalesController::class, 'getCategories']);
$router->get('/api/sales/shelf', [SalesController::class, 'getShelf']);
$router->post('/api/sales/shelf/add', [SalesController::class, 'addToShelf']);
$router->post('/api/sales/shelf/remove', [SalesController::class, 'removeFromShelf']);

$router->get('/api/sales/inventory', [SalesController::class, 'getInventory']);
$router->post('/api/sales/inventory', [SalesController::class, 'createProduct']);
$router->put('/api/sales/inventory/{id}', [SalesController::class, 'updateProduct']);
$router->delete('/api/sales/inventory/{id}', [SalesController::class, 'deleteProduct']);
$router->get('/api/sales/inventory/export', [SalesController::class, 'exportInventory']);

$router->post('/api/sales/check-customer', [SalesController::class, 'checkCustomer']);
$router->post('/api/sales/create-order', [SalesController::class, 'createOrder']);
$router->post('/api/sales/confirm-payment', [SalesController::class, 'confirmPayment']);
$router->post('/api/sales/cancel-order', [SalesController::class, 'cancelOrder']);
$router->get('/api/sales/orders', [SalesController::class, 'getOrders']);
$router->get('/api/sales/orders/export', [SalesController::class, 'exportOrders']);
$router->get('/api/sales/orders/{id}/pdf', [SalesController::class, 'generateInvoicePDF']);

$router->get('/api/sales/inventory/{id}/orders', [SalesController::class, 'getProductOrders']);
$router->get('/api/sales/users', [SalesController::class, 'getUsers']);
$router->post('/api/sales/users', [SalesController::class, 'createUser']);
$router->put('/api/sales/users/{id}', [SalesController::class, 'updateUser']);
$router->delete('/api/sales/users/{id}', [SalesController::class, 'deleteUser']);
