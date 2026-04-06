<?php
declare(strict_types=1);

namespace Controllers\User;

use Core\BaseController;
use Core\Middleware;
use Models\Review;
use Models\Order;

class ReviewController extends BaseController
{
    private Review $reviewModel;

    public function __construct()
    {
        parent::__construct();
        $this->reviewModel = new Review();
    }

    public function index(int $productId): void
    {
        $rating = isset($_GET['rating']) ? (int)$_GET['rating'] : null;
        $reviews = $this->reviewModel->getByProduct($productId, $rating);
        $summary = $this->reviewModel->getSummary($productId);
        
        $this->success([
            'reviews' => $reviews,
            'summary' => $summary
        ]);
    }

    public function store(int $productId): void
    {
        $payload = Middleware::requireAuth();
        $body = $this->getBody();
        
        $missing = $this->validate($body, ['rating', 'comment', 'order_id']);
        if (!empty($missing)) $this->error('Thiếu thông tin đánh giá', 422);
        if ($body['rating'] < 1 || $body['rating'] > 5) $this->error('Số sao không hợp lệ', 400);

        $orderModel = new Order();
        if (!$orderModel->verifyPurchase($payload['user_id'], $productId, (int)$body['order_id'])) {
            $this->error('Bạn chưa mua sản phẩm này trong đơn hàng đã chọn hoặc đơn hàng chưa hoàn thành.', 403);
        }

        if ($this->reviewModel->hasReviewed($payload['user_id'], $productId, (int)$body['order_id'])) {
            $this->error('Bạn đã đánh giá sản phẩm này cho đơn hàng này rồi.', 409);
        }

        $this->reviewModel->create(['product_id' => $productId, 'user_id' => $payload['user_id'], 'order_id' => $body['order_id'], 'rating' => $body['rating'], 'comment' => $body['comment']]);
        $this->success(null, 'Cảm ơn bạn đã đánh giá sản phẩm!', 201);
    }

    public function eligibleOrders(int $productId): void
    {
        $payload = Middleware::requireAuth();
        $userId = $payload['user_id'];
        
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            'SELECT o.id, o.created_at 
             FROM `orders` o 
             JOIN `order_items` oi ON o.id = oi.order_id 
             WHERE o.user_id = ? AND oi.product_id = ? AND o.status = "completed"
               AND NOT EXISTS (SELECT 1 FROM `product_reviews` pr WHERE pr.user_id = o.user_id AND pr.product_id = oi.product_id AND pr.order_id = o.id)
             ORDER BY o.created_at DESC'
        );
        $stmt->execute([$userId, $productId]);
        $this->success($stmt->fetchAll());
    }
}